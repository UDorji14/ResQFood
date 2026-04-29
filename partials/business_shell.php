<?php
/**
 * ResQFood Business Shell
 * Shared app-shell for business internal pages.
 */

if (!function_exists('businessShellIcon')) {
    function businessShellIcon(string $key): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1.5"/><rect x="11" y="3" width="6" height="6" rx="1.5"/><rect x="3" y="11" width="6" height="6" rx="1.5"/><rect x="11" y="11" width="6" height="6" rx="1.5"/></svg>',
            'listings' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M6.5 8H13.5M6.5 11H11.5"/></svg>',
            'post' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4V16M4 10H16"/></svg>',
            'reservations' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M10 6.5V10L12.8 12.2"/></svg>',
            'reports' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3L17 16H3L10 3Z"/><path d="M10 8V11.2M10 13.6H10.01"/></svg>',
            'impact' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 13.5L7.5 10L10.2 12.5L15.5 7.2"/><path d="M13.5 7.2H15.5V9.2"/></svg>',
            'account' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="6.5" r="3"/><path d="M3.5 16C4 12.8 6.4 11 10 11C13.6 11 16 12.8 16.5 16"/></svg>',
            'profile' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="3.5" width="13" height="13" rx="2"/><path d="M7 8H13M7 11H11"/></svg>',
            'logout' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 4.5H6.2C5.4 4.5 4.8 5.1 4.8 5.9V14.1C4.8 14.9 5.4 15.5 6.2 15.5H8"/><path d="M11.5 13.2L14.8 10L11.5 6.8"/><path d="M14.8 10H8.5"/></svg>',
        ];

        return $icons[$key] ?? $icons['dashboard'];
    }
}

if (!function_exists('businessShellItem')) {
    function businessShellItem(string $url, string $label, string $icon, bool $active = false): string
    {
        return sprintf(
            '<a href="%s" class="biz-nav-item %s"><span class="biz-nav-item__icon">%s</span><span>%s</span></a>',
            e($url),
            $active ? 'is-active' : '',
            businessShellIcon($icon),
            e($label)
        );
    }
}

if (!function_exists('renderBusinessShellStart')) {
    function renderBusinessShellStart(string $currentKey, string $title, string $subtitle = '', string $actionsHtml = ''): void
    {
        $current = $currentKey;
        ?>
        <div class="biz-shell" data-biz-shell>
            <aside class="biz-sidebar" id="biz-sidebar" aria-label="Business navigation">
                <div class="biz-sidebar__brand">
                    <a href="<?= baseUrl('dashboard.php') ?>" class="biz-brand-mark">
                        <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z"/><path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" opacity=".55"/></svg>
                        <div>
                            <strong>ResQFood</strong>
                            <span>Business Console</span>
                        </div>
                    </a>
                </div>

                <nav class="biz-nav-group">
                    <?= businessShellItem(baseUrl('dashboard.php'), 'Dashboard', 'dashboard', $current === 'dashboard') ?>
                    <?= businessShellItem(baseUrl('modules/listings/index.php'), 'My Listings', 'listings', $current === 'listings') ?>
                    <?= businessShellItem(baseUrl('modules/listings/create.php'), 'Post Food', 'post', $current === 'post') ?>
                    <?= businessShellItem(baseUrl('modules/reservations/index.php'), 'Reservations', 'reservations', $current === 'reservations') ?>
                    <?= businessShellItem(baseUrl('modules/reports/index.php'), 'Reports', 'reports', $current === 'reports') ?>
                    <?= businessShellItem(baseUrl('modules/dashboard/impact.php'), 'My Impact', 'impact', $current === 'impact') ?>
                    <?= businessShellItem(baseUrl('modules/profile/business_profile.php#account'), 'Account', 'account', $current === 'account') ?>
                    <?= businessShellItem(baseUrl('modules/profile/business_profile.php#details'), 'Profile', 'profile', $current === 'profile') ?>
                </nav>

                <div class="biz-sidebar__foot">
                    <div class="biz-user-mini">
                        <div class="biz-user-mini__avatar"><?= e(mb_strtoupper(mb_substr(currentUserName(), 0, 1))) ?></div>
                        <div>
                            <strong><?= e(truncate(currentUserName(), 24)) ?></strong>
                            <span>Business Account</span>
                        </div>
                    </div>
                    <form method="POST" action="<?= baseUrl('logout.php') ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="biz-logout-btn">
                            <span class="biz-nav-item__icon"><?= businessShellIcon('logout') ?></span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="biz-shell-backdrop" id="biz-shell-backdrop" aria-hidden="true"></div>

            <section class="biz-workspace">
                <header class="biz-topbar">
                    <button type="button" class="biz-sidebar-toggle" id="biz-sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="biz-sidebar">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="biz-topbar__head">
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle !== ''): ?>
                            <p><?= e($subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($actionsHtml !== ''): ?>
                        <div class="biz-topbar__actions"><?= $actionsHtml ?></div>
                    <?php endif; ?>
                </header>

                <div class="biz-content">
        <?php
    }
}

if (!function_exists('renderBusinessShellEnd')) {
    function renderBusinessShellEnd(): void
    {
        echo '</div></section></div>';
    }
}
