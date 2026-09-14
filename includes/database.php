<?php
/**
 * SAYAK LIBRARY - Database Singleton / Connection Class
 * Uses PDO with Prepared Statements for Maximum Security
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Fallback attempt to localhost
                try {
                    $dsnFallback = "mysql:host=localhost;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                    self::$instance = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
                } catch (PDOException $e2) {
                    error_log("Database Connection Error: " . $e2->getMessage());
                    if (APP_ENV === 'development') {
                        die("Database Connection Error: " . $e2->getMessage());
                    } else {
                        die("Database Connection Error. Please contact system administrator.");
                    }
                }
            }
        }
        return self::$instance;
    }
}

// Global function to quickly obtain PDO instance
function getDB(): PDO {
    return Database::getConnection();
}
