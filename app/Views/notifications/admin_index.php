<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-bell"></i> Platform Notifications</h1>
        <p class="page-subtitle">Broadcast announcements targeted to the entire platform, specific batches, teachers, or students.</p>
    </div>
    <div>
        <a href="<?= url('/platform-notifications/create') ?>" class="btn btn-primary"><i class="bx bx-plus"></i> Create Announcement</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title & Message</th>
                    <th>Target Audience</th>
                    <th>Priority</th>
                    <th>Email Sent</th>
                    <th>Schedule Window</th>
                    <th>Reads</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No platform notifications dispatched yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <tr>
                            <td>
                                <strong><?= e($n['title']) ?></strong>
                                <div style="font-size: 0.85rem; color: var(--text-muted); max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($n['message']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-outline" style="text-transform: capitalize;">
                                    <?= $n['target_type'] === 'batch' ? 'Batch: ' . e($n['batch_name'] ?? 'ID ' . $n['target_id']) : $n['target_type'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $n['priority'] === 'urgent' ? 'badge-danger' : ($n['priority'] === 'important' ? 'badge-warning' : 'badge-primary') ?>">
                                    <?= ucfirst($n['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($n['send_email']) ? '<i class="bx bx-check text-success"></i> Yes' : '<span style="color: var(--text-muted);">No</span>' ?>
                            </td>
                            <td style="font-size: 0.82rem;">
                                <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                                <?= !empty($n['expiry_time']) ? '<br><span style="color: var(--text-muted);">Expires ' . date('d M Y', strtotime($n['expiry_time'])) . '</span>' : '' ?>
                            </td>
                            <td>
                                <strong><?= $n['read_count'] ?></strong> read(s)
                            </td>
                            <td>
                                <span class="badge <?= $n['status'] === 'published' ? 'badge-success' : 'badge-outline' ?>">
                                    <?= ucfirst($n['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
