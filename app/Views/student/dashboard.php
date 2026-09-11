<div class="page-header">
    <div>
        <h1 class="page-title">My Learning Portal</h1>
        <p class="page-subtitle">Welcome back, <?= e($currentUser['name']) ?>!</p>
    </div>
    <?php if ($batch): ?>
        <div style="background: var(--primary-light); padding: 0.5rem 1rem; border-radius: var(--radius-md); border: 1px solid rgba(79, 70, 229, 0.2);">
            <span style="font-size: 0.82rem; color: var(--text-muted);">Enrolled Batch:</span>
            <strong style="color: var(--primary); margin-left: 0.25rem;"><?= e($batch['name']) ?> (<?= e($batch['code']) ?>)</strong>
        </div>
    <?php endif; ?>
</div>

<!-- Continue Learning Banner -->
<?php if ($lastLesson): ?>
    <div class="card" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(6, 182, 212, 0.08)); border-color: rgba(79, 70, 229, 0.25);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 0.5rem;"><i class="bx bx-play-circle"></i> Continue Learning</span>
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;"><?= e($lastLesson['title']) ?></h2>
                <div style="font-size: 0.88rem; color: var(--text-muted);">
                    Course: <strong><?= e($lastLesson['course_title']) ?></strong> &bull; Unit: <?= e($lastLesson['unit_title']) ?>
                </div>
            </div>
            <a href="<?= url('/student/lessons/' . $lastLesson['id']) ?>" class="btn btn-primary btn-lg">
                <i class="bx bx-right-arrow-alt"></i> Resume Lesson
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- My Courses Grid -->
<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700;"><i class="bx bx-book-open"></i> My Courses</h2>
        <span style="font-size: 0.88rem; color: var(--text-muted);"><?= count($courses) ?> Total Enrolled</span>
    </div>

    <?php if (empty($courses)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <i class="bx bx-book" style="font-size: 3rem; color: var(--text-subtle); margin-bottom: 0.5rem;"></i>
            <h3>No Courses Assigned Yet</h3>
            <p style="color: var(--text-muted);">Your administrator has not assigned courses to your batch yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($courses as $c): ?>
                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <span class="badge badge-info"><code><?= e($c['code']) ?></code></span>
                            <?php if ($c['progress_percentage'] >= 100): ?>
                                <span class="badge badge-success"><i class="bx bx-check"></i> Completed</span>
                            <?php else: ?>
                                <span class="badge badge-neutral"><?= round($c['progress_percentage']) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <a href="<?= url('/student/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a>
                        </h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.4;">
                            <?= e(mb_strimwidth($c['description'] ?? 'Course material and lessons.', 0, 95, '...')) ?>
                        </p>
                    </div>

                    <div>
                        <div style="margin-bottom: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                                <span>Progress</span>
                                <span><?= $c['completed_lessons_count'] ?> / <?= $c['total_lessons_count'] ?> lessons</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?= min(100, $c['progress_percentage']) ?>%;"></div>
                            </div>
                        </div>

                        <a href="<?= url('/student/courses/' . $c['id']) ?>" class="btn btn-outline" style="width: 100%;">
                            <i class="bx bx-book-reader"></i> Open Course
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
    <!-- Upcoming Assessments -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-check-square"></i> Upcoming Quizzes & Tests</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Time Limit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($upcomingAssessments)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No upcoming tests scheduled.</td></tr>
                    <?php else: ?>
                        <?php foreach ($upcomingAssessments as $a): ?>
                            <tr>
                                <td>
                                    <strong><?= e($a['title']) ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($a['course_title']) ?></div>
                                </td>
                                <td><?= $a['time_limit_minutes'] ?> mins</td>
                                <td>
                                    <a href="<?= url('/student/assessments/' . $a['id']) ?>" class="btn btn-primary btn-sm">Start</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Certificates -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-certification"></i> Earned Certificates</div>
        </div>
        <div>
            <?php if (empty($certificates)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 2rem 1rem;">
                    <i class="bx bx-award" style="font-size: 2.5rem; color: var(--text-subtle); margin-bottom: 0.5rem;"></i>
                    <p>Complete courses to 100% to earn official certificates.</p>
                </div>
            <?php else: ?>
                <?php foreach ($certificates as $cert): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <strong><?= e($cert['course_name']) ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Issued: <?= e($cert['issue_date']) ?> &bull; No: <code><?= e($cert['certificate_number']) ?></code></div>
                        </div>
                        <a href="<?= url('/student/certificates/' . $cert['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-download"></i> View</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
