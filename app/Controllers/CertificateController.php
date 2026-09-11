<?php

namespace App\Controllers;

use App\Database;
use App\Helpers\AuthHelper;
use App\Helpers\HashId;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Services\MailService;
use Throwable;

/**
 * Lazy LMS - Expanded Certificate Management System
 * Supports manual assignment, batch-wise issuance, platform-wide issuance, revocation, and restoration.
 */
class CertificateController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        if ($user['role'] === 'student') {
            $stmt = $db->prepare("
                SELECT cert.*, c.title as course_title, c.code as course_code
                FROM certificates cert
                JOIN courses c ON cert.course_id = c.id
                WHERE cert.user_id = ? AND cert.status = 'issued'
                ORDER BY cert.issue_date DESC
            ");
            $stmt->execute([$user['id']]);
            $certificates = $stmt->fetchAll();

            $this->render('certificates/student_index', [
                'certificates' => $certificates,
                'user' => $user
            ]);
            return;
        }

        // Admin & Teacher
        $this->requirePermission('reports.view');
        $stmt = $db->query("
            SELECT cert.*, c.title as course_title, u.name as student_name, u.email as student_email,
                   b.name as batch_name
            FROM certificates cert
            JOIN courses c ON cert.course_id = c.id
            JOIN users u ON cert.user_id = u.id
            LEFT JOIN batches b ON cert.batch_id = b.id
            ORDER BY cert.issue_date DESC
        ");
        $certificates = $stmt->fetchAll();

        $batches = $db->query("SELECT id, name FROM batches WHERE status = 'active' ORDER BY name ASC")->fetchAll();
        $courses = $db->query("SELECT id, title FROM courses WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $students = $db->query("SELECT u.id, u.name, u.email FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'student' AND u.status = 'active' ORDER BY u.name ASC")->fetchAll();

        $this->render('certificates/index', [
            'certificates' => $certificates,
            'batches' => $batches,
            'courses' => $courses,
            'students' => $students,
            'user' => $user
        ]);
    }

    public function show(string|int $id): void
    {
        $this->requireAuth();
        $user = $this->currentUser();
        $db = Database::getInstance();

        $cId = HashId::resolveId('certificates', $id);
        if (!$cId) Response::abort(404);

        $stmt = $db->prepare("
            SELECT cert.*, c.title as course_title, c.code as course_code, u.name as student_name,
                   b.name as batch_name
            FROM certificates cert
            JOIN courses c ON cert.course_id = c.id
            JOIN users u ON cert.user_id = u.id
            LEFT JOIN batches b ON cert.batch_id = b.id
            WHERE cert.id = ?
        ");
        $stmt->execute([$cId]);
        $cert = $stmt->fetch();
        if (!$cert) Response::abort(404);

        if ($user['role'] === 'student') {
            if ((int)$cert['user_id'] !== (int)$user['id'] || $cert['status'] === 'revoked') {
                Response::abort(403);
            }
        }

        $this->render('certificates/view', [
            'certificate' => $cert,
            'user' => $user
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $userId = (int)Request::post('user_id');
        $courseId = HashId::resolveId('courses', Request::post('course_id'));
        $batchId = !empty(Request::post('batch_id')) ? HashId::resolveId('batches', Request::post('batch_id')) : null;

        if (!$userId || !$courseId) {
            Session::flash('error', 'Student and Course selection are required.');
            Response::redirect('/certificates');
        }

        $db = Database::getInstance();
        $publicId = HashId::generate('certificates');
        $certNum = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
        $verCode = strtoupper(bin2hex(random_bytes(6)));

        $stmt = $db->prepare("
            INSERT INTO certificates (
                public_id, user_id, course_id, batch_id, certificate_number, certificate_code,
                verification_code, issue_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), 'issued', datetime('now'))
        ");
        $stmt->execute([$publicId, $userId, $courseId, $batchId, $certNum, $certNum, $verCode]);

        $certId = $db->lastInsertId();

        // Send student email notification
        $student = Database::fetchOne("SELECT id, name, email FROM users WHERE id = ?", [$userId]);
        $course = Database::fetchOne("SELECT title FROM courses WHERE id = ?", [$courseId]);
        if ($student) {
            MailService::sendTemplate('certificate.issued', $student, [
                'course_name' => $course['title'] ?? 'Course',
                'certificate_number' => $certNum,
                'certificate_url' => url('/certificates/' . $publicId)
            ]);
        }

        $this->logAudit('certificate.issue', 'certificates', $certId, ['user_id' => $userId, 'number' => $certNum]);
        Session::flash('success', "Certificate {$certNum} issued successfully.");
        Response::redirect('/certificates');
    }

    public function issueBatch(): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $batchId = HashId::resolveId('batches', Request::post('batch_id'));
        $courseId = HashId::resolveId('courses', Request::post('course_id'));

        if (!$batchId || !$courseId) {
            Session::flash('error', 'Both Batch and Course must be selected.');
            Response::redirect('/certificates');
        }

        $db = Database::getInstance();
        $students = Database::fetchAll("
            SELECT u.id, u.name, u.email 
            FROM batch_students bs 
            JOIN users u ON bs.student_id = u.id 
            WHERE bs.batch_id = ? AND bs.status = 'active'
        ", [$batchId]);

        $course = Database::fetchOne("SELECT title FROM courses WHERE id = ?", [$courseId]);
        $issuedCount = 0;

        foreach ($students as $st) {
            // Check if certificate already exists for this student & course
            $exists = Database::fetchColumn("SELECT id FROM certificates WHERE user_id = ? AND course_id = ? LIMIT 1", [$st['id'], $courseId]);
            if (!$exists) {
                $publicId = HashId::generate('certificates');
                $certNum = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
                $verCode = strtoupper(bin2hex(random_bytes(6)));

                $stmt = $db->prepare("
                    INSERT INTO certificates (
                        public_id, user_id, course_id, batch_id, certificate_number, certificate_code,
                        verification_code, issue_date, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), 'issued', datetime('now'))
                ");
                $stmt->execute([$publicId, $st['id'], $courseId, $batchId, $certNum, $certNum, $verCode]);

                MailService::sendTemplate('certificate.issued', $st, [
                    'course_name' => $course['title'] ?? 'Course',
                    'certificate_number' => $certNum,
                    'certificate_url' => url('/certificates/' . $publicId)
                ]);

                $issuedCount++;
            }
        }

        Session::flash('success', "Issued {$issuedCount} certificates to batch students successfully.");
        Response::redirect('/certificates');
    }

    public function revoke(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $cId = HashId::resolveId('certificates', $id);
        if ($cId) {
            Database::query("UPDATE certificates SET status = 'revoked' WHERE id = ?", [$cId]);
            $this->logAudit('certificate.revoke', 'certificates', $cId);
            Session::flash('success', 'Certificate revoked successfully.');
        }

        Response::redirect('/certificates');
    }

    public function restore(string|int $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->validateCsrf();

        $cId = HashId::resolveId('certificates', $id);
        if ($cId) {
            Database::query("UPDATE certificates SET status = 'issued' WHERE id = ?", [$cId]);
            $this->logAudit('certificate.restore', 'certificates', $cId);
            Session::flash('success', 'Certificate restored successfully.');
        }

        Response::redirect('/certificates');
    }

    // Public verification endpoint: /certificate/verify/{code}
    public function verify(string $code): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT cert.*, c.title as course_title, u.name as student_name
            FROM certificates cert
            JOIN courses c ON cert.course_id = c.id
            JOIN users u ON cert.user_id = u.id
            WHERE cert.certificate_code = ? OR cert.verification_code = ?
        ");
        $stmt->execute([$code, $code]);
        $cert = $stmt->fetch();

        // Render minimal public view
        extract(['certificate' => $cert, 'code' => $code]);
        require dirname(__DIR__) . '/Views/certificates/verify.php';
    }
}
