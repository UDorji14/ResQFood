<?php
/**
 * ResQFood — Authentication & Access Control
 * ───────────────────────────────────────────
 * Depends on:
 *   includes/session.php   (must be loaded first)
 *   includes/functions.php (for redirect(), e())
 *   includes/flash.php     (for setFlash())
 *   config/db.php          (for db())
 */

// ── Session Readers ───────────────────────────────────────────────────────

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function currentUserRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'User';
}

function currentUserEmail(): string
{
    return $_SESSION['user_email'] ?? '';
}

function dashboardUrlForRole(?string $role = null): string
{
    $role = $role ?? currentUserRole();
    if ($role === 'admin') {
        return baseUrl('modules/admin/dashboard.php');
    }
    return baseUrl('dashboard.php');
}

// ── Access Guards ─────────────────────────────────────────────────────────

/**
 * Halt and redirect if the visitor is not logged in.
 * Stores the intended URL so login can redirect back after success.
 */
function requireLogin(string $redirectTo = ''): void
{
    if (!isLoggedIn()) {
        if ($redirectTo === '') {
            $redirectTo = baseUrl('login.php');
        }
        setFlash('error', 'Please log in to continue.');
        redirect($redirectTo);
    }
}

/**
 * Enforce that the logged-in user holds one of the given roles.
 * Usage: requireRole(['admin']) or requireRole(['business', 'admin'])
 */
function requireRole(array $allowedRoles, string $redirectTo = ''): void
{
    requireLogin();
    if (!in_array(currentUserRole(), $allowedRoles, true)) {
        setFlash('error', 'You do not have permission to access that page.');
        redirect($redirectTo !== '' ? $redirectTo : baseUrl('dashboard.php'));
    }
}

/**
 * Redirect already-authenticated users away from guest-only pages.
 * Call at the top of login.php and register.php.
 */
function redirectIfLoggedIn(string $redirectTo = ''): void
{
    if (isLoggedIn()) {
        redirect($redirectTo !== '' ? $redirectTo : dashboardUrlForRole());
    }
}

// ── Login / Logout ────────────────────────────────────────────────────────

/**
 * Write user data into the session after a successful credential check.
 * Regenerates the session ID to prevent session-fixation attacks.
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Fully destroy the current session and redirect.
 */
function logoutUser(string $redirectTo = ''): void
{
    // Clear all session variables
    $_SESSION = [];

    // Expire the session cookie
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
    redirect($redirectTo !== '' ? $redirectTo : baseUrl('login.php'));
}

// ── DB User Fetch ─────────────────────────────────────────────────────────

/**
 * Fetch the current user's full row from the database.
 * Returns null if not logged in or user no longer exists.
 */
function getCurrentUser(): ?array
{
    $id = currentUserId();
    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('
        SELECT id, full_name, email, phone, role, status, email_notifications_enabled, created_at
        FROM   users
        WHERE  id = ?
        LIMIT  1
    ');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

// ── Audit Logging ─────────────────────────────────────────────────────────

/**
 * Write an entry to audit_logs.
 * Call after any significant action (login, role change, deletion, etc.)
 *
 * @param string      $action  Short machine-readable label, e.g. 'user_login'
 * @param string|null $details Optional context (plain text or JSON string)
 * @param int|null    $userId  Defaults to current logged-in user
 */
function auditLog(string $action, ?string $details = null, ?int $userId = null): void
{
    try {
        $stmt = db()->prepare('
            INSERT INTO audit_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId ?? currentUserId(),
            $action,
            $details,
            clientIp(),
        ]);
    } catch (PDOException $e) {
        error_log('[ResQFood auditLog] ' . $e->getMessage());
    }
}
