<div class="page-header">
    <div>
        <h1 class="page-title">My Assignments</h1>
        <p class="page-subtitle">Homework, projects, and practical coursework for your classes.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Assignment</th>
                    <th>Course</th>
                    <th>Max Marks</th>
                    <th>Due Date</th>
                    <th>Submission Status</th>
                    <th>Grade Awarded</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No assignments currently due.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignments as $asg): ?>
                        <tr>
                            <td><strong><?= e($asg['title']) ?></strong></td>
                            <td><?= e($asg['course_title']) ?></td>
                            <td><?= $asg['max_marks'] ?></td>
                            <td><?= e($asg['due_date']) ?></td>
                            <td>
                                <?php if ($asg['submission_status'] === 'graded'): ?>
                                    <span class="badge badge-success">Graded</span>
                                <?php elseif ($asg['submission_status'] === 'submitted'): ?>
                                    <span class="badge badge-info">Submitted</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending Submission</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($asg['grade'] !== null): ?>
                                    <strong><?= $asg['grade'] ?> / <?= $asg['max_marks'] ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/assignments/' . $asg['id']) ?>" class="btn btn-primary btn-sm">
                                    <i class="bx <?= $asg['submission_id'] ? 'bx-edit' : 'bx-upload' ?>"></i> 
                                    <?= $asg['submission_id'] ? 'View / Resubmit' : 'Submit' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
