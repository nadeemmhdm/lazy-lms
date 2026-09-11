<?php
/**
 * Lazy LMS - Student Assignment Interface
 */
$maxAttempts = (int)($assignment['max_attempts'] ?: 1);
$attemptCount = count($submissions);
$remainingAttempts = max(0, $maxAttempts - $attemptCount);
$isLate = time() > strtotime($assignment['due_date']);
$canSubmit = ($remainingAttempts > 0) && (!$isLate || !empty($assignment['late_allowed']));
$assignmentId = $assignment['public_id'] ?? $assignment['id'];
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= url('/assignments') ?>" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="bx bx-arrow-back"></i> Assignments
            </a>
            <span style="color: var(--text-muted);">&bull;</span>
            <span style="color: var(--text-muted); font-size: 0.9rem;"><?= e($assignment['course_title']) ?></span>
        </div>
        <h1 class="page-title"><?= e($assignment['title']) ?></h1>
    </div>
    <div>
        <?php if ($submission && $submission['status'] === 'graded'): ?>
            <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <i class="bx bx-check-circle"></i> Graded: <?= $submission['grade'] ?> / <?= $assignment['max_marks'] ?>
            </span>
        <?php elseif ($submission): ?>
            <span class="badge badge-primary" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <i class="bx bx-time-five"></i> Submitted (Pending Review)
            </span>
        <?php else: ?>
            <span class="badge badge-warning" style="font-size: 1rem; padding: 0.5rem 1rem;">
                <i class="bx bx-error-circle"></i> Not Submitted
            </span>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- Main Brief & Submission Form -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bx bx-info-circle text-primary"></i> Assignment Brief & Instructions
            </h3>
            <div style="line-height: 1.6; color: var(--text-main); white-space: pre-wrap;">
                <?= !empty($assignment['instructions']) ? e($assignment['instructions']) : '<em>No additional written instructions provided.</em>' ?>
            </div>
        </div>

        <?php if ($canSubmit): ?>
            <div class="card">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-paper-plane text-primary"></i> 
                    <?= $attemptCount > 0 ? "Submit Next Attempt (" . ($attemptCount + 1) . "/{$maxAttempts})" : "Your Submission" ?>
                </h3>

                <form action="<?= url('/assignments/' . $assignmentId . '/submit') ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                    <?php if ($assignment['assignment_type'] === 'mcq'): ?>
                        <!-- MCQ Questions Form -->
                        <div style="display: grid; gap: 1.25rem;">
                            <?php foreach ($questions as $idx => $q): ?>
                                <div style="padding: 1rem; background: var(--bg-hover, #f8fafc); border: 1px solid var(--border-color); border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong>Question <?= $idx + 1 ?>:</strong>
                                        <span class="badge badge-outline"><?= $q['marks'] ?> marks</span>
                                    </div>
                                    <p style="margin-bottom: 0.75rem; font-size: 0.95rem;"><?= e($q['question_text']) ?></p>
                                    <div style="display: grid; gap: 0.4rem;">
                                        <?php foreach ($q['options'] as $opt): ?>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.4rem 0.6rem; border-radius: 4px; background: #fff; border: 1px solid var(--border-color);">
                                                <input type="radio" name="mcq_answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>" required>
                                                <span><?= e($opt['option_text']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <!-- Written Answer or File Upload -->
                        <?php 
                        $mode = $assignment['submission_mode'] ?? 'both'; 
                        $allowWrite = ($mode === 'write' || $mode === 'both' || $assignment['assignment_type'] === 'short_answer');
                        $allowUpload = ($mode === 'upload' || $mode === 'both') && ($assignment['assignment_type'] !== 'short_answer');
                        ?>

                        <?php if ($allowWrite): ?>
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between;">
                                    <span><i class="bx bx-edit"></i> Write Answer Online</span>
                                    <span style="font-weight: normal; font-size: 0.8rem; color: var(--text-muted);" id="wordCount">0 words</span>
                                </label>
                                <textarea name="text_content" id="answerTextArea" class="form-control" rows="8" placeholder="Type or paste your answer here..."></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if ($allowWrite && $allowUpload): ?>
                            <div style="text-align: center; margin: 1rem 0; position: relative;">
                                <hr style="border: 0; border-top: 1px solid var(--border-color);">
                                <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--card-bg, #fff); padding: 0 1rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">OR</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($allowUpload): ?>
                            <div class="form-group">
                                <label class="form-label"><i class="bx bx-upload"></i> Upload Answer File</label>
                                <input type="file" name="file" class="form-control">
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    <span>Allowed formats: <?= e($assignment['allowed_extensions'] ?? 'pdf,doc,docx,zip,txt') ?></span>
                                    <span>Max: <?= $assignment['max_file_size_mb'] ?? 25 ?> MB</span>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Ready to submit your assignment?');">
                            <i class="bx bx-check-double"></i> Submit Assignment
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <i class="bx bx-lock-alt" style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--text-muted);"></i>
                <h4 style="margin-bottom: 0.5rem;">Submissions Closed</h4>
                <p style="font-size: 0.9rem;">
                    <?= $remainingAttempts <= 0 ? "You have reached the maximum attempt limit ({$maxAttempts}/{$maxAttempts})." : "The deadline has passed and late submissions are not allowed." ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info & History -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.05rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <i class="bx bx-calendar-event"></i> Assignment Details
            </h3>
            <div style="display: grid; gap: 0.75rem; font-size: 0.9rem;">
                <div>
                    <span style="color: var(--text-muted);">Due Date:</span><br>
                    <strong><?= date('d M Y, h:i A', strtotime($assignment['due_date'])) ?></strong>
                    <?php if ($isLate): ?>
                        <span class="badge badge-danger" style="margin-left: 0.25rem;">Passed</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Maximum Marks:</span><br>
                    <strong><?= $assignment['max_marks'] ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Attempts Allowed:</span><br>
                    <strong><?= $maxAttempts ?> attempt(s)</strong> (<?= $remainingAttempts ?> remaining)
                </div>
                <div>
                    <span style="color: var(--text-muted);">Late Submissions:</span><br>
                    <strong><?= !empty($assignment['late_allowed']) ? 'Allowed' : 'Not Allowed' ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($submissions)): ?>
            <div class="card">
                <h3 style="font-size: 1.05rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <i class="bx bx-history"></i> Submission History
                </h3>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($submissions as $sub): ?>
                        <div style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                <strong>Attempt #<?= $sub['attempt_number'] ?></strong>
                                <span class="badge <?= $sub['status'] === 'graded' ? 'badge-success' : 'badge-outline' ?>">
                                    <?= ucfirst($sub['status']) ?>
                                </span>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 0.5rem;">
                                Submitted: <?= date('d M Y, H:i', strtotime($sub['submitted_at'])) ?>
                                <?= !empty($sub['is_late']) ? '<span class="text-danger">(Late)</span>' : '' ?>
                            </div>

                            <?php if ($sub['status'] === 'graded'): ?>
                                <div style="margin-top: 0.4rem; padding: 0.5rem; background: var(--bg-hover, #f8fafc); border-radius: 4px;">
                                    <strong>Score: <?= $sub['grade'] ?> / <?= $assignment['max_marks'] ?></strong>
                                    <?php if (!empty($sub['feedback'])): ?>
                                        <div style="margin-top: 0.25rem; color: var(--text-main);">Feedback: <?= e($sub['feedback']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sub['file_name'])): ?>
                                <div style="margin-top: 0.5rem;">
                                    <a href="<?= url('/submissions/download/' . ($sub['public_id'] ?? $sub['id'])) ?>" class="btn btn-sm btn-outline" style="width: 100%; justify-content: center;">
                                        <i class="bx bx-download"></i> Download Submitted File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const txt = document.getElementById('answerTextArea');
    const wc = document.getElementById('wordCount');
    if (txt && wc) {
        txt.addEventListener('input', () => {
            const count = txt.value.trim() ? txt.value.trim().split(/\s+/).length : 0;
            wc.textContent = `${count} words`;
        });
    }
});
</script>
