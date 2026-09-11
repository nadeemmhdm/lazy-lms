<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/teachers') ?>">Teachers</a>
            <i class="bx bx-chevron-right"></i>
            <span><?= e($teacher['name']) ?></span>
        </div>
        <h1 class="page-title"><?= e($teacher['name']) ?></h1>
        <p class="page-subtitle">Faculty Email: <?= e($teacher['email']) ?> &bull; Phone: <?= e($teacher['phone'] ?: 'None') ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= url('/admin/teachers/' . $teacher['id'] . '/edit') ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Edit</a>
        <?php if ($teacher['status'] === 'active'): ?>
            <form action="<?= url('/admin/teachers/' . $teacher['id'] . '/archive') ?>" method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--warning);"><i class="bx bx-archive"></i> Archive</button>
            </form>
        <?php else: ?>
            <form action="<?= url('/admin/teachers/' . $teacher['id'] . '/restore') ?>" method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--success);"><i class="bx bx-refresh"></i> Restore</button>
            </form>
        <?php endif; ?>
        <form action="<?= url('/admin/teachers/' . $teacher['id'] . '/delete') ?>" method="POST" style="display:inline;" data-confirm="Permanently delete this faculty member?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-sm"><i class="bx bx-trash"></i> Delete</button>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
    <!-- Assigned Courses -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-book-bookmark"></i> Assigned Courses</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No courses directly assigned.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><a href="<?= url('/admin/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a></strong></td>
                                <td><code><?= e($c['code']) ?></code></td>
                                <td><span class="badge badge-success"><?= ucfirst($c['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assigned Batches -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="bx bx-group"></i> Assigned Cohorts / Batches</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Batch Name</th>
                        <th>Code</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No batches assigned.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><strong><a href="<?= url('/admin/batches/' . $b['id']) ?>"><?= e($b['name']) ?></a></strong></td>
                                <td><code><?= e($b['code']) ?></code></td>
                                <td><span class="badge badge-success"><?= ucfirst($b['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
