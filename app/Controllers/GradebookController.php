<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;

class GradebookController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            // Student personal gradebook view
            $stmt = $db->prepare("
                SELECT c.id as course_id, c.title as course_title, c.code as course_code,
                       cp.progress_percentage, cp.is_completed, cp.completed_at
                FROM courses c
                JOIN batch_courses bc ON c.id = bc.course_id
                JOIN batch_students bs ON bc.batch_id = bs.batch_id
                LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ?
                WHERE bs.student_id = ? AND bs.status = 'active'
                ORDER BY c.title ASC
            ");
            $stmt->execute([$user['id'], $user['id']]);
            $courses = $stmt->fetchAll();

            foreach ($courses as &$c) {
                // Get assessment attempts
                $aStmt = $db->prepare("
                    SELECT a.title, att.score, att.max_score, att.completed_at
                    FROM assessment_attempts att
                    JOIN assessments a ON att.assessment_id = a.id
                    WHERE a.course_id = ? AND att.user_id = ?
                    ORDER BY att.completed_at DESC
                ");
                $aStmt->execute([$c['course_id'], $user['id']]);
                $c['assessments'] = $aStmt->fetchAll();

                // Get assignment submissions
                $subStmt = $db->prepare("
                    SELECT asg.title, s.grade, asg.max_marks, s.graded_at, s.feedback
                    FROM assignment_submissions s
                    JOIN assignments asg ON s.assignment_id = asg.id
                    WHERE asg.course_id = ? AND s.user_id = ? AND s.status = 'graded'
                ");
                $subStmt->execute([$c['course_id'], $user['id']]);
                $c['assignments'] = $subStmt->fetchAll();
            }

            $this->render('gradebook/student_view', [
                'courses' => $courses,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher gradebook
        $this->requirePermission('reports.view');

        $batchId = Request::get('batch_id') ? (int)Request::get('batch_id') : null;
        $courseId = Request::get('course_id') ? (int)Request::get('course_id') : null;

        $batches = $db->query("SELECT id, name, code FROM batches WHERE status != 'archived' ORDER BY name ASC")->fetchAll();
        $courses = $db->query("SELECT id, title, code FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();

        $students = [];
        $assessments = [];
        $assignments = [];

        if ($courseId) {
            // Get all students enrolled in this course via batches
            $stStmt = $db->prepare("
                SELECT DISTINCT u.id, u.name, u.email, bs.batch_id, b.name as batch_name
                FROM users u
                JOIN batch_students bs ON u.id = bs.student_id
                JOIN batch_courses bc ON bs.batch_id = bc.batch_id
                JOIN batches b ON bs.batch_id = b.id
                WHERE bc.course_id = ? AND bs.status = 'active'
                " . ($batchId ? "AND bs.batch_id = $batchId" : "") . "
                ORDER BY u.name ASC
            ");
            $stStmt->execute([$courseId]);
            $students = $stStmt->fetchAll();

            $asStmt = $db->prepare("SELECT id, title, total_marks FROM assessments WHERE course_id = ? ORDER BY id ASC");
            $asStmt->execute([$courseId]);
            $assessments = $asStmt->fetchAll();

            $asgStmt = $db->prepare("SELECT id, title, max_marks FROM assignments WHERE course_id = ? ORDER BY id ASC");
            $asgStmt->execute([$courseId]);
            $assignments = $asgStmt->fetchAll();

            // Populate grades for each student
            foreach ($students as &$stu) {
                $stu['assessment_grades'] = [];
                foreach ($assessments as $ass) {
                    $attStmt = $db->prepare("SELECT MAX(score) FROM assessment_attempts WHERE assessment_id = ? AND user_id = ?");
                    $attStmt->execute([$ass['id'], $stu['id']]);
                    $stu['assessment_grades'][$ass['id']] = $attStmt->fetchColumn();
                }

                $stu['assignment_grades'] = [];
                foreach ($assignments as $asg) {
                    $subStmt = $db->prepare("SELECT grade FROM assignment_submissions WHERE assignment_id = ? AND user_id = ? AND status = 'graded'");
                    $subStmt->execute([$asg['id'], $stu['id']]);
                    $stu['assignment_grades'][$asg['id']] = $subStmt->fetchColumn();
                }
            }
        }

        if (Request::get('export') === 'csv' && $courseId) {
            $this->exportCsv($students, $assessments, $assignments);
            return;
        }

        $this->render('gradebook/index', [
            'batches' => $batches,
            'courses' => $courses,
            'selectedBatch' => $batchId,
            'selectedCourse' => $courseId,
            'students' => $students,
            'assessments' => $assessments,
            'assignments' => $assignments,
            'user' => $user
        ]);
    }

    private function exportCsv(array $students, array $assessments, array $assignments): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gradebook_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        $header = ['Student ID', 'Student Name', 'Email', 'Batch'];
        foreach ($assessments as $a) {
            $header[] = $a['title'] . ' (/' . $a['total_marks'] . ')';
        }
        foreach ($assignments as $asg) {
            $header[] = $asg['title'] . ' (/' . $asg['max_marks'] . ')';
        }
        fputcsv($out, $header);

        foreach ($students as $s) {
            $row = [$s['id'], $s['name'], $s['email'], $s['batch_name']];
            foreach ($assessments as $a) {
                $row[] = $s['assessment_grades'][$a['id']] ?? '-';
            }
            foreach ($assignments as $asg) {
                $row[] = $s['assignment_grades'][$asg['id']] ?? '-';
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
