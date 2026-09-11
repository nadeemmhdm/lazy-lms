<?php

namespace App\Helpers;

class FileHelper {
    public static function validateAndStore(array $file, string $subfolder = 'uploads', ?int $maxSizeBytes = null): array {
        return self::upload($file, $subfolder, null, $maxSizeBytes);
    }

    public static function upload(
        array $file,
        string $subfolder = 'materials',
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null
    ): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No valid uploaded file provided.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
        }

        $maxSize = $maxSizeBytes ?? (int)config('security.max_upload_size_bytes', 50 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            $mb = round($maxSize / 1048576, 1);
            return ['success' => false, 'error' => "File exceeds the maximum allowed size of {$mb}MB."];
        }

        $allowed = $allowedExtensions ?? array_keys(config('security.allowed_upload_types', []));
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Reject dangerous extensions strictly
        $blocked = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar', 'exe', 'sh', 'bat', 'cmd', 'cgi', 'pl'];
        if (in_array($ext, $blocked) || !in_array($ext, $allowed)) {
            return ['success' => false, 'error' => "File extension .{$ext} is not permitted."];
        }

        // Server-side MIME type verification using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // If it's an image, verify image integrity
        if (str_starts_with($realMime, 'image/')) {
            $imgInfo = @getimagesize($file['tmp_name']);
            if ($imgInfo === false) {
                return ['success' => false, 'error' => 'The uploaded file is not a valid image.'];
            }
        }

        // Generate randomized secure filename
        $safeFileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetDir = storage_path('uploads' . DIRECTORY_SEPARATOR . trim($subfolder, '/\\'));

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file to storage.'];
        }

        $relativePath = trim($subfolder, '/\\') . '/' . $safeFileName;

        return [
            'success' => true,
            'filename' => $safeFileName,
            'original_name' => $origName,
            'relative_path' => $relativePath,
            'absolute_path' => $targetPath,
            'mime_type' => $realMime,
            'size' => $file['size'],
            'extension' => $ext,
        ];
    }

    public static function delete(string $relativePath): bool {
        $fullPath = storage_path('uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if (file_exists($fullPath) && is_file($fullPath)) {
            return @unlink($fullPath);
        }
        return false;
    }

    public static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
