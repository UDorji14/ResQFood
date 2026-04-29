<?php
/**
 * ResQFood — Global Helper Functions
 * ────────────────────────────────────
 * General-purpose utilities shared across all modules.
 * Loaded once via the bootstrap chain.
 */

// ── Output ────────────────────────────────────────────────────────────────

/**
 * Escape a value for safe HTML output.
 * Always use: <?= e($untrustedValue) ?>
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Navigation ────────────────────────────────────────────────────────────

/**
 * Redirect to a URL and halt execution.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Return the base URL of the project (for links and assets).
 * Example: baseUrl('login.php') → /ResQFood/login.php
 */
function baseUrl(string $path = ''): string
{
    $base = '/ResQFood';
    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/**
 * Return the filename of the current script (for nav active states).
 */
function currentPage(): string
{
    return basename($_SERVER['PHP_SELF']);
}

// ── Input ─────────────────────────────────────────────────────────────────

/**
 * Trim and strip tags from a string input.
 * Never use on passwords — those must not be stripped.
 */
function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Sanitize every value in an associative array.
 */
function sanitizeAll(array $data): array
{
    return array_map('sanitize', $data);
}

/**
 * Return POST value, sanitized, with an optional default.
 */
function post(string $key, string $default = ''): string
{
    return sanitize($_POST[$key] ?? $default);
}

// ── Formatting ────────────────────────────────────────────────────────────

/**
 * Format a MySQL datetime string for human display.
 */
function formatDate(string $datetime, string $format = 'd M Y, H:i'): string
{
    return $datetime ? date($format, strtotime($datetime)) : '-';
}

/**
 * Truncate a string to a given length and append an ellipsis if truncated.
 */
function truncate(string $text, int $limit = 120): string
{
    return mb_strlen($text) > $limit
        ? mb_substr($text, 0, $limit) . '…'
        : $text;
}

// ── Tokens & Codes ────────────────────────────────────────────────────────

/**
 * Generate a random uppercase hex token.
 * Used for pickup codes, verification links, etc.
 */
function generateToken(int $bytes = 4): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}

// ── Role Helpers ──────────────────────────────────────────────────────────

/**
 * Human-readable label for a role slug.
 */
function roleLabel(string $role): string
{
    return match ($role) {
        'business'     => 'Food Business',
        'charity'      => 'Charity',
        'admin'        => 'Administrator',
        'general_user' => 'General User',
        default        => ucfirst(str_replace('_', ' ', $role)),
    };
}

/**
 * CSS class suffix for a role badge.
 */
function roleBadgeClass(string $role): string
{
    return match ($role) {
        'business'     => 'olive',
        'charity'      => 'terra',
        'admin'        => 'amber',
        'general_user' => 'sage',
        default        => 'default',
    };
}

// ── Status Helpers ────────────────────────────────────────────────────────

/**
 * CSS class suffix for a listing/reservation status.
 */
function statusClass(string $status): string
{
    return match ($status) {
        'available', 'active', 'verified', 'collected', 'resolved' => 'green',
        'reserved',  'pending', 'under_review'                     => 'amber',
        'expired',   'cancelled', 'suspended', 'rejected',
        'dismissed', 'no_show'                                      => 'red',
        default                                                     => 'default',
    };
}

/**
 * Human-readable label for a status slug.
 */
function statusLabel(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}

// ── IP Helper ─────────────────────────────────────────────────────────────

/**
 * Get the client IP address (with basic proxy awareness).
 */
function clientIp(): string
{
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return 'unknown';
}
