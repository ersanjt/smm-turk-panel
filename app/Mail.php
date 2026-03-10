<?php
/**
 * SMM Turk - Mail helper
 * If Admin → Settings has smtp_host set, sends via SMTP; otherwise uses PHP mail() (cPanel).
 */
class Mail {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Get From address: settings smtp_from or config */
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
     * Send email. Uses SMTP when smtp_host is set, else PHP mail().
     * @return true on success, false on failure
     */
    public function send(string $to, string $subject, string $bodyPlain, string $bodyHtml = ''): bool {
        $from = $this->getFrom();
        $siteName = $this->db->getSetting('site_name') ?: (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
        $message = $bodyHtml !== '' ? $bodyHtml : '<pre>' . htmlspecialchars($bodyPlain) . '</pre>';

        $smtpHost = $this->db->getSetting('smtp_host');
        if ($smtpHost !== null && trim($smtpHost) !== '') {
            $ok = $this->sendSmtp(trim($smtpHost), $from, $to, $subject, $message, $siteName);
            if (!$ok) {
                Logger::log("SMTP send failed to {$to}: " . ($this->lastError ?? 'unknown'), 'mail');
            }
            return $ok;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $siteName . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $ok = @mail($to, $subject, $message, implode("\r\n", $headers));
        if (!$ok) {
            Logger::log("mail() send failed to {$to}", 'mail');
        }
        return $ok;
    }

    private ?string $lastError = null;

    /**
     * Send via SMTP (socket). Uses smtp_port, smtp_user, smtp_pass from settings.
     */
    private function sendSmtp(string $host, string $from, string $to, string $subject, string $body, string $siteName): bool {
        $this->lastError = null;
        $port = (int) ($this->db->getSetting('smtp_port') ?: 587);
        $user = $this->db->getSetting('smtp_user');
        $pass = $this->db->getSetting('smtp_pass');
        $useTls = ($port === 587 || $port === 465);
        $ssl = ($port === 465);
        $target = ($ssl ? 'ssl://' : '') . $host . ':' . $port;

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $ssl ? null : stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]])
        );
        if (!$fp) {
            $this->lastError = "Connect: {$errstr} ({$errno})";
            return false;
        }

        $read = [$fp];
        $write = [$fp];
        $except = null;
        $getLine = function () use ($fp, &$read, &$write, &$except): ?string {
            $line = @fgets($fp, 512);
            return $line !== false ? trim($line) : null;
        };
        $send = function (string $cmd) use ($fp): bool {
            return @fwrite($fp, $cmd . "\r\n") !== false;
        };

        $line = $getLine();
        if ($line === null || (int)substr($line, 0, 3) >= 400) {
            $this->lastError = $line ?? 'No greeting';
            fclose($fp);
            return false;
        }

        $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        while ($line = $getLine()) {
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }

        if ($useTls && $port === 587) {
            $send('STARTTLS');
            $line = $getLine();
            if ($line === null || (int)substr($line, 0, 3) >= 400) {
                fclose($fp);
                return false;
            }
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                return false;
            }
            $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            while ($line = $getLine()) {
                if (strlen($line) < 4 || $line[3] === ' ') break;
            }
        }

        if ($user !== null && $user !== '' && $pass !== null) {
            $send('AUTH LOGIN');
            $line = $getLine();
            if ($line === null || (int)substr($line, 0, 3) >= 400) {
                fclose($fp);
                return false;
            }
            $send(base64_encode($user));
            $line = $getLine();
            if ($line === null || (int)substr($line, 0, 3) >= 400) {
                fclose($fp);
                return false;
            }
            $send(base64_encode($pass));
            $line = $getLine();
            if ($line === null || (int)substr($line, 0, 3) >= 400) {
                $this->lastError = 'Auth failed';
                fclose($fp);
                return false;
            }
        }

        $send('MAIL FROM:<' . $from . '>');
        if ((int)substr($getLine() ?? '500', 0, 3) >= 400) { fclose($fp); return false; }
        $send('RCPT TO:<' . $to . '>');
        if ((int)substr($getLine() ?? '500', 0, 3) >= 400) { fclose($fp); return false; }
        $send('DATA');
        if ((int)substr($getLine() ?? '500', 0, 3) >= 400) { fclose($fp); return false; }

        $headers = "From: {$siteName} <{$from}>\r\nTo: {$to}\r\nSubject: {$subject}\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $send($headers . "\r\n" . $body);
        $send('.');
        $line = $getLine();
        fclose($fp);
        return $line !== null && (int)substr($line, 0, 3) <= 299;
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
