<div class="page-header">
    <div>
        <h1 class="page-title">Course Assignments</h1>
        <p class="page-subtitle">Track project submissions, deadlines, and teacher evaluations.</p>
    </div>
    <div>
        <a href="<?= url('/assignments/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create Assignment</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Assignment Title</th>
                    <th>Course</th>
                    <th>Max Marks</th>
                    <th>Due Date</th>
                    <th>Submissions</th>
                    <th>Pending Grading</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No assignments configured yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignments as $asg): ?>
                        <tr>
                            <td><strong><a href="<?= url('/assignments/' . $asg['id']) ?>"><?= e($asg['title']) ?></a></strong></td>
                            <td><?= e($asg['course_title']) ?></td>
                            <td><?= $asg['max_marks'] ?></td>
                            <td><span style="color: var(--danger);"><i class="bx bx-calendar"></i> <?= e($asg['due_date']) ?></span></td>
                            <td><span class="badge badge-info"><?= $asg['total_submissions'] ?> Submissions</span></td>
                            <td>
                                <?php if ($asg['pending_grading'] > 0): ?>
                                    <span class="badge badge-warning"><?= $asg['pending_grading'] ?> To Grade</span>
                                <?php else: ?>
                                    <span class="badge badge-success">All Graded</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/assignments/' . $asg['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-show"></i> Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
