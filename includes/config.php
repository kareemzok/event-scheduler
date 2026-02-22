<?php
// Simple .env loader
function loadEnv($path)
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// AI Configuration
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'gpt-4o-mini');
define('OPENAI_TEMPERATURE', getenv('OPENAI_TEMPERATURE') ?: 0.7);
define('AI_DAILY_LIMIT', getenv('AI_DAILY_LIMIT') ?: 3);
define('AI_ENABLED', filter_var(getenv('AI_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN));

// App Settings
define('APP_NAME', getenv('APP_NAME') ?: 'EventFlow AI');

// Dynamic Base URL Detection
if (getenv('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL'));
} else {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (str_ends_with($script_name, '/api')) {
        $script_name = dirname($script_name);
    }
    // Ensure trailing slash
    $base_url = $protocol . "://" . $host . rtrim($script_name, '/\\') . '/';
    define('BASE_URL', $base_url);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
