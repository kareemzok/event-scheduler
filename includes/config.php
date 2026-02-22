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

if (!function_exists('phpErrorLevelToName')) {
    function phpErrorLevelToName(int $level): string
    {
        $levels = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        return $levels[$level] ?? (string) $level;
    }
}

if (!function_exists('logAppThrowable')) {
    function logAppThrowable(Throwable $exception, string $context = ''): void
    {
        $prefix = $context !== '' ? '[' . $context . '] ' : '';
        error_log(sprintf(
            '%s%s: %s in %s:%d',
            $prefix,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));
        error_log($exception->getTraceAsString());
    }
}

if (!function_exists('setupAppErrorLogging')) {
    function setupAppErrorLogging(): void
    {
        $storagePath = __DIR__ . '/../storage';
        if (!is_dir($storagePath) && !mkdir($storagePath, 0777, true) && !is_dir($storagePath)) {
            return;
        }

        $errorLogPath = $storagePath . '/error.log';
        if (!file_exists($errorLogPath)) {
            $handle = @fopen($errorLogPath, 'ab');
            if ($handle !== false) {
                fclose($handle);
            }
        }

        ini_set('log_errors', '1');
        ini_set('error_log', $errorLogPath);
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            error_log(sprintf(
                '[PHP %s] %s in %s:%d',
                phpErrorLevelToName($severity),
                $message,
                $file,
                $line
            ));

            return true;
        });

        set_exception_handler(function (Throwable $exception): void {
            logAppThrowable($exception, 'Uncaught exception');

            if (!headers_sent()) {
                http_response_code(500);
            }
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error === null) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array((int) $error['type'], $fatalTypes, true)) {
                return;
            }

            error_log(sprintf(
                '[Fatal %s] %s in %s:%d',
                phpErrorLevelToName((int) $error['type']),
                $error['message'] ?? 'Unknown fatal error',
                $error['file'] ?? 'unknown',
                (int) ($error['line'] ?? 0)
            ));
        });
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');
setupAppErrorLogging();

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
