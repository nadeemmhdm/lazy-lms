<?php

namespace App\Helpers;

use App\Database;

class AuthHelper {
    protected static ?array $cachedUser = null;
    protected static ?array $cachedPermissions = null;

    public static function check(): bool {
        return self::id() !== null;
    }

    public static function id(): ?int {
        Session::start();
        return Session::get('user_id');
    }

    public static function user(): ?array {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $id = self::id();
        
        // Check Remember Me cookie if no active session
        if (!$id && isset($_COOKIE['lazy_remember'])) {
            $id = self::resolveRememberToken($_COOKIE['lazy_remember']);
        }

        if (!$id) {
            return null;
        }

        // Enforce 5 hours of continuous use session limit
        $loginTime = Session::get('login_time');
        if ($loginTime && (time() - $loginTime) > (5 * 3600)) {
            self::logout();
            return null;
        }

        try {
            $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug 
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.id
                    WHERE u.id = ? AND u.status = 'active'
                    LIMIT 1";
            $user = Database::fetchOne($sql, [$id]);

            if (!$user) {
                // If user was deleted or archived or suspended, force logout
                self::logout();
                return null;
            }

            self::$cachedUser = $user;
            return self::$cachedUser;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function role(): ?string {
        $user = self::user();
        return $user['role_slug'] ?? null;
    }

    public static function hasRole(string|array $roles): bool {
        $userRole = self::role();
        if (!$userRole) {
            return false;
        }
        if ($userRole === 'super_admin') {
            return true; // Super admin possesses all roles
        }
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($userRole, $roles);
    }

    public static function permissions(): array {
        if (self::$cachedPermissions !== null) {
            return self::$cachedPermissions;
        }

        $user = self::user();
        if (!$user) {
            return [];
        }

        if (($user['role_slug'] ?? '') === 'super_admin') {
            // Super Admin has all permissions wildcard
            self::$cachedPermissions = ['*'];
            return self::$cachedPermissions;
        }

        try {
            $sql = "SELECT DISTINCT p.name 
                    FROM permissions p
                    JOIN role_permissions rp ON p.id = rp.permission_id
                    JOIN user_roles ur ON rp.role_id = ur.role_id
                    WHERE ur.user_id = ?";
            $rows = Database::fetchAll($sql, [$user['id']]);
            self::$cachedPermissions = array_column($rows, 'name');
            return self::$cachedPermissions;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function hasPermission(string $permission): bool {
        $perms = self::permissions();
        if (in_array('*', $perms, true)) {
            return true;
        }
        return in_array($permission, $perms, true);
    }

    public static function login(array $user): void {
        Session::start();
        Session::regenerate(true);
        Session::set('user_id', (int)$user['id']);
        Session::set('login_time', time());
        self::$cachedUser = null;
        self::$cachedPermissions = null;

        // Record session into database
        try {
            $req = new Request();
            Database::query(
                "INSERT OR REPLACE INTO sessions (id, user_id, ip_address, user_agent, last_activity) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    Session::id(),
                    $user['id'],
                    $req->ip(),
                    substr($req->userAgent(), 0, 255),
                    time(),
                ]
            );

            // Update user last login timestamp
            Database::query("UPDATE users SET last_login_at = datetime('now') WHERE id = ?", [$user['id']]);

            // Clear failed login attempts for this IP and username
            self::clearLoginAttempts($req->ip(), $user['email']);
        } catch (\Throwable $e) {
            // Non-fatal if session table is not yet migrated
        }
    }

    public static function logout(): void {
        $sessId = Session::id();
        try {
            if ($sessId) {
                Database::query("DELETE FROM sessions WHERE id = ?", [$sessId]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        self::$cachedUser = null;
        self::$cachedPermissions = null;
        Session::destroy();
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function normalizeSecurityAnswer(string $answer): string {
        // Lowercase, trim, collapse multiple whitespace
        $clean = mb_strtolower(trim($answer), 'UTF-8');
        $clean = preg_replace('/\s+/', ' ', $clean);
        return hash('sha256', $clean);
    }

    public static function checkLoginLockout(string $ip, string $identifier): array {
        $maxAttempts = (int)config('security.login_max_attempts', 5);
        $lockoutSeconds = (int)config('security.login_lockout_seconds', 900);
        $since = time() - $lockoutSeconds;

        try {
            $row = Database::fetchOne(
                "SELECT COUNT(*) as count, MAX(attempted_at) as last_attempt 
                 FROM login_attempts 
                 WHERE (ip_address = ? OR identifier = ?) AND attempted_at > ?",
                [$ip, $identifier, $since]
            );

            $attempts = (int)($row['count'] ?? 0);
            if ($attempts >= $maxAttempts) {
                $lastAttempt = (int)($row['last_attempt'] ?? time());
                $remaining = ($lastAttempt + $lockoutSeconds) - time();
                if ($remaining > 0) {
                    $mins = max(1, ceil($remaining / 60));
                    return [
                        'locked' => true,
                        'message' => "Too many failed login attempts. Please try again in {$mins} minute(s).",
                    ];
                }
            }

            // Progressive delay
            if ($attempts > 2) {
                sleep(min($attempts - 2, 3)); // 1 to 3 seconds delay
            }

            return ['locked' => false];
        } catch (\Throwable $e) {
            return ['locked' => false];
        }
    }

    public static function recordFailedLogin(string $ip, string $identifier): void {
        try {
            Database::query(
                "INSERT INTO login_attempts (ip_address, identifier, attempted_at) VALUES (?, ?, ?)",
                [$ip, $identifier, time()]
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function clearLoginAttempts(string $ip, string $identifier): void {
        try {
            Database::query(
                "DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?",
                [$ip, $identifier]
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function setRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = time() + (12 * 3600); // 12 hours as specified

        try {
            Database::query(
                "INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, datetime(?, 'unixepoch'), datetime('now'))",
                [$userId, $hash, $expires]
            );

            // 12-hour HttpOnly, SameSite, Secure cookie
            setcookie('lazy_remember', $token, [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function resolveRememberToken(string $rawToken): ?int {
        $hash = hash('sha256', $rawToken);
        try {
            $row = Database::fetchOne(
                "SELECT user_id, expires_at FROM remember_tokens WHERE token_hash = ? AND datetime(expires_at) > datetime('now') LIMIT 1",
                [$hash]
            );
            if ($row) {
                // Refresh session for user
                $userId = (int)$row['user_id'];
                Session::start();
                Session::set('user_id', $userId);
                Session::set('login_time', time());
                return $userId;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public static function revokeRememberToken(): void {
        if (isset($_COOKIE['lazy_remember'])) {
            $hash = hash('sha256', $_COOKIE['lazy_remember']);
            try {
                Database::query("DELETE FROM remember_tokens WHERE token_hash = ?", [$hash]);
            } catch (\Throwable $e) {}

            setcookie('lazy_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    public static function checkAndRecordDevice(int $userId, string $ip, string $userAgent): void {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id FROM login_devices WHERE user_id = ? AND ip_address = ? LIMIT 1");
            $stmt->execute([$userId, $ip]);
            $device = $stmt->fetch();

            if (!$device) {
                // New device/IP detected!
                $ins = $db->prepare("INSERT INTO login_devices (user_id, ip_address, user_agent, first_seen_at, last_seen_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))");
                $ins->execute([$userId, $ip, substr($userAgent, 0, 255)]);

                self::recordSecurityEvent($userId, 'security.new_ip_login', [
                    'ip' => $ip,
                    'user_agent' => $userAgent
                ], $ip, $userAgent);

                // Send email alert to user
                $user = Database::fetchOne("SELECT id, name, email FROM users WHERE id = ?", [$userId]);
                if ($user && !empty($user['email'])) {
                    \App\Services\MailService::sendTemplate('security.new_ip_login', $user, [
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ]);
                }
            } else {
                $up = $db->prepare("UPDATE login_devices SET last_seen_at = datetime('now'), user_agent = ? WHERE id = ?");
                $up->execute([substr($userAgent, 0, 255), $device['id']]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function recordSecurityEvent(?int $userId, string $eventType, array|string $details = [], ?string $ip = null, ?string $userAgent = null): void {
        $db = Database::getInstance();
        $req = new Request();
        $resolvedIp = $ip ?? $req->ip();
        $resolvedUa = $userAgent ?? $req->userAgent();
        try {
            $stmt = $db->prepare("INSERT INTO security_events (user_id, event_type, ip_address, user_agent, details, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))");
            $stmt->execute([
                $userId,
                $eventType,
                $resolvedIp,
                substr((string)$resolvedUa, 0, 255),
                is_array($details) ? json_encode($details) : (string)$details
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
