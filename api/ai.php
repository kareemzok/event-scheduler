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
        'model' => 'gpt-3.5-turbo',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

if ($action === 'suggest_description') {
    $title = $_POST['title'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!$title) {
        echo json_encode(['error' => 'Title is required for suggestions']);
        exit;
    }

    $prompt = "Generate a professional and engaging event description for an event titled '$title' located at '$location'. Keep it under 100 words.";
    $aiResponse = callOpenAI($prompt);

    if (isset($aiResponse['choices'][0]['message']['content'])) {
        echo json_encode(['success' => true, 'description' => $aiResponse['choices'][0]['message']['content']]);
    } else {
        // Fallback for demo if no key
        echo json_encode(['success' => true, 'description' => "Join us for an exclusive '$title' at $location. A professional gathering for networking and innovation."]);
    }
} elseif ($action === 'generate_event') {
    $prompt_input = $_POST['prompt'] ?? '';

    if (!$prompt_input) {
        echo json_encode(['error' => 'Prompt is required']);
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
            echo json_encode(['success' => true, 'event' => $eventData]);
            exit;
        }
    }

    // Fallback/Error
    echo json_encode(['error' => 'AI could not generate event. Please try again or provide more details.']);
}
?>