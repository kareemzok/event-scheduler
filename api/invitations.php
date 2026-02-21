<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'invite') {
    $eventId = $_POST['event_id'] ?? 0;
    $targetUsername = $_POST['username'] ?? '';

    // Find user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$targetUsername, $targetUsername]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, invited_by, status) VALUES (?, ?, ?, 'invited')");
        $stmt->execute([$eventId, $targetUser['id'], $userId]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'User already invited or error occurred']);
    }
} elseif ($action === 'respond') {
    $eventId = $_POST['event_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    $allowed = ['attending', 'maybe', 'declined'];
    if (!in_array($status, $allowed)) {
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE event_participants SET status = ?, responded_at = CURRENT_TIMESTAMP WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$status, $eventId, $userId]);
    echo json_encode(['success' => true]);
} elseif ($action === 'list_invites') {
    $stmt = $pdo->prepare("
        SELECT e.*, u.username as inviter 
        FROM events e 
        JOIN event_participants ep ON e.id = ep.event_id 
        JOIN users u ON ep.invited_by = u.id
        WHERE ep.user_id = ? AND ep.status = 'invited'
    ");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
}
