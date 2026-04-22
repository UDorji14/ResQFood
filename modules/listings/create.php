<?php
/**
 * ResQFood — Create Food Listing (Business only)
 * TODO: Implement listing creation form with image upload.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['business']);

$pageTitle = 'New Listing';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> /
        <a href="<?= baseUrl('modules/listings/index.php') ?>">Listings</a> /
        <span>Create</span>
    </div>
    <h1>Post a New Listing</h1>
    <p class="text-muted">Share surplus food for your community.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Create listing form coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
