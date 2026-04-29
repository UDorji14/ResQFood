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

<div class="breadcrumb">
    <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a>
    <span>Listings</span>
</div>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Listing Oversight</h1>
            <p class="text-muted"><strong style="color:var(--text-mid)"><?= number_format($total) ?></strong> total listings on the platform.</p>
        </div>
    </div>
</div>

<!-- Status tabs -->
<nav class="tab-nav" style="margin-bottom:1rem">
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

<!-- Search -->
<form method="GET" action="">
    <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <div class="admin-filter">
        <div class="form-group">
            <label class="form-label" for="q">Search listings</label>
            <input type="text" id="q" name="q" class="form-control"
                   value="<?= e($filters['keyword']) ?>" placeholder="Title or business name…" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($filters['keyword']): ?>
            <a href="?status=<?= e($filters['status']) ?>" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card" style="overflow:hidden">
    <?php if (empty($listings)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 80 80" width="64" fill="none">
            <rect x="12" y="18" width="56" height="46" rx="6" stroke="#4a6741" stroke-width="2"/>
            <path d="M28 40h24M28 50h16" stroke="#7a9a6a" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.3rem">No listings found</h3>
        <p style="color:var(--text-muted);font-size:.85rem">Try a different status tab or clear the search.</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Business</th>
                    <th>Status</th>
                    <th>Qty</th>
                    <th>Pickup Window</th>
                    <th style="text-align:center">Res.</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td>
                        <div class="listing-row-title">
                            <div class="listing-thumb">
                                <?php if (!empty($l['primary_image'])): ?>
                                    <img src="<?= baseUrl(e($l['primary_image'])) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" width="18" fill="none" style="color:var(--olive);opacity:.3"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" stroke="currentColor" stroke-width="1.5"/></svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a class="listing-row-title__name"
                                   href="<?= baseUrl('modules/admin/view_listing.php?id=' . $l['id']) ?>">
                                    <?= e(truncate($l['title'], 38)) ?>
                                </a>
                                <?php if ($l['category']): ?>
                                <span class="listing-row-title__sub"><?= e($l['category']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $l['business_user_id']) ?>"
                           style="font-size:.84rem;font-weight:600;color:var(--olive);text-decoration:none">
                            <?= e(truncate($l['business_name'] ?? $l['owner_name'], 24)) ?>
                        </a>
                        <?php if ($l['business_city']): ?>
                        <div style="font-size:.73rem;color:var(--text-muted)"><?= e($l['business_city']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-badge--<?= statusClass($l['status']) ?>">
                            <?= statusLabel($l['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:.83rem;font-weight:600;white-space:nowrap">
                        <?= e($l['quantity'] . ' ' . $l['unit']) ?>
                    </td>
                    <td style="font-size:.79rem;color:var(--text-muted);line-height:1.45">
                        <?= formatDate($l['pickup_start'], 'd M, H:i') ?><br>
                        <span>&rarr; <?= formatDate($l['pickup_end'], 'd M, H:i') ?></span>
                    </td>
                    <td style="text-align:center;font-size:.85rem;font-weight:600">
                        <?= number_format($l['total_reservations']) ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $l['id']) ?>"
                           class="btn btn-xs btn-outline">View</a>
                        <?php if (in_array($l['status'], ['available', 'reserved'])): ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>"
                              style="display:inline"
                              data-confirm="Cancel this listing? Active reservations will also be cancelled.">
                            <input type="hidden" name="action"     value="listing_cancel">
                            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"   value="modules/admin/listings.php">
                            <button class="btn btn-xs btn-danger" type="submit">Cancel</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-pagination">
        <nav class="pagination">
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++):
                $url = '?status=' . urlencode($filters['status']) . '&q=' . urlencode($filters['keyword']) . '&page=' . $p;
            ?>
                <?php if ($p === $page): ?><span class="current"><?= $p ?></span><?php else: ?><a href="<?= $url ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
