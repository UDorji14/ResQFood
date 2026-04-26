<?php
/**
 * ResQFood — My Reservations
 * Lists all reservations for the current general_user / charity user.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['general_user', 'charity']);

$uid          = currentUserId();
$statusFilter = sanitize($_GET['status'] ?? 'all');

// Expire any overdue listings first (keeps status consistent)
expireOldListings();

$reservations = getMyReservations($uid, $statusFilter);

// Count by status for tab badges
$pdo = db();
$cntStmt = $pdo->prepare('
    SELECT r.reservation_status, COUNT(*) AS cnt
    FROM   reservations r
    WHERE  r.reserved_by = ?
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
    'reserved'  => 'Active',
    'collected' => 'Collected',
    'cancelled' => 'Cancelled',
    'expired'   => 'Expired',
    'no_show'   => 'No-show',
];

$pageTitle = 'My Reservations';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb"><a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>My Reservations</span></div>
            <h1>My Reservations</h1>
            <p class="text-muted">Track your reserved food pickups.</p>
        </div>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Browse Listings</a>
    </div>
</div>

<!-- Status tabs -->
<nav class="tab-nav">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= e($key) ?>"
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
            <rect x="10" y="14" width="44" height="36" rx="4" stroke="#4a6741" stroke-width="2"/>
            <path d="M22 30h20M22 37h14" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M28 14v-3a2 2 0 012-2h4a2 2 0 012 2v3" stroke="#4a6741" stroke-width="1.5"/>
        </svg>
        <p>No reservations<?= $statusFilter !== 'all' ? ' with status "' . e($tabs[$statusFilter] ?? $statusFilter) . '"' : '' ?> yet.</p>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Browse Available Listings</a>
    </div>
</div>

<?php else: ?>
<div style="display:flex;flex-direction:column;gap:1rem">
    <?php foreach ($reservations as $r): ?>
    <div class="card" style="overflow:hidden">
        <div style="display:grid;grid-template-columns:1fr auto;align-items:start;gap:1rem;padding:1.25rem 1.5rem">

            <!-- Left: listing + business info -->
            <div>
                <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.5rem">
                    <span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>">
                        <?= statusLabel($r['reservation_status']) ?>
                    </span>
                    <?php if ($r['category']): ?>
                    <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted)"><?= e($r['category']) ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?= baseUrl('modules/listings/view.php?id=' . $r['listing_id']) ?>"
                   style="font-size:1.05rem;font-weight:700;color:var(--olive);text-decoration:none">
                    <?= e($r['title']) ?>
                </a>
                <div style="font-size:.82rem;color:var(--text-muted);margin-top:.3rem">
                    <?= e($r['business_name'] ?? '—') ?>
                    <?php if ($r['business_city']): ?> &middot; <?= e($r['business_city']) ?><?php endif; ?>
                </div>
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.65rem;font-size:.82rem;color:var(--text-muted)">
                    <span>
                        <strong style="color:var(--text-mid)">Quantity:</strong>
                        <?= e($r['quantity'] . ' ' . $r['unit']) ?>
                    </span>
                    <span>
                        <strong style="color:var(--text-mid)">Pickup:</strong>
                        <?= formatDate($r['pickup_start'], 'd M H:i') ?> – <?= formatDate($r['pickup_end'], 'H:i') ?>
                    </span>
                    <span>
                        <strong style="color:var(--text-mid)">Reserved:</strong>
                        <?= formatDate($r['reserved_at'], 'd M Y, H:i') ?>
                    </span>
                    <?php if ($r['collected_at']): ?>
                    <span>
                        <strong style="color:var(--text-mid)">Collected:</strong>
                        <?= formatDate($r['collected_at'], 'd M Y, H:i') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: pickup code + actions -->
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:.75rem;flex-shrink:0">
                <?php if ($r['reservation_status'] === 'reserved'): ?>
                <div style="text-align:center">
                    <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:.3rem">Pickup Code</div>
                    <?= pickupCodeBadge($r['pickup_code']) ?>
                </div>
                <?php endif; ?>

                <?php if (isCancellable($r['reservation_status'])): ?>
                <form method="POST" action="<?= baseUrl('modules/reservations/cancel.php') ?>"
                      onsubmit="return confirm('Cancel this reservation? The listing will become available again.')">
                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline"
                            style="color:var(--terra);border-color:rgba(181,96,74,.4)">
                        Cancel
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div>

        <!-- Address bar -->
        <?php if ($r['pickup_address']): ?>
        <div style="padding:.65rem 1.5rem;border-top:1px solid var(--line);background:rgba(248,244,234,.5);font-size:.8rem;color:var(--text-muted)">
            <svg viewBox="0 0 14 14" width="12" fill="none" style="vertical-align:middle;margin-right:.3rem"><circle cx="7" cy="6" r="3" stroke="currentColor" stroke-width="1.2"/><path d="M7 11c-3 0-5-2-5-5a5 5 0 0110 0c0 3-2 5-5 5z" stroke="currentColor" stroke-width="1.2"/></svg>
            <?= e($r['pickup_address']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
