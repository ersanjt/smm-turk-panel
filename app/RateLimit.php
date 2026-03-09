<?php
/**
 * Simple file-based rate limit (e.g. for login attempts per IP).
 * Uses tmp/rate_limit/ - ensure this directory exists and is writable.
 */
class RateLimit {
    private string $dir;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 5, int $windowSeconds = 900) {
        $this->dir = defined('ROOT_PATH') ? ROOT_PATH . '/tmp/rate_limit' : __DIR__ . '/../tmp/rate_limit';
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    private function key(): string {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip = trim(explode(',', $ip)[0]);
        return md5($ip);
    }

    private function filePath(): string {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
        return $this->dir . '/' . $this->key() . '.json';
    }

    /** Returns true if the request is over the limit (should be blocked). */
    public function isLimited(): bool {
        $path = $this->filePath();
        if (!file_exists($path)) {
            return false;
        }
        $data = @json_decode((string)file_get_contents($path), true);
        if (!is_array($data) || empty($data['start']) || empty($data['count'])) {
            return false;
        }
        if (time() - (int)$data['start'] > $this->windowSeconds) {
            @unlink($path);
            return false;
        }
        return (int)$data['count'] >= $this->maxAttempts;
    }

    /** Record a failed attempt. Call this after a failed login. */
    public function recordAttempt(): void {
        $path = $this->filePath();
        $data = ['start' => time(), 'count' => 1];
        if (file_exists($path)) {
            $existing = @json_decode((string)file_get_contents($path), true);
            if (is_array($existing) && isset($existing['start'], $existing['count'])) {
                if (time() - (int)$existing['start'] <= $this->windowSeconds) {
                    $data['start'] = (int)$existing['start'];
                    $data['count'] = (int)$existing['count'] + 1;
                }
            }
        }
        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    /** Clear rate limit for current IP (e.g. after successful login). */
    public function clear(): void {
        $path = $this->filePath();
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
