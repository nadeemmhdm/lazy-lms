-- Lazy LMS Migration 003: Platform Notifications, Email Templates, Security & Calendar

-- 1. Targeted Platform Notifications
CREATE TABLE IF NOT EXISTS platform_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    target_type TEXT NOT NULL CHECK(target_type IN ('all', 'batch', 'teachers', 'students', 'user')),
    target_id INTEGER,
    priority TEXT NOT NULL DEFAULT 'normal' CHECK(priority IN ('normal', 'important', 'urgent')),
    send_email INTEGER NOT NULL DEFAULT 0,
    start_time DATETIME,
    expiry_time DATETIME,
    status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('draft', 'published')),
    created_by INTEGER,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_p_notifications_public_id ON platform_notifications(public_id);

CREATE TABLE IF NOT EXISTS notification_reads (
    notification_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    read_at DATETIME NOT NULL,
    PRIMARY KEY (notification_id, user_id),
    FOREIGN KEY (notification_id) REFERENCES platform_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Email Templates & Notification Jobs Queue
CREATE TABLE IF NOT EXISTS email_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_key TEXT NOT NULL UNIQUE,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    is_enabled INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS notification_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- 'email', 'in_app'
    recipient_email TEXT,
    subject TEXT,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT,
    created_at DATETIME NOT NULL,
    sent_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Remember Me Persistent Tokens, Login Devices & Security Audit Events
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT NOT NULL,
    first_seen_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS security_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    event_type TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT,
    details TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_sec_events_type ON security_events(event_type);

-- 4. Scheduled Classes & Calendar Events
CREATE TABLE IF NOT EXISTS scheduled_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    title TEXT NOT NULL,
    course_id INTEGER NOT NULL,
    unit_id INTEGER,
    lesson_id INTEGER,
    batch_id INTEGER NOT NULL,
    teacher_id INTEGER,
    class_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    meeting_url TEXT,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'in_progress', 'completed', 'cancelled')),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_scheduled_classes_public_id ON scheduled_classes(public_id);

-- 5. Admin Quick Links / Resource Visibility Controls
CREATE TABLE IF NOT EXISTS admin_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    icon TEXT DEFAULT 'bx-link',
    visibility_type TEXT NOT NULL DEFAULT 'all' CHECK(visibility_type IN ('all', 'batch', 'teachers', 'students', 'course', 'user')),
    target_id INTEGER,
    is_visible INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
);

-- Add public_id columns to existing core tables if they don't have them
ALTER TABLE users ADD COLUMN public_id TEXT;
ALTER TABLE batches ADD COLUMN public_id TEXT;
ALTER TABLE courses ADD COLUMN public_id TEXT;
ALTER TABLE units ADD COLUMN public_id TEXT;
ALTER TABLE lessons ADD COLUMN public_id TEXT;
ALTER TABLE certificates ADD COLUMN public_id TEXT;
ALTER TABLE certificates ADD COLUMN batch_id INTEGER REFERENCES batches(id) ON DELETE SET NULL;
ALTER TABLE certificates ADD COLUMN status TEXT NOT NULL DEFAULT 'issued' CHECK(status IN ('issued', 'revoked'));
ALTER TABLE certificates ADD COLUMN qr_code TEXT;
ALTER TABLE certificates ADD COLUMN admin_signature TEXT;
