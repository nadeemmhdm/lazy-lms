<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;

class ReportController extends BaseController
{
    public function index(): void
    {
        $this->requirePermission('reports.view');
        $db = Database::getInstance();
        $user = $this->currentUser();

        // High-level report metrics
        $batchCount = (int)$db->query("SELECT COUNT(*) FROM batches WHERE status = 'active'")->fetchColumn();
        $courseCount = (int)$db->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn();
        $studentCount = (int)$db->query("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name = 'student' AND u.status = 'active'")->fetchColumn();
        $certCount = (int)$db->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

        $batches = $db->query("SELECT id, name, code FROM batches ORDER BY name ASC")->fetchAll();
        $courses = $db->query("SELECT id, title, code FROM courses ORDER BY title ASC")->fetchAll();

        $this->render('reports/index', [
            'metrics' => [
                'batches' => $batchCount,
                'courses' => $courseCount,
                'students' => $studentCount,
                'certificates' => $certCount
            ],
            'batches' => $batches,
            'courses' => $courses,
            'user' => $user
        ]);
    }

    public function exportStudents(): void
    {
        $this->requirePermission('reports.view');
        $db = Database::getInstance();

        $stmt = $db->query("
            SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login,
                   b.name as batch_name,
                   (SELECT COUNT(*) FROM course_progress WHERE user_id = u.id AND is_completed = 1) as completed_courses
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active'
            LEFT JOIN batches b ON bs.batch_id = b.id
            WHERE r.name = 'student'
            ORDER BY u.name ASC
        ");
        $students = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=students_report_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Email', 'Status', 'Batch', 'Completed Courses', 'Registered Date', 'Last Login']);
        foreach ($students as $s) {
            fputcsv($out, [$s['id'], $s['name'], $s['email'], $s['status'], $s['batch_name'] ?? 'None', $s['completed_courses'], $s['created_at'], $s['last_login']]);
        }
        fclose($out);
        exit;
    }

    public function exportCourses(): void
    {
        $this->requirePermission('reports.view');
        $db = Database::getInstance();

        $stmt = $db->query("
            SELECT c.id, c.code, c.title, c.status,
                   (SELECT COUNT(*) FROM course_units WHERE course_id = c.id) as unit_count,
                   (SELECT COUNT(*) FROM course_progress WHERE course_id = c.id AND is_completed = 1) as completions,
                   (SELECT COUNT(*) FROM batch_courses WHERE course_id = c.id) as batch_count
            FROM courses c
            ORDER BY c.title ASC
        ");
        $courses = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=courses_report_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Code', 'Title', 'Status', 'Units', 'Assigned Batches', 'Completions']);
        foreach ($courses as $c) {
            fputcsv($out, [$c['id'], $c['code'], $c['title'], $c['status'], $c['unit_count'], $c['batch_count'], $c['completions']]);
        }
        fclose($out);
        exit;
    }
}
