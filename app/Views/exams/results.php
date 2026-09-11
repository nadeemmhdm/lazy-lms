<?php
/**
 * Lazy LMS - Student Exam Result Sheet
 */
$examId = $exam['public_id'] ?? $exam['id'];
$isGraded = ($attempt['status'] === 'graded');
$isPassed = !empty($attempt['is_passed']);
$scorePercent = ($exam['total_marks'] > 0) ? round(((float)$attempt['score'] / (float)$exam['total_marks']) * 100, 1) : 0;
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="bx bx-receipt"></i> Examination Result</h1>
            <p class="page-subtitle"><?= e($exam['title']) ?> &bull; <?= e($exam['course_title']) ?></p>
        </div>
        <div>
            <a href="<?= url('/exams') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Exams</a>
        </div>
    </div>

    <div class="card" style="text-align: center; padding: 2.5rem 1.5rem; margin-bottom: 2rem;">
        <?php if ($isGraded && $isPassed): ?>
            <i class="bx bx-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 0.5rem;"></i>
            <h2 style="margin-bottom: 0.25rem;">Congratulations! You Passed</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Attempt #<?= $attempt['attempt_number'] ?> was completed and evaluated successfully.</p>
        <?php elseif ($isGraded): ?>
            <i class="bx bx-x-circle" style="font-size: 4rem; color: var(--danger); margin-bottom: 0.5rem;"></i>
            <h2 style="margin-bottom: 0.25rem;">Did Not Meet Passing Mark</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Passing score required: <?= $exam['passing_score'] ?>%</p>
        <?php else: ?>
            <i class="bx bx-time" style="font-size: 4rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
            <h2 style="margin-bottom: 0.25rem;">Exam Submitted (Pending Evaluation)</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Your answers have been securely recorded. Descriptive sections are currently under teacher evaluation.</p>
        <?php endif; ?>

        <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
            <div style="background: var(--bg-hover, #f8fafc); padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">Your Score</span>
                <strong style="font-size: 1.5rem; color: var(--primary);"><?= $attempt['score'] ?> / <?= $exam['total_marks'] ?></strong>
            </div>
            <div style="background: var(--bg-hover, #f8fafc); padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">Percentage</span>
                <strong style="font-size: 1.5rem;"><?= $scorePercent ?>%</strong>
            </div>
            <div style="background: var(--bg-hover, #f8fafc); padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">Passing Benchmark</span>
                <strong style="font-size: 1.5rem;"><?= $exam['passing_score'] ?>%</strong>
            </div>
        </div>

        <?php if (!empty($attempt['teacher_feedback'])): ?>
            <div style="margin-top: 2rem; text-align: left; background: var(--bg-hover, #f8fafc); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--primary);">
                <h4 style="margin: 0 0 0.5rem 0;"><i class="bx bx-comment-detail"></i> Teacher Feedback</h4>
                <p style="margin: 0; line-height: 1.6; color: var(--text-main);"><?= nl2br(e($attempt['teacher_feedback'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
