<?php
/**
 * ResQFood — My Listings (Business)
 * TODO: Implement listing management for business accounts.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['business', 'admin']);

$pageTitle = 'My Listings';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <div class="page-head__top">
        <h1>My Listings</h1>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">+ New Listing</a>
    </div>
    <p class="text-muted">Manage your surplus food listings.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Listings module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
