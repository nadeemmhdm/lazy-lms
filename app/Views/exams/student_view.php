<?php
/**
 * Lazy LMS - Student Exam Overview & Launchpad
 */
$maxAttempts = (int)($exam['max_attempts'] ?: 1);
$attemptCount = count($attempts);
$remainingAttempts = max(0, $maxAttempts - $attemptCount);
$now = time();
$start = strtotime($exam['start_time']);
$end = strtotime($exam['end_time']);
$isOpen = ($now >= $start && $now <= $end);
$canStart = $isOpen && ($remainingAttempts > 0);
$examId = $exam['public_id'] ?? $exam['id'];
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= url('/exams') ?>" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="bx bx-arrow-back"></i> Examinations
            </a>
            <span style="color: var(--text-muted);">&bull;</span>
            <span style="color: var(--text-muted); font-size: 0.9rem;"><?= e($exam['course_title']) ?></span>
        </div>
        <h1 class="page-title"><?= e($exam['title']) ?></h1>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- Main Instructions & Start Button -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bx bx-info-circle text-primary"></i> Examination Instructions
            </h3>
            <div style="line-height: 1.6; color: var(--text-main); font-size: 0.95rem; white-space: pre-wrap;">
                <?= !empty($exam['instructions']) ? e($exam['instructions']) : '<em>Please answer all questions thoroughly before time expires. The system automatically records answers and submits upon timer expiration.</em>' ?>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-hover, #f8fafc); border-radius: 8px; border-left: 4px solid var(--warning);">
                <strong>Important Examination Rules:</strong>
                <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    <li>Once started, the examination timer runs continuously on the server for <?= $exam['duration_minutes'] ?> minutes.</li>
                    <li>If you lose connection or close the browser, your answers are auto-saved and you can resume where you left off.</li>
                    <li>When the timer expires, all saved answers are automatically submitted.</li>
                </ul>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <?php if ($canStart): ?>
                    <a href="<?= url('/exams/' . $examId . '/take') ?>" class="btn btn-primary btn-lg" onclick="return confirm('Start examination now? The timer will begin immediately.');">
                        <i class="bx bx-pencil"></i> Start Examination (Attempt <?= $attemptCount + 1 ?>/<?= $maxAttempts ?>)
                    </a>
                <?php elseif ($attemptCount >= $maxAttempts): ?>
                    <button class="btn btn-outline btn-lg" disabled>
                        <i class="bx bx-check-double"></i> Maximum Attempts Completed
                    </button>
                <?php elseif ($now < $start): ?>
                    <button class="btn btn-outline btn-lg" disabled>
                        <i class="bx bx-time"></i> Opens <?= date('d M Y, h:i A', $start) ?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline btn-lg" disabled>
                        <i class="bx bx-lock-alt"></i> Examination Closed
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($attempts)): ?>
            <div class="card">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-history text-primary"></i> Your Attempt History
                </h3>
                <div style="display: grid; gap: 0.75rem;">
                    <?php foreach ($attempts as $att): ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>Attempt #<?= $att['attempt_number'] ?></strong>
                                <span style="margin-left: 0.5rem;" class="badge <?= $att['status'] === 'graded' ? 'badge-success' : 'badge-primary' ?>"><?= ucfirst($att['status']) ?></span>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    Completed: <?= date('d M Y, H:i', strtotime($att['submitted_at'] ?: $att['started_at'])) ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <strong><?= $att['status'] === 'graded' ? $att['score'] . ' / ' . $exam['total_marks'] : 'Pending Review' ?></strong>
                                <a href="<?= url('/exams/results/' . ($att['public_id'] ?? $att['id'])) ?>" class="btn btn-outline btn-sm">
                                    <i class="bx bx-show"></i> View Result
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Rules & Info -->
    <div class="card">
        <h3 style="font-size: 1.05rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <i class="bx bx-bar-chart-alt-2"></i> Exam Details
        </h3>
        <div style="display: grid; gap: 0.75rem; font-size: 0.9rem;">
            <div>
                <span style="color: var(--text-muted);">Duration:</span><br>
                <strong><?= $exam['duration_minutes'] ?> Minutes</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Total Marks:</span><br>
                <strong><?= $exam['total_marks'] ?> marks</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Passing Score:</span><br>
                <strong><?= $exam['passing_score'] ?>%</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Open Window:</span><br>
                <strong><?= date('d M Y, h:i A', $start) ?></strong><br>
                <span style="color: var(--text-muted);">to <?= date('d M Y, h:i A', $end) ?></span>
            </div>
            <div>
                <span style="color: var(--text-muted);">Attempts Remaining:</span><br>
                <strong><?= $remainingAttempts ?> of <?= $maxAttempts ?></strong>
            </div>
        </div>
    </div>
</div>
