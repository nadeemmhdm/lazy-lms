<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\AuthHelper;
use App\Database;

class TeacherDashboardController extends BaseController {
    public function index(Request $request): string {
        $teacher = AuthHelper::user();
        $teacherId = $teacher['id'];

        // Assigned courses
        $courses = Database::fetchAll(
            "SELECT c.*, COUNT(DISTINCT cu.id) as unit_count 
             FROM courses c 
             JOIN course_teachers ct ON c.id = ct.course_id 
             LEFT JOIN units cu ON c.id = cu.course_id 
             WHERE ct.teacher_id = ? 
             GROUP BY c.id 
             ORDER BY c.id DESC",
            [$teacherId]
        );

        // Assigned batches
        $batches = Database::fetchAll(
            "SELECT b.*, COUNT(DISTINCT bs.student_id) as student_count 
             FROM batches b 
             JOIN batch_teachers bt ON b.id = bt.batch_id 
             LEFT JOIN batch_students bs ON b.id = bs.batch_id AND bs.status = 'active'
             WHERE bt.teacher_id = ? 
             GROUP BY b.id 
             ORDER BY b.id DESC",
            [$teacherId]
        );

        // Compute total unique students under this teacher
        $totalStudents = (int)(Database::fetchOne(
            "SELECT COUNT(DISTINCT bs.student_id) as cnt 
             FROM batch_students bs 
             JOIN batch_teachers bt ON bs.batch_id = bt.batch_id 
             WHERE bt.teacher_id = ? AND bs.status = 'active'",
            [$teacherId]
        )['cnt'] ?? 0);

        // Pending assessment attempts needing grading
        $pendingAssessments = Database::fetchAll(
            "SELECT aa.*, a.title as assessment_title, u.name as student_name 
             FROM assessment_attempts aa 
             JOIN assessments a ON aa.assessment_id = a.id 
             JOIN course_teachers ct ON a.course_id = ct.course_id 
             JOIN users u ON aa.user_id = u.id 
             WHERE ct.teacher_id = ? AND aa.status = 'submitted' 
             ORDER BY aa.submitted_at ASC LIMIT 6",
            [$teacherId]
        );

        // Pending assignment submissions needing grading
        $pendingAssignments = Database::fetchAll(
            "SELECT asub.*, a.title as assignment_title, u.name as student_name 
             FROM assignment_submissions asub 
             JOIN assignments a ON asub.assignment_id = a.id 
             JOIN course_teachers ct ON a.course_id = ct.course_id 
             JOIN users u ON asub.user_id = u.id 
             WHERE ct.teacher_id = ? AND asub.status = 'submitted' 
             ORDER BY asub.submitted_at ASC LIMIT 6",
            [$teacherId]
        );

        $pendingGradingCount = count($pendingAssessments) + count($pendingAssignments);

        return $this->render('teacher/dashboard', [
            'title' => 'Teacher Portal',
            'courses' => $courses,
            'batches' => $batches,
            'totalStudents' => $totalStudents,
            'pendingAssessments' => $pendingAssessments,
            'pendingAssignments' => $pendingAssignments,
            'pendingGradingCount' => $pendingGradingCount,
        ]);
    }
}
