<?php
/**
 * SMM Turk - Simple mail helper (cPanel / PHP mail compatible)
 * Uses PHP mail() so cPanel can deliver via local mail or forward.
 */
class Mail {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Get From address: settings smtp_from or config SITE_URL domain */
    private function getFrom(): string {
        $from = $this->db->getSetting('smtp_from');
        if ($from !== null && trim($from) !== '') {
            return trim($from);
        }
        if (defined('MAIL_FROM') && MAIL_FROM !== '') {
            return MAIL_FROM;
        }
        $host = parse_url(defined('SITE_URL') ? SITE_URL : 'https://localhost', PHP_URL_HOST);
        return 'noreply@' . ($host ?: 'localhost');
    }

    /**
     * Send email (PHP mail - works with cPanel default mail)
     * @return true on success, false on failure
     */
    public function send(string $to, string $subject, string $bodyPlain, string $bodyHtml = ''): bool {
        $from = $this->getFrom();
        $siteName = $this->db->getSetting('site_name') ?: (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $siteName . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $message = $bodyHtml !== '' ? $bodyHtml : '<pre>' . htmlspecialchars($bodyPlain) . '</pre>';
        return @mail($to, $subject, $message, implode("\r\n", $headers));
    }

    /** Send verification email with link */
    public function sendVerification(string $to, string $username, string $token): bool {
        $siteUrl = rtrim($this->db->getSetting('site_url') ?: (defined('SITE_URL') ? SITE_URL : ''), '/');
        $siteName = $this->db->getSetting('site_name') ?: (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
        $link = $siteUrl . '/verify-email.php?token=' . urlencode($token);
        $subject = '[' . $siteName . '] Verify your email';
        $body = '<p>Hi ' . htmlspecialchars($username) . ',</p>';
        $body .= '<p>Please verify your email by clicking the link below:</p>';
        $body .= '<p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#1a1aff;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;">Verify Email</a></p>';
        $body .= '<p>Or copy this URL: ' . htmlspecialchars($link) . '</p>';
        $body .= '<p>This link expires in 24 hours. If you did not register, ignore this email.</p>';
        $body .= '<p>— ' . htmlspecialchars($siteName) . '</p>';
        return $this->send($to, $subject, strip_tags($body), $body);
    }

    /** Send password reset email with link */
    public function sendPasswordReset(string $to, string $username, string $token): bool {
        $siteUrl = rtrim($this->db->getSetting('site_url') ?: (defined('SITE_URL') ? SITE_URL : ''), '/');
        $siteName = $this->db->getSetting('site_name') ?: (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
        $link = $siteUrl . '/reset-password.php?token=' . urlencode($token);
        $subject = '[' . $siteName . '] Reset your password';
        $body = '<p>Hi ' . htmlspecialchars($username) . ',</p>';
        $body .= '<p>You requested a password reset. Click the link below to set a new password:</p>';
        $body .= '<p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#E30A17;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;">Reset Password</a></p>';
        $body .= '<p>Or copy this URL: ' . htmlspecialchars($link) . '</p>';
        $body .= '<p>This link expires in 1 hour. If you did not request this, ignore this email.</p>';
        $body .= '<p>— ' . htmlspecialchars($siteName) . '</p>';
        return $this->send($to, $subject, strip_tags($body), $body);
    }
}
