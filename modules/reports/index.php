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

if ($role === 'business') {
    $lStmt = $pdo->prepare('SELECT id, title FROM food_listings WHERE business_user_id = ? ORDER BY created_at DESC LIMIT 100');
    $lStmt->execute([$uid]);
} else {
    $lStmt = $pdo->prepare('SELECT fl.id, fl.title FROM food_listings fl WHERE fl.status IN ("available","reserved","collected","expired") ORDER BY fl.created_at DESC LIMIT 150');
    $lStmt->execute();
}
$listingOptions = $lStmt->fetchAll();

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

$countStmt = $pdo->prepare('SELECT report_status, COUNT(*) AS cnt FROM reports WHERE report_by = ? GROUP BY report_status');
$countStmt->execute([$uid]);
$reportCounts = ['all' => 0, 'open' => 0, 'under_review' => 0, 'resolved' => 0, 'dismissed' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $reportCounts[$row['report_status']] = (int) $row['cnt'];
    $reportCounts['all'] += (int) $row['cnt'];
}

$tabs = [
    'all'          => ['label' => 'All',          'icon' => '◉'],
    'open'         => ['label' => 'Open',         'icon' => '●'],
    'under_review' => ['label' => 'Under Review', 'icon' => '◑'],
    'resolved'     => ['label' => 'Resolved',     'icon' => '✓'],
    'dismissed'    => ['label' => 'Dismissed',    'icon' => '✕'],
];

/* Status colour map */
$statusColors = [
    'open'         => ['bg' => '#fef3c7', 'text' => '#92400e', 'dot' => '#f59e0b'],
    'under_review' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'dot' => '#3b82f6'],
    'resolved'     => ['bg' => '#d1fae5', 'text' => '#065f46', 'dot' => '#10b981'],
    'dismissed'    => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'dot' => '#9ca3af'],
];

$pageTitle = 'My Reports';
require_once __DIR__ . '/../../partials/header.php';
if ($role === 'business') {
    require_once __DIR__ . '/../../partials/business_shell.php';
    renderBusinessShellStart('reports', 'Reports', 'Submit issues and track moderation status.');
} elseif ($role === 'general_user') {
    require_once __DIR__ . '/../../partials/user_shell.php';
    renderUserShellStart('reports', 'Reports', 'Submit issues and track moderation updates.');
} elseif ($role === 'charity') {
    require_once __DIR__ . '/../../partials/charity_shell.php';
    renderCharityShellStart('reports', 'Reports', 'Submit issues and track admin responses.');
}
?>

<style>
/* ── Reports page scoped styles ── */
.rpt-layout {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 1.25rem;
    align-items: start;
}

/* Submit form card */
.rpt-form-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 8px rgba(0,0,0,0.05);
    position: sticky;
    top: 1rem;
}
.rpt-form-card__head {
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.65rem;
}
.rpt-form-card__head-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #4a6741 0%, #3d5436 100%);
    display: grid; place-items: center; flex-shrink: 0;
}
.rpt-form-card__head-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; }
.rpt-form-card__head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #1a2a17; }
.rpt-form-card__head p  { margin: 0; font-size: 0.78rem; color: #6b7280; }
.rpt-form-card__body { padding: 1.25rem; }

/* Reports list panel */
.rpt-list-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 8px rgba(0,0,0,0.05);
}
.rpt-list-card__head {
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.rpt-list-card__head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #1a2a17; }
.rpt-count-badge {
    background: #1a2a17;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    letter-spacing: 0.04em;
}

/* Status filter chips — horizontal scroll */
.rpt-filter-bar {
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
    background: #fafafa;
}
.rpt-filter-bar::-webkit-scrollbar { display: none; }

.rpt-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.38rem 0.85rem;
    border-radius: 999px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #374151;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.rpt-chip:hover { border-color: #4a6741; color: #2e3f2a; background: #f0f4ee; text-decoration: none; }
.rpt-chip.is-active { background: #1a2a17; border-color: #1a2a17; color: #fff; }
.rpt-chip__count {
    background: rgba(255,255,255,0.25);
    font-size: 0.7rem;
    padding: 0.05rem 0.45rem;
    border-radius: 999px;
    min-width: 1.4rem;
    text-align: center;
    line-height: 1.4;
}
.rpt-chip.is-active .rpt-chip__count { background: rgba(255,255,255,0.2); }
.rpt-chip:not(.is-active) .rpt-chip__count { background: #f3f4f6; color: #374151; }

/* Individual report cards */
.rpt-feed {
    padding: 0.75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.rpt-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.15s ease;
}
.rpt-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }

.rpt-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
}

.rpt-card__id {
    font-size: 0.78rem;
    font-weight: 700;
    color: #6b7280;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.rpt-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    letter-spacing: 0.04em;
}
.rpt-status-pill__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.rpt-card__date {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-left: auto;
    flex-shrink: 0;
}

.rpt-card__body {
    padding: 1rem;
}

.rpt-card__reason {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.rpt-card__listing {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: #4b5563;
    background: #f3f4f6;
    border-radius: 6px;
    padding: 0.25rem 0.6rem;
    margin-bottom: 0.65rem;
}

.rpt-card__details {
    font-size: 0.84rem;
    color: #6b7280;
    line-height: 1.55;
    border-top: 1px solid #f3f4f6;
    padding-top: 0.65rem;
    margin-top: 0.5rem;
}

.rpt-card__admin-note {
    margin: 0.75rem 1rem 1rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    font-size: 0.82rem;
    color: #1e40af;
    line-height: 1.5;
}
.rpt-card__admin-note strong {
    display: block;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #3b82f6;
    margin-bottom: 0.35rem;
}

/* Empty state */
.rpt-empty {
    padding: 3rem 1.5rem;
    text-align: center;
}
.rpt-empty-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: #f3f4f6;
    display: grid; place-items: center;
    margin: 0 auto 1rem;
}
.rpt-empty-icon svg { width: 26px; height: 26px; stroke: #9ca3af; fill: none; stroke-width: 1.5; stroke-linecap: round; }
.rpt-empty h4 { margin: 0 0 0.35rem; font-size: 0.95rem; color: #374151; }
.rpt-empty p  { margin: 0; font-size: 0.83rem; color: #9ca3af; }

/* Responsive */
@media (max-width: 860px) {
    .rpt-layout {
        grid-template-columns: 1fr !important;
    }
    .rpt-form-card {
        position: static !important;
    }
}

@media (max-width: 480px) {
    .rpt-card__top { gap: 0.4rem; }
    .rpt-card__date { width: 100%; margin-left: 0; }
    .rpt-feed { padding: 0.5rem; }
}
</style>

<div class="rpt-layout">

    <!-- ── Left: Submit Form ── -->
    <div class="rpt-form-card">
        <div class="rpt-form-card__head">
            <div class="rpt-form-card__head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            </div>
            <div>
                <h3>Submit a Report</h3>
                <p>Describe the issue clearly for admins to review</p>
            </div>
        </div>
        <div class="rpt-form-card__body">
            <?php if (!empty($errors)): ?>
                <div class="flash flash--error" style="margin-bottom:1rem;">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Please fix the errors below.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_report">

                <div class="form-group">
                    <label class="form-label" for="reason">Reason <span class="required">*</span></label>
                    <select id="reason" name="reason" class="form-control <?= isset($errors['reason']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Select a reason…</option>
                        <?php foreach ($reasons as $reason): ?>
                            <option value="<?= e($reason) ?>" <?= $old['reason'] === $reason ? 'selected' : '' ?>><?= e($reason) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['reason'])): ?><span class="form-error"><?= e($errors['reason']) ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="listing_id">Related Listing <span style="color:#9ca3af;font-weight:400">(optional)</span></label>
                    <select id="listing_id" name="listing_id" class="form-control <?= isset($errors['listing_id']) ? 'is-invalid' : '' ?>">
                        <option value="0">Not related to a specific listing</option>
                        <?php foreach ($listingOptions as $opt): ?>
                            <option value="<?= (int) $opt['id'] ?>" <?= (int)$old['listing_id'] === (int)$opt['id'] ? 'selected' : '' ?>>
                                #<?= (int) $opt['id'] ?> — <?= e(truncate($opt['title'], 60)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['listing_id'])): ?><span class="form-error"><?= e($errors['listing_id']) ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="details">Details <span class="required">*</span></label>
                    <textarea id="details" name="details" rows="5"
                              class="form-control <?= isset($errors['details']) ? 'is-invalid' : '' ?>"
                              placeholder="Describe what happened, when, and any relevant context…"
                              data-maxlength="2500"
                              required><?= e($old['details']) ?></textarea>
                    <?php if (isset($errors['details'])): ?><span class="form-error"><?= e($errors['details']) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" style="flex-shrink:0" aria-hidden="true"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                    Submit Report
                </button>
            </form>
        </div>
    </div>

    <!-- ── Right: My Reports List ── -->
    <div class="rpt-list-card">
        <div class="rpt-list-card__head">
            <h3>My Reports</h3>
            <span class="rpt-count-badge"><?= $reportCounts['all'] ?> total</span>
        </div>

        <!-- Status filter chips -->
        <div class="rpt-filter-bar" role="navigation" aria-label="Filter reports by status">
            <?php foreach ($tabs as $key => $info): ?>
                <a href="?status=<?= e($key) ?>"
                   class="rpt-chip <?= $statusView === $key ? 'is-active' : '' ?>"
                   aria-current="<?= $statusView === $key ? 'page' : 'false' ?>">
                    <?= e($info['label']) ?>
                    <?php if ($reportCounts[$key] > 0): ?>
                        <span class="rpt-chip__count"><?= $reportCounts[$key] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Reports feed -->
        <?php if (empty($myReports)): ?>
            <div class="rpt-empty">
                <div class="rpt-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/></svg>
                </div>
                <h4>No reports found</h4>
                <p>No <?= $statusView !== 'all' ? e(str_replace('_', ' ', $statusView)) . ' ' : '' ?>reports yet. Use the form to submit one.</p>
            </div>
        <?php else: ?>
            <div class="rpt-feed">
                <?php foreach ($myReports as $rep):
                    $sc = $statusColors[$rep['report_status']] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280', 'dot' => '#9ca3af'];
                ?>
                    <div class="rpt-card">
                        <div class="rpt-card__top">
                            <span class="rpt-card__id">Report #<?= (int) $rep['id'] ?></span>

                            <span class="rpt-status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>">
                                <span class="rpt-status-pill__dot" style="background:<?= $sc['dot'] ?>"></span>
                                <?= statusLabel($rep['report_status']) ?>
                            </span>

                            <span class="rpt-card__date"><?= formatDate($rep['created_at'], 'd M Y, H:i') ?></span>
                        </div>

                        <div class="rpt-card__body">
                            <div class="rpt-card__reason"><?= e($rep['reason']) ?></div>

                            <?php if (!empty($rep['listing_id'])): ?>
                                <div class="rpt-card__listing">
                                    <svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M7 8h6M7 11h4"/></svg>
                                    Listing #<?= (int) $rep['listing_id'] ?> — <?= e(truncate($rep['listing_title'] ?? 'N/A', 50)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($rep['details'])): ?>
                                <div class="rpt-card__details"><?= nl2br(e(truncate($rep['details'], 220))) ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($rep['admin_note'])): ?>
                            <div class="rpt-card__admin-note">
                                <strong>
                                    <?php if (!empty($rep['reviewer_name'])): ?>
                                        Admin note · <?= e($rep['reviewer_name']) ?>
                                    <?php else: ?>
                                        Admin note
                                    <?php endif; ?>
                                </strong>
                                <?= nl2br(e($rep['admin_note'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === 'business') renderBusinessShellEnd(); ?>
<?php if ($role === 'general_user') renderUserShellEnd(); ?>
<?php if ($role === 'charity') renderCharityShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
