<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Database;

class LessonController extends BaseController {
    public function create(Request $request, string|int $courseId, string|int $unitId): string {
        $course = Database::fetchOne("SELECT * FROM courses WHERE id = ?", [$courseId]);
        $unit = Database::fetchOne("SELECT * FROM units WHERE id = ? AND course_id = ?", [$unitId, $courseId]);

        if (!$course || !$unit) {
            Session::flash('error', 'Course or Unit not found.');
            redirect('/admin/courses');
        }

        return $this->render('admin/lessons/create', [
            'title' => 'Add Lesson to ' . $unit['title'],
            'course' => $course,
            'unit' => $unit,
        ]);
    }

    public function store(Request $request, string|int $courseId, string|int $unitId): never {
        $errors = $this->validate($request, [
            'title' => 'required|min:2|max:150',
            'lesson_type' => 'required|in:text,video,pdf,ppt,assessment',
            'completion_rule' => 'required|in:manual,video_90,pdf_open,assessment_passed',
        ]);

        if (!empty($errors)) {
            redirect("/admin/courses/{$courseId}/units/{$unitId}/lessons/create");
        }

        $order = (int)(Database::fetchOne("SELECT MAX(sort_order) as mx FROM lessons WHERE unit_id = ?", [$unitId])['mx'] ?? 0) + 1;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Insert lesson record
            $lStmt = $pdo->prepare(
                "INSERT INTO lessons (unit_id, course_id, title, description, lesson_type, content, duration_minutes, sort_order, status, completion_rule, created_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
            );
            $lStmt->execute([
                $unitId,
                $courseId,
                $request->input('title'),
                $request->input('description'),
                $request->input('lesson_type', 'text'),
                $request->input('content'),
                (int)$request->input('duration_minutes', 15),
                $order,
                $request->input('status', 'published'),
                $request->input('completion_rule', 'manual'),
                AuthHelper::id(),
            ]);
            $lessonId = (int)$pdo->lastInsertId();

            // 2. Video handling
            $sourceType = $request->input('video_source_type');
            if ($sourceType) {
                $videoUrl = null;
                $videoPath = null;

                if ($sourceType === 'upload' && $request->hasFile('video_file')) {
                    $up = FileHelper::upload($request->file('video_file'), 'videos', ['mp4', 'webm'], 200 * 1024 * 1024);
                    if ($up['success']) {
                        $videoPath = $up['relative_path'];
                    }
                } else {
                    $videoUrl = $request->input('video_url');
                }

                if ($videoUrl || $videoPath) {
                    $pdo->prepare(
                        "INSERT INTO lesson_videos (lesson_id, source_type, video_url, video_path, duration_seconds, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))"
                    )->execute([
                        $lessonId,
                        $sourceType,
                        $videoUrl,
                        $videoPath,
                        (int)$request->input('duration_minutes', 0) * 60,
                    ]);
                }
            }

            // 3. Materials / file attachments
            if (isset($_FILES['materials']) && is_array($_FILES['materials']['name'])) {
                $matFiles = $_FILES['materials'];
                for ($i = 0; $i < count($matFiles['name']); $i++) {
                    if ($matFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name' => $matFiles['name'][$i],
                            'type' => $matFiles['type'][$i],
                            'tmp_name' => $matFiles['tmp_name'][$i],
                            'error' => $matFiles['error'][$i],
                            'size' => $matFiles['size'][$i],
                        ];
                        $up = FileHelper::upload($singleFile, 'materials', ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'txt', 'zip', 'mp3']);
                        if ($up['success']) {
                            $pdo->prepare(
                                "INSERT INTO lesson_materials (lesson_id, title, file_path, original_filename, file_type, file_size, sort_order, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))"
                            )->execute([
                                $lessonId,
                                pathinfo($up['original_name'], PATHINFO_FILENAME),
                                $up['relative_path'],
                                $up['original_name'],
                                $up['extension'],
                                $up['size'],
                                $i,
                            ]);
                        }
                    }
                }
            }

            // 4. Initial Lesson Notes record
            if (!empty($request->input('content'))) {
                $pdo->prepare(
                    "INSERT INTO lesson_notes (lesson_id, content, version, status, updated_by, created_at, updated_at) 
                     VALUES (?, ?, 1, 'published', ?, datetime('now'), datetime('now'))"
                )->execute([$lessonId, $request->input('content'), AuthHelper::id()]);
            }

            $pdo->commit();
            $this->audit('lesson.created', 'lesson', $lessonId, ['title' => $request->input('title')]);
            Session::flash('success', 'Lesson created successfully!');
            redirect('/admin/courses/' . $courseId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Failed to create lesson: ' . $e->getMessage());
            redirect("/admin/courses/{$courseId}/units/{$unitId}/lessons/create");
        }
    }

    public function show(Request $request, string|int $id): string {
        $lesson = Database::fetchOne(
            "SELECT l.*, c.title as course_title, c.code as course_code, u.title as unit_title 
             FROM lessons l 
             JOIN courses c ON l.course_id = c.id 
             JOIN units u ON l.unit_id = u.id 
             WHERE l.id = ? LIMIT 1",
            [$id]
        );

        if (!$lesson) {
            Session::flash('error', 'Lesson not found.');
            redirect('/admin/courses');
        }

        $video = Database::fetchOne("SELECT * FROM lesson_videos WHERE lesson_id = ?", [$id]);
        $materials = Database::fetchAll("SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY sort_order ASC", [$id]);
        $discussions = Database::fetchAll(
            "SELECT d.*, u.name as author_name 
             FROM discussions d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.lesson_id = ? 
             ORDER BY d.is_pinned DESC, d.id DESC",
            [$id]
        );

        return $this->render('admin/lessons/show', [
            'title' => 'Lesson: ' . $lesson['title'],
            'lesson' => $lesson,
            'video' => $video,
            'materials' => $materials,
            'discussions' => $discussions,
        ]);
    }

    public function edit(Request $request, string|int $id): string {
        $lesson = Database::fetchOne("SELECT * FROM lessons WHERE id = ?", [$id]);
        if (!$lesson) {
            redirect('/admin/courses');
        }
        $video = Database::fetchOne("SELECT * FROM lesson_videos WHERE lesson_id = ?", [$id]);
        $materials = Database::fetchAll("SELECT * FROM lesson_materials WHERE lesson_id = ?", [$id]);

        return $this->render('admin/lessons/edit', [
            'title' => 'Edit Lesson: ' . $lesson['title'],
            'lesson' => $lesson,
            'video' => $video,
            'materials' => $materials,
        ]);
    }

    public function update(Request $request, string|int $id): never {
        $lesson = Database::fetchOne("SELECT * FROM lessons WHERE id = ?", [$id]);
        if (!$lesson) {
            redirect('/admin/courses');
        }

        $errors = $this->validate($request, [
            'title' => 'required|min:2|max:150',
            'lesson_type' => 'required|in:text,video,pdf,ppt,assessment',
            'completion_rule' => 'required|in:manual,video_90,pdf_open,assessment_passed',
        ]);

        if (!empty($errors)) {
            redirect('/admin/lessons/' . $id . '/edit');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE lessons SET title = ?, description = ?, lesson_type = ?, content = ?, duration_minutes = ?, status = ?, completion_rule = ?, updated_at = datetime('now') WHERE id = ?"
            )->execute([
                $request->input('title'),
                $request->input('description'),
                $request->input('lesson_type'),
                $request->input('content'),
                (int)$request->input('duration_minutes', 0),
                $request->input('status', 'published'),
                $request->input('completion_rule', 'manual'),
                $id,
            ]);

            // Update or insert video
            $sourceType = $request->input('video_source_type');
            if ($sourceType) {
                $videoUrl = $request->input('video_url');
                $videoPath = null;
                if ($sourceType === 'upload' && $request->hasFile('video_file')) {
                    $up = FileHelper::upload($request->file('video_file'), 'videos', ['mp4', 'webm']);
                    if ($up['success']) {
                        $videoPath = $up['relative_path'];
                    }
                }

                $existingVideo = $pdo->query("SELECT id, video_path FROM lesson_videos WHERE lesson_id = {$id}")->fetch();
                if ($existingVideo) {
                    $finalPath = $videoPath ?: $existingVideo['video_path'];
                    $pdo->prepare("UPDATE lesson_videos SET source_type = ?, video_url = ?, video_path = ?, updated_at = datetime('now') WHERE lesson_id = ?")
                        ->execute([$sourceType, $videoUrl, $finalPath, $id]);
                } elseif ($videoUrl || $videoPath) {
                    $pdo->prepare("INSERT INTO lesson_videos (lesson_id, source_type, video_url, video_path, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))")
                        ->execute([$id, $sourceType, $videoUrl, $videoPath]);
                }
            }

            // New Material uploads
            if (isset($_FILES['materials']) && is_array($_FILES['materials']['name'])) {
                $matFiles = $_FILES['materials'];
                for ($i = 0; $i < count($matFiles['name']); $i++) {
                    if ($matFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name' => $matFiles['name'][$i],
                            'type' => $matFiles['type'][$i],
                            'tmp_name' => $matFiles['tmp_name'][$i],
                            'error' => $matFiles['error'][$i],
                            'size' => $matFiles['size'][$i],
                        ];
                        $up = FileHelper::upload($singleFile, 'materials', ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'txt', 'zip', 'mp3']);
                        if ($up['success']) {
                            $pdo->prepare(
                                "INSERT INTO lesson_materials (lesson_id, title, file_path, original_filename, file_type, file_size, sort_order, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, 0, datetime('now'))"
                            )->execute([
                                $id,
                                pathinfo($up['original_name'], PATHINFO_FILENAME),
                                $up['relative_path'],
                                $up['original_name'],
                                $up['extension'],
                                $up['size'],
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();
            $this->audit('lesson.updated', 'lesson', (int)$id);
            Session::flash('success', 'Lesson updated successfully.');
            redirect('/admin/lessons/' . $id);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Update failed: ' . $e->getMessage());
            redirect('/admin/lessons/' . $id . '/edit');
        }
    }

    public function destroy(Request $request, string|int $id): never {
        $lesson = Database::fetchOne("SELECT course_id FROM lessons WHERE id = ?", [$id]);
        $courseId = $lesson['course_id'] ?? null;
        Database::query("DELETE FROM lessons WHERE id = ?", [$id]);
        $this->audit('lesson.deleted', 'lesson', (int)$id);
        Session::flash('success', 'Lesson deleted.');
        redirect($courseId ? '/admin/courses/' . $courseId : '/admin/courses');
    }

    public function deleteMaterial(Request $request, string|int $lessonId, string|int $materialId): never {
        $mat = Database::fetchOne("SELECT file_path FROM lesson_materials WHERE id = ? AND lesson_id = ?", [$materialId, $lessonId]);
        if ($mat) {
            FileHelper::delete($mat['file_path']);
            Database::query("DELETE FROM lesson_materials WHERE id = ?", [$materialId]);
            Session::flash('success', 'Material attachment removed.');
        }
        redirect('/admin/lessons/' . $lessonId . '/edit');
    }

    // Student View: Interactive Lesson Taker
    public function studentView(Request $request, string|int $id): string {
        $student = AuthHelper::user();
        $studentId = $student['id'];

        $lesson = Database::fetchOne(
            "SELECT l.*, c.title as course_title, c.code as course_code, u.title as unit_title 
             FROM lessons l 
             JOIN courses c ON l.course_id = c.id 
             JOIN units u ON l.unit_id = u.id 
             WHERE l.id = ? AND l.status = 'published' LIMIT 1",
            [$id]
        );

        if (!$lesson) {
            Session::flash('error', 'Lesson not found or unavailable.');
            redirect('/student');
        }

        $video = Database::fetchOne("SELECT * FROM lesson_videos WHERE lesson_id = ?", [$id]);
        $materials = Database::fetchAll("SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY sort_order ASC", [$id]);

        // Lesson progress
        $progress = Database::fetchOne("SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ?", [$studentId, $id]);

        // Previous and Next Lessons in course
        $courseLessons = Database::fetchAll(
            "SELECT l.id, l.title, l.unit_id 
             FROM lessons l 
             JOIN units u ON l.unit_id = u.id 
             WHERE l.course_id = ? AND l.status = 'published' 
             ORDER BY u.sort_order ASC, l.sort_order ASC, l.id ASC",
            [$lesson['course_id']]
        );

        $prevLesson = null;
        $nextLesson = null;
        $found = false;
        foreach ($courseLessons as $cl) {
            if ($cl['id'] == $id) {
                $found = true;
                continue;
            }
            if (!$found) {
                $prevLesson = $cl;
            } else {
                $nextLesson = $cl;
                break;
            }
        }

        // Discussions
        $discussions = Database::fetchAll(
            "SELECT d.*, u.name as author_name, 
                    (SELECT COUNT(*) FROM discussion_replies dr WHERE dr.discussion_id = d.id AND dr.is_hidden = 0) as reply_count 
             FROM discussions d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.lesson_id = ? AND d.is_hidden = 0 
             ORDER BY d.is_pinned DESC, d.id DESC",
            [$id]
        );

        return $this->render('student/lesson_view', [
            'title' => $lesson['title'] . ' - ' . $lesson['course_title'],
            'lesson' => $lesson,
            'video' => $video,
            'materials' => $materials,
            'progress' => $progress,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'discussions' => $discussions,
        ]);
    }

    // Student: Mark Lesson Complete
    public function markComplete(Request $request, string|int $id): never {
        $student = AuthHelper::user();
        $studentId = $student['id'];

        $lesson = Database::fetchOne("SELECT course_id, completion_rule FROM lessons WHERE id = ?", [$id]);
        if (!$lesson) {
            redirect('/student');
        }

        Database::query(
            "INSERT INTO lesson_progress (user_id, lesson_id, is_completed, completed_at, updated_at) 
             VALUES (?, ?, 1, datetime('now'), datetime('now')) 
             ON CONFLICT(user_id, lesson_id) DO UPDATE SET 
             is_completed = 1, completed_at = COALESCE(completed_at, datetime('now')), updated_at = datetime('now')",
            [$studentId, $id]
        );

        // Recalculate Course Progress & Check Certificate Eligibility
        $this->recalculateCourseProgress($studentId, $lesson['course_id']);

        Session::flash('success', 'Lesson marked as completed! Keep up the great work.');
        redirect('/student/lessons/' . $id);
    }

    // AJAX Endpoint: 90% Video Watch Tracking
    public function videoProgress(Request $request, string|int $id): never {
        $student = AuthHelper::user();
        $studentId = $student['id'];

        $seconds = (int)$request->input('seconds_watched', 0);
        $percentage = (float)$request->input('percentage', 0.0);

        $lesson = Database::fetchOne("SELECT course_id, completion_rule FROM lessons WHERE id = ?", [$id]);
        if (!$lesson) {
            json_response(['error' => 'Lesson not found'], 404);
        }

        $autoComplete = ($lesson['completion_rule'] === 'video_90' && $percentage >= 90.0);

        Database::query(
            "INSERT INTO lesson_progress (user_id, lesson_id, is_completed, video_seconds_watched, video_max_percentage, completed_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, datetime('now')) 
             ON CONFLICT(user_id, lesson_id) DO UPDATE SET 
             video_seconds_watched = MAX(video_seconds_watched, excluded.video_seconds_watched),
             video_max_percentage = MAX(video_max_percentage, excluded.video_max_percentage),
             is_completed = CASE WHEN excluded.is_completed = 1 THEN 1 ELSE is_completed END,
             completed_at = CASE WHEN excluded.is_completed = 1 AND completed_at IS NULL THEN datetime('now') ELSE completed_at END,
             updated_at = datetime('now')",
            [
                $studentId,
                $id,
                $autoComplete ? 1 : 0,
                $seconds,
                $percentage,
                $autoComplete ? date('Y-m-d H:i:s') : null,
            ]
        );

        if ($autoComplete) {
            $this->recalculateCourseProgress($studentId, $lesson['course_id']);
        }

        json_response(['success' => true, 'is_completed' => $autoComplete, 'percentage' => $percentage]);
    }

    protected function recalculateCourseProgress(int $userId, int $courseId): void {
        $totalLessons = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM lessons WHERE course_id = ? AND status = 'published'",
            [$courseId]
        )['cnt'] ?? 0);

        $completedLessons = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM lesson_progress lp 
             JOIN lessons l ON lp.lesson_id = l.id 
             WHERE l.course_id = ? AND lp.user_id = ? AND lp.is_completed = 1 AND l.status = 'published'",
            [$courseId, $userId]
        )['cnt'] ?? 0);

        $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0.0;
        $isCompleted = ($percentage >= 100.0) ? 1 : 0;

        Database::query(
            "INSERT INTO course_progress (user_id, course_id, progress_percentage, completed_lessons_count, total_lessons_count, is_completed, completed_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now')) 
             ON CONFLICT(user_id, course_id) DO UPDATE SET 
             progress_percentage = excluded.progress_percentage,
             completed_lessons_count = excluded.completed_lessons_count,
             total_lessons_count = excluded.total_lessons_count,
             is_completed = excluded.is_completed,
             completed_at = CASE WHEN excluded.is_completed = 1 AND completed_at IS NULL THEN datetime('now') ELSE completed_at END,
             updated_at = datetime('now')",
            [
                $userId,
                $courseId,
                $percentage,
                $completedLessons,
                $totalLessons,
                $isCompleted,
                $isCompleted ? date('Y-m-d H:i:s') : null,
            ]
        );

        // If 100% completed, auto-issue certificate if not already issued
        if ($isCompleted) {
            $this->issueCertificateIfEligible($userId, $courseId);
        }
    }

    protected function issueCertificateIfEligible(int $userId, int $courseId): void {
        $existing = Database::fetchOne("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?", [$userId, $courseId]);
        if ($existing) {
            return;
        }

        $user = Database::fetchOne("SELECT name FROM users WHERE id = ?", [$userId]);
        $course = Database::fetchOne("SELECT title FROM courses WHERE id = ?", [$courseId]);
        $teacher = Database::fetchOne(
            "SELECT u.name FROM users u JOIN course_teachers ct ON u.id = ct.teacher_id WHERE ct.course_id = ? LIMIT 1",
            [$courseId]
        );

        $certNum = 'CERT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $verCode = strtoupper(bin2hex(random_bytes(8)));

        Database::query(
            "INSERT INTO certificates (user_id, course_id, certificate_number, verification_code, student_name, course_name, instructor_name, issue_date, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, date('now'), datetime('now'))",
            [
                $userId,
                $courseId,
                $certNum,
                $verCode,
                $user['name'],
                $course['title'],
                $teacher['name'] ?? 'Faculty Board',
            ]
        );

        // Trigger in-app notification
        Database::query(
            "INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at) 
             VALUES (?, 'Course Completed!', ?, '/student/certificates', 'success', 0, datetime('now'))",
            [
                $userId,
                "Congratulations! You completed '{$course['title']}' and earned your certificate.",
            ]
        );
    }
}
