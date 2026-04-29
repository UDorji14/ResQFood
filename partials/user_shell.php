<?php
/**
 * ResQFood General User Shell
 */

if (!function_exists('userShellIcon')) {
    function userShellIcon(string $key): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1.5"/><rect x="11" y="3" width="6" height="6" rx="1.5"/><rect x="3" y="11" width="6" height="6" rx="1.5"/><rect x="11" y="11" width="6" height="6" rx="1.5"/></svg>',
            'browse' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="M14 14L17 17"/></svg>',
            'reservations' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M6 8.2H14M6 11.2H11"/></svg>',
            'reports' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3L17 16H3L10 3Z"/><path d="M10 8V11.2M10 13.6H10.01"/></svg>',
            'account' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="6.5" r="3"/><path d="M3.5 16C4 12.8 6.4 11 10 11C13.6 11 16 12.8 16.5 16"/></svg>',
            'profile' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="3.5" width="13" height="13" rx="2"/><path d="M7 8H13M7 11H11"/></svg>',
            'logout' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 4.5H6.2C5.4 4.5 4.8 5.1 4.8 5.9V14.1C4.8 14.9 5.4 15.5 6.2 15.5H8"/><path d="M11.5 13.2L14.8 10L11.5 6.8"/><path d="M14.8 10H8.5"/></svg>',
        ];
        return $icons[$key] ?? $icons['dashboard'];
    }
}

if (!function_exists('userShellItem')) {
    function userShellItem(string $url, string $label, string $icon, bool $active = false): string
    {
        return sprintf(
            '<a href="%s" class="usr-nav-item %s"><span class="usr-nav-item__icon">%s</span><span>%s</span></a>',
            e($url),
            $active ? 'is-active' : '',
            userShellIcon($icon),
            e($label)
        );
    }
}

if (!function_exists('renderUserShellStart')) {
    function renderUserShellStart(string $currentKey, string $title, string $subtitle = '', string $actionsHtml = ''): void
    {
        ?>
        <div class="usr-shell" data-user-shell>
            <aside class="usr-sidebar" id="usr-sidebar" aria-label="General user navigation">
                <div class="usr-sidebar__brand">
                    <a href="<?= baseUrl('dashboard.php') ?>" class="usr-brand-mark">
                        <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z"/><path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" opacity=".55"/></svg>
                        <div><strong>ResQFood</strong><span>Member Portal</span></div>
                    </a>
                </div>
                <nav class="usr-nav-group">
                    <?= userShellItem(baseUrl('dashboard.php'), 'Dashboard', 'dashboard', $currentKey === 'dashboard') ?>
                    <?= userShellItem(baseUrl('modules/listings/browse.php'), 'Browse Food', 'browse', $currentKey === 'browse') ?>
                    <?= userShellItem(baseUrl('modules/reservations/my.php'), 'My Reservations', 'reservations', $currentKey === 'reservations') ?>
                    <?= userShellItem(baseUrl('modules/reports/index.php'), 'Reports', 'reports', $currentKey === 'reports') ?>
                    <?= userShellItem(baseUrl('modules/profile/index.php#account'), 'Account', 'account', $currentKey === 'account') ?>
                    <?= userShellItem(baseUrl('modules/profile/index.php#account'), 'Profile', 'profile', $currentKey === 'profile') ?>
                </nav>
                <div class="usr-sidebar__foot">
                    <div class="usr-user-mini">
                        <div class="usr-user-mini__avatar"><?= e(mb_strtoupper(mb_substr(currentUserName(), 0, 1))) ?></div>
                        <div><strong><?= e(truncate(currentUserName(), 24)) ?></strong><span>General User</span></div>
                    </div>
                    <form method="POST" action="<?= baseUrl('logout.php') ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="usr-logout-btn"><span class="usr-nav-item__icon"><?= userShellIcon('logout') ?></span><span>Logout</span></button>
                    </form>
                </div>
            </aside>
            <div class="usr-shell-backdrop" id="usr-shell-backdrop" aria-hidden="true"></div>
            <section class="usr-workspace">
                <header class="usr-topbar">
                    <button type="button" class="usr-sidebar-toggle" id="usr-sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="usr-sidebar"><span></span><span></span><span></span></button>
                    <div class="usr-topbar__head">
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle !== ''): ?><p><?= e($subtitle) ?></p><?php endif; ?>
                    </div>
                    <?php if ($actionsHtml !== ''): ?><div class="usr-topbar__actions"><?= $actionsHtml ?></div><?php endif; ?>
                </header>
                <div class="usr-content">
        <?php
    }
}

if (!function_exists('renderUserShellEnd')) {
    function renderUserShellEnd(): void
    {
        echo '</div></section></div>';
    }
}
