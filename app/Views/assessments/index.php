<div class="page-header">
    <div>
        <h1 class="page-title">Assessments & Quizzes</h1>
        <p class="page-subtitle">Create and manage online exams, timed tests, and automatic evaluation rules.</p>
    </div>
    <div>
        <a href="<?= url('/assessments/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create Assessment</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Unit</th>
                    <th>Total Marks</th>
                    <th>Passing Marks</th>
                    <th>Time Limit</th>
                    <th>Questions</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assessments)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No assessments created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assessments as $a): ?>
                        <tr>
                            <td><strong><a href="<?= url('/assessments/' . $a['id'] . '/builder') ?>"><?= e($a['title']) ?></a></strong></td>
                            <td><?= e($a['course_title']) ?></td>
                            <td><?= e($a['unit_title'] ?? 'Course Final') ?></td>
                            <td><?= $a['total_marks'] ?></td>
                            <td><?= $a['pass_marks'] ?></td>
                            <td><?= $a['time_limit'] ?> mins</td>
                            <td><span class="badge badge-neutral"><?= $a['question_count'] ?> Questions</span></td>
                            <td>
                                <span class="badge <?= $a['status'] === 'published' ? 'badge-success' : 'badge-neutral' ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/assessments/' . $a['id'] . '/builder') ?>" class="btn btn-outline btn-sm" title="Manage Questions"><i class="bx bx-customize"></i> Builder</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
