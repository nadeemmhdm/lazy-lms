<?php

namespace App;

use App\Helpers\Request;
use App\Helpers\Response;

class Router {
    protected array $routes = [];
    protected array $groupStack = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): void {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function any(string $path, array|callable $handler, array $middleware = []): void {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $m) {
            $this->addRoute($m, $path, $handler, $middleware);
        }
    }

    public function group(array $attributes, callable $callback): void {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function addRoute(string $method, string $path, array|callable $handler, array $middleware = []): void {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, (array)$group['middleware']);
            }
        }

        $fullPath = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $allMiddleware = array_merge($groupMiddleware, $middleware);

        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $allMiddleware,
        ];
    }

    public function dispatch(Request $request): void {
        $requestMethod = $request->method();
        $requestPath = $request->path();

        $matchedMethod = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $requestPath, $matches)) {
                if ($route['method'] !== $requestMethod) {
                    $matchedMethod = true;
                    continue;
                }

                // Extract named parameter arguments
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }

                // Execute middleware pipeline
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw)) {
                        $instance = new $mw();
                        $instance->handle($request);
                    } elseif (is_callable($mw)) {
                        $mw($request);
                    } elseif (is_object($mw) && method_exists($mw, 'handle')) {
                        $mw->handle($request);
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    $response = call_user_func($handler, $request, ...array_values($params));
                } elseif (is_array($handler)) {
                    [$controllerClass, $action] = $handler;
                    $controller = new $controllerClass();
                    $response = $controller->$action($request, ...array_values($params));
                } else {
                    throw new \RuntimeException('Invalid route handler.');
                }

                if (is_string($response)) {
                    echo $response;
                }
                return;
            }
        }

        if ($matchedMethod) {
            http_response_code(405);
            echo "405 Method Not Allowed";
            return;
        }

        // 404 Not Found
        http_response_code(404);
        echo Response::view('errors/404', [
            'title' => '404 Page Not Found',
            'message' => 'The requested page or resource could not be found.'
        ], null);
    }

    public function getRoutes(): array {
        return $this->routes;
    }
}
