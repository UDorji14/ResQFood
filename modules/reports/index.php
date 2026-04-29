<?php
/**
 * ResQFood — My Reports (Business / User / Charity)
 * Submit reports to admins and track their status.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['business', 'general_user', 'charity']);

$uid        = currentUserId();
$role       = currentUserRole();
$pdo        = db();
$errors     = [];
$statusView = sanitize($_GET['status'] ?? 'all');
$allowedStatus = ['all', 'open', 'under_review', 'resolved', 'dismissed'];
if (!in_array($statusView, $allowedStatus, true)) {
    $statusView = 'all';
}

$old = [
    'reason'        => '',
    'details'       => '',
    'listing_id'    => (int) ($_GET['listing_id'] ?? 0),
    'reported_user' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'create_report') {
        $old['reason']        = sanitize($_POST['reason'] ?? '');
        $old['details']       = sanitize($_POST['details'] ?? '');
        $old['listing_id']    = (int) ($_POST['listing_id'] ?? 0);
        $old['reported_user'] = sanitize($_POST['reported_user'] ?? '');

        if ($old['reason'] === '') {
            $errors['reason'] = 'Please select a reason.';
        } elseif (mb_strlen($old['reason']) > 120) {
            $errors['reason'] = 'Reason is too long.';
        }
        if ($old['details'] === '') {
            $errors['details'] = 'Please add report details.';
        } elseif (mb_strlen($old['details']) > 2500) {
            $errors['details'] = 'Details must be under 2500 characters.';
        }

        $listingRow = null;
        if ($old['listing_id'] > 0) {
            $st = $pdo->prepare('SELECT id, title, business_user_id FROM food_listings WHERE id = ? LIMIT 1');
            $st->execute([$old['listing_id']]);
            $listingRow = $st->fetch();
            if (!$listingRow) {
                $errors['listing_id'] = 'Selected listing does not exist.';
            }
        }

        $reportedUserId = null;
        if ($old['reported_user'] !== '') {
            if (!ctype_digit($old['reported_user'])) {
                $errors['reported_user'] = 'Invalid reported user.';
            } else {
                $reportedUserId = (int) $old['reported_user'];
            }
        } elseif ($listingRow && (int) $listingRow['business_user_id'] !== $uid) {
            // Useful default: when reporting a listing, target the listing owner.
            $reportedUserId = (int) $listingRow['business_user_id'];
        }

        if (empty($errors)) {
            $pdo->prepare('
                INSERT INTO reports (report_by, listing_id, reported_user, reason, details, report_status)
                VALUES (?, ?, ?, ?, ?, "open")
            ')->execute([
                $uid,
                $old['listing_id'] > 0 ? $old['listing_id'] : null,
                $reportedUserId,
                $old['reason'],
                $old['details'],
            ]);

            $reportId = (int) $pdo->lastInsertId();
            auditLog('report_create', 'report_id=' . $reportId . ' role=' . $role, $uid);

            // Notify all active admins
            $admins = $pdo->query('SELECT id FROM users WHERE role = "admin" AND status = "active"')->fetchAll();
            foreach ($admins as $admin) {
                $pdo->prepare('
                    INSERT INTO notifications (user_id, title, message, link)
                    VALUES (?, ?, ?, ?)
                ')->execute([
                    (int) $admin['id'],
                    'New User Report',
                    currentUserName() . ' submitted a report: ' . truncate($old['reason'], 70),
                    baseUrl('modules/admin/reports.php?status=open'),
                ]);
            }

            setFlash('success', 'Report submitted successfully. Admins will review it soon.');
            redirect(baseUrl('modules/reports/index.php'));
        }
    }
}

$reasons = [
    'Fake or misleading listing',
    'Unsafe food condition',
    'Abusive behavior',
    'Pickup issue',
    'Spam or scam',
    'Other',
];

// Listings available for quick association in the form
if ($role === 'business') {
    $lStmt = $pdo->prepare('
        SELECT id, title
        FROM food_listings
        WHERE business_user_id = ?
        ORDER BY created_at DESC
        LIMIT 100
    ');
    $lStmt->execute([$uid]);
} else {
    $lStmt = $pdo->prepare('
        SELECT fl.id, fl.title
        FROM food_listings fl
        WHERE fl.status IN ("available", "reserved", "collected", "expired")
        ORDER BY fl.created_at DESC
        LIMIT 150
    ');
    $lStmt->execute();
}
$listingOptions = $lStmt->fetchAll();

// My reports list + optional status filter
$where = 'WHERE rp.report_by = ?';
$params = [$uid];
if ($statusView !== 'all') {
    $where .= ' AND rp.report_status = ?';
    $params[] = $statusView;
}
$stmt = $pdo->prepare('
    SELECT rp.*, fl.title AS listing_title, u.full_name AS reviewer_name
    FROM reports rp
    LEFT JOIN food_listings fl ON fl.id = rp.listing_id
    LEFT JOIN users u ON u.id = rp.reviewed_by
    ' . $where . '
    ORDER BY rp.created_at DESC
');
$stmt->execute($params);
$myReports = $stmt->fetchAll();

$countStmt = $pdo->prepare('
    SELECT report_status, COUNT(*) AS cnt
    FROM reports
    WHERE report_by = ?
    GROUP BY report_status
');
$countStmt->execute([$uid]);
$reportCounts = ['all' => 0, 'open' => 0, 'under_review' => 0, 'resolved' => 0, 'dismissed' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $reportCounts[$row['report_status']] = (int) $row['cnt'];
    $reportCounts['all'] += (int) $row['cnt'];
}

$tabs = [
    'all' => 'All',
    'open' => 'Open',
    'under_review' => 'Under Review',
    'resolved' => 'Resolved',
    'dismissed' => 'Dismissed',
];

$pageTitle = 'My Reports';
require_once __DIR__ . '/../../partials/header.php';
if ($role === 'business') {
    require_once __DIR__ . '/../../partials/business_shell.php';
    renderBusinessShellStart('reports', 'Reports', 'Submit operational issues and track moderation outcomes.');
} elseif ($role === 'general_user') {
    require_once __DIR__ . '/../../partials/user_shell.php';
    renderUserShellStart('reports', 'Reports', 'Submit issues and track moderation updates from admins.');
} elseif ($role === 'charity') {
    require_once __DIR__ . '/../../partials/charity_shell.php';
    renderCharityShellStart('reports', 'Reports', 'Submit operational issues and track moderation updates from admins.');
}
?>
<?php if (!in_array($role, ['business', 'general_user', 'charity'], true)): ?>
<div class="page-head">
    <div class="page-head__top">
        <div><h1>Report to Admin</h1><p class="text-muted">Submit issues and track moderation status.</p></div>
    </div>
</div>
<?php endif; ?>

<div class="<?= in_array($role, ['business', 'general_user', 'charity'], true) ? 'biz-report-layout' : 'admin-2col' ?>" style="align-items:start">
    <div class="card">
        <div class="card-header"><h3>Submit a Report</h3></div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_report">

                <div class="form-group">
                    <label class="form-label" for="reason">Reason <span class="required">*</span></label>
                    <select id="reason" name="reason" class="form-control <?= isset($errors['reason']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Select a reason</option>
                        <?php foreach ($reasons as $reason): ?>
                            <option value="<?= e($reason) ?>" <?= $old['reason'] === $reason ? 'selected' : '' ?>><?= e($reason) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['reason'])): ?><span class="form-error"><?= e($errors['reason']) ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="listing_id">Related Listing (optional)</label>
                    <select id="listing_id" name="listing_id" class="form-control <?= isset($errors['listing_id']) ? 'is-invalid' : '' ?>">
                        <option value="0">Not related to a specific listing</option>
                        <?php foreach ($listingOptions as $opt): ?>
                            <option value="<?= (int) $opt['id'] ?>" <?= (int)$old['listing_id'] === (int)$opt['id'] ? 'selected' : '' ?>>
                                #<?= (int) $opt['id'] ?> - <?= e(truncate($opt['title'], 70)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['listing_id'])): ?><span class="form-error"><?= e($errors['listing_id']) ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="details">Details <span class="required">*</span></label>
                    <textarea id="details" name="details" rows="5" class="form-control <?= isset($errors['details']) ? 'is-invalid' : '' ?>"
                              placeholder="Describe what happened, when, and any important context..." required><?= e($old['details']) ?></textarea>
                    <?php if (isset($errors['details'])): ?><span class="form-error"><?= e($errors['details']) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Submit Report</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>My Reports Status</h3>
            <span class="status-badge status-badge--default"><?= $reportCounts['all'] ?> total</span>
        </div>
        <div class="card-body" style="padding:1rem">
            <nav class="tab-nav" style="margin-bottom:1rem">
                <?php foreach ($tabs as $key => $label): ?>
                    <a href="?status=<?= e($key) ?>" class="tab-nav__item <?= $statusView === $key ? 'active' : '' ?>">
                        <?= e($label) ?>
                        <?php if (!empty($reportCounts[$key])): ?>
                            <span class="tab-nav__count"><?= $reportCounts[$key] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (empty($myReports)): ?>
                <div class="empty-state" style="padding:1.5rem .5rem">
                    <p class="text-muted" style="margin:0">No reports found for this status.</p>
                </div>
            <?php else: ?>
                <div class="report-feed">
                    <?php foreach ($myReports as $rep): ?>
                        <div class="report-item report-item--<?= e($rep['report_status']) ?>">
                            <div class="report-item__head">
                                <div class="report-item__id">
                                    <span>Report #<?= (int) $rep['id'] ?></span>
                                    <span class="status-badge status-badge--<?= statusClass($rep['report_status']) ?>">
                                        <?= statusLabel($rep['report_status']) ?>
                                    </span>
                                    <span><?= formatDate($rep['created_at'], 'd M Y, H:i') ?></span>
                                </div>
                            </div>

                            <div class="report-item__body">
                                <div class="report-item__reason"><?= e($rep['reason']) ?></div>
                                <?php if (!empty($rep['listing_id'])): ?>
                                <div class="report-item__links">
                                    <span><strong>Listing:</strong> #<?= (int) $rep['listing_id'] ?> - <?= e(truncate($rep['listing_title'] ?? 'N/A', 55)) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($rep['details'])): ?>
                                <div class="report-item__details"><?= nl2br(e($rep['details'])) ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($rep['admin_note'])): ?>
                            <div class="report-item__foot">
                                <div class="report-item__admin-note">
                                    <div class="report-item__note-label">Admin note<?= !empty($rep['reviewer_name']) ? ' by ' . e($rep['reviewer_name']) : '' ?></div>
                                    <?= nl2br(e($rep['admin_note'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($role === 'business') renderBusinessShellEnd(); ?>
<?php if ($role === 'general_user') renderUserShellEnd(); ?>
<?php if ($role === 'charity') renderCharityShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
