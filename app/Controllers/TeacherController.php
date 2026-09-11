<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Database;

class TeacherController extends BaseController {
    public function index(Request $request): string {
        $status = $request->query('status', 'active');
        $search = trim((string)$request->query('q', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = "r.slug = 'teacher'";
        $params = [];

        if ($status !== 'all') {
            $where .= " AND u.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $countSql = "SELECT COUNT(DISTINCT u.id) as cnt 
                     FROM users u 
                     JOIN user_roles ur ON u.id = ur.user_id 
                     JOIN roles r ON ur.role_id = r.id 
                     WHERE {$where}";
        $total = (int)(Database::fetchOne($countSql, $params)['cnt'] ?? 0);

        $sql = "SELECT u.*, 
                       COUNT(DISTINCT ct.course_id) as assigned_courses_count,
                       COUNT(DISTINCT bt.batch_id) as assigned_batches_count 
                FROM users u 
                JOIN user_roles ur ON u.id = ur.user_id 
                JOIN roles r ON ur.role_id = r.id 
                LEFT JOIN course_teachers ct ON u.id = ct.teacher_id 
                LEFT JOIN batch_teachers bt ON u.id = bt.teacher_id 
                WHERE {$where}
                GROUP BY u.id 
                ORDER BY u.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $teachers = Database::fetchAll($sql, $params);

        return $this->render('admin/teachers/index', [
            'title' => 'Faculty Management',
            'teachers' => $teachers,
            'total' => $total,
            'currentPage' => $page,
            'lastPage' => max(1, ceil($total / $perPage)),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(Request $request): string {
        return $this->render('admin/teachers/create', [
            'title' => 'Register Faculty Member',
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
            redirect('/admin/teachers/create');
        }

        $exists = Database::fetchOne("SELECT id FROM users WHERE LOWER(email) = LOWER(?)", [$request->input('email')]);
        if ($exists) {
            Session::flash('error', 'A user with this email address already exists.');
            redirect('/admin/teachers/create');
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
                "INSERT INTO users (name, email, phone, avatar, password, status, force_password_change, locale, timezone, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'en', 'UTC', datetime('now'), datetime('now'))"
            );
            $stmt->execute([
                $request->input('name'),
                strtolower((string)$request->input('email')),
                $request->input('phone'),
                $avatarPath,
                AuthHelper::hashPassword($request->input('password')),
                $request->input('status', 'active'),
                $request->input('force_password_change') ? 1 : 0,
            ]);
            $teacherId = (int)$pdo->lastInsertId();

            $roleRow = $pdo->query("SELECT id FROM roles WHERE slug = 'teacher'")->fetch();
            $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$teacherId, $roleRow['id']]);

            $pdo->commit();
            $this->audit('teacher.created', 'user', $teacherId, ['name' => $request->input('name')]);
            Session::flash('success', "Faculty member '{$request->input('name')}' created successfully!");
            redirect('/admin/teachers/' . $teacherId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Error creating teacher: ' . $e->getMessage());
            redirect('/admin/teachers/create');
        }
    }

    public function show(Request $request, string|int $id): string {
        $teacher = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$teacher) {
            redirect('/admin/teachers');
        }

        // Assigned Courses
        $courses = Database::fetchAll(
            "SELECT c.* FROM courses c JOIN course_teachers ct ON c.id = ct.course_id WHERE ct.teacher_id = ? ORDER BY c.title ASC",
            [$id]
        );

        // Assigned Batches
        $batches = Database::fetchAll(
            "SELECT b.* FROM batches b JOIN batch_teachers bt ON b.id = bt.batch_id WHERE bt.teacher_id = ? ORDER BY b.name ASC",
            [$id]
        );

        return $this->render('admin/teachers/show', [
            'title' => 'Faculty: ' . $teacher['name'],
            'teacher' => $teacher,
            'courses' => $courses,
            'batches' => $batches,
        ]);
    }

    public function edit(Request $request, string|int $id): string {
        $teacher = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$teacher) {
            redirect('/admin/teachers');
        }

        return $this->render('admin/teachers/edit', [
            'title' => 'Edit Faculty: ' . $teacher['name'],
            'teacher' => $teacher,
        ]);
    }

    public function update(Request $request, string|int $id): never {
        $teacher = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$teacher) {
            redirect('/admin/teachers');
        }

        $errors = $this->validate($request, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'status' => 'required|in:active,archived,suspended',
        ]);

        if (!empty($errors)) {
            redirect('/admin/teachers/' . $id . '/edit');
        }

        $exists = Database::fetchOne("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?", [$request->input('email'), $id]);
        if ($exists) {
            Session::flash('error', 'Email is already taken by another user.');
            redirect('/admin/teachers/' . $id . '/edit');
        }

        $updateData = [
            'name' => $request->input('name'),
            'email' => strtolower((string)$request->input('email')),
            'phone' => $request->input('phone'),
            'status' => $request->input('status'),
            'force_password_change' => $request->input('force_password_change') ? 1 : 0,
        ];

        if (!empty($request->input('new_password'))) {
            if (strlen($request->input('new_password')) < 8) {
                Session::flash('error', 'New password must be at least 8 characters long.');
                redirect('/admin/teachers/' . $id . '/edit');
            }
            $updateData['password'] = AuthHelper::hashPassword($request->input('new_password'));
        }

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
        $this->audit('teacher.updated', 'user', (int)$id);
        Session::flash('success', 'Teacher updated successfully.');
        redirect('/admin/teachers/' . $id);
    }

    public function archive(Request $request, string|int $id): never {
        Database::query("UPDATE users SET status = 'archived', updated_at = datetime('now') WHERE id = ?", [$id]);
        $this->audit('teacher.archived', 'user', (int)$id);
        Session::flash('success', 'Teacher archived successfully.');
        redirect('/admin/teachers/' . $id);
    }

    public function restore(Request $request, string|int $id): never {
        Database::query("UPDATE users SET status = 'active', updated_at = datetime('now') WHERE id = ?", [$id]);
        $this->audit('teacher.restored', 'user', (int)$id);
        Session::flash('success', 'Teacher restored to active status.');
        redirect('/admin/teachers/' . $id);
    }

    public function destroy(Request $request, string|int $id): never {
        Database::query("DELETE FROM users WHERE id = ?", [$id]);
        $this->audit('teacher.deleted', 'user', (int)$id);
        Session::flash('success', 'Teacher account permanently deleted.');
        redirect('/admin/teachers');
    }
}
