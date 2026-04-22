<?php
/**
 * ResQFood — Admin: User Management
 * TODO: List, filter, suspend, and verify users.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(['admin']);

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <h1>Manage Users</h1>
    <p class="text-muted">View and moderate all platform accounts.</p>
</div>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">User management module coming soon.</p>
</div></div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
