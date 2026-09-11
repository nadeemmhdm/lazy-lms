<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\FileHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Services\MailService;
use Throwable;

/**
 * Lazy LMS - Expanded Assignment Controller
 * Supports MCQ, Short Answer, and Descriptive (Write / Upload / Both) Assignments
 */
class AssignmentController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            $stmt = $db->prepare("
                SELECT a.*, c.title as course_title,
                       (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id AND user_id = ?) as student_attempts,
                       (SELECT grade FROM assignment_submissions WHERE assignment_id = a.id AND user_id = ? ORDER BY id DESC LIMIT 1) as latest_grade,
                       (SELECT status FROM assignment_submissions WHERE assignment_id = a.id AND user_id = ? ORDER BY id DESC LIMIT 1) as submission_status
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                JOIN batch_courses bc ON c.id = bc.course_id
                JOIN batch_students bs ON bc.batch_id = bs.batch_id
                WHERE bs.student_id = ? AND bs.status = 'active'
                ORDER BY a.due_date ASC
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
            $assignments = $stmt->fetchAll();

            $this->render('assignments/student_index', [
                'assignments' => $assignments,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher
        $this->requirePermission('assessments.view');
        
        $sql = "
            SELECT a.*, c.title as course_title,
                   (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as total_submissions,
                   (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id AND status = 'submitted') as pending_grading
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
        ";
        
        $params = [];
        // If teacher without unrestricted access, filter by their courses
        if ($user['role'] === 'teacher' && !AuthHelper::hasRole(['super_admin', 'admin'])) {
            $sql .= " WHERE a.course_id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ?)";
            $params[] = $user['id'];
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        $this->render('assignments/index', [
            'assignments' => $assignments,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('assessments.create');
        $db = Database::getInstance();
        $user = $this->currentUser();

        $coursesSql = "SELECT id, title, public_id FROM courses WHERE status != 'archived'";
        $params = [];
        if ($user['role'] === 'teacher' && !AuthHelper::hasRole(['super_admin', 'admin'])) {
            $coursesSql .= " AND id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ?)";
            $params[] = $user['id'];
        }
        $coursesSql .= " ORDER BY title ASC";
        $stmt = $db->prepare($coursesSql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();

        $this->render('assignments/create', [
            'courses' => $courses,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('assessments.create');
        $this->validateCsrf();

        $courseId = HashId::resolveId('courses', Request::post('course_id'));
        $title = trim((string)Request::post('title', ''));
        $instructions = trim((string)Request::post('instructions', ''));
        $assignmentType = Request::post('assignment_type', 'descriptive');
        $submissionMode = Request::post('submission_mode', 'both');
        $maxMarks = max(1, (float)Request::post('max_marks', 100));
        $startDate = Request::post('start_date') ?: date('Y-m-d H:i:s');
        $dueDate = Request::post('due_date');
        $maxAttempts = max(1, (int)Request::post('max_attempts', 1));
        $cooldownHours = max(0, (int)Request::post('attempt_cooldown_hours', 0));
        $lateAllowed = !empty(Request::post('late_allowed')) ? 1 : 0;
        $allowedExtensions = trim((string)Request::post('allowed_extensions', 'pdf,doc,docx,zip,txt'));
        $maxFileSize = min(100, max(1, (int)Request::post('max_file_size_mb', 25)));
        $maxFiles = max(1, (int)Request::post('max_files', 3));

        if (!$courseId || empty($title) || empty($dueDate)) {
            Session::flash('error', 'Course, Title, and Due Date are required.');
            Response::redirect('/assignments/create');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('assignments');

        $stmt = $db->prepare("
            INSERT INTO assignments (
                public_id, course_id, title, instructions, max_marks, due_date,
                assignment_type, submission_mode, start_date, max_attempts,
                attempt_cooldown_hours, late_allowed, allowed_extensions, max_file_size_mb, max_files,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $publicId, $courseId, $title, $instructions, $maxMarks, $dueDate,
            $assignmentType, $submissionMode, $startDate, $maxAttempts,
            $cooldownHours, $lateAllowed, $allowedExtensions, $maxFileSize, $maxFiles
        ]);

        $assignmentId = $db->lastInsertId();

        // Handle MCQ questions if submitted during creation
        if ($assignmentType === 'mcq' && !empty($_POST['mcq_questions'])) {
            foreach ($_POST['mcq_questions'] as $q) {
                if (empty(trim($q['text'] ?? ''))) continue;
                $qPub = HashId::generate('assignment_questions');
                $qStmt = $db->prepare("INSERT INTO assignment_questions (public_id, assignment_id, question_text, marks, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
                $qMarks = max(0.5, (float)($q['marks'] ?? 1.0));
                $qStmt->execute([$qPub, $assignmentId, trim($q['text']), $qMarks]);
                $qId = $db->lastInsertId();

                $correctIndex = (int)($q['correct'] ?? 0);
                if (!empty($q['options']) && is_array($q['options'])) {
                    foreach ($q['options'] as $idx => $optText) {
                        if (trim($optText) === '') continue;
                        $oStmt = $db->prepare("INSERT INTO assignment_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)");
                        $oStmt->execute([$qId, trim($optText), ($idx === $correctIndex) ? 1 : 0, $idx]);
                    }
                }
            }
        }

        // Notify enrolled students via email & in-app
        $this->notifyAssignmentPublished($assignmentId, $title, $courseId, $dueDate);

        $this->logAudit('assignment.create', 'assignments', $assignmentId, ['title' => $title]);
        Session::flash('success', 'Assignment published successfully.');
        Response::redirect('/assignments/' . $publicId);
    }

    public function show(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $assignmentId = HashId::resolveId('assignments', $id);
        if (!$assignmentId) Response::abort(404);

        $stmt = $db->prepare("
            SELECT a.*, c.title as course_title, c.public_id as course_public_id 
            FROM assignments a 
            JOIN courses c ON a.course_id = c.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch();
        if (!$assignment) Response::abort(404);

        // Load MCQ questions if MCQ assignment
        $questions = [];
        if ($assignment['assignment_type'] === 'mcq') {
            $qStmt = $db->prepare("SELECT * FROM assignment_questions WHERE assignment_id = ? ORDER BY sort_order ASC, id ASC");
            $qStmt->execute([$assignmentId]);
            $questions = $qStmt->fetchAll();
            foreach ($questions as &$q) {
                $optStmt = $db->prepare("SELECT * FROM assignment_options WHERE question_id = ? ORDER BY sort_order ASC, id ASC");
                $optStmt->execute([$q['id']]);
                $q['options'] = $optStmt->fetchAll();
            }
        }

        if ($user['role'] === 'student') {
            // Student attempts history
            $subStmt = $db->prepare("
                SELECT s.*, u.original_name as file_name
                FROM assignment_submissions s
                LEFT JOIN uploads u ON s.file_id = u.id
                WHERE s.assignment_id = ? AND s.user_id = ?
                ORDER BY s.attempt_number DESC
            ");
            $subStmt->execute([$assignmentId, $user['id']]);
            $submissions = $subStmt->fetchAll();
            $latestSubmission = $submissions[0] ?? null;

            $this->render('assignments/student_view', [
                'assignment' => $assignment,
                'submissions' => $submissions,
                'submission' => $latestSubmission,
                'questions' => $questions,
                'user' => $user
            ]);
            return;
        }

        // Submissions list for teacher/admin
        $subStmt = $db->prepare("
            SELECT s.*, u.name as student_name, u.email as student_email, up.original_name as file_name
            FROM assignment_submissions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN uploads up ON s.file_id = up.id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ");
        $subStmt->execute([$assignmentId]);
        $submissions = $subStmt->fetchAll();

        $this->render('assignments/show', [
            'assignment' => $assignment,
            'submissions' => $submissions,
            'questions' => $questions,
            'user' => $user
        ]);
    }

    public function submit(string|int $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        if ($user['role'] !== 'student') Response::abort(403);

        $assignmentId = HashId::resolveId('assignments', $id);
        if (!$assignmentId) Response::abort(404);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch();
        if (!$assignment) Response::abort(404);

        // Check attempt limit
        $attemptCount = (int)$db->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?")
            ->execute([$assignmentId, $user['id']]) ? $db->query("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = {$assignmentId} AND user_id = {$user['id']}")->fetchColumn() : 0;

        $maxAttempts = (int)($assignment['max_attempts'] ?: 1);
        if ($attemptCount >= $maxAttempts) {
            Session::flash('error', "Maximum attempt limit of {$maxAttempts} reached for this assignment.");
            Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
        }

        // Check cooldown
        $cooldownHours = (int)($assignment['attempt_cooldown_hours'] ?? 0);
        if ($cooldownHours > 0 && $attemptCount > 0) {
            $lastSub = $db->query("SELECT submitted_at FROM assignment_submissions WHERE assignment_id = {$assignmentId} AND user_id = {$user['id']} ORDER BY id DESC LIMIT 1")->fetch();
            if ($lastSub && !empty($lastSub['submitted_at'])) {
                $lastTime = strtotime($lastSub['submitted_at']);
                $cooldownEnds = $lastTime + ($cooldownHours * 3600);
                if (time() < $cooldownEnds) {
                    $waitMins = ceil(($cooldownEnds - time()) / 60);
                    Session::flash('error', "Attempt cooldown in effect. Please wait {$waitMins} more minute(s).");
                    Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
                }
            }
        }

        // Check due date & late submission rule
        $isLate = 0;
        $now = time();
        $dueTime = strtotime($assignment['due_date']);
        if ($now > $dueTime) {
            if (empty($assignment['late_allowed'])) {
                Session::flash('error', 'The due date has passed and late submissions are not allowed for this assignment.');
                Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
            }
            $isLate = 1;
        }

        $textContent = trim((string)Request::post('text_content', ''));
        $fileId = null;
        $score = null;
        $status = 'submitted';

        // MCQ grading
        if ($assignment['assignment_type'] === 'mcq') {
            $answers = Request::post('mcq_answers', []);
            $qStmt = $db->prepare("SELECT * FROM assignment_questions WHERE assignment_id = ?");
            $qStmt->execute([$assignmentId]);
            $questions = $qStmt->fetchAll();

            $totalMarks = 0;
            $earnedMarks = 0;
            foreach ($questions as $q) {
                $totalMarks += (float)$q['marks'];
                $selectedOptId = (int)($answers[$q['id']] ?? 0);
                if ($selectedOptId) {
                    $optCheck = $db->prepare("SELECT is_correct FROM assignment_options WHERE id = ? AND question_id = ?");
                    $optCheck->execute([$selectedOptId, $q['id']]);
                    if ((int)$optCheck->fetchColumn() === 1) {
                        $earnedMarks += (float)$q['marks'];
                    }
                }
            }
            $score = ($totalMarks > 0) ? round(($earnedMarks / $totalMarks) * (float)$assignment['max_marks'], 2) : 0;
            $status = 'graded';
            $textContent = json_encode(['answers' => $answers, 'earned' => $earnedMarks, 'total' => $totalMarks]);
        }

        // Upload file handling
        if (!empty($_FILES['file']['name'])) {
            // Validate allowed extensions & file size
            $rawAllowed = explode(',', str_replace(' ', '', $assignment['allowed_extensions'] ?: 'pdf,doc,docx,zip,txt'));
            $maxBytes = (int)($assignment['max_file_size_mb'] ?: 25) * 1024 * 1024;

            $upResult = FileHelper::upload($_FILES['file'], 'submissions', $rawAllowed, $maxBytes);
            if ($upResult['success']) {
                $fStmt = $db->prepare("INSERT INTO uploads (filename, original_name, mime_type, file_size, file_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, 'submission', ?, datetime('now'))");
                $fStmt->execute([$upResult['filename'], $upResult['original_name'], $upResult['mime_type'], $upResult['size'], $user['id']]);
                $fileId = $db->lastInsertId();
            } else {
                Session::flash('error', $upResult['error']);
                Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
            }
        }

        // Validate submission requirements according to assignment mode
        if ($assignment['assignment_type'] === 'descriptive') {
            $mode = $assignment['submission_mode'] ?? 'both';
            if ($mode === 'write' && empty($textContent)) {
                Session::flash('error', 'Please write your answer in the text field.');
                Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
            }
            if ($mode === 'upload' && empty($fileId)) {
                Session::flash('error', 'Please upload a valid answer document.');
                Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
            }
            if ($mode === 'both' && empty($textContent) && empty($fileId)) {
                Session::flash('error', 'Please write your answer or upload a submission file.');
                Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
            }
        }

        $attemptNumber = $attemptCount + 1;
        $subPublicId = HashId::generate('assignment_submissions');

        $insStmt = $db->prepare("
            INSERT INTO assignment_submissions (
                public_id, assignment_id, user_id, attempt_number, submission_text, file_id,
                grade, status, is_late, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $insStmt->execute([
            $subPublicId, $assignmentId, $user['id'], $attemptNumber,
            $textContent, $fileId, $score, $status, $isLate
        ]);

        $this->logAudit('assignment.submit', 'assignment_submissions', $db->lastInsertId(), [
            'assignment_id' => $assignmentId,
            'attempt' => $attemptNumber,
            'is_late' => $isLate
        ]);

        Session::flash('success', ($assignment['assignment_type'] === 'mcq') ? "MCQ Assignment submitted and auto-graded: {$score} / {$assignment['max_marks']} marks!" : "Assignment submitted successfully (Attempt {$attemptNumber}/{$maxAttempts})!");
        Response::redirect('/assignments/' . ($assignment['public_id'] ?: $assignmentId));
    }

    public function grade(string|int $submissionId): void
    {
        $this->requirePermission('assessments.grade');
        $this->validateCsrf();

        $subId = HashId::resolveId('assignment_submissions', $submissionId);
        if (!$subId) Response::abort(404);

        $grade = (float)Request::post('grade', 0);
        $feedback = trim((string)Request::post('feedback', ''));

        $db = Database::getInstance();
        $user = $this->currentUser();

        $stmt = $db->prepare("
            UPDATE assignment_submissions 
            SET grade = ?, feedback = ?, graded_by = ?, graded_at = datetime('now'), status = 'graded'
            WHERE id = ?
        ");
        $stmt->execute([$grade, $feedback, $user['id'], $subId]);

        // Send student notifications
        $infoStmt = $db->prepare("
            SELECT s.user_id, a.title, a.id as assignment_id, a.public_id, u.name as student_name, u.email as student_email, c.title as course_title
            FROM assignment_submissions s 
            JOIN assignments a ON s.assignment_id = a.id 
            JOIN courses c ON a.course_id = c.id
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $infoStmt->execute([$subId]);
        $info = $infoStmt->fetch();

        if ($info) {
            $notif = $db->prepare("INSERT INTO notifications (user_id, title, message, link, type, created_at) VALUES (?, ?, ?, ?, 'grade', datetime('now'))");
            $notif->execute([
                $info['user_id'],
                "Assignment Graded: {$info['title']}",
                "Your submission received {$grade} marks. Feedback: {$feedback}",
                "/assignments/" . ($info['public_id'] ?: $info['assignment_id'])
            ]);

            // Dispatch automated template email
            MailService::sendTemplate('assignment.graded', [
                'id' => $info['user_id'],
                'name' => $info['student_name'],
                'email' => $info['student_email']
            ], [
                'assignment_name' => $info['title'],
                'course_name' => $info['course_title'],
                'grade' => $grade,
                'feedback' => $feedback ?: 'None provided'
            ]);
        }

        Session::flash('success', 'Assignment submission graded successfully.');
        Response::redirect('/assignments/' . ($info['public_id'] ?? $info['assignment_id'] ?? ''));
    }

    /**
     * Protected Submission File Download Endpoint (Section 43)
     */
    public function downloadSubmissionFile(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $subId = HashId::resolveId('assignment_submissions', $id);
        if (!$subId) Response::abort(404);

        $stmt = $db->prepare("
            SELECT s.*, a.course_id, up.filename, up.original_name, up.mime_type, up.file_size
            FROM assignment_submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN uploads up ON s.file_id = up.id
            WHERE s.id = ?
        ");
        $stmt->execute([$subId]);
        $sub = $stmt->fetch();
        if (!$sub || empty($sub['filename'])) Response::abort(404);

        // Security check: Student can ONLY download their own file
        if ($user['role'] === 'student' && (int)$sub['user_id'] !== (int)$user['id']) {
            Response::abort(403);
        }

        // Security check: Teacher must be assigned to this course (unless admin)
        if ($user['role'] === 'teacher' && !AuthHelper::hasRole(['super_admin', 'admin'])) {
            $tCheck = $db->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
            $tCheck->execute([$sub['course_id'], $user['id']]);
            if (!$tCheck->fetch()) Response::abort(403);
        }

        $filePath = storage_path('uploads/submissions/' . $sub['filename']);
        if (!file_exists($filePath)) {
            // Try standard uploads path fallback
            $filePath = storage_path('uploads/' . $sub['filename']);
            if (!file_exists($filePath)) Response::abort(404);
        }

        // Stream file safely
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($sub['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($sub['original_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    private function notifyAssignmentPublished(int $assignmentId, string $title, int $courseId, string $dueDate): void
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT u.id, u.name, u.email, c.title as course_title, a.public_id
                FROM batch_courses bc
                JOIN batch_students bs ON bc.batch_id = bs.batch_id
                JOIN users u ON bs.student_id = u.id
                JOIN courses c ON bc.course_id = c.id
                JOIN assignments a ON a.id = ?
                WHERE bc.course_id = ? AND bs.status = 'active'
            ");
            $stmt->execute([$assignmentId, $courseId]);
            $students = $stmt->fetchAll();

            foreach ($students as $st) {
                // In-app notification
                $db->prepare("INSERT INTO notifications (user_id, title, message, link, type, created_at) VALUES (?, ?, ?, ?, 'assignment', datetime('now'))")
                   ->execute([
                       $st['id'],
                       "New Assignment: {$title}",
                       "A new assignment has been published in {$st['course_title']}. Due: {$dueDate}",
                       "/assignments/" . ($st['public_id'] ?: $assignmentId)
                   ]);

                // Email
                MailService::sendTemplate('assignment.published', $st, [
                    'assignment_name' => $title,
                    'course_name' => $st['course_title'],
                    'due_date' => $dueDate
                ]);
            }
        } catch (Throwable $e) {
            // ignore
        }
    }
}
