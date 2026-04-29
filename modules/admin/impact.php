<?php
/**
 * ResQFood — Admin: Impact Dashboard
 * TODO: Display aggregated meals saved, kg diverted, CO2 reduced.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['admin']);

$pageTitle = 'Impact Data';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>Impact Overview</h1>
    <p class="text-muted">Cumulative environmental and social metrics.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Impact data module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
