<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profile') {
        $bio = $_POST['bio'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $pdo->prepare("UPDATE users SET bio = ?, email = ? WHERE id = ?");
        $stmt->execute([$bio, $email, $userId]);
        echo json_encode(['success' => true]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_profile') {
        $stmt = $pdo->prepare("SELECT username, email, bio, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetch());
    }
}
