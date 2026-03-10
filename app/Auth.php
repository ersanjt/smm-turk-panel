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
        header('Location: ' . url('login.php'));
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
            header('Location: ' . url('login.php'));
            exit;
        }
    }

    public function requireAdmin(): void {
        if (!$this->isAdmin()) {
            header('Location: ' . url('index.php'));
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

    public function updateEmail(int $userId, string $newEmail, string $currentPassword): array {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        $user = $this->db->fetch("SELECT id, password, email FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        $existing = $this->db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$newEmail, $userId]);
        if ($existing) {
            return ['success' => false, 'error' => 'Email already in use'];
        }
        $this->db->execute("UPDATE users SET email = ? WHERE id = ?", [$newEmail, $userId]);
        return ['success' => true];
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): array {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'New password must be at least 6 characters'];
        }
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
        return ['success' => true];
    }

    /**
     * Request password reset: create token and send email. Returns success true even if email not found (avoid enumeration).
     */
    public function requestPasswordReset(string $email): array {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        $user = $this->db->fetch("SELECT id, username FROM users WHERE email = ? AND status = 'active'", [$email]);
        if (!$user) {
            return ['success' => true];
        }
        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        try {
            $this->db->execute(
                "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
                [$token, $expires, $user['id']]
            );
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Password reset is not available. Please contact support.'];
        }
        $mail = new Mail();
        $mail->sendPasswordReset($email, $user['username'], $token);
        return ['success' => true];
    }

    /**
     * Reset password using token from email. Returns ['success' => bool, 'error' => string|null].
     */
    public function resetPasswordByToken(string $token, string $newPassword): array {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        $token = trim($token);
        if ($token === '') {
            return ['success' => false, 'error' => 'Invalid reset link.'];
        }
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()",
            [$token]
        );
        if (!$user) {
            return ['success' => false, 'error' => 'This reset link is invalid or has expired.'];
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->execute(
            "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?",
            [$hash, $user['id']]
        );
        return ['success' => true];
    }

    /**
     * Login or create user from Google OAuth (email, name, google_id from Google profile).
     * Returns ['success' => bool, 'error' => string|null].
     */
    public function loginOrCreateFromGoogle(string $googleId, string $email, string $name): array {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email from Google'];
        }
        $googleId = trim($googleId);
        if ($googleId === '') {
            return ['success' => false, 'error' => 'Missing Google ID'];
        }

        // Existing user by google_id
        $user = $this->db->fetch("SELECT * FROM users WHERE google_id = ? AND status != 'banned'", [$googleId]);
        if ($user) {
            $this->setSession($user);
            $this->db->execute("UPDATE users SET last_login = NOW(), email = ? WHERE id = ?", [$email, $user['id']]);
            return ['success' => true];
        }

        // Existing user by email (link Google)
        $user = $this->db->fetch("SELECT * FROM users WHERE email = ? AND status != 'banned'", [$email]);
        if ($user) {
            try {
                $this->db->execute("UPDATE users SET google_id = ?, last_login = NOW() WHERE id = ?", [$googleId, $user['id']]);
            } catch (Throwable $e) {
                // column may not exist yet
            }
            $this->setSession($user);
            return ['success' => true];
        }

        // New user: create (no email verification for Google sign-in)
        $username = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($name));
        if (strlen($username) < 3) $username = 'user' . substr(uniqid(), -6);
        $existing = $this->db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) $username = $username . substr(uniqid(), -4);
        $api_key = bin2hex(random_bytes(20));
        $ref_code = strtolower(substr(md5(uniqid()), 0, 8));
        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $id = $this->db->insert(
            "INSERT INTO users (username, email, password, role, status, api_key, referral_code, google_id) VALUES (?, ?, ?, 'user', 'active', ?, ?, ?)",
            [$username, $email, $passwordHash, $api_key, $ref_code, $googleId]
        );
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if ($user) {
            $this->setSession($user);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Could not create account'];
    }

    private function setSession(array $user): void {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;
    }
}
