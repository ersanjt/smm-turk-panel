<?php
/**
 * SMM Turk — Mail (cPanel mail() + SMTP) · TR / EN templates
 */
class Mail
{
    private Database $db;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function getFrom(): string
    {
        $from = trim((string) ($this->db->getSetting('smtp_from') ?? ''));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }
        if (defined('MAIL_FROM') && MAIL_FROM !== '' && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)) {
            return MAIL_FROM;
        }
        $host = parse_url(defined('SITE_URL') ? SITE_URL : 'https://localhost', PHP_URL_HOST);
        return 'noreply@' . ($host ?: 'localhost');
    }

    private function getReplyTo(): ?string
    {
        $contact = trim((string) ($this->db->getSetting('contact_email') ?? ''));
        return filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
    }

    private function getSiteName(): string
    {
        return $this->db->getSetting('site_name') ?: (defined('SITE_NAME') ? SITE_NAME : 'SMM Turk');
    }

    private function siteUrl(): string
    {
        return defined('SITE_URL') && SITE_URL !== '' ? rtrim(SITE_URL, '/') : '';
    }

    private function btn(string $href, string $label): string
    {
        return '<p style="margin:20px 0;">'
            . '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:inline-block;background:#E30A17;color:#fff;padding:12px 28px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:15px;">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }

    private function copyLink(string $href, ?string $lang): string
    {
        return '<p style="font-size:12px;color:#666;margin-top:8px;">'
            . htmlspecialchars(MailLocale::t('copy_link', $lang), ENT_QUOTES, 'UTF-8')
            . '<br><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" style="color:#E30A17;word-break:break-all;">'
            . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }

    /** Branded HTML wrapper */
    public function wrapHtml(string $title, string $innerHtml, ?string $lang = null): string
    {
        $lang = MailLocale::resolveLang($lang);
        $siteName = htmlspecialchars($this->getSiteName(), ENT_QUOTES, 'UTF-8');
        $home = htmlspecialchars($this->siteUrl() ?: page_url('index.php'), ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $footer = htmlspecialchars(MailLocale::t('footer_auto', $lang), ENT_QUOTES, 'UTF-8');
        $enNote = MailLocale::t('footer_en_note', $lang);
        $enLine = $enNote !== '' ? '<br><span style="color:#aaa;">' . htmlspecialchars($enNote, ENT_QUOTES, 'UTF-8') . '</span>' : '';

        return '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:28px 12px;"><tr><td align="center">'
            . '<table width="100%" style="max-width:560px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.06);">'
            . '<tr><td style="background:#E30A17;padding:20px 24px;">'
            . '<a href="' . $home . '" style="color:#fff;font-size:20px;font-weight:bold;text-decoration:none;">' . $siteName . '</a></td></tr>'
            . '<tr><td style="padding:28px 24px;color:#1f2937;font-size:15px;line-height:1.65;">' . $innerHtml . '</td></tr>'
            . '<tr><td style="padding:16px 24px;background:#f9fafb;color:#9ca3af;font-size:11px;border-top:1px solid #e5e7eb;line-height:1.5;">'
            . '© ' . $year . ' ' . $siteName . '. ' . $footer . $enLine
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private function encodeHeader(string $text): string
    {
        return preg_match('/[^\x20-\x7E]/', $text)
            ? '=?UTF-8?B?' . base64_encode($text) . '?='
            : $text;
    }

    /**
     * @return true on success
     */
    public function send(string $to, string $subject, string $bodyPlain, string $bodyHtml = '', ?string $lang = null): bool
    {
        $this->lastError = null;
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Invalid recipient email';
            return false;
        }

        $from = $this->getFrom();
        $siteName = $this->getSiteName();
        $html = $bodyHtml !== '' ? $bodyHtml : '<pre style="font-family:monospace;white-space:pre-wrap;">' . htmlspecialchars($bodyPlain) . '</pre>';
        if (stripos($html, '<html') === false) {
            $html = $this->wrapHtml($subject, $html, $lang);
        }

        $mode = strtolower(trim((string) ($this->db->getSetting('mail_mode') ?? 'auto')));
        $smtpHost = trim((string) ($this->db->getSetting('smtp_host') ?? ''));

        if ($mode === 'mail' || ($mode === 'auto' && $smtpHost === '')) {
            return $this->sendPhpMail($from, $to, $subject, $html, $siteName);
        }

        if ($smtpHost !== '') {
            $ok = $this->sendSmtp($smtpHost, $from, $to, $subject, $html, $siteName);
            if ($ok) {
                return true;
            }
            if ($mode === 'smtp') {
                Logger::log("SMTP only mode failed to {$to}: " . ($this->lastError ?? 'unknown'), 'mail');
                return false;
            }
            Logger::log("SMTP failed ({$this->lastError}), falling back to mail() for {$to}", 'mail');
        }

        return $this->sendPhpMail($from, $to, $subject, $html, $siteName);
    }

    private function sendPhpMail(string $from, string $to, string $subject, string $html, string $siteName): bool
    {
        $encodedSubject = $this->encodeHeader($subject);
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->encodeHeader($siteName) . ' <' . $from . '>',
            'Reply-To: ' . ($this->getReplyTo() ?? $from),
            'X-Mailer: SMM-Turk-Mail/3.0',
        ];

        $params = '-f' . $from;
        $ok = @mail($to, $encodedSubject, $html, implode("\r\n", $headers), $params);
        if (!$ok) {
            $this->lastError = 'PHP mail() failed. Configure SMTP in Admin → Settings.';
            Logger::log("mail() failed to {$to} from {$from}", 'mail');
        }
        return $ok;
    }

    /** @return string[] */
    private function readSmtpResponse($fp): array
    {
        $lines = [];
        while (!feof($fp)) {
            $line = fgets($fp, 8192);
            if ($line === false) {
                break;
            }
            $lines[] = rtrim($line, "\r\n");
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $lines;
    }

    private function smtpCode(array $lines): int
    {
        return $lines === [] ? 500 : (int) substr($lines[0], 0, 3);
    }

    private function smtpCmd($fp, string $cmd, array $expectCodes = [250]): bool
    {
        if (@fwrite($fp, $cmd . "\r\n") === false) {
            $this->lastError = 'Write failed: ' . $cmd;
            return false;
        }
        $lines = $this->readSmtpResponse($fp);
        $code = $this->smtpCode($lines);
        if (!in_array($code, $expectCodes, true) && !($code >= 200 && $code < 300)) {
            $this->lastError = 'SMTP ' . $cmd . ' → ' . implode(' | ', $lines);
            return false;
        }
        return true;
    }

    private function sendSmtp(string $host, string $from, string $to, string $subject, string $body, string $siteName): bool
    {
        $this->lastError = null;
        $port = (int) ($this->db->getSetting('smtp_port') ?: 587);
        $user = trim((string) ($this->db->getSetting('smtp_user') ?? ''));
        $pass = (string) ($this->db->getSetting('smtp_pass') ?? '');
        $enc = strtolower(trim((string) ($this->db->getSetting('smtp_encryption') ?? 'auto')));

        if ($enc === 'auto') {
            $enc = $port === 465 ? 'ssl' : ($port === 587 ? 'tls' : 'none');
        }

        $target = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        $fp = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            $this->lastError = "Cannot connect to {$target}: {$errstr} ({$errno})";
            return false;
        }
        stream_set_timeout($fp, 20);

        $greeting = $this->readSmtpResponse($fp);
        if ($this->smtpCode($greeting) !== 220) {
            $this->lastError = 'Bad greeting: ' . implode(' | ', $greeting);
            fclose($fp);
            return false;
        }

        $ehloHost = parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_HOST) ?: 'localhost';
        if (!$this->smtpCmd($fp, 'EHLO ' . $ehloHost, [250])) {
            fclose($fp);
            return false;
        }

        if ($enc === 'tls') {
            if (!$this->smtpCmd($fp, 'STARTTLS', [220])) {
                fclose($fp);
                return false;
            }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = 'STARTTLS handshake failed';
                fclose($fp);
                return false;
            }
            if (!$this->smtpCmd($fp, 'EHLO ' . $ehloHost, [250])) {
                fclose($fp);
                return false;
            }
        }

        if ($user !== '') {
            if (!$this->smtpCmd($fp, 'AUTH LOGIN', [334]) || !$this->smtpCmd($fp, base64_encode($user), [334]) || !$this->smtpCmd($fp, base64_encode($pass), [235])) {
                $this->lastError = 'SMTP authentication failed';
                fclose($fp);
                return false;
            }
        }

        if (!$this->smtpCmd($fp, 'MAIL FROM:<' . $from . '>', [250]) || !$this->smtpCmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]) || !$this->smtpCmd($fp, 'DATA', [354])) {
            fclose($fp);
            return false;
        }

        $replyTo = $this->getReplyTo() ?? $from;
        $msg = 'From: ' . $this->encodeHeader($siteName) . ' <' . $from . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Reply-To: <' . $replyTo . ">\r\n"
            . 'Subject: ' . $this->encodeHeader($subject) . "\r\nMIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $this->smtpDotStuff($body) . "\r\n";

        if (@fwrite($fp, $msg) === false || !$this->smtpCmd($fp, '.', [250])) {
            fclose($fp);
            return false;
        }
        $this->smtpCmd($fp, 'QUIT', [221]);
        fclose($fp);
        return true;
    }

    private function smtpDotStuff(string $body): string
    {
        return preg_replace('/^\./m', '..', $body) ?? $body;
    }

    private function subjectPrefix(string $text, ?string $lang): string
    {
        return '[' . $this->getSiteName() . '] ' . $text;
    }

    public function sendTest(string $to, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $subject = $this->subjectPrefix(MailLocale::t('test_subject', $lang) . ' — ' . date('Y-m-d H:i'), $lang);
        $inner = '<p>' . MailLocale::t('test_body', $lang) . '</p>'
            . '<p><strong>' . MailLocale::t('test_time', $lang) . ':</strong> ' . htmlspecialchars(date('c')) . '</p>'
            . '<p><strong>' . MailLocale::t('test_from', $lang) . ':</strong> ' . htmlspecialchars($this->getFrom()) . '</p>';
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml(MailLocale::t('test_subject', $lang), $inner, $lang), $lang);
    }

    public function sendVerification(string $to, string $username, string $token, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $link = page_url('verify-email.php', ['token' => $token]);
        $subject = $this->subjectPrefix(MailLocale::t('verify_subject', $lang), $lang);
        $inner = '<p>' . MailLocale::t('verify_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('verify_body', $lang) . '</p>'
            . $this->btn($link, MailLocale::t('btn_verify', $lang))
            . $this->copyLink($link, $lang)
            . '<p style="font-size:13px;color:#666;">' . MailLocale::t('verify_expires', $lang) . '</p>';
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml(MailLocale::t('verify_subject', $lang), $inner, $lang), $lang);
    }

    public function sendPasswordReset(string $to, string $username, string $token, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $link = page_url('reset-password.php', ['token' => $token]);
        $subject = $this->subjectPrefix(MailLocale::t('reset_subject', $lang), $lang);
        $inner = '<p>' . MailLocale::t('reset_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('reset_body', $lang) . '</p>'
            . $this->btn($link, MailLocale::t('btn_reset', $lang))
            . $this->copyLink($link, $lang)
            . '<p style="font-size:13px;color:#666;">' . MailLocale::t('reset_expires', $lang) . '<br>' . MailLocale::t('reset_ignore', $lang) . '</p>';
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml(MailLocale::t('reset_subject', $lang), $inner, $lang), $lang);
    }

    public function sendDepositConfirmed(string $to, string $username, float $amount, float $balanceAfter, ?int $transactionId = null, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $amountFmt = number_format($amount, 2);
        $balanceFmt = number_format($balanceAfter, 2);
        $ref = $transactionId ? ' (#' . $transactionId . ')' : '';
        $subject = $this->subjectPrefix(MailLocale::t('deposit_subject', $lang, ['amount' => $amountFmt]), $lang);
        $inner = '<p>' . MailLocale::t('deposit_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('deposit_body', $lang, ['amount' => $amountFmt, 'ref' => $ref]) . '</p>'
            . '<p><strong>' . MailLocale::t('deposit_balance', $lang) . '</strong> $' . htmlspecialchars($balanceFmt) . '</p>'
            . $this->btn(page_url('index.php'), MailLocale::t('btn_deposit', $lang));
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml(MailLocale::t('deposit_subject', $lang, ['amount' => $amountFmt]), $inner, $lang), $lang);
    }

    public function sendOrderPlaced(string $to, string $username, int $orderId, string $serviceName, int $quantity, float $charge, string $link, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $chargeFmt = number_format($charge, 4);
        $subject = $this->subjectPrefix(MailLocale::t('order_placed_subject', $lang, ['id' => $orderId]), $lang);
        $inner = '<p>' . MailLocale::t('order_placed_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('order_placed_body', $lang) . '</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">'
            . '<tr><td style="padding:8px;border-bottom:1px solid #eee;color:#666;">#' . (int) $orderId . '</td><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">' . htmlspecialchars($serviceName) . '</td></tr>'
            . '<tr><td style="padding:8px;border-bottom:1px solid #eee;color:#666;">' . MailLocale::t('order_qty', $lang) . '</td><td style="padding:8px;border-bottom:1px solid #eee;">' . number_format($quantity) . '</td></tr>'
            . '<tr><td style="padding:8px;border-bottom:1px solid #eee;color:#666;">' . MailLocale::t('order_charge', $lang) . '</td><td style="padding:8px;border-bottom:1px solid #eee;">$' . htmlspecialchars($chargeFmt) . '</td></tr>'
            . '<tr><td style="padding:8px;color:#666;">' . MailLocale::t('order_link_label', $lang) . '</td><td style="padding:8px;word-break:break-all;font-size:12px;">' . htmlspecialchars($link) . '</td></tr>'
            . '</table>'
            . $this->btn(page_url('orders.php'), MailLocale::t('btn_orders', $lang));
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml('Order #' . $orderId, $inner, $lang), $lang);
    }

    public function sendOrderStatusUpdate(string $to, string $username, int $orderId, string $serviceName, string $status, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $statusLabel = MailLocale::orderStatusLabel($status, $lang);
        $subject = $this->subjectPrefix(MailLocale::t('order_status_subject', $lang, ['id' => $orderId, 'status' => $statusLabel]), $lang);
        $inner = '<p>' . MailLocale::t('order_status_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('order_status_body', $lang) . '</p>'
            . '<p><strong>#' . (int) $orderId . '</strong> — ' . htmlspecialchars($serviceName) . '</p>'
            . '<p><strong>' . MailLocale::t('order_status_label', $lang) . ':</strong> ' . htmlspecialchars($statusLabel) . '</p>'
            . $this->btn(page_url('orders.php'), MailLocale::t('btn_orders', $lang));
        return $this->send($to, $subject, strip_tags($inner), $this->wrapHtml('Order #' . $orderId, $inner, $lang), $lang);
    }

    public function sendTicketStaffReply(string $to, string $username, int $ticketId, string $subject, string $message, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang);
        $link = page_url('ticket.php', ['id' => $ticketId]);
        $emailSubject = $this->subjectPrefix(MailLocale::t('ticket_reply_subject', $lang, ['id' => $ticketId]), $lang);
        $inner = '<p>' . MailLocale::t('ticket_reply_hi', $lang, ['name' => $username]) . '</p>'
            . '<p>' . MailLocale::t('ticket_reply_body', $lang, ['subject' => $subject]) . '</p>'
            . '<blockquote style="margin:12px 0;padding:12px 16px;background:#f5f5f5;border-left:4px solid #E30A17;">'
            . nl2br(htmlspecialchars($message)) . '</blockquote>'
            . $this->btn($link, MailLocale::t('btn_ticket', $lang));
        return $this->send($to, $emailSubject, strip_tags($inner), $this->wrapHtml('Ticket #' . $ticketId, $inner, $lang), $lang);
    }

    public function sendTicketNewToAdmin(string $to, string $username, string $userEmail, int $ticketId, string $subject, string $message, ?string $lang = null): bool
    {
        $lang = MailLocale::resolveLang($lang ?? 'en');
        $link = page_url('admin/admin-ticket.php', ['id' => $ticketId]);
        $emailSubject = $this->subjectPrefix(MailLocale::t('ticket_new_subject', $lang, ['id' => $ticketId]) . ' — ' . $subject, $lang);
        $inner = '<p>' . MailLocale::t('ticket_new_body', $lang, ['user' => $username, 'email' => $userEmail]) . '</p>'
            . '<p><strong>' . MailLocale::t('ticket_subject_label', $lang) . ':</strong> ' . htmlspecialchars($subject) . '</p>'
            . '<blockquote style="margin:12px 0;padding:12px 16px;background:#f5f5f5;border-left:4px solid #E30A17;">'
            . nl2br(htmlspecialchars($message)) . '</blockquote>'
            . $this->btn($link, MailLocale::t('btn_admin', $lang));
        return $this->send($to, $emailSubject, strip_tags($inner), $this->wrapHtml('Ticket #' . $ticketId, $inner, $lang), $lang);
    }

    public function getDiagnostics(): array
    {
        $host = parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_HOST) ?: 'yourdomain.com';
        return [
            'from' => $this->getFrom(),
            'reply_to' => $this->getReplyTo(),
            'mail_mode' => $this->db->getSetting('mail_mode') ?: 'auto',
            'mail_lang' => $this->db->getSetting('mail_lang') ?: 'tr',
            'smtp_host' => $this->db->getSetting('smtp_host') ?: '(empty)',
            'smtp_port' => $this->db->getSetting('smtp_port') ?: '587',
            'smtp_user' => $this->db->getSetting('smtp_user') ?: '(empty)',
            'smtp_encryption' => $this->db->getSetting('smtp_encryption') ?: 'auto',
            'smtp_configured' => trim((string) ($this->db->getSetting('smtp_host') ?? '')) !== '',
            'cpanel_hint_host' => 'mail.' . $host,
            'last_error' => $this->lastError,
        ];
    }
}
