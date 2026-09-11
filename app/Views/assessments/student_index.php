<div class="page-header">
    <div>
        <h1 class="page-title">My Quizzes & Assessments</h1>
        <p class="page-subtitle">Assessments assigned to your current courses and batches.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Course</th>
                    <th>Total Marks</th>
                    <th>Pass Marks</th>
                    <th>Duration</th>
                    <th>Attempts</th>
                    <th>Best Score</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assessments)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No active assessments found in your enrolled courses.</td></tr>
                <?php else: ?>
                    <?php foreach ($assessments as $a): ?>
                        <tr>
                            <td><strong><?= e($a['title']) ?></strong></td>
                            <td><?= e($a['course_title']) ?></td>
                            <td><?= $a['total_marks'] ?></td>
                            <td><?= $a['pass_marks'] ?></td>
                            <td><?= $a['time_limit'] ?> mins</td>
                            <td>
                                <?= $a['attempts_taken'] ?> / <?= $a['attempts_allowed'] > 0 ? $a['attempts_allowed'] : 'Unlimited' ?>
                            </td>
                            <td>
                                <?php if ($a['best_score'] !== null): ?>
                                    <span class="badge <?= $a['best_score'] >= $a['pass_marks'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $a['best_score'] ?> / <?= $a['total_marks'] ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Not taken</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($a['attempts_allowed'] == 0 || $a['attempts_taken'] < $a['attempts_allowed']): ?>
                                    <a href="<?= url('/assessments/' . $a['id'] . '/take') ?>" class="btn btn-primary btn-sm"><i class="bx bx-play-circle"></i> Take Quiz</a>
                                <?php else: ?>
                                    <span class="badge badge-neutral">Attempts Exhausted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
