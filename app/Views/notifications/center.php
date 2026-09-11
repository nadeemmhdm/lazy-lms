<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-bell"></i> Notification Center</h1>
        <p class="page-subtitle">Announcements and notifications targeted to you and your batch.</p>
    </div>
    <div>
        <form action="<?= url('/platform-notifications/mark-all-read') ?>" method="POST" style="display: inline;">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-outline"><i class="bx bx-check-double"></i> Mark All as Read</button>
        </form>
    </div>
</div>

<div style="max-width: 900px; margin: 0 auto;">
    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <i class="bx bx-bell-off" style="font-size: 3.5rem; margin-bottom: 0.5rem;"></i>
            <h3>All Caught Up!</h3>
            <p>You have no announcements or notifications at this time.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($notifications as $n): ?>
                <?php 
                $isUnread = empty($n['read_at']);
                $priorityClass = $n['priority'] === 'urgent' ? 'border-left: 4px solid var(--danger);' : ($n['priority'] === 'important' ? 'border-left: 4px solid var(--warning);' : 'border-left: 4px solid var(--primary);');
                ?>
                <div class="card" style="<?= $priorityClass ?> <?= $isUnread ? 'background: var(--bg-hover, #f8fafc);' : '' ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <?php if ($isUnread): ?>
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--primary);"></span>
                            <?php endif; ?>
                            <h3 style="margin: 0; font-size: 1.1rem;"><?= e($n['title']) ?></h3>
                            <span class="badge <?= $n['priority'] === 'urgent' ? 'badge-danger' : ($n['priority'] === 'important' ? 'badge-warning' : 'badge-primary') ?>" style="font-size: 0.75rem;">
                                <?= ucfirst($n['priority']) ?>
                            </span>
                        </div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                            <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                        </span>
                    </div>

                    <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); margin-bottom: 1rem; white-space: pre-wrap;">
                        <?= e($n['message']) ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                        <span>From: <?= e($n['creator_name'] ?? 'System Administration') ?></span>
                        <div>
                            <?php if ($isUnread): ?>
                                <form action="<?= url('/platform-notifications/' . ($n['public_id'] ?? $n['id']) . '/read') ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-sm btn-outline"><i class="bx bx-check"></i> Mark as Read</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--success);"><i class="bx bx-check-circle"></i> Read on <?= date('d M, H:i', strtotime($n['read_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
