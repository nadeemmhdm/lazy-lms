<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Database;
use Throwable;

/**
 * Lazy LMS - Admin Dashboard Controller
 * Aggregates all institutional KPIs: batches, students, teachers, pending assignments,
 * exam grading, upcoming classes, certificates, and real-time security events.
 */
class AdminDashboardController extends BaseController {
    public function index(Request $request): string {
        $db = Database::getInstance();

        $totalStudents = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.slug = 'student' AND u.status = 'active'"
        )['cnt'] ?? 0);

        $totalTeachers = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.slug = 'teacher' AND u.status = 'active'"
        )['cnt'] ?? 0);

        $totalBatches = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM batches WHERE status = 'active'")['cnt'] ?? 0);
        $totalCourses = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM courses WHERE status = 'published'")['cnt'] ?? 0);

        $pendingAssignments = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM assignment_submissions WHERE status = 'submitted'")['cnt'] ?? 0);
        $pendingExamGrading = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM exam_attempts WHERE status = 'submitted'")['cnt'] ?? 0);

        $upcomingClasses = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM scheduled_classes WHERE class_date >= date('now') AND status = 'scheduled'")['cnt'] ?? 0);
        $upcomingExams = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM exams WHERE datetime(start_time) >= datetime('now') AND status = 'published'")['cnt'] ?? 0);

        $totalCertificates = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'issued'")['cnt'] ?? 0);
        $unreadNotifications = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM platform_notifications WHERE status = 'published'")['cnt'] ?? 0);

        // Recent Security Events
        $recentSecurityEvents = [];
        try {
            $recentSecurityEvents = Database::fetchAll("
                SELECT se.*, u.name as user_name, u.email as user_email
                FROM security_events se
                LEFT JOIN users u ON se.user_id = u.id
                ORDER BY se.id DESC LIMIT 6
            ");
        } catch (Throwable $e) {}

        // Recent students
        $recentStudents = Database::fetchAll(
            "SELECT u.*, b.name as batch_name 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             LEFT JOIN batch_students bs ON u.id = bs.student_id AND bs.status = 'active'
             LEFT JOIN batches b ON bs.batch_id = b.id
             WHERE r.slug = 'student' 
             ORDER BY u.id DESC LIMIT 5"
        );

        // Active Batches with student count
        $batches = Database::fetchAll(
            "SELECT b.*, COUNT(bs.student_id) as student_count 
             FROM batches b 
             LEFT JOIN batch_students bs ON b.id = bs.batch_id AND bs.status = 'active'
             WHERE b.status = 'active' 
             GROUP BY b.id 
             ORDER BY b.id DESC LIMIT 5"
        );

        return $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard - Lazy LMS',
            'stats' => [
                'totalStudents' => $totalStudents,
                'totalTeachers' => $totalTeachers,
                'totalBatches' => $totalBatches,
                'totalCourses' => $totalCourses,
                'pendingAssignments' => $pendingAssignments,
                'pendingExamGrading' => $pendingExamGrading,
                'upcomingClasses' => $upcomingClasses,
                'upcomingExams' => $upcomingExams,
                'totalCertificates' => $totalCertificates,
                'unreadNotifications' => $unreadNotifications,
            ],
            'recentStudents' => $recentStudents,
            'recentSecurityEvents' => $recentSecurityEvents,
            'batches' => $batches,
        ]);
    }
}
