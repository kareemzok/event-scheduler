<?php
require_once '../includes/db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'register') {
        register($pdo);
    } elseif ($action === 'login') {
        login($pdo);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'logout') {
    logout();
}

function logout()
{
    session_destroy();
    header('Location: ../index.php');
    exit;
}

function register($pdo)
{
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        header('Location: ../index.php?error=missing_fields');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';

        header('Location: ../dashboard.php');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header('Location: ../index.php?error=already_exists');
        } else {
            header('Location: ../index.php?error=system_error');
        }
    }
    exit;
}

function login($pdo)
{
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        header('Location: ../index.php?error=missing_fields');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: ../dashboard.php');
    } else {
        header('Location: ../index.php?error=invalid_credentials');
    }
    exit;
}
