<div class="page-header">
    <div>
        <h1 class="page-title">Batch Management</h1>
        <p class="page-subtitle">Organize students and curriculum delivery into academic cohorts.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= url('/batches/change-student') ?>" class="btn btn-outline"><i class="bx bx-transfer"></i> Change Student Batch</a>
        <a href="<?= url('/batches/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create Batch</a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <!-- Filters -->
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= url('/batches?status=all') ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <a href="<?= url('/batches?status=active') ?>" class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-outline' ?>">Active</a>
            <a href="<?= url('/batches?status=draft') ?>" class="btn btn-sm <?= $status === 'draft' ? 'btn-primary' : 'btn-outline' ?>">Draft</a>
            <a href="<?= url('/batches?status=completed') ?>" class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline' ?>">Completed</a>
            <a href="<?= url('/batches?status=archived') ?>" class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : 'btn-outline' ?>">Archived</a>
        </div>

        <!-- Search -->
        <form action="<?= url('/batches') ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="q" class="form-control" style="width: 220px; padding: 0.35rem 0.75rem;" placeholder="Search name or code..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Batch Name</th>
                    <th>Enrolled Students</th>
                    <th>Assigned Courses</th>
                    <th>Timeline</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No batches found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                        <tr>
                            <td><code><?= e($b['code']) ?></code></td>
                            <td><strong><a href="<?= url('/batches/' . $b['id']) ?>"><?= e($b['name']) ?></a></strong></td>
                            <td><span class="badge badge-info"><?= $b['student_count'] ?> Students</span></td>
                            <td><span class="badge badge-neutral"><?= $b['course_count'] ?> Courses</span></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= e($b['start_date'] ?? 'N/A') ?> &rarr; <?= e($b['end_date'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <?php 
                                    $badge = match($b['status']) {
                                        'active' => 'badge-success',
                                        'draft' => 'badge-neutral',
                                        'completed' => 'badge-info',
                                        'archived' => 'badge-warning',
                                        default => 'badge-neutral'
                                    };
                                ?>
                                <span class="badge <?= $badge ?>"><?= ucfirst($b['status']) ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/batches/' . $b['id']) ?>" class="btn btn-outline btn-sm" title="View Batch"><i class="bx bx-show"></i> View</a>
                                <a href="<?= url('/batches/' . $b['id'] . '/edit') ?>" class="btn btn-outline btn-sm" title="Edit Batch"><i class="bx bx-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
        <div style="display: flex; justify-content: center; gap: 0.35rem; margin-top: 1.5rem;">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                <a href="<?= url('/batches?page=' . $p . '&status=' . $status . '&q=' . urlencode($search)) ?>" 
                   class="btn btn-sm <?= $p === $currentPage ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
