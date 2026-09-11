<div class="page-header">
    <div>
        <h1 class="page-title">My Academic Gradebook</h1>
        <p class="page-subtitle">Your personal assessment scores, assignment grades, and completion metrics.</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php if (empty($courses)): ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
            No enrolled courses found for your batch.
        </div>
    <?php else: ?>
        <?php foreach ($courses as $c): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="margin: 0;"><?= e($c['course_title']) ?> <small style="color: var(--text-muted); font-size: 0.85rem;">(<?= e($c['course_code']) ?>)</small></h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="badge <?= $c['is_completed'] ? 'badge-success' : 'badge-info' ?>">
                            <?= $c['is_completed'] ? 'Completed' : round($c['progress_percentage'] ?? 0) . '% Progress' ?>
                        </span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Quizzes/Assessments -->
                    <div>
                        <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 0.95rem; color: var(--text-muted);">Quizzes & Assessments</h4>
                        <?php if (empty($c['assessments'])): ?>
                            <p style="font-size: 0.88rem; color: var(--text-muted);">No quiz attempts recorded yet.</p>
                        <?php else: ?>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php foreach ($c['assessments'] as $a): ?>
                                    <li style="display: flex; justify-content: space-between; background: var(--bg-hover); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.88rem;">
                                        <span><?= e($a['title']) ?></span>
                                        <strong><?= $a['score'] ?> / <?= $a['max_score'] ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Assignments -->
                    <div>
                        <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 0.95rem; color: var(--text-muted);">Assignments & Projects</h4>
                        <?php if (empty($c['assignments'])): ?>
                            <p style="font-size: 0.88rem; color: var(--text-muted);">No graded assignments yet.</p>
                        <?php else: ?>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php foreach ($c['assignments'] as $asg): ?>
                                    <li style="display: flex; justify-content: space-between; background: var(--bg-hover); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.88rem;">
                                        <span><?= e($asg['title']) ?></span>
                                        <strong><?= $asg['grade'] ?> / <?= $asg['max_marks'] ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
