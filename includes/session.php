<?php
/**
 * ResQFood — Session Bootstrap
 * ─────────────────────────────
 * Must be the FIRST include on every entry-point page.
 * Sets hardened cookie parameters before session_start().
 *
 * Never call session_start() anywhere else — always include this file.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,        // Cookie expires when browser closes
        'path'     => '/',
        'secure'   => false,    // Set true in production (HTTPS)
        'httponly' => true,     // Prevents JavaScript access to cookie
        'samesite' => 'Lax',   // Protects against most CSRF via cookie
    ]);

    session_name('RESQFOOD_SESS');
    session_start();
}
