<?php
/**
 * ResQFood — App Header Partial
 * ─────────────────────────────
 * Responsive navigation with mobile hamburger drawer.
 * Set $pageTitle before including this file.
 * Requires: session.php, functions.php, auth.php, csrf.php loaded.
 */
$pageTitle = $pageTitle ?? 'ResQFood';
$_role     = currentUserRole() ?? 'guest';
$_php_self = $_SERVER['PHP_SELF'] ?? '';

/** Return 'active' if current PHP_SELF contains $segment */
function _navActive(string $segment): string {
    global $_php_self;
    return str_contains($_php_self, $segment) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ResQFood - Redistribute surplus food, feed communities.">
    <title><?= e($pageTitle) ?> - ResQFood</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/custom_dashboard.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
</head>
<body class="app-body" data-role="<?= e($_role) ?>">
<?= displayFlash('toast') ?>

<!-- ═══════════════════════════════════════════════════════════
     TOP NAVIGATION
═══════════════════════════════════════════════════════════ -->
<nav class="app-nav" id="app-nav" role="navigation" aria-label="Main navigation">
    <div class="app-nav__inner">

        <!-- Brand -->
        <a href="<?= baseUrl('dashboard.php') ?>" class="app-nav__brand" aria-label="ResQFood home">
            <svg viewBox="0 0 28 28" width="22" height="22" fill="none" aria-hidden="true">
                <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="#4a6741" opacity=".95"/>
                <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="#7a9a6a" opacity=".65"/>
                <circle cx="14" cy="11" r="2" fill="#fff" opacity=".5"/>
            </svg>
            ResQFood
        </a>

        <!-- Desktop nav links -->
        <?php if (isLoggedIn()): ?>
        <div class="app-nav__links" id="desktop-nav" role="list">

            <?php if ($_role === 'business'): ?>
                <a href="<?= baseUrl('modules/listings/index.php') ?>" class="<?= _navActive('/listings/index') ?>" role="listitem">My Listings</a>
                <a href="<?= baseUrl('modules/listings/create.php') ?>" class="<?= _navActive('/listings/create') ?>">+ Post Food</a>
                <a href="<?= baseUrl('modules/reservations/index.php') ?>" class="<?= _navActive('/reservations/index') ?>">Reservations</a>
                <a href="<?= baseUrl('modules/reports/index.php') ?>" class="<?= _navActive('/reports/index') ?>">Reports</a>
                <a href="<?= baseUrl('modules/dashboard/impact.php') ?>" class="<?= _navActive('/impact') ?>">My Impact</a>
                <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" class="<?= _navActive('business_profile') ?>">Profile</a>

            <?php elseif ($_role === 'general_user'): ?>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="<?= _navActive('/listings/browse') ?>">Browse Food</a>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="<?= _navActive('/reservations/my') ?>">My Reservations</a>
                <a href="<?= baseUrl('modules/reports/index.php') ?>" class="<?= _navActive('/reports/index') ?>">Reports</a>
                <a href="<?= baseUrl('modules/profile/index.php') ?>" class="<?= _navActive('/profile/index') ?>">Profile</a>

            <?php elseif ($_role === 'charity'): ?>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="<?= _navActive('/listings/browse') ?>">Browse Food</a>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="<?= _navActive('/reservations/my') ?>">My Collections</a>
                <a href="<?= baseUrl('modules/reports/index.php') ?>" class="<?= _navActive('/reports/index') ?>">Reports</a>
                <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" class="<?= _navActive('charity_profile') ?>">Profile</a>

            <?php elseif ($_role === 'admin'): ?>
                <a href="<?= baseUrl('modules/admin/dashboard.php') ?>" class="<?= _navActive('/admin/dashboard') ?>">Admin Home</a>
                <a href="<?= baseUrl('modules/admin/users.php') ?>" class="<?= _navActive('/admin/users') ?> <?= _navActive('view_user') ?>">Users</a>
                <a href="<?= baseUrl('modules/admin/listings.php') ?>" class="<?= _navActive('/admin/listings') ?> <?= _navActive('view_listing') ?>">Listings</a>
                <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="<?= _navActive('/admin/reports') ?>">Reports</a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <!-- User menu (desktop) + Hamburger -->
        <div class="app-nav__user">

            <?php if (isLoggedIn()): ?>
                <!-- Role badge desktop -->
                <span class="role-badge role-badge--<?= e(roleBadgeClass($_role)) ?>" aria-hidden="true">
                    <?= e(roleLabel($_role)) ?>
                </span>

                <!-- Username (desktop only via CSS) -->
                <a href="<?= baseUrl('dashboard.php') ?>" class="app-nav__username">
                    <?= e(truncate(currentUserName(), 22)) ?>
                </a>

                <!-- Logout -->
                <form method="POST" action="<?= baseUrl('logout.php') ?>" id="logout-form">
                    <?= csrfField() ?>
                    <button type="submit" class="btn-nav-logout">Log out</button>
                </form>
            <?php else: ?>
                <a href="<?= baseUrl('login.php') ?>"    class="btn btn-sm btn-outline">Log in</a>
                <a href="<?= baseUrl('register.php') ?>" class="btn btn-sm btn-primary">Sign up</a>
            <?php endif; ?>

            <!-- Hamburger (shown on mobile via CSS) -->
            <?php if (isLoggedIn()): ?>
            <button class="nav-hamburger" id="nav-hamburger"
                    aria-label="Toggle navigation menu"
                    aria-expanded="false"
                    aria-controls="nav-drawer">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <?php endif; ?>

        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════════════
     MOBILE DRAWER
═══════════════════════════════════════════════════════════ -->
<?php if (isLoggedIn()): ?>
<div class="nav-drawer" id="nav-drawer" role="dialog" aria-label="Navigation menu" aria-hidden="true">
    <div class="nav-drawer__links" role="list">

        <a href="<?= baseUrl('dashboard.php') ?>" class="<?= _navActive('dashboard') ?>" role="listitem">
            <svg viewBox="0 0 16 16" width="14" fill="none" aria-hidden="true"><rect x="2" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.3"/><rect x="9" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.3"/><rect x="9" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.3"/></svg>
            Dashboard
        </a>

        <?php if ($_role === 'business'): ?>
            <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a>
            <a href="<?= baseUrl('modules/listings/create.php') ?>">+ Post New Listing</a>
            <a href="<?= baseUrl('modules/reservations/index.php') ?>">Incoming Reservations</a>
            <a href="<?= baseUrl('modules/reports/index.php') ?>">Reports</a>
            <a href="<?= baseUrl('modules/dashboard/impact.php') ?>">My Impact</a>
            <a href="<?= baseUrl('modules/profile/business_profile.php') ?>">Business Profile</a>

        <?php elseif ($_role === 'general_user'): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Food</a>
            <a href="<?= baseUrl('modules/reservations/my.php') ?>">My Reservations</a>
            <a href="<?= baseUrl('modules/reports/index.php') ?>">Reports</a>
            <a href="<?= baseUrl('modules/profile/index.php') ?>">My Profile</a>

        <?php elseif ($_role === 'charity'): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Food</a>
            <a href="<?= baseUrl('modules/reservations/my.php') ?>">My Collections</a>
            <a href="<?= baseUrl('modules/reports/index.php') ?>">Reports</a>
            <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>">Charity Profile</a>

        <?php elseif ($_role === 'admin'): ?>
            <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin Dashboard</a>
            <a href="<?= baseUrl('modules/admin/users.php') ?>">User Management</a>
            <a href="<?= baseUrl('modules/admin/listings.php') ?>">Listing Oversight</a>
            <a href="<?= baseUrl('modules/admin/reports.php') ?>">Reports</a>
        <?php endif; ?>

    </div>

    <div class="nav-drawer__user">
        <span class="role-badge role-badge--<?= roleBadgeClass($_role) ?>"><?= roleLabel($_role) ?></span>
        <span style="font-size:.85rem;font-weight:700;color:var(--text-mid)"><?= e(currentUserName()) ?></span>
        <form method="POST" action="<?= baseUrl('logout.php') ?>">
            <?= csrfField() ?>
            <button type="submit" class="btn-nav-logout">Log out</button>
        </form>
    </div>
</div>
<div class="nav-backdrop" id="nav-backdrop" aria-hidden="true"></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     MAIN CONTENT START
═══════════════════════════════════════════════════════════ -->
<main class="app-main" id="main-content">
    <div class="app-container">
