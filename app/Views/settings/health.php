<div class="page-header">
    <div>
        <h1 class="page-title">System Health & Diagnostics</h1>
        <p class="page-subtitle">Verify SQLite engine parameters, PHP extension readiness, and filesystem write permissions.</p>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Subsystem Metric</th>
                    <th>Status</th>
                    <th>Runtime Value</th>
                    <th>Requirement</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health as $item): ?>
                    <tr>
                        <td><strong><?= e($item['name']) ?></strong></td>
                        <td>
                            <?php if ($item['status'] === 'pass'): ?>
                                <span class="badge badge-success"><i class="bx bx-check"></i> Pass</span>
                            <?php elseif ($item['status'] === 'warning'): ?>
                                <span class="badge badge-warning"><i class="bx bx-error"></i> Warning</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="bx bx-x"></i> Fail</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($item['value']) ?></code></td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($item['requirement']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
