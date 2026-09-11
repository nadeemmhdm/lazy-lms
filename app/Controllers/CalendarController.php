<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\Response;
use Throwable;

/**
 * Lazy LMS - Advanced Academic Calendar Controller
 * Aggregates Scheduled Classes, Assignments, Exams, Quizzes, and Announcements.
 */
class CalendarController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $events = [];

        // 1. Scheduled Live Classes
        try {
            $clSql = "
                SELECT sc.*, c.title as course_title, b.name as batch_name, u.name as teacher_name
                FROM scheduled_classes sc
                JOIN courses c ON sc.course_id = c.id
                JOIN batches b ON sc.batch_id = b.id
                LEFT JOIN users u ON sc.teacher_id = u.id
                WHERE sc.status != 'cancelled'
            ";
            $clParams = [];
            if ($user['role'] === 'student') {
                $clSql .= " AND sc.batch_id IN (SELECT batch_id FROM batch_students WHERE student_id = ? AND status = 'active')";
                $clParams[] = $user['id'];
            }
            $classes = Database::fetchAll($clSql, $clParams);
            foreach ($classes as $c) {
                $events[] = [
                    'id' => 'class_' . $c['id'],
                    'title' => $c['title'],
                    'date' => $c['class_date'],
                    'time' => date('h:i A', strtotime($c['start_time'])),
                    'end_time' => date('h:i A', strtotime($c['end_time'])),
                    'type' => 'class',
                    'type_label' => 'Live Class',
                    'badge' => 'badge-primary',
                    'course' => $c['course_title'],
                    'batch' => $c['batch_name'],
                    'teacher' => $c['teacher_name'] ?? 'Instructor',
                    'description' => $c['description'] ?? '',
                    'url' => !empty($c['meeting_url']) ? $c['meeting_url'] : '/classes'
                ];
            }
        } catch (Throwable $e) {}

        // 2. Assignments Deadlines
        try {
            $asgSql = "
                SELECT a.*, c.title as course_title
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                WHERE a.due_date IS NOT NULL
            ";
            $asgParams = [];
            if ($user['role'] === 'student') {
                $asgSql .= " AND a.course_id IN (SELECT course_id FROM batch_courses bc JOIN batch_students bs ON bc.batch_id = bs.batch_id WHERE bs.student_id = ? AND bs.status = 'active')";
                $asgParams[] = $user['id'];
            }
            $assignments = Database::fetchAll($asgSql, $asgParams);
            foreach ($assignments as $a) {
                $events[] = [
                    'id' => 'asg_' . $a['id'],
                    'title' => 'Assignment Due: ' . $a['title'],
                    'date' => substr($a['due_date'], 0, 10),
                    'time' => date('h:i A', strtotime($a['due_date'])),
                    'type' => 'assignment',
                    'type_label' => 'Assignment',
                    'badge' => 'badge-danger',
                    'course' => $a['course_title'],
                    'batch' => 'All Enrolled',
                    'teacher' => '',
                    'description' => $a['instructions'] ?? '',
                    'url' => '/assignments/' . ($a['public_id'] ?: $a['id'])
                ];
            }
        } catch (Throwable $e) {}

        // 3. Examinations
        try {
            $exSql = "
                SELECT e.*, c.title as course_title, b.name as batch_name
                FROM exams e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN batches b ON e.batch_id = b.id
                WHERE e.status = 'published'
            ";
            $exParams = [];
            if ($user['role'] === 'student') {
                $exSql .= " AND e.course_id IN (SELECT course_id FROM batch_courses bc JOIN batch_students bs ON bc.batch_id = bs.batch_id WHERE bs.student_id = ? AND bs.status = 'active')";
                $exParams[] = $user['id'];
            }
            $exams = Database::fetchAll($exSql, $exParams);
            foreach ($exams as $ex) {
                $events[] = [
                    'id' => 'exam_' . $ex['id'],
                    'title' => 'Exam: ' . $ex['title'],
                    'date' => substr($ex['start_time'], 0, 10),
                    'time' => date('h:i A', strtotime($ex['start_time'])),
                    'type' => 'exam',
                    'type_label' => 'Examination',
                    'badge' => 'badge-warning',
                    'course' => $ex['course_title'],
                    'batch' => $ex['batch_name'] ?? 'All Batches',
                    'teacher' => '',
                    'description' => $ex['instructions'] ?? '',
                    'url' => '/exams/' . ($ex['public_id'] ?: $ex['id'])
                ];
            }
        } catch (Throwable $e) {}

        // 4. Announcements
        try {
            $announcements = Database::fetchAll("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");
            foreach ($announcements as $an) {
                $events[] = [
                    'id' => 'ann_' . $an['id'],
                    'title' => 'Announcement: ' . $an['title'],
                    'date' => substr($an['created_at'], 0, 10),
                    'time' => date('h:i A', strtotime($an['created_at'])),
                    'type' => 'announcement',
                    'type_label' => 'Announcement',
                    'badge' => 'badge-info',
                    'course' => 'Platform',
                    'batch' => 'General',
                    'teacher' => '',
                    'description' => $an['content'] ?? '',
                    'url' => '/announcements'
                ];
            }
        } catch (Throwable $e) {}

        // Sort events by date ascending
        usort($events, fn($a, $b) => strcmp($a['date'] . ' ' . $a['time'], $b['date'] . ' ' . $b['time']));

        $this->render('calendar/index', [
            'events' => $events,
            'user' => $user
        ]);
    }
}
