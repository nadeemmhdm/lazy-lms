<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\AuthHelper;
use App\Database;

class FileDownloadController extends BaseController {
    public function download(Request $request, string $fileIdentifier): never {
        // 1. Allow public download if it is a public branding asset (logo / favicon)
        $isBranding = str_starts_with($fileIdentifier, 'branding/');
        if (!$isBranding && !AuthHelper::check()) {
            http_response_code(401);
            echo "Unauthorized access. Please log in.";
            exit;
        }

        // 2. Prevent path traversal attacks
        $cleanIdentifier = str_replace(['../', '..\\'], '', $fileIdentifier);
        $fullPath = storage_path('uploads/' . $cleanIdentifier);

        $realPath = realpath($fullPath);
        $allowedBase = realpath(storage_path('uploads'));

        if (!$realPath || !str_starts_with($realPath, $allowedBase) || !file_exists($realPath)) {
            http_response_code(404);
            echo "File not found or access denied.";
            exit;
        }

        // Fetch original filename from database if available
        $mat = Database::fetchOne("SELECT original_filename FROM lesson_materials WHERE file_path = ? LIMIT 1", [$cleanIdentifier]);
        $downloadName = $mat['original_filename'] ?? basename($realPath);

        Response::download($realPath, $downloadName);
    }
}
