<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

/**
 * Lazy LMS - Admin Quick Links & Resource Visibility Controller
 */
class AdminLinkController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $links = Database::fetchAll("SELECT * FROM admin_links ORDER BY sort_order ASC, id DESC");
        $batches = Database::fetchAll("SELECT id, name FROM batches WHERE status = 'active' ORDER BY name ASC");
        $courses = Database::fetchAll("SELECT id, title FROM courses WHERE status != 'archived' ORDER BY title ASC");

        $this->render('links/index', [
            'links' => $links,
            'batches' => $batches,
            'courses' => $courses,
            'user' => $this->currentUser()
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $title = trim((string)Request::post('title', ''));
        $url = trim((string)Request::post('url', ''));
        $icon = trim((string)Request::post('icon', 'bx-link')) ?: 'bx-link';
        $visibility = Request::post('visibility_type', 'all');
        $targetId = !empty(Request::post('target_id')) ? (int)Request::post('target_id') : null;
        $sortOrder = (int)Request::post('sort_order', 0);

        if (empty($title) || empty($url)) {
            Session::flash('error', 'Link Title and URL are required.');
            Response::redirect('/admin/links');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('admin_links');

        $stmt = $db->prepare("
            INSERT INTO admin_links (
                public_id, title, url, icon, visibility_type, target_id, is_visible, sort_order, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, datetime('now'))
        ");
        $stmt->execute([$publicId, $title, $url, $icon, $visibility, $targetId, $sortOrder]);

        Session::flash('success', 'Admin quick link added successfully.');
        Response::redirect('/admin/links');
    }

    public function toggleVisibility(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $lId = HashId::resolveId('admin_links', $id);
        if ($lId) {
            Database::query("UPDATE admin_links SET is_visible = CASE WHEN is_visible = 1 THEN 0 ELSE 1 END WHERE id = ?", [$lId]);
            Session::flash('success', 'Link visibility toggled.');
        }

        Response::redirect('/admin/links');
    }

    public function delete(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $lId = HashId::resolveId('admin_links', $id);
        if ($lId) {
            Database::query("DELETE FROM admin_links WHERE id = ?", [$lId]);
            Session::flash('success', 'Link removed.');
        }

        Response::redirect('/admin/links');
    }
}
