<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class AttendanceController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            // Student personal attendance summary
            $stmt = $db->prepare("
                SELECT a.*, s.session_date, s.title as session_title, b.name as batch_name
                FROM attendance a
                JOIN attendance_sessions s ON a.session_id = s.id
                JOIN batches b ON s.batch_id = b.id
                WHERE a.user_id = ?
                ORDER BY s.session_date DESC
            ");
            $stmt->execute([$user['id']]);
            $records = $stmt->fetchAll();

            $total = count($records);
            $present = 0;
            foreach ($records as $r) {
                if ($r['status'] === 'present' || $r['status'] === 'late') $present++;
            }
            $pct = $total > 0 ? round(($present / $total) * 100, 1) : 100;

            $this->render('attendance/student_view', [
                'records' => $records,
                'total' => $total,
                'percentage' => $pct,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher: Sessions list
        $this->requirePermission('batches.view');
        $batchId = Request::get('batch_id') ? (int)Request::get('batch_id') : null;

        $batches = $db->query("SELECT id, name, code FROM batches WHERE status != 'archived' ORDER BY name ASC")->fetchAll();

        $sql = "
            SELECT s.*, b.name as batch_name, u.name as creator_name,
                   (SELECT COUNT(*) FROM attendance WHERE session_id = s.id AND status = 'present') as present_count,
                   (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as total_marked
            FROM attendance_sessions s
            JOIN batches b ON s.batch_id = b.id
            JOIN users u ON s.created_by = u.id
        ";
        if ($batchId) {
            $sql .= " WHERE s.batch_id = $batchId";
        }
        $sql .= " ORDER BY s.session_date DESC";
        $sessions = $db->query($sql)->fetchAll();

        $this->render('attendance/index', [
            'sessions' => $sessions,
            'batches' => $batches,
            'selectedBatch' => $batchId,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('batches.edit');
        $db = Database::getInstance();
        $batches = $db->query("SELECT id, name, code FROM batches WHERE status != 'archived' ORDER BY name ASC")->fetchAll();
        $this->render('attendance/create', [
            'batches' => $batches,
            'user' => $this->currentUser()
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('batches.edit');
        $this->validateCsrf();

        $batchId = (int)Request::post('batch_id');
        $title = trim(Request::post('title', ''));
        $date = Request::post('session_date', date('Y-m-d'));

        if (!$batchId || empty($title)) {
            Session::flash('error', 'Batch and title are required.');
            Response::redirect('/attendance/create');
        }

        $db = Database::getInstance();
        $user = $this->currentUser();

        $stmt = $db->prepare("
            INSERT INTO attendance_sessions (batch_id, title, session_date, created_by, created_at)
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([$batchId, $title, $date, $user['id']]);
        $sessionId = $db->lastInsertId();

        // Auto-seed active students with 'present'
        $students = $db->prepare("SELECT student_id FROM batch_students WHERE batch_id = ? AND status = 'active'");
        $students->execute([$batchId]);
        $ins = $db->prepare("INSERT INTO attendance (session_id, user_id, status) VALUES (?, ?, 'present')");
        foreach ($students->fetchAll() as $s) {
            $ins->execute([$sessionId, $s['student_id']]);
        }

        Session::flash('success', 'Attendance session created.');
        Response::redirect('/attendance/' . $sessionId);
    }

    public function show(int $id): void
    {
        $this->requirePermission('batches.view');
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT s.*, b.name as batch_name 
            FROM attendance_sessions s 
            JOIN batches b ON s.batch_id = b.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $session = $stmt->fetch();
        if (!$session) Response::abort(404);

        $attStmt = $db->prepare("
            SELECT a.*, u.name as student_name, u.email as student_email
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE a.session_id = ?
            ORDER BY u.name ASC
        ");
        $attStmt->execute([$id]);
        $attendance = $attStmt->fetchAll();

        $this->render('attendance/show', [
            'session' => $session,
            'attendance' => $attendance,
            'user' => $this->currentUser()
        ]);
    }

    public function update(int $id): void
    {
        $this->requirePermission('batches.edit');
        $this->validateCsrf();
        $db = Database::getInstance();

        $statuses = Request::post('statuses', []); // [user_id => 'present'|'absent'|'late'|'excused']
        $upd = $db->prepare("UPDATE attendance SET status = ? WHERE session_id = ? AND user_id = ?");

        foreach ($statuses as $userId => $status) {
            if (in_array($status, ['present', 'absent', 'late', 'excused'])) {
                $upd->execute([$status, $id, $userId]);
            }
        }

        Session::flash('success', 'Attendance record updated.');
        Response::redirect('/attendance/' . $id);
    }
}
