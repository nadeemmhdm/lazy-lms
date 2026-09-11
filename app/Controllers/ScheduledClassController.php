<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

/**
 * Lazy LMS - Scheduled Live Classes Controller
 * Supports Zoom, Google Meet, Microsoft Teams links with batch and course authorization.
 */
class ScheduledClassController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $sql = "
            SELECT sc.*, c.title as course_title, b.name as batch_name,
                   u.name as teacher_name, l.title as lesson_title
            FROM scheduled_classes sc
            JOIN courses c ON sc.course_id = c.id
            JOIN batches b ON sc.batch_id = b.id
            LEFT JOIN users u ON sc.teacher_id = u.id
            LEFT JOIN lessons l ON sc.lesson_id = l.id
        ";

        $params = [];
        if ($user['role'] === 'student') {
            // Only show classes for student's active batch
            $sql .= " JOIN batch_students bs ON b.id = bs.batch_id WHERE bs.student_id = ? AND bs.status = 'active'";
            $params[] = $user['id'];
        } elseif ($user['role'] === 'teacher' && !AuthHelper::hasRole(['super_admin', 'admin'])) {
            $sql .= " WHERE sc.teacher_id = ? OR sc.batch_id IN (SELECT batch_id FROM batch_teachers WHERE teacher_id = ?)";
            $params[] = $user['id'];
            $params[] = $user['id'];
        }

        $sql .= " ORDER BY sc.class_date DESC, sc.start_time ASC";
        $classes = Database::fetchAll($sql, $params);

        $this->render('classes/index', [
            'classes' => $classes,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('courses.edit');
        $db = Database::getInstance();
        $user = $this->currentUser();

        $courses = $db->query("SELECT id, title, public_id FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $batches = $db->query("SELECT id, name, public_id FROM batches WHERE status = 'active' ORDER BY name ASC")->fetchAll();
        $teachers = $db->query("SELECT u.id, u.name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'teacher' AND u.status = 'active' ORDER BY u.name ASC")->fetchAll();

        $this->render('classes/create', [
            'courses' => $courses,
            'batches' => $batches,
            'teachers' => $teachers,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('courses.edit');
        $this->validateCsrf();

        $title = trim((string)Request::post('title', ''));
        $courseId = HashId::resolveId('courses', Request::post('course_id'));
        $batchId = HashId::resolveId('batches', Request::post('batch_id'));
        $teacherId = !empty(Request::post('teacher_id')) ? (int)Request::post('teacher_id') : (int)$this->currentUser()['id'];
        $classDate = Request::post('class_date');
        $startTime = Request::post('start_time');
        $endTime = Request::post('end_time');
        $meetingUrl = trim((string)Request::post('meeting_url', ''));
        $description = trim((string)Request::post('description', ''));

        if (empty($title) || !$courseId || !$batchId || empty($classDate) || empty($startTime) || empty($endTime)) {
            Session::flash('error', 'Title, Course, Batch, Date, and Times are required.');
            Response::redirect('/classes/create');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('scheduled_classes');

        $stmt = $db->prepare("
            INSERT INTO scheduled_classes (
                public_id, title, course_id, batch_id, teacher_id, class_date,
                start_time, end_time, meeting_url, description, status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $publicId, $title, $courseId, $batchId, $teacherId, $classDate,
            $startTime, $endTime, $meetingUrl, $description
        ]);

        $this->logAudit('class.create', 'scheduled_classes', $db->lastInsertId(), ['title' => $title]);
        Session::flash('success', 'Live class scheduled successfully.');
        Response::redirect('/classes');
    }

    public function cancel(string|int $id): void
    {
        $this->requirePermission('courses.edit');
        $this->validateCsrf();

        $cid = HashId::resolveId('scheduled_classes', $id);
        if ($cid) {
            Database::query("UPDATE scheduled_classes SET status = 'cancelled', updated_at = datetime('now') WHERE id = ?", [$cid]);
            Session::flash('success', 'Scheduled class has been cancelled.');
        }

        Response::redirect('/classes');
    }
}
