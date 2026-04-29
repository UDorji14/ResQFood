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
function redirect(string $path = ''): never
{
    $target = $path;
    if ($target === '') {
        $target = url();
    } elseif (preg_match('#^(?:https?:)?//#i', $target)) {
        // Keep absolute/external URLs unchanged.
    } elseif (str_starts_with($target, '/')) {
        // Already an absolute app path (e.g. baseUrl(...) result), do not prepend again.
    } else {
        $target = url($target);
    }
    header('Location: ' . $target);
    exit;
}

/**
 * Resolve the URL base path from DOCUMENT_ROOT and project root.
 * Example: /my-project (or empty string when installed in web root).
 */
function detectBasePath(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $projectRoot = realpath(__DIR__ . '/..');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot !== false && $documentRoot !== false) {
        $projectRootNorm = str_replace('\\', '/', $projectRoot);
        $documentRootNorm = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if ($documentRootNorm !== '' && str_starts_with($projectRootNorm, $documentRootNorm)) {
            $relative = substr($projectRootNorm, strlen($documentRootNorm));
            $relative = trim((string) $relative, '/');
            $basePath = $relative === '' ? '' : '/' . $relative;
            return $basePath;
        }
    }

    // Fallback for unusual server setups.
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(dirname($scriptName), '/.');
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    }

    return $basePath;
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', detectBasePath());
}

if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . BASE_PATH);
}

/**
 * Build an internal application URL from a project-relative path.
 */
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    return BASE_PATH . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

/**
 * Backward-compatible URL helper.
 */
function baseUrl(string $path = ''): string
{
    return url($path);
}

/**
 * Build an asset URL (CSS/JS/images/uploads).
 */
function asset(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return url('assets');
    }
    if (str_starts_with($cleanPath, 'assets/')) {
        return url($cleanPath);
    }
    return url('assets/' . $cleanPath);
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
