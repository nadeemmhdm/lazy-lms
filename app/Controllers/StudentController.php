<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Database;

class StudentController extends BaseController {
    public function index(Request $request): string {
        $status = $request->query('status', 'active');
        $batchId = $request->query('batch_id');
        $search = trim((string)$request->query('q', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = "r.slug = 'student'";
        $params = [];

        if ($status !== 'all') {
            $where .= " AND u.status = ?";
            $params[] = $status;
        }

        if (!empty($batchId)) {
            $where .= " AND bs.batch_id = ? AND bs.status = 'active'";
            $params[] = $batchId;
        }

        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $countSql = "SELECT COUNT(DISTINCT u.id) as cnt 
                     FROM users u 
                     JOIN user_roles ur ON u.id = ur.user_id 
                     JOIN roles r ON ur.role_id = r.id 
                     LEFT JOIN batch_students bs ON u.id = bs.student_id 
                     WHERE {$where}";
        $total = (int)(Database::fetchOne($countSql, $params)['cnt'] ?? 0);

        $sql = "SELECT u.*, b.name as batch_name, b.code as batch_code 
                FROM users u 
                JOIN user_roles ur ON u.id = ur.user_id 
                JOIN roles r ON ur.role_id = r.id 
                LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active'
                LEFT JOIN batches b ON bs.batch_id = b.id 
                WHERE {$where}
                GROUP BY u.id 
                ORDER BY u.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $students = Database::fetchAll($sql, $params);
        $batches = Database::fetchAll("SELECT id, name, code FROM batches WHERE status = 'active' ORDER BY name ASC");

        return $this->render('admin/students/index', [
            'title' => 'Student Management',
            'students' => $students,
            'batches' => $batches,
            'total' => $total,
            'currentPage' => $page,
            'lastPage' => max(1, ceil($total / $perPage)),
            'status' => $status,
            'batchId' => $batchId,
            'search' => $search,
        ]);
    }

    public function create(Request $request): string {
        $batches = Database::fetchAll("SELECT id, name, code FROM batches WHERE status = 'active' ORDER BY name ASC");
        return $this->render('admin/students/create', [
            'title' => 'Enroll New Student',
            'batches' => $batches,
        ]);
    }

    public function store(Request $request): never {
        $errors = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'status' => 'required|in:active,archived,suspended',
        ]);

        if (!empty($errors)) {
            redirect('/admin/students/create');
        }

        // Email uniqueness
        $exists = Database::fetchOne("SELECT id FROM users WHERE LOWER(email) = LOWER(?)", [$request->input('email')]);
        if ($exists) {
            Session::flash('error', 'A user with this email address already exists.');
            redirect('/admin/students/create');
        }

        // Student ID uniqueness
        $studentId = $request->input('student_id') ?: ('STD-' . date('Y') . '-' . rand(1000, 9999));
        $existsId = Database::fetchOne("SELECT id FROM users WHERE student_id = ?", [$studentId]);
        if ($existsId) {
            $studentId .= '-' . rand(10, 99);
        }

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $upload = FileHelper::upload($request->file('avatar'), 'avatars', ['jpg', 'jpeg', 'png', 'webp']);
            if ($upload['success']) {
                $avatarPath = $upload['relative_path'];
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, student_id, phone, avatar, password, status, force_password_change, locale, timezone, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en', 'UTC', datetime('now'), datetime('now'))"
            );
            $stmt->execute([
                $request->input('name'),
                strtolower((string)$request->input('email')),
                $studentId,
                $request->input('phone'),
                $avatarPath,
                AuthHelper::hashPassword($request->input('password')),
                $request->input('status', 'active'),
                $request->input('force_password_change') ? 1 : 0,
            ]);
            $newUserId = (int)$pdo->lastInsertId();

            // Assign Student Role
            $roleRow = $pdo->query("SELECT id FROM roles WHERE slug = 'student'")->fetch();
            $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$newUserId, $roleRow['id']]);

            // Assign Batch if selected
            $batchId = (int)$request->input('batch_id');
            if ($batchId) {
                $pdo->prepare(
                    "INSERT INTO batch_students (batch_id, student_id, enrolled_at, status) VALUES (?, ?, datetime('now'), 'active')"
                )->execute([$batchId, $newUserId]);
            }

            $pdo->commit();
            $this->audit('student.created', 'user', $newUserId, ['name' => $request->input('name'), 'email' => $request->input('email')]);
            Session::flash('success', "Student '{$request->input('name')}' created successfully!");
            redirect('/admin/students/' . $newUserId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Error creating student: ' . $e->getMessage());
            redirect('/admin/students/create');
        }
    }

    public function show(Request $request, string|int $id): string {
        $student = Database::fetchOne(
            "SELECT u.*, b.id as batch_id, b.name as batch_name, b.code as batch_code 
             FROM users u 
             LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active'
             LEFT JOIN batches b ON bs.batch_id = b.id 
             WHERE u.id = ? LIMIT 1",
            [$id]
        );

        if (!$student) {
            Session::flash('error', 'Student not found.');
            redirect('/admin/students');
        }

        // Assigned Courses via Batch
        $courses = [];
        if ($student['batch_id']) {
            $courses = Database::fetchAll(
                "SELECT c.*, COALESCE(cp.progress_percentage, 0) as progress_percentage 
                 FROM courses c 
                 JOIN batch_courses bc ON c.id = bc.course_id 
                 LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ? 
                 WHERE bc.batch_id = ? 
                 ORDER BY c.title ASC",
                [$id, $student['batch_id']]
            );
        }

        // Assessment attempts
        $attempts = Database::fetchAll(
            "SELECT aa.*, a.title as assessment_title 
             FROM assessment_attempts aa 
             JOIN assessments a ON aa.assessment_id = a.id 
             WHERE aa.user_id = ? 
             ORDER BY aa.started_at DESC",
            [$id]
        );

        // Batch change history
        $batchHistory = Database::fetchAll(
            "SELECT bh.*, ob.name as old_batch_name, nb.name as new_batch_name, u.name as changer_name 
             FROM batch_history bh 
             LEFT JOIN batches ob ON bh.old_batch_id = ob.id 
             JOIN batches nb ON bh.new_batch_id = nb.id 
             LEFT JOIN users u ON bh.changed_by = u.id 
             WHERE bh.student_id = ? 
             ORDER BY bh.id DESC",
            [$id]
        );

        // Certificates
        $certificates = Database::fetchAll("SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC", [$id]);

        return $this->render('admin/students/show', [
            'title' => 'Student: ' . $student['name'],
            'student' => $student,
            'courses' => $courses,
            'attempts' => $attempts,
            'batchHistory' => $batchHistory,
            'certificates' => $certificates,
        ]);
    }

    public function edit(Request $request, string|int $id): string {
        $student = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$student) {
            redirect('/admin/students');
        }
        $batches = Database::fetchAll("SELECT id, name, code FROM batches WHERE status = 'active' ORDER BY name ASC");

        $currentEnrollment = Database::fetchOne(
            "SELECT batch_id FROM batch_students WHERE student_id = ? AND status = 'active' LIMIT 1",
            [$id]
        );

        return $this->render('admin/students/edit', [
            'title' => 'Edit Student: ' . $student['name'],
            'student' => $student,
            'batches' => $batches,
            'currentBatchId' => $currentEnrollment['batch_id'] ?? null,
        ]);
    }

    public function update(Request $request, string|int $id): never {
        $student = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$student) {
            redirect('/admin/students');
        }

        $errors = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'status' => 'required|in:active,archived,suspended',
        ]);

        if (!empty($errors)) {
            redirect('/admin/students/' . $id . '/edit');
        }

        // Email uniqueness for others
        $exists = Database::fetchOne("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?", [$request->input('email'), $id]);
        if ($exists) {
            Session::flash('error', 'Email is already taken by another user.');
            redirect('/admin/students/' . $id . '/edit');
        }

        $updateData = [
            'name' => $request->input('name'),
            'email' => strtolower((string)$request->input('email')),
            'student_id' => $request->input('student_id'),
            'phone' => $request->input('phone'),
            'status' => $request->input('status'),
            'force_password_change' => $request->input('force_password_change') ? 1 : 0,
        ];

        // Optional password reset
        if (!empty($request->input('new_password'))) {
            if (strlen($request->input('new_password')) < 8) {
                Session::flash('error', 'New password must be at least 8 characters long.');
                redirect('/admin/students/' . $id . '/edit');
            }
            $updateData['password'] = AuthHelper::hashPassword($request->input('new_password'));
        }

        // Avatar upload
        if ($request->hasFile('avatar')) {
            $upload = FileHelper::upload($request->file('avatar'), 'avatars', ['jpg', 'jpeg', 'png', 'webp']);
            if ($upload['success']) {
                $updateData['avatar'] = $upload['relative_path'];
            }
        }

        $sets = [];
        $vals = [];
        foreach ($updateData as $k => $v) {
            $sets[] = "{$k} = ?";
            $vals[] = $v;
        }
        $vals[] = $id;

        Database::query("UPDATE users SET " . implode(', ', $sets) . ", updated_at = datetime('now') WHERE id = ?", $vals);

        $this->audit('student.updated', 'user', (int)$id);
        Session::flash('success', 'Student details updated successfully.');
        redirect('/admin/students/' . $id);
    }

    public function archive(Request $request, string|int $id): never {
        Database::query("UPDATE users SET status = 'archived', updated_at = datetime('now') WHERE id = ?", [$id]);
        $this->audit('student.archived', 'user', (int)$id);
        Session::flash('success', 'Student has been archived. Academic records preserved.');
        redirect('/admin/students/' . $id);
    }

    public function restore(Request $request, string|int $id): never {
        Database::query("UPDATE users SET status = 'active', updated_at = datetime('now') WHERE id = ?", [$id]);
        $this->audit('student.restored', 'user', (int)$id);
        Session::flash('success', 'Student restored to active status.');
        redirect('/admin/students/' . $id);
    }

    public function destroy(Request $request, string|int $id): never {
        // Permanent delete
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Delete user - cascading foreign keys will remove user_roles, sessions, etc.
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $pdo->commit();

            $this->audit('student.deleted_permanently', 'user', (int)$id);
            Session::flash('success', 'Student account permanently deleted.');
            redirect('/admin/students');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Error deleting student: ' . $e->getMessage());
            redirect('/admin/students/' . $id);
        }
    }
}
