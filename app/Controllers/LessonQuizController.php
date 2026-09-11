<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

/**
 * Lazy LMS - Lesson Quiz Controller (MCQ Only)
 * Attached directly to a lesson and updates progress upon passing.
 */
class LessonQuizController extends BaseController
{
    public function show(string|int $lessonId): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $lid = HashId::resolveId('lessons', $lessonId);
        if (!$lid) Response::abort(404);

        $lesson = Database::fetchOne("
            SELECT l.*, u.course_id, c.title as course_title, c.public_id as course_public_id
            FROM lessons l
            JOIN units u ON l.unit_id = u.id
            JOIN courses c ON u.course_id = c.id
            WHERE l.id = ?
        ", [$lid]);
        if (!$lesson) Response::abort(404);

        $quiz = Database::fetchOne("SELECT * FROM lesson_quizzes WHERE lesson_id = ? LIMIT 1", [$lid]);

        if (!$quiz) {
            if ($user['role'] === 'student') {
                Session::flash('info', 'No quiz has been added to this lesson.');
                Response::redirect('/lessons/' . ($lesson['public_id'] ?? $lid));
            }
            Response::redirect("/lessons/{$lid}/quiz/create");
        }

        // Load questions with options
        $qSql = "SELECT * FROM lesson_quiz_questions WHERE quiz_id = ?";
        if (!empty($quiz['randomize_questions'])) {
            $qSql .= " ORDER BY RANDOM()";
        } else {
            $qSql .= " ORDER BY sort_order ASC, id ASC";
        }
        $questions = Database::fetchAll($qSql, [$quiz['id']]);

        foreach ($questions as &$q) {
            $optSql = "SELECT * FROM lesson_quiz_options WHERE question_id = ?";
            if (!empty($quiz['randomize_options'])) {
                $optSql .= " ORDER BY RANDOM()";
            } else {
                $optSql .= " ORDER BY sort_order ASC, id ASC";
            }
            $q['options'] = Database::fetchAll($optSql, [$q['id']]);
        }

        // Student attempt history
        $attempts = [];
        $latestAttempt = null;
        if ($user['role'] === 'student') {
            $attempts = Database::fetchAll("
                SELECT * FROM lesson_quiz_attempts 
                WHERE quiz_id = ? AND user_id = ? 
                ORDER BY attempt_number DESC
            ", [$quiz['id'], $user['id']]);
            $latestAttempt = $attempts[0] ?? null;
        }

        $this->render('lessons/quiz', [
            'lesson' => $lesson,
            'quiz' => $quiz,
            'questions' => $questions,
            'attempts' => $attempts,
            'latestAttempt' => $latestAttempt,
            'user' => $user
        ]);
    }

    public function create(string|int $lessonId): void
    {
        $this->requirePermission('lessons.edit');
        $db = Database::getInstance();

        $lid = HashId::resolveId('lessons', $lessonId);
        if (!$lid) Response::abort(404);

        $lesson = Database::fetchOne("
            SELECT l.*, u.course_id, c.title as course_title 
            FROM lessons l 
            JOIN units u ON l.unit_id = u.id 
            JOIN courses c ON u.course_id = c.id 
            WHERE l.id = ?
        ", [$lid]);
        if (!$lesson) Response::abort(404);

        $existingQuiz = Database::fetchOne("SELECT * FROM lesson_quizzes WHERE lesson_id = ? LIMIT 1", [$lid]);
        if ($existingQuiz) {
            Response::redirect('/lessons/' . ($lesson['public_id'] ?? $lid) . '/quiz');
        }

        $this->render('lessons/quiz_create', [
            'lesson' => $lesson,
            'user' => $this->currentUser()
        ]);
    }

    public function store(string|int $lessonId): void
    {
        $this->requirePermission('lessons.edit');
        $this->validateCsrf();

        $lid = HashId::resolveId('lessons', $lessonId);
        if (!$lid) Response::abort(404);

        $lesson = Database::fetchOne("SELECT l.*, u.course_id FROM lessons l JOIN units u ON l.unit_id = u.id WHERE l.id = ?", [$lid]);
        if (!$lesson) Response::abort(404);

        $title = trim((string)Request::post('title', ''));
        $instructions = trim((string)Request::post('instructions', ''));
        $passingScore = max(1, (float)Request::post('passing_score', 50.0));
        $maxAttempts = max(1, (int)Request::post('max_attempts', 3));
        $cooldownMinutes = max(0, (int)Request::post('cooldown_minutes', 0));
        $randQuestions = !empty(Request::post('randomize_questions')) ? 1 : 0;
        $randOptions = !empty(Request::post('randomize_options')) ? 1 : 0;
        $status = Request::post('status', 'published');

        if (empty($title)) {
            Session::flash('error', 'Quiz Title is required.');
            Response::redirect("/lessons/{$lid}/quiz/create");
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('lesson_quizzes');

        $stmt = $db->prepare("
            INSERT INTO lesson_quizzes (
                public_id, lesson_id, course_id, title, instructions, passing_score,
                max_attempts, cooldown_minutes, randomize_questions, randomize_options,
                status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $publicId, $lid, $lesson['course_id'], $title, $instructions, $passingScore,
            $maxAttempts, $cooldownMinutes, $randQuestions, $randOptions, $status
        ]);
        $quizId = $db->lastInsertId();

        // Process MCQ questions
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                if (empty(trim($q['text'] ?? ''))) continue;
                $qPub = HashId::generate('lesson_quiz_questions');
                $qMarks = max(0.5, (float)($q['marks'] ?? 1.0));
                $qStmt = $db->prepare("INSERT INTO lesson_quiz_questions (public_id, quiz_id, question_text, marks) VALUES (?, ?, ?, ?)");
                $qStmt->execute([$qPub, $quizId, trim($q['text']), $qMarks]);
                $qId = $db->lastInsertId();

                $correctIdx = (int)($q['correct'] ?? 0);
                if (!empty($q['options']) && is_array($q['options'])) {
                    foreach ($q['options'] as $oIdx => $optText) {
                        if (trim($optText) === '') continue;
                        $oStmt = $db->prepare("INSERT INTO lesson_quiz_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)");
                        $oStmt->execute([$qId, trim($optText), ($oIdx === $correctIdx) ? 1 : 0, $oIdx]);
                    }
                }
            }
        }

        $this->logAudit('lesson_quiz.create', 'lesson_quizzes', $quizId, ['title' => $title]);
        Session::flash('success', 'Lesson Quiz created successfully.');
        Response::redirect('/lessons/' . ($lesson['public_id'] ?? $lid));
    }

    public function submit(string|int $quizId): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        if ($user['role'] !== 'student') Response::abort(403);

        $qid = HashId::resolveId('lesson_quizzes', $quizId);
        if (!$qid) Response::abort(404);

        $db = Database::getInstance();
        $quiz = Database::fetchOne("SELECT * FROM lesson_quizzes WHERE id = ?", [$qid]);
        if (!$quiz) Response::abort(404);

        // Attempt limits & cooldown check
        $attempts = (int)Database::fetchColumn("SELECT COUNT(*) FROM lesson_quiz_attempts WHERE quiz_id = ? AND user_id = ?", [$qid, $user['id']]);
        if ($attempts >= (int)$quiz['max_attempts']) {
            Session::flash('error', "You have already reached the maximum attempt limit ({$quiz['max_attempts']}) for this quiz.");
            Response::redirect('/lessons/' . $quiz['lesson_id'] . '/quiz');
        }

        if ((int)$quiz['cooldown_minutes'] > 0 && $attempts > 0) {
            $lastAttemptTime = Database::fetchColumn("SELECT started_at FROM lesson_quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1", [$qid, $user['id']]);
            if ($lastAttemptTime) {
                $cooldownEnds = strtotime($lastAttemptTime) + ((int)$quiz['cooldown_minutes'] * 60);
                if (time() < $cooldownEnds) {
                    $minsLeft = ceil(($cooldownEnds - time()) / 60);
                    Session::flash('error', "Quiz cooldown active. Please wait {$minsLeft} more minute(s).");
                    Response::redirect('/lessons/' . $quiz['lesson_id'] . '/quiz');
                }
            }
        }

        $selectedAnswers = Request::post('answers', []);
        $questions = Database::fetchAll("SELECT * FROM lesson_quiz_questions WHERE quiz_id = ?", [$qid]);

        $totalMarks = 0;
        $earnedMarks = 0;

        foreach ($questions as $q) {
            $totalMarks += (float)$q['marks'];
            $optId = (int)($selectedAnswers[$q['id']] ?? 0);
            if ($optId) {
                $check = Database::fetchColumn("SELECT is_correct FROM lesson_quiz_options WHERE id = ? AND question_id = ?", [$optId, $q['id']]);
                if ((int)$check === 1) {
                    $earnedMarks += (float)$q['marks'];
                }
            }
        }

        $scorePercent = ($totalMarks > 0) ? round(($earnedMarks / $totalMarks) * 100, 1) : 0;
        $isPassed = ($scorePercent >= (float)$quiz['passing_score']) ? 1 : 0;
        $attemptNum = $attempts + 1;
        $attPublicId = HashId::generate('lesson_quiz_attempts');

        $ins = $db->prepare("
            INSERT INTO lesson_quiz_attempts (
                public_id, quiz_id, user_id, attempt_number, score, max_score,
                is_passed, started_at, completed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $ins->execute([
            $attPublicId, $qid, $user['id'], $attemptNum, $earnedMarks, $totalMarks, $isPassed
        ]);

        // If passed, automatically update lesson progress!
        if ($isPassed) {
            $progCheck = Database::fetchOne("SELECT * FROM lesson_progress WHERE lesson_id = ? AND user_id = ?", [$quiz['lesson_id'], $user['id']]);
            if (!$progCheck) {
                $db->prepare("INSERT INTO lesson_progress (lesson_id, user_id, is_completed, completed_at, updated_at) VALUES (?, ?, 1, datetime('now'), datetime('now'))")
                   ->execute([$quiz['lesson_id'], $user['id']]);
            } else {
                $db->prepare("UPDATE lesson_progress SET is_completed = 1, completed_at = datetime('now'), updated_at = datetime('now') WHERE id = ?")
                   ->execute([$progCheck['id']]);
            }
            Session::flash('success', "Congratulations! You passed the quiz with {$scorePercent}% ({$earnedMarks}/{$totalMarks} marks) and completed this lesson!");
        } else {
            Session::flash('warning', "You scored {$scorePercent}% ({$earnedMarks}/{$totalMarks} marks). Passing requirement is {$quiz['passing_score']}%. You have " . max(0, (int)$quiz['max_attempts'] - $attemptNum) . " attempts remaining.");
        }

        Response::redirect('/lessons/' . $quiz['lesson_id'] . '/quiz');
    }
}
