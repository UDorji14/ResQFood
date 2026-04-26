<?php
/**
 * ResQFood — Browse Available Listings
 * Accessible to general_user, charity, and admin.
 * Supports keyword, category, and city filtering with pagination.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['general_user', 'charity', 'admin']);

// Auto-expire stale listings before browsing
expireOldListings();

// ── Filters ───────────────────────────────────────────────────────────────
$filters = [
    'keyword'  => sanitize($_GET['q']    ?? ''),
    'category' => sanitize($_GET['cat']  ?? ''),
    'city'     => sanitize($_GET['city'] ?? ''),
];

$perPage = 12;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$listings    = browseListings($filters, $perPage, $offset);
$totalCount  = countBrowseListings($filters);
$totalPages  = (int) ceil($totalCount / $perPage);
$categories  = getListingCategories();

// ── Build URL helper for pagination ──────────────────────────────────────
function browseUrl(int $pg, array $f): string {
    $q = array_filter([
        'q'    => $f['keyword'],
        'cat'  => $f['category'],
        'city' => $f['city'],
        'page' => $pg > 1 ? $pg : null,
    ]);
    $qs = http_build_query($q);
    return baseUrl('modules/listings/browse.php') . ($qs ? '?' . $qs : '');
}

$pageTitle = 'Browse Food Listings';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Browse Food Listings</h1>
            <p class="text-muted">
                <?= $totalCount ?> listing<?= $totalCount !== 1 ? 's' : '' ?> available right now.
            </p>
        </div>
        <?php if (currentUserRole() === 'general_user' || currentUserRole() === 'charity'): ?>
            <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="btn btn-outline">My Reservations</a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Filter bar ── -->
<form method="GET" action="" class="filter-bar">
    <div class="form-group">
        <label class="form-label" for="q">Search</label>
        <input type="text" id="q" name="q" class="form-control"
               value="<?= e($filters['keyword']) ?>"
               placeholder="Title, category, business…">
    </div>
    <div class="form-group" style="max-width:200px">
        <label class="form-label" for="cat">Category</label>
        <select id="cat" name="cat" class="form-control">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $filters['category'] === $cat ? 'selected' : '' ?>>
                    <?= e($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="max-width:180px">
        <label class="form-label" for="city">City</label>
        <input type="text" id="city" name="city" class="form-control"
               value="<?= e($filters['city']) ?>"
               placeholder="e.g. London">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($filters['keyword'] || $filters['category'] || $filters['city']): ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<!-- ── Listings grid ── -->
<?php if (empty($listings)): ?>
<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="56" fill="none">
            <circle cx="28" cy="28" r="18" stroke="#4a6741" stroke-width="2"/>
            <path d="M42 42l12 12" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M22 28h12M28 22v12" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p>No listings match your search<?php if ($filters['keyword'] || $filters['category'] || $filters['city']): ?> — try adjusting the filters<?php endif; ?>.</p>
        <?php if ($filters['keyword'] || $filters['category'] || $filters['city']): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-outline">Show all listings</a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="listing-grid">
    <?php foreach ($listings as $l): ?>
    <a href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>" class="listing-card">

        <div class="listing-card__image">
            <?php if ($l['primary_image']): ?>
                <img src="<?= baseUrl(e($l['primary_image'])) ?>" alt="<?= e($l['title']) ?>" loading="lazy">
            <?php else: ?>
                <?= listingImageTag(null) ?>
            <?php endif; ?>
            <?php if ($l['category']): ?>
                <span class="listing-card__cat"><?= e($l['category']) ?></span>
            <?php endif; ?>
        </div>

        <div class="listing-card__body">
            <div class="listing-card__biz">
                <?= e($l['business_name'] ?? '—') ?>
                <?php if ($l['business_city']): ?> &middot; <?= e($l['business_city']) ?><?php endif; ?>
            </div>
            <div class="listing-card__title"><?= e($l['title']) ?></div>
            <div class="listing-card__meta">
                <span>
                    <svg viewBox="0 0 14 14" width="12" fill="none"><rect x="2" y="2" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M5 7h4M7 5v4" stroke="currentColor" stroke-width="1" stroke-linecap="round"/></svg>
                    <?= e($l['quantity'] . ' ' . $l['unit']) ?>
                </span>
                <?php if ($l['business_city']): ?>
                <span>
                    <svg viewBox="0 0 14 14" width="12" fill="none"><circle cx="7" cy="6" r="3" stroke="currentColor" stroke-width="1.2"/><path d="M7 11c-3 0-5-2-5-5a5 5 0 0110 0c0 3-2 5-5 5z" stroke="currentColor" stroke-width="1.2"/></svg>
                    <?= e($l['business_city']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="listing-card__footer">
            <span class="listing-card__time">
                <?= pickupTimeLabel($l['pickup_start'], $l['pickup_end']) ?>
            </span>
            <span class="btn btn-sm btn-primary" style="pointer-events:none">Reserve</span>
        </div>

    </a>
    <?php endforeach; ?>
</div>

<!-- ── Pagination ── -->
<?php if ($totalPages > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= browseUrl($page - 1, $filters) ?>">&larr; Prev</a>
    <?php else: ?>
        <span class="disabled">&larr; Prev</span>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    if ($start > 1) echo '<span>…</span>';
    for ($p = $start; $p <= $end; $p++):
    ?>
        <?php if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= browseUrl($p, $filters) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span>…</span>'; ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?= browseUrl($page + 1, $filters) ?>">Next &rarr;</a>
    <?php else: ?>
        <span class="disabled">Next &rarr;</span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
