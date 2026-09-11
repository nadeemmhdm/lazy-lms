<div align="center">

# 🎓 Lazy LMS

### *A Modern, Secure, Self-Hosted Learning Management System*

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Database](https://img.shields.io/badge/Database-SQLite3%20%2F%20PDO-003B57?style=flat-square&logo=sqlite&logoColor=white)](https://sqlite.org)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)
[![Security](https://img.shields.io/badge/Security-OWASP%20Compliant-blue?style=flat-square)](SECURITY.md)
[![Tests](https://img.shields.io/badge/Tests-16%20Passing-brightgreen?style=flat-square)](tests/test_all.php)
[![Responsive](https://img.shields.io/badge/UI-Responsive%20%26%20Mobile--First-orange?style=flat-square)](#-responsive-design)

</div>

---

## 📖 Overview

**Lazy LMS** is an enterprise-grade, self-hosted Learning Management System built purely with standard **PHP 8.2+**, **SQLite3 (PDO)**, **HTML5**, **CSS3 (Custom Properties & Glassmorphism)**, and **Vanilla JavaScript**.

Designed from the ground up for schools, academies, universities, and private training organizations, Lazy LMS delivers rich capabilities without the complexity, bloat, or maintenance overhead of large frameworks like Laravel or Symfony. Standard PHP hosting with SQLite is all you need.

---

## 🌟 Core Features & Modules

### 1. 📚 Academic Hierarchy & Subject Architecture
* **Structured Learning**: Supports `Batch → Subject → Course → Unit → Lesson → Materials` as well as the streamlined `Batch → Course → Unit → Lesson` model.
* **Multi-Course Batches**: One batch can enroll in multiple subjects and courses; one course contains multiple units and lessons.
* **Seamless Batch Transitions**: Transfer students between batches with customizable course retention or replacement, fully logged in enrollment history.

### 2. 📝 Expanded Assignment System
* **MCQ Assignments**: Multi-option questions, single correct answer, custom marks, automatic grading, attempt limits, and cooldown intervals.
* **Short Answer Assignments**: Text answers with character/word count boundaries, optional automatic or teacher manual grading, and feedback.
* **Descriptive Assignments**: Flexible submission methods chosen by instructors:
  * *Write Online* (rich text answer)
  * *Upload File* (safe document submission)
  * *Write OR Upload File* (student chooses preferred method)
* **Late Submission Handling**: Configurable policies for on-time vs. late submissions.
* **Protected Storage**: Student submission files are stored strictly outside the webroot at `storage/uploads/submissions/` and streamed exclusively via authorized endpoints (`/submissions/download/{id}`).

### 3. ⏱️ Lightweight Lesson Quizzes
* **Directly Attached to Lessons**: Seamlessly integrated into curriculum units.
* **MCQ-Only Evaluation**: Focused, rapid comprehension checks.
* **Automatic Progress Tracking**: Earning a passing score instantly marks the corresponding lesson and course progress as complete.
* **Cooldown & Attempt Management**: Prevents rapid-fire retakes with configurable cooldown timers.

### 4. 🏛️ Robust Examination System
* **Multi-Type Questions**: Combine Multiple Choice (auto-graded), Short Answer (manual grading), and Descriptive (write or file upload) questions in a single exam.
* **Server-Side Countdown Timer**: Prevents client-side manipulation; exam duration is strictly calculated on the server.
* **Auto-Save Engine**: Periodically auto-saves student draft answers every 30 seconds via background AJAX calls.
* **Auto-Submit on Expiry**: Automatically collects and finalizes student submissions when time expires.
* **Disconnection Resilience**: Browser reloads or network drops restore active attempts without data loss.
* **Teacher Evaluation Dashboard**: Centralized grading hub to review descriptive answers, download submitted files, provide constructive feedback, and award marks.

### 5. 🔔 Platform Notification System
* **Audience Targeting**: Broadcast announcements to:
  * *Entire Platform*
  * *Specific Batch*
  * *Teachers Only*
  * *Students Only*
  * *Specific Users*
* **Priority Levels**: Categorize updates as `Normal`, `Important`, or `Urgent`.
* **In-App Notification Center**: Topbar notification bell with unread counters, mark as read, mark all read, and historical audit.
* **Email Notification Dispatch**: Optional toggle to dispatch emails alongside in-app alerts.

### 6. 📅 Advanced Calendar & Scheduled Classes
* **Multiple Calendar Views**: Full calendar supporting `Month`, `Week`, `Day`, and `Agenda` views.
* **Interactive Day-Click Modal**: Instantly view all scheduled items for a selected date (classes, assignment deadlines, lesson quizzes, and exams).
* **Live Scheduled Classes**: Create virtual classrooms with integrated meeting URLs supporting **Zoom**, **Google Meet**, **Microsoft Teams**, or custom platforms.

### 7. 🔒 Enterprise Security & Session Management
* **5-Hour Continuous Session Policy**: Authenticated sessions remain active for up to 5 hours of continuous use with server-side validation.
* **12-Hour Persistent "Remember Me"**: Secure rotating token saved with SHA-256 server-side hash via `HttpOnly`, `SameSite=Lax`, and `Secure` cookies.
* **New IP / Device Login Detection**: Automatically registers new login devices, records security events, and dispatches instant security alert emails.
* **Non-Sequential 5–8 Character Public IDs (`HashId`)**: All entities (users, courses, units, lessons, assignments, exams, attempts, certificates, notifications, etc.) utilize unique 5–8 character alphanumeric IDs (`[A-Za-z0-9]`), completely preventing IDOR and sequential enumeration attacks.
* **Two-Factor Authentication (2FA)**: RFC 6238 TOTP support with recovery codes and QR code pairing.
* **Normalized Security Questions**: Normalized SHA-256 answer hashing with lockout thresholds.
* **One-Click Hot SQLite Backups**: Generate timestamped SQLite database backups with one click.

### 8. 🛠️ Platform Maintenance Mode
* **Instant Toggle**: Administrators can switch the platform into maintenance mode with a single click.
* **Role-Based Bypass**: Students and teachers are greeted with a customized 503 Maintenance page, while administrators retain 100% full access to `/admin` and platform settings without session invalidation.

### 9. 📧 Custom SMTP & Dynamic Email Templates
* **Native SMTP Socket Transport**: Send emails via custom external SMTP hosts with TLS/SSL encryption and fallback simulation logging.
* **Customizable Email Templates**: Manage email notifications with rich dynamic placeholders:
  * `{{user_name}}`, `{{course_name}}`, `{{batch_name}}`, `{{assignment_name}}`, `{{exam_name}}`, `{{certificate_number}}`, `{{login_time}}`, `{{ip_address}}`, `{{lms_name}}`.
* **Asynchronous Jobs Queue**: Failed delivery attempts are tracked and retried in `notification_jobs`.

### 10. 📜 Certificate Management & Verification
* **Automated & Manual Issuance**: Issue graduation certificates on 100% course completion or manually assign them to individual students, entire batches, or platform-wide audiences.
* **Revocation & Restoration**: Revoke non-compliant certificates and restore them at will.
* **Public Anti-Counterfeit Portal**: Publicly verify authenticity at `/certificate/verify/{code}` without leaking private student records.

### 11. 🔗 Admin Quick Links
* **Curated Resource Hub**: Create customized quick links for assignments, assessments, exams, external documentation, or student portals.
* **Granular Visibility**: Target links by platform, batch, students, or teachers, with instant Show/Hide toggle switches.

---

## 📱 Responsive Design

Lazy LMS is designed mobile-first and tested rigorously across standard device viewports:

| Device | Width | Support |
| --- | --- | --- |
| Mobile Small | `320px` | :white_check_mark: Full layout reflow & drawer navigation |
| Mobile Standard | `375px - 425px` | :white_check_mark: Optimized touch targets & card layouts |
| Tablet | `768px` | :white_check_mark: Responsive data grids & modal drawers |
| Desktop | `1024px - 1280px+` | :white_check_mark: Multi-column dashboards & rich split views |

---

## 🏗️ Project Architecture

Lazy LMS adheres strictly to a clean, decoupled MVC (Model-View-Controller) architecture:

```text
/
├── app/
│   ├── App.php                  # Application bootstrapper & error handling
│   ├── Database.php             # PDO SQLite singleton connection & query helpers
│   ├── Router.php               # Front-controller regex router & middleware pipeline
│   ├── routes.php               # Application route declarations
│   ├── Controllers/             # Modular controllers (Auth, Exam, Assignment, etc.)
│   ├── Helpers/                 # HashId, AuthHelper, FileHelper, I18n, Session, etc.
│   ├── Middleware/              # Auth, Role, Guest, CSRF, and Maintenance middleware
│   ├── Models/                  # Lightweight domain models
│   ├── Services/                # MailService, BackupService, TotpService
│   └── Views/                   # Clean PHP views, layout components, and error pages
├── config/                      # Configuration files (app, security, mail, database)
├── database/
│   ├── schema.sql               # Baseline database tables and indexes
│   ├── migrate.php              # Automated CLI database migration runner
│   └── migrations/              # Incremental versioned migration scripts
├── public/                      # Web root directory (ONLY directory exposed to web)
│   ├── index.php                # Unified entry point
│   ├── css/app.css              # Custom design system (vanilla CSS + tokens)
│   └── js/app.js                # Interactive vanilla JavaScript utilities
├── storage/                     # Private application storage (denied from web access)
│   ├── database.sqlite          # Primary SQLite database
│   ├── backups/                 # Hot backup archives
│   ├── logs/                    # Security, audit, and error logs
│   └── uploads/submissions/     # Secured student exam & assignment uploads
└── tests/                       # Automated test suites
    └── test_all.php             # Comprehensive 16-suite verification test runner
```

---

## 🚀 Quick Start & Installation

### Prerequisites
* PHP 8.2 or higher
* Enabled PHP extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `curl`

### Installation Steps

1. **Clone the repository**:
   ```bash
   git clone https://github.com/nadeemmhdm/lazy-lms.git
   cd lazy-lms
   ```

2. **Configure environment**:
   ```bash
   cp .env.example .env
   ```

3. **Run database migrations**:
   ```bash
   php database/migrate.php
   ```

4. **Launch development server**:
   ```bash
   php -S 127.0.0.1:8080 -t public
   ```

5. **Open Lazy LMS**:
   Navigate to [http://127.0.0.1:8080](http://127.0.0.1:8080).

### Default Test Credentials

| Role | Email | Password |
| --- | --- | --- |
| **Super Admin** | `admin@openlms.test` | `SuperSecret123!` |
| **Teacher** | `teacher@openlms.test` | `TeacherPass123!` |
| **Student** | `student@openlms.test` | `StudentPass123!` |

*(Note: In production, change default passwords immediately and enable 2FA via the Admin Security settings).*

---

## 🧪 Automated Testing

Lazy LMS includes an integrated end-to-end test suite verifying database migrations, user models, RFC 6238 TOTP, batch transitions, assignment submissions, exam auto-save timers, notification filtering, and maintenance mode:

```bash
php tests/test_all.php
```

Expected output:
```text
=======================================================
       LAZY LMS VERIFICATION & ACCEPTANCE TEST         
=======================================================

• Testing: Database Migrator creates schema, tables, foreign keys, and seeds ... [PASS]
• Testing: User model hashes passwords securely, assigns roles, and saves recovery question ... [PASS]
• Testing: Security answer normalization and lockout threshold logic works ... [PASS]
• Testing: RFC 6238 TOTP generates valid secrets, verification codes, and recovery keys ... [PASS]
• Testing: Batch, Course, Unit, and Lesson hierarchy correctly links and enforces relationships ... [PASS]
• Testing: Student batch change handles replacement, retention, and enrollment audit history ... [PASS]
• Testing: Question bank supports numeric, true/false, single choice, and scores answers accurately ... [PASS]
• Testing: 100% course completion triggers certificate creation with verifiable code ... [PASS]
• Testing: Hot SQLite backup file copy executes and is protected ... [PASS]
• Testing: Router registers all front-controller routes and API endpoints correctly ... [PASS]
• Testing: HashId generates secure 5-8 character alphanumeric public IDs and resolves collisions ... [PASS]
• Testing: Remember-me stores 12-hour rotating token and tracks new device/IP login security events ... [PASS]
• Testing: Lesson quiz validates MCQ answers, calculates passing score, and auto-updates lesson progress ... [PASS]
• Testing: Examination system supports MCQ, Short Answer, Descriptive, timer bounds, and auto-save ... [PASS]
• Testing: Platform notification system filters target audiences and manages quick links ... [PASS]
• Testing: Maintenance mode toggles state and preserves full administrator management bypass ... [PASS]

=======================================================
TEST RESULTS: 16 Passed, 0 Failed
=======================================================
```

---

## 🔒 Security & Vulnerabilities

Please review our [SECURITY.md](SECURITY.md) for our vulnerability disclosure process, threat model, and secure configuration guidelines.

---

## 🤝 Contributing

We welcome community contributions! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our coding standards, branch naming, and pull request workflow.

---

## 📄 License

Lazy LMS is open-source software licensed under the [MIT License](LICENSE).
<br>
*Powered by Lazy LMS*
