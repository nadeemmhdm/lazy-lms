<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-award"></i> Examinations Management</h1>
        <p class="page-subtitle">Configure formal exams with server-side timer, MCQ, short answer, and file upload submissions.</p>
    </div>
    <div>
        <a href="<?= url('/exams/create') ?>" class="btn btn-primary"><i class="bx bx-plus"></i> Schedule New Exam</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Exam Title</th>
                    <th>Course & Batch</th>
                    <th>Schedule</th>
                    <th>Duration</th>
                    <th>Marks & Pass</th>
                    <th>Submissions</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No examinations configured yet. Click "Schedule New Exam" to create one.</td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $e): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= url('/exams/' . ($e['public_id'] ?? $e['id'])) ?>" style="text-decoration: none; color: inherit;"><?= e($e['title']) ?></a></strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $e['question_count'] ?> Question(s)</div>
                            </td>
                            <td>
                                <?= e($e['course_title']) ?><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($e['batch_name'] ?? 'All Batches') ?></span>
                            </td>
                            <td style="font-size: 0.85rem;">
                                <?= date('d M Y, h:i A', strtotime($e['start_time'])) ?><br>
                                <span style="color: var(--text-muted);">to <?= date('d M Y, h:i A', strtotime($e['end_time'])) ?></span>
                            </td>
                            <td><span class="badge badge-outline"><?= $e['duration_minutes'] ?> mins</span></td>
                            <td>
                                <strong><?= $e['total_marks'] ?> marks</strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Pass: <?= $e['passing_score'] ?>%</span>
                            </td>
                            <td>
                                <span class="badge <?= $e['pending_grading'] > 0 ? 'badge-warning' : 'badge-primary' ?>">
                                    <?= $e['total_attempts'] ?> Total (<?= $e['pending_grading'] ?> to grade)
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/exams/' . ($e['public_id'] ?? $e['id'])) ?>" class="btn btn-outline btn-sm">
                                    <i class="bx bx-show"></i> View Attempts
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
