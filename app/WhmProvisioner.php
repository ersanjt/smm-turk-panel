<?php
/**
 * WHM / cPanel API — addon domains, MySQL databases for child panels.
 */
class WhmProvisioner
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isConfigured(): bool
    {
        return $this->apiHost() !== '' && $this->whmUser() !== '' && $this->apiToken() !== '';
    }

    public function cpanelUser(): string
    {
        $user = trim((string) ($this->db->getSetting('child_panel_cpanel_user') ?? ''));
        return $user !== '' ? $user : $this->whmUser();
    }

    private function apiHost(): string
    {
        $host = trim((string) ($this->db->getSetting('child_panel_whm_host') ?? ''));
        $host = preg_replace('#^https?://#', '', $host) ?? $host;
        return rtrim($host, '/');
    }

    private function whmUser(): string
    {
        return trim((string) ($this->db->getSetting('child_panel_whm_username') ?? ''));
    }

    private function apiToken(): string
    {
        return trim((string) ($this->db->getSetting('child_panel_whm_api_token') ?? ''));
    }

    private function apiPort(): int
    {
        $port = (int) ($this->db->getSetting('child_panel_whm_port') ?? 2087);
        return $port > 0 ? $port : 2087;
    }

    /** @return array{success: bool, data?: mixed, error?: string, http_code?: int} */
    private function whmCpanelCall(string $module, string $func, array $params = [], int $apiVersion = 2): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WHM API not configured.'];
        }

        $query = array_merge([
            'cpanel_jsonapi_user' => $this->cpanelUser(),
            'cpanel_jsonapi_apiversion' => $apiVersion,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $func,
        ], $params);

        $url = 'https://' . $this->apiHost() . ':' . $this->apiPort() . '/json-api/cpanel?' . http_build_query($query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Authorization: whm ' . $this->whmUser() . ':' . $this->apiToken(),
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['success' => false, 'error' => 'WHM curl error: ' . $err, 'http_code' => $code];
        }

        $json = json_decode((string) $body, true);
        if ($code < 200 || $code >= 300) {
            return [
                'success' => false,
                'error' => 'WHM HTTP ' . $code . ': ' . substr((string) $body, 0, 300),
                'http_code' => $code,
                'data' => $json,
            ];
        }

        $cpanelResult = $json['cpanelresult'] ?? $json;
        $event = $cpanelResult['event']['result'] ?? null;
        if ($event !== null && (int) $event !== 1) {
            $reason = $cpanelResult['event']['reason'] ?? $cpanelResult['error'] ?? 'Unknown cPanel error';
            return ['success' => false, 'error' => (string) $reason, 'data' => $json];
        }

        $data = $cpanelResult['data'] ?? $cpanelResult;
        if (isset($data['result']) && (int) $data['result'] === 0) {
            $reason = $data['reason'] ?? $data['error'] ?? 'cPanel API failed';
            if (stripos((string) $reason, 'already') === false) {
                return ['success' => false, 'error' => (string) $reason, 'data' => $json];
            }
        }

        return ['success' => true, 'data' => $data, 'http_code' => $code];
    }

    /** @return array{success: bool, error?: string, document_root?: string, message?: string, panel_url?: string} */
    public function provisionDomain(string $domain): array
    {
        $domain = ChildPanelManager::normalizeDomain($domain);
        if ($domain === '') {
            return ['success' => false, 'error' => 'Invalid domain.'];
        }

        if ($this->domainExistsOnAccount($domain)) {
            $deployer = new ChildPanelDeployer();
            $docroot = $deployer->docrootForDomain($domain);
            return [
                'success' => true,
                'message' => 'Domain already exists on cPanel account.',
                'document_root' => $docroot,
                'panel_url' => 'https://' . $domain,
            ];
        }

        $sub = preg_replace('/[^a-z0-9]/', '', strtolower(explode('.', $domain)[0] ?? 'panel')) ?: 'panel';
        if (strlen($sub) < 2) {
            $sub = 'cp' . substr(md5($domain), 0, 6);
        }

        $dir = 'public_html/' . $domain;
        $result = $this->whmCpanelCall('AddonDomain', 'addaddondomain', [
            'newdomain' => $domain,
            'subdomain' => $sub,
            'dir' => $dir,
        ]);

        if (!$result['success']) {
            $tryPark = $this->whmCpanelCall('Park', 'park', [
                'domain' => $domain,
                'topdomain' => $this->primaryDomain(),
                'disallowdot' => 0,
            ]);
            if (!$tryPark['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Addon domain failed.'];
            }
            $deployer = new ChildPanelDeployer();
            return [
                'success' => true,
                'message' => 'Parked domain on account.',
                'document_root' => $deployer->homePath() . '/public_html',
                'panel_url' => 'https://' . $domain,
            ];
        }

        $deployer = new ChildPanelDeployer();
        return [
            'success' => true,
            'message' => 'Addon domain created.',
            'document_root' => $deployer->docrootForDomain($domain),
            'panel_url' => 'https://' . $domain,
        ];
    }

    public function domainExistsOnAccount(string $domain): bool
    {
        $domain = ChildPanelManager::normalizeDomain($domain);
        $result = $this->whmCpanelCall('AddonDomain', 'listaddondomains');
        if (!$result['success']) {
            return is_dir((new ChildPanelDeployer())->docrootForDomain($domain));
        }
        $data = $result['data'] ?? [];
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $row) {
            $d = is_array($row) ? ($row['domain'] ?? $row[0] ?? '') : (string) $row;
            if (ChildPanelManager::normalizeDomain($d) === $domain) {
                return true;
            }
        }
        return is_dir((new ChildPanelDeployer())->docrootForDomain($domain));
    }

    private function primaryDomain(): string
    {
        $d = trim((string) ($this->db->getSetting('child_panel_primary_domain') ?? ''));
        if ($d !== '') {
            return ChildPanelManager::normalizeDomain($d);
        }
        if (defined('SITE_URL')) {
            $host = parse_url(SITE_URL, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return $host;
            }
        }
        return 'smm-turk.com';
    }

    /**
     * @return array{success: bool, error?: string, db_name?: string, db_user?: string, db_pass?: string}
     */
    public function createDatabase(string $baseName, string $baseUser, string $password): array
    {
        $baseName = preg_replace('/[^a-z0-9_]/', '', strtolower($baseName)) ?: 'cpdb';
        $baseUser = preg_replace('/[^a-z0-9_]/', '', strtolower($baseUser)) ?: $baseName;

        $dbRes = $this->whmCpanelCall('Mysql', 'create_database', ['name' => $baseName]);
        if (!$dbRes['success'] && stripos($dbRes['error'] ?? '', 'exists') === false) {
            return ['success' => false, 'error' => $dbRes['error'] ?? 'create_database failed'];
        }

        $userRes = $this->whmCpanelCall('Mysql', 'create_user', [
            'name' => $baseUser,
            'password' => $password,
        ]);
        if (!$userRes['success'] && stripos($userRes['error'] ?? '', 'exists') === false) {
            return ['success' => false, 'error' => $userRes['error'] ?? 'create_user failed'];
        }

        $privRes = $this->whmCpanelCall('Mysql', 'set_privileges_on_database', [
            'user' => $baseUser,
            'database' => $baseName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
        if (!$privRes['success']) {
            return ['success' => false, 'error' => $privRes['error'] ?? 'set_privileges failed'];
        }

        $prefix = $this->cpanelUser() . '_';
        return [
            'success' => true,
            'db_name' => $prefix . $baseName,
            'db_user' => $prefix . $baseUser,
            'db_pass' => $password,
        ];
    }

    /** Request AutoSSL for addon domain (best-effort). */
    public function requestAutoSsl(string $domain): void
    {
        $this->whmCpanelCall('SSL', 'installssl', [], 3);
        $this->whmCpanelCall('AutoSSL', 'enable_autossl_for_domains', [
            'domains' => $domain,
        ], 3);
    }
}
