<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Your real-time activity stream, assignments, grading feedback, and course updates.</p>
    </div>
    <div>
        <form action="<?= url('/notifications/mark-all-read') ?>" method="POST" style="display: inline;">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-outline"><i class="bx bx-check-double"></i> Mark All as Read</button>
        </form>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <?php if (empty($notifications)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
            <i class="bx bx-bell-off" style="font-size: 3rem; margin-bottom: 0.5rem; display: block;"></i>
            You have no notifications at this time.
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($notifications as $n): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-radius: var(--radius-sm); background: <?= $n['is_read'] ? 'transparent' : 'var(--bg-hover)' ?>; border: 1px solid var(--border-color); transition: background var(--transition-fast);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $n['is_read'] ? 'var(--bg-hover)' : 'var(--primary-light)' ?>; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="bx <?= $n['type'] === 'grade' ? 'bx-medal' : ($n['type'] === 'announcement' ? 'bx-broadcast' : 'bx-bell') ?>"></i>
                        </div>
                        <div>
                            <div style="font-weight: <?= $n['is_read'] ? '500' : '700' ?>; font-size: 0.95rem;">
                                <?php if (!empty($n['link'])): ?>
                                    <a href="<?= url($n['link']) ?>"><?= e($n['title']) ?></a>
                                <?php else: ?>
                                    <?= e($n['title']) ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                                <?= e($n['message']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                                <?= e($n['created_at']) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!$n['is_read']): ?>
                        <form action="<?= url('/notifications/' . $n['id'] . '/read') ?>" method="POST">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="Mark as Read"><i class="bx bx-check"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
