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
$hasFilters = $filters['keyword'] || $filters['category'] || $filters['city'];
require_once __DIR__ . '/../../partials/header.php';
?>

<!-- ── Browse Hero ─────────────────────────────────────────────── -->
<div class="browse-hero">
    <div class="browse-hero__inner">
        <div>
            <h1>Browse Available Food</h1>
            <p>
                <?php if ($totalCount > 0): ?>
                    <strong style="color:rgba(255,255,255,.9)"><?= $totalCount ?></strong>
                    listing<?= $totalCount !== 1 ? 's' : '' ?> available right now
                    <?php if ($hasFilters): ?>&mdash; filtered<?php endif; ?>
                <?php else: ?>
                    No listings match your search right now
                <?php endif; ?>
            </p>
        </div>
        <?php if (in_array(currentUserRole(), ['general_user', 'charity'])): ?>
        <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="btn--hero-outline">
            <svg viewBox="0 0 18 18" width="14" fill="none" style="margin-right:.35rem"><rect x="2" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6 3V1m6 2V1M2 7h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            My Reservations
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Filter Strip ────────────────────────────────────────────── -->
<form method="GET" action="" id="filter-form">
    <div class="filter-strip">
        <div class="form-group">
            <label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" class="form-control"
                   value="<?= e($filters['keyword']) ?>"
                   placeholder="Food title, business, keyword…"
                   autocomplete="off">
        </div>
        <div class="form-group" style="max-width:185px">
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
        <div class="form-group" style="max-width:160px">
            <label class="form-label" for="city">City</label>
            <input type="text" id="city" name="city" class="form-control"
                   value="<?= e($filters['city']) ?>"
                   placeholder="e.g. London">
        </div>
        <div class="filter-strip__actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($hasFilters): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- ── Active filter chips ─────────────────────────────────────── -->
<?php if ($hasFilters): ?>
<div class="filter-chips">
    <?php if ($filters['keyword']): ?>
    <a href="<?= browseUrl(1, array_merge($filters, ['keyword'=>''])) ?>" class="filter-chip">
        <svg viewBox="0 0 12 12" width="10" fill="none"><path d="M2 6h8M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Search: <?= e(truncate($filters['keyword'], 24)) ?>
        <svg viewBox="0 0 10 10" width="9" fill="none"><path d="M2 2l6 6M8 2l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($filters['category']): ?>
    <a href="<?= browseUrl(1, array_merge($filters, ['category'=>''])) ?>" class="filter-chip">
        <?= e($filters['category']) ?>
        <svg viewBox="0 0 10 10" width="9" fill="none"><path d="M2 2l6 6M8 2l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($filters['city']): ?>
    <a href="<?= browseUrl(1, array_merge($filters, ['city'=>''])) ?>" class="filter-chip">
        <svg viewBox="0 0 12 12" width="10" fill="none"><circle cx="6" cy="5" r="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M6 10C3.5 10 1.5 8 1.5 5.5a4.5 4.5 0 019 0C10.5 8 8.5 10 6 10z" stroke="currentColor" stroke-width="1.2"/></svg>
        <?= e($filters['city']) ?>
        <svg viewBox="0 0 10 10" width="9" fill="none"><path d="M2 2l6 6M8 2l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Listings grid / empty state ─────────────────────────────── -->
<?php if (empty($listings)): ?>

<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 80 80" width="68" fill="none">
            <circle cx="34" cy="34" r="22" stroke="#4a6741" stroke-width="2"/>
            <path d="M52 52l18 18" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M28 34h12M34 28v12" stroke="#7a9a6a" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-dark);margin-bottom:.3rem">
            <?= $hasFilters ? 'No listings match your search' : 'No listings available right now' ?>
        </h3>
        <p style="color:var(--text-muted);font-size:.87rem">
            <?= $hasFilters ? 'Try adjusting your search or removing filters.' : 'Check back soon — new food is posted daily.' ?>
        </p>
        <?php if ($hasFilters): ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-outline">Show all listings</a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<div class="listing-grid">
    <?php foreach ($listings as $l): ?>
    <a href="<?= baseUrl('modules/listings/view.php?id=' . $l['id']) ?>" class="listing-card">

        <div class="listing-card__image">
            <?php if (!empty($l['primary_image'])): ?>
                <img src="<?= baseUrl(e($l['primary_image'])) ?>"
                     alt="<?= e($l['title']) ?>" loading="lazy">
            <?php else: ?>
                <div class="listing-placeholder">
                    <svg viewBox="0 0 56 56" width="48" fill="none" style="opacity:.3">
                        <path d="M10 38C10 28 18 14 28 10c10 4 18 18 18 28" stroke="#4a6741" stroke-width="2" stroke-linecap="round"/>
                        <ellipse cx="28" cy="38" rx="18" ry="6" stroke="#4a6741" stroke-width="1.5"/>
                        <path d="M20 30c2-4 5-7 8-8m8 0c2 1 4 4 6 8" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
            <?php endif; ?>
            <?php if ($l['category']): ?>
                <span class="listing-card__cat"><?= e($l['category']) ?></span>
            <?php endif; ?>
        </div>

        <div class="listing-card__body">
            <div class="listing-card__biz">
                <?= e($l['business_name'] ?? '—') ?>
                <?php if ($l['business_city']): ?>&nbsp;&middot;&nbsp;<?= e($l['business_city']) ?><?php endif; ?>
            </div>
            <div class="listing-card__title"><?= e($l['title']) ?></div>
            <div class="listing-card__meta">
                <span>
                    <svg viewBox="0 0 14 14" width="11" fill="none"><rect x="2" y="2" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M5 7h4M7 5v4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                    <?= e($l['quantity'] . ' ' . $l['unit']) ?>
                </span>
                <?php if ($l['business_city']): ?>
                <span>
                    <svg viewBox="0 0 14 14" width="11" fill="none"><circle cx="7" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M7 11C4 11 2.5 9 2.5 6.5a4.5 4.5 0 019 0C11.5 9 10 11 7 11z" stroke="currentColor" stroke-width="1.2"/></svg>
                    <?= e($l['business_city']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="listing-card__footer">
            <span class="listing-card__time">
                <svg viewBox="0 0 14 14" width="11" fill="none" style="opacity:.6;vertical-align:middle;margin-right:.2rem"><circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.2"/><path d="M7 4.5V7l2 1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                <?= pickupTimeLabel($l['pickup_start'], $l['pickup_end']) ?>
            </span>
            <span class="btn btn-sm btn-primary" style="pointer-events:none;font-size:.75rem;padding:.3rem .8rem">Reserve</span>
        </div>

    </a>
    <?php endforeach; ?>
</div>

<!-- ── Pagination ────────────────────────────────────────────────── -->
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
    if ($start > 1) echo '<span class="disabled">&hellip;</span>';
    for ($p = $start; $p <= $end; $p++):
    ?>
        <?php if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= browseUrl($p, $filters) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span class="disabled">&hellip;</span>'; ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?= browseUrl($page + 1, $filters) ?>">Next &rarr;</a>
    <?php else: ?>
        <span class="disabled">Next &rarr;</span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
