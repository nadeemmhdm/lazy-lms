<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;

class SearchController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $q = trim(Request::get('q', ''));
        $user = $this->currentUser();
        $db = Database::getInstance();

        $results = [
            'students' => [],
            'courses' => [],
            'batches' => [],
            'lessons' => []
        ];

        if (strlen($q) >= 2) {
            $like = "%$q%";

            // Courses
            $cStmt = $db->prepare("SELECT id, title, code, description FROM courses WHERE title LIKE ? OR code LIKE ? LIMIT 10");
            $cStmt->execute([$like, $like]);
            $results['courses'] = $cStmt->fetchAll();

            // Batches
            $bStmt = $db->prepare("SELECT id, name, code FROM batches WHERE name LIKE ? OR code LIKE ? LIMIT 10");
            $bStmt->execute([$like, $like]);
            $results['batches'] = $bStmt->fetchAll();

            // Students (Admin / Teacher only)
            if ($user['role'] !== 'student') {
                $sStmt = $db->prepare("
                    SELECT u.id, u.name, u.email 
                    FROM users u
                    JOIN user_roles ur ON u.id = ur.user_id
                    JOIN roles r ON ur.role_id = r.id
                    WHERE r.name = 'student' AND (u.name LIKE ? OR u.email LIKE ?)
                    LIMIT 10
                ");
                $sStmt->execute([$like, $like]);
                $results['students'] = $sStmt->fetchAll();
            }

            // Lessons
            $lStmt = $db->prepare("SELECT id, unit_id, title FROM lessons WHERE title LIKE ? LIMIT 10");
            $lStmt->execute([$like]);
            $results['lessons'] = $lStmt->fetchAll();
        }

        if (Request::isAjax()) {
            Response::json($results);
            return;
        }

        $this->render('search/index', [
            'query' => $q,
            'results' => $results,
            'user' => $user
        ]);
    }
}
