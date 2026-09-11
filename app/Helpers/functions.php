<?php

use App\Helpers\Session;
use App\Helpers\Csrf;
use App\Helpers\AuthHelper;
use App\Helpers\I18n;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            return $default;
        }
        return match (strtolower($val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $val,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed {
        static $configs = [];
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($configs[$file])) {
            $path = __DIR__ . '/../../config/' . $file . '.php';
            if (file_exists($path)) {
                $configs[$file] = require $path;
            } else {
                $configs[$file] = [];
            }
        }

        $val = $configs[$file];
        foreach ($parts as $p) {
            if (is_array($val) && array_key_exists($p, $val)) {
                $val = $val[$p];
            } else {
                return $default;
            }
        }
        return $val;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string {
        return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim(config('app.url', 'http://localhost:8000'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): never {
        $target = str_starts_with($path, 'http') ? $path : url($path);
        header("Location: $target", true, $status);
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never {
        header('Content-Type: application/json; charset=utf-8', true, $status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed {
        return Session::getOld($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $message = null): mixed {
        if ($message === null) {
            return Session::getFlash($key);
        }
        Session::flash($key, $message);
        return null;
    }
}

if (!function_exists('auth')) {
    function auth(): ?array {
        return AuthHelper::user();
    }
}

if (!function_exists('has_permission')) {
    function has_permission(string $permission): bool {
        return AuthHelper::hasPermission($permission);
    }
}

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string {
        return I18n::translate($key, $replace);
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        return $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
