<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class AnnouncementController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $user = $this->currentUser();

        if ($user['role'] === 'student') {
            // Fetch announcements targeted to all, or student's batch
            $stmt = $db->prepare("
                SELECT a.*, u.name as author_name 
                FROM announcements a
                JOIN users u ON a.created_by = u.id
                WHERE a.target_type = 'global' 
                   OR (a.target_type = 'batch' AND a.target_id IN (
                       SELECT batch_id FROM batch_students WHERE student_id = ? AND status = 'active'
                   ))
                ORDER BY a.priority = 'high' DESC, a.created_at DESC
            ");
            $stmt->execute([$user['id']]);
            $announcements = $stmt->fetchAll();

            $this->render('announcements/student_index', [
                'announcements' => $announcements,
                'user' => $user
            ]);
            return;
        }

        // Admin and teachers
        $this->requirePermission('announcements.view');
        $stmt = $db->query("
            SELECT a.*, u.name as author_name,
                   CASE 
                     WHEN a.target_type = 'batch' THEN (SELECT name FROM batches WHERE id = a.target_id)
                     WHEN a.target_type = 'course' THEN (SELECT title FROM courses WHERE id = a.target_id)
                     ELSE 'All Users'
                   END as target_name
            FROM announcements a
            JOIN users u ON a.created_by = u.id
            ORDER BY a.created_at DESC
        ");
        $announcements = $stmt->fetchAll();

        // Get batches and courses for target selection in modal/form
        $batches = $db->query("SELECT id, name, code FROM batches WHERE status != 'archived' ORDER BY name ASC")->fetchAll();
        $courses = $db->query("SELECT id, title, code FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();

        $this->render('announcements/index', [
            'announcements' => $announcements,
            'batches' => $batches,
            'courses' => $courses,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('announcements.create');
        $this->validateCsrf();

        $title = trim(Request::post('title', ''));
        $content = trim(Request::post('content', ''));
        $targetType = Request::post('target_type', 'global');
        $targetId = Request::post('target_id') ? (int)Request::post('target_id') : null;
        $priority = Request::post('priority', 'normal');

        if (empty($title) || empty($content)) {
            Session::flash('error', 'Title and content are required.');
            Response::redirect('/announcements');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO announcements (title, content, target_type, target_id, priority, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $user = $this->currentUser();
        $stmt->execute([$title, $content, $targetType, $targetId, $priority, $user['id']]);
        $announcementId = $db->lastInsertId();

        // Notify target users
        if ($targetType === 'global') {
            $users = $db->query("SELECT id FROM users WHERE status = 'active'")->fetchAll();
            $nStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link, type, created_at) VALUES (?, ?, ?, ?, 'announcement', datetime('now'))");
            foreach ($users as $u) {
                if ($u['id'] !== $user['id']) {
                    $nStmt->execute([$u['id'], "Announcement: $title", substr(strip_tags($content), 0, 150), '/announcements']);
                }
            }
        } elseif ($targetType === 'batch' && $targetId) {
            $students = $db->prepare("SELECT student_id FROM batch_students WHERE batch_id = ? AND status = 'active'");
            $students->execute([$targetId]);
            $nStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link, type, created_at) VALUES (?, ?, ?, ?, 'announcement', datetime('now'))");
            foreach ($students->fetchAll() as $s) {
                $nStmt->execute([$s['student_id'], "Batch Announcement: $title", substr(strip_tags($content), 0, 150), '/announcements']);
            }
        }

        Session::flash('success', 'Announcement published successfully.');
        Response::redirect('/announcements');
    }

    public function delete(int $id): void
    {
        $this->requirePermission('announcements.delete');
        $this->validateCsrf();

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);

        Session::flash('success', 'Announcement deleted.');
        Response::redirect('/announcements');
    }
}
