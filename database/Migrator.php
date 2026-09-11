<?php

namespace Database;

use App\Database;
use PDO;

class Migrator {
    public static function run(?string $customDbPath = null): void {
        $pdo = Database::connect($customDbPath);

        // Schema tracking table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            );
        ");

        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $applied = $pdo->query("SELECT 1 FROM migrations WHERE migration = '001_initial_schema'")->fetchColumn();
            if (!$applied) {
                $pdo->beginTransaction();
                try {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                    self::seedRolesAndPermissions($pdo);
                    self::seedDefaultSettings($pdo);
                    $pdo->prepare("INSERT INTO migrations (migration, applied_at) VALUES ('001_initial_schema', datetime('now'))")->execute();
                    $pdo->commit();
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
            }
        }

        // Apply incremental migrations in order
        $migDir = __DIR__ . '/migrations';
        if (is_dir($migDir)) {
            $files = glob($migDir . '/*.sql');
            sort($files);
            foreach ($files as $file) {
                $base = basename($file);
                $check = $pdo->prepare("SELECT 1 FROM migrations WHERE migration = ?");
                $check->execute([$base]);
                if (!$check->fetchColumn()) {
                    $pdo->beginTransaction();
                    try {
                        $sql = file_get_contents($file);
                        $pdo->exec($sql);
                        $ins = $pdo->prepare("INSERT INTO migrations (migration, applied_at) VALUES (?, datetime('now'))");
                        $ins->execute([$base]);
                        $pdo->commit();
                    } catch (\Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        // Non-fatal if column already exists from previous runs
                    }
                }
            }
        }
    }

    public static function isMigrated(): bool {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            return $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function seedRolesAndPermissions(PDO $pdo): void {
        // 1. Roles
        $roles = [
            ['Super Admin', 'super_admin', 'Full system access and super administrator privileges.'],
            ['Admin', 'admin', 'Manage LMS operations, users, courses, and settings.'],
            ['Teacher', 'teacher', 'Access assigned batches, courses, lessons, and student grading.'],
            ['Student', 'student', 'Access enrolled courses via batch, submit assessments and assignments.'],
        ];

        $roleStmt = $pdo->prepare("INSERT OR IGNORE INTO roles (name, slug, description, created_at) VALUES (?, ?, ?, datetime('now'))");
        foreach ($roles as $r) {
            $roleStmt->execute($r);
        }

        // Fetch inserted role IDs
        $roleIds = [];
        $rows = $pdo->query("SELECT id, slug FROM roles")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $roleIds[$row['slug']] = (int)$row['id'];
        }

        // 2. Permissions
        $permissions = [
            // Users
            ['users.view', 'View users list and profiles', 'users'],
            ['users.create', 'Create new students and teachers', 'users'],
            ['users.edit', 'Edit user details and passwords', 'users'],
            ['users.archive', 'Archive or restore users', 'users'],
            ['users.delete', 'Permanently delete users', 'users'],

            // Batches
            ['batches.view', 'View batches', 'batches'],
            ['batches.create', 'Create new batches', 'batches'],
            ['batches.edit', 'Edit batches and assign courses/students', 'batches'],
            ['batches.archive', 'Archive or restore batches', 'batches'],
            ['batches.delete', 'Delete batches', 'batches'],

            // Courses
            ['courses.view', 'View courses', 'courses'],
            ['courses.create', 'Create courses', 'courses'],
            ['courses.edit', 'Edit courses and content', 'courses'],
            ['courses.delete', 'Delete courses', 'courses'],
            ['courses.publish', 'Publish or unpublish courses', 'courses'],

            // Units
            ['units.view', 'View course units', 'units'],
            ['units.create', 'Create units', 'units'],
            ['units.edit', 'Edit and reorder units', 'units'],
            ['units.delete', 'Delete units', 'units'],

            // Lessons
            ['lessons.view', 'View lessons', 'lessons'],
            ['lessons.create', 'Create lessons', 'lessons'],
            ['lessons.edit', 'Edit lessons and materials', 'lessons'],
            ['lessons.delete', 'Delete lessons', 'lessons'],
            ['lessons.publish', 'Publish or unpublish lessons', 'lessons'],

            // Assessments
            ['assessments.view', 'View assessments', 'assessments'],
            ['assessments.create', 'Create assessments and quizzes', 'assessments'],
            ['assessments.edit', 'Edit assessments', 'assessments'],
            ['assessments.delete', 'Delete assessments', 'assessments'],
            ['assessments.grade', 'Grade student assessments and override marks', 'assessments'],
            ['assessments.take', 'Take assessments and view results', 'assessments'],

            // Assignments
            ['assignments.view', 'View assignments', 'assignments'],
            ['assignments.create', 'Create assignments', 'assignments'],
            ['assignments.edit', 'Edit assignments', 'assignments'],
            ['assignments.delete', 'Delete assignments', 'assignments'],
            ['assignments.grade', 'Grade student assignment submissions', 'assignments'],
            ['assignments.submit', 'Submit assignment solutions', 'assignments'],

            // Gradebook & Attendance
            ['gradebook.view', 'View gradebook reports', 'academic'],
            ['gradebook.edit', 'Edit manual grades and overrides', 'academic'],
            ['attendance.view', 'View attendance logs', 'academic'],
            ['attendance.mark', 'Mark attendance sessions', 'academic'],

            // Discussions
            ['discussions.participate', 'Post questions and replies in lesson discussions', 'communication'],
            ['discussions.moderate', 'Pin, lock, and moderate discussions', 'communication'],

            // System & Reports
            ['reports.view', 'View analytics and export CSV reports', 'reports'],
            ['settings.manage', 'Manage LMS branding, SMTP, and preferences', 'settings'],
            ['security.manage', 'Manage backups, audit logs, and sessions', 'security'],
        ];

        $permStmt = $pdo->prepare("INSERT OR IGNORE INTO permissions (name, description, module, created_at) VALUES (?, ?, ?, datetime('now'))");
        foreach ($permissions as $p) {
            $permStmt->execute($p);
        }

        // Fetch inserted permission IDs
        $permIds = [];
        $rows = $pdo->query("SELECT id, name FROM permissions")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $permIds[$row['name']] = (int)$row['id'];
        }

        // 3. Assign Role Permissions
        $rpStmt = $pdo->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

        // Super admin gets all permissions
        if (isset($roleIds['super_admin'])) {
            foreach ($permIds as $pId) {
                $rpStmt->execute([$roleIds['super_admin'], $pId]);
            }
        }

        // Admin permissions
        if (isset($roleIds['admin'])) {
            $adminPerms = array_filter(array_keys($permIds), fn($name) => !in_array($name, ['assessments.take', 'assignments.submit']));
            foreach ($adminPerms as $name) {
                $rpStmt->execute([$roleIds['admin'], $permIds[$name]]);
            }
        }

        // Teacher permissions
        if (isset($roleIds['teacher'])) {
            $teacherPerms = [
                'batches.view', 'courses.view', 'units.view', 'units.create', 'units.edit',
                'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.publish',
                'assessments.view', 'assessments.create', 'assessments.edit', 'assessments.grade',
                'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.grade',
                'gradebook.view', 'gradebook.edit', 'attendance.view', 'attendance.mark',
                'discussions.participate', 'discussions.moderate', 'reports.view'
            ];
            foreach ($teacherPerms as $name) {
                if (isset($permIds[$name])) {
                    $rpStmt->execute([$roleIds['teacher'], $permIds[$name]]);
                }
            }
        }

        // Student permissions
        if (isset($roleIds['student'])) {
            $studentPerms = [
                'batches.view', 'courses.view', 'units.view', 'lessons.view',
                'assessments.take', 'assignments.submit', 'discussions.participate',
                'gradebook.view', 'attendance.view'
            ];
            foreach ($studentPerms as $name) {
                if (isset($permIds[$name])) {
                    $rpStmt->execute([$roleIds['student'], $permIds[$name]]);
                }
            }
        }
    }

    protected static function seedDefaultSettings(PDO $pdo): void {
        $defaults = [
            'app_name' => 'Open LMS',
            'app_description' => 'A modern private Learning Management System.',
            'logo' => '',
            'favicon' => '',
            'login_title' => 'Sign In to Your Learning Portal',
            'login_description' => 'Enter your credentials to continue to your courses.',
            'login_logo' => '',
            'login_background' => '',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#06b6d4',
            'footer_text' => 'Powered by Open LMS',
            'contact_email' => 'support@example.com',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'theme' => 'light',
            'show_calendar' => '1',
            'certificate_issuer' => 'Open LMS Academy',
        ];

        $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value, created_at, updated_at) VALUES (?, ?, datetime('now'), datetime('now'))");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }
}
