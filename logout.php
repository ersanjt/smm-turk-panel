<?php
// logout.php
require_once __DIR__ . '/app/init.php';
$_SESSION = [];
session_destroy();
$loginUrl = defined('SITE_URL') && SITE_URL !== '' ? rtrim(SITE_URL, '/') . '/login.php' : '/login.php';
header('Location: ' . $loginUrl);
exit;
