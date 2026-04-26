<?php
/**
 * ResQFood — App Header Partial
 * ──────────────────────────────
 * Included at the top of every authenticated app page.
 * Set $pageTitle before including this file.
 *
 * Requires: session.php, functions.php, auth.php already loaded.
 */
$pageTitle = $pageTitle ?? 'ResQFood';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — ResQFood</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
</head>
<body class="app-body" data-role="<?= e(currentUserRole() ?? 'guest') ?>">

<nav class="app-nav" id="app-nav">
    <div class="app-nav__inner">

        <!-- Brand -->
        <a href="<?= baseUrl('dashboard.php') ?>" class="app-nav__brand">
            <svg viewBox="0 0 28 28" width="22" fill="none" aria-hidden="true">
                <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="var(--olive)" opacity=".9"/>
                <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="var(--sage)"  opacity=".6"/>
            </svg>
            <strong>ResQFood</strong>
        </a>

        <!-- Role-based nav links -->
        <?php if (isLoggedIn()): $role = currentUserRole(); ?>
        <div class="app-nav__links" id="nav-links">

            <?php if ($role === 'business'): ?>
                <a href="<?= baseUrl('modules/listings/index.php') ?>"
                   class="<?= currentPage() === 'index.php' && strpos($_SERVER['PHP_SELF'], 'listings') !== false ? 'active' : '' ?>">
                   My Listings
                </a>
                <a href="<?= baseUrl('modules/listings/create.php') ?>">+ Post Food</a>
                <a href="<?= baseUrl('modules/reservations/index.php') ?>">Reservations</a>
                <a href="<?= baseUrl('modules/profile/business_profile.php') ?>"
                   class="<?= currentPage() === 'business_profile.php' ? 'active' : '' ?>">
                   My Profile
                </a>

            <?php elseif ($role === 'general_user'): ?>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>"
                   class="<?= currentPage() === 'browse.php' ? 'active' : '' ?>">
                   Browse Food
                </a>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>"
                   class="<?= currentPage() === 'my.php' ? 'active' : '' ?>">
                   My Reservations
                </a>
                <a href="<?= baseUrl('modules/profile/index.php') ?>"
                   class="<?= currentPage() === 'index.php' && strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : '' ?>">
                   My Profile
                </a>

            <?php elseif ($role === 'charity'): ?>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>"
                   class="<?= currentPage() === 'browse.php' ? 'active' : '' ?>">
                   Browse Food
                </a>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>"
                   class="<?= currentPage() === 'my.php' ? 'active' : '' ?>">
                   My Collections
                </a>
                <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>"
                   class="<?= currentPage() === 'charity_profile.php' ? 'active' : '' ?>">
                   My Profile
                </a>

            <?php elseif ($role === 'admin'): ?>
                <a href="<?= baseUrl('modules/admin/dashboard.php') ?>"
                   class="<?= currentPage() === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?>">
                   Admin Home
                </a>
                <a href="<?= baseUrl('modules/admin/users.php') ?>"
                   class="<?= currentPage() === 'users.php' ? 'active' : '' ?>">
                   Users
                </a>
                <a href="<?= baseUrl('modules/admin/listings.php') ?>"
                   class="<?= currentPage() === 'listings.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?>">
                   Listings
                </a>
                <a href="<?= baseUrl('modules/admin/reports.php') ?>"
                   class="<?= currentPage() === 'reports.php' ? 'active' : '' ?>">
                   Reports
                </a>
                <a href="<?= baseUrl('modules/dashboard/impact.php') ?>"
                   class="<?= currentPage() === 'impact.php' ? 'active' : '' ?>">
                   Impact
                </a>
            <?php endif; ?>
            <!-- Impact link for business users -->
            <?php if ($role === 'business'): ?>
                <a href="<?= baseUrl('modules/dashboard/impact.php') ?>"
                   class="<?= currentPage() === 'impact.php' ? 'active' : '' ?>">
                   My Impact
                </a>
            <?php endif; ?>

        </div><!-- /.app-nav__links -->
        <?php endif; ?>

        <!-- User menu -->
        <?php if (isLoggedIn()): ?>
        <div class="app-nav__user">
            <span class="role-badge role-badge--<?= e(roleBadgeClass(currentUserRole())) ?>">
                <?= e(roleLabel(currentUserRole())) ?>
            </span>
            <a href="<?= baseUrl('dashboard.php') ?>" class="app-nav__username"
               style="text-decoration:none;color:var(--text-mid)">
               <?= e(currentUserName()) ?>
            </a>
            <!-- Logout (POST form for CSRF safety, styled as link) -->
            <form method="POST" action="<?= baseUrl('logout.php') ?>" style="display:inline">
                <?= csrfField() ?>
                <button type="submit" class="btn-nav-logout">Log out</button>
            </form>
        </div>
        <?php else: ?>
        <div class="app-nav__user">
            <a href="<?= baseUrl('login.php') ?>"    class="btn btn-sm btn-outline">Log in</a>
            <a href="<?= baseUrl('register.php') ?>" class="btn btn-sm btn-primary">Sign up</a>
        </div>
        <?php endif; ?>

    </div><!-- /.app-nav__inner -->
</nav>

<main class="app-main">
    <div class="app-container">
        <?= displayFlash() ?>
