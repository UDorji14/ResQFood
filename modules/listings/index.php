<?php
/**
 * ResQFood — My Listings (Business Management Hub)
 * Shows all of the current business user's listings with status filtering.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['business', 'admin']);

// Auto-expire overdue listings before showing the list
expireOldListings();

$uid          = currentUserId();
$statusFilter = $_GET['status'] ?? 'all';
$listings     = getListingsByBusiness($uid, $statusFilter);

// Count by status for the tab badges
$pdo    = db();
$counts = $pdo->prepare('
    SELECT status, COUNT(*) AS cnt
    FROM   food_listings
    WHERE  business_user_id = ?
    GROUP  BY status
');
$counts->execute([$uid]);
$statusCounts = [];
foreach ($counts->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['cnt'];
}
$statusCounts['all'] = array_sum($statusCounts);

$tabs = [
    'all'       => 'All',
    'available' => 'Available',
    'reserved'  => 'Reserved',
    'collected' => 'Collected',
    'expired'   => 'Expired',
    'cancelled' => 'Cancelled',
];

$pageTitle = 'My Listings';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="breadcrumb">
    <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a>
    <span>My Listings</span>
</div>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>My Listings</h1>
            <p class="text-muted">Manage your surplus food posts and track their status.</p>
        </div>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">
            <svg viewBox="0 0 20 20" width="15" fill="none" style="margin-right:.35rem"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            New Listing
        </a>
    </div>
</div>

<!-- ── Status pill bar ─────────────────────────────────────────── -->
<div class="listings-stat-bar">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= e($key) ?>"
       class="stat-pill stat-pill--<?= e($key) ?> <?= $statusFilter === $key ? '' : '' ?>"
       style="<?= $statusFilter === $key ? 'box-shadow:0 0 0 2px currentColor;opacity:1' : 'opacity:.72' ?>">
        <span class="stat-pill__dot"></span>
        <?= e($label) ?>
        <?php if (isset($statusCounts[$key]) && $statusCounts[$key] > 0): ?>
            <strong>(<?= $statusCounts[$key] ?>)</strong>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($listings)): ?>

<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 80 80" width="68" fill="none">
            <rect x="12" y="18" width="56" height="46" rx="6" stroke="#4a6741" stroke-width="2"/>
            <path d="M28 18v-4a3 3 0 013-3h18a3 3 0 013 3v4" stroke="#4a6741" stroke-width="2"/>
            <circle cx="40" cy="43" r="10" stroke="#7a9a6a" stroke-width="1.5" stroke-dasharray="3 2"/>
            <path d="M36 43h8M40 39v8" stroke="#4a6741" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-dark);margin-bottom:.35rem">
            <?= $statusFilter !== 'all' ? 'No ' . e($tabs[$statusFilter] ?? $statusFilter) . ' listings' : 'No listings yet' ?>
        </h3>
        <p style="color:var(--text-muted);font-size:.88rem;max-width:320px;margin:0 auto .25rem">
            <?php if ($statusFilter !== 'all'): ?>
                Try a different filter, or post a new listing.
            <?php else: ?>
                Start sharing your surplus food with the community.
            <?php endif; ?>
        </p>
        <?php if ($statusFilter === 'all'): ?>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">Post Your First Listing</a>
        <?php else: ?>
        <a href="?status=all" class="btn btn-outline">Show all listings</a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<div class="card" style="overflow:hidden">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Qty</th>
                    <th>Pickup Window</th>
                    <th>Status</th>
                    <th style="text-align:center">Reservations</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <!-- Title + thumb -->
                    <td>
                        <div class="listing-row-title">
                            <div class="listing-thumb">
                                <?php if (!empty($l['primary_image'])): ?>
                                    <img src="<?= baseUrl(e($l['primary_image'])) ?>"
                                         alt="<?= e($l['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" width="20" fill="none" style="color:var(--olive);opacity:.35"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" stroke="currentColor" stroke-width="1.5"/><path d="M9 22V12h6v10" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a class="listing-row-title__name"
                                   href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>">
                                    <?= e(truncate($l['title'], 42)) ?>
                                </a>
                                <?php if ($l['category']): ?>
                                <span class="listing-row-title__sub"><?= e($l['category']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <!-- Quantity -->
                    <td style="white-space:nowrap;font-size:.85rem;font-weight:600">
                        <?= e($l['quantity'] . ' ' . $l['unit']) ?>
                    </td>

                    <!-- Pickup window -->
                    <td style="font-size:.8rem;line-height:1.45">
                        <span style="font-weight:600;color:var(--text-mid)"><?= formatDate($l['pickup_start'], 'd M, H:i') ?></span><br>
                        <span class="text-muted">&rarr; <?= formatDate($l['pickup_end'], 'd M, H:i') ?></span>
                    </td>

                    <!-- Status -->
                    <td>
                        <span class="status-badge status-badge--<?= statusClass($l['status']) ?>">
                            <?= statusLabel($l['status']) ?>
                        </span>
                    </td>

                    <!-- Reservations -->
                    <td style="text-align:center">
                        <?php if ($l['active_reservations'] > 0): ?>
                            <a href="<?= baseUrl('modules/reservations/index.php?listing=' . $l['id']) ?>"
                               class="status-badge status-badge--amber">
                                <?= $l['active_reservations'] ?> active
                            </a>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:.8rem">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>"
                           class="btn btn-xs btn-outline">View</a>

                        <?php if (in_array($l['status'], ['available', 'reserved'])): ?>
                        <a href="<?= baseUrl('modules/listings/edit.php?id=' . $l['id']) ?>"
                           class="btn btn-xs btn-outline">Edit</a>
                        <?php endif; ?>

                        <?php if (in_array($l['status'], ['available', 'expired', 'cancelled'])): ?>
                        <form method="POST" action="<?= baseUrl('modules/listings/delete.php') ?>"
                              style="display:inline"
                              data-confirm="Delete &ldquo;<?= e(addslashes(truncate($l['title'], 40))) ?>&rdquo;? This cannot be undone.">
                            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                        </form>
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
