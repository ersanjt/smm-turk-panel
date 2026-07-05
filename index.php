<?php
/**
 * Legacy entry — / is the public home; panel lives at /dashboard.
 */
require_once __DIR__ . '/app/init.php';
redirect($auth->isLoggedIn() ? url('dashboard.php') : url('home.php'));
