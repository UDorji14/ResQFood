<?php
/**
 * ResQFood Charity Shell
 * Shared app-shell for charity internal pages.
 */

if (!function_exists('charShellIcon')) {
    function charShellIcon(string $key): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1.5"/><rect x="11" y="3" width="6" height="6" rx="1.5"/><rect x="3" y="11" width="6" height="6" rx="1.5"/><rect x="11" y="11" width="6" height="6" rx="1.5"/></svg>',
            'browse' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 6.2h13"/><path d="M7 6.2v9.6"/><path d="M13 6.2v9.6"/></svg>',
            'reservations' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="4.5" width="13" height="11" rx="2"/><path d="M7 8.2h6"/><path d="M7 11.2h4"/></svg>',
            'reports' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3L17 16H3L10 3Z"/><path d="M10 7.8V11.4"/><path d="M10 13.6h.01"/></svg>',
            'profile' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="6.3" r="3.1"/><path d="M4 16c.6-3.4 3.2-5.2 6-5.2s5.4 1.8 6 5.2"/></svg>',
            'logout' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 4.5H6.2C5.4 4.5 4.8 5.1 4.8 5.9V14.1C4.8 14.9 5.4 15.5 6.2 15.5H8"/><path d="M11.5 13.2L14.8 10L11.5 6.8"/><path d="M14.8 10H8.5"/></svg>',
        ];

        return $icons[$key] ?? $icons['dashboard'];
    }
}

if (!function_exists('charShellItem')) {
    function charShellItem(string $url, string $label, string $icon, bool $active = false): string
    {
        return sprintf(
            '<a href="%s" class="char-nav-item %s"><span class="char-nav-item__icon">%s</span><span>%s</span></a>',
            e($url),
            $active ? 'is-active' : '',
            charShellIcon($icon),
            e($label)
        );
    }
}

if (!function_exists('renderCharityShellStart')) {
    function renderCharityShellStart(string $currentKey, string $title, string $subtitle = '', string $actionsHtml = ''): void
    {
        ?>
        <div class="char-shell" data-char-shell>
            <aside class="char-sidebar" id="char-sidebar" aria-label="Charity navigation">
                <div class="char-sidebar__brand">
                    <a href="<?= baseUrl('dashboard.php') ?>" class="char-brand-mark">
                        <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z"/><path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" opacity=".55"/></svg>
                        <div><strong>ResQFood</strong><span>Charity Console</span></div>
                    </a>
                </div>

                <nav class="char-nav-group">
                    <?= charShellItem(baseUrl('dashboard.php'), 'Dashboard', 'dashboard', $currentKey === 'dashboard') ?>
                    <?= charShellItem(baseUrl('modules/listings/browse.php'), 'Browse Food', 'browse', $currentKey === 'browse') ?>
                    <?= charShellItem(baseUrl('modules/reservations/my.php'), 'My Collections', 'reservations', $currentKey === 'reservations') ?>
                    <?= charShellItem(baseUrl('modules/reports/index.php'), 'Reports', 'reports', $currentKey === 'reports') ?>
                    <?= charShellItem(baseUrl('modules/profile/charity_profile.php'), 'Profile', 'profile', $currentKey === 'profile') ?>
                </nav>

                <div class="char-sidebar__foot">
                    <div class="char-user-mini">
                        <div class="char-user-mini__avatar"><?= e(mb_strtoupper(mb_substr(currentUserName(), 0, 1))) ?></div>
                        <div><strong><?= e(truncate(currentUserName(), 24)) ?></strong><span>Charity Account</span></div>
                    </div>
                    <form method="POST" action="<?= baseUrl('logout.php') ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="char-logout-btn">
                            <span class="char-nav-item__icon"><?= charShellIcon('logout') ?></span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="char-shell-backdrop" id="char-shell-backdrop" aria-hidden="true"></div>

            <section class="char-workspace">
                <header class="char-topbar">
                    <button type="button" class="char-sidebar-toggle" id="char-sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="char-sidebar">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="char-topbar__head">
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle !== ''): ?>
                            <p><?= e($subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($actionsHtml !== ''): ?>
                        <div class="char-topbar__actions"><?= $actionsHtml ?></div>
                    <?php endif; ?>
                </header>

                <div class="char-content">
        <?php
    }
}

if (!function_exists('renderCharityShellEnd')) {
    function renderCharityShellEnd(): void
    {
        echo '</div></section></div>';
    }
}

