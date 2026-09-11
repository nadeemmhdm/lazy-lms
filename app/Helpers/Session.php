<?php

namespace App\Helpers;

class Session {
    protected static bool $started = false;

    public static function start(): void {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = (int)config('security.session_lifetime', 7200);
        $secure = (bool)config('security.session_secure', false);
        $httpOnly = (bool)config('security.session_http_only', true);
        $sameSite = (string)config('security.session_same_site', 'Strict');

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string)$lifetime);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        session_name('open_lms_sess');
        session_start();
        self::$started = true;

        // Initialize flash storage arrays
        if (!isset($_SESSION['_flash_current'])) {
            $_SESSION['_flash_current'] = [];
        }
        if (!isset($_SESSION['_flash_next'])) {
            $_SESSION['_flash_next'] = [];
        }

        // Age flash messages
        $_SESSION['_flash_current'] = $_SESSION['_flash_next'];
        $_SESSION['_flash_next'] = [];

        // Old input rotation
        if (isset($_SESSION['_old_next'])) {
            $_SESSION['_old_current'] = $_SESSION['_old_next'];
            unset($_SESSION['_old_next']);
        } else {
            $_SESSION['_old_current'] = [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void {
        self::start();
        $_SESSION['_flash_next'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION['_flash_current'][$key] ?? $default;
    }

    public static function setOld(array $input): void {
        self::start();
        // Exclude sensitive fields
        unset($input['password'], $input['password_confirmation'], $input['confirm_password'], $input['_csrf_token']);
        $_SESSION['_old_next'] = $input;
    }

    public static function getOld(string $key, mixed $default = ''): mixed {
        self::start();
        return $_SESSION['_old_current'][$key] ?? $default;
    }

    public static function regenerate(bool $deleteOldSession = true): bool {
        self::start();
        return session_regenerate_id($deleteOldSession);
    }

    public static function id(): string {
        self::start();
        return session_id();
    }

    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        self::$started = false;
    }
}
