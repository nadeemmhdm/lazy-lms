<div class="page-header">
    <div>
        <h1 class="page-title">Active User Sessions</h1>
        <p class="page-subtitle">Monitor logged-in devices and revoke sessions when account compromise is suspected.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>User Agent / Device</th>
                    <th>Last Activity</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No active user sessions recorded in database.</td></tr>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td>
                                <strong><?= e($s['user_name']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($s['user_email']) ?></small>
                            </td>
                            <td><code><?= e($s['ip_address']) ?></code></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= e($s['user_agent'] ?: 'Standard Browser') ?>
                            </td>
                            <td style="font-size: 0.85rem;"><?= date('Y-m-d H:i:s', (int)$s['last_activity']) ?></td>
                            <td style="text-align: right;">
                                <form action="<?= url('/settings/sessions/' . $s['id'] . '/destroy') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Terminate this session immediately?');">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-log-out-circle"></i> Terminate</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
