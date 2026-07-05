<?php
/**
 * Admin bootstrap — require instead of duplicating init + requireAdmin in each file.
 *
 *   require_once __DIR__ . '/_init.php';
 *   $pageTitle = 'Page Title';
 */
$appDir = __DIR__ . '/../app';
if (function_exists('opcache_invalidate')) {
    foreach (['init.php', 'ChildPanelRemoteSettings.php'] as $f) {
        @opcache_invalidate($appDir . '/' . $f, true);
    }
}
require_once $appDir . '/init.php';
$auth->requireAdmin();
