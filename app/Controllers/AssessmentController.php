<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class AssessmentController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            // Fetch assessments available to student's batch courses
            $stmt = $db->prepare("
                SELECT a.*, c.title as course_title,
                       (SELECT MAX(score) FROM assessment_attempts WHERE assessment_id = a.id AND user_id = ?) as best_score,
                       (SELECT COUNT(*) FROM assessment_attempts WHERE assessment_id = a.id AND user_id = ?) as attempts_taken
                FROM assessments a
                JOIN courses c ON a.course_id = c.id
                JOIN batch_courses bc ON c.id = bc.course_id
                JOIN batch_students bs ON bc.batch_id = bs.batch_id
                WHERE bs.student_id = ? AND bs.status = 'active' AND a.status = 'published'
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id']]);
            $assessments = $stmt->fetchAll();

            $this->render('assessments/student_index', [
                'assessments' => $assessments,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher
        $this->requirePermission('assessments.view');
        $stmt = $db->query("
            SELECT a.*, c.title as course_title, u.title as unit_title,
                   (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
                   (SELECT COUNT(*) FROM assessment_attempts WHERE assessment_id = a.id) as total_attempts
            FROM assessments a
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN units u ON a.unit_id = u.id
            ORDER BY a.created_at DESC
        ");
        $assessments = $stmt->fetchAll();

        $this->render('assessments/index', [
            'assessments' => $assessments,
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('assessments.create');
        $db = Database::getInstance();
        $courses = $db->query("SELECT id, title FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $this->render('assessments/create', [
            'courses' => $courses,
            'user' => $this->currentUser()
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('assessments.create');
        $this->validateCsrf();

        $title = trim(Request::post('title', ''));
        $courseId = (int)Request::post('course_id');
        $unitId = Request::post('unit_id') ? (int)Request::post('unit_id') : null;
        $description = trim(Request::post('description', ''));
        $totalMarks = (float)Request::post('total_marks', 100);
        $passMarks = (float)Request::post('pass_marks', 40);
        $timeLimit = (int)Request::post('time_limit', 30);
        $attemptsAllowed = (int)Request::post('attempts_allowed', 1);
        $randomizeQuestions = Request::post('randomize_questions') ? 1 : 0;
        $status = Request::post('status', 'draft');

        if (empty($title) || !$courseId) {
            Session::flash('error', 'Title and Course are required.');
            Response::redirect('/assessments/create');
        }

        $db = Database::getInstance();
        $user = $this->currentUser();

        $stmt = $db->prepare("
            INSERT INTO assessments (course_id, unit_id, title, description, total_marks, pass_marks, time_limit, attempts_allowed, randomize_questions, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([$courseId, $unitId, $title, $description, $totalMarks, $passMarks, $timeLimit, $attemptsAllowed, $randomizeQuestions, $status, $user['id']]);
        $id = $db->lastInsertId();

        Session::flash('success', 'Assessment created successfully. Now add questions.');
        Response::redirect('/assessments/' . $id . '/builder');
    }

    public function builder(int $id): void
    {
        $this->requirePermission('assessments.edit');
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT a.*, c.title as course_title FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $assessment = $stmt->fetch();
        if (!$assessment) Response::abort(404);

        $qStmt = $db->prepare("
            SELECT aq.*, qb.question, qb.type, qb.marks, qb.difficulty, qb.category
            FROM assessment_questions aq
            JOIN question_bank qb ON aq.question_id = qb.id
            WHERE aq.assessment_id = ?
            ORDER BY aq.order_num ASC
        ");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        // Bank questions for picker
        $bankStmt = $db->query("SELECT * FROM question_bank ORDER BY created_at DESC LIMIT 100");
        $bankQuestions = $bankStmt->fetchAll();

        $this->render('assessments/builder', [
            'assessment' => $assessment,
            'questions' => $questions,
            'bankQuestions' => $bankQuestions,
            'user' => $this->currentUser()
        ]);
    }

    public function addQuestion(int $id): void
    {
        $this->requirePermission('assessments.edit');
        $this->validateCsrf();
        $db = Database::getInstance();

        $questionId = (int)Request::post('question_id');
        $marks = (float)Request::post('marks', 1.0);

        $maxOrderStmt = $db->prepare("SELECT MAX(order_num) FROM assessment_questions WHERE assessment_id = ?");
        $maxOrderStmt->execute([$id]);
        $nextOrder = ((int)$maxOrderStmt->fetchColumn()) + 1;

        $stmt = $db->prepare("INSERT INTO assessment_questions (assessment_id, question_id, marks, order_num) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $questionId, $marks, $nextOrder]);

        Session::flash('success', 'Question linked to assessment.');
        Response::redirect('/assessments/' . $id . '/builder');
    }

    public function removeQuestion(int $id, int $aqId): void
    {
        $this->requirePermission('assessments.edit');
        $this->validateCsrf();
        $db = Database::getInstance();

        $stmt = $db->prepare("DELETE FROM assessment_questions WHERE id = ? AND assessment_id = ?");
        $stmt->execute([$aqId, $id]);

        Session::flash('success', 'Question removed from assessment.');
        Response::redirect('/assessments/' . $id . '/builder');
    }

    // Student Assessment flow
    public function take(int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT a.*, c.title as course_title FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND a.status = 'published'");
        $stmt->execute([$id]);
        $assessment = $stmt->fetch();
        if (!$assessment) Response::abort(404);

        // Check attempts count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM assessment_attempts WHERE assessment_id = ? AND user_id = ?");
        $countStmt->execute([$id, $user['id']]);
        $attemptsTaken = (int)$countStmt->fetchColumn();

        if ($assessment['attempts_allowed'] > 0 && $attemptsTaken >= $assessment['attempts_allowed']) {
            Session::flash('error', 'You have exhausted all allowed attempts for this assessment.');
            Response::redirect('/assessments');
        }

        // Fetch questions with options
        $qStmt = $db->prepare("
            SELECT aq.marks as q_marks, qb.* 
            FROM assessment_questions aq 
            JOIN question_bank qb ON aq.question_id = qb.id 
            WHERE aq.assessment_id = ? 
            ORDER BY " . ($assessment['randomize_questions'] ? "RANDOM()" : "aq.order_num ASC")
        );
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        foreach ($questions as &$q) {
            $optStmt = $db->prepare("SELECT id, option_text FROM question_options WHERE question_id = ? ORDER BY order_num ASC");
            $optStmt->execute([$q['id']]);
            $q['options'] = $optStmt->fetchAll();
        }

        $this->render('assessments/take', [
            'assessment' => $assessment,
            'questions' => $questions,
            'attemptNumber' => $attemptsTaken + 1,
            'user' => $user
        ]);
    }

    public function submit(int $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT * FROM assessments WHERE id = ?");
        $stmt->execute([$id]);
        $assessment = $stmt->fetch();
        if (!$assessment) Response::abort(404);

        $answers = Request::post('answers', []); // [question_id => user_answer]
        $timeSpent = (int)Request::post('time_spent', 0);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM assessment_attempts WHERE assessment_id = ? AND user_id = ?");
        $countStmt->execute([$id, $user['id']]);
        $attemptNum = (int)$countStmt->fetchColumn() + 1;

        $db->beginTransaction();
        try {
            $attStmt = $db->prepare("
                INSERT INTO assessment_attempts (assessment_id, user_id, attempt_number, started_at, completed_at, score, max_score, status)
                VALUES (?, ?, ?, datetime('now'), datetime('now'), 0, ?, 'completed')
            ");
            $attStmt->execute([$id, $user['id'], $attemptNum, $assessment['total_marks']]);
            $attemptId = $db->lastInsertId();

            $totalScore = 0.0;
            $ansStmt = $db->prepare("
                INSERT INTO assessment_answers (attempt_id, question_id, user_answer, is_correct, marks_awarded, feedback)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            // Evaluate each question
            $qStmt = $db->prepare("SELECT aq.marks as assigned_marks, qb.* FROM assessment_questions aq JOIN question_bank qb ON aq.question_id = qb.id WHERE aq.assessment_id = ?");
            $qStmt->execute([$id]);
            $questions = $qStmt->fetchAll();

            foreach ($questions as $q) {
                $userAns = $answers[$q['id']] ?? '';
                $isCorrect = 0;
                $awarded = 0.0;
                $userAnsStr = is_array($userAns) ? json_encode($userAns) : trim((string)$userAns);

                if ($q['type'] === 'single_choice' || $q['type'] === 'true_false') {
                    // Check question_options where is_correct = 1
                    $optCheck = $db->prepare("SELECT id FROM question_options WHERE question_id = ? AND is_correct = 1");
                    $optCheck->execute([$q['id']]);
                    $correctOptId = $optCheck->fetchColumn();
                    if ($correctOptId && (string)$correctOptId === (string)$userAns) {
                        $isCorrect = 1;
                        $awarded = (float)$q['assigned_marks'];
                    }
                } elseif ($q['type'] === 'numeric') {
                    // Normalize and compare numbers
                    $correctNums = json_decode($q['correct_answer'] ?? '[]', true);
                    if (!empty($correctNums) && is_numeric($userAns)) {
                        $diff = abs((float)$userAns - (float)$correctNums[0]);
                        if ($diff < 0.0001) {
                            $isCorrect = 1;
                            $awarded = (float)$q['assigned_marks'];
                        }
                    }
                } elseif ($q['type'] === 'short_answer') {
                    $correctAnswers = json_decode($q['correct_answer'] ?? '[]', true);
                    if (is_array($correctAnswers)) {
                        foreach ($correctAnswers as $ca) {
                            if (strcasecmp(trim($ca), trim((string)$userAns)) === 0) {
                                $isCorrect = 1;
                                $awarded = (float)$q['assigned_marks'];
                                break;
                            }
                        }
                    }
                }

                $totalScore += $awarded;
                $ansStmt->execute([$attemptId, $q['id'], $userAnsStr, $isCorrect, $awarded, '']);
            }

            // Update attempt score and pass status
            $isPassed = ($totalScore >= $assessment['pass_marks']) ? 1 : 0;
            $updAtt = $db->prepare("UPDATE assessment_attempts SET score = ?, status = 'completed' WHERE id = ?");
            $updAtt->execute([$totalScore, $attemptId]);

            // If passed, trigger course completion evaluation
            if ($isPassed) {
                $this->checkCourseCompletion($assessment['course_id'], $user['id']);
            }

            $db->commit();
            Session::flash('success', "Assessment submitted! Your score: $totalScore / {$assessment['total_marks']}");
            Response::redirect('/assessments/results/' . $attemptId);
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Failed to submit assessment: ' . $e->getMessage());
            Response::redirect('/assessments/' . $id . '/take');
        }
    }

    public function results(int $attemptId): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT att.*, a.title as assessment_title, a.pass_marks, a.total_marks as max_possible, c.title as course_title, u.name as student_name
            FROM assessment_attempts att
            JOIN assessments a ON att.assessment_id = a.id
            JOIN courses c ON a.course_id = c.id
            JOIN users u ON att.user_id = u.id
            WHERE att.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        if (!$attempt) Response::abort(404);

        if ($user['role'] === 'student' && $attempt['user_id'] != $user['id']) {
            Response::abort(403);
        }

        $ansStmt = $db->prepare("
            SELECT aa.*, qb.question, qb.type, qb.explanation, qb.correct_answer
            FROM assessment_answers aa
            JOIN question_bank qb ON aa.question_id = qb.id
            WHERE aa.attempt_id = ?
        ");
        $ansStmt->execute([$attemptId]);
        $answers = $ansStmt->fetchAll();

        $this->render('assessments/results', [
            'attempt' => $attempt,
            'answers' => $answers,
            'user' => $user
        ]);
    }

    private function checkCourseCompletion(int $courseId, int $userId): void
    {
        $db = Database::getInstance();
        // Check if all units and lessons are completed
        $totalLessons = (int)$db->query("SELECT COUNT(*) FROM lessons WHERE unit_id IN (SELECT id FROM units WHERE course_id = $courseId)")->fetchColumn();
        $completedLessons = (int)$db->query("SELECT COUNT(*) FROM lesson_progress WHERE user_id = $userId AND is_completed = 1 AND lesson_id IN (SELECT id FROM lessons WHERE unit_id IN (SELECT id FROM units WHERE course_id = $courseId))")->fetchColumn();

        $percent = ($totalLessons > 0) ? round(($completedLessons / $totalLessons) * 100, 2) : 100;

        $progStmt = $db->prepare("
            INSERT INTO course_progress (user_id, course_id, progress_percentage, is_completed, completed_at, updated_at)
            VALUES (?, ?, ?, ?, ?, datetime('now'))
            ON CONFLICT(user_id, course_id) DO UPDATE SET 
                progress_percentage = excluded.progress_percentage,
                is_completed = excluded.is_completed,
                completed_at = CASE WHEN excluded.is_completed = 1 AND course_progress.completed_at IS NULL THEN datetime('now') ELSE course_progress.completed_at END,
                updated_at = datetime('now')
        ");
        $isCompleted = ($percent >= 100) ? 1 : 0;
        $completedAt = $isCompleted ? date('Y-m-d H:i:s') : null;
        $progStmt->execute([$userId, $courseId, $percent, $isCompleted, $completedAt]);

        // Auto-generate certificate if complete
        if ($isCompleted) {
            $certCheck = $db->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
            $certCheck->execute([$userId, $courseId]);
            if (!$certCheck->fetch()) {
                $code = 'CERT-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
                $insCert = $db->prepare("INSERT INTO certificates (certificate_code, user_id, course_id, issue_date) VALUES (?, ?, ?, datetime('now'))");
                $insCert->execute([$code, $userId, $courseId]);
            }
        }
    }
}
