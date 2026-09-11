<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/students') ?>">Students</a>
            <i class="bx bx-chevron-right"></i>
            <span><?= e($student['name']) ?></span>
        </div>
        <h1 class="page-title"><?= e($student['name']) ?></h1>
        <p class="page-subtitle">Student ID: <code><?= e($student['student_id'] ?: 'N/A') ?></code> &bull; Email: <?= e($student['email']) ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?= url('/admin/batches/change-student?student_id=' . $student['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-transfer"></i> Change Batch</a>
        <a href="<?= url('/admin/students/' . $student['id'] . '/edit') ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Edit Details</a>
        <?php if ($student['status'] === 'active'): ?>
            <form action="<?= url('/admin/students/' . $student['id'] . '/archive') ?>" method="POST" style="display:inline;" data-confirm="Archive this student? They will no longer be able to log in, but all academic history is kept.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--warning);"><i class="bx bx-archive"></i> Archive</button>
            </form>
        <?php else: ?>
            <form action="<?= url('/admin/students/' . $student['id'] . '/restore') ?>" method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--success);"><i class="bx bx-refresh"></i> Restore to Active</button>
            </form>
        <?php endif; ?>
        <form action="<?= url('/admin/students/' . $student['id'] . '/delete') ?>" method="POST" style="display:inline;" data-confirm="CRITICAL: Permanently delete this student and their authentication account? This cannot be undone.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-sm"><i class="bx bx-trash"></i> Permanent Delete</button>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: 280px 1fr; gap: 1.5rem; align-items: start;">
    <!-- Profile Summary Sidebar -->
    <div class="card">
        <div style="text-align: center; margin-bottom: 1.25rem;">
            <?php if ($student['avatar']): ?>
                <img src="<?= url('/download/file/' . $student['avatar']) ?>" alt="Avatar" style="width: 84px; height: 84px; border-radius: 50%; object-fit: cover; margin-bottom: 0.75rem;">
            <?php else: ?>
                <div class="user-avatar" style="width: 84px; height: 84px; font-size: 2.2rem; margin: 0 auto 0.75rem;">
                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <h3 style="font-size: 1.15rem; font-weight: 700;"><?= e($student['name']) ?></h3>
            <span class="badge <?= $student['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= ucfirst($student['status']) ?></span>
        </div>

        <div style="font-size: 0.88rem; display: flex; flex-direction: column; gap: 0.75rem; border-top: 1px solid var(--border); padding-top: 1rem;">
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.78rem;">Current Batch</span>
                <strong><?= e($student['batch_name'] ?? 'Unassigned') ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.78rem;">Phone</span>
                <?= e($student['phone'] ?: 'None provided') ?>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.78rem;">Last Login</span>
                <?= e($student['last_login_at'] ?? 'Never') ?>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.78rem;">Enrolled On</span>
                <?= e($student['created_at']) ?>
            </div>
        </div>
    </div>

    <!-- Academic Records -->
    <div>
        <!-- Courses & Progress -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-book-bookmark"></i> Enrolled Courses & Progress</div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Code</th>
                            <th style="width: 180px;">Completion Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No courses assigned through current batch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['title']) ?></strong></td>
                                    <td><code><?= e($c['code']) ?></code></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="progress-bar-container" style="flex:1;">
                                                <div class="progress-bar-fill" style="width: <?= min(100, $c['progress_percentage']) ?>%;"></div>
                                            </div>
                                            <span style="font-size: 0.8rem; font-weight: 600;"><?= round($c['progress_percentage']) ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($c['progress_percentage'] >= 100): ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assessment History -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-check-square"></i> Assessment Attempts & Scores</div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attempts)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No assessment attempts recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attempts as $att): ?>
                                <tr>
                                    <td><strong><?= e($att['assessment_title']) ?></strong> (Attempt #<?= $att['attempt_number'] ?>)</td>
                                    <td><?= $att['score'] ?> / <?= $att['max_score'] ?></td>
                                    <td><strong><?= round($att['percentage']) ?>%</strong></td>
                                    <td>
                                        <span class="badge <?= $att['is_passed'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $att['is_passed'] ? 'Passed' : 'Failed' ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.82rem; color: var(--text-muted);"><?= e($att['submitted_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Batch History Log -->
        <?php if (!empty($batchHistory)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bx bx-history"></i> Batch Transfer History</div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From Batch</th>
                                <th>To Batch</th>
                                <th>Mode</th>
                                <th>Authorized By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batchHistory as $bh): ?>
                                <tr>
                                    <td style="font-size: 0.82rem; color: var(--text-muted);"><?= e($bh['created_at']) ?></td>
                                    <td><?= e($bh['old_batch_name'] ?? 'Initial Enrollment') ?></td>
                                    <td><strong><?= e($bh['new_batch_name']) ?></strong></td>
                                    <td><span class="badge badge-neutral"><?= ucfirst($bh['transition_mode']) ?></span></td>
                                    <td><?= e($bh['changer_name'] ?? 'Admin') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
