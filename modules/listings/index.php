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

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb"><a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>My Listings</span></div>
            <h1>My Listings</h1>
            <p class="text-muted">Manage your surplus food posts.</p>
        </div>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">+ New Listing</a>
    </div>
</div>

<!-- Status tabs -->
<nav class="tab-nav" aria-label="Filter by status">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= e($key) ?>"
       class="tab-nav__item <?= $statusFilter === $key ? 'active' : '' ?>">
        <?= e($label) ?>
        <?php if (isset($statusCounts[$key]) && $statusCounts[$key] > 0): ?>
            <span class="tab-nav__count"><?= $statusCounts[$key] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($listings)): ?>
<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="56" fill="none">
            <rect x="10" y="14" width="44" height="36" rx="4" stroke="#4a6741" stroke-width="2"/>
            <path d="M22 14v-3a2 2 0 012-2h16a2 2 0 012 2v3" stroke="#4a6741" stroke-width="2"/>
            <path d="M24 30h16M24 37h10" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p>No listings<?= $statusFilter !== 'all' ? ' with status "' . e($statusFilter) . '"' : '' ?> yet.</p>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">Post Your First Listing</a>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Pickup Window</th>
                    <th>Status</th>
                    <th>Reservations</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td>
                        <a href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>"
                           style="font-weight:700;color:var(--olive)">
                            <?= e(truncate($l['title'], 45)) ?>
                        </a>
                    </td>
                    <td><?= e($l['category'] ?? '—') ?></td>
                    <td><?= e($l['quantity'] . ' ' . $l['unit']) ?></td>
                    <td style="font-size:.82rem">
                        <?= formatDate($l['pickup_start'], 'd M, H:i') ?><br>
                        <span class="text-muted">– <?= formatDate($l['pickup_end'], 'H:i') ?></span>
                    </td>
                    <td>
                        <span class="status-badge status-badge--<?= statusClass($l['status']) ?>">
                            <?= statusLabel($l['status']) ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?php if ($l['active_reservations'] > 0): ?>
                            <a href="<?= baseUrl('modules/reservations/index.php?listing=' . $l['id']) ?>"
                               class="status-badge status-badge--amber">
                                <?= $l['active_reservations'] ?> active
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>"
                           class="btn btn-sm btn-outline" title="View">View</a>

                        <?php if (in_array($l['status'], ['available', 'reserved'])): ?>
                        <a href="<?= baseUrl('modules/listings/edit.php?id=' . $l['id']) ?>"
                           class="btn btn-sm btn-outline" title="Edit">Edit</a>
                        <?php endif; ?>

                        <?php if (in_array($l['status'], ['available', 'expired', 'cancelled'])): ?>
                        <form method="POST" action="<?= baseUrl('modules/listings/delete.php') ?>"
                              style="display:inline"
                              onsubmit="return confirm('Delete this listing? This cannot be undone.')">
                            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
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
