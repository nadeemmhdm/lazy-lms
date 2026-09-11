<div class="page-header">
    <div>
        <h1 class="page-title">Assessment Builder: <?= e($assessment['title']) ?></h1>
        <p class="page-subtitle">Course: <?= e($assessment['course_title']) ?> | Total Marks: <?= $assessment['total_marks'] ?> | Time Limit: <?= $assessment['time_limit'] ?>m</p>
    </div>
    <div>
        <a href="<?= url('/assessments') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Done & Return</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Configured Questions -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: space-between;">
            <span>Included Questions (<?= count($questions) ?>)</span>
            <span class="badge badge-info"><?= array_sum(array_column($questions, 'marks')) ?> Marks Total</span>
        </h3>

        <?php if (empty($questions)): ?>
            <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                <i class="bx bx-layer" style="font-size: 3rem; margin-bottom: 0.5rem; display: block;"></i>
                No questions added to this assessment yet.<br>Select questions from the right panel to include them.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($questions as $idx => $q): ?>
                    <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; background: var(--bg-card); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                <?= ($idx + 1) ?>. <?= e(strip_tags($q['question'])) ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem; font-size: 0.8rem;">
                                <span class="badge badge-neutral"><?= e(str_replace('_', ' ', $q['type'])) ?></span>
                                <span class="badge badge-info"><?= $q['marks'] ?> Marks</span>
                                <span style="color: var(--text-muted); align-self: center;"><?= e($q['category']) ?></span>
                            </div>
                        </div>
                        <form action="<?= url('/assessments/' . $assessment['id'] . '/questions/' . $q['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Remove question from assessment?');">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Question Bank Picker -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem;">Pick From Bank</h3>

        <?php if (empty($bankQuestions)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No bank questions available. <a href="<?= url('/question-bank/create') ?>">Create questions first</a>.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 550px; overflow-y: auto; padding-right: 0.25rem;">
                <?php foreach ($bankQuestions as $bq): ?>
                    <form action="<?= url('/assessments/' . $assessment['id'] . '/questions') ?>" method="POST" style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem;">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="question_id" value="<?= $bq['id'] ?>">
                        <div style="font-size: 0.88rem; font-weight: 500; margin-bottom: 0.35rem;">
                            <?= e(substr(strip_tags($bq['question']), 0, 75)) ?>...
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($bq['type']) ?></span>
                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                <input type="number" step="0.5" name="marks" value="<?= $bq['marks'] ?>" style="width: 50px; padding: 0.2rem; font-size: 0.8rem;" class="form-control" title="Marks">
                                <button type="submit" class="btn btn-primary btn-sm" title="Add to Assessment"><i class="bx bx-plus"></i></button>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
