<?php

namespace App\Models;

use App\Database;

abstract class BaseModel {
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function all(string $orderBy = 'id DESC'): array {
        $table = static::$table;
        return Database::fetchAll("SELECT * FROM {$table} ORDER BY {$orderBy}");
    }

    public static function find(int|string $id): ?array {
        $table = static::$table;
        $pk = static::$primaryKey;
        return Database::fetchOne("SELECT * FROM {$table} WHERE {$pk} = ? LIMIT 1", [$id]);
    }

    public static function where(string $column, mixed $value, string $operator = '='): array {
        $table = static::$table;
        return Database::fetchAll("SELECT * FROM {$table} WHERE {$column} {$operator} ?", [$value]);
    }

    public static function firstWhere(string $column, mixed $value, string $operator = '='): ?array {
        $table = static::$table;
        return Database::fetchOne("SELECT * FROM {$table} WHERE {$column} {$operator} ? LIMIT 1", [$value]);
    }

    public static function count(string $where = '1=1', array $params = []): int {
        $table = static::$table;
        $row = Database::fetchOne("SELECT COUNT(*) as count FROM {$table} WHERE {$where}", $params);
        return (int)($row['count'] ?? 0);
    }

    public static function create(array $data): int {
        $table = static::$table;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        Database::query($sql, array_values($data));
        return (int)Database::lastInsertId();
    }

    public static function update(int|string $id, array $data): bool {
        $table = static::$table;
        $pk = static::$primaryKey;
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $sets = [];
        $values = [];
        foreach ($data as $col => $val) {
            $sets[] = "{$col} = ?";
            $values[] = $val;
        }
        $values[] = $id;

        $sql = sprintf("UPDATE %s SET %s WHERE %s = ?", $table, implode(', ', $sets), $pk);
        Database::query($sql, $values);
        return true;
    }

    public static function delete(int|string $id): bool {
        $table = static::$table;
        $pk = static::$primaryKey;
        Database::query("DELETE FROM {$table} WHERE {$pk} = ?", [$id]);
        return true;
    }

    public static function paginate(int $page = 1, int $perPage = 15, string $where = '1=1', array $params = [], string $orderBy = 'id DESC'): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $table = static::$table;

        $countSql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $total = (int)(Database::fetchOne($countSql, $params)['total'] ?? 0);

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        $items = Database::fetchAll($sql, $params);

        $totalPages = (int)ceil($total / $perPage);

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, $totalPages),
            'has_more' => $page < $totalPages,
        ];
    }
}
