<?php
// Configuration Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'event_scheduler');
define('DB_USER', 'root');
define('DB_PASS', '');

// AI API Key (Placeholder)
define('OPENAI_API_KEY', '');

// App Settings
define('APP_NAME', 'EventFlow AI');
define('BASE_URL', 'http://localhost/event-scheduler-app/');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
