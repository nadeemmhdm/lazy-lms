<?php

/**
 * Open LMS Routes Definition
 * All clean HTTP routes mapped to respective Controllers and Methods
 */

use App\Controllers\AdminDashboardController;
use App\Controllers\AnnouncementController;
use App\Controllers\ApiController;
use App\Controllers\AssessmentController;
use App\Controllers\AssignmentController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\BatchController;
use App\Controllers\CalendarController;
use App\Controllers\CertificateController;
use App\Controllers\CourseController;
use App\Controllers\DiscussionController;
use App\Controllers\FileDownloadController;
use App\Controllers\GradebookController;
use App\Controllers\InstallController;
use App\Controllers\LessonController;
use App\Controllers\NotificationController;
use App\Controllers\QuestionBankController;
use App\Controllers\ReportController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\StudentController;
use App\Controllers\StudentDashboardController;
use App\Controllers\TeacherController;
use App\Controllers\TeacherDashboardController;
use App\Controllers\LessonQuizController;
use App\Controllers\ExamController;
use App\Controllers\PlatformNotificationController;
use App\Controllers\ScheduledClassController;
use App\Controllers\AdminLinkController;
use App\Controllers\SubjectController;

/** @var \App\Router $router */

// --- First Run Setup Wizard ---
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install/step2', [InstallController::class, 'step2']);
$router->post('/install/finish', [InstallController::class, 'finish']);
$router->post('/install/test-smtp', [InstallController::class, 'testSmtp']);

// --- Public Authentication ---
$router->get('/', [AuthController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/login/2fa', [AuthController::class, 'twoFactorForm']);
$router->post('/login/2fa', [AuthController::class, 'twoFactorVerify']);

$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotPasswordCheck']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// --- Role Dashboards ---
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'index']);
$router->get('/teacher', [TeacherDashboardController::class, 'index']);
$router->get('/teacher/dashboard', [TeacherDashboardController::class, 'index']);
$router->get('/student', [StudentDashboardController::class, 'index']);
$router->get('/student/dashboard', [StudentDashboardController::class, 'index']);

// --- Batches Management ---
$router->get('/batches', [BatchController::class, 'index']);
$router->get('/batches/create', [BatchController::class, 'create']);
$router->post('/batches', [BatchController::class, 'store']);
$router->get('/batches/{id}', [BatchController::class, 'show']);
$router->get('/batches/{id}/edit', [BatchController::class, 'edit']);
$router->post('/batches/{id}', [BatchController::class, 'update']);
$router->post('/batches/{id}/archive', [BatchController::class, 'archive']);
$router->post('/batches/{id}/restore', [BatchController::class, 'restore']);
$router->post('/batches/{id}/delete', [BatchController::class, 'delete']);
$router->post('/batches/{id}/add-student', [BatchController::class, 'addStudent']);
$router->post('/batches/{id}/remove-student', [BatchController::class, 'removeStudent']);
$router->post('/batches/{id}/assign-courses', [BatchController::class, 'assignCourses']);
$router->get('/batches/{id}/change-student', [BatchController::class, 'changeStudentBatchForm']);
$router->post('/batches/{id}/change-student', [BatchController::class, 'changeStudentBatch']);

// --- Student Management ---
$router->get('/students', [StudentController::class, 'index']);
$router->get('/students/create', [StudentController::class, 'create']);
$router->post('/students', [StudentController::class, 'store']);
$router->get('/students/{id}', [StudentController::class, 'show']);
$router->get('/students/{id}/edit', [StudentController::class, 'edit']);
$router->post('/students/{id}', [StudentController::class, 'update']);
$router->post('/students/{id}/archive', [StudentController::class, 'archive']);
$router->post('/students/{id}/restore', [StudentController::class, 'restore']);
$router->post('/students/{id}/delete', [StudentController::class, 'delete']);
$router->post('/students/{id}/reset-password', [StudentController::class, 'resetPassword']);

// --- Teacher Management ---
$router->get('/teachers', [TeacherController::class, 'index']);
$router->get('/teachers/create', [TeacherController::class, 'create']);
$router->post('/teachers', [TeacherController::class, 'store']);
$router->get('/teachers/{id}', [TeacherController::class, 'show']);
$router->get('/teachers/{id}/edit', [TeacherController::class, 'edit']);
$router->post('/teachers/{id}', [TeacherController::class, 'update']);
$router->post('/teachers/{id}/archive', [TeacherController::class, 'archive']);
$router->post('/teachers/{id}/restore', [TeacherController::class, 'restore']);
$router->post('/teachers/{id}/delete', [TeacherController::class, 'delete']);

// --- Courses ---
$router->get('/courses', [CourseController::class, 'index']);
$router->get('/courses/create', [CourseController::class, 'create']);
$router->post('/courses', [CourseController::class, 'store']);
$router->get('/courses/{id}', [CourseController::class, 'show']);
$router->get('/courses/{id}/edit', [CourseController::class, 'edit']);
$router->post('/courses/{id}', [CourseController::class, 'update']);
$router->post('/courses/{id}/duplicate', [CourseController::class, 'duplicate']);
$router->post('/courses/{id}/publish', [CourseController::class, 'togglePublish']);
$router->post('/courses/{id}/archive', [CourseController::class, 'archive']);
$router->post('/courses/{id}/delete', [CourseController::class, 'delete']);
$router->get('/courses/{id}/builder', [CourseController::class, 'builder']);

// Course Units
$router->post('/courses/{id}/units', [CourseController::class, 'storeUnit']);
$router->post('/courses/{id}/units/{uid}/edit', [CourseController::class, 'updateUnit']);
$router->post('/courses/{id}/units/{uid}/delete', [CourseController::class, 'deleteUnit']);

// --- Lessons ---
$router->get('/courses/{cid}/units/{uid}/lessons/create', [LessonController::class, 'create']);
$router->post('/courses/{cid}/units/{uid}/lessons', [LessonController::class, 'store']);
$router->get('/lessons/{id}', [LessonController::class, 'show']);
$router->get('/lessons/{id}/edit', [LessonController::class, 'edit']);
$router->post('/lessons/{id}', [LessonController::class, 'update']);
$router->post('/lessons/{id}/delete', [LessonController::class, 'delete']);
$router->post('/lessons/{id}/complete', [LessonController::class, 'markComplete']);
$router->post('/lessons/{id}/video-progress', [LessonController::class, 'updateVideoProgress']);
$router->post('/lessons/{id}/materials', [LessonController::class, 'uploadMaterial']);
$router->post('/lessons/{id}/materials/{mid}/delete', [LessonController::class, 'deleteMaterial']);

// --- Discussions ---
$router->post('/lessons/{id}/discussions', [DiscussionController::class, 'store']);
$router->post('/discussions/{id}/reply', [DiscussionController::class, 'reply']);
$router->post('/discussions/{id}/pin', [DiscussionController::class, 'pin']);
$router->post('/discussions/{id}/lock', [DiscussionController::class, 'lock']);
$router->post('/discussions/{id}/delete', [DiscussionController::class, 'delete']);
$router->post('/discussions/{id}/report', [DiscussionController::class, 'report']);

// --- Question Bank ---
$router->get('/question-bank', [QuestionBankController::class, 'index']);
$router->get('/question-bank/create', [QuestionBankController::class, 'create']);
$router->post('/question-bank', [QuestionBankController::class, 'store']);
$router->post('/question-bank/{id}/delete', [QuestionBankController::class, 'delete']);

// --- Assessments ---
$router->get('/assessments', [AssessmentController::class, 'index']);
$router->get('/assessments/create', [AssessmentController::class, 'create']);
$router->post('/assessments', [AssessmentController::class, 'store']);
$router->get('/assessments/{id}/builder', [AssessmentController::class, 'builder']);
$router->post('/assessments/{id}/questions', [AssessmentController::class, 'addQuestion']);
$router->post('/assessments/{id}/questions/{qid}/delete', [AssessmentController::class, 'removeQuestion']);
$router->get('/assessments/{id}/take', [AssessmentController::class, 'take']);
$router->post('/assessments/{id}/submit', [AssessmentController::class, 'submit']);
$router->get('/assessments/results/{attemptId}', [AssessmentController::class, 'results']);

// --- Lesson Quizzes ---
$router->get('/lessons/{id}/quiz', [LessonQuizController::class, 'show']);
$router->get('/lessons/{id}/quiz/create', [LessonQuizController::class, 'create']);
$router->post('/lessons/{id}/quiz/store', [LessonQuizController::class, 'store']);
$router->post('/lesson-quizzes/{id}/submit', [LessonQuizController::class, 'submit']);

// --- Assignments ---
$router->get('/assignments', [AssignmentController::class, 'index']);
$router->get('/assignments/create', [AssignmentController::class, 'create']);
$router->post('/assignments', [AssignmentController::class, 'store']);
$router->get('/assignments/{id}', [AssignmentController::class, 'show']);
$router->post('/assignments/{id}/submit', [AssignmentController::class, 'submit']);
$router->post('/assignments/submissions/{id}/grade', [AssignmentController::class, 'grade']);
$router->get('/submissions/download/{id}', [AssignmentController::class, 'downloadSubmissionFile']);

// --- Examinations ---
$router->get('/exams', [ExamController::class, 'index']);
$router->get('/exams/create', [ExamController::class, 'create']);
$router->post('/exams', [ExamController::class, 'store']);
$router->get('/exams/{id}', [ExamController::class, 'show']);
$router->get('/exams/{id}/take', [ExamController::class, 'take']);
$router->post('/exams/{id}/auto-save', [ExamController::class, 'autoSave']);
$router->post('/exams/{id}/submit', [ExamController::class, 'submit']);
$router->get('/exams/{id}/attempts/{attemptId}/grade', [ExamController::class, 'gradeAttempt']);
$router->post('/exams/{id}/attempts/{attemptId}/save-grade', [ExamController::class, 'saveGrade']);
$router->get('/exams/results/{attemptId}', [ExamController::class, 'results']);
$router->get('/exams/submissions/download/{id}', [ExamController::class, 'downloadAnswerFile']);

// --- Subjects ---
$router->get('/subjects', [SubjectController::class, 'index']);
$router->post('/subjects', [SubjectController::class, 'store']);
$router->post('/subjects/{id}/delete', [SubjectController::class, 'delete']);

// --- Scheduled Live Classes ---
$router->get('/classes', [ScheduledClassController::class, 'index']);
$router->get('/classes/create', [ScheduledClassController::class, 'create']);
$router->post('/classes', [ScheduledClassController::class, 'store']);
$router->post('/classes/{id}/cancel', [ScheduledClassController::class, 'cancel']);

// --- Gradebook ---
$router->get('/gradebook', [GradebookController::class, 'index']);

// --- Attendance ---
$router->get('/attendance', [AttendanceController::class, 'index']);
$router->get('/attendance/create', [AttendanceController::class, 'create']);
$router->post('/attendance', [AttendanceController::class, 'store']);
$router->get('/attendance/{id}', [AttendanceController::class, 'show']);
$router->post('/attendance/{id}', [AttendanceController::class, 'update']);

// --- Certificates ---
$router->get('/certificates', [CertificateController::class, 'index']);
$router->post('/certificates', [CertificateController::class, 'store']);
$router->post('/certificates/issue-batch', [CertificateController::class, 'issueBatch']);
$router->post('/certificates/{id}/revoke', [CertificateController::class, 'revoke']);
$router->post('/certificates/{id}/restore', [CertificateController::class, 'restore']);
$router->get('/certificates/{id}', [CertificateController::class, 'show']);
$router->get('/certificate/verify/{code}', [CertificateController::class, 'verify']);

// --- Announcements & Platform Notifications ---
$router->get('/announcements', [AnnouncementController::class, 'index']);
$router->post('/announcements', [AnnouncementController::class, 'store']);
$router->post('/announcements/{id}/delete', [AnnouncementController::class, 'delete']);

$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
$router->post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
$router->get('/api/notifications/unread', [NotificationController::class, 'getUnread']);

$router->get('/platform-notifications', [PlatformNotificationController::class, 'index']);
$router->get('/platform-notifications/create', [PlatformNotificationController::class, 'create']);
$router->post('/platform-notifications', [PlatformNotificationController::class, 'store']);
$router->post('/platform-notifications/{id}/read', [PlatformNotificationController::class, 'markRead']);
$router->post('/platform-notifications/mark-all-read', [PlatformNotificationController::class, 'markAllRead']);
$router->get('/api/platform-notifications/unread', [PlatformNotificationController::class, 'getUnreadCount']);

// --- Admin Quick Links ---
$router->get('/admin/links', [AdminLinkController::class, 'index']);
$router->post('/admin/links', [AdminLinkController::class, 'store']);
$router->post('/admin/links/{id}/toggle', [AdminLinkController::class, 'toggleVisibility']);
$router->post('/admin/links/{id}/delete', [AdminLinkController::class, 'delete']);

// --- Calendar ---
$router->get('/calendar', [CalendarController::class, 'index']);

// --- Reports ---
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/students/export', [ReportController::class, 'exportStudents']);
$router->get('/reports/courses/export', [ReportController::class, 'exportCourses']);

// --- Global Search ---
$router->get('/search', [SearchController::class, 'index']);

// --- Settings & Administration ---
$router->get('/settings/branding', [SettingsController::class, 'branding']);
$router->post('/settings/branding', [SettingsController::class, 'updateBranding']);
$router->get('/settings/smtp', [SettingsController::class, 'smtp']);
$router->post('/settings/smtp', [SettingsController::class, 'updateSmtp']);
$router->post('/settings/smtp/test', [SettingsController::class, 'testSmtp']);
$router->get('/settings/email-templates', [SettingsController::class, 'emailTemplates']);
$router->post('/settings/email-templates/{id}', [SettingsController::class, 'updateEmailTemplate']);
$router->get('/settings/maintenance', [SettingsController::class, 'maintenance']);
$router->post('/settings/maintenance/toggle', [SettingsController::class, 'toggleMaintenance']);
$router->get('/settings/health', [SettingsController::class, 'systemHealth']);
$router->get('/settings/backups', [SettingsController::class, 'backups']);
$router->post('/settings/backups/create', [SettingsController::class, 'createBackup']);
$router->get('/settings/backups/download/{file}', [SettingsController::class, 'downloadBackup']);
$router->get('/settings/audit-logs', [SettingsController::class, 'auditLogs']);
$router->get('/settings/sessions', [SettingsController::class, 'sessions']);
$router->post('/settings/sessions/{id}/destroy', [SettingsController::class, 'destroySession']);

// --- Protected File Downloads ---
$router->get('/download/file/{id}', [FileDownloadController::class, 'download']);

// --- REST API v1 ---
// --- Admin Aliases for seamless linking ---
$router->get('/admin/batches', [BatchController::class, 'index']);
$router->get('/admin/batches/create', [BatchController::class, 'create']);
$router->get('/admin/batches/change-student', [BatchController::class, 'changeStudentBatchForm']);
$router->get('/admin/batches/{id}', [BatchController::class, 'show']);
$router->get('/admin/batches/{id}/edit', [BatchController::class, 'edit']);

$router->get('/admin/students', [StudentController::class, 'index']);
$router->get('/admin/students/create', [StudentController::class, 'create']);
$router->get('/admin/students/{id}', [StudentController::class, 'show']);
$router->get('/admin/students/{id}/edit', [StudentController::class, 'edit']);

$router->get('/admin/teachers', [TeacherController::class, 'index']);
$router->get('/admin/teachers/create', [TeacherController::class, 'create']);
$router->get('/admin/teachers/{id}', [TeacherController::class, 'show']);
$router->get('/admin/teachers/{id}/edit', [TeacherController::class, 'edit']);

$router->get('/admin/courses', [CourseController::class, 'index']);
$router->get('/admin/courses/create', [CourseController::class, 'create']);
$router->get('/admin/courses/{id}', [CourseController::class, 'show']);
$router->get('/admin/courses/{id}/edit', [CourseController::class, 'edit']);

$router->get('/admin/assessments', [AssessmentController::class, 'index']);
$router->get('/admin/assignments', [AssignmentController::class, 'index']);
$router->get('/admin/gradebook', [GradebookController::class, 'index']);
$router->get('/admin/attendance', [AttendanceController::class, 'index']);
$router->get('/admin/certificates', [CertificateController::class, 'index']);
$router->get('/admin/reports', [ReportController::class, 'index']);
$router->get('/admin/health', [SettingsController::class, 'systemHealth']);
$router->get('/admin/backups', [SettingsController::class, 'backups']);
$router->get('/admin/audit-logs', [SettingsController::class, 'auditLogs']);
$router->get('/admin/sessions', [SettingsController::class, 'sessions']);
$router->get('/admin/settings/branding', [SettingsController::class, 'branding']);
$router->get('/admin/settings/smtp', [SettingsController::class, 'smtp']);

// --- Student / Teacher Route Aliases ---
$router->get('/student/courses', [CourseController::class, 'index']);
$router->get('/student/assessments', [AssessmentController::class, 'index']);
$router->get('/student/assignments', [AssignmentController::class, 'index']);
$router->get('/student/certificates', [CertificateController::class, 'index']);
// --- REST API v1 ---
$router->post('/api/v1/login', [ApiController::class, 'login']);
$router->get('/api/v1/courses', [ApiController::class, 'courses']);
$router->get('/api/v1/batches', [ApiController::class, 'batches']);
$router->get('/api/v1/notifications', [ApiController::class, 'notifications']);
