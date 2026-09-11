<div class="page-header">
    <div>
        <h1 class="page-title">My Earned Certificates</h1>
        <p class="page-subtitle">Your accredited certificates of course completion with official verification codes.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Certificate Code</th>
                    <th>Course Title</th>
                    <th>Date of Issue</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($certificates)): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">You have not earned any certificates yet. Complete all lessons and quizzes in your enrolled courses to graduate!</td></tr>
                <?php else: ?>
                    <?php foreach ($certificates as $c): ?>
                        <tr>
                            <td><code><?= e($c['certificate_code']) ?></code></td>
                            <td><strong><?= e($c['course_title']) ?></strong></td>
                            <td><?= e($c['issue_date']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('/certificates/' . $c['id']) ?>" class="btn btn-primary btn-sm"><i class="bx bx-award"></i> View Certificate</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
