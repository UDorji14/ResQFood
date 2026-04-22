<?php
/**
 * ResQFood — My Reservations (General User / Charity view)
 * TODO: Show user's own reservations, allow cancellation.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['general_user', 'charity']);

$pageTitle = 'My Reservations';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>My Reservations</h1>
    <p class="text-muted">Track your food reservations and pickup codes.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">My reservations module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
