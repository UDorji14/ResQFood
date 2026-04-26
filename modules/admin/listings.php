<?php
/**
 * ResQFood — Admin Listing Oversight
 * View and manage all listings across all businesses.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['admin']);

expireOldListings();

$perPage = 20;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$filters = [
    'status'  => sanitize($_GET['status']  ?? ''),
    'keyword' => sanitize($_GET['q']       ?? ''),
];

$listings   = adminGetListings($filters, $perPage, $offset);
$total      = (int) db()->query('SELECT COUNT(*) FROM food_listings')->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

// Status counts for tabs
$statusCountRows = db()->query('SELECT status, COUNT(*) AS cnt FROM food_listings GROUP BY status')->fetchAll();
$statusCounts    = ['all' => $total];
foreach ($statusCountRows as $r) $statusCounts[$r['status']] = (int) $r['cnt'];

$tabs = ['all' => 'All', 'available' => 'Available', 'reserved' => 'Reserved',
         'collected' => 'Collected', 'expired' => 'Expired', 'cancelled' => 'Cancelled'];

$currentStatus = $filters['status'] ?: 'all';

$pageTitle = 'Listing Oversight';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a> / <span>Listings</span>
    </div>
    <h1>Listing Oversight</h1>
    <p class="text-muted"><?= number_format($total) ?> total listings on the platform.</p>
</div>

<nav class="tab-nav">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= $key ?><?= $filters['keyword'] ? '&q=' . urlencode($filters['keyword']) : '' ?>"
       class="tab-nav__item <?= $currentStatus === $key ? 'active' : '' ?>">
        <?= $label ?>
        <?php if (!empty($statusCounts[$key])): ?>
            <span class="tab-nav__count"><?= $statusCounts[$key] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<form method="GET" action="" class="filter-bar">
    <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <div class="form-group" style="flex:1">
        <label class="form-label" for="q">Search</label>
        <input type="text" id="q" name="q" class="form-control"
               value="<?= e($filters['keyword']) ?>" placeholder="Title or business name…">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($filters['keyword']): ?>
        <a href="?status=<?= e($filters['status']) ?>" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (empty($listings)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="48" fill="none"><rect x="10" y="14" width="44" height="36" rx="4" stroke="#4a6741" stroke-width="2"/><path d="M24 30h16M24 37h10" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/></svg>
        <p>No listings found.</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Business</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Qty</th>
                    <th>Pickup window</th>
                    <th>Reservations</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td>
                        <a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $l['id']) ?>"
                           style="font-weight:700;color:var(--olive)"><?= e(truncate($l['title'], 40)) ?></a>
                        <div style="font-size:.73rem;color:var(--text-muted)"><?= formatDate($l['created_at'], 'd M Y') ?></div>
                    </td>
                    <td>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $l['business_user_id']) ?>"
                           style="font-size:.85rem;font-weight:600;color:var(--text)">
                            <?= e($l['business_name'] ?? $l['owner_name']) ?>
                        </a>
                        <?php if ($l['business_city']): ?>
                            <div style="font-size:.73rem;color:var(--text-muted)"><?= e($l['business_city']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.83rem"><?= e($l['category'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge status-badge--<?= statusClass($l['status']) ?>">
                            <?= statusLabel($l['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:.83rem"><?= e($l['quantity'] . ' ' . $l['unit']) ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted)">
                        <?= formatDate($l['pickup_start'], 'd M, H:i') ?><br>→ <?= formatDate($l['pickup_end'], 'H:i') ?>
                    </td>
                    <td style="text-align:center;font-size:.85rem">
                        <?= number_format($l['total_reservations']) ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $l['id']) ?>"
                           class="btn btn-sm btn-outline">View</a>
                        <?php if (in_array($l['status'], ['available', 'reserved'])): ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline"
                              onsubmit="return confirm('Cancel this listing?')">
                            <input type="hidden" name="action"     value="listing_cancel">
                            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"   value="modules/admin/listings.php">
                            <button class="btn btn-sm btn-danger" type="submit">Cancel</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--line)">
        <nav class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php $url = '?status=' . urlencode($filters['status']) . '&q=' . urlencode($filters['keyword']) . '&page=' . $p; ?>
                <?php if ($p === $page): ?><span class="current"><?= $p ?></span>
                <?php else: ?><a href="<?= $url ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
