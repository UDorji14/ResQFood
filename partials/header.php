<?php
/**
 * ResQFood — App Header Partial
 * ──────────────────────────────
 * Included at the top of every authenticated app page.
 * Expects $pageTitle to be set before inclusion.
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

<nav class="app-nav">
    <div class="app-nav__inner">

        <a href="<?= baseUrl('dashboard.php') ?>" class="app-nav__brand">
            <span class="brand-leaf" aria-hidden="true">
                <svg viewBox="0 0 28 28" width="24" fill="none">
                    <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="var(--olive)" opacity=".9"/>
                    <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="var(--sage)" opacity=".6"/>
                </svg>
            </span>
            <strong>ResQFood</strong>
        </a>

        <div class="app-nav__links">
            <?php if (isLoggedIn()): ?>
                <?php $role = currentUserRole(); ?>

                <?php if ($role === 'business'): ?>
                    <a href="<?= baseUrl('modules/listings/index.php') ?>"
                       class="<?= currentPage() === 'index.php' ? 'active' : '' ?>">My Listings</a>
                    <a href="<?= baseUrl('modules/reservations/index.php') ?>">Reservations</a>

                <?php elseif ($role === 'general_user'): ?>
                    <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Food</a>
                    <a href="<?= baseUrl('modules/reservations/my.php') ?>">My Reservations</a>

                <?php elseif ($role === 'charity'): ?>
                    <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Food</a>
                    <a href="<?= baseUrl('modules/reservations/my.php') ?>">Collections</a>

                <?php elseif ($role === 'admin'): ?>
                    <a href="<?= baseUrl('modules/admin/users.php') ?>">Users</a>
                    <a href="<?= baseUrl('modules/admin/listings.php') ?>">Listings</a>
                    <a href="<?= baseUrl('modules/reports/index.php') ?>">Reports</a>
                    <a href="<?= baseUrl('modules/admin/impact.php') ?>">Impact</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="app-nav__user">
            <span class="role-badge role-badge--<?= e(roleBadgeClass(currentUserRole())) ?>">
                <?= e(roleLabel(currentUserRole())) ?>
            </span>
            <span class="app-nav__username"><?= e(currentUserName()) ?></span>
            <a href="<?= baseUrl('logout.php') ?>" class="btn-nav-logout">Log out</a>
        </div>
        <?php endif; ?>

    </div>
</nav>

<main class="app-main">
    <div class="app-container">
        <?= displayFlash() ?>
