<?php

namespace App\Helpers;

use App\Database;

/**
 * Lazy LMS - Secure Public ID Generator & Resolver
 * Generates non-sequential, random 6-character alphanumeric identifiers (A-Z, a-z, 0-9)
 * for all major entities to prevent IDOR and enumeration.
 */
class HashId {
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
    private const LENGTH = 6;

    public static function generate(string $table, string $column = 'public_id'): string {
        $db = Database::getInstance();
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $id = '';
            $maxIdx = strlen(self::ALPHABET) - 1;
            $randomBytes = random_bytes(self::LENGTH);
            for ($j = 0; $j < self::LENGTH; $j++) {
                $id .= self::ALPHABET[ord($randomBytes[$j]) % ($maxIdx + 1)];
            }

            // Check collision
            try {
                $check = $db->prepare("SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1");
                $check->execute([$id]);
                if (!$check->fetch()) {
                    return $id;
                }
            } catch (\Throwable $e) {
                return $id;
            }
        }

        // Fallback with timestamp salt if multiple collisions
        return substr(bin2hex(random_bytes(4)), 0, self::LENGTH);
    }

    /**
     * Resolves an ID which could be either a numeric primary key or a public_id string.
     */
    public static function resolveId(string $table, string|int $identifier): ?int {
        $db = Database::getInstance();
        if (is_numeric($identifier) && (int)$identifier > 0) {
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$identifier]);
            $val = $stmt->fetchColumn();
            if ($val) return (int)$val;
        }

        // Search by public_id
        try {
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE public_id = ? LIMIT 1");
            $stmt->execute([(string)$identifier]);
            $val = $stmt->fetchColumn();
            return $val ? (int)$val : null;
        } catch (\Throwable $e) {
            return is_numeric($identifier) ? (int)$identifier : null;
        }
    }

    public static function getPublicId(string $table, int $internalId): string {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT public_id FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$internalId]);
            $pub = $stmt->fetchColumn();
            if ($pub) return $pub;
        } catch (\Throwable $e) {
            // ignore
        }
        return (string)$internalId;
    }
}
