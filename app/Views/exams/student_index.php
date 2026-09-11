<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-award"></i> My Examinations</h1>
        <p class="page-subtitle">Formal examinations scheduled for your enrolled courses.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Examination</th>
                    <th>Course</th>
                    <th>Schedule Window</th>
                    <th>Duration</th>
                    <th>Total Marks</th>
                    <th>Status / Result</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No examinations currently scheduled for your courses.</td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $e): ?>
                        <?php 
                        $now = time();
                        $start = strtotime($e['start_time']);
                        $end = strtotime($e['end_time']);
                        $isOpen = ($now >= $start && $now <= $end);
                        $isUpcoming = ($now < $start);
                        $isClosed = ($now > $end);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($e['title']) ?></strong>
                            </td>
                            <td><?= e($e['course_title']) ?></td>
                            <td style="font-size: 0.85rem;">
                                <?= date('d M Y, h:i A', $start) ?><br>
                                <span style="color: var(--text-muted);">to <?= date('d M Y, h:i A', $end) ?></span>
                            </td>
                            <td><span class="badge badge-outline"><?= $e['duration_minutes'] ?> mins</span></td>
                            <td><strong><?= $e['total_marks'] ?> marks</strong></td>
                            <td>
                                <?php if (!empty($e['active_attempt_id'])): ?>
                                    <span class="badge badge-warning"><i class="bx bx-time"></i> In Progress</span>
                                <?php elseif ($e['student_attempts'] > 0): ?>
                                    <span class="badge <?= !empty($e['latest_passed']) ? 'badge-success' : 'badge-primary' ?>">
                                        <?= !empty($e['latest_passed']) ? 'Passed' : 'Submitted' ?> (Score: <?= $e['latest_score'] ?? 'Pending' ?>)
                                    </span>
                                <?php elseif ($isOpen): ?>
                                    <span class="badge badge-success"><i class="bx bx-check-circle"></i> Open Now</span>
                                <?php elseif ($isUpcoming): ?>
                                    <span class="badge badge-outline">Upcoming</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Closed</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if (!empty($e['active_attempt_id'])): ?>
                                    <a href="<?= url('/exams/' . ($e['public_id'] ?? $e['id']) . '/take') ?>" class="btn btn-warning btn-sm">
                                        <i class="bx bx-play"></i> Resume Exam
                                    </a>
                                <?php elseif ($isOpen && ($e['student_attempts'] < (int)$e['max_attempts'])): ?>
                                    <a href="<?= url('/exams/' . ($e['public_id'] ?? $e['id']) . '/take') ?>" class="btn btn-primary btn-sm">
                                        <i class="bx bx-pencil"></i> Start Exam
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('/exams/' . ($e['public_id'] ?? $e['id'])) ?>" class="btn btn-outline btn-sm">
                                        <i class="bx bx-show"></i> View Details
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
