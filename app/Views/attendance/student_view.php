<div class="page-header">
    <div>
        <h1 class="page-title">My Attendance Record</h1>
        <p class="page-subtitle">Personal attendance overview and classroom participation percentage.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 1.5rem;">
    <!-- Stat Card -->
    <div class="card" style="text-align: center; padding: 2rem; height: fit-content;">
        <div style="font-size: 2.5rem; font-weight: 700; color: <?= $percentage >= 75 ? 'var(--success)' : 'var(--warning)' ?>;">
            <?= $percentage ?>%
        </div>
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.25rem;">Overall Attendance</div>
        <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-color);">
            <?= $total ?> total scheduled sessions
        </div>
    </div>

    <!-- Attendance History Table -->
    <div class="card">
        <h3 style="margin-top: 0; margin-bottom: 1rem;">Session History</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session Title</th>
                        <th>Batch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No attendance records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= e($r['session_date']) ?></td>
                                <td><strong><?= e($r['session_title']) ?></strong></td>
                                <td><span class="badge badge-neutral"><?= e($r['batch_name']) ?></span></td>
                                <td>
                                    <?php
                                        $bClass = match($r['status']) {
                                            'present' => 'badge-success',
                                            'late' => 'badge-warning',
                                            'excused' => 'badge-info',
                                            default => 'badge-danger'
                                        };
                                    ?>
                                    <span class="badge <?= $bClass ?>"><?= ucfirst($r['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
