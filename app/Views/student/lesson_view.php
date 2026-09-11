<div class="breadcrumb" style="margin-bottom: 1rem;">
    <a href="<?= url('/student/courses') ?>">Courses</a>
    <i class="bx bx-chevron-right"></i>
    <a href="<?= url('/student/courses/' . $lesson['course_id']) ?>"><?= e($lesson['course_title']) ?></a>
    <i class="bx bx-chevron-right"></i>
    <span><?= e($lesson['unit_title']) ?></span>
    <i class="bx bx-chevron-right"></i>
    <strong><?= e($lesson['title']) ?></strong>
</div>

<div class="page-header" style="margin-bottom: 1.25rem;">
    <div>
        <h1 class="page-title" style="font-size: 1.6rem;"><?= e($lesson['title']) ?></h1>
        <p class="page-subtitle"><?= e($lesson['unit_title']) ?> &bull; <i class="bx bx-time"></i> <?= $lesson['duration_minutes'] ?> mins</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <?php if (!empty($progress['is_completed'])): ?>
            <span class="badge badge-success" style="padding: 0.5rem 1rem; font-size: 0.88rem;">
                <i class="bx bx-check-double"></i> Completed
            </span>
        <?php else: ?>
            <form action="<?= url('/student/lessons/' . $lesson['id'] . '/complete') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-check"></i> Mark as Completed
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">
    <!-- Main Learning Center -->
    <div>
        <!-- Video Player -->
        <?php if ($video): ?>
            <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 1.5rem;">
                <?php if ($video['source_type'] === 'upload' && $video['video_path']): ?>
                    <video id="lesson-video-player" data-lesson-id="<?= $lesson['id'] ?>" controls style="width: 100%; max-height: 500px; background: #000;">
                        <source src="<?= url('/download/file/' . $video['video_path']) ?>" type="video/mp4">
                        Your browser does not support HTML5 video.
                    </video>
                    <?php if ($lesson['completion_rule'] === 'video_90'): ?>
                        <div style="padding: 0.65rem 1rem; font-size: 0.82rem; background: var(--bg); color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bx bx-info-circle"></i>
                            <span>You must watch at least 90% of this video to automatically complete this lesson.</span>
                        </div>
                    <?php endif; ?>
                <?php elseif ($video['source_type'] === 'youtube' && $video['video_url']): ?>
                    <?php
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $video['video_url'], $matches);
                        $ytId = $matches[1] ?? '';
                    ?>
                    <?php if ($ytId): ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                            <iframe src="https://www.youtube-nocookie.com/embed/<?= e($ytId) ?>?enablejsapi=1&rel=0" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
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

        <!-- Lesson Study Notes -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-book-open"></i> Lesson Notes</div>
            </div>
            <div style="line-height: 1.8; font-size: 0.98rem; color: var(--text);">
                <?= nl2br(e($lesson['content'] ?? 'No text notes provided for this lesson.')) ?>
            </div>
        </div>

        <!-- Previous / Next Lesson Navigation -->
        <div style="display: flex; justify-content: space-between; gap: 1rem; margin: 1.5rem 0;">
            <?php if ($prevLesson): ?>
                <a href="<?= url('/student/lessons/' . $prevLesson['id']) ?>" class="btn btn-outline">
                    <i class="bx bx-left-arrow-alt"></i> Previous: <?= e($prevLesson['title']) ?>
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <?php if ($nextLesson): ?>
                <a href="<?= url('/student/lessons/' . $nextLesson['id']) ?>" class="btn btn-primary">
                    Next: <?= e($nextLesson['title']) ?> <i class="bx bx-right-arrow-alt"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Lesson Discussion Forum Section -->
        <div class="card" id="discussions-section" style="margin-top: 2rem;">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-conversation"></i> Lesson Discussion & Q&A</div>
            </div>

            <!-- Post Question Form -->
            <form action="<?= url('/discussions') ?>" method="POST" style="margin-bottom: 2rem; background: var(--bg); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <?= csrf_field() ?>
                <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                <div class="form-group">
                    <label class="form-label">Ask a question or start a discussion</label>
                    <input type="text" name="title" class="form-control" placeholder="Question summary..." required>
                </div>
                <div class="form-group">
                    <textarea name="content" class="form-control" rows="3" placeholder="Describe your question or insight in detail..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-send"></i> Post Discussion</button>
            </form>

            <!-- Discussion List -->
            <div>
                <?php if (empty($discussions)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No questions posted yet. Be the first to start a conversation!</p>
                <?php else: ?>
                    <?php foreach ($discussions as $disc): ?>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                                    <strong><?= e($disc['author_name']) ?></strong>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);"><?= date('M j, Y H:i', strtotime($disc['created_at'])) ?></span>
                                    <?php if ($disc['is_pinned']): ?>
                                        <span class="badge badge-warning" style="font-size: 0.68rem;"><i class="bx bx-pin"></i> Pinned</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h4 style="font-size: 1.05rem; font-weight: 600; margin-bottom: 0.35rem;"><?= e($disc['title']) ?></h4>
                            <p style="font-size: 0.9rem; color: var(--text); line-height: 1.5; margin-bottom: 0.75rem;"><?= nl2br(e($disc['content'])) ?></p>

                            <!-- Replies Container -->
                            <div style="margin-left: 1.5rem; border-left: 2px solid var(--border); padding-left: 1rem;">
                                <?php
                                    $replies = \App\Database::fetchAll(
                                        "SELECT dr.*, u.name as reply_author, r.slug as role_slug 
                                         FROM discussion_replies dr 
                                         JOIN users u ON dr.user_id = u.id 
                                         LEFT JOIN user_roles ur ON u.id = ur.user_id 
                                         LEFT JOIN roles r ON ur.role_id = r.id 
                                         WHERE dr.discussion_id = ? AND dr.is_hidden = 0 
                                         ORDER BY dr.id ASC",
                                        [$disc['id']]
                                    );
                                ?>
                                <?php foreach ($replies as $rep): ?>
                                    <div style="padding: 0.5rem 0; font-size: 0.88rem;">
                                        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem;">
                                            <strong><?= e($rep['reply_author']) ?></strong>
                                            <?php if ($rep['role_slug'] === 'teacher' || $rep['role_slug'] === 'super_admin'): ?>
                                                <span class="badge badge-info" style="font-size: 0.65rem;">Instructor</span>
                                            <?php endif; ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M j, H:i', strtotime($rep['created_at'])) ?></span>
                                        </div>
                                        <div style="color: var(--text);"><?= nl2br(e($rep['content'])) ?></div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Reply Input -->
                                <?php if (!$disc['is_locked']): ?>
                                    <form action="<?= url('/discussions/' . $disc['id'] . '/reply') ?>" method="POST" style="margin-top: 0.75rem;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                        <div style="display: flex; gap: 0.5rem;">
                                            <input type="text" name="content" class="form-control" placeholder="Write a reply..." required style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                                            <button type="submit" class="btn btn-outline btn-sm">Reply</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;"><i class="bx bx-lock"></i> Discussion locked by instructor.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Class Materials & Curriculum Outline -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-folder"></i> Class Materials</div>
            </div>
            <div>
                <?php if (empty($materials)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">No additional downloadable files.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                        <?php foreach ($materials as $m): ?>
                            <a href="<?= url('/download/file/' . $m['file_path']) ?>" class="btn btn-outline" style="justify-content: flex-start; text-align: left; font-size: 0.85rem; padding: 0.65rem;">
                                <i class="bx bx-download" style="font-size: 1.2rem; color: var(--primary);"></i>
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex:1;">
                                    <?= e($m['original_filename']) ?>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);"><?= \App\Helpers\FileHelper::formatBytes((int)$m['file_size']) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-check-circle"></i> Lesson Requirement</div>
            </div>
            <div style="font-size: 0.85rem; color: var(--text);">
                <?php if ($lesson['completion_rule'] === 'video_90'): ?>
                    <p><i class="bx bx-video"></i> Watch at least 90% of the video.</p>
                <?php elseif ($lesson['completion_rule'] === 'pdf_open'): ?>
                    <p><i class="bx bx-file"></i> Open and review the class materials.</p>
                <?php else: ?>
                    <p><i class="bx bx-pointer"></i> Click "Mark as Completed" once you finish studying.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
