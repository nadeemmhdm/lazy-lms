<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class QuestionBankController extends BaseController
{
    public function index(): void
    {
        $this->requirePermission('assessments.view');
        $db = Database::getInstance();
        $user = $this->currentUser();

        $category = Request::get('category', '');
        $type = Request::get('type', '');
        $difficulty = Request::get('difficulty', '');
        $search = Request::get('search', '');

        $sql = "SELECT qb.*, u.name as author_name FROM question_bank qb JOIN users u ON qb.author_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND qb.category = ?";
            $params[] = $category;
        }
        if (!empty($type)) {
            $sql .= " AND qb.type = ?";
            $params[] = $type;
        }
        if (!empty($difficulty)) {
            $sql .= " AND qb.difficulty = ?";
            $params[] = $difficulty;
        }
        if (!empty($search)) {
            $sql .= " AND (qb.question LIKE ? OR qb.tags LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY qb.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        // Get unique categories for filter
        $categories = $db->query("SELECT DISTINCT category FROM question_bank WHERE category IS NOT NULL AND category != ''")->fetchAll(\PDO::FETCH_COLUMN);

        $this->render('question_bank/index', [
            'questions' => $questions,
            'categories' => $categories,
            'filters' => [
                'category' => $category,
                'type' => $type,
                'difficulty' => $difficulty,
                'search' => $search
            ],
            'user' => $user
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('assessments.create');
        $this->render('question_bank/create', ['user' => $this->currentUser()]);
    }

    public function store(): void
    {
        $this->requirePermission('assessments.create');
        $this->validateCsrf();

        $question = trim(Request::post('question', ''));
        $type = Request::post('type', 'single_choice');
        $category = trim(Request::post('category', 'General'));
        $difficulty = Request::post('difficulty', 'medium');
        $tags = trim(Request::post('tags', ''));
        $marks = (float)Request::post('marks', 1.0);
        $explanation = trim(Request::post('explanation', ''));
        $options = Request::post('options', []); // Array of options
        $correctAnswers = Request::post('correct_answer', []); // can be array or string

        if (empty($question)) {
            Session::flash('error', 'Question text is required.');
            Response::redirect('/question-bank/create');
        }

        $correctAnswerJson = '';
        if (is_array($correctAnswers)) {
            $correctAnswerJson = json_encode(array_values($correctAnswers));
        } else {
            $correctAnswerJson = json_encode([trim($correctAnswers)]);
        }

        $db = Database::getInstance();
        $user = $this->currentUser();

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO question_bank (question, type, category, difficulty, tags, marks, explanation, correct_answer, author_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([$question, $type, $category, $difficulty, $tags, $marks, $explanation, $correctAnswerJson, $user['id']]);
            $questionId = $db->lastInsertId();

            if (in_array($type, ['single_choice', 'multiple_choice', 'true_false']) && !empty($options)) {
                $optStmt = $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_num) VALUES (?, ?, ?, ?)");
                $order = 1;
                foreach ($options as $idx => $optText) {
                    $optText = trim($optText);
                    if ($optText === '') continue;
                    
                    $isCorrect = 0;
                    if (is_array($correctAnswers)) {
                        $isCorrect = in_array((string)$idx, $correctAnswers) ? 1 : 0;
                    } else {
                        $isCorrect = ((string)$idx === (string)$correctAnswers) ? 1 : 0;
                    }
                    $optStmt->execute([$questionId, $optText, $isCorrect, $order++]);
                }
            }

            $db->commit();
            Session::flash('success', 'Question created in question bank.');
            Response::redirect('/question-bank');
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Error creating question: ' . $e->getMessage());
            Response::redirect('/question-bank/create');
        }
    }

    public function delete(int $id): void
    {
        $this->requirePermission('assessments.delete');
        $this->validateCsrf();

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM question_bank WHERE id = ?");
        $stmt->execute([$id]);

        Session::flash('success', 'Question deleted from question bank.');
        Response::redirect('/question-bank');
    }
}
