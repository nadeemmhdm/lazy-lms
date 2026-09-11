<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <span>Create Course</span>
        </div>
        <h1 class="page-title">Create Course</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/courses') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Course Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Modern Full-Stack Web Development" value="<?= e(old('title')) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Course Code *</label>
                <input type="text" name="code" class="form-control" required placeholder="e.g. CS-101" value="<?= e(old('code')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Publishing Status *</label>
                <select name="status" class="form-control">
                    <option value="draft">Draft (Hidden from students)</option>
                    <option value="published">Published (Available to batch)</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description & Syllabus Overview</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Course goals, prerequisites, and learning outcomes..."><?= e(old('description')) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e(old('start_date')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e(old('end_date')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Course Thumbnail</label>
            <input type="file" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Course & Open Builder</button>
            <a href="<?= url('/admin/courses') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
