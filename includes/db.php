<?php
/**
 * Knitin Portfolio — Database Connection (PDO Singleton)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = env('DB_HOST', 'localhost');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_NAME', '');
            $username = env('DB_USER', '');
            $password = env('DB_PASS', '');
            $charset = env('DB_CHARSET', 'utf8mb4');

            if (empty($dbname)) {
                throw new RuntimeException("Database name is not configured in .env file.");
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new RuntimeException("Database connection failed. Please verify your .env settings or run install.php.");
            }
        }

        return self::$instance;
    }

    public static function isConfigured(): bool {
        $dbname = env('DB_NAME', '');
        return !empty($dbname);
    }
}

function db(): PDO {
    return Database::getConnection();
}
