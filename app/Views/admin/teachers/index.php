<div class="page-header">
    <div>
        <h1 class="page-title">Faculty Management</h1>
        <p class="page-subtitle">Manage instructors, course assignments, and cohort permissions.</p>
    </div>
    <div>
        <a href="<?= url('/admin/teachers/create') ?>" class="btn btn-primary"><i class="bx bx-user-plus"></i> Register Faculty Member</a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= url('/admin/teachers?status=all') ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <a href="<?= url('/admin/teachers?status=active') ?>" class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-outline' ?>">Active</a>
            <a href="<?= url('/admin/teachers?status=archived') ?>" class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : 'btn-outline' ?>">Archived</a>
            <a href="<?= url('/admin/teachers?status=suspended') ?>" class="btn btn-sm <?= $status === 'suspended' ? 'btn-primary' : 'btn-outline' ?>">Suspended</a>
        </div>

        <form action="<?= url('/admin/teachers') ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="q" class="form-control" style="width: 220px; padding: 0.35rem 0.75rem;" placeholder="Search faculty name, email..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Assigned Courses</th>
                    <th>Assigned Batches</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No teachers registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($t['name'], 0, 1)) ?>
                                    </div>
                                    <strong><a href="<?= url('/admin/teachers/' . $t['id']) ?>"><?= e($t['name']) ?></a></strong>
                                </div>
                            </td>
                            <td><?= e($t['email']) ?></td>
                            <td><?= e($t['phone'] ?: 'N/A') ?></td>
                            <td><span class="badge badge-info"><?= $t['assigned_courses_count'] ?> Courses</span></td>
                            <td><span class="badge badge-neutral"><?= $t['assigned_batches_count'] ?> Batches</span></td>
                            <td>
                                <span class="badge <?= $t['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                                    <?= ucfirst($t['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= url('/admin/teachers/' . $t['id']) ?>" class="btn btn-outline btn-sm" title="View"><i class="bx bx-show"></i></a>
                                <a href="<?= url('/admin/teachers/' . $t['id'] . '/edit') ?>" class="btn btn-outline btn-sm" title="Edit"><i class="bx bx-edit"></i></a>
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
                <a href="<?= url('/admin/teachers?page=' . $p . '&status=' . $status . '&q=' . urlencode($search)) ?>" 
                   class="btn btn-sm <?= $p === $currentPage ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
