<?php
/**
 * ResQFood — Admin Reports Moderation
 * Date-range, keyword and status filtering. Full moderation actions.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';

requireRole(['admin']);

// ── Filters from GET ────────────────────────────────────────────────────────
$statusFilter = sanitize($_GET['status']    ?? 'all');
$fromDate     = sanitize($_GET['from_date'] ?? '');
$toDate       = sanitize($_GET['to_date']   ?? '');
$keyword      = sanitize($_GET['q']         ?? '');
$sort         = in_array($_GET['sort'] ?? '', ['newest', 'oldest']) ? $_GET['sort'] : 'newest';

$perPage  = 20;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$filters = [
    'status'    => $statusFilter,
    'from_date' => $fromDate,
    'to_date'   => $toDate,
    'keyword'   => $keyword,
    'sort'      => $sort,
];

$reports    = adminGetReports($filters, $perPage, $offset);
$totalCount = adminCountReports($filters);
$totalPages = (int) ceil($totalCount / $perPage);

// Count by status for tabs (always based on ALL reports, not filtered)
$countRows = db()->query('SELECT report_status, COUNT(*) AS cnt FROM reports GROUP BY report_status')->fetchAll();
$statusCounts = ['all' => 0, 'open' => 0, 'under_review' => 0, 'resolved' => 0, 'dismissed' => 0];
foreach ($countRows as $r) {
    $statusCounts[$r['report_status']] = (int) $r['cnt'];
    $statusCounts['all']              += (int) $r['cnt'];
}

// Summary within current date/keyword filter (ignore status filter)
$summaryFilters = ['from_date' => $fromDate, 'to_date' => $toDate, 'keyword' => $keyword];
$sumOpen     = adminCountReports(array_merge($summaryFilters, ['status' => 'open']));
$sumReview   = adminCountReports(array_merge($summaryFilters, ['status' => 'under_review']));
$sumResolved = adminCountReports(array_merge($summaryFilters, ['status' => 'resolved']));
$sumAll      = adminCountReports($summaryFilters);

$tabs = [
    'all'          => 'All',
    'open'         => 'Open',
    'under_review' => 'Under Review',
    'resolved'     => 'Resolved',
    'dismissed'    => 'Dismissed',
];

$hasDateFilter = $fromDate !== '' || $toDate !== '';
$hasFilters    = $hasDateFilter || $keyword !== '' || $statusFilter !== 'all';

// ── Helper: build pagination URL ────────────────────────────────────────────
function reportsUrl(array $overrides = []): string {
    global $statusFilter, $fromDate, $toDate, $keyword, $sort, $page;
    $q = array_filter(array_merge([
        'status'    => $statusFilter,
        'from_date' => $fromDate,
        'to_date'   => $toDate,
        'q'         => $keyword,
        'sort'      => $sort !== 'newest' ? $sort : '',
        'page'      => $page > 1 ? $page : '',
    ], $overrides), fn($v) => $v !== '' && $v !== null);
    return baseUrl('modules/admin/reports.php') . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle = 'Reports Moderation';
require_once __DIR__ . '/../../partials/header.php';
?>

<?php
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart(
    'reports',
    'Reports Moderation',
    'Review, filter, and resolve user-submitted reports.'
);
?>

<div class="breadcrumb">
    <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a>
    <span>Reports</span>
</div>

<?php if ($statusCounts['open'] > 0): ?>
<div class="notice notice--danger" style="margin:0 0 1rem;padding:.6rem 1rem;font-size:.83rem">
    <svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M9 5.5v4m0 2.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <span><strong><?= $statusCounts['open'] ?></strong> open report<?= $statusCounts['open'] !== 1 ? 's' : '' ?> need<?= $statusCounts['open'] !== 1 ? '' : 's' ?> attention</span>
</div>
<?php endif; ?>

<!-- ── Summary strip ─────────────────────────────────────────────────────── -->
<?php if ($hasDateFilter || $sumAll > 0): ?>
<div class="reports-summary-strip">
    <div class="reports-summary-item">
        <div class="reports-summary-item__val"><?= $sumAll ?></div>
        <div class="reports-summary-item__lbl"><?= $hasDateFilter ? 'In range' : 'Total' ?></div>
    </div>
    <div class="reports-summary-item reports-summary-item--open">
        <div class="reports-summary-item__val"><?= $sumOpen ?></div>
        <div class="reports-summary-item__lbl">Open</div>
    </div>
    <div class="reports-summary-item reports-summary-item--review">
        <div class="reports-summary-item__val"><?= $sumReview ?></div>
        <div class="reports-summary-item__lbl">Under Review</div>
    </div>
    <div class="reports-summary-item reports-summary-item--resolved">
        <div class="reports-summary-item__val"><?= $sumResolved ?></div>
        <div class="reports-summary-item__lbl">Resolved</div>
    </div>
</div>
<?php endif; ?>

<!-- ── Filters ────────────────────────────────────────────────────────────── -->
<div class="admin-filter-panel">
    <form method="GET" action="">
        <div class="admin-filter-row">

            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control"
                       value="<?= e($keyword) ?>"
                       placeholder="Reason, details, reporter name…"
                       autocomplete="off">
            </div>

            <div class="form-group" style="max-width:175px">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <?php foreach ($tabs as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                        <?= e($label) ?><?= !empty($statusCounts[$key]) ? ' (' . $statusCounts[$key] . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="max-width:160px">
                <label class="form-label">From date</label>
                <input type="date" name="from_date" class="form-control"
                       value="<?= e($fromDate) ?>">
            </div>

            <div class="form-group" style="max-width:160px">
                <label class="form-label">To date</label>
                <input type="date" name="to_date" class="form-control"
                       value="<?= e($toDate) ?>">
            </div>

            <div class="form-group" style="max-width:145px">
                <label class="form-label">Sort</label>
                <select name="sort" class="form-control">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 18 18" width="13" fill="none" style="margin-right:.25rem"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.6"/><path d="M12.5 12.5L16 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Filter
                </button>
                <?php if ($hasFilters): ?>
                <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="btn btn-outline">
                    Clear
                </a>
                <?php endif; ?>
            </div>

        </div>
    </form>

    <?php if ($hasDateFilter): ?>
    <div class="admin-filter-active">
        <svg viewBox="0 0 14 14" width="12" fill="none"><rect x="1.5" y="1.5" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 1.5v2M9.5 1.5v2M1.5 5.5h11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Showing reports
        <?php if ($fromDate && $toDate): ?>
            from <strong><?= e(date('d M Y', strtotime($fromDate))) ?></strong>
            to <strong><?= e(date('d M Y', strtotime($toDate))) ?></strong>
        <?php elseif ($fromDate): ?>
            from <strong><?= e(date('d M Y', strtotime($fromDate))) ?></strong>
        <?php elseif ($toDate): ?>
            up to <strong><?= e(date('d M Y', strtotime($toDate))) ?></strong>
        <?php endif; ?>
        - <strong><?= $totalCount ?></strong> result<?= $totalCount !== 1 ? 's' : '' ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── Status tab nav ─────────────────────────────────────────────────────── -->
<nav class="tab-nav" style="margin-bottom:1.5rem">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= reportsUrl(['status' => $key, 'page' => '']) ?>"
       class="tab-nav__item <?= $statusFilter === $key ? 'active' : '' ?>">
        <?= e($label) ?>
        <?php if (!empty($statusCounts[$key])): ?>
            <span class="tab-nav__count<?= $key === 'open' ? ' tab-nav__count--urgent' : '' ?>">
                <?= $statusCounts[$key] ?>
            </span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<!-- ── Reports list ──────────────────────────────────────────────────────── -->
<?php if (empty($reports)): ?>

<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 80 80" width="64" fill="none">
            <circle cx="40" cy="40" r="30" stroke="#4a6741" stroke-width="2"/>
            <path d="M28 40l8 8 16-16" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.35rem">
            <?php if ($hasFilters): ?>
                No reports match these filters
            <?php elseif ($statusFilter === 'open'): ?>
                No open reports - platform is clear
            <?php else: ?>
                No <?= e($tabs[$statusFilter] ?? $statusFilter) ?> reports
            <?php endif; ?>
        </h3>
        <p style="color:var(--text-muted);font-size:.85rem;max-width:320px;margin:0 auto">
            <?php if ($hasFilters): ?>
                Try adjusting the date range, status, or search term.
            <?php elseif ($statusFilter === 'open'): ?>
                No reports currently need your attention.
            <?php else: ?>
                Reports with this status will appear here.
            <?php endif; ?>
        </p>
        <?php if ($hasFilters): ?>
        <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="btn btn-outline" style="margin-top:.75rem">
            Clear filters
        </a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<div class="report-feed">
    <?php foreach ($reports as $rep): ?>
    <div class="report-item report-item--<?= e($rep['report_status']) ?>">
        <div class="report-item__grid">

            <!-- ── Report details ──────────────────────────────── -->
            <div>
                <div class="report-item__id">
                    Report #<?= $rep['id'] ?>
                    &nbsp;&middot;&nbsp;
                    <span class="status-badge status-badge--<?= statusClass($rep['report_status']) ?>">
                        <?= statusLabel($rep['report_status']) ?>
                    </span>
                    &nbsp;&middot;&nbsp;
                    <span title="<?= e(formatDate($rep['created_at'], 'd M Y, H:i')) ?>" style="cursor:default">
                        <?= e(formatDate($rep['created_at'], 'd M Y, H:i')) ?>
                    </span>
                </div>

                <div class="report-item__reason"><?= e($rep['reason']) ?></div>

                <?php if ($rep['details']): ?>
                <div class="report-item__details"><?= nl2br(e($rep['details'])) ?></div>
                <?php endif; ?>

                <div class="report-item__links">
                    <span>
                        <strong>Reported by:</strong>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $rep['report_by']) ?>">
                            <?= e($rep['reporter_name']) ?>
                        </a>
                        (<?= roleLabel($rep['reporter_role'] ?? '') ?>)
                    </span>
                    <?php if ($rep['listing_id']): ?>
                    <span>
                        <strong>Listing:</strong>
                        <a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $rep['listing_id']) ?>">
                            <?= e(truncate($rep['listing_title'] ?? 'View listing', 32)) ?>
                        </a>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($rep['reported_user'])): ?>
                    <span>
                        <strong>Reported user:</strong>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $rep['reported_user']) ?>">
                            <?= e($rep['reported_user_name'] ?? 'View user') ?>
                        </a>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($rep['reviewer_name'])): ?>
                    <span><strong>Reviewed by:</strong> <?= e($rep['reviewer_name']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($rep['admin_note']): ?>
                <div class="report-item__admin-note">
                    <div class="report-item__note-label">Admin note</div>
                    <?= nl2br(e($rep['admin_note'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Action panel ────────────────────────────────── -->
            <div class="report-action">

                <!-- Quick status change buttons -->
                <div class="report-quick-actions">
                    <?php
                    $quickMap = [
                        'open'         => ['open', 'Open'],
                        'under_review' => ['amber', 'Under Review'],
                        'resolved'     => ['green', 'Resolved'],
                        'dismissed'    => ['default', 'Dismissed'],
                    ];
                    foreach ($quickMap as $st => [$cls, $lbl]): ?>
                    <?php if ($rep['report_status'] !== $st): ?>
                    <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action"        value="report_update">
                        <input type="hidden" name="report_id"     value="<?= $rep['id'] ?>">
                        <input type="hidden" name="report_status" value="<?= $st ?>">
                        <input type="hidden" name="redirect"      value="modules/admin/reports.php?<?= e(http_build_query(array_filter(['status'=>$statusFilter,'from_date'=>$fromDate,'to_date'=>$toDate,'q'=>$keyword,'sort'=>$sort!=='newest'?$sort:'','page'=>$page>1?$page:'']))) ?>">
                        <button type="submit" class="btn btn-xs btn-outline report-quick-btn">
                            → <?= e($lbl) ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action"    value="report_update">
                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                    <input type="hidden" name="redirect"  value="modules/admin/reports.php?<?= e(http_build_query(array_filter(['status'=>$statusFilter,'from_date'=>$fromDate,'to_date'=>$toDate,'q'=>$keyword,'sort'=>$sort!=='newest'?$sort:'','page'=>$page>1?$page:'']))) ?>">

                    <div class="form-group">
                        <label class="form-label">Update status</label>
                        <select name="report_status" class="form-control">
                            <?php foreach ($quickMap as $rs => [$cls, $lbl]): ?>
                                <option value="<?= $rs ?>" <?= $rep['report_status'] === $rs ? 'selected' : '' ?>>
                                    <?= e($lbl) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Admin note</label>
                        <textarea name="admin_note" class="form-control" rows="3"
                                  placeholder="Resolution note or internal comment…"
                                  style="font-size:.83rem;resize:vertical"><?= e($rep['admin_note'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:.65rem">
                        Save Update
                    </button>
                </form>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Pagination ──────────────────────────────────────────────────────────── -->
<?php if ($totalPages > 1): ?>
<nav class="pagination" style="margin-top:1.5rem">
    <?php if ($page > 1): ?>
        <a href="<?= reportsUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
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
            <a href="<?= reportsUrl(['page' => $p]) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span class="disabled">&hellip;</span>'; ?>
    <?php if ($page < $totalPages): ?>
        <a href="<?= reportsUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
    <?php else: ?>
        <span class="disabled">Next &rarr;</span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php renderAdminShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
