<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

/**
 * Lazy LMS - Academic Subjects Controller
 * Implements Batch -> Subject -> Course -> Unit -> Lesson hierarchy.
 */
class SubjectController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $subjects = Database::fetchAll("
            SELECT s.*, b.name as batch_name,
                   (SELECT COUNT(*) FROM courses WHERE subject_id = s.id) as course_count
            FROM subjects s
            LEFT JOIN batches b ON s.batch_id = b.id
            ORDER BY s.name ASC
        ");

        $batches = Database::fetchAll("SELECT id, name FROM batches WHERE status = 'active' ORDER BY name ASC");

        $this->render('subjects/index', [
            'subjects' => $subjects,
            'batches' => $batches,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $name = trim((string)Request::post('name', ''));
        $code = strtoupper(trim((string)Request::post('code', '')));
        $batchId = !empty(Request::post('batch_id')) ? HashId::resolveId('batches', Request::post('batch_id')) : null;
        $description = trim((string)Request::post('description', ''));

        if (empty($name) || empty($code)) {
            Session::flash('error', 'Subject Name and Subject Code are required.');
            Response::redirect('/subjects');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('subjects');

        $stmt = $db->prepare("
            INSERT INTO subjects (public_id, batch_id, name, code, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([$publicId, $batchId, $name, $code, $description]);

        Session::flash('success', 'Subject created successfully.');
        Response::redirect('/subjects');
    }

    public function delete(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $sId = HashId::resolveId('subjects', $id);
        if ($sId) {
            Database::query("DELETE FROM subjects WHERE id = ?", [$sId]);
            Session::flash('success', 'Subject deleted.');
        }

        Response::redirect('/subjects');
    }
}
