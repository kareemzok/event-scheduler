<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$action = $_GET['action'] ?? '';

function callOpenAI($prompt)
{
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        return ['error' => 'OpenAI API Key not configured.'];
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-3.5-turbo',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => defined('OPENAI_TEMPERATURE') ? (float) OPENAI_TEMPERATURE : 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function getRemainingLimit($userId, $pdo)
{
    $date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT request_count FROM ai_usage_logs WHERE user_id = ? AND request_date = ?");
    $stmt->execute([$userId, $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $used = $row ? $row['request_count'] : 0;
    $limit = defined('AI_DAILY_LIMIT') ? (int) AI_DAILY_LIMIT : 3;

    return max(0, $limit - $used);
}

function incrementUsage($userId, $pdo)
{
    $date = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO ai_usage_logs (user_id, request_date, request_count) 
                          VALUES (?, ?, 1) 
                          ON DUPLICATE KEY UPDATE request_count = request_count + 1");
    $stmt->execute([$userId, $date]);
}

if ($action === 'get_remaining_limit') {
    echo json_encode(['success' => true, 'remaining' => getRemainingLimit($_SESSION['user_id'], $pdo)]);
    exit;
}

if ($action === 'suggest_description') {
    $userId = $_SESSION['user_id'];
    $remaining = getRemainingLimit($userId, $pdo);

    if ($remaining <= 0) {
        echo json_encode(['error' => 'Daily AI limit reached. Please try again tomorrow.']);
        exit;
    }

    $title = $_POST['title'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!$title) {
        echo json_encode(['error' => 'Title is required for suggestions']);
        exit;
    }

    $prompt = "Generate a professional and engaging event description for an event titled '$title' located at '$location'. Keep it under 100 words.";
    $aiResponse = callOpenAI($prompt);

    if (isset($aiResponse['choices'][0]['message']['content'])) {
        incrementUsage($userId, $pdo);
        echo json_encode([
            'success' => true,
            'description' => $aiResponse['choices'][0]['message']['content'],
            'remaining' => getRemainingLimit($userId, $pdo)
        ]);
    } else {
        // Fallback for demo if no key
        incrementUsage($userId, $pdo);
        echo json_encode([
            'success' => true,
            'description' => "Join us for an exclusive '$title' at $location. A professional gathering for networking and innovation.",
            'remaining' => getRemainingLimit($userId, $pdo)
        ]);
    }
} elseif ($action === 'generate_event') {
    $userId = $_SESSION['user_id'];
    $remaining = getRemainingLimit($userId, $pdo);

    if ($remaining <= 0) {
        echo json_encode(['error' => 'Daily AI limit reached. Please try again tomorrow.']);
        exit;
    }

    $prompt_input = $_POST['prompt'] ?? '';

    if (!$prompt_input) {
        echo json_encode(['error' => 'Prompt is required']);
        exit;
    }

    if (strlen($prompt_input) > 250) {
        echo json_encode(['error' => 'Prompt exceed 250 characters limit.']);
        exit;
    }

    $prompt = "Create a JSON object for an event based on this: '$prompt_input'. 
               Return strictly JSON with keys: 'title', 'description', 'location', 'event_date' (format Y-m-d H:i).";

    $aiResponse = callOpenAI($prompt);

    if (isset($aiResponse['choices'][0]['message']['content'])) {
        $content = trim($aiResponse['choices'][0]['message']['content']);
        // Extract JSON if AI surrounds it with markdown code blocks
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }
        $eventData = json_decode($content, true);
        if ($eventData) {
            incrementUsage($userId, $pdo);
            echo json_encode([
                'success' => true,
                'event' => $eventData,
                'remaining' => getRemainingLimit($userId, $pdo)
            ]);
            exit;
        }
    }

    // Fallback/Error
    echo json_encode(['error' => 'AI could not generate event. Please try again.']);
}
?>