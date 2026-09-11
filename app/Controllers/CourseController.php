<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Database;

class CourseController extends BaseController {
    public function index(Request $request): string {
        $status = $request->query('status', 'all');
        $search = trim((string)$request->query('q', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];

        if ($status !== 'all') {
            $where .= " AND c.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where .= " AND (c.title LIKE ? OR c.code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $total = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM courses c WHERE {$where}", $params)['cnt'] ?? 0);

        $sql = "SELECT c.*, 
                       COUNT(DISTINCT cu.id) as unit_count,
                       COUNT(DISTINCT l.id) as lesson_count,
                       COUNT(DISTINCT bs.student_id) as enrolled_students_count
                FROM courses c 
                LEFT JOIN units cu ON c.id = cu.course_id 
                LEFT JOIN lessons l ON c.id = l.course_id 
                LEFT JOIN batch_courses bc ON c.id = bc.course_id 
                LEFT JOIN batch_students bs ON bc.batch_id = bs.batch_id AND bs.status = 'active'
                WHERE {$where}
                GROUP BY c.id 
                ORDER BY c.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $courses = Database::fetchAll($sql, $params);

        return $this->render('admin/courses/index', [
            'title' => 'Course Catalog & Builder',
            'courses' => $courses,
            'total' => $total,
            'currentPage' => $page,
            'lastPage' => max(1, ceil($total / $perPage)),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(Request $request): string {
        return $this->render('admin/courses/create', [
            'title' => 'Create New Course',
        ]);
    }

    public function store(Request $request): never {
        $errors = $this->validate($request, [
            'title' => 'required|min:3|max:150',
            'code' => 'required|min:2|max:30',
            'status' => 'required|in:draft,published,archived',
        ]);

        if (!empty($errors)) {
            redirect('/admin/courses/create');
        }

        $code = strtoupper((string)$request->input('code'));
        $existing = Database::fetchOne("SELECT id FROM courses WHERE code = ?", [$code]);
        if ($existing) {
            Session::flash('error', 'Course code must be unique.');
            redirect('/admin/courses/create');
        }

        $thumbPath = null;
        if ($request->hasFile('thumbnail')) {
            $upload = FileHelper::upload($request->file('thumbnail'), 'thumbnails', ['jpg', 'jpeg', 'png', 'webp']);
            if ($upload['success']) {
                $thumbPath = $upload['relative_path'];
            }
        }

        Database::query(
            "INSERT INTO courses (title, code, description, thumbnail, status, start_date, end_date, completion_rules, created_by, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $request->input('title'),
                $code,
                $request->input('description'),
                $thumbPath,
                $request->input('status', 'draft'),
                $request->input('start_date') ?: null,
                $request->input('end_date') ?: null,
                $request->input('completion_rules', 'all_lessons'),
                AuthHelper::id(),
            ]
        );
        $courseId = (int)Database::lastInsertId();

        $this->audit('course.created', 'course', $courseId, ['title' => $request->input('title'), 'code' => $code]);
        Session::flash('success', 'Course created! You can now add units and lessons in the Course Builder.');
        redirect('/admin/courses/' . $courseId);
    }

    public function show(Request $request, string|int $id): string {
        $course = Database::fetchOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            Session::flash('error', 'Course not found.');
            redirect('/admin/courses');
        }

        // Fetch Units with their Lessons and Assessments
        $units = Database::fetchAll("SELECT * FROM units WHERE course_id = ? ORDER BY sort_order ASC, id ASC", [$id]);

        foreach ($units as &$u) {
            $u['lessons'] = Database::fetchAll(
                "SELECT l.*, lv.source_type, lv.video_url, lv.duration_seconds 
                 FROM lessons l 
                 LEFT JOIN lesson_videos lv ON l.id = lv.lesson_id 
                 WHERE l.unit_id = ? 
                 ORDER BY l.sort_order ASC, l.id ASC",
                [$u['id']]
            );
            $u['assessments'] = Database::fetchAll(
                "SELECT * FROM assessments WHERE unit_id = ? ORDER BY id ASC",
                [$u['id']]
            );
        }
        unset($u);

        // Course-level final assessments
        $finalAssessments = Database::fetchAll(
            "SELECT * FROM assessments WHERE course_id = ? AND (unit_id IS NULL OR unit_id = 0) ORDER BY id ASC",
            [$id]
        );

        // Assigned teachers
        $teachers = Database::fetchAll(
            "SELECT u.*, ct.assigned_at 
             FROM users u 
             JOIN course_teachers ct ON u.id = ct.teacher_id 
             WHERE ct.course_id = ? 
             ORDER BY u.name ASC",
            [$id]
        );

        $availableTeachers = Database::fetchAll(
            "SELECT u.id, u.name, u.email 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.slug = 'teacher' AND u.status = 'active' 
               AND u.id NOT IN (SELECT teacher_id FROM course_teachers WHERE course_id = ?) 
             ORDER BY u.name ASC",
            [$id]
        );

        return $this->render('admin/courses/builder', [
            'title' => 'Course Builder: ' . $course['title'],
            'course' => $course,
            'units' => $units,
            'finalAssessments' => $finalAssessments,
            'teachers' => $teachers,
            'availableTeachers' => $availableTeachers,
        ]);
    }

    public function edit(Request $request, string|int $id): string {
        $course = Database::fetchOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            redirect('/admin/courses');
        }

        return $this->render('admin/courses/edit', [
            'title' => 'Edit Course: ' . $course['title'],
            'course' => $course,
        ]);
    }

    public function update(Request $request, string|int $id): never {
        $course = Database::fetchOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            redirect('/admin/courses');
        }

        $errors = $this->validate($request, [
            'title' => 'required|min:3|max:150',
            'code' => 'required|min:2|max:30',
            'status' => 'required|in:draft,published,archived',
        ]);

        if (!empty($errors)) {
            redirect('/admin/courses/' . $id . '/edit');
        }

        $code = strtoupper((string)$request->input('code'));
        $existing = Database::fetchOne("SELECT id FROM courses WHERE code = ? AND id != ?", [$code, $id]);
        if ($existing) {
            Session::flash('error', 'Course code must be unique.');
            redirect('/admin/courses/' . $id . '/edit');
        }

        $thumbPath = $course['thumbnail'];
        if ($request->hasFile('thumbnail')) {
            $upload = FileHelper::upload($request->file('thumbnail'), 'thumbnails', ['jpg', 'jpeg', 'png', 'webp']);
            if ($upload['success']) {
                $thumbPath = $upload['relative_path'];
            }
        }

        Database::query(
            "UPDATE courses SET title = ?, code = ?, description = ?, thumbnail = ?, status = ?, start_date = ?, end_date = ?, completion_rules = ?, updated_at = datetime('now') WHERE id = ?",
            [
                $request->input('title'),
                $code,
                $request->input('description'),
                $thumbPath,
                $request->input('status', 'draft'),
                $request->input('start_date') ?: null,
                $request->input('end_date') ?: null,
                $request->input('completion_rules', 'all_lessons'),
                $id,
            ]
        );

        $this->audit('course.updated', 'course', (int)$id);
        Session::flash('success', 'Course updated successfully.');
        redirect('/admin/courses/' . $id);
    }

    // Duplicate / Clone Course
    public function duplicate(Request $request, string|int $id): never {
        $course = Database::fetchOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            redirect('/admin/courses');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $newCode = $course['code'] . '-COPY-' . rand(100, 999);
            $newTitle = $course['title'] . ' (Copy)';

            $stmt = $pdo->prepare(
                "INSERT INTO courses (title, code, description, thumbnail, status, start_date, end_date, completion_rules, created_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, 'draft', ?, ?, ?, ?, datetime('now'), datetime('now'))"
            );
            $stmt->execute([
                $newTitle,
                $newCode,
                $course['description'],
                $course['thumbnail'],
                $course['start_date'],
                $course['end_date'],
                $course['completion_rules'],
                AuthHelper::id(),
            ]);
            $newCourseId = (int)$pdo->lastInsertId();

            // Clone Units
            $units = $pdo->query("SELECT * FROM units WHERE course_id = {$id} ORDER BY sort_order ASC")->fetchAll();
            foreach ($units as $u) {
                $uStmt = $pdo->prepare(
                    "INSERT INTO units (course_id, title, description, thumbnail, sort_order, status, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
                );
                $uStmt->execute([
                    $newCourseId,
                    $u['title'],
                    $u['description'],
                    $u['thumbnail'],
                    $u['sort_order'],
                    $u['status'],
                ]);
                $newUnitId = (int)$pdo->lastInsertId();

                // Clone Lessons
                $lessons = $pdo->query("SELECT * FROM lessons WHERE unit_id = {$u['id']} ORDER BY sort_order ASC")->fetchAll();
                foreach ($lessons as $l) {
                    $lStmt = $pdo->prepare(
                        "INSERT INTO lessons (unit_id, course_id, title, description, lesson_type, content, duration_minutes, sort_order, status, completion_rule, created_by, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
                    );
                    $lStmt->execute([
                        $newUnitId,
                        $newCourseId,
                        $l['title'],
                        $l['description'],
                        $l['lesson_type'],
                        $l['content'],
                        $l['duration_minutes'],
                        $l['sort_order'],
                        $l['status'],
                        $l['completion_rule'],
                        AuthHelper::id(),
                    ]);
                    $newLessonId = (int)$pdo->lastInsertId();

                    // Clone Video config if present
                    $video = $pdo->query("SELECT * FROM lesson_videos WHERE lesson_id = {$l['id']}")->fetch();
                    if ($video) {
                        $vStmt = $pdo->prepare(
                            "INSERT INTO lesson_videos (lesson_id, source_type, video_url, video_path, duration_seconds, thumbnail, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
                        );
                        $vStmt->execute([
                            $newLessonId,
                            $video['source_type'],
                            $video['video_url'],
                            $video['video_path'],
                            $video['duration_seconds'],
                            $video['thumbnail'],
                        ]);
                    }
                }
            }

            $pdo->commit();
            $this->audit('course.duplicated', 'course', $newCourseId, ['source_course_id' => $id]);
            Session::flash('success', 'Course and curriculum successfully cloned as draft!');
            redirect('/admin/courses/' . $newCourseId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Cloning failed: ' . $e->getMessage());
            redirect('/admin/courses/' . $id);
        }
    }

    public function destroy(Request $request, string|int $id): never {
        Database::query("DELETE FROM courses WHERE id = ?", [$id]);
        $this->audit('course.deleted', 'course', (int)$id);
        Session::flash('success', 'Course deleted.');
        redirect('/admin/courses');
    }

    // Unit Actions
    public function addUnit(Request $request, string|int $courseId): never {
        $title = trim((string)$request->input('title'));
        if (empty($title)) {
            Session::flash('error', 'Unit title is required.');
            redirect('/admin/courses/' . $courseId);
        }

        $order = (int)(Database::fetchOne("SELECT MAX(sort_order) as mx FROM units WHERE course_id = ?", [$courseId])['mx'] ?? 0) + 1;

        Database::query(
            "INSERT INTO units (course_id, title, description, sort_order, status, created_at, updated_at) 
             VALUES (?, ?, ?, ?, 'published', datetime('now'), datetime('now'))",
            [$courseId, $title, $request->input('description', ''), $order]
        );

        $this->audit('unit.created', 'course', (int)$courseId, ['title' => $title]);
        Session::flash('success', 'Unit added.');
        redirect('/admin/courses/' . $courseId);
    }

    public function updateUnit(Request $request, string|int $courseId, string|int $unitId): never {
        $title = trim((string)$request->input('title'));
        if (!empty($title)) {
            Database::query(
                "UPDATE units SET title = ?, description = ?, updated_at = datetime('now') WHERE id = ? AND course_id = ?",
                [$title, $request->input('description', ''), $unitId, $courseId]
            );
            Session::flash('success', 'Unit updated.');
        }
        redirect('/admin/courses/' . $courseId);
    }

    public function deleteUnit(Request $request, string|int $courseId, string|int $unitId): never {
        Database::query("DELETE FROM units WHERE id = ? AND course_id = ?", [$unitId, $courseId]);
        $this->audit('unit.deleted', 'unit', (int)$unitId);
        Session::flash('success', 'Unit deleted.');
        redirect('/admin/courses/' . $courseId);
    }

    // Teacher assignment to course
    public function assignTeacher(Request $request, string|int $courseId): never {
        $teacherId = (int)$request->input('teacher_id');
        if ($teacherId) {
            Database::query(
                "INSERT OR IGNORE INTO course_teachers (course_id, teacher_id, assigned_at) VALUES (?, ?, datetime('now'))",
                [$courseId, $teacherId]
            );
            $this->audit('course.teacher_assigned', 'course', (int)$courseId, ['teacher_id' => $teacherId]);
            Session::flash('success', 'Faculty assigned to course.');
        }
        redirect('/admin/courses/' . $courseId);
    }

    public function removeTeacher(Request $request, string|int $courseId, string|int $teacherId): never {
        Database::query("DELETE FROM course_teachers WHERE course_id = ? AND teacher_id = ?", [$courseId, $teacherId]);
        Session::flash('success', 'Faculty removed from course.');
        redirect('/admin/courses/' . $courseId);
    }
}
