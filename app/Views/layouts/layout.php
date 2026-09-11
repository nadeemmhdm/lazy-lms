<!-- Lazy LMS - Modern, Secure, Self-Hosted Learning Management System -->
<!DOCTYPE html>
<html lang="<?= e(\App\Helpers\I18n::getLocale()) ?>" dir="<?= \App\Helpers\I18n::isRtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'Lazy LMS') ?> - <?= e($appSettings['app_name'] ?? 'Lazy LMS') ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="icon" href="<?= !empty($appSettings['favicon']) ? url('/download/file/' . $appSettings['favicon']) : asset('favicon.ico') ?>" type="image/x-icon">
    <?php if (!empty($appSettings['primary_color']) || !empty($appSettings['secondary_color'])): ?>
    <style>
        :root {
            <?php if (!empty($appSettings['primary_color'])): ?>--primary: <?= e($appSettings['primary_color']) ?>;<?php endif; ?>
            <?php if (!empty($appSettings['secondary_color'])): ?>--secondary: <?= e($appSettings['secondary_color']) ?>;<?php endif; ?>
        }
    </style>
    <?php endif; ?>
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <a href="<?= url('/') ?>" class="brand-link">
                <?php if (!empty($appSettings['logo'])): ?>
                    <img src="<?= url('/download/file/' . $appSettings['logo']) ?>" alt="Logo" style="height: 32px; border-radius: 6px;">
                <?php else: ?>
                    <div class="brand-logo-icon"><i class="bx bxs-graduation"></i></div>
                <?php endif; ?>
                <span><?= e($appSettings['app_name'] ?? 'Lazy LMS') ?></span>
            </a>
        </div>

        <div class="sidebar-nav">
            <?php 
                $role = \App\Helpers\AuthHelper::role(); 
                $curr = $_SERVER['REQUEST_URI'] ?? '';
            ?>

            <!-- Dashboards -->
            <div class="nav-section-title">Dashboard</div>
            <?php if ($role === 'super_admin' || $role === 'admin'): ?>
                <a href="<?= url('/admin') ?>" class="nav-item <?= $curr === '/admin' ? 'active' : '' ?>">
                    <i class="bx bxs-dashboard"></i> <span>Admin Dashboard</span>
                </a>
            <?php elseif ($role === 'teacher'): ?>
                <a href="<?= url('/teacher') ?>" class="nav-item <?= $curr === '/teacher' ? 'active' : '' ?>">
                    <i class="bx bxs-dashboard"></i> <span>Teacher Dashboard</span>
                </a>
            <?php else: ?>
                <a href="<?= url('/student') ?>" class="nav-item <?= $curr === '/student' ? 'active' : '' ?>">
                    <i class="bx bxs-dashboard"></i> <span>My Learning Portal</span>
                </a>
            <?php endif; ?>

            <!-- Learning -->
            <div class="nav-section-title">Learning & Curriculum</div>
            <a href="<?= url('/courses') ?>" class="nav-item <?= str_contains($curr, '/courses') ? 'active' : '' ?>">
                <i class="bx bx-book-bookmark"></i> <span><?= $role === 'student' ? 'My Courses' : 'Courses' ?></span>
            </a>
            <?php if (has_permission('courses.view') || has_permission('courses.edit')): ?>
                <a href="<?= url('/subjects') ?>" class="nav-item <?= str_contains($curr, '/subjects') ? 'active' : '' ?>">
                    <i class="bx bx-book-content"></i> <span>Subjects</span>
                </a>
            <?php endif; ?>

            <a href="<?= url('/assignments') ?>" class="nav-item <?= str_contains($curr, '/assignments') ? 'active' : '' ?>">
                <i class="bx bx-task"></i> <span>Assignments</span>
            </a>

            <a href="<?= url('/exams') ?>" class="nav-item <?= str_contains($curr, '/exams') ? 'active' : '' ?>">
                <i class="bx bx-award"></i> <span>Examinations</span>
            </a>

            <a href="<?= url('/assessments') ?>" class="nav-item <?= str_contains($curr, '/assessments') ? 'active' : '' ?>">
                <i class="bx bx-check-square"></i> <span>Assessments</span>
            </a>

            <a href="<?= url('/classes') ?>" class="nav-item <?= str_contains($curr, '/classes') ? 'active' : '' ?>">
                <i class="bx bx-video"></i> <span>Live Classes</span>
            </a>

            <a href="<?= url('/certificates') ?>" class="nav-item <?= str_contains($curr, '/certificates') ? 'active' : '' ?>">
                <i class="bx bx-certification"></i> <span><?= $role === 'student' ? 'My Certificates' : 'Certificates' ?></span>
            </a>

            <!-- People -->
            <?php if (has_permission('users.view') || has_permission('batches.view')): ?>
                <div class="nav-section-title">People & Batches</div>
                <?php if (has_permission('batches.view')): ?>
                    <a href="<?= url('/batches') ?>" class="nav-item <?= str_contains($curr, '/batches') ? 'active' : '' ?>">
                        <i class="bx bx-group"></i> <span>Batches</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('users.view')): ?>
                    <a href="<?= url('/students') ?>" class="nav-item <?= str_contains($curr, '/students') ? 'active' : '' ?>">
                        <i class="bx bxs-user-detail"></i> <span>Students</span>
                    </a>
                    <a href="<?= url('/teachers') ?>" class="nav-item <?= str_contains($curr, '/teachers') ? 'active' : '' ?>">
                        <i class="bx bx-user-pin"></i> <span>Teachers</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Academic Operations -->
            <?php if (has_permission('reports.view') || has_permission('batches.view') || has_permission('assessments.create') || $role === 'student'): ?>
                <div class="nav-section-title">Academic & Progress</div>
                <?php if (has_permission('assessments.create')): ?>
                    <a href="<?= url('/question-bank') ?>" class="nav-item <?= str_contains($curr, '/question-bank') ? 'active' : '' ?>">
                        <i class="bx bx-layer"></i> <span>Question Bank</span>
                    </a>
                <?php endif; ?>
                <a href="<?= url('/gradebook') ?>" class="nav-item <?= str_contains($curr, '/gradebook') ? 'active' : '' ?>">
                    <i class="bx bx-medal"></i> <span>Gradebook</span>
                </a>
                <a href="<?= url('/attendance') ?>" class="nav-item <?= str_contains($curr, '/attendance') ? 'active' : '' ?>">
                    <i class="bx bx-calendar-check"></i> <span>Attendance</span>
                </a>
                <?php if (has_permission('reports.view')): ?>
                    <a href="<?= url('/reports') ?>" class="nav-item <?= str_contains($curr, '/reports') ? 'active' : '' ?>">
                        <i class="bx bx-bar-chart-alt-2"></i> <span>Reports & Analytics</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Communication & Calendar -->
            <div class="nav-section-title">Communication</div>
            <a href="<?= url('/platform-notifications') ?>" class="nav-item <?= str_contains($curr, '/platform-notifications') ? 'active' : '' ?>">
                <i class="bx bx-bell"></i> <span>Platform Broadcasts</span>
            </a>
            <a href="<?= url('/announcements') ?>" class="nav-item <?= str_contains($curr, '/announcements') ? 'active' : '' ?>">
                <i class="bx bx-broadcast"></i> <span>Announcements</span>
            </a>

            <!-- Calendar (Toggleable in Settings) -->
            <?php if (($appSettings['show_calendar'] ?? '1') === '1'): ?>
                <a href="<?= url('/calendar') ?>" class="nav-item <?= str_contains($curr, '/calendar') ? 'active' : '' ?>">
                    <i class="bx bx-calendar"></i> <span>Academic Calendar</span>
                </a>
            <?php endif; ?>

            <!-- System & Settings for Admins -->
            <?php if (has_permission('settings.manage') || has_permission('security.manage')): ?>
                <div class="nav-section-title">Administration</div>
                <a href="<?= url('/settings/branding') ?>" class="nav-item <?= str_contains($curr, '/settings/branding') ? 'active' : '' ?>">
                    <i class="bx bx-palette"></i> <span>Branding & Logo</span>
                </a>
                <a href="<?= url('/settings/smtp') ?>" class="nav-item <?= str_contains($curr, '/settings/smtp') ? 'active' : '' ?>">
                    <i class="bx bx-mail-send"></i> <span>Custom SMTP</span>
                </a>
                <a href="<?= url('/settings/email-templates') ?>" class="nav-item <?= str_contains($curr, '/settings/email-templates') ? 'active' : '' ?>">
                    <i class="bx bx-envelope"></i> <span>Email Templates</span>
                </a>
                <a href="<?= url('/settings/maintenance') ?>" class="nav-item <?= str_contains($curr, '/settings/maintenance') ? 'active' : '' ?>">
                    <i class="bx bx-wrench"></i> <span>Maintenance Mode</span>
                </a>
                <a href="<?= url('/admin/links') ?>" class="nav-item <?= str_contains($curr, '/admin/links') ? 'active' : '' ?>">
                    <i class="bx bx-link"></i> <span>Quick Links</span>
                </a>
                <a href="<?= url('/settings/backups') ?>" class="nav-item <?= str_contains($curr, '/settings/backups') ? 'active' : '' ?>">
                    <i class="bx bx-data"></i> <span>Database Backups</span>
                </a>
                <a href="<?= url('/settings/health') ?>" class="nav-item <?= str_contains($curr, '/settings/health') ? 'active' : '' ?>">
                    <i class="bx bx-pulse"></i> <span>System Health</span>
                </a>
                <a href="<?= url('/settings/audit-logs') ?>" class="nav-item <?= str_contains($curr, '/settings/audit-logs') ? 'active' : '' ?>">
                    <i class="bx bx-history"></i> <span>Audit Logs</span>
                </a>
                <a href="<?= url('/settings/sessions') ?>" class="nav-item <?= str_contains($curr, '/settings/sessions') ? 'active' : '' ?>">
                    <i class="bx bx-devices"></i> <span>Active Sessions</span>
                </a>
            <?php endif; ?>

            <!-- User Preferences -->
            <div class="nav-section-title">Account</div>
            <a href="<?= url('/profile') ?>" class="nav-item <?= str_contains($curr, '/profile') ? 'active' : '' ?>">
                <i class="bx bx-user-circle"></i> <span>My Profile & 2FA</span>
            </a>
            <a href="<?= url('/logout') ?>" class="nav-item" data-confirm="Are you sure you want to log out?">
                <i class="bx bx-log-out" style="color: var(--danger);"></i> <span style="color: var(--danger);">Sign Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" title="Toggle Navigation">
                    <i class="bx bx-menu"></i>
                </button>
                <form action="<?= url('/search') ?>" method="GET" class="global-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="q" placeholder="Global search courses, students, batches..." value="<?= e($_GET['q'] ?? '') ?>">
                </form>
            </div>

            <div class="topbar-right">
                <!-- Theme Toggle -->
                <button type="button" class="sidebar-toggle-btn" id="theme-toggle-btn" title="Toggle Dark/Light Mode">
                    <i class="bx bx-moon"></i>
                </button>

                <!-- Notifications Dropdown -->
                <a href="<?= url('/platform-notifications') ?>" class="sidebar-toggle-btn" style="position: relative;" title="Notifications">
                    <i class="bx bx-bell"></i>
                    <?php if (($unreadNotificationsCount ?? 0) > 0): ?>
                        <span style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%;"></span>
                    <?php endif; ?>
                </a>

                <!-- User Profile Pill -->
                <div class="user-menu-btn" onclick="window.location.href='<?= url('/profile') ?>'">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span style="font-size: 0.88rem; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= e($currentUser['name'] ?? 'User') ?>
                    </span>
                    <span class="badge badge-info" style="font-size: 0.68rem;">
                        <?= e(strtoupper(str_replace('_', ' ', $currentUser['role_slug'] ?? 'User'))) ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Flash Messages & Toasts -->
        <?php if ($msg = flash('success')): ?>
            <div class="toast-container">
                <div class="toast toast-success">
                    <i class="bx bx-check-circle" style="font-size: 1.25rem;"></i>
                    <div style="flex:1; font-size: 0.9rem;"><?= e($msg) ?></div>
                    <button style="background:none; border:none; color:var(--text-muted); cursor:pointer;" onclick="this.parentElement.remove();">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('error')): ?>
            <div class="toast-container">
                <div class="toast toast-danger">
                    <i class="bx bx-error-circle" style="font-size: 1.25rem;"></i>
                    <div style="flex:1; font-size: 0.9rem;"><?= e($msg) ?></div>
                    <button style="background:none; border:none; color:var(--text-muted); cursor:pointer;" onclick="this.parentElement.remove();">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('warning')): ?>
            <div class="toast-container">
                <div class="toast toast-warning">
                    <i class="bx bx-alarm-exclamation" style="font-size: 1.25rem;"></i>
                    <div style="flex:1; font-size: 0.9rem;"><?= e($msg) ?></div>
                    <button style="background:none; border:none; color:var(--text-muted); cursor:pointer;" onclick="this.parentElement.remove();">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Body -->
        <main class="app-content">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
