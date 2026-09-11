<?php

namespace App\Middleware;

use App\Helpers\Csrf;
use App\Helpers\Request;

class CsrfMiddleware {
    public function handle(Request $request): void {
        // Exclude safe HTTP methods and API routes
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }

        if (str_starts_with($request->path(), '/api/')) {
            return;
        }

        $token = $request->input('_csrf_token') ?? $request->header('X_CSRF_TOKEN');

        if (!Csrf::validate($token)) {
            if ($request->isAjax()) {
                http_response_code(419);
                json_response(['error' => 'CSRF token mismatch or expired. Please refresh the page.'], 419);
            }
            http_response_code(419);
            echo \App\Helpers\Response::view('errors/419', [
                'title' => '419 Page Expired',
                'message' => 'Your session or security token has expired. Please go back, refresh the page, and try again.'
            ], null);
            exit;
        }
    }
}
