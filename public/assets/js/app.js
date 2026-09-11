/**
 * Open LMS - Interactive Client UI Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSidebar();
    initModals();
    initTabs();
    initConfirmations();
    initAutoDismissToasts();
    initVideoTracker();
    initAssessmentTimer();
});

// Theme Management
function initTheme() {
    const savedTheme = localStorage.getItem('open_lms_theme') || 
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);

    const toggleBtn = document.getElementById('theme-toggle-btn');
    if (toggleBtn) {
        updateThemeIcon(toggleBtn, savedTheme);
        toggleBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('open_lms_theme', next);
            updateThemeIcon(toggleBtn, next);
        });
    }
}

function updateThemeIcon(btn, theme) {
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = theme === 'dark' ? 'bx bx-sun' : 'bx bx-moon';
    }
}

// Mobile Sidebar Drawer
function initSidebar() {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay && sidebar) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

// Modal Controller
function initModals() {
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-modal-target');
            const modal = document.querySelector(targetId);
            if (modal) {
                modal.classList.add('active');
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
}

// Tab Switching
function initTabs() {
    document.querySelectorAll('.tab-nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.tabs-container');
            if (!container) return;

            const targetTab = btn.getAttribute('data-tab');

            container.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
            container.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const pane = container.querySelector(`.tab-pane[data-tab-content="${targetTab}"]`);
            if (pane) pane.classList.add('active');
        });
    });
}

// Confirmation Dialogs
function initConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Toast Notifications
function showToast(type, message) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'bx-info-circle';
    if (type === 'success') icon = 'bx-check-circle';
    if (type === 'danger') icon = 'bx-error-circle';
    if (type === 'warning') icon = 'bx-alarm-exclamation';

    toast.innerHTML = `
        <i class="bx ${icon}" style="font-size:1.25rem;"></i>
        <div style="flex:1; font-size:0.9rem;">${message}</div>
        <button style="background:none; border:none; color:var(--text-muted); cursor:pointer;" onclick="this.parentElement.remove();">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function initAutoDismissToasts() {
    document.querySelectorAll('.toast').forEach(toast => {
        setTimeout(() => {
            toast.remove();
        }, 5000);
    });
}

// HTML5 Video Completion & 90% Watch Tracker
function initVideoTracker() {
    const video = document.getElementById('lesson-video-player');
    if (!video) return;

    const lessonId = video.getAttribute('data-lesson-id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!lessonId) return;

    let maxPercent = 0;

    video.addEventListener('timeupdate', () => {
        if (!video.duration) return;
        const currentPercent = (video.currentTime / video.duration) * 100;
        if (currentPercent > maxPercent) {
            maxPercent = currentPercent;
        }

        // Periodically sync watch state when passing increments
        if (Math.floor(maxPercent) % 10 === 0 && Math.floor(maxPercent) > 0) {
            syncVideoProgress(lessonId, video.currentTime, maxPercent, csrfToken);
        }
    });

    video.addEventListener('ended', () => {
        syncVideoProgress(lessonId, video.duration, 100, csrfToken);
    });
}

function syncVideoProgress(lessonId, secondsWatched, percentage, csrfToken) {
    fetch(`/student/lessons/${lessonId}/video-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            seconds_watched: Math.floor(secondsWatched),
            percentage: Math.min(100, Math.round(percentage * 10) / 10)
        })
    }).catch(() => {});
}

// Timed Assessment Countdown
function initAssessmentTimer() {
    const timerDisplay = document.getElementById('assessment-timer');
    const form = document.getElementById('assessment-quiz-form');
    if (!timerDisplay || !form) return;

    let secondsLeft = parseInt(timerDisplay.getAttribute('data-seconds-left'), 10) || 0;

    const interval = setInterval(() => {
        if (secondsLeft <= 0) {
            clearInterval(interval);
            timerDisplay.innerText = 'Time Expired!';
            showToast('danger', 'Time expired! Auto-submitting your assessment now...');
            setTimeout(() => {
                form.submit();
            }, 1200);
            return;
        }

        secondsLeft--;
        const mins = Math.floor(secondsLeft / 60);
        const secs = secondsLeft % 60;
        timerDisplay.innerText = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }, 1000);
}
