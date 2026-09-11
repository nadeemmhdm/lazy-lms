<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Session;
use App\Database;

class BatchController extends BaseController {
    public function index(Request $request): string {
        $status = $request->query('status', 'active');
        $search = trim((string)$request->query('q', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];

        if ($status !== 'all') {
            $where .= " AND b.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where .= " AND (b.name LIKE ? OR b.code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $total = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM batches b WHERE {$where}", $params)['cnt'] ?? 0);

        $sql = "SELECT b.*, 
                       COUNT(DISTINCT bs.student_id) as student_count,
                       COUNT(DISTINCT bc.course_id) as course_count
                FROM batches b 
                LEFT JOIN batch_students bs ON b.id = bs.batch_id AND bs.status = 'active'
                LEFT JOIN batch_courses bc ON b.id = bc.batch_id 
                WHERE {$where}
                GROUP BY b.id 
                ORDER BY b.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $batches = Database::fetchAll($sql, $params);

        return $this->render('admin/batches/index', [
            'title' => 'Batch Management',
            'batches' => $batches,
            'total' => $total,
            'currentPage' => $page,
            'lastPage' => max(1, ceil($total / $perPage)),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(Request $request): string {
        return $this->render('admin/batches/create', [
            'title' => 'Create Academic Batch',
        ]);
    }

    public function store(Request $request): never {
        $errors = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'code' => 'required|min:2|max:30',
            'status' => 'required|in:draft,active,completed,archived',
        ]);

        if (!empty($errors)) {
            redirect('/admin/batches/create');
        }

        // Check code uniqueness
        $existing = Database::fetchOne("SELECT id FROM batches WHERE code = ?", [$request->input('code')]);
        if ($existing) {
            Session::flash('error', 'A batch with this code already exists.');
            redirect('/admin/batches/create');
        }

        Database::query(
            "INSERT INTO batches (name, code, description, start_date, end_date, status, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $request->input('name'),
                strtoupper((string)$request->input('code')),
                $request->input('description'),
                $request->input('start_date') ?: null,
                $request->input('end_date') ?: null,
                $request->input('status', 'active'),
            ]
        );
        $batchId = (int)Database::lastInsertId();

        $this->audit('batch.created', 'batch', $batchId, ['name' => $request->input('name'), 'code' => $request->input('code')]);
        Session::flash('success', 'Batch created successfully!');
        redirect('/admin/batches/' . $batchId);
    }

    public function show(Request $request, string|int $id): string {
        $batch = Database::fetchOne("SELECT * FROM batches WHERE id = ?", [$id]);
        if (!$batch) {
            Session::flash('error', 'Batch not found.');
            redirect('/admin/batches');
        }

        // Students in batch
        $students = Database::fetchAll(
            "SELECT u.*, bs.enrolled_at, bs.status as enrollment_status 
             FROM users u 
             JOIN batch_students bs ON u.id = bs.student_id 
             WHERE bs.batch_id = ? 
             ORDER BY u.name ASC",
            [$id]
        );

        // Courses in batch
        $courses = Database::fetchAll(
            "SELECT c.*, bc.assigned_at 
             FROM courses c 
             JOIN batch_courses bc ON c.id = bc.course_id 
             WHERE bc.batch_id = ? 
             ORDER BY c.title ASC",
            [$id]
        );

        // Teachers in batch
        $teachers = Database::fetchAll(
            "SELECT u.*, bt.assigned_at 
             FROM users u 
             JOIN batch_teachers bt ON u.id = bt.teacher_id 
             WHERE bt.batch_id = ? 
             ORDER BY u.name ASC",
            [$id]
        );

        // Available active students not in this batch
        $availableStudents = Database::fetchAll(
            "SELECT u.id, u.name, u.email, u.student_id 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.slug = 'student' AND u.status = 'active' 
               AND u.id NOT IN (SELECT student_id FROM batch_students WHERE batch_id = ?) 
             ORDER BY u.name ASC",
            [$id]
        );

        // Available courses not in this batch
        $availableCourses = Database::fetchAll(
            "SELECT id, title, code 
             FROM courses 
             WHERE status = 'published' 
               AND id NOT IN (SELECT course_id FROM batch_courses WHERE batch_id = ?) 
             ORDER BY title ASC",
            [$id]
        );

        // Available teachers not in this batch
        $availableTeachers = Database::fetchAll(
            "SELECT u.id, u.name, u.email 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.slug = 'teacher' AND u.status = 'active' 
               AND u.id NOT IN (SELECT teacher_id FROM batch_teachers WHERE batch_id = ?) 
             ORDER BY u.name ASC",
            [$id]
        );

        return $this->render('admin/batches/show', [
            'title' => 'Batch: ' . $batch['name'],
            'batch' => $batch,
            'students' => $students,
            'courses' => $courses,
            'teachers' => $teachers,
            'availableStudents' => $availableStudents,
            'availableCourses' => $availableCourses,
            'availableTeachers' => $availableTeachers,
        ]);
    }

    public function edit(Request $request, string|int $id): string {
        $batch = Database::fetchOne("SELECT * FROM batches WHERE id = ?", [$id]);
        if (!$batch) {
            redirect('/admin/batches');
        }

        return $this->render('admin/batches/edit', [
            'title' => 'Edit Batch: ' . $batch['name'],
            'batch' => $batch,
        ]);
    }

    public function update(Request $request, string|int $id): never {
        $batch = Database::fetchOne("SELECT * FROM batches WHERE id = ?", [$id]);
        if (!$batch) {
            redirect('/admin/batches');
        }

        $errors = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'code' => 'required|min:2|max:30',
            'status' => 'required|in:draft,active,completed,archived',
        ]);

        if (!empty($errors)) {
            redirect('/admin/batches/' . $id . '/edit');
        }

        Database::query(
            "UPDATE batches SET name = ?, code = ?, description = ?, start_date = ?, end_date = ?, status = ?, updated_at = datetime('now') WHERE id = ?",
            [
                $request->input('name'),
                strtoupper((string)$request->input('code')),
                $request->input('description'),
                $request->input('start_date') ?: null,
                $request->input('end_date') ?: null,
                $request->input('status', 'active'),
                $id,
            ]
        );

        $this->audit('batch.updated', 'batch', (int)$id);
        Session::flash('success', 'Batch updated successfully!');
        redirect('/admin/batches/' . $id);
    }

    public function addStudent(Request $request, string|int $batchId): never {
        $studentId = (int)$request->input('student_id');
        if ($studentId) {
            Database::query(
                "INSERT OR REPLACE INTO batch_students (batch_id, student_id, enrolled_at, status) 
                 VALUES (?, ?, datetime('now'), 'active')",
                [$batchId, $studentId]
            );
            $this->audit('batch.student_added', 'batch', (int)$batchId, ['student_id' => $studentId]);
            Session::flash('success', 'Student added to batch.');
        }
        redirect('/admin/batches/' . $batchId);
    }

    public function removeStudent(Request $request, string|int $batchId, string|int $studentId): never {
        Database::query("DELETE FROM batch_students WHERE batch_id = ? AND student_id = ?", [$batchId, $studentId]);
        $this->audit('batch.student_removed', 'batch', (int)$batchId, ['student_id' => (int)$studentId]);
        Session::flash('success', 'Student removed from batch.');
        redirect('/admin/batches/' . $batchId);
    }

    public function assignCourse(Request $request, string|int $batchId): never {
        $courseId = (int)$request->input('course_id');
        if ($courseId) {
            Database::query(
                "INSERT OR IGNORE INTO batch_courses (batch_id, course_id, assigned_at) VALUES (?, ?, datetime('now'))",
                [$batchId, $courseId]
            );
            $this->audit('batch.course_assigned', 'batch', (int)$batchId, ['course_id' => $courseId]);
            Session::flash('success', 'Course assigned to batch.');
        }
        redirect('/admin/batches/' . $batchId);
    }

    public function removeCourse(Request $request, string|int $batchId, string|int $courseId): never {
        Database::query("DELETE FROM batch_courses WHERE batch_id = ? AND course_id = ?", [$batchId, $courseId]);
        $this->audit('batch.course_removed', 'batch', (int)$batchId, ['course_id' => (int)$courseId]);
        Session::flash('success', 'Course removed from batch.');
        redirect('/admin/batches/' . $batchId);
    }

    public function assignTeacher(Request $request, string|int $batchId): never {
        $teacherId = (int)$request->input('teacher_id');
        if ($teacherId) {
            Database::query(
                "INSERT OR IGNORE INTO batch_teachers (batch_id, teacher_id, assigned_at) VALUES (?, ?, datetime('now'))",
                [$batchId, $teacherId]
            );
            $this->audit('batch.teacher_assigned', 'batch', (int)$batchId, ['teacher_id' => $teacherId]);
            Session::flash('success', 'Teacher assigned to batch.');
        }
        redirect('/admin/batches/' . $batchId);
    }

    public function removeTeacher(Request $request, string|int $batchId, string|int $teacherId): never {
        Database::query("DELETE FROM batch_teachers WHERE batch_id = ? AND teacher_id = ?", [$batchId, $teacherId]);
        $this->audit('batch.teacher_removed', 'batch', (int)$batchId, ['teacher_id' => (int)$teacherId]);
        Session::flash('success', 'Teacher removed from batch.');
        redirect('/admin/batches/' . $batchId);
    }

    // Student Batch Change Form
    public function showChangeBatch(Request $request): string {
        $students = Database::fetchAll(
            "SELECT u.id, u.name, u.email, u.student_id, b.id as current_batch_id, b.name as current_batch_name 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active' 
             LEFT JOIN batches b ON bs.batch_id = b.id 
             WHERE r.slug = 'student' AND u.status = 'active' 
             ORDER BY u.name ASC"
        );

        $batches = Database::fetchAll("SELECT id, name, code FROM batches WHERE status = 'active' ORDER BY name ASC");

        return $this->render('admin/batches/change_student', [
            'title' => 'Change Student Batch',
            'students' => $students,
            'batches' => $batches,
            'preselectedStudentId' => $request->query('student_id'),
        ]);
    }

    // Execute Student Batch Change with Preserve History
    public function executeChangeBatch(Request $request): never {
        $studentId = (int)$request->input('student_id');
        $newBatchId = (int)$request->input('new_batch_id');
        $mode = (string)$request->input('transition_mode', 'merge'); // replace, keep, merge
        $notes = (string)$request->input('notes', '');

        if (!$studentId || !$newBatchId) {
            Session::flash('error', 'Please select both the student and the target batch.');
            redirect('/admin/batches/change-student');
        }

        $currentEnrollment = Database::fetchOne(
            "SELECT batch_id FROM batch_students WHERE student_id = ? AND status = 'active' LIMIT 1",
            [$studentId]
        );
        $oldBatchId = $currentEnrollment ? (int)$currentEnrollment['batch_id'] : null;

        if ($oldBatchId === $newBatchId) {
            Session::flash('error', 'Student is already actively enrolled in this batch.');
            redirect('/admin/batches/change-student');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Log transition history
            $historyStmt = $pdo->prepare(
                "INSERT INTO batch_history (student_id, old_batch_id, new_batch_id, transition_mode, notes, changed_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, datetime('now'))"
            );
            $historyStmt->execute([
                $studentId,
                $oldBatchId,
                $newBatchId,
                $mode,
                $notes,
                AuthHelper::id(),
            ]);

            // 2. Handle batch enrollment
            if ($oldBatchId) {
                // Update old enrollment status to completed or dropped
                $pdo->prepare("UPDATE batch_students SET status = 'completed' WHERE batch_id = ? AND student_id = ?")
                    ->execute([$oldBatchId, $studentId]);
            }

            // Enroll in new batch
            $pdo->prepare(
                "INSERT OR REPLACE INTO batch_students (batch_id, student_id, enrolled_at, status) 
                 VALUES (?, ?, datetime('now'), 'active')"
            )->execute([$newBatchId, $studentId]);

            // Note: Historical progress, attempts, submissions, and grades remain completely untouched!
            $pdo->commit();

            $this->audit('student.batch_changed', 'user', $studentId, [
                'old_batch_id' => $oldBatchId,
                'new_batch_id' => $newBatchId,
                'mode' => $mode
            ]);

            Session::flash('success', 'Student batch changed successfully. Academic history and grades preserved.');
            redirect('/admin/students/' . $studentId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Batch change failed: ' . $e->getMessage());
            redirect('/admin/batches/change-student');
        }
    }

    public function export(Request $request, string|int $id): never {
        $batch = Database::fetchOne("SELECT * FROM batches WHERE id = ?", [$id]);
        if (!$batch) {
            redirect('/admin/batches');
        }

        $students = Database::fetchAll(
            "SELECT u.name, u.email, u.student_id, bs.enrolled_at, bs.status 
             FROM users u 
             JOIN batch_students bs ON u.id = bs.student_id 
             WHERE bs.batch_id = ? 
             ORDER BY u.name ASC",
            [$id]
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="batch_' . $batch['code'] . '_students.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student Name', 'Email', 'Student ID', 'Enrolled Date', 'Enrollment Status']);
        foreach ($students as $s) {
            fputcsv($out, [$s['name'], $s['email'], $s['student_id'], $s['enrolled_at'], $s['status']]);
        }
        fclose($out);
        exit;
    }
}
