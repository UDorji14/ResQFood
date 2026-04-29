<?php
/**
 * ResQFood — Incoming Reservations (Business View)
 * Shows all reservations across the business's listings.
 * Optionally filtered by a specific listing_id via ?listing=N.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['business', 'admin']);

// Auto-expire before showing the list
expireOldListings();

$uid          = currentUserId();
$statusFilter = sanitize($_GET['status']  ?? 'all');
$listingFilter = (int) ($_GET['listing'] ?? 0);

$reservations = getBusinessReservations($uid, $statusFilter);

// Apply optional per-listing filter in PHP to avoid an extra query parameter
if ($listingFilter > 0) {
    $reservations = array_filter($reservations, fn($r) => (int) $r['listing_id'] === $listingFilter);
}

// Count by status for tabs
$pdo     = db();
$cntStmt = $pdo->prepare('
    SELECT r.reservation_status, COUNT(*) AS cnt
    FROM   reservations r
    JOIN   food_listings fl ON fl.id = r.listing_id
    WHERE  fl.business_user_id = ?
    GROUP  BY r.reservation_status
');
$cntStmt->execute([$uid]);
$statusCounts = [];
foreach ($cntStmt->fetchAll() as $row) {
    $statusCounts[$row['reservation_status']] = (int) $row['cnt'];
}
$statusCounts['all'] = array_sum($statusCounts);

$tabs = [
    'all'       => 'All',
    'reserved'  => 'Pending Pickup',
    'collected' => 'Collected',
    'cancelled' => 'Cancelled',
    'expired'   => 'Expired',
    'no_show'   => 'No-show',
];

$pageTitle = 'Incoming Reservations';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb"><a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>Reservations</span></div>
            <h1>Incoming Reservations</h1>
            <p class="text-muted">
                Manage pickups across all your listings.
                <?php if ($listingFilter > 0): ?>
                    &mdash; <a href="<?= baseUrl('modules/reservations/index.php') ?>">Show all listings</a>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= baseUrl('modules/listings/index.php') ?>" class="btn btn-outline">My Listings</a>
    </div>
</div>

<!-- Status tabs -->
<nav class="tab-nav">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= e($key) ?><?= $listingFilter > 0 ? '&listing=' . $listingFilter : '' ?>"
       class="tab-nav__item <?= $statusFilter === $key ? 'active' : '' ?>">
        <?= e($label) ?>
        <?php if (!empty($statusCounts[$key])): ?>
            <span class="tab-nav__count"><?= $statusCounts[$key] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($reservations)): ?>
<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="56" fill="none">
            <circle cx="32" cy="32" r="24" stroke="#4a6741" stroke-width="2"/>
            <path d="M22 32l6 6 14-14" stroke="#4a6741" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p>No reservations found<?= $statusFilter !== 'all' ? ' for status "' . e($tabs[$statusFilter] ?? $statusFilter) . '"' : '' ?>.</p>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">Post a New Listing</a>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="table-wrapper">
        <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Listing</th>
                            <th>Reserved By</th>
                            <th>Role</th>
                            <th>Qty Reserved</th>
                            <th>Pickup Code</th>
                            <th>Status</th>
                            <th>Reserved At</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-size:.82rem">#<?= $r['id'] ?></td>
                            <td>
                                <a href="<?= baseUrl('modules/listings/view.php?id=' . $r['listing_id']) ?>"
                                   style="font-weight:700;color:var(--olive)">
                                    <?= e(truncate($r['title'], 35)) ?>
                                </a>
                                <div style="font-size:.75rem;color:var(--text-muted)">
                                    Total: <?= e(formatQty((float)$r['quantity']) . ' ' . $r['unit']) ?>
                                </div>
                            </td>
                    <td>
                        <div style="font-weight:600"><?= e($r['reserved_by_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--text-muted)"><?= e($r['reserved_by_email']) ?></div>
                    </td>
                    <td>
                        <span class="role-badge role-badge--<?= roleBadgeClass($r['reserver_role']) ?>">
                            <?= roleLabel($r['reserver_role']) ?>
                        </span>
                    </td>
                            <td style="font-weight:700;color:var(--olive-deep);white-space:nowrap">
                                <?= e(formatQty((float)($r['reserved_quantity'] ?? 1)) . ' ' . $r['unit']) ?>
                            </td>
                            <td><?= pickupCodeBadge($r['pickup_code']) ?></td>
                            <td>
                                <span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>">
                                    <?= statusLabel($r['reservation_status']) ?>
                                </span>
                            </td>
                            <td style="font-size:.82rem;color:var(--text-muted)">
                                <?= formatDate($r['reserved_at'], 'd M Y, H:i') ?>
                            </td>
                    <td style="text-align:right;white-space:nowrap">
                        <?php if ($r['reservation_status'] === 'reserved'): ?>
                        <a href="<?= baseUrl('modules/reservations/confirm_pickup.php?id=' . $r['id']) ?>"
                           class="btn btn-sm btn-primary">Confirm Pickup</a>
                        <?php endif; ?>
                        <?php if ($r['reservation_status'] === 'collected'): ?>
                        <span style="font-size:.78rem;color:var(--olive);font-weight:700">
                            ✓ Collected <?= $r['collected_at'] ? formatDate($r['collected_at'], 'd M') : '' ?>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
