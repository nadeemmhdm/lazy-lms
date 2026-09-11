<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\AuthHelper;
use App\Database;

abstract class BaseController {
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/layout'): string {
        // Automatically inject current user, auth status, site settings, and pending notifications count
        $data['currentUser'] = AuthHelper::user();
        $data['appSettings'] = $this->getSettings();
        $data['unreadNotificationsCount'] = $this->getUnreadNotificationsCount();
        return Response::view($view, $data, $layout);
    }

    protected function requireAuth(): void {
        if (!AuthHelper::check()) {
            redirect('/login');
        }
    }

    protected function requirePermission(string $permission): void {
        $this->requireAuth();
        if (!AuthHelper::hasPermission($permission)) {
            Response::abort(403, 'You do not have permission to access this resource.');
        }
    }

    protected function requireRole(string|array $roles): void {
        $this->requireAuth();
        if (!AuthHelper::hasRole($roles)) {
            Response::abort(403, 'Unauthorized role access.');
        }
    }

    protected function currentUser(): ?array {
        return AuthHelper::user();
    }

    protected function validateCsrf(): void {
        $req = new Request();
        $token = $req->post('_token') ?? $req->header('X-CSRF-Token');
        if (!\App\Helpers\Csrf::verify($token)) {
            Response::abort(419, 'Page expired or invalid CSRF token.');
        }
    }

    protected function logAudit(string $action, ?string $targetType = null, ?int $targetId = null, ?array $details = null): void {
        $this->audit($action, $targetType, $targetId, $details);
    }

    protected function audit(string $action, ?string $targetType = null, ?int $targetId = null, ?array $details = null): void {
        try {
            $user = AuthHelper::user();
            $req = new Request();
            Database::query(
                "INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, datetime('now'))",
                [
                    $user['id'] ?? null,
                    $action,
                    $targetType,
                    $targetId,
                    $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                    $req->ip(),
                ]
            );
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    protected function getSettings(): array {
        static $settings = null;
        if ($settings !== null) {
            return $settings;
        }

        try {
            $rows = Database::fetchAll("SELECT key, value FROM settings");
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['key']] = $r['value'];
            }
            return $settings;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getUnreadNotificationsCount(): int {
        $user = AuthHelper::user();
        if (!$user) {
            return 0;
        }

        try {
            $row = Database::fetchOne(
                "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0",
                [$user['id']]
            );
            return (int)($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
