<div class="page-header">
    <div>
        <h1 class="page-title">Course Curriculum & Builder</h1>
        <p class="page-subtitle">Design curriculum structures, manage lessons, and publish courses to batches.</p>
    </div>
    <div>
        <a href="<?= url('/admin/courses/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create New Course</a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= url('/admin/courses?status=all') ?>" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <a href="<?= url('/admin/courses?status=published') ?>" class="btn btn-sm <?= $status === 'published' ? 'btn-primary' : 'btn-outline' ?>">Published</a>
            <a href="<?= url('/admin/courses?status=draft') ?>" class="btn btn-sm <?= $status === 'draft' ? 'btn-primary' : 'btn-outline' ?>">Draft</a>
            <a href="<?= url('/admin/courses?status=archived') ?>" class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : 'btn-outline' ?>">Archived</a>
        </div>

        <form action="<?= url('/admin/courses') ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="q" class="form-control" style="width: 220px; padding: 0.35rem 0.75rem;" placeholder="Search course title or code..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-search"></i></button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
        <?php if (empty($courses)): ?>
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 3rem;">
                <i class="bx bx-book" style="font-size: 3rem; color: var(--text-subtle); margin-bottom: 0.5rem;"></i>
                <p>No courses found. Click "Create New Course" above to start building.</p>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <code><?= e($c['code']) ?></code>
                            <span class="badge <?= $c['status'] === 'published' ? 'badge-success' : 'badge-neutral' ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <a href="<?= url('/admin/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a>
                        </h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
                            <?= e(mb_strimwidth($c['description'] ?? 'Curriculum description and course units.', 0, 100, '...')) ?>
                        </p>
                    </div>

                    <div>
                        <div style="display: flex; gap: 1rem; font-size: 0.82rem; color: var(--text-muted); border-top: 1px solid var(--border); padding: 0.75rem 0; margin-bottom: 0.75rem;">
                            <span><i class="bx bx-folder"></i> <?= $c['unit_count'] ?> Units</span>
                            <span><i class="bx bx-file"></i> <?= $c['lesson_count'] ?> Lessons</span>
                            <span><i class="bx bx-user"></i> <?= $c['enrolled_students_count'] ?> Students</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                            <a href="<?= url('/admin/courses/' . $c['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;">
                                <i class="bx bx-layer"></i> Curriculum Builder
                            </a>
                            <a href="<?= url('/admin/courses/' . $c['id'] . '/edit') ?>" class="btn btn-outline btn-sm" title="Edit Settings">
                                <i class="bx bx-cog"></i>
                            </a>
                            <form action="<?= url('/admin/courses/' . $c['id'] . '/duplicate') ?>" method="POST" style="display:inline;" title="Clone Course">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-copy"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
