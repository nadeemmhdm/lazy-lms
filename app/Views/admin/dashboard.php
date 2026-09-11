<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-tachometer"></i> Admin Dashboard</h1>
        <p class="page-subtitle">Unified administrative control center for Lazy LMS.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?= url('/classes/create') ?>" class="btn btn-primary btn-sm"><i class="bx bx-video"></i> Schedule Class</a>
        <a href="<?= url('/exams/create') ?>" class="btn btn-outline btn-sm"><i class="bx bx-award"></i> Schedule Exam</a>
        <a href="<?= url('/platform-notifications/create') ?>" class="btn btn-outline btn-sm"><i class="bx bx-broadcast"></i> Broadcast</a>
        <a href="<?= url('/students/create') ?>" class="btn btn-outline btn-sm"><i class="bx bx-user-plus"></i> Enroll Student</a>
    </div>
</div>

<!-- Stat Cards (Section 41) -->
<div class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bx bxs-user-detail"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['totalStudents']) ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="bx bx-user-pin"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['totalTeachers']) ?></div>
            <div class="stat-label">Total Faculty</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bx bx-group"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['totalBatches']) ?></div>
            <div class="stat-label">Total Batches</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="bx bx-book-bookmark"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['totalCourses']) ?></div>
            <div class="stat-label">Total Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="bx bx-task"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['pendingAssignments']) ?></div>
            <div class="stat-label">Pending Assignments</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="bx bx-edit"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['pendingExamGrading']) ?></div>
            <div class="stat-label">Pending Exam Grading</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bx bx-video"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['upcomingClasses']) ?></div>
            <div class="stat-label">Upcoming Classes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="bx bx-award"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['upcomingExams']) ?></div>
            <div class="stat-label">Upcoming Exams</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="bx bx-bell"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['unreadNotifications']) ?></div>
            <div class="stat-label">Active Broadcasts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bx bx-certification"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['totalCertificates']) ?></div>
            <div class="stat-label">Certificates Issued</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Active Batches -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-group"></i> Active Academic Batches</div>
            <a href="<?= url('/admin/batches') ?>" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Name</th>
                        <th>Students</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No active batches created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><strong><a href="<?= url('/admin/batches/' . ($b['public_id'] ?? $b['id'])) ?>"><?= e($b['code']) ?></a></strong></td>
                                <td><?= e($b['name']) ?></td>
                                <td><span class="badge badge-info"><?= $b['student_count'] ?> Enrolled</span></td>
                                <td><span class="badge badge-success"><?= ucfirst($b['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recently Enrolled Students -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bxs-user-detail"></i> Enrolled Students</div>
            <a href="<?= url('/admin/students') ?>" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentStudents)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No students registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentStudents as $s): ?>
                            <tr>
                                <td><strong><a href="<?= url('/admin/students/' . ($s['public_id'] ?? $s['id'])) ?>"><?= e($s['name']) ?></a></strong></td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($s['email']) ?></td>
                                <td><span class="badge badge-outline"><?= e($s['batch_name'] ?? 'Unassigned') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Security & Authentication Events (Section 35 & 41) -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="bx bx-shield-quarter"></i> Recent Security & Authentication Events</div>
        <a href="<?= url('/admin/sessions') ?>" class="btn btn-outline btn-sm">Active Sessions</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Security Event</th>
                    <th>IP Address</th>
                    <th>Browser / Device</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentSecurityEvents)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No recent security incidents logged.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentSecurityEvents as $se): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d M Y, H:i:s', strtotime($se['created_at'])) ?></td>
                            <td>
                                <strong><?= e($se['user_name'] ?? 'Guest / Anonymous') ?></strong>
                                <?php if (!empty($se['user_email'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($se['user_email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $evType = $se['event_type'];
                                $badgeClass = str_contains($evType, 'fail') ? 'badge-danger' : (str_contains($evType, 'new_ip') ? 'badge-warning' : 'badge-success');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($evType) ?></span>
                            </td>
                            <td><code><?= e($se['ip_address']) ?></code></td>
                            <td style="font-size: 0.8rem; color: var(--text-muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= e($se['user_agent'] ?? 'Unknown') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
