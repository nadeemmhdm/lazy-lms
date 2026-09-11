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
 * Lazy LMS - Examination Controller
 * Supports MCQ (auto-graded), Short Answer, and Descriptive (Write / Upload) with server-side timer,
 * auto-save, auto-submit, attempt tracking, and teacher grading dashboard.
 */
class ExamController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            $stmt = $db->prepare("
                SELECT e.*, c.title as course_title, b.name as batch_name,
                       (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND user_id = ?) as student_attempts,
                       (SELECT score FROM exam_attempts WHERE exam_id = e.id AND user_id = ? AND status != 'in_progress' ORDER BY id DESC LIMIT 1) as latest_score,
                       (SELECT is_passed FROM exam_attempts WHERE exam_id = e.id AND user_id = ? AND status != 'in_progress' ORDER BY id DESC LIMIT 1) as latest_passed,
                       (SELECT id FROM exam_attempts WHERE exam_id = e.id AND user_id = ? AND status = 'in_progress' LIMIT 1) as active_attempt_id
                FROM exams e
                JOIN courses c ON e.course_id = c.id
                JOIN batch_courses bc ON c.id = bc.course_id
                JOIN batch_students bs ON bc.batch_id = bs.batch_id
                LEFT JOIN batches b ON e.batch_id = b.id
                WHERE bs.student_id = ? AND bs.status = 'active' AND e.status = 'published'
                ORDER BY e.start_time ASC
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
            $exams = $stmt->fetchAll();

            $this->render('exams/student_index', [
                'exams' => $exams,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher
        $this->requirePermission('assessments.view');
        
        $sql = "
            SELECT e.*, c.title as course_title, b.name as batch_name,
                   (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
                   (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id) as total_attempts,
                   (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND status = 'submitted') as pending_grading
            FROM exams e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN batches b ON e.batch_id = b.id
        ";

        $params = [];
        if ($user['role'] === 'teacher' && !AuthHelper::hasRole(['super_admin', 'admin'])) {
            $sql .= " WHERE e.course_id IN (SELECT course_id FROM course_teachers WHERE teacher_id = ?)";
            $params[] = $user['id'];
        }

        $sql .= " ORDER BY e.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $exams = $stmt->fetchAll();

        $this->render('exams/index', [
            'exams' => $exams,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('assessments.create');
        $db = Database::getInstance();
        $user = $this->currentUser();

        $courses = $db->query("SELECT id, title, public_id FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $batches = $db->query("SELECT id, name, public_id FROM batches WHERE status = 'active' ORDER BY name ASC")->fetchAll();

        $this->render('exams/create', [
            'courses' => $courses,
            'batches' => $batches,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('assessments.create');
        $this->validateCsrf();

        $courseId = HashId::resolveId('courses', Request::post('course_id'));
        $batchId = !empty(Request::post('batch_id')) ? HashId::resolveId('batches', Request::post('batch_id')) : null;
        $title = trim((string)Request::post('title', ''));
        $description = trim((string)Request::post('description', ''));
        $instructions = trim((string)Request::post('instructions', ''));
        $startTime = Request::post('start_time');
        $endTime = Request::post('end_time');
        $duration = max(5, (int)Request::post('duration_minutes', 60));
        $totalMarks = max(1, (float)Request::post('total_marks', 100));
        $passingScore = max(1, (float)Request::post('passing_score', 40));
        $maxAttempts = max(1, (int)Request::post('max_attempts', 1));
        $cooldown = max(0, (int)Request::post('cooldown_minutes', 0));
        $randQuestions = !empty(Request::post('randomize_questions')) ? 1 : 0;
        $randOptions = !empty(Request::post('randomize_options')) ? 1 : 0;
        $autoSubmit = !empty(Request::post('auto_submit', 1)) ? 1 : 0;
        $status = Request::post('status', 'published');

        if (!$courseId || empty($title) || empty($startTime) || empty($endTime)) {
            Session::flash('error', 'Course, Title, Start Time, and End Time are required.');
            Response::redirect('/exams/create');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('exams');
        $user = $this->currentUser();

        $stmt = $db->prepare("
            INSERT INTO exams (
                public_id, batch_id, course_id, title, description, instructions,
                start_time, end_time, duration_minutes, total_marks, passing_score,
                max_attempts, cooldown_minutes, randomize_questions, randomize_options,
                auto_submit, status, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $publicId, $batchId, $courseId, $title, $description, $instructions,
            $startTime, $endTime, $duration, $totalMarks, $passingScore,
            $maxAttempts, $cooldown, $randQuestions, $randOptions,
            $autoSubmit, $status, $user['id']
        ]);

        $examId = $db->lastInsertId();

        // Process questions if added in create wizard
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                if (empty(trim($q['text'] ?? ''))) continue;
                $qPub = HashId::generate('exam_questions');
                $qType = $q['type'] ?? 'mcq';
                $descMode = $q['desc_mode'] ?? 'both';
                $marks = max(0.5, (float)($q['marks'] ?? 1.0));
                $optsJson = null;
                $correct = null;

                if ($qType === 'mcq') {
                    $opts = $q['options'] ?? [];
                    $optsJson = json_encode(array_values(array_filter($opts, fn($v) => trim((string)$v) !== '')));
                    $correct = (string)($q['correct'] ?? '0');
                }

                $qStmt = $db->prepare("
                    INSERT INTO exam_questions (
                        public_id, exam_id, question_type, descriptive_mode, question_text,
                        marks, options_json, correct_answer, sort_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $qStmt->execute([$qPub, $examId, $qType, $descMode, trim($q['text']), $marks, $optsJson, $correct]);
            }
        }

        $this->logAudit('exam.create', 'exams', $examId, ['title' => $title]);
        Session::flash('success', 'Examination created successfully.');
        Response::redirect('/exams/' . $publicId);
    }

    public function show(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $examId = HashId::resolveId('exams', $id);
        if (!$examId) Response::abort(404);

        $exam = Database::fetchOne("
            SELECT e.*, c.title as course_title, b.name as batch_name 
            FROM exams e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN batches b ON e.batch_id = b.id 
            WHERE e.id = ?
        ", [$examId]);
        if (!$exam) Response::abort(404);

        $questions = Database::fetchAll("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC", [$examId]);

        if ($user['role'] === 'student') {
            $attempts = Database::fetchAll("
                SELECT * FROM exam_attempts 
                WHERE exam_id = ? AND user_id = ? 
                ORDER BY attempt_number DESC
            ", [$examId, $user['id']]);

            $this->render('exams/student_view', [
                'exam' => $exam,
                'questions' => $questions,
                'attempts' => $attempts,
                'user' => $user
            ]);
            return;
        }

        // Teacher/Admin View: show list of student attempts
        $attempts = Database::fetchAll("
            SELECT a.*, u.name as student_name, u.email as student_email, b.name as batch_name
            FROM exam_attempts a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active'
            LEFT JOIN batches b ON bs.batch_id = b.id
            WHERE a.exam_id = ?
            ORDER BY a.started_at DESC
        ", [$examId]);

        $this->render('exams/show', [
            'exam' => $exam,
            'questions' => $questions,
            'attempts' => $attempts,
            'user' => $user
        ]);
    }

    /**
     * Start / Resume taking an Exam (Section 7: Server-side timer & security)
     */
    public function take(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        if ($user['role'] !== 'student') Response::abort(403);

        $examId = HashId::resolveId('exams', $id);
        if (!$examId) Response::abort(404);

        $db = Database::getInstance();
        $exam = Database::fetchOne("SELECT * FROM exams WHERE id = ?", [$examId]);
        if (!$exam) Response::abort(404);

        $now = time();
        $startTime = strtotime($exam['start_time']);
        $endTime = strtotime($exam['end_time']);

        if ($now < $startTime) {
            Session::flash('error', 'This examination has not started yet. It opens on ' . date('d M Y, h:i A', $startTime));
            Response::redirect('/exams/' . ($exam['public_id'] ?: $examId));
        }
        if ($now > $endTime) {
            Session::flash('error', 'This examination closed on ' . date('d M Y, h:i A', $endTime));
            Response::redirect('/exams/' . ($exam['public_id'] ?: $examId));
        }

        // Find active in_progress attempt or check attempt limits
        $activeAttempt = Database::fetchOne("
            SELECT * FROM exam_attempts 
            WHERE exam_id = ? AND user_id = ? AND status = 'in_progress' 
            LIMIT 1
        ", [$examId, $user['id']]);

        if (!$activeAttempt) {
            $pastAttempts = (int)Database::fetchColumn("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND user_id = ?", [$examId, $user['id']]);
            if ($pastAttempts >= (int)$exam['max_attempts']) {
                Session::flash('error', "You have already completed the maximum number of attempts ({$exam['max_attempts']}) for this exam.");
                Response::redirect('/exams/' . ($exam['public_id'] ?: $examId));
            }

            // Check cooldown
            if ((int)$exam['cooldown_minutes'] > 0 && $pastAttempts > 0) {
                $lastSub = Database::fetchColumn("SELECT submitted_at FROM exam_attempts WHERE exam_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1", [$examId, $user['id']]);
                if ($lastSub) {
                    $cooldownEnds = strtotime($lastSub) + ((int)$exam['cooldown_minutes'] * 60);
                    if ($now < $cooldownEnds) {
                        $mins = ceil(($cooldownEnds - $now) / 60);
                        Session::flash('error', "Attempt cooldown in effect. Please wait {$mins} more minute(s).");
                        Response::redirect('/exams/' . ($exam['public_id'] ?: $examId));
                    }
                }
            }

            // Start new attempt
            $attPublicId = HashId::generate('exam_attempts');
            $attemptNumber = $pastAttempts + 1;
            $ins = $db->prepare("
                INSERT INTO exam_attempts (
                    public_id, exam_id, user_id, attempt_number, started_at, auto_saved_at,
                    max_score, status
                ) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'), ?, 'in_progress')
            ");
            $ins->execute([$attPublicId, $examId, $user['id'], $attemptNumber, $exam['total_marks']]);
            $activeAttempt = Database::fetchOne("SELECT * FROM exam_attempts WHERE id = ?", [$db->lastInsertId()]);
        }

        // Calculate server-side remaining time
        $startedTimestamp = strtotime($activeAttempt['started_at']);
        $durationSeconds = (int)$exam['duration_minutes'] * 60;
        $deadlineFromDuration = $startedTimestamp + $durationSeconds;
        $actualDeadline = min($deadlineFromDuration, $endTime);
        $secondsRemaining = $actualDeadline - $now;

        // If time expired, auto-submit
        if ($secondsRemaining <= 0) {
            $this->finalizeExamSubmission($activeAttempt['id'], true);
            Session::flash('info', 'Time expired! Your exam attempt has been automatically submitted.');
            Response::redirect('/exams/' . ($exam['public_id'] ?: $examId));
        }

        // Fetch questions
        $qSql = "SELECT * FROM exam_questions WHERE exam_id = ?";
        if (!empty($exam['randomize_questions'])) {
            $qSql .= " ORDER BY RANDOM()";
        } else {
            $qSql .= " ORDER BY sort_order ASC, id ASC";
        }
        $questions = Database::fetchAll($qSql, [$examId]);

        $savedAnswers = json_decode($activeAttempt['answers_json'] ?? '[]', true) ?: [];

        $this->render('exams/take', [
            'exam' => $exam,
            'attempt' => $activeAttempt,
            'questions' => $questions,
            'savedAnswers' => $savedAnswers,
            'secondsRemaining' => $secondsRemaining,
            'user' => $user
        ]);
    }

    /**
     * AJAX Auto-Save Endpoint (Section 7)
     */
    public function autoSave(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();

        $examId = HashId::resolveId('exams', $id);
        $attemptId = (int)Request::post('attempt_id');
        $answers = Request::post('answers', []);

        $db = Database::getInstance();
        $attempt = Database::fetchOne("SELECT * FROM exam_attempts WHERE id = ? AND exam_id = ? AND user_id = ? AND status = 'in_progress'", [$attemptId, $examId, $user['id']]);
        if (!$attempt) {
            Response::json(['success' => false, 'error' => 'No active attempt found.'], 403);
            return;
        }

        $db->prepare("UPDATE exam_attempts SET answers_json = ?, auto_saved_at = datetime('now') WHERE id = ?")
           ->execute([json_encode($answers), $attemptId]);

        Response::json(['success' => true, 'timestamp' => date('H:i:s')]);
    }

    /**
     * Complete and Submit Exam
     */
    public function submit(string|int $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();

        $examId = HashId::resolveId('exams', $id);
        $attemptId = (int)Request::post('attempt_id');

        $attempt = Database::fetchOne("SELECT * FROM exam_attempts WHERE id = ? AND exam_id = ? AND user_id = ? AND status = 'in_progress'", [$attemptId, $examId, $user['id']]);
        if (!$attempt) {
            Session::flash('error', 'Active attempt not found or already submitted.');
            Response::redirect('/exams');
        }

        // Collect answers from POST, including file uploads for descriptive questions
        $answers = Request::post('answers', []);
        
        if (!empty($_FILES['answer_files']['name']) && is_array($_FILES['answer_files']['name'])) {
            foreach ($_FILES['answer_files']['name'] as $qId => $fileName) {
                if (empty($fileName)) continue;
                $fileItem = [
                    'name' => $_FILES['answer_files']['name'][$qId],
                    'type' => $_FILES['answer_files']['type'][$qId],
                    'tmp_name' => $_FILES['answer_files']['tmp_name'][$qId],
                    'error' => $_FILES['answer_files']['error'][$qId],
                    'size' => $_FILES['answer_files']['size'][$qId],
                ];
                $up = FileHelper::upload($fileItem, 'submissions', ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'png']);
                if ($up['success']) {
                    $db = Database::getInstance();
                    $fStmt = $db->prepare("INSERT INTO uploads (filename, original_name, mime_type, file_size, file_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, 'exam_submission', ?, datetime('now'))");
                    $fStmt->execute([$up['filename'], $up['original_name'], $up['mime_type'], $up['size'], $user['id']]);
                    $answers[$qId]['file_id'] = $db->lastInsertId();
                    $answers[$qId]['file_name'] = $up['original_name'];
                }
            }
        }

        Database::query("UPDATE exam_attempts SET answers_json = ? WHERE id = ?", [json_encode($answers), $attemptId]);

        $this->finalizeExamSubmission($attemptId, false);

        Session::flash('success', 'Your examination has been submitted successfully.');
        Response::redirect('/exams/results/' . ($attempt['public_id'] ?: $attemptId));
    }

    /**
     * Finalize submission: auto-grade MCQ questions and set status
     */
    private function finalizeExamSubmission(int $attemptId, bool $autoTimedOut = false): void
    {
        $db = Database::getInstance();
        $attempt = Database::fetchOne("SELECT * FROM exam_attempts WHERE id = ?", [$attemptId]);
        if (!$attempt || $attempt['status'] !== 'in_progress') return;

        $exam = Database::fetchOne("SELECT * FROM exams WHERE id = ?", [$attempt['exam_id']]);
        $questions = Database::fetchAll("SELECT * FROM exam_questions WHERE exam_id = ?", [$attempt['exam_id']]);
        $answers = json_decode($attempt['answers_json'] ?? '[]', true) ?: [];

        $totalEarned = 0;
        $hasManualQuestions = false;

        foreach ($questions as $q) {
            if ($q['question_type'] === 'mcq') {
                $userAns = $answers[$q['id']] ?? null;
                if ($userAns !== null && (string)$userAns === (string)$q['correct_answer']) {
                    $totalEarned += (float)$q['marks'];
                }
            } else {
                $hasManualQuestions = true;
            }
        }

        $finalStatus = $hasManualQuestions ? 'submitted' : 'graded';
        $isPassed = (!$hasManualQuestions && $totalEarned >= (float)$exam['passing_score']) ? 1 : 0;

        $db->prepare("
            UPDATE exam_attempts 
            SET score = ?, status = ?, is_passed = ?, submitted_at = datetime('now')
            WHERE id = ?
        ")->execute([$totalEarned, $finalStatus, $isPassed, $attemptId]);

        $this->logAudit('exam.submit', 'exam_attempts', $attemptId, [
            'auto_timed_out' => $autoTimedOut,
            'status' => $finalStatus,
            'initial_mcq_score' => $totalEarned
        ]);
    }

    /**
     * Teacher Grading View for an attempt
     */
    public function gradeAttempt(string|int $examId, string|int $attemptId): void
    {
        $this->requirePermission('assessments.grade');
        $db = Database::getInstance();

        $eId = HashId::resolveId('exams', $examId);
        $aId = HashId::resolveId('exam_attempts', $attemptId);
        if (!$eId || !$aId) Response::abort(404);

        $exam = Database::fetchOne("SELECT e.*, c.title as course_title FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ?", [$eId]);
        $attempt = Database::fetchOne("SELECT a.*, u.name as student_name, u.email as student_email FROM exam_attempts a JOIN users u ON a.user_id = u.id WHERE a.id = ?", [$aId]);
        if (!$exam || !$attempt) Response::abort(404);

        $questions = Database::fetchAll("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC", [$eId]);
        $answers = json_decode($attempt['answers_json'] ?? '[]', true) ?: [];

        $this->render('exams/grade', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
            'user' => $this->currentUser()
        ]);
    }

    public function saveGrade(string|int $examId, string|int $attemptId): void
    {
        $this->requirePermission('assessments.grade');
        $this->validateCsrf();

        $eId = HashId::resolveId('exams', $examId);
        $aId = HashId::resolveId('exam_attempts', $attemptId);
        if (!$eId || !$aId) Response::abort(404);

        $db = Database::getInstance();
        $user = $this->currentUser();
        $exam = Database::fetchOne("SELECT * FROM exams WHERE id = ?", [$eId]);
        $attempt = Database::fetchOne("SELECT * FROM exam_attempts WHERE id = ?", [$aId]);
        if (!$exam || !$attempt) Response::abort(404);

        $totalScore = (float)Request::post('final_score', 0);
        $feedback = trim((string)Request::post('teacher_feedback', ''));
        $isPassed = ($totalScore >= (float)$exam['passing_score']) ? 1 : 0;

        $db->prepare("
            UPDATE exam_attempts 
            SET score = ?, status = 'graded', is_passed = ?, graded_by = ?, graded_at = datetime('now'), teacher_feedback = ?
            WHERE id = ?
        ")->execute([$totalScore, $isPassed, $user['id'], $feedback, $aId]);

        // Send student notification & email
        $student = Database::fetchOne("SELECT id, name, email FROM users WHERE id = ?", [$attempt['user_id']]);
        if ($student) {
            $db->prepare("INSERT INTO notifications (user_id, title, message, link, type, created_at) VALUES (?, ?, ?, ?, 'grade', datetime('now'))")
               ->execute([
                   $student['id'],
                   "Exam Graded: {$exam['title']}",
                   "Your exam result is ready. Score: {$totalScore} / {$exam['total_marks']}. " . ($isPassed ? 'Passed!' : 'Needs improvement.'),
                   "/exams/results/" . ($attempt['public_id'] ?: $aId)
               ]);
        }

        Session::flash('success', 'Exam graded and student notified.');
        Response::redirect('/exams/' . ($exam['public_id'] ?: $eId));
    }

    /**
     * Student view of completed attempt results
     */
    public function results(string|int $attemptId): void
    {
        $this->requireAuth();
        $user = $this->currentUser();

        $aId = HashId::resolveId('exam_attempts', $attemptId);
        if (!$aId) Response::abort(404);

        $attempt = Database::fetchOne("SELECT a.*, u.name as student_name FROM exam_attempts a JOIN users u ON a.user_id = u.id WHERE a.id = ?", [$aId]);
        if (!$attempt) Response::abort(404);

        // Security check
        if ($user['role'] === 'student' && (int)$attempt['user_id'] !== (int)$user['id']) {
            Response::abort(403);
        }

        $exam = Database::fetchOne("SELECT e.*, c.title as course_title FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ?", [$attempt['exam_id']]);
        $questions = Database::fetchAll("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC", [$attempt['exam_id']]);
        $answers = json_decode($attempt['answers_json'] ?? '[]', true) ?: [];

        $this->render('exams/results', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
            'user' => $user
        ]);
    }

    /**
     * Protected Answer File Download Endpoint
     */
    public function downloadAnswerFile(string|int $uploadId): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $up = Database::fetchOne("SELECT * FROM uploads WHERE id = ?", [$uploadId]);
        if (!$up) Response::abort(404);

        if ($user['role'] === 'student' && (int)$up['uploaded_by'] !== (int)$user['id']) {
            Response::abort(403);
        }

        $filePath = storage_path('uploads/submissions/' . $up['filename']);
        if (!file_exists($filePath)) {
            $filePath = storage_path('uploads/' . $up['filename']);
            if (!file_exists($filePath)) Response::abort(404);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($up['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($up['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
