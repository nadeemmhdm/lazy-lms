<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-book-bookmark"></i> Subjects Management</h1>
        <p class="page-subtitle">Batch &rarr; Subject &rarr; Course &rarr; Unit &rarr; Lesson structural hierarchy.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    <?php if ($user['role'] !== 'student'): ?>
        <div class="card">
            <h3 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem;">
                <i class="bx bx-plus-circle text-primary"></i> Add New Subject
            </h3>

            <form action="<?= url('/subjects') ?>" method="POST">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label class="form-label">Subject Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Computer Science" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Subject Code *</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. CS-101" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Associated Batch (Optional)</label>
                    <select name="batch_id" class="form-control">
                        <option value="">Cross-Batch Subject</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Subject overview, syllabus scope..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="bx bx-save"></i> Save Subject
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card" style="<?= $user['role'] === 'student' ? 'grid-column: 1 / -1;' : '' ?>">
        <h3 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem;">
            Registered Academic Subjects (<?= count($subjects) ?>)
        </h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Code</th>
                        <th>Associated Batch</th>
                        <th>Courses</th>
                        <?php if ($user['role'] !== 'student'): ?><th style="text-align: right;">Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No academic subjects created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= e($s['name']) ?></strong>
                                    <?php if (!empty($s['description'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($s['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-outline"><?= e($s['code']) ?></span></td>
                                <td><?= e($s['batch_name'] ?? 'All Batches') ?></td>
                                <td><?= $s['course_count'] ?> Course(s)</td>
                                <?php if ($user['role'] !== 'student'): ?>
                                    <td style="text-align: right;">
                                        <form action="<?= url('/subjects/' . ($s['public_id'] ?? $s['id']) . '/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Delete this subject?');">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
