<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($exam['title']) ?></h1>
        <p class="page-subtitle"><?= e($exam['course_title']) ?> &bull; <?= e($exam['batch_name'] ?? 'All Batches') ?> &bull; <?= $exam['duration_minutes'] ?> Mins &bull; Total: <?= $exam['total_marks'] ?> Marks (Pass: <?= $exam['passing_score'] ?>%)</p>
    </div>
    <div>
        <a href="<?= url('/exams') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Exams</a>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <h3 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="bx bx-info-circle text-primary"></i> Examination Schedule & Questions
    </h3>
    <div style="display: flex; gap: 2rem; flex-wrap: wrap; font-size: 0.9rem; color: var(--text-main);">
        <div><strong>Starts:</strong> <?= date('d M Y, h:i A', strtotime($exam['start_time'])) ?></div>
        <div><strong>Ends:</strong> <?= date('d M Y, h:i A', strtotime($exam['end_time'])) ?></div>
        <div><strong>Total Questions:</strong> <?= count($questions) ?></div>
        <div><strong>Max Attempts:</strong> <?= $exam['max_attempts'] ?></div>
    </div>
    <?php if (!empty($exam['instructions'])): ?>
        <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted);">
            <?= nl2br(e($exam['instructions'])) ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="bx bx-group text-primary"></i> Student Exam Attempts (<?= count($attempts) ?>)
    </h3>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Batch</th>
                    <th>Attempt</th>
                    <th>Started / Submitted</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attempts)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No students have taken this examination yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($attempts as $att): ?>
                        <tr>
                            <td>
                                <strong><?= e($att['student_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($att['student_email']) ?></span>
                            </td>
                            <td><?= e($att['batch_name'] ?? 'N/A') ?></td>
                            <td><span class="badge badge-outline">Attempt #<?= $att['attempt_number'] ?></span></td>
                            <td style="font-size: 0.85rem;">
                                <?= date('d M Y, H:i', strtotime($att['started_at'])) ?><br>
                                <span style="color: var(--text-muted);"><?= !empty($att['submitted_at']) ? 'Finished ' . date('H:i', strtotime($att['submitted_at'])) : 'Active' ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $att['status'] === 'graded' ? 'badge-success' : ($att['status'] === 'submitted' ? 'badge-warning' : 'badge-primary') ?>">
                                    <?= ucfirst($att['status']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= $att['status'] !== 'in_progress' ? $att['score'] . ' / ' . $exam['total_marks'] : '-' ?></strong>
                                <?php if ($att['status'] === 'graded'): ?>
                                    <span class="badge <?= !empty($att['is_passed']) ? 'badge-success' : 'badge-danger' ?>" style="margin-left: 0.25rem;">
                                        <?= !empty($att['is_passed']) ? 'Passed' : 'Failed' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($att['status'] === 'submitted' || $att['status'] === 'graded'): ?>
                                    <a href="<?= url('/exams/' . ($exam['public_id'] ?? $exam['id']) . '/attempts/' . ($att['public_id'] ?? $att['id']) . '/grade') ?>" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> <?= $att['status'] === 'graded' ? 'Review Grade' : 'Grade Exam' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Exam in progress</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
