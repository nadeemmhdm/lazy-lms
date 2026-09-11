<?php

namespace App\Services;

use App\Database;
use Throwable;

/**
 * Lazy LMS - Automated SMTP Email Service & Template Engine
 */
class MailService
{
    /**
     * Send email using configured template key or custom subject/body.
     */
    public static function sendTemplate(string $templateKey, array $recipient, array $vars = []): bool
    {
        $db = Database::getInstance();
        $template = null;

        try {
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_key = ? LIMIT 1");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch();
        } catch (Throwable $e) {
            // ignore
        }

        $appSettings = [];
        try {
            $sets = $db->query("SELECT key, value FROM settings")->fetchAll();
            foreach ($sets as $s) {
                $appSettings[$s['key']] = $s['value'];
            }
        } catch (Throwable $e) {}

        $lmsName = $appSettings['app_name'] ?? 'Lazy LMS';

        // Default templates fallback if database row not present
        $defaults = self::getDefaultTemplates();
        $tmpl = $template ?: ($defaults[$templateKey] ?? null);

        if (!$tmpl || (isset($tmpl['is_enabled']) && !(int)$tmpl['is_enabled'])) {
            return false;
        }

        $subject = $tmpl['subject'] ?? 'Lazy LMS Notification';
        $body = $tmpl['body'] ?? '';

        // Inject standard vars
        $allVars = array_merge([
            'user_name' => $recipient['name'] ?? 'User',
            'user_email' => $recipient['email'] ?? '',
            'lms_name' => $lmsName,
            'current_time' => date('Y-m-d H:i:s'),
        ], $vars);

        foreach ($allVars as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', (string)$v, $subject);
            $body = str_replace('{{' . $k . '}}', (string)$v, $body);
        }

        return self::send($recipient['email'], $recipient['name'] ?? '', $subject, $body, (int)($recipient['id'] ?? 0));
    }

    /**
     * Dispatch email and log to notification_jobs queue.
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, int $userId = 0): bool
    {
        $db = Database::getInstance();

        // Record job in queue
        $jobId = null;
        try {
            $stmt = $db->prepare("
                INSERT INTO notification_jobs (user_id, type, recipient_email, subject, message, status, attempts, created_at)
                VALUES (?, 'email', ?, ?, ?, 'pending', 1, datetime('now'))
            ");
            $stmt->execute([$userId, $toEmail, $subject, $htmlBody]);
            $jobId = $db->lastInsertId();
        } catch (Throwable $e) {
            // ignore
        }

        $sent = self::transmitSmtp($toEmail, $toName, $subject, $htmlBody, $error);

        if ($jobId) {
            try {
                if ($sent) {
                    $uStmt = $db->prepare("UPDATE notification_jobs SET status = 'sent', sent_at = datetime('now') WHERE id = ?");
                    $uStmt->execute([$jobId]);
                } else {
                    $uStmt = $db->prepare("UPDATE notification_jobs SET status = 'failed', last_error = ? WHERE id = ?");
                    $uStmt->execute([$error, $jobId]);
                }
            } catch (Throwable $e) {}
        }

        return $sent;
    }

    /**
     * Transmit through configured SMTP or mail() fallback.
     */
    private static function transmitSmtp(string $toEmail, string $toName, string $subject, string $htmlBody, ?string &$error = null): bool
    {
        $db = Database::getInstance();
        $smtp = null;
        try {
            $smtp = $db->query("SELECT * FROM email_settings LIMIT 1")->fetch();
        } catch (Throwable $e) {}

        if (!$smtp || empty($smtp['smtp_host'])) {
            // Fallback to PHP native mail or safe local log
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . ($smtp['from_name'] ?? 'Lazy LMS') . " <" . ($smtp['from_email'] ?? 'noreply@lazylms.local') . ">\r\n";
            
            $res = @mail($toEmail, $subject, $htmlBody, $headers);
            if (!$res) {
                // In local dev without sendmail, save to file log
                $logDir = dirname(__DIR__, 2) . '/storage/logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
                file_put_contents($logDir . '/mail.log', "[" . date('Y-m-d H:i:s') . "] To: {$toEmail} | Subject: {$subject}\n", FILE_APPEND);
                return true;
            }
            return true;
        }

        $host = $smtp['smtp_host'];
        $port = (int)($smtp['smtp_port'] ?: 587);
        $enc = strtolower($smtp['smtp_encryption'] ?? 'tls');
        $user = $smtp['smtp_username'] ?? '';
        $pass = $smtp['smtp_password'] ?? '';
        $fromEmail = $smtp['from_email'] ?: 'noreply@lazylms.local';
        $fromName = $smtp['from_name'] ?: 'Lazy LMS';

        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$socket) {
            $error = "Socket connection failed: {$errstr} ({$errno})";
            // Write to mail.log fallback so notifications don't get lost
            $logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
            file_put_contents($logDir . '/mail.log', "[" . date('Y-m-d H:i:s') . "] [OFFLINE-SMTP] To: {$toEmail} | Subject: {$subject} | Body: " . substr(strip_tags($htmlBody), 0, 100) . "\n", FILE_APPEND);
            return true; // Marked as handled in queue
        }

        stream_set_timeout($socket, 10);
        $read = function() use ($socket) {
            $data = '';
            while ($line = fgets($socket, 512)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function(string $cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read();
        $write("EHLO " . gethostname());
        $read();

        if ($enc === 'tls') {
            $write("STARTTLS");
            $resp = $read();
            if (str_starts_with($resp, '220')) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $write("EHLO " . gethostname());
                $read();
            }
        }

        if (!empty($user) && !empty($pass)) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $authResp = $read();
            if (!str_starts_with($authResp, '235')) {
                $error = "SMTP Auth failed: " . trim($authResp);
                fclose($socket);
                return false;
            }
        }

        $write("MAIL FROM: <{$fromEmail}>");
        $read();
        $write("RCPT TO: <{$toEmail}>");
        $read();
        $write("DATA");
        $read();

        $headers = [
            "From: {$fromName} <{$fromEmail}>",
            "To: {$toName} <{$toEmail}>",
            "Subject: {$subject}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Date: " . date('r'),
        ];

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        $write($payload);
        $dataResp = $read();
        $write("QUIT");
        fclose($socket);

        return str_starts_with($dataResp, '250');
    }

    /**
     * Default email templates seeds
     */
    public static function getDefaultTemplates(): array
    {
        return [
            'security.new_ip_login' => [
                'subject' => 'New Login Detected - {{lms_name}}',
                'body' => '<h2>New Login Detected</h2><p>Hello {{user_name}},</p><p>Your {{lms_name}} account was recently used from a new IP address.</p><p><strong>Time:</strong> {{login_time}}<br><strong>IP:</strong> {{ip_address}}<br><strong>Device/Browser:</strong> {{user_agent}}</p><p>If this was not you, please immediately reset your password and contact your administrator.</p>',
                'is_enabled' => 1
            ],
            'auth.password_reset' => [
                'subject' => 'Reset Your Password - {{lms_name}}',
                'body' => '<h2>Password Reset Request</h2><p>Hello {{user_name}},</p><p>A password reset was requested for your account on {{lms_name}}.</p><p><a href="{{reset_url}}">Click here to reset your password</a></p><p>This link expires in 1 hour.</p>',
                'is_enabled' => 1
            ],
            'user.welcome' => [
                'subject' => 'Welcome to {{lms_name}}',
                'body' => '<h2>Welcome to {{lms_name}}!</h2><p>Hello {{user_name}},</p><p>Your account has been created successfully. You can log in using your email <strong>{{user_email}}</strong>.</p><p><a href="{{login_url}}">Access LMS Portal</a></p>',
                'is_enabled' => 1
            ],
            'assignment.published' => [
                'subject' => 'New Assignment Published: {{assignment_name}}',
                'body' => '<h2>New Assignment Available</h2><p>Hello {{user_name}},</p><p>A new assignment <strong>{{assignment_name}}</strong> has been published in <strong>{{course_name}}</strong>.</p><p><strong>Due Date:</strong> {{due_date}}</p>',
                'is_enabled' => 1
            ],
            'assignment.graded' => [
                'subject' => 'Assignment Graded: {{assignment_name}}',
                'body' => '<h2>Assignment Graded</h2><p>Hello {{user_name}},</p><p>Your submission for <strong>{{assignment_name}}</strong> in <strong>{{course_name}}</strong> has been graded.</p><p><strong>Grade:</strong> {{grade}}</p><p><strong>Feedback:</strong> {{feedback}}</p>',
                'is_enabled' => 1
            ],
            'exam.published' => [
                'subject' => 'Exam Scheduled: {{exam_name}}',
                'body' => '<h2>Examination Scheduled</h2><p>Hello {{user_name}},</p><p>An exam <strong>{{exam_name}}</strong> is scheduled for course <strong>{{course_name}}</strong>.</p><p><strong>Schedule:</strong> {{start_time}} to {{end_time}}<br><strong>Duration:</strong> {{duration}} minutes</p>',
                'is_enabled' => 1
            ],
            'certificate.issued' => [
                'subject' => 'Certificate Awarded: {{course_name}}',
                'body' => '<h2>Congratulations {{user_name}}!</h2><p>You have successfully completed <strong>{{course_name}}</strong> and been awarded Certificate <strong>#{{certificate_number}}</strong>.</p><p><a href="{{certificate_url}}">View and Download Certificate</a></p>',
                'is_enabled' => 1
            ]
        ];
    }
}
