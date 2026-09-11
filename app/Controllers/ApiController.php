<?php
namespace App\Controllers;

use App\Database;
use App\Helpers\Request;
use App\Helpers\Response;

class ApiController extends BaseController
{
    private ?array $apiUser = null;

    public function __construct()
    {
        // Enforce JSON response header
        header('Content-Type: application/json; charset=utf-8');
    }

    private function authenticateApi(): bool
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = trim($matches[1]);
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role 
                FROM sessions s 
                JOIN users u ON s.user_id = u.id 
                JOIN user_roles ur ON u.id = ur.user_id 
                JOIN roles r ON ur.role_id = r.id 
                WHERE s.id = ? AND u.status = 'active'
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $this->apiUser = $user;
                return true;
            }
        }

        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: Invalid or missing bearer token']);
        exit;
    }

    public function login(): void
    {
        $email = trim(Request::post('email', ''));
        $password = Request::post('password', '');

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $token = bin2hex(random_bytes(32));
            $sStmt = $db->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, ?, ?, ?, '', ?)");
            $sStmt->execute([$token, $user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'API', time()]);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
            exit;
        }

        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    public function courses(): void
    {
        $this->authenticateApi();
        $db = Database::getInstance();

        $stmt = $db->query("SELECT id, title, code, description, status FROM courses WHERE status = 'published' ORDER BY title ASC");
        echo json_encode(['courses' => $stmt->fetchAll()]);
        exit;
    }

    public function batches(): void
    {
        $this->authenticateApi();
        $db = Database::getInstance();

        $stmt = $db->query("SELECT id, name, code, status, start_date, end_date FROM batches WHERE status = 'active' ORDER BY name ASC");
        echo json_encode(['batches' => $stmt->fetchAll()]);
        exit;
    }

    public function notifications(): void
    {
        $this->authenticateApi();
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$this->apiUser['id']]);
        echo json_encode(['notifications' => $stmt->fetchAll()]);
        exit;
    }
}
