<?php
/**
 * Lazy LMS - Teacher Exam Grading Interface
 */
$examId = $exam['public_id'] ?? $exam['id'];
$attemptId = $attempt['public_id'] ?? $attempt['id'];
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= url('/exams/' . $examId) ?>" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="bx bx-arrow-back"></i> <?= e($exam['title']) ?>
            </a>
            <span style="color: var(--text-muted);">&bull;</span>
            <span style="color: var(--text-muted); font-size: 0.9rem;">Attempt #<?= $attempt['attempt_number'] ?></span>
        </div>
        <h1 class="page-title"><i class="bx bx-check-shield"></i> Grade Exam: <?= e($attempt['student_name']) ?></h1>
    </div>
    <div>
        <span class="badge <?= $attempt['status'] === 'graded' ? 'badge-success' : 'badge-warning' ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
            Status: <?= ucfirst($attempt['status']) ?> (Current Score: <?= $attempt['score'] ?> / <?= $exam['total_marks'] ?>)
        </span>
    </div>
</div>

<form action="<?= url('/exams/' . $examId . '/attempts/' . $attemptId . '/save-grade') ?>" method="POST">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Questions and Student Submissions Review -->
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($questions as $idx => $q): ?>
                <?php 
                $qId = $q['id'];
                $ans = $answers[$qId] ?? null;
                ?>
                <div class="card" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>Question <?= $idx + 1 ?>: <span class="badge badge-outline" style="text-transform: uppercase;"><?= str_replace('_', ' ', $q['question_type']) ?></span></strong>
                        <span class="badge badge-outline">Max: <?= $q['marks'] ?> marks</span>
                    </div>

                    <div style="font-size: 0.95rem; margin-bottom: 1rem; color: var(--text-main); font-weight: 500;">
                        <?= e($q['question_text']) ?>
                    </div>

                    <?php if ($q['question_type'] === 'mcq'): ?>
                        <!-- MCQ Auto Graded Details -->
                        <?php 
                        $options = json_decode($q['options_json'] ?? '[]', true) ?: [];
                        $userPick = (string)($ans ?? '');
                        $correctPick = (string)$q['correct_answer'];
                        $isCorrect = ($userPick === $correctPick);
                        ?>
                        <div style="padding: 0.75rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.88rem;">
                            <div><strong>Student Answer:</strong> <?= isset($options[$userPick]) ? e($options[$userPick]) : '<span class="text-danger">None / Blank</span>' ?></div>
                            <div style="margin-top: 0.25rem;"><strong>Correct Option:</strong> <?= e($options[$correctPick] ?? 'N/A') ?></div>
                            <div style="margin-top: 0.5rem;">
                                <span class="badge <?= $isCorrect ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $isCorrect ? "Auto-Graded: {$q['marks']} / {$q['marks']} marks" : "Auto-Graded: 0 / {$q['marks']} marks" ?>
                                </span>
                            </div>
                        </div>

                    <?php elseif ($q['question_type'] === 'short_answer'): ?>
                        <!-- Short Answer Written Text -->
                        <div style="padding: 0.75rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color);">
                            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Student Response:</label>
                            <div style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.5; color: var(--text-main);">
                                <?= !empty($ans['text']) ? e($ans['text']) : (is_string($ans) && trim($ans) !== '' ? e($ans) : '<em style="color: var(--text-muted);">No response entered.</em>') ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Descriptive Response -->
                        <div style="padding: 0.75rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color); display: grid; gap: 0.75rem;">
                            <?php if (!empty($ans['text']) || (is_string($ans) && trim($ans) !== '')): ?>
                                <div>
                                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Written Answer Online:</label>
                                    <div style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.5; color: var(--text-main);">
                                        <?= e(is_array($ans) ? ($ans['text'] ?? '') : $ans) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ans['file_id'])): ?>
                                <div>
                                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Uploaded Answer Document:</label>
                                    <a href="<?= url('/exams/submissions/download/' . $ans['file_id']) ?>" class="btn btn-outline btn-sm">
                                        <i class="bx bx-download"></i> Download <?= e($ans['file_name'] ?? 'Answer File') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Grading Sidebar -->
        <div class="card" style="position: sticky; top: 20px;">
            <h3 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <i class="bx bx-edit"></i> Award Final Grade
            </h3>

            <div class="form-group">
                <label class="form-label">Total Score (Max: <?= $exam['total_marks'] ?>) *</label>
                <input type="number" step="0.5" name="final_score" class="form-control" value="<?= $attempt['score'] ?>" min="0" max="<?= $exam['total_marks'] ?>" required>
                <small style="color: var(--text-muted); font-size: 0.8rem;">Passing score is <?= $exam['passing_score'] ?>% (<?= round(((float)$exam['passing_score'] / 100) * (float)$exam['total_marks'], 1) ?> marks).</small>
            </div>

            <div class="form-group">
                <label class="form-label">Teacher Feedback & Remarks</label>
                <textarea name="teacher_feedback" class="form-control" rows="5" placeholder="Enter evaluation comments, strengths, or areas for improvement..."><?= e($attempt['teacher_feedback'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="bx bx-save"></i> Save & Publish Grade
                </button>
            </div>
        </div>
    </div>
</form>
