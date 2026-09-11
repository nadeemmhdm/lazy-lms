<div class="page-header">
    <div>
        <h1 class="page-title">Student Directory</h1>
        <p class="page-subtitle">Manage enrolled learners, batch allocations, and academic statuses.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= url('/admin/batches/change-student') ?>" class="btn btn-outline"><i class="bx bx-transfer"></i> Change Batch</a>
        <a href="<?= url('/admin/students/create') ?>" class="btn btn-primary"><i class="bx bx-user-plus"></i> Enroll New Student</a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <!-- Status & Batch Filters -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= url('/admin/students?status=all' . ($batchId ? '&batch_id=' . $batchId : '')) ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <a href="<?= url('/admin/students?status=active' . ($batchId ? '&batch_id=' . $batchId : '')) ?>" class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : 'btn-outline' ?>">Active</a>
            <a href="<?= url('/admin/students?status=archived' . ($batchId ? '&batch_id=' . $batchId : '')) ?>" class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : 'btn-outline' ?>">Archived</a>
            <a href="<?= url('/admin/students?status=suspended' . ($batchId ? '&batch_id=' . $batchId : '')) ?>" class="btn btn-sm <?= $status === 'suspended' ? 'btn-primary' : 'btn-outline' ?>">Suspended</a>

            <select onchange="window.location.href=this.value" class="form-control" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                <option value="<?= url('/admin/students?status=' . $status) ?>">All Batches</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= url('/admin/students?status=' . $status . '&batch_id=' . $b['id']) ?>" <?= $batchId == $b['id'] ? 'selected' : '' ?>>
                        <?= e($b['name']) ?> (<?= e($b['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Search -->
        <form action="<?= url('/admin/students') ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php if ($batchId): ?><input type="hidden" name="batch_id" value="<?= e($batchId) ?>"><?php endif; ?>
            <input type="text" name="q" class="form-control" style="width: 220px; padding: 0.35rem 0.75rem;" placeholder="Search name, ID, email..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Email Address</th>
                    <th>Enrolled Batch</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No students found matching your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                    </div>
                                    <strong><a href="<?= url('/admin/students/' . $s['id']) ?>"><?= e($s['name']) ?></a></strong>
                                </div>
                            </td>
                            <td><code><?= e($s['student_id'] ?: 'N/A') ?></code></td>
                            <td><?= e($s['email']) ?></td>
                            <td>
                                <?php if ($s['batch_name']): ?>
                                    <span class="badge badge-info"><?= e($s['batch_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-neutral">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $badge = match($s['status']) {
                                        'active' => 'badge-success',
                                        'archived' => 'badge-warning',
                                        'suspended' => 'badge-danger',
                                        default => 'badge-neutral'
                                    };
                                ?>
                                <span class="badge <?= $badge ?>"><?= ucfirst($s['status']) ?></span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('/admin/students/' . $s['id']) ?>" class="btn btn-outline btn-sm" title="View Profile"><i class="bx bx-show"></i></a>
                                <a href="<?= url('/admin/students/' . $s['id'] . '/edit') ?>" class="btn btn-outline btn-sm" title="Edit Student"><i class="bx bx-edit"></i></a>
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
                <a href="<?= url('/admin/students?page=' . $p . '&status=' . $status . ($batchId ? '&batch_id=' . $batchId : '') . '&q=' . urlencode($search)) ?>" 
                   class="btn btn-sm <?= $p === $currentPage ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
