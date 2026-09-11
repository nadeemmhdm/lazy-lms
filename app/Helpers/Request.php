<?php

namespace App\Helpers;

class Request {
    protected array $query;
    protected array $post;
    protected array $files;
    protected array $server;
    protected ?array $json = null;

    public function __construct() {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;

        // Parse JSON body if Content-Type is application/json
        $contentType = $this->header('CONTENT_TYPE', '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->json = $decoded;
            }
        }
    }

    public function method(): string {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($this->post['_method'])) {
            return strtoupper($this->post['_method']);
        }
        return $method;
    }

    public function isMethod(string $method): bool {
        return $this->method() === strtoupper($method);
    }

    public function isPost(): bool {
        return $this->isMethod('POST');
    }

    public function isGet(): bool {
        return $this->isMethod('GET');
    }

    public function path(): string {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Normalize leading and trailing slashes
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public static function post(string $key, mixed $default = null): mixed {
        $req = new self();
        return $req->input($key, $default);
    }

    public static function get(string $key, mixed $default = null): mixed {
        $req = new self();
        return $req->query($key, $default);
    }

    public static function isAjax(): bool {
        $req = new self();
        return strtolower($req->header('X_REQUESTED_WITH', '')) === 'xmlhttprequest' ||
               str_contains($req->header('ACCEPT', ''), 'application/json');
    }

    public function input(string $key, mixed $default = null): mixed {
        if ($this->json !== null && array_key_exists($key, $this->json)) {
            return $this->json[$key];
        }
        if (array_key_exists($key, $this->post)) {
            return is_string($this->post[$key]) ? trim($this->post[$key]) : $this->post[$key];
        }
        if (array_key_exists($key, $this->query)) {
            return is_string($this->query[$key]) ? trim($this->query[$key]) : $this->query[$key];
        }
        return $default;
    }

    public function query(string $key, mixed $default = null): mixed {
        return $this->query[$key] ?? $default;
    }

    public function all(): array {
        $data = array_merge($this->query, $this->post);
        if ($this->json !== null) {
            $data = array_merge($data, $this->json);
        }
        return $data;
    }

    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function header(string $key, mixed $default = null): mixed {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($this->server[$normalized])) {
            return $this->server[$normalized];
        }
        if (isset($this->server[strtoupper($key)])) {
            return $this->server[strtoupper($key)];
        }
        return $default;
    }

    public function ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $header) {
            if (!empty($this->server[$header])) {
                $ips = explode(',', $this->server[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }

    public function userAgent(): string {
        return $this->server['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    public function validate(array $rules): array {
        $errors = [];
        $data = $this->all();

        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $val = $this->input($field);

            foreach ($ruleList as $r) {
                $params = [];
                if (str_contains($r, ':')) {
                    [$r, $paramStr] = explode(':', $r, 2);
                    $params = explode(',', $paramStr);
                }

                if ($r === 'required' && ($val === null || $val === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                    break;
                }

                if (($val !== null && $val !== '') || in_array('required', $ruleList)) {
                    if ($r === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = 'Please provide a valid email address.';
                    } elseif ($r === 'min' && strlen((string)$val) < (int)$params[0]) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$params[0]} characters.";
                    } elseif ($r === 'max' && strlen((string)$val) > (int)$params[0]) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$params[0]} characters.";
                    } elseif ($r === 'numeric' && !is_numeric($val)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid number.';
                    } elseif ($r === 'in' && !in_array($val, $params)) {
                        $errors[$field] = 'Selected value is invalid.';
                    } elseif ($r === 'confirmed') {
                        $confirmationField = $field . '_confirmation';
                        if ($this->input($confirmationField) !== $val) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match.';
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            Session::setOld($data);
            Session::flash('errors', $errors);
            Session::flash('error', reset($errors));
        }

        return $errors;
    }
}
