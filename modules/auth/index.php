<?php
/**
 * ResQFood — Auth Module Index
 * Redirects guests to login and authenticated users to their dashboard.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    redirect(baseUrl('dashboard.php'));
} else {
    redirect(baseUrl('login.php'));
}
