<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($assignment['title']) ?></h1>
        <p class="page-subtitle"><?= e($assignment['course_title']) ?> | Type: <?= strtoupper($assignment['assignment_type'] ?? 'DESCRIPTIVE') ?> | Max Marks: <?= $assignment['max_marks'] ?> | Due: <?= date('d M Y, h:i A', strtotime($assignment['due_date'])) ?></p>
    </div>
    <div>
        <a href="<?= url('/assignments') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Assignments</a>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <h3 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="bx bx-file text-primary"></i> Instructions & Rubric
    </h3>
    <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); white-space: pre-wrap;">
        <?= !empty($assignment['instructions']) ? e($assignment['instructions']) : '<em>No additional written instructions provided.</em>' ?>
    </div>
</div>

<?php if ($assignment['assignment_type'] === 'mcq' && !empty($questions)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bx bx-list-check text-primary"></i> MCQ Questions (<?= count($questions) ?>)
        </h3>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($questions as $idx => $q): ?>
                <div style="padding: 0.75rem 1rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <strong>Q<?= $idx + 1 ?>: <?= e($q['question_text']) ?></strong>
                        <span class="badge badge-outline"><?= $q['marks'] ?> marks</span>
                    </div>
                    <div style="font-size: 0.85rem; display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.4rem;">
                        <?php foreach ($q['options'] as $opt): ?>
                            <span style="<?= !empty($opt['is_correct']) ? 'color: var(--success); font-weight: 600;' : 'color: var(--text-muted);' ?>">
                                <?= !empty($opt['is_correct']) ? '✔ ' : '○ ' ?><?= e($opt['option_text']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="bx bx-group text-primary"></i> Student Submissions (<?= count($submissions) ?>)
    </h3>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Attempt</th>
                    <th>Submitted At</th>
                    <th>Answer / Attachment</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No students have submitted this assignment yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td>
                                <strong><?= e($sub['student_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($sub['student_email']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-outline">Attempt #<?= $sub['attempt_number'] ?? 1 ?></span>
                                <?php if (!empty($sub['is_late'])): ?>
                                    <span class="badge badge-danger" style="margin-left: 0.25rem;">Late</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y, H:i', strtotime($sub['submitted_at'])) ?></td>
                            <td>
                                <?php if (!empty($sub['file_name'])): ?>
                                    <a href="<?= url('/submissions/download/' . ($sub['public_id'] ?? $sub['id'])) ?>" class="btn btn-outline btn-sm">
                                        <i class="bx bx-download"></i> <?= e($sub['file_name']) ?>
                                    </a>
                                <?php elseif (!empty($sub['submission_text'])): ?>
                                    <span style="font-size: 0.85rem; color: var(--text-main); font-family: monospace;">
                                        <?= e(mb_strimwidth($sub['submission_text'], 0, 45, '...')) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $sub['status'] === 'graded' ? 'badge-success' : 'badge-primary' ?>">
                                    <?= ucfirst($sub['status']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= $sub['grade'] !== null ? $sub['grade'] . ' / ' . $assignment['max_marks'] : 'Pending' ?></strong>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="openGradeModal(<?= $sub['id'] ?>, '<?= e($sub['student_name']) ?>', '<?= $sub['grade'] ?? '' ?>', <?= json_encode($sub['feedback'] ?? '') ?>, <?= json_encode($sub['submission_text'] ?? '') ?>)">
                                    <i class="bx bx-edit"></i> <?= $sub['status'] === 'graded' ? 'Regrade' : 'Grade' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Grade Modal -->
<div id="grade-modal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 550px; margin: 1rem;">
        <h3 id="grade-modal-title" style="margin-top: 0; margin-bottom: 1rem;">Grade Submission</h3>
        
        <div id="grade-modal-text" style="background: var(--bg-hover, #f8fafc); padding: 0.75rem; border-radius: 6px; font-size: 0.88rem; margin-bottom: 1rem; max-height: 160px; overflow-y: auto; white-space: pre-wrap; border: 1px solid var(--border-color);"></div>

        <form id="grade-form" action="" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Grade / Marks (Max: <?= $assignment['max_marks'] ?>) *</label>
                <input type="number" step="0.5" name="grade" id="grade-input" class="form-control" max="<?= $assignment['max_marks'] ?>" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teacher Feedback</label>
                <textarea name="feedback" id="feedback-input" class="form-control" rows="3" placeholder="Provide constructive feedback to the student..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeGradeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Grade</button>
            </div>
        </form>
    </div>
</div>

<script>
function openGradeModal(subId, name, grade, feedback, text) {
    document.getElementById('grade-modal-title').innerText = 'Grade Submission: ' + name;
    document.getElementById('grade-form').action = '<?= url("/assignments/submissions/") ?>' + subId + '/grade';
    document.getElementById('grade-input').value = grade || '';
    document.getElementById('feedback-input').value = feedback || '';
    document.getElementById('grade-modal-text').innerText = text || '(No written text submitted or MCQ submission)';
    document.getElementById('grade-modal').style.display = 'flex';
}

function closeGradeModal() {
    document.getElementById('grade-modal').style.display = 'none';
}
</script>
