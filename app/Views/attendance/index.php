<div class="page-header">
    <div>
        <h1 class="page-title">Attendance Tracking</h1>
        <p class="page-subtitle">Schedule class roll-calls, record present/absent/late marks, and monitor participation.</p>
    </div>
    <div>
        <a href="<?= url('/attendance/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> New Attendance Session</a>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <form action="<?= url('/attendance') ?>" method="GET" style="display: flex; gap: 1rem; align-items: center;">
        <select name="batch_id" class="form-control" style="max-width: 250px;" onchange="this.form.submit()">
            <option value="">All Batches</option>
            <?php foreach ($batches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $selectedBatch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-outline btn-sm">Filter</button></noscript>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Session Title</th>
                    <th>Batch</th>
                    <th>Date</th>
                    <th>Present Count</th>
                    <th>Logged By</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No attendance sessions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><strong><a href="<?= url('/attendance/' . $s['id']) ?>"><?= e($s['title']) ?></a></strong></td>
                            <td><span class="badge badge-neutral"><?= e($s['batch_name']) ?></span></td>
                            <td><i class="bx bx-calendar"></i> <?= e($s['session_date']) ?></td>
                            <td>
                                <span class="badge badge-success"><?= $s['present_count'] ?> / <?= $s['total_marked'] ?> Present</span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($s['creator_name']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('/attendance/' . $s['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-user-check"></i> Mark / View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
