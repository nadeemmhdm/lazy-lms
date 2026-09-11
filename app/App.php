<?php

namespace App;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Middleware\CsrfMiddleware;

class App {
    protected static ?Router $router = null;

    public static function run(): void {
        self::boot();

        $request = new Request();
        $isInstalled = self::isInstalled();
        $path = $request->path();

        // Installation enforcement
        if (!$isInstalled) {
            if (!str_starts_with($path, '/install') && !str_starts_with($path, '/assets')) {
                redirect('/install');
            }
        } else {
            if (str_starts_with($path, '/install')) {
                redirect('/login');
            }
        }

        // Apply universal CSRF protection middleware on mutating requests
        $csrfMiddleware = new CsrfMiddleware();
        $csrfMiddleware->handle($request);

        // Load Plugins
        self::loadPlugins();

        // Dispatch routes
        self::getRouter()->dispatch($request);
    }

    public static function boot(): void {
        self::initAutoloader();
        self::loadEnv();
        self::initErrorHandling();
        Session::start();
    }

    public static function isInstalled(): bool {
        return file_exists(storage_path('installed.lock'));
    }

    public static function getRouter(): Router {
        if (self::$router === null) {
            self::$router = new Router();
            self::registerRoutes(self::$router);
        }
        return self::$router;
    }

    protected static function registerRoutes(Router $router): void {
        require __DIR__ . '/routes.php';
    }

    protected static function initAutoloader(): void {
        spl_autoload_register(function ($class) {
            if (str_starts_with($class, 'App\\')) {
                $relativeClass = substr($class, 4);
                $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            } elseif (str_starts_with($class, 'Database\\')) {
                $relativeClass = substr($class, 9);
                $file = dirname(__DIR__) . '/database/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });

        // Load global helpers
        require_once __DIR__ . '/Helpers/functions.php';
    }

    protected static function loadEnv(): void {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    protected static function initErrorHandling(): void {
        error_reporting(E_ALL);

        set_error_handler(function ($level, $message, $file, $line) {
            if (error_reporting() & $level) {
                throw new \ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function (\Throwable $e) {
            $logMessage = sprintf(
                "[%s] Uncaught %s: %s in %s on line %d\nStack trace:\n%s\n",
                date('Y-m-d H:i:s'),
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );

            $logDir = storage_path('logs');
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logDir . '/app.log', $logMessage, FILE_APPEND);

            http_response_code(500);
            $debug = config('app.debug', false);

            if ($debug) {
                echo "<h1>Internal Server Error (Debug Mode)</h1>";
                echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                echo Response::view('errors/500', [
                    'title' => '500 Server Error',
                    'message' => 'An internal server error occurred. Our engineers have been notified.'
                ], null);
            }
            exit;
        });
    }

    protected static function loadPlugins(): void {
        $pluginsDir = base_path('plugins');
        if (!is_dir($pluginsDir)) {
            return;
        }

        $items = scandir($pluginsDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $pluginFile = $pluginsDir . '/' . $item . '/plugin.php';
            if (file_exists($pluginFile)) {
                require_once $pluginFile;
            }
        }
    }
}
