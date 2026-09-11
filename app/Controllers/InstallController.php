<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Database;
use Database\Migrator;

class InstallController extends BaseController {
    public function index(Request $request): string {
        if (\App\App::isInstalled()) {
            redirect('/login');
        }

        // Diagnostics check
        $diagnostics = [
            'php_version' => [
                'name' => 'PHP Version >= 8.2',
                'pass' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo_sqlite' => [
                'name' => 'PDO SQLite Extension',
                'pass' => extension_loaded('pdo_sqlite'),
                'current' => extension_loaded('pdo_sqlite') ? 'Enabled' : 'Missing',
            ],
            'sqlite3' => [
                'name' => 'SQLite3 Extension',
                'pass' => extension_loaded('sqlite3'),
                'current' => extension_loaded('sqlite3') ? 'Enabled' : 'Missing',
            ],
            'mbstring' => [
                'name' => 'MBString Extension',
                'pass' => extension_loaded('mbstring'),
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Missing',
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'pass' => extension_loaded('openssl'),
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Missing',
            ],
            'storage_writable' => [
                'name' => 'Storage Directory Writable',
                'pass' => is_writable(storage_path()) || @mkdir(storage_path(), 0755, true),
                'current' => is_writable(storage_path()) ? 'Writable' : 'Not Writable',
            ],
        ];

        $allPassed = !in_array(false, array_column($diagnostics, 'pass'), true);

        return Response::view('install/index', [
            'title' => 'Open LMS - Installation Wizard',
            'diagnostics' => $diagnostics,
            'allPassed' => $allPassed,
        ], null);
    }

    public function install(Request $request): never {
        if (\App\App::isInstalled()) {
            redirect('/login');
        }

        $errors = $this->validate($request, [
            'app_name' => 'required|min:2|max:100',
            'admin_name' => 'required|min:2|max:100',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8|confirmed',
            'recovery_question' => 'required|min:5|max:200',
            'recovery_answer' => 'required|min:2|max:100',
            'timezone' => 'required',
            'date_format' => 'required',
            'time_format' => 'required',
        ]);

        if (!empty($errors)) {
            redirect('/install');
        }

        try {
            // 1. Run database migrations and initial seeds
            $dbPath = storage_path('database.sqlite');
            Migrator::run($dbPath);

            $pdo = Database::pdo();
            $pdo->beginTransaction();

            // 2. Create Super Admin User
            $hashedPassword = AuthHelper::hashPassword($request->input('admin_password'));
            $userStmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, status, locale, timezone, created_at, updated_at) 
                 VALUES (?, ?, ?, 'active', 'en', ?, datetime('now'), datetime('now'))"
            );
            $userStmt->execute([
                $request->input('admin_name'),
                $request->input('admin_email'),
                $hashedPassword,
                $request->input('timezone', 'UTC'),
            ]);
            $adminId = (int)$pdo->lastInsertId();

            // Assign Super Admin Role
            $roleRow = $pdo->query("SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1")->fetch();
            if ($roleRow) {
                $roleStmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $roleStmt->execute([$adminId, $roleRow['id']]);
            }

            // 3. Store Normalized Recovery Question & Hash
            $normalizedAnswerHash = AuthHelper::normalizeSecurityAnswer($request->input('recovery_answer'));
            $recStmt = $pdo->prepare(
                "INSERT INTO recovery_questions (user_id, question, answer_hash, created_at, updated_at) 
                 VALUES (?, ?, ?, datetime('now'), datetime('now'))"
            );
            $recStmt->execute([
                $adminId,
                $request->input('recovery_question'),
                $normalizedAnswerHash,
            ]);

            // 4. Save App Settings & Branding
            $settings = [
                'app_name' => $request->input('app_name'),
                'app_description' => $request->input('app_description', 'Private Learning Management System'),
                'timezone' => $request->input('timezone', 'UTC'),
                'date_format' => $request->input('date_format', 'Y-m-d'),
                'time_format' => $request->input('time_format', 'H:i'),
                'primary_color' => $request->input('primary_color', '#4f46e5'),
                'secondary_color' => $request->input('secondary_color', '#06b6d4'),
                'footer_text' => 'Powered by ' . $request->input('app_name'),
                'contact_email' => $request->input('admin_email'),
                'show_calendar' => '1',
            ];

            // Handle optional logo upload
            if ($request->hasFile('logo')) {
                $logoUpload = FileHelper::upload($request->file('logo'), 'branding', ['png', 'jpg', 'jpeg', 'webp', 'svg']);
                if ($logoUpload['success']) {
                    $settings['logo'] = $logoUpload['relative_path'];
                }
            }

            // Handle optional favicon upload
            if ($request->hasFile('favicon')) {
                $favUpload = FileHelper::upload($request->file('favicon'), 'branding', ['png', 'ico']);
                if ($favUpload['success']) {
                    $settings['favicon'] = $favUpload['relative_path'];
                }
            }

            $setStmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value, created_at, updated_at) VALUES (?, ?, datetime('now'), datetime('now'))");
            foreach ($settings as $k => $v) {
                $setStmt->execute([$k, $v]);
            }

            // 5. Store SMTP Email Settings
            $emailStmt = $pdo->prepare(
                "INSERT OR REPLACE INTO email_settings (id, host, port, encryption, username, password, from_name, from_email, created_at, updated_at) 
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
            );
            $emailStmt->execute([
                $request->input('smtp_host', 'localhost'),
                (int)$request->input('smtp_port', 587),
                $request->input('smtp_encryption', 'tls'),
                $request->input('smtp_username', ''),
                $request->input('smtp_password', ''),
                $request->input('app_name'),
                $request->input('from_email', $request->input('admin_email')),
            ]);

            // 6. Record Audit Log
            $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, created_at) 
                 VALUES (?, 'lms.installed', 'system', 1, ?, ?, datetime('now'))"
            )->execute([
                $adminId,
                json_encode(['installer_ip' => $request->ip(), 'time' => date('c')]),
                $request->ip(),
            ]);

            $pdo->commit();

            // 7. Write installation lock file
            $lockData = json_encode([
                'installed_at' => date('c'),
                'version' => config('app.version', '1.0.0'),
                'admin_email' => $request->input('admin_email'),
            ], JSON_PRETTY_PRINT);
            file_put_contents(storage_path('installed.lock'), $lockData);

            // Flash success message and redirect
            Session::flash('success', 'Installation completed successfully! You can now log in with your administrator account.');
            redirect('/login');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Installation failed: ' . $e->getMessage());
            redirect('/install');
        }
    }

    public function testEmail(Request $request): never {
        $host = $request->input('smtp_host');
        $port = (int)$request->input('smtp_port', 587);

        if (!$host) {
            json_response(['success' => false, 'message' => 'Please specify an SMTP host.'], 400);
        }

        // Test TCP connection to host:port
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($fp) {
            fclose($fp);
            json_response(['success' => true, 'message' => "Successfully connected to {$host}:{$port}."]);
        } else {
            json_response(['success' => false, 'message' => "Connection failed: {$errstr} ({$errno})"], 400);
        }
    }
}
