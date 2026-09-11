<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use App\Helpers\Request;

class AuthMiddleware {
    public function handle(Request $request): void {
        if (!AuthHelper::check()) {
            if ($request->isAjax()) {
                http_response_code(401);
                json_response(['error' => 'Unauthenticated.'], 401);
            }
            redirect('/login');
        }

        // Maintenance Mode Enforcement (Specification 13 & 14):
        // When active, students and teachers see maintenance screen, but administrators have full access.
        $user = AuthHelper::user();
        $isMaintenance = (bool)\App\Database::fetchOne("SELECT value FROM settings WHERE key = 'maintenance_mode'")['value'] ?? false;
        
        if ($isMaintenance && $user && !in_array($user['role_slug'] ?? '', ['super_admin', 'admin'])) {
            \App\Helpers\Response::abort(503, 'The LMS is temporarily undergoing maintenance. Please check back shortly.');
        }
    }
}
