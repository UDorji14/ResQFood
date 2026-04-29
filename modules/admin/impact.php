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

<?php
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart(
    'impact',
    'Impact Overview',
    'Cumulative environmental and social metrics.'
);
?>
<div class="card"><div class="card-body text-center" style="padding:3rem">
    <p class="text-muted">Impact data module coming soon.</p>
</div></div>
<?php renderAdminShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
