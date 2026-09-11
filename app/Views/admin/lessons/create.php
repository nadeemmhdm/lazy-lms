<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/courses/' . $course['id']) ?>"><?= e($course['title']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Add Lesson (<?= e($unit['title']) ?>)</span>
        </div>
        <h1 class="page-title">Add Lesson</h1>
    </div>
</div>

<div class="card" style="max-width: 840px;">
    <form action="<?= url('/admin/courses/' . $course['id'] . '/units/' . $unit['id'] . '/lessons') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Lesson Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Understanding HTTP Requests & Middleware" value="<?= e(old('title')) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Lesson Type *</label>
                <select name="lesson_type" id="lesson_type" class="form-control" onchange="toggleLessonMediaInputs(this.value)">
                    <option value="text">Rich Text & Notes</option>
                    <option value="video" selected>Video Lesson</option>
                    <option value="pdf">Document / PDF Study</option>
                    <option value="ppt">Slide Presentation</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Estimated Duration (Minutes)</label>
                <input type="number" name="duration_minutes" class="form-control" value="<?= e(old('duration_minutes', 15)) ?>" min="1">
            </div>
        </div>

        <!-- Video Source Section -->
        <div id="video-section" style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;"><i class="bx bx-video"></i> Video Configuration</h3>
            <div class="form-group">
                <label class="form-label">Video Source</label>
                <select name="video_source_type" id="video_source_type" class="form-control" onchange="toggleVideoSourceInput(this.value)">
                    <option value="youtube">YouTube URL</option>
                    <option value="vimeo">Vimeo URL</option>
                    <option value="external_url">External Video URL (.mp4/.webm)</option>
                    <option value="upload">Upload Video File (.mp4/.webm)</option>
                </select>
            </div>

            <div class="form-group" id="video-url-group">
                <label class="form-label">Video URL</label>
                <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/...">
            </div>

            <div class="form-group" id="video-file-group" style="display: none;">
                <label class="form-label">Upload Video File</label>
                <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm">
            </div>
        </div>

        <!-- Rich Text Notes / Content -->
        <div class="form-group">
            <label class="form-label">Lesson Notes & Text Content</label>
            <textarea name="content" class="form-control" rows="8" placeholder="Write comprehensive study notes, markdown or HTML content for this lesson..."><?= e(old('content')) ?></textarea>
        </div>

        <!-- Class Materials / File Attachments -->
        <div class="form-group">
            <label class="form-label">Attach Class Materials (PDF, PPT, PPTX, DOC, DOCX, ZIP)</label>
            <input type="file" name="materials[]" class="form-control" multiple>
            <div class="form-help">You can select multiple files to attach to this lesson.</div>
        </div>

        <!-- Completion Rule -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Completion Requirement *</label>
                <select name="completion_rule" class="form-control">
                    <option value="manual">Manual (Student clicks "Mark as Completed")</option>
                    <option value="video_90">Video Watch (Student must watch 90% of video)</option>
                    <option value="pdf_open">Document Opened (Student opens required material)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Publishing Status</label>
                <select name="status" class="form-control">
                    <option value="published">Published (Visible to students)</option>
                    <option value="draft">Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Lesson</button>
            <a href="<?= url('/admin/courses/' . $course['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleVideoSourceInput(sourceType) {
    document.getElementById('video-url-group').style.display = (sourceType === 'upload') ? 'none' : 'block';
    document.getElementById('video-file-group').style.display = (sourceType === 'upload') ? 'block' : 'none';
}
</script>
