<?php
/**
 * SAYAK LIBRARY - Advanced Error & Exception Logger
 * Produces structured, high-detail logs for fast & easy production debugging
 */

// Prevent direct script access
if (count(get_included_files()) == 1) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden.");
}

class AppLogger {
    private static ?string $logFile = null;

    /**
     * Initialize logger path
     */
    public static function getLogFile(): string {
        if (self::$logFile === null) {
            $dir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            self::$logFile = defined('ERROR_LOG_PATH') ? ERROR_LOG_PATH : $dir . 'error.log';
        }
        return self::$logFile;
    }

    /**
     * Mask sensitive values (passwords, tokens, secret keys)
     */
    public static function sanitizeData(array $data): array {
        $sensitiveKeys = ['password', 'passwd', 'pass', 'token', 'csrf', 'secret', 'card', 'cvv', 'auth', 'key'];
        $clean = [];

        foreach ($data as $key => $val) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $sk) {
                if (stripos((string)$key, $sk) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$key] = '******** [REDACTED]';
            } elseif (is_array($val)) {
                $clean[$key] = self::sanitizeData($val);
            } else {
                $clean[$key] = $val;
            }
        }
        return $clean;
    }

    /**
     * Extract a snippet of code around the error line
     */
    public static function getCodeSnippet(string $file, int $line, int $padding = 2): string {
        if (!is_file($file) || !is_readable($file)) {
            return "   [File not readable or dynamic eval]";
        }

        $lines = @file($file);
        if ($lines === false) {
            return "   [Unable to read file content]";
        }

        $start = max(0, $line - $padding - 1);
        $end = min(count($lines), $line + $padding);
        $output = '';

        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $indicator = ($lineNum === $line) ? ' >> ' : '    ';
            $output .= sprintf("%s%4d | %s", $indicator, $lineNum, rtrim($lines[$i])) . PHP_EOL;
        }

        return rtrim($output);
    }

    /**
     * Gather complete HTTP request & session context
     */
    public static function getRequestContext(): array {
        $context = [];

        // Request URL & Method
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'CLI');
        $context['request'] = "$method $protocol$host$uri";

        // IP Address
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $context['client_ip'] = explode(',', $ip)[0];

        // User Agent
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown / CLI';

        // Referrer
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $context['referer'] = $_SERVER['HTTP_REFERER'];
        }

        // Active Session User
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'])) {
            $uid = $_SESSION['user_id'];
            $role = $_SESSION['role_code'] ?? 'UNKNOWN';
            $name = $_SESSION['full_name'] ?? 'N/A';
            $email = $_SESSION['email'] ?? 'N/A';
            $context['user'] = "ID: {$uid} | Role: {$role} | Name: {$name} | Email: {$email}";
        } else {
            $context['user'] = "Guest / Not Logged In";
        }

        // Request Payload
        if (!empty($_GET)) {
            $context['get_params'] = self::sanitizeData($_GET);
        }
        if (!empty($_POST)) {
            $context['post_data'] = self::sanitizeData($_POST);
        }

        return $context;
    }

    /**
     * Format and write detailed error block to the error.log file
     */
    public static function log(
        string $level,
        string $message,
        string $file = '',
        int $line = 0,
        ?string $trace = null,
        array $extra = []
    ): void {
        $date = date('Y-m-d H:i:s T');
        $level = strtoupper($level);
        $context = self::getRequestContext();

        $entry  = "================================================================================" . PHP_EOL;
        $entry .= "[$date] [$level] $message" . PHP_EOL;
        $entry .= "================================================================================" . PHP_EOL;

        if ($file && $line > 0) {
            $entry .= "Location    : $file on line $line" . PHP_EOL;
            $entry .= "--- CODE PREVIEW ---" . PHP_EOL;
            $entry .= self::getCodeSnippet($file, $line) . PHP_EOL;
        }

        $entry .= "--- REQUEST CONTEXT ---" . PHP_EOL;
        $entry .= "Endpoint    : " . ($context['request'] ?? 'N/A') . PHP_EOL;
        $entry .= "Client IP   : " . ($context['client_ip'] ?? 'N/A') . PHP_EOL;
        $entry .= "User Agent  : " . ($context['user_agent'] ?? 'N/A') . PHP_EOL;
        if (!empty($context['referer'])) {
            $entry .= "Referrer    : " . $context['referer'] . PHP_EOL;
        }
        $entry .= "User Session: " . ($context['user'] ?? 'N/A') . PHP_EOL;

        if (!empty($context['get_params'])) {
            $entry .= "GET Params  : " . json_encode($context['get_params'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        if (!empty($context['post_data'])) {
            $entry .= "POST Data   : " . json_encode($context['post_data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        if (!empty($extra)) {
            $entry .= "Context Data: " . json_encode(self::sanitizeData($extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        if (!empty($trace)) {
            $entry .= "--- STACK TRACE ---" . PHP_EOL;
            $entry .= trim($trace) . PHP_EOL;
        }

        $entry .= "--------------------------------------------------------------------------------" . PHP_EOL . PHP_EOL;

        @file_put_contents(self::getLogFile(), $entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Global helper function for logging custom messages/errors with context
 */
function app_log_error(string $message, array $context = [], string $level = 'ERROR'): void {
    $trace = (new Exception())->getTraceAsString();
    AppLogger::log($level, $message, '', 0, $trace, $context);
}

/**
 * Custom PHP Error Handler
 */
function app_error_handler(int $errno, string $errstr, string $errfile, int $errline): bool {
    // Respect '@' silence operator (PHP 8 sets error_reporting() to 0)
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $severityMap = [
        E_ERROR             => 'FATAL ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'PARSE ERROR',
        E_NOTICE            => 'NOTICE',
        E_CORE_ERROR        => 'CORE ERROR',
        E_CORE_WARNING      => 'CORE WARNING',
        E_COMPILE_ERROR     => 'COMPILE ERROR',
        E_COMPILE_WARNING   => 'COMPILE WARNING',
        E_USER_ERROR        => 'USER ERROR',
        E_USER_WARNING      => 'USER WARNING',
        E_USER_NOTICE       => 'USER NOTICE',
        E_STRICT            => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED        => 'DEPRECATED',
        E_USER_DEPRECATED   => 'USER DEPRECATED',
    ];

    $level = $severityMap[$errno] ?? "ERROR ($errno)";
    AppLogger::log($level, $errstr, $errfile, $errline);

    // Let standard handler run if in development mode
    return (defined('APP_ENV') && APP_ENV === 'development') ? false : true;
}

/**
 * Custom PHP Exception Handler (catches uncaught Exception & Throwable)
 */
function app_exception_handler(Throwable $e): void {
    $className = get_class($e);
    $message = "Uncaught $className: " . $e->getMessage();
    
    AppLogger::log('CRITICAL EXCEPTION', $message, $e->getFile(), $e->getLine(), $e->getTraceAsString(), [
        'exception_code' => $e->getCode(),
    ]);

    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo "<div style='background:#f8d7da;color:#721c24;padding:20px;margin:20px;border:1px solid #f5c6cb;border-radius:6px;font-family:monospace;'>";
        echo "<h3 style='margin-top:0;'>⚠️ Uncaught " . htmlspecialchars($className) . "</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
        echo "<pre style='background:#fff;padding:10px;border:1px solid #ddd;overflow-x:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "<p style='color:#555;'><small>Details written to <code>logs/error.log</code></small></p>";
        echo "</div>";
    } else {
        // Production response
        if (!headers_sent()) {
            http_response_code(500);
        }
        $errorPage = __DIR__ . '/../errors/500.php';
        if (file_exists($errorPage)) {
            require $errorPage;
        } else {
            echo "<h1>500 Internal Server Error</h1><p>An unexpected error occurred. The incident has been recorded for administrator review.</p>";
        }
    }
    exit(1);
}

/**
 * Custom Shutdown Handler (catches fatal errors like E_ERROR, parse errors, out of memory)
 */
function app_shutdown_handler(): void {
    $lastError = error_get_last();
    if ($lastError !== null && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $level = 'FATAL SHUTDOWN ERROR';
        AppLogger::log($level, $lastError['message'], $lastError['file'], $lastError['line']);
    }
}

// Register Global Error, Exception, and Shutdown Handlers
set_error_handler('app_error_handler');
set_exception_handler('app_exception_handler');
register_shutdown_function('app_shutdown_handler');
