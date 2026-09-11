<div class="page-header">
    <div>
        <h1 class="page-title">Faculty Portal</h1>
        <p class="page-subtitle">Welcome back, <?= e($currentUser['name']) ?>. Here is your academic teaching overview.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bx bx-book-bookmark"></i></div>
        <div>
            <div class="stat-value"><?= count($courses) ?></div>
            <div class="stat-label">Assigned Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="bx bx-group"></i></div>
        <div>
            <div class="stat-value"><?= count($batches) ?></div>
            <div class="stat-label">Assigned Batches</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bx bxs-user-detail"></i></div>
        <div>
            <div class="stat-value"><?= number_format($totalStudents) ?></div>
            <div class="stat-label">Active Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="bx bx-edit-alt"></i></div>
        <div>
            <div class="stat-value"><?= $pendingGradingCount ?></div>
            <div class="stat-label">Submissions to Grade</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem;">
    <!-- Assigned Courses -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-book"></i> My Assigned Courses</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Units</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No courses assigned to you yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td>
                                    <strong><a href="<?= url('/teacher/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a></strong>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($c['code']) ?></div>
                                </td>
                                <td><?= $c['unit_count'] ?> Units</td>
                                <td><span class="badge badge-success"><?= ucfirst($c['status']) ?></span></td>
                                <td>
                                    <a href="<?= url('/teacher/courses/' . $c['id']) ?>" class="btn btn-outline btn-sm">Manage Content</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assigned Batches -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-group"></i> My Assigned Batches</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Code</th>
                        <th>Students</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No batches assigned to you yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><strong><?= e($b['name']) ?></strong></td>
                                <td><code><?= e($b['code']) ?></code></td>
                                <td><span class="badge badge-info"><?= $b['student_count'] ?> Students</span></td>
                                <td>
                                    <a href="<?= url('/admin/attendance?batch_id=' . $b['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-calendar-check"></i> Mark</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pending Submissions -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <div class="card-title"><i class="bx bx-time-five"></i> Pending Submissions Needing Review</div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Assessment / Assignment</th>
                    <th>Student</th>
                    <th>Submitted At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingAssessments) && empty($pendingAssignments)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">All submissions are up to date! Nothing pending grading.</td></tr>
                <?php else: ?>
                    <?php foreach ($pendingAssessments as $pa): ?>
                        <tr>
                            <td><span class="badge badge-warning">Quiz Attempt</span></td>
                            <td><strong><?= e($pa['assessment_title']) ?></strong></td>
                            <td><?= e($pa['student_name']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($pa['submitted_at']) ?></td>
                            <td>
                                <a href="<?= url('/teacher/grading/assessment/' . $pa['id']) ?>" class="btn btn-primary btn-sm"><i class="bx bx-pencil"></i> Grade</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($pendingAssignments as $pas): ?>
                        <tr>
                            <td><span class="badge badge-info">Assignment</span></td>
                            <td><strong><?= e($pas['assignment_title']) ?></strong></td>
                            <td><?= e($pas['student_name']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($pas['submitted_at']) ?></td>
                            <td>
                                <a href="<?= url('/teacher/grading/assignment/' . $pas['id']) ?>" class="btn btn-primary btn-sm"><i class="bx bx-pencil"></i> Grade</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
