<?php
/**
 * ResQFood Admin Shell
 * App-shell layout for internal admin pages.
 */

if (!function_exists('adminShellIcon')) {
    function adminShellIcon(string $key): string
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="14" height="14" rx="3"/><path d="M6 10h3v3H6zM11 7h3v6h-3z" fill="none"/></svg>',
            'users' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="8.2" cy="7.2" r="3"/><path d="M3.5 17c1.2-3.5 3.2-5.2 6.2-5.2s5 1.7 6.2 5.2"/></svg>',
            'listings' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="4" width="13" height="12" rx="2"/><path d="M6 8h8M6 11h5"/></svg>',
            'reports' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3L17 16H3L10 3Z"/><path d="M10 8v3.2"/><path d="M10 13.8h.01"/></svg>',
            'health' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3l6 2.5v5c0 4.5-3 8.5-6 9.5-3-1-6-5-6-9.5v-5L10 3z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M10 7v6m-3-3h6" stroke="currentColor" stroke-width="1.5"/></svg>',
            'settings' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M13.2 8.5l2.8-1-1.3-3.1-2.8 1a6.3 6.3 0 00-2-1.7V1h-3.4v2.8a6.3 6.3 0 00-2 1.7l-2.8-1-1.3 3.1 2.8 1a6.3 6.3 0 000 2.4l-2.8 1 1.3 3.1 2.8-1a6.3 6.3 0 002 1.7V19h3.4v-2.8a6.3 6.3 0 002-1.7l2.8 1 1.3-3.1-2.8-1a6.3 6.3 0 000-2.4zM10 12.8a2.8 2.8 0 110-5.6 2.8 2.8 0 010 5.6z" fill="currentColor"/></svg>',
            'logout' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 4.5H6.2C5.4 4.5 4.8 5.1 4.8 5.9V14.1C4.8 14.9 5.4 15.5 6.2 15.5H8"/><path d="M11.5 13.2L14.8 10L11.5 6.8"/><path d="M14.8 10H8.5"/></svg>',
        ];

        return $icons[$key] ?? $icons['dashboard'];
    }
}

if (!function_exists('adminShellItem')) {
    function adminShellItem(string $url, string $label, string $icon, bool $active = false): string
    {
        return sprintf(
            '<a href="%s" class="admin-nav-item %s"><span class="admin-nav-item__icon">%s</span><span>%s</span></a>',
            e($url),
            $active ? 'is-active' : '',
            adminShellIcon($icon),
            e($label)
        );
    }
}

if (!function_exists('renderAdminShellStart')) {
    function renderAdminShellStart(string $currentKey, string $title, string $subtitle = '', string $actionsHtml = ''): void
    {
        ?>
        <div class="admin-shell" data-admin-shell>
            <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
                <div class="admin-sidebar__brand">
                    <a href="<?= baseUrl('modules/admin/dashboard.php') ?>" class="admin-brand-mark">
                        <?php if (setting('logo_path')): ?>
                            <img src="<?= url(setting('logo_path')) ?>" alt="Logo" style="width:28px;height:28px;object-fit:contain">
                        <?php else: ?>
                            <svg viewBox="0 0 28 28" aria-hidden="true">
                                <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z"/>
                                <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" opacity=".55"/>
                            </svg>
                        <?php endif; ?>
                        <div>
                            <strong><?= e(setting('site_name', 'ResQFood')) ?></strong>
                            <span>Admin Console</span>
                        </div>
                    </a>
                </div>

                <nav class="admin-nav-group">
                    <?= adminShellItem(baseUrl('modules/admin/dashboard.php'), 'Dashboard', 'dashboard', $currentKey === 'dashboard') ?>
                    <?= adminShellItem(baseUrl('modules/admin/users.php'), 'Users', 'users', $currentKey === 'users') ?>
                    <?= adminShellItem(baseUrl('modules/admin/listings.php'), 'Listings', 'listings', $currentKey === 'listings') ?>
                    <?= adminShellItem(baseUrl('modules/admin/reports.php'), 'Reports', 'reports', $currentKey === 'reports') ?>
                    <?= adminShellItem(baseUrl('modules/admin/system_health.php'), 'System Health', 'health', $currentKey === 'health') ?>
                    <?= adminShellItem(baseUrl('modules/admin/settings.php'), 'Site Settings', 'settings', $currentKey === 'settings') ?>
                </nav>

                <div class="admin-sidebar__foot">
                    <div class="admin-user-mini">
                        <div class="admin-user-mini__avatar"><?= e(mb_strtoupper(mb_substr(currentUserName(), 0, 1))) ?></div>
                        <div>
                            <strong><?= e(truncate(currentUserName(), 24)) ?></strong>
                            <span>Administrator</span>
                        </div>
                    </div>

                    <form method="POST" action="<?= baseUrl('logout.php') ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="admin-logout-btn">
                            <span class="admin-nav-item__icon"><?= adminShellIcon('logout') ?></span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="admin-shell-backdrop" id="admin-shell-backdrop" aria-hidden="true"></div>

            <section class="admin-workspace">
                <header class="admin-topbar">
                    <button type="button" class="admin-sidebar-toggle" id="admin-sidebar-toggle"
                            aria-label="Toggle navigation" aria-expanded="false" aria-controls="admin-sidebar">
                        <span></span><span></span><span></span>
                    </button>

                    <div class="admin-topbar__head">
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle !== ''): ?>
                            <p><?= e($subtitle) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($actionsHtml !== ''): ?>
                        <div class="admin-topbar__actions"><?= $actionsHtml ?></div>
                    <?php endif; ?>
                </header>

                <div class="admin-content">
        <?php
    }
}

if (!function_exists('renderAdminShellEnd')) {
    function renderAdminShellEnd(): void
    {
        echo '</div></section></div>';
    }
}

