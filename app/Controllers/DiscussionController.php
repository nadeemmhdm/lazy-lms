<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Database;

class DiscussionController extends BaseController {
    public function store(Request $request): never {
        $lessonId = (int)$request->input('lesson_id');
        $title = trim((string)$request->input('title'));
        $content = trim((string)$request->input('content'));

        if (!$lessonId || empty($title) || empty($content)) {
            Session::flash('error', 'Please provide a title and content for your question.');
            redirect('/student');
        }

        Database::query(
            "INSERT INTO discussions (lesson_id, user_id, title, content, is_pinned, is_locked, is_hidden, created_at, updated_at) 
             VALUES (?, ?, ?, ?, 0, 0, 0, datetime('now'), datetime('now'))",
            [$lessonId, AuthHelper::id(), $title, $content]
        );

        $this->audit('discussion.created', 'lesson', $lessonId);
        Session::flash('success', 'Question posted to discussion thread.');
        redirect('/student/lessons/' . $lessonId . '#discussions-section');
    }

    public function reply(Request $request, string|int $discussionId): never {
        $content = trim((string)$request->input('content'));
        $lessonId = (int)$request->input('lesson_id');

        $disc = Database::fetchOne("SELECT * FROM discussions WHERE id = ?", [$discussionId]);
        if (!$disc || $disc['is_locked']) {
            Session::flash('error', 'Discussion thread is locked or unavailable.');
            redirect('/student');
        }

        if (empty($content)) {
            redirect('/student/lessons/' . $lessonId . '#discussions-section');
        }

        Database::query(
            "INSERT INTO discussion_replies (discussion_id, user_id, content, is_answer, is_hidden, created_at, updated_at) 
             VALUES (?, ?, ?, 0, 0, datetime('now'), datetime('now'))",
            [$discussionId, AuthHelper::id(), $content]
        );

        // Notify question author if different user
        if ($disc['user_id'] != AuthHelper::id()) {
            Database::query(
                "INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at) 
                 VALUES (?, 'New Reply on Your Question', ?, ?, 'info', 0, datetime('now'))",
                [
                    $disc['user_id'],
                    AuthHelper::user()['name'] . ' replied to your question.',
                    '/student/lessons/' . $disc['lesson_id'] . '#discussions-section'
                ]
            );
        }

        $this->audit('discussion.replied', 'discussion', (int)$discussionId);
        Session::flash('success', 'Reply posted.');
        redirect('/student/lessons/' . $lessonId . '#discussions-section');
    }

    public function pin(Request $request, string|int $id): never {
        $disc = Database::fetchOne("SELECT lesson_id, is_pinned FROM discussions WHERE id = ?", [$id]);
        if ($disc) {
            $newPin = $disc['is_pinned'] ? 0 : 1;
            Database::query("UPDATE discussions SET is_pinned = ?, updated_at = datetime('now') WHERE id = ?", [$newPin, $id]);
            Session::flash('success', $newPin ? 'Discussion pinned.' : 'Discussion unpinned.');
            redirect('/admin/lessons/' . $disc['lesson_id']);
        }
        redirect('/admin');
    }

    public function lock(Request $request, string|int $id): never {
        $disc = Database::fetchOne("SELECT lesson_id, is_locked FROM discussions WHERE id = ?", [$id]);
        if ($disc) {
            $newLock = $disc['is_locked'] ? 0 : 1;
            Database::query("UPDATE discussions SET is_locked = ?, updated_at = datetime('now') WHERE id = ?", [$newLock, $id]);
            Session::flash('success', $newLock ? 'Discussion locked.' : 'Discussion unlocked.');
            redirect('/admin/lessons/' . $disc['lesson_id']);
        }
        redirect('/admin');
    }

    public function report(Request $request, string|int $id): never {
        $reason = trim((string)$request->input('reason', 'Inappropriate content'));
        Database::query(
            "INSERT INTO discussion_reports (discussion_id, reported_by, reason, status, created_at) VALUES (?, ?, ?, 'pending', datetime('now'))",
            [$id, AuthHelper::id(), $reason]
        );
        Session::flash('success', 'Report submitted to moderators.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
