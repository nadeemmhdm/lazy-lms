<div class="page-header">
    <div>
        <h1 class="page-title">Batch & Campus Announcements</h1>
        <p class="page-subtitle">Stay updated on important news, timetable revisions, and system alerts.</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 1rem; max-width: 800px; margin: 0 auto;">
    <?php if (empty($announcements)): ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
            No announcements currently available for your batch.
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $a): ?>
            <div class="card" style="border-left: 4px solid <?= $a['priority'] === 'high' ? 'var(--danger)' : 'var(--primary)' ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <h3 style="margin: 0; font-size: 1.2rem;">
                        <?= e($a['title']) ?>
                        <?php if ($a['priority'] === 'high'): ?>
                            <span class="badge badge-danger" style="margin-left: 0.5rem;">Important</span>
                        <?php endif; ?>
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($a['created_at']) ?></span>
                </div>

                <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-color); margin-top: 0.5rem;">
                    <?= nl2br(e($a['content'])) ?>
                </div>

                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    Posted by <?= e($a['author_name']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
