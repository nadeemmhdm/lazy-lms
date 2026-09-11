<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/courses/' . $course['id']) ?>"><?= e($course['title']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-title">Edit Course Settings</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/courses/' . $course['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="form-group">
            <label class="form-label">Course Title *</label>
            <input type="text" name="title" class="form-control" required value="<?= e($course['title']) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Course Code *</label>
                <input type="text" name="code" class="form-control" required value="<?= e($course['code']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control">
                    <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= $course['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= e($course['description']) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($course['start_date']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($course['end_date']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Course Completion Rules</label>
            <select name="completion_rules" class="form-control">
                <option value="all_lessons" <?= $course['completion_rules'] === 'all_lessons' ? 'selected' : '' ?>>Complete all lessons in all units</option>
                <option value="lessons_and_final" <?= $course['completion_rules'] === 'lessons_and_final' ? 'selected' : '' ?>>Complete all lessons and pass the final assessment</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Update Thumbnail</label>
            <input type="file" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Changes</button>
            <a href="<?= url('/admin/courses/' . $course['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
