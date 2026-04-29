<?php
/**
 * ResQFood — Logout Handler
 * POST-only for CSRF safety; GET falls through gracefully.
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

if (isLoggedIn()) {
    auditLog('user_logout');
    logoutUser(baseUrl('login.php'));
}

// Already logged out — redirect silently
redirect(baseUrl('login.php'));
