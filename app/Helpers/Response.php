<?php

namespace App\Helpers;

class Response {
    public static function setSecurityHeaders(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    public static function view(string $view, array $data = [], ?string $layout = 'layouts/layout'): string {
        self::setSecurityHeaders();
        extract($data);

        // Normalize view path
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [$view] not found at $viewPath");
        }

        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        if ($layout) {
            $layoutPath = __DIR__ . '/../Views/' . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutPath)) {
                ob_start();
                include $layoutPath;
                return ob_get_clean();
            }
        }

        return $content;
    }

    public static function json(mixed $data, int $status = 200): never {
        self::setSecurityHeaders();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never {
        header("Location: $url", true, $status);
        exit;
    }

    public static function abort(int $code, string $message = ''): never {
        self::setSecurityHeaders();
        http_response_code($code);

        $view = "errors/{$code}";
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (file_exists($viewPath)) {
            echo self::view($view, [
                'title' => "Error {$code}",
                'message' => $message ?: 'An error occurred.'
            ], null);
        } else {
            echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit;
    }

    public static function download(string $filePath, ?string $downloadName = null, ?string $mimeType = null): never {
        // Prevent path traversal
        $realPath = realpath($filePath);
        if (!$realPath || !file_exists($realPath) || !is_readable($realPath)) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        $downloadName = $downloadName ?? basename($realPath);
        // Sanitize download filename for Content-Disposition
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $downloadName);

        if (!$mimeType) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $realPath) ?: 'application/octet-stream';
            finfo_close($finfo);
        }

        self::setSecurityHeaders();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($realPath));

        // Flush buffer and readfile in chunks to support large files without memory exhaustion
        if (ob_get_level()) {
            ob_end_clean();
        }

        $handle = fopen($realPath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 1024 * 1024); // 1MB chunks
            flush();
        }
        fclose($handle);
        exit;
    }
}
