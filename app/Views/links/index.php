<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-link-external"></i> Admin Quick Links & Visibility Controls</h1>
        <p class="page-subtitle">Manage shared resources, tools, and external portals with target audience and visibility toggles.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    <!-- Create Link Form -->
    <div class="card">
        <h3 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bx bx-plus-circle text-primary"></i> Add Quick Link
        </h3>

        <form action="<?= url('/admin/links') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label">Link Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Digital Library Portal" required>
            </div>

            <div class="form-group">
                <label class="form-label">Target URL *</label>
                <input type="text" name="url" class="form-control" placeholder="/certificates or https://..." required>
            </div>

            <div class="form-group">
                <label class="form-label">Boxicon Class</label>
                <input type="text" name="icon" class="form-control" value="bx-link" placeholder="e.g. bx-book, bx-video, bx-link">
            </div>

            <div class="form-group">
                <label class="form-label">Audience Visibility *</label>
                <select name="visibility_type" class="form-control" required>
                    <option value="all">Entire Platform (All Users)</option>
                    <option value="teachers">Teachers Only</option>
                    <option value="students">Students Only</option>
                    <option value="batch">Specific Batch</option>
                    <option value="course">Specific Course</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                <i class="bx bx-plus"></i> Add Link
            </button>
        </form>
    </div>

    <!-- Existing Links Table -->
    <div class="card">
        <h3 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem;">
            Existing Managed Links (<?= count($links) ?>)
        </h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title & URL</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No quick links created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($links as $l): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="bx <?= e($l['icon']) ?>" style="font-size: 1.25rem; color: var(--primary);"></i>
                                        <div>
                                            <strong><?= e($l['title']) ?></strong><br>
                                            <a href="<?= e($l['url']) ?>" target="_blank" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;">
                                                <?= e(mb_strimwidth($l['url'], 0, 35, '...')) ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline" style="text-transform: capitalize;">
                                        <?= $l['visibility_type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= !empty($l['is_visible']) ? 'badge-success' : 'badge-danger' ?>">
                                        <?= !empty($l['is_visible']) ? 'Visible' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.4rem;">
                                        <form action="<?= url('/admin/links/' . ($l['public_id'] ?? $l['id']) . '/toggle') ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">
                                                <i class="bx <?= !empty($l['is_visible']) ? 'bx-hide' : 'bx-show' ?>"></i>
                                                <?= !empty($l['is_visible']) ? 'Hide' : 'Show' ?>
                                            </button>
                                        </form>

                                        <form action="<?= url('/admin/links/' . ($l['public_id'] ?? $l['id']) . '/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Delete this link?');">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline text-danger">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
