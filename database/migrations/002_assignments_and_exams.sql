-- Lazy LMS Migration 002: Assignments Expansion, Lesson Quizzes, Exams & Subjects

-- 1. Subjects Entity (Optional academic hierarchy: Batch -> Subject -> Course -> Unit -> Lesson)
CREATE TABLE IF NOT EXISTS subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    batch_id INTEGER,
    name TEXT NOT NULL,
    code TEXT NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_subjects_public_id ON subjects(public_id);

-- 2. Expanded Assignment Types and Submissions
-- Add public_id, assignment_type, submission_mode to assignments if not exists
ALTER TABLE assignments ADD COLUMN public_id TEXT;
ALTER TABLE assignments ADD COLUMN assignment_type TEXT NOT NULL DEFAULT 'descriptive'; -- 'mcq', 'short_answer', 'descriptive'
ALTER TABLE assignments ADD COLUMN submission_mode TEXT NOT NULL DEFAULT 'both'; -- 'write', 'upload', 'both'
ALTER TABLE assignments ADD COLUMN start_date DATETIME;
ALTER TABLE assignments ADD COLUMN max_attempts INTEGER NOT NULL DEFAULT 1;
ALTER TABLE assignments ADD COLUMN attempt_cooldown_hours INTEGER NOT NULL DEFAULT 0;
ALTER TABLE assignments ADD COLUMN late_allowed INTEGER NOT NULL DEFAULT 1;
ALTER TABLE assignments ADD COLUMN allowed_extensions TEXT DEFAULT 'pdf,doc,docx,ppt,pptx,zip,txt';
ALTER TABLE assignments ADD COLUMN max_file_size_mb INTEGER NOT NULL DEFAULT 25;
ALTER TABLE assignments ADD COLUMN max_files INTEGER NOT NULL DEFAULT 3;

-- Assignment Questions (For MCQ assignments)
CREATE TABLE IF NOT EXISTS assignment_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    assignment_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    marks REAL NOT NULL DEFAULT 1.0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Assignment Options (For MCQ assignment questions)
CREATE TABLE IF NOT EXISTS assignment_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    is_correct INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES assignment_questions(id) ON DELETE CASCADE
);

-- Expand assignment submissions with attempt numbers and submission text
ALTER TABLE assignment_submissions ADD COLUMN public_id TEXT;
ALTER TABLE assignment_submissions ADD COLUMN attempt_number INTEGER NOT NULL DEFAULT 1;
ALTER TABLE assignment_submissions ADD COLUMN is_late INTEGER NOT NULL DEFAULT 0;

-- 3. Lesson Quizzes (MCQ only, attached directly to a lesson)
CREATE TABLE IF NOT EXISTS lesson_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    lesson_id INTEGER NOT NULL UNIQUE,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    instructions TEXT,
    passing_score REAL NOT NULL DEFAULT 50.0,
    max_attempts INTEGER NOT NULL DEFAULT 3,
    cooldown_minutes INTEGER NOT NULL DEFAULT 0,
    randomize_questions INTEGER NOT NULL DEFAULT 0,
    randomize_options INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('draft', 'published')),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_lesson_quizzes_public_id ON lesson_quizzes(public_id);

CREATE TABLE IF NOT EXISTS lesson_quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    marks REAL NOT NULL DEFAULT 1.0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES lesson_quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lesson_quiz_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    is_correct INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES lesson_quiz_questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lesson_quiz_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    quiz_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    attempt_number INTEGER NOT NULL DEFAULT 1,
    score REAL DEFAULT 0.0,
    max_score REAL DEFAULT 0.0,
    is_passed INTEGER NOT NULL DEFAULT 0,
    started_at DATETIME NOT NULL,
    completed_at DATETIME,
    FOREIGN KEY (quiz_id) REFERENCES lesson_quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Formal Examination System (MCQ, Short Answer, Descriptive with Write / Upload)
CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    batch_id INTEGER,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    instructions TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    total_marks REAL NOT NULL DEFAULT 100.0,
    passing_score REAL NOT NULL DEFAULT 40.0,
    max_attempts INTEGER NOT NULL DEFAULT 1,
    cooldown_minutes INTEGER NOT NULL DEFAULT 0,
    randomize_questions INTEGER NOT NULL DEFAULT 0,
    randomize_options INTEGER NOT NULL DEFAULT 0,
    auto_submit INTEGER NOT NULL DEFAULT 1,
    status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('draft', 'published', 'archived')),
    created_by INTEGER,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_exams_public_id ON exams(public_id);

CREATE TABLE IF NOT EXISTS exam_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    exam_id INTEGER NOT NULL,
    question_type TEXT NOT NULL CHECK(question_type IN ('mcq', 'short_answer', 'descriptive')),
    descriptive_mode TEXT NOT NULL DEFAULT 'both' CHECK(descriptive_mode IN ('write', 'upload', 'both')),
    question_text TEXT NOT NULL,
    marks REAL NOT NULL DEFAULT 1.0,
    options_json TEXT, -- For MCQ options
    correct_answer TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id TEXT UNIQUE,
    exam_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    attempt_number INTEGER NOT NULL DEFAULT 1,
    started_at DATETIME NOT NULL,
    submitted_at DATETIME,
    auto_saved_at DATETIME,
    answers_json TEXT, -- Auto-saved intermediate state
    score REAL DEFAULT 0.0,
    max_score REAL DEFAULT 0.0,
    is_passed INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'in_progress' CHECK(status IN ('in_progress', 'submitted', 'graded')),
    graded_by INTEGER,
    graded_at DATETIME,
    teacher_feedback TEXT,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);
