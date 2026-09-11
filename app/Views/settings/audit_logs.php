<div class="page-header">
    <div>
        <h1 class="page-title">Administrative Audit Logs</h1>
        <p class="page-subtitle">Immutable timeline of security actions, student transfers, permissions, and credential updates.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No audit logs recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($l['created_at']) ?></td>
                            <td>
                                <strong><?= e($l['user_name'] ?? 'System') ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($l['user_email'] ?? '') ?></small>
                            </td>
                            <td><?= e($l['action']) ?></td>
                            <td><code><?= e($l['target_type'] ?? '-') ?> <?= e($l['target_id'] ? '#' . $l['target_id'] : '') ?></code></td>
                            <td style="font-size: 0.85rem;"><?= e($l['ip_address'] ?? '127.0.0.1') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
