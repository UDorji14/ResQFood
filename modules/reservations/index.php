<?php
/**
 * ResQFood — Reservations Management (Business view)
 * TODO: List reservations for the business's listings, allow collected/no-show updates.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['business', 'admin']);

$pageTitle = 'Reservations';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>Reservations</h1>
    <p class="text-muted">Manage incoming reservations for your listings.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Reservations module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
