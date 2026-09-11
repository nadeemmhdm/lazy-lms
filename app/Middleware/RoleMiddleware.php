<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use App\Helpers\Request;

class RoleMiddleware {
    protected array $roles;

    public function __construct(string ...$roles) {
        $this->roles = $roles;
    }

    public function handle(Request $request): void {
        if (!AuthHelper::check()) {
            redirect('/login');
        }

        if (!AuthHelper::hasRole($this->roles)) {
            if ($request->isAjax()) {
                http_response_code(403);
                json_response(['error' => 'Unauthorized. Insufficient role permissions.'], 403);
            }
            http_response_code(403);
            echo \App\Helpers\Response::view('errors/403', ['title' => '403 Forbidden', 'message' => 'You do not have permission to access this area.'], null);
            exit;
        }
    }
}
