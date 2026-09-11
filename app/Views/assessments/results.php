<div class="page-header">
    <div>
        <h1 class="page-title">Assessment Results: <?= e($attempt['assessment_title']) ?></h1>
        <p class="page-subtitle"><?= e($attempt['course_title']) ?> | Student: <?= e($attempt['student_name']) ?></p>
    </div>
    <div>
        <a href="<?= url('/assessments') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Return to Assessments</a>
    </div>
</div>

<div style="max-width: 850px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Score Card -->
    <div class="card" style="text-align: center; padding: 2.5rem;">
        <?php $passed = ($attempt['score'] >= $attempt['pass_marks']); ?>
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: <?= $passed ? 'var(--success-light)' : 'var(--danger-light)' ?>; color: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>; font-size: 2.5rem; margin-bottom: 1rem;">
            <i class="bx <?= $passed ? 'bx-check' : 'bx-x' ?>"></i>
        </div>

        <h2 style="margin: 0; font-size: 2rem;"><?= $attempt['score'] ?> / <?= $attempt['max_possible'] ?></h2>
        <p style="margin-top: 0.5rem; font-size: 1.1rem; font-weight: 600; color: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= $passed ? 'Passed Examination' : 'Did Not Meet Passing Criteria (' . $attempt['pass_marks'] . ' Marks required)' ?>
        </p>
        <span style="font-size: 0.85rem; color: var(--text-muted);">Completed at <?= e($attempt['completed_at']) ?></span>
    </div>

    <!-- Question by Question Review -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">Question Breakdown</h3>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($answers as $idx => $ans): ?>
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-weight: 700;">Question <?= ($idx + 1) ?></span>
                        <span class="badge <?= $ans['is_correct'] ? 'badge-success' : 'badge-danger' ?>">
                            <?= $ans['marks_awarded'] ?> Marks (<?= $ans['is_correct'] ? 'Correct' : 'Incorrect' ?>)
                        </span>
                    </div>

                    <div style="font-weight: 500; margin-bottom: 0.75rem;">
                        <?= nl2br(e($ans['question'])) ?>
                    </div>

                    <div style="background: var(--bg-hover); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <strong>Your Answer:</strong> <?= e($ans['user_answer'] ?: 'No answer submitted') ?>
                    </div>

                    <?php if (!empty($ans['explanation'])): ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                            <strong>Explanation:</strong> <?= e($ans['explanation']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
