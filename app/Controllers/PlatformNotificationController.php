<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Services\MailService;
use Throwable;

/**
 * Lazy LMS - Targeted Platform Notification System
 * Admin announcements targeting Entire Platform, Specific Batch, Teachers only, Students only, or Specific User.
 */
class PlatformNotificationController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        // If Admin, show creation and history management
        if (AuthHelper::hasRole(['super_admin', 'admin'])) {
            $notifications = Database::fetchAll("
                SELECT pn.*, u.name as creator_name, b.name as batch_name,
                       (SELECT COUNT(*) FROM notification_reads WHERE notification_id = pn.id) as read_count
                FROM platform_notifications pn
                LEFT JOIN users u ON pn.created_by = u.id
                LEFT JOIN batches b ON pn.target_id = b.id AND pn.target_type = 'batch'
                ORDER BY pn.created_at DESC
            ");

            $batches = Database::fetchAll("SELECT id, name, public_id FROM batches WHERE status = 'active' ORDER BY name ASC");

            $this->render('notifications/admin_index', [
                'notifications' => $notifications,
                'batches' => $batches,
                'user' => $user
            ]);
            return;
        }

        // For Students & Teachers: Load targeted published notifications
        $notifications = $this->getUserNotifications((int)$user['id'], $user['role']);

        $this->render('notifications/center', [
            'notifications' => $notifications,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $batches = Database::fetchAll("SELECT id, name, public_id FROM batches WHERE status = 'active' ORDER BY name ASC");
        $users = Database::fetchAll("SELECT id, name, email, public_id FROM users WHERE status = 'active' ORDER BY name ASC LIMIT 100");

        $this->render('notifications/create', [
            'batches' => $batches,
            'users' => $users,
            'user' => $this->currentUser()
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $title = trim((string)Request::post('title', ''));
        $message = trim((string)Request::post('message', ''));
        $targetType = Request::post('target_type', 'all');
        $targetId = !empty(Request::post('target_id')) ? (int)Request::post('target_id') : null;
        $priority = Request::post('priority', 'normal');
        $sendEmail = !empty(Request::post('send_email')) ? 1 : 0;
        $startTime = Request::post('start_time') ?: date('Y-m-d H:i:s');
        $expiryTime = Request::post('expiry_time') ?: null;
        $status = Request::post('status', 'published');

        if (empty($title) || empty($message)) {
            Session::flash('error', 'Title and Message are required.');
            Response::redirect('/platform-notifications/create');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('platform_notifications');
        $currentUser = $this->currentUser();

        $stmt = $db->prepare("
            INSERT INTO platform_notifications (
                public_id, title, message, target_type, target_id, priority,
                send_email, start_time, expiry_time, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([
            $publicId, $title, $message, $targetType, $targetId, $priority,
            $sendEmail, $startTime, $expiryTime, $status, $currentUser['id']
        ]);

        $notificationId = $db->lastInsertId();

        // If send_email is checked and status is published, dispatch emails to target audience
        if ($sendEmail && $status === 'published') {
            $this->dispatchEmailsToAudience($targetType, $targetId, $title, $message, $priority);
        }

        $this->logAudit('platform_notification.create', 'platform_notifications', $notificationId, [
            'title' => $title,
            'target_type' => $targetType,
            'priority' => $priority
        ]);

        Session::flash('success', 'Platform notification dispatched successfully.');
        Response::redirect('/platform-notifications');
    }

    public function markRead(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $nId = HashId::resolveId('platform_notifications', $id);
        if ($nId) {
            $db->prepare("
                INSERT OR IGNORE INTO notification_reads (notification_id, user_id, read_at) 
                VALUES (?, ?, datetime('now'))
            ")->execute([$nId, $user['id']]);
        }

        if (Request::isAjax()) {
            Response::json(['success' => true]);
            return;
        }

        Response::redirect('/platform-notifications');
    }

    public function markAllRead(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $userNotifs = $this->getUserNotifications((int)$user['id'], $user['role']);
        foreach ($userNotifs as $n) {
            $db->prepare("
                INSERT OR IGNORE INTO notification_reads (notification_id, user_id, read_at) 
                VALUES (?, ?, datetime('now'))
            ")->execute([$n['id'], $user['id']]);
        }

        if (Request::isAjax()) {
            Response::json(['success' => true]);
            return;
        }

        Session::flash('success', 'All notifications marked as read.');
        Response::redirect('/platform-notifications');
    }

    public function getUnreadCount(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $userNotifs = $this->getUserNotifications((int)$user['id'], $user['role']);
        $unreadCount = count(array_filter($userNotifs, fn($n) => empty($n['read_at'])));

        Response::json(['unread' => $unreadCount]);
    }

    private function getUserNotifications(int $userId, string $role): array
    {
        $db = Database::getInstance();
        
        // Find student batch ID if student
        $batchId = null;
        if ($role === 'student') {
            $batchId = Database::fetchColumn("SELECT batch_id FROM batch_students WHERE student_id = ? AND status = 'active' LIMIT 1", [$userId]);
        } elseif ($role === 'teacher') {
            $batchId = Database::fetchColumn("SELECT batch_id FROM batch_teachers WHERE teacher_id = ? LIMIT 1", [$userId]);
        }

        $sql = "
            SELECT pn.*, nr.read_at, u.name as creator_name
            FROM platform_notifications pn
            LEFT JOIN users u ON pn.created_by = u.id
            LEFT JOIN notification_reads nr ON pn.id = nr.notification_id AND nr.user_id = ?
            WHERE pn.status = 'published'
              AND (pn.start_time IS NULL OR datetime(pn.start_time) <= datetime('now'))
              AND (pn.expiry_time IS NULL OR datetime(pn.expiry_time) >= datetime('now'))
              AND (
                  pn.target_type = 'all'
                  OR (pn.target_type = 'teachers' AND ? = 'teacher')
                  OR (pn.target_type = 'students' AND ? = 'student')
                  OR (pn.target_type = 'user' AND pn.target_id = ?)
                  OR (pn.target_type = 'batch' AND pn.target_id = ?)
              )
            ORDER BY pn.created_at DESC
        ";

        return Database::fetchAll($sql, [$userId, $role, $role, $userId, $batchId]);
    }

    private function dispatchEmailsToAudience(string $targetType, ?int $targetId, string $title, string $message, string $priority): void
    {
        $db = Database::getInstance();
        $recipients = [];

        if ($targetType === 'all') {
            $recipients = Database::fetchAll("SELECT id, name, email FROM users WHERE status = 'active'");
        } elseif ($targetType === 'teachers') {
            $recipients = Database::fetchAll("SELECT u.id, u.name, u.email FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'teacher' AND u.status = 'active'");
        } elseif ($targetType === 'students') {
            $recipients = Database::fetchAll("SELECT u.id, u.name, u.email FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' AND u.status = 'active'");
        } elseif ($targetType === 'batch' && $targetId) {
            $recipients = Database::fetchAll("
                SELECT u.id, u.name, u.email FROM users u
                JOIN batch_students bs ON u.id = bs.student_id
                WHERE bs.batch_id = ? AND bs.status = 'active'
                UNION
                SELECT u.id, u.name, u.email FROM users u
                JOIN batch_teachers bt ON u.id = bt.teacher_id
                WHERE bt.batch_id = ?
            ", [$targetId, $targetId]);
        } elseif ($targetType === 'user' && $targetId) {
            $recipients = Database::fetchAll("SELECT id, name, email FROM users WHERE id = ? AND status = 'active'", [$targetId]);
        }

        $subject = "[{$priority}] {$title}";
        $html = "<h2>{$title}</h2><p>" . nl2br(htmlspecialchars($message)) . "</p><p style='color: #64748b; font-size: 0.85rem;'>Priority: " . ucfirst($priority) . "</p>";

        foreach ($recipients as $rec) {
            if (!empty($rec['email'])) {
                MailService::send($rec['email'], $rec['name'], $subject, $html, (int)$rec['id']);
            }
        }
    }
}
