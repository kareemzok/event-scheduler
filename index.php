<?php
require_once 'includes/config.php';

if (!empty($_GET['invite_token'])) {
    $_SESSION['pending_invite_token'] = substr(trim($_GET['invite_token']), 0, 64);
}

if (isset($_SESSION['user_id'])) {
    $redirect = 'dashboard.php';
    if (!empty($_SESSION['pending_invite_token'])) {
        $redirect .= '?invite_token=' . rawurlencode($_SESSION['pending_invite_token']);
        unset($_SESSION['pending_invite_token']);
    }
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo APP_NAME; ?> - Event Management Redefined
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body class="auth-page">
    <div id="login-container" class="glass-container auth-card">
        <h1 class="auth-title">EventFlow AI</h1>

        <?php if (isset($_GET['error'])): ?>
            <div style="color: #ef4444; margin-bottom: 20px; text-align: center; font-size: 0.9rem;">
                <?php
                $errors = [
                    'missing_fields' => 'All fields are required.',
                    'already_exists' => 'Username or Email already taken.',
                    'invalid_credentials' => 'Invalid username or password.',
                    'system_error' => 'A system error occurred. Please try again.'
                ];
                echo $errors[$_GET['error']] ?? 'An error occurred.';
                ?>
            </div>
        <?php endif; ?>

        <form action="api/auth.php?action=login" method="POST">
            <div class="form-group">
                <label for="login-user">Username or Email</label>
                <input type="text" id="login-user" name="username" class="form-control"
                    placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="login-pass">Password</label>
                <input type="password" id="login-pass" name="password" class="form-control" placeholder="••••••••"
                    required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="#" id="show-register">Register here</a>
        </div>
    </div>

    <div id="register-container" class="glass-container auth-card hidden">
        <h1 class="auth-title">Create Account</h1>
        <form action="api/auth.php?action=register" method="POST">
            <div class="form-group">
                <label for="reg-user">Username</label>
                <input type="text" id="reg-user" name="username" class="form-control" placeholder="Choose a username"
                    required>
            </div>
            <div class="form-group">
                <label for="reg-email">Email Address</label>
                <input type="email" id="reg-email" name="email" class="form-control" placeholder="you@example.com"
                    required>
            </div>
            <div class="form-group">
                <label for="reg-pass">Password</label>
                <input type="password" id="reg-pass" name="password" class="form-control" placeholder="••••••••"
                    required>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="#" id="show-login">Login here</a>
        </div>
    </div>

    <script>
        const btnShowRegister = document.getElementById('show-register');
        const btnShowLogin = document.getElementById('show-login');
        const loginContainer = document.getElementById('login-container');
        const registerContainer = document.getElementById('register-container');

        btnShowRegister.addEventListener('click', (e) => {
            e.preventDefault();
            loginContainer.classList.add('hidden');
            registerContainer.classList.remove('hidden');
        });

        btnShowLogin.addEventListener('click', (e) => {
            e.preventDefault();
            registerContainer.classList.add('hidden');
            loginContainer.classList.remove('hidden');
        });
    </script>
</body>

</html>
