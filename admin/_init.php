<?php
/**
 * Admin bootstrap — require instead of duplicating init + requireAdmin in each file.
 *
 *   require_once __DIR__ . '/_init.php';
 *   $pageTitle = 'Page Title';
 */
$appDir = __DIR__ . '/../app';
if (function_exists('opcache_invalidate')) {
    foreach (['init.php', 'init-v2.php', 'ChildPanelRemoteSettings.php'] as $f) {
        @opcache_invalidate($appDir . '/' . $f, true);
    }
}
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
require_once $appDir . '/init-v2.php';
$auth->requireAdmin();
