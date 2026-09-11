<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($assessment['title']) ?></h1>
        <p class="page-subtitle"><?= e($assessment['course_title']) ?> | Attempt #<?= $attemptNumber ?> | Pass Mark: <?= $assessment['pass_marks'] ?>/<?= $assessment['total_marks'] ?></p>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="background: var(--bg-card); border: 2px solid var(--primary); padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bx bx-time" style="color: var(--primary);"></i>
            <span id="countdown-timer">--:--</span>
        </div>
    </div>
</div>

<form action="<?= url('/assessments/' . $assessment['id'] . '/submit') ?>" method="POST" id="assessment-exam-form">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="time_spent" id="time_spent_input" value="0">

    <div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 850px; margin: 0 auto;">
        <?php foreach ($questions as $idx => $q): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="font-weight: 700; color: var(--primary);">Question <?= ($idx + 1) ?> of <?= count($questions) ?></span>
                    <span class="badge badge-info"><?= $q['q_marks'] ?> Marks</span>
                </div>

                <div style="font-size: 1.05rem; font-weight: 500; margin-bottom: 1.25rem;">
                    <?= nl2br(e($q['question'])) ?>
                </div>

                <!-- Single Choice / True False -->
                <?php if ($q['type'] === 'single_choice' || $q['type'] === 'true_false'): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($q['options'] as $opt): ?>
                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; transition: background var(--transition-fast);" class="exam-option-label">
                                <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>">
                                <span><?= e($opt['option_text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <!-- Multiple Choice -->
                <?php elseif ($q['type'] === 'multiple_choice'): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($q['options'] as $opt): ?>
                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer;" class="exam-option-label">
                                <input type="checkbox" name="answers[<?= $q['id'] ?>][]" value="<?= $opt['id'] ?>">
                                <span><?= e($opt['option_text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <!-- Short Answer -->
                <?php elseif ($q['type'] === 'short_answer'): ?>
                    <div class="form-group">
                        <input type="text" name="answers[<?= $q['id'] ?>]" class="form-control" placeholder="Type your answer...">
                    </div>

                <!-- Numeric Answer -->
                <?php elseif ($q['type'] === 'numeric'): ?>
                    <div class="form-group">
                        <input type="number" step="any" name="answers[<?= $q['id'] ?>]" class="form-control" placeholder="Enter numeric value (e.g. 10.5, -2, 0.75)">
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem; margin-bottom: 3rem;">
            <button type="submit" class="btn btn-primary btn-lg" id="submit-assessment-btn" onclick="return confirm('Submit your assessment answers now?');">
                <i class="bx bx-check-double"></i> Submit Assessment
            </button>
        </div>
    </div>
</form>

<script>
// Timer implementation
let durationSeconds = <?= (int)$assessment['time_limit'] * 60 ?>;
let secondsLeft = durationSeconds;
let timerDisplay = document.getElementById('countdown-timer');
let timeSpentInput = document.getElementById('time_spent_input');
let form = document.getElementById('assessment-exam-form');

let timerInterval = setInterval(function() {
    secondsLeft--;
    let timeSpent = durationSeconds - secondsLeft;
    timeSpentInput.value = timeSpent;

    let mins = Math.floor(secondsLeft / 60);
    let secs = secondsLeft % 60;
    timerDisplay.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

    if (secondsLeft <= 0) {
        clearInterval(timerInterval);
        alert('Time is up! Submitting your answers automatically.');
        form.submit();
    }
}, 1000);
</script>
