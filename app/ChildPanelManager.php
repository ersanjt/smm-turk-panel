<?php
/**
 * Child panel orders, billing, and automated provisioning.
 */
class ChildPanelManager
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    public const PROVISION_DNS_WAIT = 'dns_wait';
    public const PROVISION_QUEUED = 'queued';
    public const PROVISION_PROVISIONING = 'provisioning';
    public const PROVISION_READY = 'ready';
    public const PROVISION_FAILED = 'failed';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        self::ensureSchema($this->db);
    }

    public static function ensureSchema(Database $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $pdo = $db->getConnection();
        $columns = [
            'admin_email'       => 'VARCHAR(255) DEFAULT NULL',
            'panel_url'         => 'VARCHAR(255) DEFAULT NULL',
            'panel_api_key'     => 'VARCHAR(64) DEFAULT NULL',
            'provision_status'  => "VARCHAR(32) NOT NULL DEFAULT 'pending'",
            'provision_error'   => 'VARCHAR(512) DEFAULT NULL',
            'provision_log'     => 'TEXT DEFAULT NULL',
            'activated_at'      => 'DATETIME DEFAULT NULL',
            'expires_at'        => 'DATETIME DEFAULT NULL',
            'ns_verified'       => 'TINYINT(1) NOT NULL DEFAULT 0',
            'document_root'     => 'VARCHAR(512) DEFAULT NULL',
            'admin_password_enc'=> 'TEXT DEFAULT NULL',
            'updated_at'        => 'DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
        ];
        foreach ($columns as $name => $def) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute(['child_panels', $name]);
            if ((int) $stmt->fetchColumn() === 0) {
                try {
                    $pdo->exec("ALTER TABLE child_panels ADD COLUMN `$name` $def");
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') === false) {
                        throw $e;
                    }
                }
            }
        }
        try {
            $pdo->exec('CREATE UNIQUE INDEX uk_child_panels_domain ON child_panels (domain)');
        } catch (PDOException $e) {
            /* index may exist */
        }
        $dir = ROOT_PATH . '/storage/child-panels';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public function monthlyPrice(): float
    {
        return (float) ($this->db->getSetting('child_panel_price') ?: 5);
    }

    public function autoMode(): string
    {
        $mode = strtolower(trim((string) ($this->db->getSetting('child_panel_auto_mode') ?? 'instant')));
        return in_array($mode, ['manual', 'instant', 'dns', 'whm', 'full'], true) ? $mode : 'instant';
    }

    public function serverIp(): string
    {
        return trim((string) ($this->db->getSetting('child_panel_server_ip') ?? ''));
    }

    public function provisioningEnabled(): bool
    {
        $whm = new WhmProvisioner();
        if ($whm->isConfigured()) {
            return true;
        }
        return (new ChildPanelDeployer())->homePath() !== '';
    }

    public static function encryptSecret(string $plain): string
    {
        $key = defined('SECRET_KEY') ? (string) SECRET_KEY : 'smm-child-panel';
        $iv = substr(hash('sha256', $key . 'iv'), 0, 16);
        $enc = openssl_encrypt($plain, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
        return $enc === false ? '' : base64_encode($enc);
    }

    public static function decryptSecret(string $enc): string
    {
        if ($enc === '') {
            return '';
        }
        $key = defined('SECRET_KEY') ? (string) SECRET_KEY : 'smm-child-panel';
        $iv = substr(hash('sha256', $key . 'iv'), 0, 16);
        $raw = base64_decode($enc, true);
        if ($raw === false) {
            return '';
        }
        $plain = openssl_decrypt($raw, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    /** @return array{ready: bool, method: string} */
    public function checkDomainReady(string $domain): array
    {
        $domain = self::normalizeDomain($domain);
        $dnsMode = strtolower(trim((string) ($this->db->getSetting('child_panel_dns_mode') ?? 'both')));
        $serverIp = $this->serverIp();

        $nsOk = $this->checkNameservers($domain);
        $aOk = false;
        $records = @dns_get_record($domain, DNS_A);
        if (is_array($records)) {
            foreach ($records as $row) {
                $ip = (string) ($row['ip'] ?? '');
                if ($ip === '') {
                    continue;
                }
                if ($serverIp === '' || $ip === $serverIp) {
                    $aOk = true;
                    break;
                }
            }
            if (!$aOk && $records !== [] && $dnsMode !== 'ns') {
                $aOk = true;
            }
        }
        $resolves = @gethostbyname($domain);
        $resolveOk = is_string($resolves) && $resolves !== $domain;

        if ($dnsMode === 'ns') {
            return ['ready' => $nsOk, 'method' => $nsOk ? 'ns' : ''];
        }
        if ($dnsMode === 'a') {
            $ready = $aOk || $resolveOk;
            return ['ready' => $ready, 'method' => $aOk ? 'a' : ($resolveOk ? 'resolve' : '')];
        }
        $ready = $nsOk || $aOk || $resolveOk;
        $method = $nsOk ? 'ns' : ($aOk ? 'a' : ($resolveOk ? 'resolve' : ''));
        return ['ready' => $ready, 'method' => $method];
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = preg_replace('#/.*$#', '', $domain) ?? $domain;
        return rtrim($domain, '.');
    }

    public static function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain);
    }

    /** @return array{success: bool, panel_id?: int, error?: string, instant?: bool} */
    public function placeOrder(
        int $userId,
        string $domain,
        string $currency,
        string $adminUsername,
        string $adminPassword,
        ?string $adminEmail = null
    ): array {
        $domain = self::normalizeDomain($domain);
        if (!self::isValidDomain($domain)) {
            return ['success' => false, 'error' => 'Enter a valid domain (e.g. yourpanel.com).'];
        }

        $existing = $this->db->fetch(
            "SELECT id FROM child_panels WHERE domain = ? AND status NOT IN ('cancelled')",
            [$domain]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'This domain is already registered on a child panel order.'];
        }

        $price = $this->monthlyPrice();
        $user = $this->db->fetch('SELECT id, username, email, balance, api_key FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }
        if ((float) $user['balance'] < $price) {
            return ['success' => false, 'error' => 'Insufficient balance. You need $' . number_format($price, 2) . '. Add funds first.'];
        }

        $adminEmail = trim((string) ($adminEmail ?: $user['email'] ?? ''));
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'A valid admin email is required.'];
        }

        $mode = $this->autoMode();
        $initialStatus = self::STATUS_PENDING;
        $provisionStatus = match ($mode) {
            'manual' => 'pending',
            'dns' => self::PROVISION_DNS_WAIT,
            default => self::PROVISION_QUEUED,
        };

        $passwordEnc = self::encryptSecret($adminPassword);

        try {
            $this->db->beginTransaction();
            $deducted = $this->db->execute(
                'UPDATE users SET balance = balance - ?, spent = spent + ? WHERE id = ? AND balance >= ?',
                [$price, $price, $userId, $price]
            );
            if ($deducted === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Insufficient balance.'];
            }

            $panelId = $this->db->insert(
                'INSERT INTO child_panels (user_id, domain, currency, admin_username, admin_password, admin_password_enc, admin_email, price, status, provision_status, panel_api_key)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $domain,
                    $currency,
                    $adminUsername,
                    password_hash($adminPassword, PASSWORD_DEFAULT),
                    $passwordEnc,
                    $adminEmail,
                    $price,
                    $initialStatus,
                    $provisionStatus,
                    $user['api_key'] ?? bin2hex(random_bytes(20)),
                ]
            );

            $balanceAfter = (float) $user['balance'] - $price;
            $this->db->insert(
                "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference)
                 VALUES (?, 'child_panel', ?, ?, ?, ?, ?)",
                [$userId, -$price, (float) $user['balance'], $balanceAfter, 'Child panel: ' . $domain, (string) $panelId]
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            Logger::log('Child panel order failed: ' . $e->getMessage(), 'child_panel');
            if (stripos($e->getMessage(), "doesn't exist") !== false) {
                return ['success' => false, 'error' => 'Child panel tables missing. Run php migrate-db.php on the server.'];
            }
            return ['success' => false, 'error' => 'Order failed. Please try again or contact support.'];
        }

        $instant = false;
        if ($mode !== 'manual') {
            $dns = $this->checkDomainReady($domain);
            if (!$dns['ready']) {
                $this->db->execute(
                    'UPDATE child_panels SET provision_status = ? WHERE id = ?',
                    [self::PROVISION_DNS_WAIT, $panelId]
                );
            } else {
                $provision = $this->provision((int) $panelId, $adminPassword);
                $instant = $provision['success'] ?? false;
            }
        }

        return ['success' => true, 'panel_id' => (int) $panelId, 'instant' => $instant];
    }

    /** @return array{success: bool, error?: string, pending_dns?: bool} */
    public function provision(int $panelId, ?string $adminPlainPassword = null): array
    {
        $panel = $this->db->fetch(
            'SELECT cp.*, u.username, u.email FROM child_panels cp JOIN users u ON u.id = cp.user_id WHERE cp.id = ?',
            [$panelId]
        );
        if (!$panel) {
            return ['success' => false, 'error' => 'Panel not found.'];
        }
        if (($panel['status'] ?? '') === self::STATUS_CANCELLED) {
            return ['success' => false, 'error' => 'Panel order cancelled.'];
        }

        $this->appendLog($panelId, 'Provisioning started.');
        $this->db->execute(
            "UPDATE child_panels SET provision_status = ?, provision_error = NULL WHERE id = ?",
            [self::PROVISION_PROVISIONING, $panelId]
        );

        $domain = self::normalizeDomain((string) $panel['domain']);
        $dns = $this->checkDomainReady($domain);
        if (!$dns['ready']) {
            $this->appendLog($panelId, 'Waiting for DNS (' . ($dns['method'] ?: 'not detected') . ').');
            $this->db->execute(
                'UPDATE child_panels SET provision_status = ?, provision_error = NULL WHERE id = ?',
                [self::PROVISION_DNS_WAIT, $panelId]
            );
            return ['success' => false, 'pending_dns' => true, 'error' => 'Domain DNS not ready yet. Point nameservers or A record, then retry.'];
        }
        $this->appendLog($panelId, 'DNS verified via ' . ($dns['method'] ?: 'check') . '.');

        $panelUrl = 'https://' . $domain;
        $documentRoot = trim((string) ($panel['document_root'] ?? ''));
        $deployError = null;

        if ($adminPlainPassword === null || $adminPlainPassword === '') {
            $adminPlainPassword = self::decryptSecret((string) ($panel['admin_password_enc'] ?? ''));
        }

        if ($this->provisioningEnabled()) {
            $whm = new WhmProvisioner();
            if ($whm->isConfigured()) {
                $whmResult = $whm->provisionDomain($domain);
                if (!$whmResult['success']) {
                    $deployError = $whmResult['error'] ?? 'WHM addon domain failed';
                    $this->appendLog($panelId, 'WHM error: ' . $deployError);
                } else {
                    $this->appendLog($panelId, $whmResult['message'] ?? 'Addon domain OK');
                    $documentRoot = trim((string) ($whmResult['document_root'] ?? $documentRoot));
                    if (!empty($whmResult['panel_url'])) {
                        $panelUrl = $whmResult['panel_url'];
                    }
                    try {
                        $whm->requestAutoSsl($domain);
                    } catch (Throwable $e) {
                        /* best effort */
                    }
                }
            } else {
                $deployer = new ChildPanelDeployer();
                $documentRoot = $documentRoot !== '' ? $documentRoot : $deployer->docrootForDomain($domain);
                $this->appendLog($panelId, 'Local deploy path: ' . $documentRoot);
            }

            if ($deployError === null && $documentRoot !== '' && $adminPlainPassword !== '') {
                $deployer = new ChildPanelDeployer();
                $deploy = $deployer->deploy($panel, $documentRoot, $adminPlainPassword);
                if (!$deploy['success']) {
                    $deployError = $deploy['error'] ?? 'Deploy failed';
                    $this->appendLog($panelId, 'Deploy error: ' . $deployError);
                } else {
                    $documentRoot = $deploy['document_root'] ?? $documentRoot;
                    $this->appendLog($panelId, 'Files deployed to ' . $documentRoot);
                }
            } elseif ($deployError === null && $adminPlainPassword === '') {
                $deployError = 'Admin password unavailable for provisioning retry.';
            }
        } else {
            $this->appendLog($panelId, 'Auto-deploy disabled — metadata only.');
        }

        $apiKey = trim((string) ($panel['panel_api_key'] ?? ''));
        if ($apiKey === '') {
            $userRow = $this->db->fetch('SELECT api_key FROM users WHERE id = ?', [$panel['user_id']]);
            $apiKey = $userRow['api_key'] ?? bin2hex(random_bytes(20));
            $this->db->execute('UPDATE child_panels SET panel_api_key = ? WHERE id = ?', [$apiKey, $panelId]);
        }

        $parentApi = trim((string) ($this->db->getSetting('child_panel_parent_api_url') ?? ''));
        if ($parentApi === '') {
            $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
            $parentApi = $siteUrl !== '' ? $siteUrl . '/api/v2' : '/api/v2';
        }

        if ($deployError !== null && $this->provisioningEnabled()) {
            $this->db->execute(
                'UPDATE child_panels SET provision_status = ?, provision_error = ?, updated_at = NOW() WHERE id = ?',
                [self::PROVISION_FAILED, substr($deployError, 0, 500), $panelId]
            );
            return ['success' => false, 'error' => $deployError];
        }

        $this->writeConfigFile($panelId, [
            'domain' => $domain,
            'panel_url' => $panelUrl,
            'document_root' => $documentRoot,
            'parent_api_url' => $parentApi,
            'api_key' => $apiKey,
            'admin_username' => $panel['admin_username'],
            'admin_email' => $panel['admin_email'] ?? $panel['email'],
            'currency' => $panel['currency'],
            'provisioned_at' => date('c'),
        ]);

        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $this->db->execute(
            "UPDATE child_panels SET status = ?, provision_status = ?, panel_url = ?, document_root = ?,
             activated_at = NOW(), expires_at = ?, provision_error = NULL, ns_verified = 1, updated_at = NOW() WHERE id = ?",
            [
                self::STATUS_ACTIVE,
                self::PROVISION_READY,
                $panelUrl,
                $documentRoot !== '' ? $documentRoot : null,
                $expiresAt,
                $panelId,
            ]
        );
        $this->appendLog($panelId, 'Panel live at ' . $panelUrl);

        $to = $panel['admin_email'] ?? $panel['email'] ?? '';
        if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            try {
                $mail = new Mail();
                $mail->sendChildPanelReady(
                    $to,
                    (string) $panel['username'],
                    $domain,
                    $panelUrl,
                    $parentApi,
                    $apiKey,
                    (string) $panel['admin_username']
                );
            } catch (Throwable $e) {
                Logger::log('Child panel email failed #' . $panelId . ': ' . $e->getMessage(), 'mail');
            }
        }

        return ['success' => true];
    }

    public function checkNameservers(string $domain): bool
    {
        $ns1 = strtolower(rtrim(trim((string) ($this->db->getSetting('child_panel_ns1') ?? '')), '.'));
        $ns2 = strtolower(rtrim(trim((string) ($this->db->getSetting('child_panel_ns2') ?? '')), '.'));
        if ($ns1 === '' && $ns2 === '') {
            return false;
        }
        $records = @dns_get_record($domain, DNS_NS);
        if (!is_array($records) || $records === []) {
            return false;
        }
        $found = [];
        foreach ($records as $row) {
            $target = strtolower(rtrim((string) ($row['target'] ?? ''), '.'));
            if ($target !== '') {
                $found[] = $target;
            }
        }
        $required = array_filter([$ns1, $ns2]);
        foreach ($required as $need) {
            if (!in_array($need, $found, true)) {
                return false;
            }
        }
        return true;
    }

    /** Process DNS-waiting panels. Returns number provisioned. */
    public function processDnsQueue(): int
    {
        $rows = $this->db->fetchAll(
            "SELECT id, domain FROM child_panels WHERE provision_status = ? AND status NOT IN ('cancelled', 'active')",
            [self::PROVISION_DNS_WAIT]
        );
        $done = 0;
        foreach ($rows as $row) {
            $domain = self::normalizeDomain((string) $row['domain']);
            $dns = $this->checkDomainReady($domain);
            if (!$dns['ready']) {
                continue;
            }
            $this->db->execute('UPDATE child_panels SET ns_verified = 1 WHERE id = ?', [$row['id']]);
            $result = $this->provision((int) $row['id']);
            if ($result['success']) {
                $done++;
            }
        }
        return $done;
    }

    /** Retry queued/stuck panels (cron). Returns number provisioned. */
    public function retryPendingQueue(): int
    {
        $rows = $this->db->fetchAll(
            "SELECT id FROM child_panels
             WHERE status NOT IN ('cancelled', 'active')
             AND provision_status IN (?, ?, ?)
             AND created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            [self::PROVISION_QUEUED, self::PROVISION_PROVISIONING, self::PROVISION_FAILED]
        );
        $done = 0;
        foreach ($rows as $row) {
            $result = $this->provision((int) $row['id']);
            if ($result['success']) {
                $done++;
            }
        }
        return $done;
    }

    /** @return array{dns: int, retried: int} */
    public function processCron(): array
    {
        return [
            'dns' => $this->processDnsQueue(),
            'retried' => $this->retryPendingQueue(),
        ];
    }

    private function appendLog(int $panelId, string $line): void
    {
        $row = $this->db->fetch('SELECT provision_log FROM child_panels WHERE id = ?', [$panelId]);
        $log = trim((string) ($row['provision_log'] ?? ''));
        $log .= ($log !== '' ? "\n" : '') . '[' . date('Y-m-d H:i:s') . '] ' . $line;
        $this->db->execute('UPDATE child_panels SET provision_log = ? WHERE id = ?', [$log, $panelId]);
    }

    /** @param array<string, mixed> $config */
    private function writeConfigFile(int $panelId, array $config): void
    {
        $dir = ROOT_PATH . '/storage/child-panels/' . $panelId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function getConfigForPanel(int $panelId, int $userId): ?array
    {
        $panel = $this->db->fetch('SELECT * FROM child_panels WHERE id = ? AND user_id = ?', [$panelId, $userId]);
        if (!$panel) {
            return null;
        }
        $parentApi = trim((string) ($this->db->getSetting('child_panel_parent_api_url') ?? ''));
        if ($parentApi === '') {
            $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
            $parentApi = $siteUrl !== '' ? $siteUrl . '/api/v2' : '/api/v2';
        }
        return [
            'panel' => $panel,
            'parent_api_url' => $parentApi,
            'ns1' => trim((string) ($this->db->getSetting('child_panel_ns1') ?? '')),
            'ns2' => trim((string) ($this->db->getSetting('child_panel_ns2') ?? '')),
        ];
    }

    /** @return list<string> */
    public static function provisionSteps(array $panel): array
    {
        $status = $panel['status'] ?? 'pending';
        $ps = $panel['provision_status'] ?? 'pending';
        if ($status === self::STATUS_ACTIVE && $ps === self::PROVISION_READY) {
            return ['paid', 'dns_ok', 'deployed', 'active'];
        }
        if ($ps === self::PROVISION_DNS_WAIT) {
            return ['paid', 'dns_wait'];
        }
        if ($ps === self::PROVISION_PROVISIONING || $ps === self::PROVISION_QUEUED) {
            return ['paid', 'provisioning'];
        }
        if ($ps === self::PROVISION_FAILED) {
            return ['paid', 'failed'];
        }
        return ['paid', 'pending'];
    }
}
