<?php
/**
 * Database Connection & Query Utility
 * Graduate Predicting Analysis System
 */

class Database {
    private static ?PDO $instance = null;
    
    // Default XAMPP MySQL configuration
    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $db   = 'graduate_predictor';
    private static string $user = 'root';
    private static string $pass = '';
    private static string $charset = 'utf8mb4';

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::$user, self::$pass, $options);
            } catch (PDOException $e) {
                // Return clean, user-friendly error without leaking sensitive internals
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Database connection failed. Please ensure MySQL is running in XAMPP.',
                    'details' => $e->getMessage()
                ]));
            }
        }

        return self::$instance;
    }
}

/**
 * Global helper to obtain active PDO instance
 */
function getDB(): PDO {
    return Database::getConnection();
}
