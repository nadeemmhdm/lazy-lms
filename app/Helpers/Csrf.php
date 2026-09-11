<?php

namespace App\Helpers;

class Csrf {
    public static function token(): string {
        Session::start();
        $token = Session::get('_csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool {
        if (!$token) {
            return false;
        }
        $sessionToken = Session::get('_csrf_token');
        if (!$sessionToken) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function verify(?string $token): bool {
        return self::validate($token);
    }

    public static function regenerate(): string {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
        return $token;
    }
}
