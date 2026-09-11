<?php

namespace App;

use PDO;
use PDOException;

class Database {
    protected static ?PDO $instance = null;

    public static function connect(?string $dbPath = null): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $path = $dbPath ?? config('database.connections.sqlite.database', storage_path('database.sqlite'));
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Enforce foreign keys and WAL mode for high concurrency
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');
            $pdo->exec('PRAGMA busy_timeout = 5000;');

            self::$instance = $pdo;
            return self::$instance;
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failure: ' . $e->getMessage());
        }
    }

    public static function pdo(): PDO {
        if (self::$instance === null) {
            return self::connect();
        }
        return self::$instance;
    }

    public static function getInstance(): PDO {
        return self::pdo();
    }

    public static function prepare(string $sql): \PDOStatement {
        return self::pdo()->prepare($sql);
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    public static function fetchColumn(string $sql, array $params = []): mixed {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function lastInsertId(): string|int {
        return self::pdo()->lastInsertId();
    }

    public static function beginTransaction(): bool {
        return self::pdo()->beginTransaction();
    }

    public static function commit(): bool {
        return self::pdo()->commit();
    }

    public static function rollBack(): bool {
        if (self::pdo()->inTransaction()) {
            return self::pdo()->rollBack();
        }
        return false;
    }

    public static function reset(): void {
        self::$instance = null;
    }
}
