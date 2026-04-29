<?php
/**
 * ResQFood — Flash Messages
 * ──────────────────────────
 * One-time messages stored in $_SESSION, consumed on next page load.
 *
 * Types:  success | error | warning | info
 *
 * Usage:
 *   setFlash('success', 'Listing created successfully.');
 *   echo displayFlash();   // in your layout
 */

/**
 * Store a flash message.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['_flash'][$type][] = $message;
}

/**
 * Retrieve and remove all flash messages of a given type.
 * Returns an array of message strings (may be empty).
 */
function getFlash(?string $type = null): array
{
    if ($type !== null) {
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $messages;
    }

    $all = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $all;
}

/**
 * Render all pending flash messages as HTML and clear them.
 * Call <?= displayFlash() ?> once inside your layout template.
 * Variant can be "inline" (default) or "toast" (overlay).
 */
function displayFlash(string $variant = 'inline'): string
{
    $all = $_SESSION['_flash'] ?? [];
    if (empty($all)) {
        return '';
    }
    unset($_SESSION['_flash']);

    $icons = [
        'success' => '<svg viewBox="0 0 20 20" width="16" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'error'   => '<svg viewBox="0 0 20 20" width="16" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'warning' => '<svg viewBox="0 0 20 20" width="16" fill="none" aria-hidden="true"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'info'    => '<svg viewBox="0 0 20 20" width="16" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    ];

    $variantClass = $variant === 'toast' ? ' flash-container--toast' : '';
    $variantAttr = $variant === 'toast' ? ' data-flash-variant="toast"' : ' data-flash-variant="inline"';
    $toastInlineStyle = $variant === 'toast'
        ? ' style="position:fixed;top:0.9rem;left:50%;transform:translateX(-50%);z-index:1200;width:min(94vw,520px);margin:0;padding:0;pointer-events:none;"'
        : '';
    $html = '<div class="flash-container' . $variantClass . '"' . $variantAttr . $toastInlineStyle . ' role="status" aria-live="polite">';

    foreach ($all as $type => $messages) {
        $icon = $icons[$type] ?? $icons['info'];
        foreach ($messages as $msg) {
            $safe = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= sprintf(
                '<div class="flash flash--%s">%s<span>%s</span></div>',
                htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
                $icon,
                $safe
            );
        }
    }

    $html .= '</div>';
    return $html;
}
