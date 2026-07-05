<?php
/**
 * Deploy child panel files, database, and admin user on the local/WHM server.
 */
class ChildPanelDeployer
{
    /** @var list<string> */
    private static array $copyExcludeDirs = [
        '.git', '.github', 'node_modules', 'tmp', 'storage/child-panels', 'storage/logs',
        'uploads/tickets', 'docs',
    ];

    /** @var list<string> */
    private static array $copyExcludeFiles = [
        'config.php', 'deploy-secret.txt', '.env', 'DEPLOY_VERSION',
    ];

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function homePath(): string
    {
        $path = trim((string) ($this->db->getSetting('child_panel_home_path') ?? ''));
        if ($path !== '' && is_dir($path)) {
            return rtrim($path, '/');
        }
        if (defined('ROOT_PATH') && is_dir(ROOT_PATH)) {
            $parent = dirname(ROOT_PATH);
            if (basename(ROOT_PATH) === 'public_html' && is_dir($parent)) {
                return $parent;
            }
        }
        return '';
    }

    public function docrootForDomain(string $domain): string
    {
        $home = $this->homePath();
        if ($home === '') {
            return '';
        }
        return $home . '/public_html/' . ChildPanelManager::normalizeDomain($domain);
    }

    public function templatePath(): string
    {
        $custom = trim((string) ($this->db->getSetting('child_panel_template_path') ?? ''));
        if ($custom !== '' && is_dir($custom)) {
            return rtrim($custom, '/\\');
        }
        return defined('ROOT_PATH') ? ROOT_PATH : '';
    }

    /**
     * @param array<string, mixed> $panel
     * @return array{success: bool, error?: string, document_root?: string, db_name?: string}
     */
    public function deploy(array $panel, string $documentRoot, string $adminPlainPassword): array
    {
        $domain = ChildPanelManager::normalizeDomain((string) ($panel['domain'] ?? ''));
        $template = $this->templatePath();
        if ($template === '' || !is_dir($template)) {
            return ['success' => false, 'error' => 'Panel template path not found on server.'];
        }
        if ($documentRoot === '') {
            $documentRoot = $this->docrootForDomain($domain);
        }
        if ($documentRoot === '') {
            return ['success' => false, 'error' => 'Could not determine document root for domain.'];
        }

        if (!is_dir($documentRoot)) {
            if (!@mkdir($documentRoot, 0755, true) && !is_dir($documentRoot)) {
                return ['success' => false, 'error' => 'Could not create document root: ' . $documentRoot];
            }
        }

        $copy = $this->copyTemplate($template, $documentRoot);
        if (!$copy['success']) {
            return $copy;
        }

        $dbResult = $this->bootstrapDatabase((int) ($panel['id'] ?? 0), $domain);
        if (!$dbResult['success']) {
            return $dbResult;
        }

        $parentApi = trim((string) ($this->db->getSetting('child_panel_parent_api_url') ?? ''));
        if ($parentApi === '') {
            $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
            $parentApi = $siteUrl !== '' ? $siteUrl . '/api/v2' : '';
        }

        $apiKey = trim((string) ($panel['panel_api_key'] ?? ''));
        $siteName = ucfirst(preg_replace('/\.[^.]+$/', '', $domain) ?: $domain);

        $configOk = $this->writeConfigPhp($documentRoot, [
            'domain' => $domain,
            'site_name' => $siteName,
            'db_host' => 'localhost',
            'db_name' => $dbResult['db_name'] ?? '',
            'db_user' => $dbResult['db_user'] ?? '',
            'db_pass' => $dbResult['db_pass'] ?? '',
            'parent_api_url' => $parentApi,
            'parent_api_key' => $apiKey,
            'secret_key' => bin2hex(random_bytes(16)),
        ]);
        if (!$configOk) {
            return ['success' => false, 'error' => 'Could not write config.php for child panel.'];
        }

        $adminOk = $this->createAdminUser(
            $dbResult['pdo'],
            (string) ($panel['admin_username'] ?? ''),
            $adminPlainPassword,
            (string) ($panel['admin_email'] ?? $panel['email'] ?? ''),
            $apiKey
        );
        if (!$adminOk['success']) {
            return $adminOk;
        }

        $this->syncServicesFromParent($dbResult['pdo']);

        @mkdir($documentRoot . '/storage/logs', 0755, true);
        @mkdir($documentRoot . '/uploads', 0755, true);

        return [
            'success' => true,
            'document_root' => $documentRoot,
            'db_name' => $dbResult['db_name'] ?? '',
        ];
    }

    /** @return array{success: bool, error?: string} */
    private function copyTemplate(string $src, string $dest): array
    {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $rel = substr($item->getPathname(), strlen($src) + 1);
                $rel = str_replace('\\', '/', $rel);
                if ($this->shouldSkipCopy($rel)) {
                    continue;
                }
                $target = $dest . '/' . $rel;
                if ($item->isDir()) {
                    if (!is_dir($target)) {
                        @mkdir($target, 0755, true);
                    }
                } else {
                    $dir = dirname($target);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    if (!is_file($target)) {
                        @copy($item->getPathname(), $target);
                    }
                }
            }
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'File copy failed: ' . $e->getMessage()];
        }
        return ['success' => true];
    }

    private function shouldSkipCopy(string $relPath): bool
    {
        foreach (self::$copyExcludeDirs as $dir) {
            if ($relPath === $dir || str_starts_with($relPath, $dir . '/')) {
                return true;
            }
        }
        $base = basename($relPath);
        if (in_array($base, self::$copyExcludeFiles, true)) {
            return true;
        }
        if (str_ends_with($relPath, '.example') || str_ends_with($relPath, '.md')) {
            return true;
        }
        return false;
    }

    /**
     * @return array{success: bool, error?: string, db_name?: string, db_user?: string, db_pass?: string, pdo?: PDO}
     */
    private function bootstrapDatabase(int $panelId, string $domain): array
    {
        $slug = 'cp' . $panelId;
        $dbName = $slug;
        $dbUser = $slug;
        $dbPass = bin2hex(random_bytes(12));

        $whm = new WhmProvisioner();
        if ($whm->isConfigured()) {
            $created = $whm->createDatabase($dbName, $dbUser, $dbPass);
            if (!$created['success']) {
                return $created;
            }
            $dbName = $created['db_name'] ?? $dbName;
            $dbUser = $created['db_user'] ?? $dbUser;
        } else {
            return [
                'success' => false,
                'error' => 'WHM API not configured — set WHM host, username, and API token in Admin → Settings → Child Panel. '
                    . 'On shared hosting you cannot create MySQL databases via SQL directly.',
            ];
        }

        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Child DB connection failed: ' . $e->getMessage()];
        }

        $sqlFile = (defined('ROOT_PATH') ? ROOT_PATH : '') . '/install-cpanel.sql';
        if (!is_file($sqlFile)) {
            return ['success' => false, 'error' => 'install-cpanel.sql not found.'];
        }
        $import = $this->importSqlFile($pdo, $sqlFile);
        if (!$import['success']) {
            return $import;
        }

        return [
            'success' => true,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPass,
            'pdo' => $pdo,
        ];
    }

    /** @return array{success: bool, error?: string} */
    private function importSqlFile(PDO $pdo, string $path): array
    {
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            return ['success' => false, 'error' => 'Empty SQL file.'];
        }
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*\n/', $sql) ?: [];
        foreach ($parts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || stripos($stmt, 'CREATE DATABASE') !== false || stripos($stmt, 'USE ') === 0) {
                continue;
            }
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'already exists') === false
                    && stripos($e->getMessage(), 'Duplicate') === false) {
                    return ['success' => false, 'error' => 'SQL import: ' . $e->getMessage()];
                }
            }
        }
        return ['success' => true];
    }

    /** @param array<string, string> $cfg */
    private function writeConfigPhp(string $documentRoot, array $cfg): bool
    {
        $siteUrl = 'https://' . ($cfg['domain'] ?? '');
        $parentUrl = rtrim($cfg['parent_api_url'] ?? '', '/');
        $parentKey = $cfg['parent_api_key'] ?? '';
        $content = '<?php' . "\n"
            . "// Auto-generated child panel config — " . date('c') . "\n"
            . "define('DB_HOST', 'localhost');\n"
            . "define('DB_NAME', " . var_export($cfg['db_name'] ?? '', true) . ");\n"
            . "define('DB_USER', " . var_export($cfg['db_user'] ?? '', true) . ");\n"
            . "define('DB_PASS', " . var_export($cfg['db_pass'] ?? '', true) . ");\n"
            . "define('DB_CHARSET', 'utf8mb4');\n"
            . "define('SITE_URL', " . var_export($siteUrl, true) . ");\n"
            . "define('SITE_NAME', " . var_export($cfg['site_name'] ?? 'Child Panel', true) . ");\n"
            . "define('GEO_REGION', 'TR');\n"
            . "define('GEO_PLACENAME', 'Turkey');\n"
            . "define('GEO_LOCALITY', 'Ankara');\n"
            . "define('GEO_LAT', 39.9334);\n"
            . "define('GEO_LNG', 32.8597);\n"
            . "define('GEO_TIMEZONE', 'Europe/Istanbul');\n"
            . "define('OG_IMAGE_URL', 'assets/img/og-default.png');\n"
            . "define('PROVIDER_API_URL', " . var_export($parentUrl, true) . ");\n"
            . "define('PROVIDER_API_KEY', " . var_export($parentKey, true) . ");\n"
            . "define('SECRET_KEY', " . var_export($cfg['secret_key'] ?? bin2hex(random_bytes(16)), true) . ");\n"
            . "define('CRON_SECRET', '');\n"
            . "define('SMM_CSP_REPORT_ONLY', false);\n"
            . "define('SESSION_LIFETIME', 86400);\n"
            . "define('DEPOSITS_CRYPTO_ONLY', true);\n"
            . "define('CRYPTO_WALLET_ADDRESS', '');\n"
            . "define('MARKUP_PERCENT', 15);\n"
            . "define('MIN_DEPOSIT', 10);\n"
            . "define('REFERRAL_COMMISSION', 2);\n"
            . "define('GOOGLE_CLIENT_ID', '');\n"
            . "define('GOOGLE_CLIENT_SECRET', '');\n"
            . "define('SMM_CHILD_PANEL', true);\n"
            . "date_default_timezone_set('UTC');\n"
            . "define('SMM_PRODUCTION', true);\n"
            . "error_reporting(E_ALL);\n"
            . "ini_set('display_errors', 0);\n";

        return @file_put_contents($documentRoot . '/config.php', $content) !== false;
    }

    /** @return array{success: bool, error?: string} */
    private function createAdminUser(PDO $pdo, string $username, string $password, string $email, string $apiKey): array
    {
        $username = trim($username);
        $email = trim($email);
        if ($username === '' || strlen($password) < 6) {
            return ['success' => false, 'error' => 'Invalid admin credentials for child panel.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid admin email for child panel.'];
        }
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, role, status, api_key, api_key_created_at, email_verification_token)
                 VALUES (?, ?, ?, 'admin', 'active', ?, NOW(), NULL)
                 ON DUPLICATE KEY UPDATE password = VALUES(password), role = 'admin', status = 'active'"
            );
            $stmt->execute([$username, $email, $hash, $apiKey !== '' ? $apiKey : bin2hex(random_bytes(20))]);
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Admin user create failed: ' . $e->getMessage()];
        }
        return ['success' => true];
    }

    private function syncServicesFromParent(PDO $childPdo): void
    {
        try {
            $parent = Database::getInstance()->getConnection();
            $rows = $parent->query("SELECT service_id, name, type, category, rate, min, max, refill, cancel, status, markup FROM services WHERE status = 'active' LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows === []) {
                return;
            }
            $stmt = $childPdo->prepare(
                'INSERT INTO services (service_id, name, type, category, rate, min, max, refill, cancel, status, markup)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE name=VALUES(name), rate=VALUES(rate), status=VALUES(status)'
            );
            foreach ($rows as $row) {
                $stmt->execute([
                    $row['service_id'], $row['name'], $row['type'], $row['category'],
                    $row['rate'], $row['min'], $row['max'], $row['refill'], $row['cancel'],
                    $row['status'], $row['markup'],
                ]);
            }
        } catch (Throwable $e) {
            Logger::log('Child panel service sync: ' . $e->getMessage(), 'child_panel');
        }
    }
}
