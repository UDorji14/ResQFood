<?php
/**
 * ResQFood — Admin Reports Moderation
 * Review and resolve platform reports.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';

requireRole(['admin']);

$statusFilter = sanitize($_GET['status'] ?? 'open');
$perPage      = 20;
$page         = max(1, (int) ($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$reports = adminGetReports($statusFilter, $perPage, $offset);

// Count by status for tabs
$countRows = db()->query('SELECT report_status, COUNT(*) AS cnt FROM reports GROUP BY report_status')->fetchAll();
$statusCounts = ['all' => 0];
foreach ($countRows as $r) {
    $statusCounts[$r['report_status']] = (int) $r['cnt'];
    $statusCounts['all']              += (int) $r['cnt'];
}

$tabs = [
    'open'         => 'Open',
    'under_review' => 'Under Review',
    'resolved'     => 'Resolved',
    'dismissed'    => 'Dismissed',
    'all'          => 'All',
];

$pageTitle = 'Reports Moderation';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb"><a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a> / <span>Reports</span></div>
            <h1>Reports Moderation</h1>
            <p class="text-muted">Review user-submitted reports and take action.</p>
        </div>
    </div>
</div>

<nav class="tab-nav">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?status=<?= e($key) ?>"
       class="tab-nav__item <?= $statusFilter === $key ? 'active' : '' ?>">
        <?= e($label) ?>
        <?php if (!empty($statusCounts[$key])): ?>
            <span class="tab-nav__count <?= $key === 'open' ? '' : '' ?>"><?= $statusCounts[$key] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($reports)): ?>
<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="48" fill="none">
            <circle cx="32" cy="32" r="24" stroke="#4a6741" stroke-width="2"/>
            <path d="M22 32l6 6 14-14" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p>No reports with status "<?= e($tabs[$statusFilter] ?? $statusFilter) ?>".</p>
    </div>
</div>

<?php else: ?>
<div style="display:flex;flex-direction:column;gap:1rem">
    <?php foreach ($reports as $rep): ?>
    <div class="card <?= $rep['report_status'] === 'open' ? 'card--highlight' : '' ?>">
        <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;padding:1.25rem 1.5rem">

            <!-- Left: Report details -->
            <div>
                <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:.65rem">
                    <span class="status-badge status-badge--<?= statusClass($rep['report_status']) ?>">
                        <?= statusLabel($rep['report_status']) ?>
                    </span>
                    <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted)">
                        Report #<?= $rep['id'] ?>
                    </span>
                    <span style="font-size:.75rem;color:var(--text-muted)"><?= formatDate($rep['created_at'], 'd M Y, H:i') ?></span>
                </div>

                <div style="font-weight:700;font-size:.95rem;margin-bottom:.35rem"><?= e($rep['reason']) ?></div>

                <?php if ($rep['details']): ?>
                <p style="font-size:.85rem;color:var(--text-mid);line-height:1.6;margin-bottom:.75rem">
                    <?= nl2br(e($rep['details'])) ?>
                </p>
                <?php endif; ?>

                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.8rem;color:var(--text-muted)">
                    <span>
                        <strong style="color:var(--text-mid)">Reported by:</strong>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $rep['report_by']) ?>"
                           style="color:var(--olive)"><?= e($rep['reporter_name']) ?></a>
                        (<?= roleLabel($rep['reporter_role'] ?? '') ?>)
                    </span>
                    <?php if ($rep['listing_id']): ?>
                    <span>
                        <strong style="color:var(--text-mid)">Listing:</strong>
                        <a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $rep['listing_id']) ?>"
                           style="color:var(--olive)"><?= e($rep['listing_title'] ?? 'View listing') ?></a>
                    </span>
                    <?php endif; ?>
                    <?php if ($rep['reported_user']): ?>
                    <span>
                        <strong style="color:var(--text-mid)">Reported user:</strong>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $rep['reported_user']) ?>"
                           style="color:var(--olive)"><?= e($rep['reported_user_name'] ?? 'View user') ?></a>
                    </span>
                    <?php endif; ?>
                    <?php if ($rep['reviewer_name']): ?>
                    <span>
                        <strong style="color:var(--text-mid)">Reviewed by:</strong> <?= e($rep['reviewer_name']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($rep['admin_note']): ?>
                <div style="margin-top:.75rem;padding:.65rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);font-size:.82rem">
                    <strong style="font-size:.72rem;text-transform:uppercase;letter-spacing:.07em">Admin note:</strong>
                    <p style="margin:.25rem 0 0;color:var(--text-mid)"><?= nl2br(e($rep['admin_note'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Quick action form -->
            <div style="min-width:220px;flex-shrink:0">
                <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action"    value="report_update">
                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                    <input type="hidden" name="redirect"  value="modules/admin/reports.php?status=<?= e($statusFilter) ?>">

                    <div class="form-group">
                        <label class="form-label" style="font-size:.72rem">Update status</label>
                        <select name="report_status" class="form-control form-control--sm">
                            <?php foreach (['open', 'under_review', 'resolved', 'dismissed'] as $rs): ?>
                                <option value="<?= $rs ?>" <?= $rep['report_status'] === $rs ? 'selected' : '' ?>>
                                    <?= statusLabel($rs) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:.72rem">Admin note</label>
                        <textarea name="admin_note" class="form-control" rows="2"
                                  placeholder="Optional resolution note…"
                                  style="font-size:.82rem"><?= e($rep['admin_note'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" style="font-size:.85rem">
                        Save Update
                    </button>
                </form>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
