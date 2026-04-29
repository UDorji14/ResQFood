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
$activeReservations = array_filter($reservations, fn($r) => $r['reservation_status'] === 'reserved');
$pastReservations   = array_filter($reservations, fn($r) => $r['reservation_status'] !== 'reserved');
require_once __DIR__ . '/../../partials/header.php';
if (currentUserRole() === 'general_user') {
    require_once __DIR__ . '/../../partials/user_shell.php';
    $actions = '<a href="' . baseUrl('modules/listings/browse.php') . '" class="btn btn-primary">Browse Listings</a>';
    renderUserShellStart('reservations', 'My Reservations', 'Track active pickups, codes, and reservation history.', $actions);
} elseif (currentUserRole() === 'charity') {
    require_once __DIR__ . '/../../partials/charity_shell.php';
    $actions = '<a href="' . baseUrl('modules/listings/browse.php') . '" class="btn btn-primary">Browse Listings</a>'
        . '<a href="' . baseUrl('modules/reports/index.php') . '" class="btn btn-outline">Reports</a>';
    renderCharityShellStart('reservations', 'My Collections', 'Track your charity pickups, access codes, and review collection history.', $actions);
}
?>
<?php if (!in_array(currentUserRole(), ['general_user', 'charity'], true)): ?>
<div class="breadcrumb"><a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a><span>My Reservations</span></div>
<div class="page-head"><div class="page-head__top"><div><h1>My Reservations</h1><p class="text-muted">Track your food pickups and access your codes.</p></div></div></div>
<?php endif; ?>

<!-- Status filter tabs -->
<nav class="tab-nav" style="margin-bottom:1.5rem">
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
        <svg viewBox="0 0 80 80" width="68" fill="none">
            <rect x="12" y="16" width="56" height="48" rx="6" stroke="#4a6741" stroke-width="2"/>
            <path d="M28 16v-5a3 3 0 013-3h10a3 3 0 013 3v5" stroke="#4a6741" stroke-width="1.8"/>
            <path d="M26 38h28M26 48h20" stroke="#7a9a6a" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-dark);margin-bottom:.3rem">
            <?= $statusFilter !== 'all' ? 'No ' . e($tabs[$statusFilter] ?? $statusFilter) . ' reservations' : 'No reservations yet' ?>
        </h3>
        <p style="color:var(--text-muted);font-size:.87rem">
            <?= $statusFilter !== 'all' ? 'Try a different filter.' : 'Browse available listings and reserve your first pickup.' ?>
        </p>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Browse Food Listings</a>
    </div>
</div>

<?php else: ?>

<?php
// When showing "all", render active first with a section heading, then past
$showSections = ($statusFilter === 'all' && !empty($activeReservations) && !empty($pastReservations));
?>

<!-- ── Active Reservations ──────────────────────────────────── -->
<?php if ($statusFilter === 'all' && !empty($activeReservations)): ?>
<div class="section-heading" style="margin-bottom:1rem">
    <span class="section-heading__label">Active</span>
    <span class="status-badge status-badge--green" style="font-size:.72rem"><?= count($activeReservations) ?></span>
    <div class="section-heading__line"></div>
</div>
<?php endif; ?>

<?php
$toRender = ($statusFilter === 'all') ? $activeReservations : $reservations;
if ($statusFilter !== 'all') $toRender = $reservations;
?>

<?php if ($statusFilter !== 'all' && empty($activeReservations) && $statusFilter === 'reserved'): ?>
    <!-- Handled by the global empty state above -->
<?php endif; ?>

<?php if (!empty($toRender) || $statusFilter !== 'all'): ?>
<div class="res-feed">
    <?php foreach (($statusFilter === 'all' ? $activeReservations : $reservations) as $r): ?>
    <?php $isActive = $r['reservation_status'] === 'reserved'; ?>
    <div class="res-item <?= $isActive ? 'res-item--active' : 'res-item--' . e($r['reservation_status']) ?>">

        <div class="res-item__body">

            <!-- ── Left: info ────────────────────────────────── -->
            <div style="min-width:0">
                <div class="res-item__meta">
                    <span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>">
                        <?= statusLabel($r['reservation_status']) ?>
                    </span>
                    <?php if ($r['category']): ?>
                    <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light)">
                        <?= e($r['category']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <a class="res-item__title"
                   href="<?= baseUrl('modules/listings/view.php?id=' . $r['listing_id']) ?>">
                    <?= e($r['title']) ?>
                </a>
                <div class="res-item__biz">
                    <?= e($r['business_name'] ?? '-') ?>
                    <?php if ($r['business_city']): ?>&nbsp;&middot;&nbsp;<?= e($r['business_city']) ?><?php endif; ?>
                </div>
                <div class="res-item__facts">
                    <span>
                        <strong>Your qty:</strong>
                        <?= e(formatQty((float)($r['reserved_quantity'] ?? 1)) . ' ' . $r['unit']) ?>
                    </span>
                    <span><strong>Pickup:</strong> <?= formatDate($r['pickup_start'], 'd M, H:i') ?> &ndash; <?= formatDate($r['pickup_end'], 'H:i') ?></span>
                    <span><strong>Reserved:</strong> <?= formatDate($r['reserved_at'], 'd M Y') ?></span>
                    <?php if ($r['collected_at']): ?>
                    <span><strong>Collected:</strong> <?= formatDate($r['collected_at'], 'd M Y') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Right: code + action ──────────────────────── -->
            <div class="res-item__right">
                <?php if ($isActive): ?>
                <div class="pickup-code-card" data-copy title="Click to copy code">
                    <div class="pickup-code-card__label">Pickup Code</div>
                    <div class="pickup-code-card__code"><?= e($r['pickup_code']) ?></div>
                    <div class="pickup-code-card__hint">Tap to copy</div>
                </div>
                <?php elseif ($r['reservation_status'] === 'collected'): ?>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:700;color:#3d6b34;background:rgba(74,103,65,.08);padding:.4rem .75rem;border-radius:var(--r-pill);border:1px solid rgba(74,103,65,.2)">
                    <svg viewBox="0 0 14 14" width="12" fill="none"><path d="M2.5 7l3 3 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Collected
                </div>
                <?php endif; ?>

                <?php if (isCancellable($r['reservation_status'])): ?>
                <form method="POST" action="<?= baseUrl('modules/reservations/cancel.php') ?>"
                      data-confirm="Cancel this reservation? The listing will become available again.">
                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline"
                            style="color:var(--terra);border-color:rgba(181,96,74,.35)">
                        Cancel
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div>

        <!-- Address bar -->
        <?php if (!empty($r['pickup_address'])): ?>
        <div class="res-item__address">
            <svg viewBox="0 0 14 14" width="11" fill="none"><circle cx="7" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M7 11C4 11 2.5 9 2.5 6.5a4.5 4.5 0 019 0C11.5 9 10 11 7 11z" stroke="currentColor" stroke-width="1.2"/></svg>
            <?= e($r['pickup_address']) ?>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Past Reservations ────────────────────────────────────── -->
<?php if ($showSections && !empty($pastReservations)): ?>
<div class="section-heading" style="margin-top:2rem;margin-bottom:1rem">
    <span class="section-heading__label">History</span>
    <div class="section-heading__line"></div>
</div>
<div class="res-feed">
    <?php foreach ($pastReservations as $r): ?>
    <div class="res-item res-item--<?= e($r['reservation_status']) ?>">
        <div class="res-item__body">
            <div style="min-width:0">
                <div class="res-item__meta">
                    <span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>">
                        <?= statusLabel($r['reservation_status']) ?>
                    </span>
                    <?php if ($r['category']): ?>
                    <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light)"><?= e($r['category']) ?></span>
                    <?php endif; ?>
                </div>
                <a class="res-item__title"
                   href="<?= baseUrl('modules/listings/view.php?id=' . $r['listing_id']) ?>">
                    <?= e($r['title']) ?>
                </a>
                <div class="res-item__biz">
                    <?= e($r['business_name'] ?? '-') ?>
                    <?php if ($r['business_city']): ?>&nbsp;&middot;&nbsp;<?= e($r['business_city']) ?><?php endif; ?>
                </div>
                <div class="res-item__facts">
                    <span>
                        <strong>Your qty:</strong>
                        <?= e(formatQty((float)($r['reserved_quantity'] ?? 1)) . ' ' . $r['unit']) ?>
                    </span>
                    <span><strong>Reserved:</strong> <?= formatDate($r['reserved_at'], 'd M Y') ?></span>
                    <?php if ($r['collected_at']): ?>
                    <span><strong>Collected:</strong> <?= formatDate($r['collected_at'], 'd M Y') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="res-item__right">
                <?php if ($r['reservation_status'] === 'collected'): ?>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:700;color:#3d6b34;background:rgba(74,103,65,.08);padding:.4rem .75rem;border-radius:var(--r-pill);border:1px solid rgba(74,103,65,.2)">
                    <svg viewBox="0 0 14 14" width="12" fill="none"><path d="M2.5 7l3 3 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Collected
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
if (currentUserRole() === 'general_user') renderUserShellEnd();
if (currentUserRole() === 'charity') renderCharityShellEnd();
?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
