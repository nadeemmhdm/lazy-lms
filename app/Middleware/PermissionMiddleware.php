<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use App\Helpers\Request;

class PermissionMiddleware {
    protected string $permission;

    public function __construct(string $permission) {
        $this->permission = $permission;
    }

    public function handle(Request $request): void {
        if (!AuthHelper::check()) {
            redirect('/login');
        }

        if (!AuthHelper::hasPermission($this->permission)) {
            if ($request->isAjax()) {
                http_response_code(403);
                json_response(['error' => 'Forbidden. Missing permission: ' . $this->permission], 403);
            }
            http_response_code(403);
            echo \App\Helpers\Response::view('errors/403', [
                'title' => '403 Forbidden',
                'message' => 'You do not have the required permission (' . htmlspecialchars($this->permission) . ') to perform this action.'
            ], null);
            exit;
        }
    }
}
