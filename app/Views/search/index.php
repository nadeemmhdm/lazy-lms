<div class="page-header">
    <div>
        <h1 class="page-title">Search Results</h1>
        <p class="page-subtitle">Showing matching results for "<?= e($query) ?>" across all LMS databases.</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 900px; margin: 0 auto;">
    <!-- Courses Match -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            Courses (<?= count($results['courses']) ?>)
        </h3>
        <?php if (empty($results['courses'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No courses match the query.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($results['courses'] as $c): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><a href="<?= url('/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a></strong>
                            <small style="color: var(--text-muted); margin-left: 0.5rem;">(<?= e($c['code']) ?>)</small>
                            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--text-muted);"><?= e(substr(strip_tags($c['description'] ?? ''), 0, 100)) ?></p>
                        </div>
                        <a href="<?= url('/courses/' . $c['id']) ?>" class="btn btn-outline btn-sm">Open Course</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Batches Match -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            Batches (<?= count($results['batches']) ?>)
        </h3>
        <?php if (empty($results['batches'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No batches match the query.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($results['batches'] as $b): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><a href="<?= url('/batches/' . $b['id']) ?>"><?= e($b['name']) ?></a></strong>
                            <small style="color: var(--text-muted); margin-left: 0.5rem;">(<?= e($b['code']) ?>)</small>
                        </div>
                        <a href="<?= url('/batches/' . $b['id']) ?>" class="btn btn-outline btn-sm">View Batch</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Students Match (Admin/Teacher) -->
    <?php if (!empty($results['students'])): ?>
        <div class="card">
            <h3 style="margin-top: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Students (<?= count($results['students']) ?>)
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($results['students'] as $s): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><a href="<?= url('/students/' . $s['id']) ?>"><?= e($s['name']) ?></a></strong>
                            <small style="color: var(--text-muted); margin-left: 0.5rem;"><?= e($s['email']) ?></small>
                        </div>
                        <a href="<?= url('/students/' . $s['id']) ?>" class="btn btn-outline btn-sm">Profile</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
