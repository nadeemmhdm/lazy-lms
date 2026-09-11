<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/courses/' . $lesson['course_id']) ?>"><?= e($lesson['course_title']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span><?= e($lesson['title']) ?></span>
        </div>
        <h1 class="page-title"><?= e($lesson['title']) ?></h1>
        <p class="page-subtitle">Unit: <strong><?= e($lesson['unit_title']) ?></strong> &bull; Type: <?= ucfirst($lesson['lesson_type']) ?> &bull; Duration: <?= $lesson['duration_minutes'] ?> mins</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?= url('/lessons/' . $lesson['id'] . '/quiz') ?>" class="btn btn-outline btn-sm"><i class="bx bx-help-circle"></i> Lesson Quiz</a>
        <a href="<?= url('/student/lessons/' . $lesson['id']) ?>" class="btn btn-outline btn-sm" target="_blank"><i class="bx bx-show"></i> Student Preview</a>
        <a href="<?= url('/admin/lessons/' . $lesson['id'] . '/edit') ?>" class="btn btn-primary btn-sm"><i class="bx bx-edit"></i> Edit Lesson</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start;">
    <div>
        <!-- Video Preview if present -->
        <?php if ($video): ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <?php if ($video['source_type'] === 'upload' && $video['video_path']): ?>
                    <video controls style="width: 100%; max-height: 480px; background: #000;">
                        <source src="<?= url('/download/file/' . $video['video_path']) ?>" type="video/mp4">
                        Your browser does not support HTML5 video.
                    </video>
                <?php elseif ($video['source_type'] === 'youtube' && $video['video_url']): ?>
                    <?php
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $video['video_url'], $matches);
                        $ytId = $matches[1] ?? '';
                    ?>
                    <?php if ($ytId): ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                            <iframe src="https://www.youtube-nocookie.com/embed/<?= e($ytId) ?>" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <p style="padding: 1.5rem; color: var(--danger);">Invalid YouTube URL configured.</p>
                    <?php endif; ?>
                <?php elseif ($video['source_type'] === 'vimeo' && $video['video_url']): ?>
                    <?php
                        preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)/', $video['video_url'], $matches);
                        $vimeoId = $matches[3] ?? '';
                    ?>
                    <?php if ($vimeoId): ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                            <iframe src="https://player.vimeo.com/video/<?= e($vimeoId) ?>" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Lesson Notes -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-notepad"></i> Lesson Study Notes</div>
            </div>
            <div style="line-height: 1.7; color: var(--text);">
                <?= nl2br(e($lesson['content'] ?? 'No notes written for this lesson.')) ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Materials & Discussions -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-file"></i> Attached Materials</div>
            </div>
            <div>
                <?php if (empty($materials)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">No files attached to this lesson.</p>
                <?php else: ?>
                    <?php foreach ($materials as $m): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                            <div style="font-size: 0.85rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <i class="bx bx-paperclip"></i> <?= e($m['original_filename']) ?>
                            </div>
                            <a href="<?= url('/download/file/' . $m['file_path']) ?>" class="btn btn-outline btn-sm" title="Download"><i class="bx bx-download"></i></a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-cog"></i> Lesson Rules</div>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.65rem;">
                <div>
                    <span style="color: var(--text-muted); display: block;">Completion Rule</span>
                    <code><?= e($lesson['completion_rule']) ?></code>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block;">Status</span>
                    <span class="badge <?= $lesson['status'] === 'published' ? 'badge-success' : 'badge-neutral' ?>"><?= ucfirst($lesson['status']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
