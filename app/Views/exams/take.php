<?php
/**
 * Lazy LMS - Secure Online Examination Engine
 * Server-side timer calculation, auto-save AJAX, auto-submit.
 */
$examId = $exam['public_id'] ?? $exam['id'];
?>

<div style="max-width: 1000px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Fixed Timer Header -->
    <div class="card" style="position: sticky; top: 10px; z-index: 99; margin-bottom: 1.5rem; background: var(--card-bg, #fff); box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-left: 4px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-award text-primary"></i> <?= e($exam['title']) ?>
                </h2>
                <span style="font-size: 0.85rem; color: var(--text-muted);"><?= count($questions) ?> Questions &bull; <?= $exam['total_marks'] ?> Total Marks</span>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div id="saveStatus" style="font-size: 0.8rem; color: var(--text-muted);">
                    <i class="bx bx-check-circle text-success"></i> Auto-saved
                </div>
                <div style="background: var(--bg-hover, #f1f5f9); padding: 0.5rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; border: 1px solid var(--border-color);">
                    <i class="bx bx-time-five" style="font-size: 1.25rem; color: var(--danger);"></i>
                    <span id="countdownTimer" style="font-size: 1.2rem; font-weight: 700; font-family: monospace; color: var(--danger);">--:--:--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Form -->
    <form action="<?= url('/exams/' . $examId . '/submit') ?>" method="POST" id="examForm" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="attempt_id" value="<?= $attempt['id'] ?>">

        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($questions as $idx => $q): ?>
                <?php 
                $qId = $q['id'];
                $savedVal = $savedAnswers[$qId] ?? null;
                ?>
                <div class="card" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-weight: 700; font-size: 1.05rem;">Question <?= $idx + 1 ?></span>
                        <span class="badge badge-outline"><?= $q['marks'] ?> marks</span>
                    </div>

                    <div style="font-size: 1rem; line-height: 1.6; margin-bottom: 1.25rem; color: var(--text-main); white-space: pre-wrap;"><?= e($q['question_text']) ?></div>

                    <?php if ($q['question_type'] === 'mcq'): ?>
                        <!-- MCQ Options -->
                        <?php 
                        $options = json_decode($q['options_json'] ?? '[]', true) ?: [];
                        ?>
                        <div style="display: grid; gap: 0.5rem;">
                            <?php foreach ($options as $oIdx => $optText): ?>
                                <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; background: #fff; transition: background 0.15s ease;" class="option-label">
                                    <input type="radio" name="answers[<?= $qId ?>]" value="<?= $oIdx ?>" class="exam-input" <?= ($savedVal !== null && (string)$savedVal === (string)$oIdx) ? 'checked' : '' ?>>
                                    <span><?= e($optText) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['question_type'] === 'short_answer'): ?>
                        <!-- Short Answer Text Area -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.85rem;">Your Written Answer:</label>
                            <textarea name="answers[<?= $qId ?>][text]" rows="4" class="form-control exam-input" placeholder="Type your short answer here..."><?= e(is_array($savedVal) ? ($savedVal['text'] ?? '') : (string)$savedVal) ?></textarea>
                        </div>

                    <?php else: ?>
                        <!-- Descriptive: Write / Upload -->
                        <?php $descMode = $q['descriptive_mode'] ?? 'both'; ?>
                        <?php if ($descMode === 'write' || $descMode === 'both'): ?>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.85rem;"><i class="bx bx-edit"></i> Write Answer Online:</label>
                                <textarea name="answers[<?= $qId ?>][text]" rows="6" class="form-control exam-input" placeholder="Type comprehensive written answer here..."><?= e(is_array($savedVal) ? ($savedVal['text'] ?? '') : (string)$savedVal) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if ($descMode === 'both'): ?>
                            <div style="text-align: center; margin: 0.75rem 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">— OR ATTACH ANSWER DOCUMENT —</div>
                        <?php endif; ?>

                        <?php if ($descMode === 'upload' || $descMode === 'both'): ?>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.85rem;"><i class="bx bx-upload"></i> Upload Answer File:</label>
                                <input type="file" name="answer_files[<?= $qId ?>]" class="form-control">
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Allowed formats: PDF, DOC, DOCX, ZIP, TXT, Images (Max 25MB)</span>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn btn-outline" id="manualSaveBtn">
                <i class="bx bx-save"></i> Save Draft
            </button>
            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Are you sure you want to finish and submit your exam?');">
                <i class="bx bx-check-double"></i> Submit Examination
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let secondsLeft = <?= max(0, (int)$secondsRemaining) ?>;
    const timerElem = document.getElementById('countdownTimer');
    const form = document.getElementById('examForm');
    const saveStatus = document.getElementById('saveStatus');
    const manualSaveBtn = document.getElementById('manualSaveBtn');

    function updateTimerDisplay() {
        if (secondsLeft <= 0) {
            timerElem.textContent = '00:00:00';
            saveStatus.innerHTML = '<span class="text-danger">Time expired! Auto-submitting...</span>';
            form.submit();
            return;
        }

        const h = Math.floor(secondsLeft / 3600);
        const m = Math.floor((secondsLeft % 3600) / 60);
        const s = secondsLeft % 60;
        timerElem.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        secondsLeft--;
    }

    updateTimerDisplay();
    const timerInterval = setInterval(updateTimerDisplay, 1000);

    // Auto-Save Intermediate Answers via AJAX
    async function performAutoSave() {
        saveStatus.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
        const formData = new FormData(form);
        try {
            const resp = await fetch('<?= url("/exams/" . $examId . "/auto-save") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            if (data.success) {
                saveStatus.innerHTML = `<i class="bx bx-check-circle text-success"></i> Saved at ${data.timestamp}`;
            } else {
                saveStatus.innerHTML = '<span class="text-danger">Save failed</span>';
            }
        } catch (e) {
            saveStatus.innerHTML = '<span class="text-warning">Offline</span>';
        }
    }

    // Auto-save every 30 seconds
    const autoSaveInterval = setInterval(performAutoSave, 30000);
    manualSaveBtn.addEventListener('click', performAutoSave);
});
</script>
