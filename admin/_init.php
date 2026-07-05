<?php
/**
 * Admin bootstrap — require instead of duplicating init + requireAdmin in each file.
 *
 *   require_once __DIR__ . '/_init.php';
 *   $pageTitle = 'Page Title';
 */
require_once __DIR__ . '/../app/init.php';
$auth->requireAdmin();
