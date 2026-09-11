<div class="page-header">
    <div>
        <h1 class="page-title">Gradebook</h1>
        <p class="page-subtitle">Consolidated performance tracking across quizzes, exams, and practical assignments.</p>
    </div>
    <?php if ($selectedCourse): ?>
        <div>
            <a href="<?= url('/gradebook?course_id=' . $selectedCourse . ($selectedBatch ? '&batch_id=' . $selectedBatch : '') . '&export=csv') ?>" class="btn btn-outline">
                <i class="bx bx-export"></i> Export CSV
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <form action="<?= url('/gradebook') ?>" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" onchange="this.form.submit()" required>
                <option value="">Select Course...</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedCourse == $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 220px;">
            <label class="form-label">Filter by Batch</label>
            <select name="batch_id" class="form-control" onchange="this.form.submit()">
                <option value="">All Batches</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $selectedBatch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt"></i> Load Grades</button>
    </form>
</div>

<?php if (!$selectedCourse): ?>
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
        <i class="bx bx-book-open" style="font-size: 3rem; margin-bottom: 0.5rem; display: block;"></i>
        Please select a course from the dropdown above to view the student grade matrix.
    </div>
<?php elseif (empty($students)): ?>
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
        No active students found in this course's assigned batches.
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Batch</th>
                        <?php foreach ($assessments as $a): ?>
                            <th style="text-align: center;"><?= e($a['title']) ?><br><small style="color: var(--text-muted);">(<?= $a['total_marks'] ?>M)</small></th>
                        <?php endforeach; ?>
                        <?php foreach ($assignments as $asg): ?>
                            <th style="text-align: center;"><?= e($asg['title']) ?><br><small style="color: var(--text-muted);">(<?= $asg['max_marks'] ?>M)</small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td>
                                <strong><?= e($stu['name']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($stu['email']) ?></small>
                            </td>
                            <td><span class="badge badge-neutral"><?= e($stu['batch_name']) ?></span></td>
                            <?php foreach ($assessments as $a): ?>
                                <td style="text-align: center;">
                                    <?php 
                                        $val = $stu['assessment_grades'][$a['id']] ?? null;
                                        echo $val !== null ? '<strong>' . $val . '</strong>' : '<span style="color: var(--text-muted);">-</span>';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <?php foreach ($assignments as $asg): ?>
                                <td style="text-align: center;">
                                    <?php 
                                        $val = $stu['assignment_grades'][$asg['id']] ?? null;
                                        echo $val !== null ? '<strong>' . $val . '</strong>' : '<span style="color: var(--text-muted);">-</span>';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
