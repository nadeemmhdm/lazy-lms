<div class="page-header">
    <div>
        <h1 class="page-title">Database Backups</h1>
        <p class="page-subtitle">One-click SQLite hot snapshots, backup download, and disaster recovery archives.</p>
    </div>
    <div>
        <form action="<?= url('/settings/backups/create') ?>" method="POST" style="display: inline;">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create Backup Snapshot</button>
        </form>
    </div>
</div>

<div class="card">
    <div style="background: var(--warning-light); border-left: 4px solid var(--warning); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.9rem;">
        <strong><i class="bx bx-shield-quarter"></i> Security Warning:</strong> Database backups contain full user tables, hashed passwords, assessment answers, and session records. Store all downloaded backup files in encrypted off-site storage.
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Backup Filename</th>
                    <th>File Size</th>
                    <th>Created At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backups)): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No backup archives generated yet. Click "Create Backup Snapshot" to create your first archive.</td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td><code><?= e($b['filename']) ?></code></td>
                            <td><?= round($b['size'] / 1024, 2) ?> KB</td>
                            <td><?= e($b['created_at']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('/settings/backups/download/' . $b['filename']) ?>" class="btn btn-outline btn-sm">
                                    <i class="bx bx-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
