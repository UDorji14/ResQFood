<?php
/**
 * ResQFood — CSRF Protection
 * ───────────────────────────
 * Double-submit token stored in $_SESSION.
 * One token per session — regenerated on login (session_regenerate_id).
 *
 * Usage in forms:
 *   <?= csrfField() ?>
 *
 * Usage in POST handlers:
 *   verifyCsrfOnPost();   // validates only on POST, no-op on GET
 *   verifyCsrf();         // always validates (use inside POST-only handlers)
 */

/**
 * Return (and generate if missing) the session CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden <input> carrying the CSRF token.
 * Include inside every <form> that modifies data.
 */
function csrfField(): string
{
    $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate the submitted token against the session token.
 * Terminates with 403 if invalid — no data should be processed after a failure.
 */
function verifyCsrf(): void
{
    // Accept token from POST body or custom HTTP header (for AJAX requests)
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!hash_equals(csrfToken(), (string) $submitted)) {
        http_response_code(403);
        die('Security token mismatch. Please go back, refresh the page, and try again.');
    }
}

/**
 * Validate CSRF only when the request method is POST.
 * Safe to call at the top of pages that serve both GET and POST.
 */
function verifyCsrfOnPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
    }
}
