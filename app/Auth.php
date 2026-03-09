<?php
class Auth {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register(string $username, string $email, string $password, string $referral_code = ''): array {
        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'error' => 'Username must be 3-30 characters'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        $existing = $this->db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($existing) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }

        $referred_by = null;
        if ($referral_code) {
            $referrer = $this->db->fetch("SELECT id FROM users WHERE referral_code = ?", [$referral_code]);
            if ($referrer) $referred_by = $referrer['id'];
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $ref_code = strtolower(substr(md5(uniqid()), 0, 8));
        $api_key = bin2hex(random_bytes(20));
        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        $id = $this->db->insert(
            "INSERT INTO users (username, email, password, referral_code, referred_by, api_key, status, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)",
            [$username, $email, $hashed, $ref_code, $referred_by, $api_key, $token, $expires]
        );

        $mail = new Mail();
        $mail->sendVerification($email, $username, $token);

        return ['success' => true, 'user_id' => $id];
    }

    public function login(string $email_or_username, string $password): array {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE (email = ? OR username = ?) AND status != 'banned'",
            [$email_or_username, $email_or_username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        if (($user['status'] ?? 'active') === 'pending') {
            return ['success' => false, 'error' => 'Please verify your email first. Check your inbox for the verification link.'];
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;

        $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        return ['success' => true, 'role' => $user['role']];
    }

    public function logout(): void {
        session_destroy();
        header('Location: /login.php');
        exit;
    }

    public function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public function isAdmin(): bool {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireAdmin(): void {
        if (!$this->isAdmin()) {
            header('Location: /index.php');
            exit;
        }
    }

    public function getCurrentUser(): ?array {
        if (!$this->isLoggedIn()) return null;
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    public function getUserId(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }
}
