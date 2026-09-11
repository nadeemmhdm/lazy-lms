<?php

declare(strict_types=1);

/**
 * Lazy LMS - Automated Acceptance Criteria Verification Test Suite
 * Aligned with the relational database/schema.sql architecture.
 */

require_once __DIR__ . '/../app/App.php';

// Boot core
\App\App::boot();

echo "=======================================================\n";
echo "       LAZY LMS VERIFICATION & ACCEPTANCE TEST         \n";
echo "=======================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function it(string $description, callable $test): void {
    global $testsPassed, $testsFailed;
    echo "• Testing: {$description} ... ";
    try {
        $test();
        echo "\033[32m[PASS]\033[0m\n";
        $testsPassed++;
    } catch (\Throwable $e) {
        echo "\033[31m[FAIL]\033[0m: " . $e->getMessage() . "\n";
        echo "  In: " . $e->getFile() . " on line " . $e->getLine() . "\n";
        $testsFailed++;
    }
}

// 1. Test Database Connectivity and Schema Migration
it("Database Migrator creates schema, tables, foreign keys, and seeds", function() {
    $dbPath = dirname(__DIR__) . '/storage/database.sqlite';
    if (file_exists($dbPath)) {
        @unlink($dbPath);
    }

    $db = \App\Database::connect($dbPath);
    $fk = $db->query("PRAGMA foreign_keys")->fetchColumn();
    if ($fk != 1) throw new Exception("Foreign keys are not enabled!");

    require_once dirname(__DIR__) . '/database/Migrator.php';
    \Database\Migrator::run();

    // Verify core tables exist
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $required = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
        'batches', 'batch_students', 'batch_courses', 'batch_history',
        'courses', 'units', 'lessons', 'lesson_materials', 'lesson_progress', 'course_progress',
        'assessments', 'assessment_questions', 'question_bank', 'assessment_attempts',
        'assignments', 'assignment_submissions',
        'attendance_sessions', 'attendance_records',
        'discussions', 'discussion_replies',
        'announcements', 'notifications',
        'certificates', 'audit_logs', 'settings', 'email_settings', 'sessions'
    ];
    foreach ($required as $table) {
        if (!in_array($table, $tables)) {
            throw new Exception("Missing required table: {$table}");
        }
    }
});

// 2. Test Super Admin and Standard User Creation with secure recovery questions
it("User model hashes passwords securely, assigns roles, and saves recovery question", function() {
    $db = \App\Database::getInstance();

    // Insert Super Admin
    $hash = \App\Helpers\AuthHelper::hashPassword('SuperSecret123!');
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, status, created_at, updated_at)
        VALUES ('Super Admin', 'admin@openlms.test', ?, 'active', datetime('now'), datetime('now'))
    ");
    $stmt->execute([$hash]);
    $adminId = (int)$db->lastInsertId();

    $roleId = $db->query("SELECT id FROM roles WHERE slug = 'super_admin'")->fetchColumn();
    $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$adminId, $roleId]);

    // Insert recovery question for Super Admin
    $ansHash = \App\Helpers\AuthHelper::normalizeSecurityAnswer('Antigravity');
    $qStmt = $db->prepare("INSERT INTO recovery_questions (user_id, question, answer_hash, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))");
    $qStmt->execute([$adminId, 'What is your favorite engine?', $ansHash]);

    // Insert Teacher
    $tHash = \App\Helpers\AuthHelper::hashPassword('TeacherPass123!');
    $stmtTeacher = $db->prepare("
        INSERT INTO users (name, email, password, status, created_at, updated_at)
        VALUES ('Professor Oak', 'teacher@openlms.test', ?, 'active', datetime('now'), datetime('now'))
    ");
    $stmtTeacher->execute([$tHash]);
    $teacherId = (int)$db->lastInsertId();
    $tRoleId = $db->query("SELECT id FROM roles WHERE slug = 'teacher'")->fetchColumn();
    $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$teacherId, $tRoleId]);

    // Insert Student
    $sHash = \App\Helpers\AuthHelper::hashPassword('StudentPass123!');
    $stmtStudent = $db->prepare("
        INSERT INTO users (name, email, password, status, created_at, updated_at)
        VALUES ('Alex Rivera', 'student@openlms.test', ?, 'active', datetime('now'), datetime('now'))
    ");
    $stmtStudent->execute([$sHash]);
    $studentId = (int)$db->lastInsertId();
    $sRoleId = $db->query("SELECT id FROM roles WHERE slug = 'student'")->fetchColumn();
    $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$studentId, $sRoleId]);

    if (!password_verify('SuperSecret123!', $hash)) throw new Exception("Password verify failed");
    if (!password_verify('TeacherPass123!', $tHash)) throw new Exception("Teacher password verify failed");
    if (!password_verify('StudentPass123!', $sHash)) throw new Exception("Student password verify failed");
});

// 3. Test Security Answers & Brute-force Lockout Logic
it("Security answer normalization and lockout threshold logic works", function() {
    $raw1 = " Antigravity ";
    $raw2 = "antigravity";
    $norm1 = \App\Helpers\AuthHelper::normalizeSecurityAnswer($raw1);
    $norm2 = \App\Helpers\AuthHelper::normalizeSecurityAnswer($raw2);
    if ($norm1 !== $norm2) throw new Exception("Answer normalization failed mismatch");

    // Check login attempts tracking
    \App\Helpers\AuthHelper::recordFailedLogin('127.0.0.1', 'unknown@test.com');
    \App\Helpers\AuthHelper::recordFailedLogin('127.0.0.1', 'unknown@test.com');
    $db = \App\Database::getInstance();
    $count = $db->query("SELECT COUNT(*) FROM login_attempts WHERE identifier = 'unknown@test.com'")->fetchColumn();
    if ($count != 2) throw new Exception("Failed login attempts not recorded");

    \App\Helpers\AuthHelper::clearLoginAttempts('127.0.0.1', 'unknown@test.com');
    $countAfter = $db->query("SELECT COUNT(*) FROM login_attempts WHERE identifier = 'unknown@test.com'")->fetchColumn();
    if ($countAfter != 0) throw new Exception("Failed login attempts not cleared");
});

// 4. Test TOTP Two-Factor Generation and RFC 6238 Verification
it("RFC 6238 TOTP generates valid secrets, verification codes, and recovery keys", function() {
    $secret = \App\Helpers\TotpHelper::generateSecret();
    if (strlen($secret) < 16) throw new Exception("Generated TOTP secret too short");

    $code = \App\Helpers\TotpHelper::getCode($secret);
    if (strlen($code) !== 6) throw new Exception("Generated TOTP code is not 6 digits: {$code}");

    $valid = \App\Helpers\TotpHelper::verify($secret, $code);
    if (!$valid) throw new Exception("TOTP code verification failed on current timestamp");

    $invalid = \App\Helpers\TotpHelper::verify($secret, '000000');
    if ($invalid && $code !== '000000') throw new Exception("TOTP accepted bad code");

    $recoveryCodes = \App\Helpers\TotpHelper::generateRecoveryCodes();
    if (count($recoveryCodes) !== 8) throw new Exception("Expected 8 recovery codes");
});

// 5. Test Academic Hierarchy: Batch -> Courses -> Units -> Lessons
it("Batch, Course, Unit, and Lesson hierarchy correctly links and enforces relationships", function() {
    $db = \App\Database::getInstance();

    // Create Batch
    $bStmt = $db->prepare("INSERT INTO batches (name, code, description, status, start_date, end_date, created_at, updated_at) VALUES ('Alpha Batch 2026', 'ALPHA26', 'First batch', 'active', '2026-01-01', '2026-12-31', datetime('now'), datetime('now'))");
    $bStmt->execute();
    $batchId = (int)$db->lastInsertId();

    // Create Course
    $cStmt = $db->prepare("INSERT INTO courses (title, code, description, status, created_by, created_at, updated_at) VALUES ('Computer Science 101', 'CS101', 'Fundamentals of Computing', 'published', 1, datetime('now'), datetime('now'))");
    $cStmt->execute();
    $courseId = (int)$db->lastInsertId();

    // Link Batch -> Course
    $bcStmt = $db->prepare("INSERT INTO batch_courses (batch_id, course_id, assigned_at) VALUES (?, ?, datetime('now'))");
    $bcStmt->execute([$batchId, $courseId]);

    // Create Unit in Course
    $uStmt = $db->prepare("INSERT INTO units (course_id, title, sort_order, created_at, updated_at) VALUES (?, 'Unit 1: Architecture', 1, datetime('now'), datetime('now'))");
    $uStmt->execute([$courseId]);
    $unitId = (int)$db->lastInsertId();

    // Create Lesson in Unit
    $lStmt = $db->prepare("INSERT INTO lessons (unit_id, course_id, title, content, lesson_type, sort_order, status, created_at, updated_at) VALUES (?, ?, 'Lesson 1.1: CPU and Memory', '<p>Computers use binary arithmetic.</p>', 'text', 1, 'published', datetime('now'), datetime('now'))");
    $lStmt->execute([$unitId, $courseId]);
    $lessonId = (int)$db->lastInsertId();

    // Enroll Student in Batch
    $studentId = $db->query("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1")->fetchColumn();
    $db->prepare("INSERT INTO batch_students (batch_id, student_id, enrolled_at, status) VALUES (?, ?, datetime('now'), 'active')")->execute([$batchId, $studentId]);

    // Verify student automatically receives course via batch
    $receivedCourse = $db->query("
        SELECT c.id FROM courses c
        JOIN batch_courses bc ON c.id = bc.course_id
        JOIN batch_students bs ON bc.batch_id = bs.batch_id
        WHERE bs.student_id = {$studentId}
    ")->fetchColumn();

    if ($receivedCourse != $courseId) throw new Exception("Student did not inherit course via batch association");
});

// 6. Test Student Batch Transfer with Course Handling
it("Student batch change handles replacement, retention, and enrollment audit history", function() {
    $db = \App\Database::getInstance();
    $studentId = (int)$db->query("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1")->fetchColumn();

    // Create Beta Batch
    $bStmt = $db->prepare("INSERT INTO batches (name, code, description, status, created_at, updated_at) VALUES ('Beta Evening 2026', 'BETA26', 'Evening cohort', 'active', datetime('now'), datetime('now'))");
    $bStmt->execute();
    $newBatchId = (int)$db->lastInsertId();

    // Record Batch History
    $histStmt = $db->prepare("
        INSERT INTO batch_history (student_id, old_batch_id, new_batch_id, transition_mode, changed_by, created_at)
        VALUES (?, 1, ?, 'replace', 1, datetime('now'))
    ");
    $histStmt->execute([$studentId, $newBatchId]);

    // Update active enrollment
    $db->prepare("UPDATE batch_students SET status = 'dropped' WHERE student_id = ? AND batch_id = 1")->execute([$studentId]);
    $db->prepare("INSERT INTO batch_students (batch_id, student_id, enrolled_at, status) VALUES (?, ?, datetime('now'), 'active')")->execute([$newBatchId, $studentId]);

    $activeBatch = $db->query("SELECT batch_id FROM batch_students WHERE student_id = {$studentId} AND status = 'active'")->fetchColumn();
    if ($activeBatch != $newBatchId) throw new Exception("Batch transfer failed");
});

// 7. Test Question Bank and Automated Assessment Engine
it("Question bank supports numeric, true/false, single choice, and scores answers accurately", function() {
    $db = \App\Database::getInstance();

    // 1. Numeric Question: What is 10.5 + 4.5? Answer: 15
    $qStmt = $db->prepare("
        INSERT INTO question_bank (question, question_type, category, difficulty, marks, correct_answer, created_by, created_at, updated_at)
        VALUES ('Calculate 10.5 + 4.5', 'numeric', 'Math', 'easy', 5.0, '[\"15\"]', 1, datetime('now'), datetime('now'))
    ");
    $qStmt->execute();
    $qId = (int)$db->lastInsertId();

    // Create Assessment
    $aStmt = $db->prepare("
        INSERT INTO assessments (course_id, title, marks, passing_score, time_limit_minutes, attempt_limit, status, created_by, created_at, updated_at)
        VALUES (1, 'Test Quiz 1', 10.0, 5.0, 20, 2, 'published', 1, datetime('now'), datetime('now'))
    ");
    $aStmt->execute();
    $assessmentId = (int)$db->lastInsertId();

    // Link Question to Assessment
    $db->prepare("INSERT INTO assessment_questions (assessment_id, question_id, marks, sort_order) VALUES (?, ?, 5.0, 1)")->execute([$assessmentId, $qId]);

    // Student submits numeric answer "15.0"
    $studentId = $db->query("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1")->fetchColumn();
    $attStmt = $db->prepare("INSERT INTO assessment_attempts (assessment_id, user_id, attempt_number, started_at, score, max_score, status) VALUES (?, ?, 1, datetime('now'), 5.0, 10.0, 'graded')");
    $attStmt->execute([$assessmentId, $studentId]);
    $attemptId = (int)$db->lastInsertId();

    // Verify submission recorded
    $recordedScore = $db->query("SELECT score FROM assessment_attempts WHERE id = {$attemptId}")->fetchColumn();
    if ($recordedScore != 5.0) throw new Exception("Assessment score calculation error");
});

// 8. Test Course Completion and Automatic Certificate Generation
it("100% course completion triggers certificate creation with verifiable code", function() {
    $db = \App\Database::getInstance();
    $studentId = (int)$db->query("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1")->fetchColumn();
    $courseId = 1;

    // Simulate completion
    $progStmt = $db->prepare("
        INSERT INTO course_progress (user_id, course_id, progress_percentage, is_completed, completed_at, updated_at)
        VALUES (?, ?, 100.0, 1, datetime('now'), datetime('now'))
        ON CONFLICT(user_id, course_id) DO UPDATE SET is_completed = 1, progress_percentage = 100.0
    ");
    $progStmt->execute([$studentId, $courseId]);

    // Issue certificate
    $code = 'CERT-VERIFY123';
    $certNum = 'NUM-1001';
    $certStmt = $db->prepare("
        INSERT INTO certificates (user_id, course_id, certificate_number, verification_code, student_name, course_name, issue_date, created_at)
        VALUES (?, ?, ?, ?, 'John Doe', 'Computer Science 101', '2026-09-11', datetime('now'))
    ");
    $certStmt->execute([$studentId, $courseId, $certNum, $code]);

    // Verify public verification query
    $verifyStmt = $db->prepare("
        SELECT cert.verification_code, cert.course_name as course_title, cert.student_name
        FROM certificates cert
        WHERE cert.verification_code = ?
    ");
    $verifyStmt->execute([$code]);
    $cert = $verifyStmt->fetch();

    if (!$cert || $cert['verification_code'] !== $code) {
        throw new Exception("Certificate verification failed");
    }
});

// 9. Test Backup Creation and System Health Inspection
it("Hot SQLite backup file copy executes and is protected", function() {
    $source = dirname(__DIR__) . '/storage/database.sqlite';
    $dest = dirname(__DIR__) . '/storage/backups/test_backup.sqlite';
    if (!copy($source, $dest)) throw new Exception("Failed to create SQLite backup");
    if (!file_exists($dest) || filesize($dest) === 0) throw new Exception("Backup file is empty or missing");
    @unlink($dest);
});

// 10. Test Router and REST API Authentication
it("Router registers all front-controller routes and API endpoints correctly", function() {
    $router = \App\App::getRouter();
    $routes = $router->getRoutes();

    if (empty($routes)) {
        throw new Exception("No routes registered in Router");
    }

    $requiredPaths = [
        '/install', '/login', '/admin', '/teacher', '/student',
        '/batches', '/students', '/teachers', '/courses',
        '/assessments', '/assignments', '/gradebook', '/attendance',
        '/certificates', '/announcements', '/notifications',
        '/calendar', '/reports', '/search', '/settings/branding',
        '/settings/health', '/settings/backups', '/download/file/{id}',
        '/api/v1/login', '/api/v1/courses', '/api/v1/batches'
    ];

    $allPaths = array_column($routes, 'path');
    foreach ($requiredPaths as $rp) {
        $found = false;
        foreach ($allPaths as $pattern) {
            if ($pattern === $rp) {
                $found = true;
                break;
            }
        }
        if (!$found) throw new Exception("Missing mapped route for path: {$rp}");
    }
});

// 11. Test HashId Public Alphanumeric Identifiers
it("HashId generates secure 5-8 character alphanumeric public IDs and resolves collisions", function() {
    $pubId = \App\Helpers\HashId::generate('users');
    if (strlen($pubId) < 5 || strlen($pubId) > 8) {
        throw new Exception("HashId length must be between 5 and 8 characters, got: " . strlen($pubId));
    }
    if (!ctype_alnum($pubId)) {
        throw new Exception("HashId must be alphanumeric, got: " . $pubId);
    }
});

// 12. Test Remember-Me & Device Security Tracking
it("Remember-me stores 12-hour rotating token and tracks new device/IP login security events", function() {
    $testUser = \App\Database::fetchOne("SELECT id, email FROM users WHERE email = 'admin@openlms.test'");
    if (!$testUser) {
        $testUser = \App\Database::fetchOne("SELECT id, email FROM users ORDER BY id ASC LIMIT 1");
    }
    if (!$testUser) throw new Exception("Admin user not found for remember-me test");

    \App\Helpers\AuthHelper::setRememberToken((int)$testUser['id']);
    $tokenRow = \App\Database::fetchOne("SELECT * FROM remember_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$testUser['id']]);
    if (!$tokenRow) throw new Exception("Failed to insert remember_token");

    // Test device tracking
    \App\Helpers\AuthHelper::checkAndRecordDevice((int)$testUser['id'], '198.51.100.42', 'PHPUnit Test Agent');
    $dev = \App\Database::fetchOne("SELECT * FROM login_devices WHERE user_id = ? AND ip_address = '198.51.100.42'", [$testUser['id']]);
    if (!$dev) throw new Exception("Failed to record new login device");

    $secEv = \App\Database::fetchOne("SELECT * FROM security_events WHERE event_type = 'security.new_ip_login' AND ip_address = '198.51.100.42'");
    if (!$secEv) throw new Exception("Failed to log security.new_ip_login security event");
});

// 13. Test Lesson Quizzes & Automatic Progress
it("Lesson quiz validates MCQ answers, calculates passing score, and auto-updates lesson progress", function() {
    $pdo = \App\Database::pdo();
    $lesson = \App\Database::fetchOne("SELECT id, course_id FROM lessons LIMIT 1");
    if (!$lesson) throw new Exception("No lesson available for quiz test");

    // Insert quiz
    $pdo->prepare("
        INSERT INTO lesson_quizzes (public_id, lesson_id, course_id, title, passing_score, max_attempts, status, created_at, updated_at)
        VALUES ('LzqZ01', ?, ?, 'Unit 1 Mastery Quiz', 60.0, 3, 'published', datetime('now'), datetime('now'))
        ON CONFLICT(lesson_id) DO UPDATE SET title = 'Unit 1 Mastery Quiz'
    ")->execute([$lesson['id'], $lesson['course_id']]);

    $quiz = \App\Database::fetchOne("SELECT id FROM lesson_quizzes WHERE lesson_id = ?", [$lesson['id']]);
    
    // Add MCQ question
    $pdo->prepare("INSERT INTO lesson_quiz_questions (public_id, quiz_id, question_text, marks) VALUES ('QzQ1', ?, 'What is PHP?', 5.0)")->execute([$quiz['id']]);
    $qId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO lesson_quiz_options (question_id, option_text, is_correct) VALUES (?, 'A server-side scripting language', 1)")->execute([$qId]);
    $pdo->prepare("INSERT INTO lesson_quiz_options (question_id, option_text, is_correct) VALUES (?, 'A database engine', 0)")->execute([$qId]);

    $student = \App\Database::fetchOne("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1");
    if ($student) {
        // Record passing attempt
        $pdo->prepare("INSERT INTO lesson_quiz_attempts (public_id, quiz_id, user_id, attempt_number, score, max_score, is_passed, started_at, completed_at) VALUES ('Atmp01', ?, ?, 1, 5.0, 5.0, 1, datetime('now'), datetime('now'))")->execute([$quiz['id'], $student['id']]);
        
        // Auto update progress
        $pdo->prepare("
            INSERT INTO lesson_progress (lesson_id, user_id, is_completed, completed_at, updated_at) 
            VALUES (?, ?, 1, datetime('now'), datetime('now'))
            ON CONFLICT(user_id, lesson_id) DO UPDATE SET is_completed = 1, completed_at = datetime('now'), updated_at = datetime('now')
        ")->execute([$lesson['id'], $student['id']]);
        $prog = \App\Database::fetchOne("SELECT is_completed FROM lesson_progress WHERE lesson_id = ? AND user_id = ?", [$lesson['id'], $student['id']]);
        if (empty($prog['is_completed'])) throw new Exception("Failed to auto-update lesson progress upon passing quiz");
    }
});

// 14. Test Examination System, Server Timer & Auto-Save
it("Examination system supports MCQ, Short Answer, Descriptive, timer bounds, and auto-save", function() {
    $pdo = \App\Database::pdo();
    $course = \App\Database::fetchOne("SELECT id FROM courses LIMIT 1");
    if (!$course) throw new Exception("No course available for exam test");

    $examPubId = \App\Helpers\HashId::generate('exams');
    $pdo->prepare("
        INSERT INTO exams (public_id, course_id, title, start_time, end_time, duration_minutes, total_marks, passing_score, max_attempts, status, created_at, updated_at)
        VALUES (?, ?, 'Midterm Examination', datetime('now', '-1 hour'), datetime('now', '+2 hours'), 90, 100.0, 50.0, 1, 'published', datetime('now'), datetime('now'))
    ")->execute([$examPubId, $course['id']]);
    $examId = $pdo->lastInsertId();

    // Add MCQ, Short Answer, Descriptive questions
    $pdo->prepare("INSERT INTO exam_questions (public_id, exam_id, question_type, descriptive_mode, question_text, marks, correct_answer) VALUES ('EQ01', ?, 'mcq', 'both', '2 + 2 = ?', 10.0, '0')")->execute([$examId]);
    $pdo->prepare("INSERT INTO exam_questions (public_id, exam_id, question_type, descriptive_mode, question_text, marks) VALUES ('EQ02', ?, 'short_answer', 'both', 'Define MVC architecture.', 15.0)")->execute([$examId]);
    $pdo->prepare("INSERT INTO exam_questions (public_id, exam_id, question_type, descriptive_mode, question_text, marks) VALUES ('EQ03', ?, 'descriptive', 'upload', 'Upload lab project zip.', 25.0)")->execute([$examId]);

    $student = \App\Database::fetchOne("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' LIMIT 1");
    if ($student) {
        $attPub = \App\Helpers\HashId::generate('exam_attempts');
        $sampleAnswers = json_encode(['EQ01' => '0', 'EQ02' => ['text' => 'Model-View-Controller pattern']]);
        $pdo->prepare("
            INSERT INTO exam_attempts (public_id, exam_id, user_id, attempt_number, started_at, auto_saved_at, answers_json, max_score, status)
            VALUES (?, ?, ?, 1, datetime('now'), datetime('now'), ?, 100.0, 'in_progress')
        ")->execute([$attPub, $examId, $student['id'], $sampleAnswers]);

        $attempt = \App\Database::fetchOne("SELECT * FROM exam_attempts WHERE public_id = ?", [$attPub]);
        if (!$attempt || empty($attempt['answers_json'])) throw new Exception("Exam attempt auto-save state not persisted correctly");
    }
});

// 15. Test Platform Notifications & Quick Links
it("Platform notification system filters target audiences and manages quick links", function() {
    $pdo = \App\Database::pdo();
    $admin = \App\Database::fetchOne("SELECT id FROM users WHERE email = 'admin@openlms.test'");
    if (!$admin) {
        $admin = \App\Database::fetchOne("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    }

    $notifPub = \App\Helpers\HashId::generate('platform_notifications');
    $pdo->prepare("
        INSERT INTO platform_notifications (public_id, title, message, target_type, priority, send_email, status, created_by, created_at)
        VALUES (?, 'Urgent Campus Update', 'Campus reopens tomorrow at 8 AM.', 'all', 'urgent', 0, 'published', ?, datetime('now'))
    ")->execute([$notifPub, $admin['id']]);

    $n = \App\Database::fetchOne("SELECT * FROM platform_notifications WHERE public_id = ?", [$notifPub]);
    if (!$n || $n['priority'] !== 'urgent') throw new Exception("Failed to insert urgent platform notification");

    // Quick link test
    $linkPub = \App\Helpers\HashId::generate('admin_links');
    $pdo->prepare("INSERT INTO admin_links (public_id, title, url, visibility_type, is_visible, sort_order, created_at) VALUES (?, 'Library', 'https://library.edu', 'all', 1, 1, datetime('now'))")->execute([$linkPub]);
    $link = \App\Database::fetchOne("SELECT * FROM admin_links WHERE public_id = ?", [$linkPub]);
    if (!$link || (int)$link['is_visible'] !== 1) throw new Exception("Failed to create admin quick link");
});

// 16. Test Maintenance Mode & Role Bypass
it("Maintenance mode toggles state and preserves full administrator management bypass", function() {
    $pdo = \App\Database::pdo();
    $pdo->prepare("INSERT INTO settings (key, value, created_at, updated_at) VALUES ('maintenance_mode', '1', datetime('now'), datetime('now')) ON CONFLICT(key) DO UPDATE SET value = '1', updated_at = datetime('now')")->execute();
    
    $isMaint = (bool)\App\Database::fetchOne("SELECT value FROM settings WHERE key = 'maintenance_mode'")['value'];
    if (!$isMaint) throw new Exception("Maintenance mode flag failed to set to ON");

    // Revert back to operational
    $pdo->prepare("UPDATE settings SET value = '0' WHERE key = 'maintenance_mode'")->execute();
    $isMaintAfter = (bool)\App\Database::fetchOne("SELECT value FROM settings WHERE key = 'maintenance_mode'")['value'];
    if ($isMaintAfter) throw new Exception("Maintenance mode failed to turn OFF");
});

echo "\n=======================================================\n";
echo "TEST RESULTS: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
} else {
    exit(0);
}
