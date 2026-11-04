<?php

namespace ResolveAI\Database;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void {
        self::$config = $config;
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                self::$config = require dirname(__DIR__) . '/config/config.php';
            }

            $dbConfig = self::$config['database'];
            $driver = $dbConfig['driver'] ?? 'sqlite';

            try {
                if ($driver === 'sqlite') {
                    $sqlitePath = $dbConfig['sqlite_path'];
                    $dir = dirname($sqlitePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    self::$instance = new PDO("sqlite:" . $sqlitePath);
                } elseif ($driver === 'pgsql') {
                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s",
                        $dbConfig['host'],
                        $dbConfig['port'],
                        $dbConfig['database']
                    );
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                } elseif ($driver === 'mysql') {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                        $dbConfig['host'],
                        $dbConfig['port'],
                        $dbConfig['database']
                    );
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                } else {
                    throw new Exception("Unsupported database driver: " . $driver);
                }

                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                // Enable foreign keys for SQLite
                if ($driver === 'sqlite') {
                    self::$instance->exec("PRAGMA foreign_keys = ON;");
                }

            } catch (PDOException $e) {
                throw new Exception("Database Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
