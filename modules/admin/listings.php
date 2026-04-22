<?php
/**
 * ResQFood — Admin: All Listings
 * TODO: Monitor, moderate, and expire listings platform-wide.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['admin']);

$pageTitle = 'All Listings';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>All Listings</h1>
    <p class="text-muted">Platform-wide food listing overview.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Admin listings module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
