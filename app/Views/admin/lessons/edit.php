<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/lessons/' . $lesson['id']) ?>"><?= e($lesson['title']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-title">Edit Lesson</h1>
    </div>
</div>

<div class="card" style="max-width: 840px;">
    <form action="<?= url('/admin/lessons/' . $lesson['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="form-group">
            <label class="form-label">Lesson Title *</label>
            <input type="text" name="title" class="form-control" required value="<?= e($lesson['title']) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Lesson Type *</label>
                <select name="lesson_type" class="form-control">
                    <option value="text" <?= $lesson['lesson_type'] === 'text' ? 'selected' : '' ?>>Rich Text & Notes</option>
                    <option value="video" <?= $lesson['lesson_type'] === 'video' ? 'selected' : '' ?>>Video Lesson</option>
                    <option value="pdf" <?= $lesson['lesson_type'] === 'pdf' ? 'selected' : '' ?>>Document / PDF Study</option>
                    <option value="ppt" <?= $lesson['lesson_type'] === 'ppt' ? 'selected' : '' ?>>Slide Presentation</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Duration (Minutes)</label>
                <input type="number" name="duration_minutes" class="form-control" value="<?= e($lesson['duration_minutes']) ?>" min="1">
            </div>
        </div>

        <!-- Video Config -->
        <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;"><i class="bx bx-video"></i> Video Configuration</h3>
            <div class="form-group">
                <label class="form-label">Video Source Type</label>
                <select name="video_source_type" id="video_source_type" class="form-control" onchange="toggleVideoSourceInput(this.value)">
                    <option value="youtube" <?= ($video['source_type'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube URL</option>
                    <option value="vimeo" <?= ($video['source_type'] ?? '') === 'vimeo' ? 'selected' : '' ?>>Vimeo URL</option>
                    <option value="external_url" <?= ($video['source_type'] ?? '') === 'external_url' ? 'selected' : '' ?>>External Video URL</option>
                    <option value="upload" <?= ($video['source_type'] ?? '') === 'upload' ? 'selected' : '' ?>>Uploaded Video File</option>
                </select>
            </div>

            <div class="form-group" id="video-url-group" style="<?= ($video['source_type'] ?? '') === 'upload' ? 'display:none;' : '' ?>">
                <label class="form-label">Video URL</label>
                <input type="url" name="video_url" class="form-control" value="<?= e($video['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div class="form-group" id="video-file-group" style="<?= ($video['source_type'] ?? '') === 'upload' ? '' : 'display:none;' ?>">
                <label class="form-label">Replace Video File</label>
                <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm">
                <?php if (!empty($video['video_path'])): ?>
                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.35rem;">Current: <?= e($video['video_path']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content Notes -->
        <div class="form-group">
            <label class="form-label">Lesson Notes & Text Content</label>
            <textarea name="content" class="form-control" rows="8"><?= e($lesson['content']) ?></textarea>
        </div>

        <!-- Attached Materials List & Upload -->
        <div class="form-group">
            <label class="form-label">Attached Class Materials</label>
            <?php if (!empty($materials)): ?>
                <div style="margin-bottom: 1rem;">
                    <?php foreach ($materials as $m): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); margin-bottom: 0.4rem;">
                            <span><i class="bx bx-paperclip"></i> <?= e($m['original_filename']) ?></span>
                            <a href="<?= url('/admin/lessons/' . $lesson['id'] . '/materials/' . $m['id'] . '/delete') ?>" class="btn btn-outline btn-sm" style="color:var(--danger); padding:2px 8px;" data-confirm="Delete this attachment?">Delete</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label class="form-label" style="font-size: 0.82rem;">Upload Additional Materials</label>
            <input type="file" name="materials[]" class="form-control" multiple>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Completion Requirement *</label>
                <select name="completion_rule" class="form-control">
                    <option value="manual" <?= $lesson['completion_rule'] === 'manual' ? 'selected' : '' ?>>Manual Click</option>
                    <option value="video_90" <?= $lesson['completion_rule'] === 'video_90' ? 'selected' : '' ?>>90% Video Watched</option>
                    <option value="pdf_open" <?= $lesson['completion_rule'] === 'pdf_open' ? 'selected' : '' ?>>Material Opened</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Publishing Status *</label>
                <select name="status" class="form-control">
                    <option value="published" <?= $lesson['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $lesson['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Update Lesson</button>
            <a href="<?= url('/admin/lessons/' . $lesson['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleVideoSourceInput(sourceType) {
    document.getElementById('video-url-group').style.display = (sourceType === 'upload') ? 'none' : 'block';
    document.getElementById('video-file-group').style.display = (sourceType === 'upload') ? 'block' : 'none';
}
</script>
