<?php
/**
 * Remote child panel settings from parent site (child-panel.php).
 */
class ChildPanelRemoteSettings
{
    /** @var list<string> */
    public const BRANDING_KEYS = ['site_name', 'site_url', 'site_logo', 'site_favicon'];

    /** @var list<string> */
    public const GENERAL_KEYS = [
        'registration_enabled', 'email_verification_required', 'maintenance_mode',
        'markup_percent', 'min_deposit', 'referral_commission', 'contact_email',
    ];

    /** @var list<string> */
    public const WALLET_KEYS = [
        'wallet_btc', 'wallet_eth', 'wallet_usdt_trc20', 'wallet_usdt_erc20', 'wallet_bnb', 'wallet_sol',
    ];

    /** @var list<string> */
    public const DEPOSIT_KEYS = [
        'deposit_auto_confirm', 'deposit_min_confirmations', 'deposit_amount_tolerance',
        'api_etherscan', 'api_trongrid', 'api_bscscan',
    ];

    /** @var list<string> */
    public const PAYMENT_KEYS = [
        'payment_smmpaygate_enabled', 'payment_smmpaygate_api_url', 'payment_smmpaygate_api_key',
        'payment_smmpaygate_merchant_id', 'payment_smmpaygate_secret',
        'payment_heleket_enabled', 'payment_heleket_merchant_id', 'payment_heleket_api_key',
        'payment_heleket_mode', 'payment_heleket_currency', 'payment_heleket_network',
        'payment_usdt_trc20_enabled',
        'payment_binance_pay_enabled', 'payment_binance_pay_api_key', 'payment_binance_pay_secret',
        'payment_zarinpal_enabled', 'payment_zarinpal_merchant_id', 'payment_zarinpal_usd_rate', 'payment_zarinpal_sandbox',
        'payment_cryptocloud_enabled', 'payment_cryptocloud_shop_id', 'payment_cryptocloud_api_key',
    ];

    /** @var list<string> */
    public const EMAIL_KEYS = [
        'mail_mode', 'smtp_encryption', 'mail_lang', 'smtp_from', 'contact_email',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
    ];

    /** @var list<string> */
    public const CHECKBOX_KEYS = [
        'payment_smmpaygate_enabled', 'payment_heleket_enabled', 'payment_usdt_trc20_enabled',
        'payment_binance_pay_enabled', 'payment_zarinpal_enabled', 'payment_cryptocloud_enabled',
    ];

    /** @var list<string> */
    public const WALLET_SECTION_KEYS = [
        'wallet_btc', 'wallet_eth', 'wallet_usdt_trc20', 'wallet_usdt_erc20', 'wallet_bnb', 'wallet_sol',
        'deposit_auto_confirm', 'deposit_min_confirmations', 'deposit_amount_tolerance',
        'api_etherscan', 'api_trongrid', 'api_bscscan',
    ];

    /** @return array<string, list<string>> */
    private static function sectionKeys(): array
    {
        return [
            'branding' => self::BRANDING_KEYS,
            'general'  => self::GENERAL_KEYS,
            'wallets'  => self::WALLET_SECTION_KEYS,
            'payments' => self::PAYMENT_KEYS,
            'email'    => self::EMAIL_KEYS,
        ];
    }

    private ChildPanelManager $cpm;
    private ChildPanelDeployer $deployer;

    public function __construct()
    {
        $this->cpm = new ChildPanelManager();
        $this->deployer = new ChildPanelDeployer();
    }

    /** @return array{success: bool, error?: string, panel?: array<string, mixed>, document_root?: string} */
    public function resolveLivePanel(int $panelId, int $userId): array
    {
        $panel = $this->cpm->getPanelForUser($panelId, $userId);
        if ($panel === null) {
            return ['success' => false, 'error' => 'Panel not found.'];
        }
        if (($panel['status'] ?? '') !== ChildPanelManager::STATUS_ACTIVE
            || ($panel['provision_status'] ?? '') !== ChildPanelManager::PROVISION_READY) {
            return ['success' => false, 'error' => 'Panel is not live yet.'];
        }
        $documentRoot = trim((string) ($panel['document_root'] ?? ''));
        if ($documentRoot === '') {
            $documentRoot = $this->deployer->docrootForDomain((string) ($panel['domain'] ?? ''));
        }
        if ($documentRoot === '' || !is_dir($documentRoot)) {
            return ['success' => false, 'error' => 'Panel files not found on server.'];
        }
        return ['success' => true, 'panel' => $panel, 'document_root' => $documentRoot];
    }

    /** @return array<string, string> */
    public function readSettings(string $documentRoot): array
    {
        $pdo = $this->deployer->pdoFromDocumentRoot($documentRoot);
        if ($pdo === null) {
            return [];
        }
        $allKeys = array_merge(
            self::BRANDING_KEYS,
            self::GENERAL_KEYS,
            self::WALLET_KEYS,
            self::DEPOSIT_KEYS,
            self::PAYMENT_KEYS,
            self::EMAIL_KEYS
        );
        $placeholders = implode(',', array_fill(0, count($allKeys), '?'));
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
        $stmt->execute($allKeys);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string) $row['key']] = (string) ($row['value'] ?? '');
        }
        return $out;
    }

    /** @return array{client_id: string, client_secret: string, configured: bool} */
    public function readGoogleOAuth(string $documentRoot): array
    {
        $cfg = $this->deployer->readConfigConstants($documentRoot, ['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET']);
        $id = trim((string) ($cfg['GOOGLE_CLIENT_ID'] ?? ''));
        $secret = trim((string) ($cfg['GOOGLE_CLIENT_SECRET'] ?? ''));
        return [
            'client_id' => $id,
            'client_secret' => $secret,
            'configured' => $id !== '',
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error?: string}
     */
    public function saveSection(int $panelId, int $userId, string $section, array $post): array
    {
        $keys = self::sectionKeys()[$section] ?? null;
        if ($keys === null) {
            return ['success' => false, 'error' => 'Invalid settings section.'];
        }

        $resolved = $this->resolveLivePanel($panelId, $userId);
        if (!$resolved['success']) {
            return ['success' => false, 'error' => $resolved['error'] ?? 'Panel not available.'];
        }

        $pdo = $this->deployer->pdoFromDocumentRoot((string) $resolved['document_root']);
        if ($pdo === null) {
            return ['success' => false, 'error' => 'Could not connect to panel database.'];
        }

        $values = [];
        foreach ($keys as $key) {
            if ($key === 'smtp_pass' && trim((string) ($post[$key] ?? '')) === '') {
                continue;
            }
            if (in_array($key, self::CHECKBOX_KEYS, true)) {
                $values[$key] = isset($post[$key]) && (string) $post[$key] === '1' ? '1' : '0';
                continue;
            }
            if (array_key_exists($key, $post)) {
                $values[$key] = trim((string) $post[$key]);
            }
        }

        foreach (self::CHECKBOX_KEYS as $cb) {
            if (in_array($cb, $keys, true) && !array_key_exists($cb, $values)) {
                $values[$cb] = '0';
            }
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
            );
            foreach ($values as $key => $value) {
                $stmt->execute([$key, $value]);
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Could not save settings: ' . $e->getMessage()];
        }

        $this->cpm->appendLogPublic($panelId, 'Settings updated (' . $section . ') from Child Panel page.');
        return ['success' => true];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, error?: string}
     */
    public function saveGoogleOAuth(int $panelId, int $userId, array $post): array
    {
        $resolved = $this->resolveLivePanel($panelId, $userId);
        if (!$resolved['success']) {
            return ['success' => false, 'error' => $resolved['error'] ?? 'Panel not available.'];
        }
        $documentRoot = (string) $resolved['document_root'];
        $clientId = trim((string) ($post['google_client_id'] ?? ''));
        $clientSecret = trim((string) ($post['google_client_secret'] ?? ''));

        if ($clientId !== '' && strlen($clientId) < 10) {
            return ['success' => false, 'error' => 'Invalid Google Client ID.'];
        }

        $existing = $this->readGoogleOAuth($documentRoot);
        if ($clientSecret === '' && $existing['client_secret'] !== '') {
            $clientSecret = $existing['client_secret'];
        }

        if (!$this->deployer->updateConfigConstants($documentRoot, [
            'GOOGLE_CLIENT_ID' => $clientId,
            'GOOGLE_CLIENT_SECRET' => $clientSecret,
        ])) {
            return ['success' => false, 'error' => 'Could not update panel config.php.'];
        }

        $this->cpm->appendLogPublic($panelId, 'Google OAuth settings updated from Child Panel page.');
        return ['success' => true];
    }

    /**
     * @param array<string, mixed> $file
     * @return array{success: bool, error?: string, path?: string}
     */
    public function uploadBranding(int $panelId, int $userId, string $type, array $file): array
    {
        if (!in_array($type, ['logo', 'favicon'], true)) {
            return ['success' => false, 'error' => 'Invalid upload type.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed. Try again.'];
        }

        $resolved = $this->resolveLivePanel($panelId, $userId);
        if (!$resolved['success']) {
            return ['success' => false, 'error' => $resolved['error'] ?? 'Panel not available.'];
        }
        $documentRoot = (string) $resolved['document_root'];

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, (string) $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime]) || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Upload a valid image (JPEG, PNG, GIF, WebP, SVG or ICO, max 2 MB).'];
        }

        $dir = $documentRoot . '/assets/img/branding';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'error' => 'Could not create upload folder on panel.'];
        }

        $ext = $allowed[$mime];
        $filename = $type . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $fullPath = $dir . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Could not save uploaded file.'];
        }

        $relativePath = 'assets/img/branding/' . $filename;
        $settingKey = $type === 'logo' ? 'site_logo' : 'site_favicon';

        $pdo = $this->deployer->pdoFromDocumentRoot($documentRoot);
        if ($pdo === null) {
            @unlink($fullPath);
            return ['success' => false, 'error' => 'Could not connect to panel database.'];
        }
        try {
            $pdo->prepare(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
            )->execute([$settingKey, $relativePath]);
        } catch (PDOException $e) {
            @unlink($fullPath);
            return ['success' => false, 'error' => 'Could not save branding path.'];
        }

        $this->cpm->appendLogPublic($panelId, ucfirst($type) . ' uploaded from Child Panel page.');
        return ['success' => true, 'path' => $relativePath];
    }

    public function panelWebhookUrl(string $panelUrl, string $gateway): string
    {
        return rtrim($panelUrl, '/') . '/payment-webhook.php?gateway=' . rawurlencode($gateway);
    }

    public function brandingPreviewUrl(string $panelUrl, string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return rtrim($panelUrl, '/') . '/' . ltrim($path, '/');
    }
}
