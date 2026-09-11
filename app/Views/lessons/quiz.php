<?php
/**
 * Lazy LMS - Lesson Quiz View & Student Attempt Interface
 */
$maxAttempts = (int)($quiz['max_attempts'] ?: 3);
$attemptCount = count($attempts);
$remainingAttempts = max(0, $maxAttempts - $attemptCount);
$hasPassed = !empty($latestAttempt['is_passed']);
$canTake = ($user['role'] === 'student') && ($remainingAttempts > 0) && !$hasPassed;
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id'])) ?>" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="bx bx-arrow-back"></i> <?= e($lesson['title']) ?>
            </a>
            <span style="color: var(--text-muted);">&bull;</span>
            <span style="color: var(--text-muted); font-size: 0.9rem;"><?= e($lesson['course_title']) ?></span>
        </div>
        <h1 class="page-title"><i class="bx bx-help-circle"></i> <?= e($quiz['title']) ?></h1>
    </div>
    <div>
        <?php if ($hasPassed): ?>
            <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <i class="bx bx-check-circle"></i> Passed (Score: <?= $latestAttempt['score'] ?> / <?= $latestAttempt['max_score'] ?>)
            </span>
        <?php elseif ($latestAttempt): ?>
            <span class="badge badge-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <i class="bx bx-x-circle"></i> Last Score: <?= $latestAttempt['score'] ?> / <?= $latestAttempt['max_score'] ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- Main Quiz Questions or Attempt View -->
    <div>
        <?php if (!empty($quiz['instructions'])): ?>
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.05rem; margin-bottom: 0.5rem;"><i class="bx bx-info-circle text-primary"></i> Instructions</h3>
                <p style="margin: 0; line-height: 1.5; color: var(--text-main);"><?= nl2br(e($quiz['instructions'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($canTake): ?>
            <div class="card">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <span>Attempt <?= $attemptCount + 1 ?> of <?= $maxAttempts ?></span>
                    <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: normal;"><?= count($questions) ?> Questions</span>
                </h3>

                <form action="<?= url('/lesson-quizzes/' . ($quiz['public_id'] ?? $quiz['id']) . '/submit') ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                    <div style="display: grid; gap: 1.25rem;">
                        <?php foreach ($questions as $idx => $q): ?>
                            <div style="padding: 1rem; background: var(--bg-hover, #f8fafc); border: 1px solid var(--border-color); border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <strong>Question <?= $idx + 1 ?></strong>
                                    <span class="badge badge-outline"><?= $q['marks'] ?> marks</span>
                                </div>
                                <p style="margin-bottom: 0.75rem; font-size: 0.95rem;"><?= e($q['question_text']) ?></p>
                                
                                <div style="display: grid; gap: 0.4rem;">
                                    <?php foreach ($q['options'] as $opt): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 6px; background: #fff; border: 1px solid var(--border-color);">
                                            <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>" required>
                                            <span><?= e($opt['option_text']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Ready to submit your quiz answers?');">
                            <i class="bx bx-check-circle"></i> Submit Quiz Answers
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 2.5rem 1.5rem;">
                <?php if ($hasPassed): ?>
                    <i class="bx bx-check-circle" style="font-size: 3.5rem; color: var(--success); margin-bottom: 0.75rem;"></i>
                    <h3 style="margin-bottom: 0.5rem;">Quiz Completed!</h3>
                    <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 1.5rem auto;">
                        You have successfully passed this lesson quiz. Your lesson progress has been automatically marked as completed.
                    </p>
                    <a href="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id'])) ?>" class="btn btn-primary">
                        <i class="bx bx-arrow-back"></i> Return to Lesson
                    </a>
                <?php else: ?>
                    <i class="bx bx-lock-alt" style="font-size: 3.5rem; color: var(--text-muted); margin-bottom: 0.75rem;"></i>
                    <h3 style="margin-bottom: 0.5rem;">Quiz Locked</h3>
                    <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 1.5rem auto;">
                        <?= $user['role'] === 'student' ? 'You have used all ' . $maxAttempts . ' attempts for this quiz. Please contact your instructor for review.' : 'You are viewing this quiz in instructor review mode.' ?>
                    </p>
                    <a href="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id'])) ?>" class="btn btn-outline">
                        <i class="bx bx-arrow-back"></i> Return to Lesson
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Stats & History -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.05rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <i class="bx bx-bar-chart-alt-2"></i> Quiz Rules
            </h3>
            <div style="display: grid; gap: 0.75rem; font-size: 0.9rem;">
                <div>
                    <span style="color: var(--text-muted);">Passing Score:</span><br>
                    <strong><?= $quiz['passing_score'] ?>%</strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Max Attempts:</span><br>
                    <strong><?= $maxAttempts ?> attempts</strong> (<?= $remainingAttempts ?> remaining)
                </div>
                <div>
                    <span style="color: var(--text-muted);">Cooldown:</span><br>
                    <strong><?= $quiz['cooldown_minutes'] ? $quiz['cooldown_minutes'] . ' minutes' : 'None' ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($attempts)): ?>
            <div class="card">
                <h3 style="font-size: 1.05rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <i class="bx bx-history"></i> Your Past Attempts
                </h3>
                <div style="display: grid; gap: 0.75rem;">
                    <?php foreach ($attempts as $att): ?>
                        <div style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                <strong>Attempt #<?= $att['attempt_number'] ?></strong>
                                <span class="badge <?= !empty($att['is_passed']) ? 'badge-success' : 'badge-danger' ?>">
                                    <?= !empty($att['is_passed']) ? 'Passed' : 'Failed' ?>
                                </span>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                Score: <strong><?= $att['score'] ?> / <?= $att['max_score'] ?></strong> (<?= round(($att['score'] / max(1, $att['max_score'])) * 100, 1) ?>%)
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
                                <?= date('d M Y, H:i', strtotime($att['completed_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
