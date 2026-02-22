<?php
require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    if (function_exists('logAppThrowable')) {
        logAppThrowable($e, 'Database connection failed');
    } else {
        error_log('Database connection failed: ' . $e->getMessage());
    }

    http_response_code(500);
    die('Database connection failed. Check storage/error.log for details.');
}
