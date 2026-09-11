<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class NotificationController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll();

        $this->render('notifications/index', [
            'notifications' => $notifications,
            'user' => $user
        ]);
    }

    public function markRead(int $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);

        if (Request::isAjax()) {
            Response::json(['success' => true]);
            return;
        }

        Response::redirect('/notifications');
    }

    public function markAllRead(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        Session::flash('success', 'All notifications marked as read.');
        Response::redirect('/notifications');
    }

    public function getUnread(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$user['id']]);
        $unread = $stmt->fetchAll();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([$user['id']]);
        $count = (int)$countStmt->fetchColumn();

        Response::json([
            'count' => $count,
            'notifications' => $unread
        ]);
    }
}
