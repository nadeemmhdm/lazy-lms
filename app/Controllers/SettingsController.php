<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\FileHelper;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class SettingsController extends BaseController
{
    public function branding(): void
    {
        $this->requirePermission('settings.manage');
        $db = Database::getInstance();

        $settingsRows = $db->query("SELECT key, value FROM settings")->fetchAll();
        $settings = [];
        foreach ($settingsRows as $r) {
            $settings[$r['key']] = $r['value'];
        }

        $this->render('settings/branding', [
            'settings' => $settings,
            'user' => $this->currentUser()
        ]);
    }

    public function updateBranding(): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        $db = Database::getInstance();

        $keys = [
            'lms_name', 'lms_description', 'timezone', 'date_format', 'time_format',
            'login_title', 'login_description', 'footer_text', 'contact_email',
            'primary_color', 'secondary_color', 'theme', 'show_calendar'
        ];

        $upd = $db->prepare("INSERT INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now')) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')");

        foreach ($keys as $k) {
            $val = Request::post($k);
            if ($k === 'show_calendar') {
                $val = Request::post('show_calendar') ? '1' : '0';
            }
            if ($val !== null) {
                $upd->execute([$k, trim($val)]);
            }
        }

        // Handle Logo upload
        if (!empty($_FILES['logo']['name'])) {
            $up = FileHelper::validateAndStore($_FILES['logo'], 'image', 5 * 1024 * 1024);
            if ($up['success']) {
                $upd->execute(['logo_url', '/download/file/' . $this->storeUpload($up, 'image')]);
            }
        }

        // Handle Favicon upload
        if (!empty($_FILES['favicon']['name'])) {
            $up = FileHelper::validateAndStore($_FILES['favicon'], 'image', 2 * 1024 * 1024);
            if ($up['success']) {
                $upd->execute(['favicon_url', '/download/file/' . $this->storeUpload($up, 'image')]);
            }
        }

        $this->logAudit('Updated branding settings', 'settings', null);
        Session::flash('success', 'Branding settings updated successfully.');
        Response::redirect('/settings/branding');
    }

    public function smtp(): void
    {
        $this->requirePermission('settings.manage');
        $db = Database::getInstance();
        $smtp = $db->query("SELECT * FROM email_settings LIMIT 1")->fetch() ?: [];

        $this->render('settings/smtp', [
            'smtp' => $smtp,
            'user' => $this->currentUser()
        ]);
    }

    public function updateSmtp(): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        $db = Database::getInstance();

        $host = trim(Request::post('smtp_host', ''));
        $port = (int)Request::post('smtp_port', 587);
        $enc = Request::post('smtp_encryption', 'tls');
        $user = trim(Request::post('smtp_username', ''));
        $pass = Request::post('smtp_password', '');
        $fromName = trim(Request::post('from_name', ''));
        $fromEmail = trim(Request::post('from_email', ''));

        $existing = $db->query("SELECT * FROM email_settings LIMIT 1")->fetch();
        if (empty($pass) && $existing) {
            $pass = $existing['smtp_password']; // keep existing if unchanged
        }

        $db->exec("DELETE FROM email_settings");
        $stmt = $db->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_encryption, smtp_username, smtp_password, from_name, from_email, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$host, $port, $enc, $user, $pass, $fromName, $fromEmail]);

        $this->logAudit('Updated SMTP configuration', 'email_settings', null);
        Session::flash('success', 'SMTP settings updated successfully.');
        Response::redirect('/settings/smtp');
    }

    public function testSmtp(): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();

        $to = trim(Request::post('test_email', ''));
        $db = Database::getInstance();
        $smtp = $db->query("SELECT * FROM email_settings LIMIT 1")->fetch();

        if (!$smtp || empty($smtp['smtp_host'])) {
            Session::flash('error', 'SMTP settings not configured.');
            Response::redirect('/settings/smtp');
        }

        // Test socket connection
        $errno = 0; $errstr = '';
        $fp = @fsockopen($smtp['smtp_host'], (int)$smtp['smtp_port'], $errno, $errstr, 5);
        if (!$fp) {
            Session::flash('error', "Cannot connect to SMTP server: $errstr ($errno)");
        } else {
            fclose($fp);
            Session::flash('success', "SMTP server connection successful to {$smtp['smtp_host']}:{$smtp['smtp_port']}!");
        }

        Response::redirect('/settings/smtp');
    }

    public function systemHealth(): void
    {
        $this->requirePermission('security.manage');
        $db = Database::getInstance();

        $health = [
            'php_version' => [
                'name' => 'PHP Version',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'pass' : 'fail',
                'value' => PHP_VERSION,
                'requirement' => '>= 8.2.0'
            ],
            'pdo_sqlite' => [
                'name' => 'PDO SQLite Extension',
                'status' => extension_loaded('pdo_sqlite') ? 'pass' : 'fail',
                'value' => extension_loaded('pdo_sqlite') ? 'Enabled' : 'Missing',
                'requirement' => 'Required'
            ],
            'storage_write' => [
                'name' => 'Storage Directory Writable',
                'status' => is_writable(dirname(__DIR__, 2) . '/storage') ? 'pass' : 'fail',
                'value' => is_writable(dirname(__DIR__, 2) . '/storage') ? 'Writable' : 'Not writable',
                'requirement' => 'Writable'
            ],
            'uploads_write' => [
                'name' => 'Uploads Directory Writable',
                'status' => is_writable(dirname(__DIR__, 2) . '/storage/uploads') ? 'pass' : 'fail',
                'value' => is_writable(dirname(__DIR__, 2) . '/storage/uploads') ? 'Writable' : 'Not writable',
                'requirement' => 'Writable'
            ],
            'backups_write' => [
                'name' => 'Backups Directory Writable',
                'status' => is_writable(dirname(__DIR__, 2) . '/storage/backups') ? 'pass' : 'fail',
                'value' => is_writable(dirname(__DIR__, 2) . '/storage/backups') ? 'Writable' : 'Not writable',
                'requirement' => 'Writable'
            ],
            'foreign_keys' => [
                'name' => 'SQLite Foreign Keys Enforcement',
                'status' => ($db->query("PRAGMA foreign_keys")->fetchColumn() == 1) ? 'pass' : 'warning',
                'value' => ($db->query("PRAGMA foreign_keys")->fetchColumn() == 1) ? 'Active (ON)' : 'Inactive',
                'requirement' => 'Active'
            ],
            'installed_lock' => [
                'name' => 'Installer Lock File',
                'status' => file_exists(dirname(__DIR__, 2) . '/storage/installed.lock') ? 'pass' : 'fail',
                'value' => file_exists(dirname(__DIR__, 2) . '/storage/installed.lock') ? 'Locked (Secure)' : 'Unprotected',
                'requirement' => 'Locked'
            ]
        ];

        $this->render('settings/health', [
            'health' => $health,
            'user' => $this->currentUser()
        ]);
    }

    public function backups(): void
    {
        $this->requirePermission('security.manage');
        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        $files = glob($backupDir . '/*.sqlite');
        $backups = [];
        foreach ($files as $f) {
            $backups[] = [
                'filename' => basename($f),
                'size' => filesize($f),
                'created_at' => date('Y-m-d H:i:s', filemtime($f))
            ];
        }

        $this->render('settings/backups', [
            'backups' => $backups,
            'user' => $this->currentUser()
        ]);
    }

    public function createBackup(): void
    {
        $this->requirePermission('security.manage');
        $this->validateCsrf();

        $source = dirname(__DIR__, 2) . '/storage/database.sqlite';
        $destDir = dirname(__DIR__, 2) . '/storage/backups';
        $dest = $destDir . '/backup_' . date('Ymd_His') . '.sqlite';

        if (copy($source, $dest)) {
            $this->logAudit('Created database backup: ' . basename($dest), 'backup', null);
            Session::flash('success', 'Backup created successfully: ' . basename($dest));
        } else {
            Session::flash('error', 'Failed to copy database file.');
        }

        Response::redirect('/settings/backups');
    }

    public function downloadBackup(string $filename): void
    {
        $this->requirePermission('security.manage');
        $filename = basename($filename); // prevent traversal
        $filePath = dirname(__DIR__, 2) . '/storage/backups/' . $filename;

        if (!file_exists($filePath)) Response::abort(404);

        $this->logAudit('Downloaded database backup: ' . $filename, 'backup', null);

        header('Content-Type: application/x-sqlite3');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function auditLogs(): void
    {
        $this->requirePermission('security.manage');
        $db = Database::getInstance();

        $stmt = $db->query("
            SELECT al.*, u.name as user_name, u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 100
        ");
        $logs = $stmt->fetchAll();

        $this->render('settings/audit_logs', [
            'logs' => $logs,
            'user' => $this->currentUser()
        ]);
    }

    public function sessions(): void
    {
        $this->requirePermission('security.manage');
        $db = Database::getInstance();

        $stmt = $db->query("
            SELECT s.*, u.name as user_name, u.email as user_email
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.last_activity DESC
            LIMIT 50
        ");
        $sessions = $stmt->fetchAll();

        $this->render('settings/sessions', [
            'sessions' => $sessions,
            'user' => $this->currentUser()
        ]);
    }

    public function destroySession(string $id): void
    {
        $this->requirePermission('security.manage');
        $this->validateCsrf();
        $db = Database::getInstance();

        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('Terminated active session', 'sessions', $id);
        Session::flash('success', 'Session terminated.');
        Response::redirect('/settings/sessions');
    }

    public function maintenance(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $db = Database::getInstance();
        $isMaintenance = (bool)$db->query("SELECT value FROM settings WHERE key = 'maintenance_mode'")->fetchColumn();

        $this->render('settings/maintenance', [
            'isMaintenance' => $isMaintenance,
            'user' => $this->currentUser()
        ]);
    }

    public function toggleMaintenance(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();
        $db = Database::getInstance();

        $curr = (bool)$db->query("SELECT value FROM settings WHERE key = 'maintenance_mode'")->fetchColumn();
        $newVal = $curr ? '0' : '1';

        $db->prepare("INSERT INTO settings (key, value, updated_at) VALUES ('maintenance_mode', ?, datetime('now')) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')")
           ->execute([$newVal]);

        $this->logAudit('maintenance.toggle', 'settings', null, ['maintenance_mode' => $newVal]);
        Session::flash('success', $newVal === '1' ? 'Maintenance Mode is now ENABLED. Non-admin users will see the maintenance screen.' : 'Maintenance Mode is now DISABLED. Normal LMS access restored.');
        Response::redirect('/settings/maintenance');
    }

    public function emailTemplates(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $db = Database::getInstance();

        // Seed default templates if empty
        $count = (int)$db->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
        if ($count === 0) {
            $defaults = \App\Services\MailService::getDefaultTemplates();
            $ins = $db->prepare("INSERT INTO email_templates (template_key, subject, body, is_enabled, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))");
            foreach ($defaults as $k => $tmpl) {
                $ins->execute([$k, $tmpl['subject'], $tmpl['body'], $tmpl['is_enabled']]);
            }
        }

        $templates = $db->query("SELECT * FROM email_templates ORDER BY template_key ASC")->fetchAll();

        $this->render('settings/email_templates', [
            'templates' => $templates,
            'user' => $this->currentUser()
        ]);
    }

    public function updateEmailTemplate(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();
        $db = Database::getInstance();

        $subject = trim((string)Request::post('subject', ''));
        $body = trim((string)Request::post('body', ''));
        $isEnabled = !empty(Request::post('is_enabled')) ? 1 : 0;

        $db->prepare("UPDATE email_templates SET subject = ?, body = ?, is_enabled = ?, updated_at = datetime('now') WHERE id = ?")
           ->execute([$subject, $body, $isEnabled, (int)$id]);

        $this->logAudit('email_template.update', 'email_templates', (int)$id);
        Session::flash('success', 'Email template updated successfully.');
        Response::redirect('/settings/email-templates');
    }

    private function storeUpload(array $up, string $type): int
    {
        $db = Database::getInstance();
        $user = $this->currentUser();
        $stmt = $db->prepare("INSERT INTO uploads (filename, original_name, mime_type, file_size, file_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$up['filename'], $up['original_name'], $up['mime_type'], $up['file_size'], $type, $user['id']]);
        return (int)$db->lastInsertId();
    }
}
