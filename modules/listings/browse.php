<?php
/**
 * ResQFood — Browse Available Listings (General Users & Charities)
 * TODO: Implement listing search, filter, and reserve actions.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['general_user', 'charity', 'admin']);

$pageTitle = 'Browse Food Listings';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>Browse Food Listings</h1>
    <p class="text-muted">Available surplus food near you.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Browse listings module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
