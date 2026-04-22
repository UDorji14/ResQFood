<?php
/**
 * ResQFood — Reports (Admin view)
 * TODO: List, filter, and resolve user-submitted reports.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['admin']);

$pageTitle = 'Reports';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>Reports</h1>
    <p class="text-muted">Review and moderate user-submitted reports.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Reports module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
