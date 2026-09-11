<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\AuthHelper;
use App\Database;

class StudentDashboardController extends BaseController {
    public function index(Request $request): string {
        $student = AuthHelper::user();
        $studentId = $student['id'];

        // Enrolled batch
        $batch = Database::fetchOne(
            "SELECT b.* 
             FROM batches b 
             JOIN batch_students bs ON b.id = bs.batch_id 
             WHERE bs.student_id = ? AND bs.status = 'active' 
             LIMIT 1",
            [$studentId]
        );

        $courses = [];
        $lastLesson = null;

        if ($batch) {
            // Courses assigned to this batch
            $courses = Database::fetchAll(
                "SELECT c.*, 
                        COALESCE(cp.progress_percentage, 0) as progress_percentage,
                        COALESCE(cp.completed_lessons_count, 0) as completed_lessons_count,
                        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.status = 'published') as total_lessons_count
                 FROM courses c 
                 JOIN batch_courses bc ON c.id = bc.course_id 
                 LEFT JOIN course_progress cp ON c.id = cp.course_id AND cp.user_id = ?
                 WHERE bc.batch_id = ? AND c.status = 'published' 
                 ORDER BY c.id ASC",
                [$studentId, $batch['id']]
            );

            // Last active lesson to continue learning
            $lastLesson = Database::fetchOne(
                "SELECT l.*, c.title as course_title, u.title as unit_title 
                 FROM lessons l 
                 JOIN courses c ON l.course_id = c.id 
                 JOIN units u ON l.unit_id = u.id 
                 LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                 WHERE l.course_id IN (SELECT course_id FROM batch_courses WHERE batch_id = ?) 
                   AND l.status = 'published'
                   AND (lp.is_completed IS NULL OR lp.is_completed = 0)
                 ORDER BY l.sort_order ASC, l.id ASC 
                 LIMIT 1",
                [$studentId, $batch['id']]
            );
        }

        // Upcoming assessments
        $upcomingAssessments = [];
        if ($batch) {
            $upcomingAssessments = Database::fetchAll(
                "SELECT a.*, c.title as course_title 
                 FROM assessments a 
                 JOIN courses c ON a.course_id = c.id 
                 JOIN batch_courses bc ON c.id = bc.course_id 
                 WHERE bc.batch_id = ? AND a.status = 'published' 
                 ORDER BY a.id DESC LIMIT 4",
                [$batch['id']]
            );
        }

        // Earned certificates
        $certificates = Database::fetchAll(
            "SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC",
            [$studentId]
        );

        return $this->render('student/dashboard', [
            'title' => 'Student Portal',
            'batch' => $batch,
            'courses' => $courses,
            'lastLesson' => $lastLesson,
            'upcomingAssessments' => $upcomingAssessments,
            'certificates' => $certificates,
        ]);
    }
}
