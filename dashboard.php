<?php
/**
 * ResQFood — Role-Aware Dashboard Home
 * ──────────────────────────────────────
 * Central hub for all roles. PHP logic unchanged; HTML fully redesigned.
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/profile.php';

requireLogin();

$role = currentUserRole();
$uid  = currentUserId();
$pdo  = db();

// Time-based greeting
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$stats  = [];
$recent = [];
$impact = [];

/* ── Business ───────────────────────────────────────────────── */
if ($role === 'business') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int) $s->fetchColumn();
    })();

    $stats = [
        'total'    => $q('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?', [$uid]),
        'active'   => $q('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ? AND status = "available"', [$uid]),
        'reserved' => $q('SELECT COUNT(*) FROM reservations r JOIN food_listings fl ON fl.id = r.listing_id WHERE fl.business_user_id = ? AND r.reservation_status = "reserved"', [$uid]),
        'collected'=> $q('SELECT COUNT(*) FROM reservations r JOIN food_listings fl ON fl.id = r.listing_id WHERE fl.business_user_id = ? AND r.reservation_status = "collected"', [$uid]),
    ];

    $profile    = getBusinessProfile($uid);
    $completion = businessProfileCompletion($uid);

    // Impact summary
    $impStmt = $pdo->prepare('
        SELECT COALESCE(SUM(ir.estimated_meals_saved),0) AS meals,
               COALESCE(SUM(ir.estimated_kg_saved),0)    AS kg,
               COALESCE(SUM(ir.estimated_co2_reduced),0) AS co2,
               COUNT(*)                                  AS records
        FROM   impact_records ir
        JOIN   food_listings  fl ON fl.id = ir.listing_id
        WHERE  fl.business_user_id = ?
    ');
    $impStmt->execute([$uid]);
    $impact = $impStmt->fetch();

    // Recent listings
    $rStmt = $pdo->prepare('
        SELECT id, title, status, quantity, unit, pickup_start, created_at
        FROM   food_listings
        WHERE  business_user_id = ?
        ORDER  BY created_at DESC LIMIT 8
    ');
    $rStmt->execute([$uid]);
    $recent = $rStmt->fetchAll();

/* ── General User ───────────────────────────────────────────── */
} elseif ($role === 'general_user') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int) $s->fetchColumn();
    })();

    $stats = [
        'available'  => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'total_res'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?', [$uid]),
        'active_res' => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "reserved"', [$uid]),
        'collected'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"', [$uid]),
    ];

    $rStmt = $pdo->prepare('
        SELECT r.id, r.reservation_status, r.reserved_at, r.pickup_code,
               fl.title, fl.pickup_start, fl.pickup_end, fl.category
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC LIMIT 6
    ');
    $rStmt->execute([$uid]);
    $recent = $rStmt->fetchAll();

/* ── Charity ─────────────────────────────────────────────────── */
} elseif ($role === 'charity') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int) $s->fetchColumn();
    })();

    $stats = [
        'available'  => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'total_col'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?', [$uid]),
        'active_col' => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "reserved"', [$uid]),
        'collected'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"', [$uid]),
    ];

    $profile    = getCharityProfile($uid);
    $completion = charityProfileCompletion($uid);

    $rStmt = $pdo->prepare('
        SELECT r.id, r.reservation_status, r.reserved_at, r.pickup_code,
               fl.title, fl.pickup_start, fl.pickup_end, fl.quantity, fl.unit
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC LIMIT 6
    ');
    $rStmt->execute([$uid]);
    $recent = $rStmt->fetchAll();

/* ── Admin ───────────────────────────────────────────────────── */
} elseif ($role === 'admin') {

    $q = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();

    $stats = [
        'users'    => $q('SELECT COUNT(*) FROM users'),
        'active'   => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'reports'  => $q('SELECT COUNT(*) FROM reports WHERE report_status = "open"'),
        'pickups'  => $q('SELECT COUNT(*) FROM reservations WHERE reservation_status = "collected"'),
    ];

    $pendingBusiness = $q('SELECT COUNT(*) FROM business_profiles WHERE verification_status = "pending"');
    $pendingCharity  = $q('SELECT COUNT(*) FROM charity_profiles  WHERE verification_status = "pending"');

    $recent = $pdo->query('
        SELECT al.action, al.details, al.ip_address, al.created_at,
               u.full_name, u.role
        FROM   audit_logs al
        LEFT   JOIN users u ON u.id = al.user_id
        ORDER  BY al.created_at DESC LIMIT 12
    ')->fetchAll();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>

<?php /* ════════════════════════════════════════════════════════
       BUSINESS DASHBOARD
════════════════════════════════════════════════════════ */ ?>
<?php if ($role === 'business'): ?>

<!-- Welcome banner -->
<div class="dash-welcome dash-welcome--business">
    <div class="dash-welcome__inner">
        <div class="dash-welcome__text">
            <div class="dash-welcome__eyebrow">
                <span class="role-badge role-badge--light">Business Account</span>
                <span class="dash-welcome__date"><?= date('l, d F Y') ?></span>
            </div>
            <div class="dash-welcome__greeting"><?= $greeting ?>, <?= e(currentUserName()) ?></div>
            <p class="dash-welcome__sub">
                <?php if ($stats['active'] > 0): ?>
                    You have <strong style="color:#fff"><?= $stats['active'] ?> active listing<?= $stats['active'] !== 1 ? 's' : '' ?></strong> available to the community right now.
                <?php else: ?>
                    Share your surplus food — post a listing to get started.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="dash-welcome__cta">
            <svg viewBox="0 0 18 18" width="14" fill="none"><path d="M9 4v10M4 9h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Post New Listing
        </a>
    </div>
</div>

<!-- Notices -->
<?php if (($profile['verification_status'] ?? '') === 'pending'): ?>
<div class="notice notice--warning">
    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong>Verification pending</strong>
        Your business is awaiting admin review. Complete your profile to speed up the process.
        <a href="<?= baseUrl('modules/profile/business_profile.php') ?>">Complete profile →</a>
    </div>
</div>
<?php elseif (($profile['verification_status'] ?? '') === 'rejected'): ?>
<div class="notice notice--danger">
    <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M7 7l6 6M13 7l-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong>Verification rejected</strong>
        Update your profile with accurate details.
        <a href="<?= baseUrl('modules/profile/business_profile.php') ?>">Update profile →</a>
    </div>
</div>
<?php endif; ?>

<?php if (isset($completion) && $completion < 100): ?>
<div class="completion-strip">
    <span class="completion-strip__label">Profile complete</span>
    <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
    <span class="completion-strip__pct"><?= $completion ?>%</span>
    <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" class="btn btn-sm btn-outline">Edit Profile</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card stat-card--olive">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M7 5V4a1 1 0 012 0v1M11 5V4a1 1 0 012 0v1" stroke="currentColor" stroke-width="1.4"/></svg></div>
        <div class="stat-card__value"><?= $stats['total'] ?></div>
        <div class="stat-card__label">Total Listings</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/listings/index.php') ?>">View all →</a></div>
    </div>
    <div class="stat-card stat-card--sage">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M10 7v3l2 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['active'] ?></div>
        <div class="stat-card__label">Available Now</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/listings/index.php?status=available') ?>">Browse →</a></div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><path d="M4 10a6 6 0 0112 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M16 10v2a6 6 0 01-12 0v-2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['reserved'] ?></div>
        <div class="stat-card__label">Pending Pickups</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/reservations/index.php') ?>">Confirm →</a></div>
    </div>
    <div class="stat-card stat-card--terra">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['collected'] ?></div>
        <div class="stat-card__label">Completed</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/dashboard/impact.php') ?>">See impact →</a></div>
    </div>
</div>

<!-- Two-col: Recent listings + Sidebar -->
<div class="dash-cols dash-cols--wide-right">

    <!-- Recent Listings -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2>Recent Listings</h2>
                <a href="<?= baseUrl('modules/listings/index.php') ?>" class="btn btn-sm btn-outline">View all</a>
            </div>
            <?php if (!empty($recent)): ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Qty</th>
                            <th>Pickup from</th>
                            <th>Posted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $row): ?>
                        <tr>
                            <td style="font-weight:600;max-width:200px">
                                <a href="<?= baseUrl('modules/listings/view.php?id=' . $row['id']) ?>" style="color:var(--olive)">
                                    <?= e(truncate($row['title'], 38)) ?>
                                </a>
                            </td>
                            <td><span class="status-badge status-badge--<?= statusClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td>
                            <td style="color:var(--text-muted)"><?= e($row['quantity'] . ' ' . $row['unit']) ?></td>
                            <td style="font-size:.82rem"><?= formatDate($row['pickup_start'], 'd M, H:i') ?></td>
                            <td style="font-size:.82rem;color:var(--text-muted)"><?= formatDate($row['created_at'], 'd M Y') ?></td>
                            <td>
                                <a href="<?= baseUrl('modules/listings/edit.php?id=' . $row['id']) ?>" class="btn btn-xs btn-outline">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state__icon"><svg viewBox="0 0 24 24" width="28" fill="none"><rect x="3" y="5" width="18" height="15" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M9 9h6M9 12h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
                <h3>No listings yet</h3>
                <p>Share your first surplus food listing with the community.</p>
                <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">Post Your First Listing</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Quick actions -->
        <div class="card">
            <div class="card-header"><h3>Quick Actions</h3></div>
            <div class="card-body" style="padding:.75rem">
                <nav class="side-actions">
                    <a href="<?= baseUrl('modules/listings/create.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(74,103,65,.1);color:var(--olive)"><svg viewBox="0 0 18 18" width="15" fill="none"><path d="M9 4v10M4 9h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                        Post a Listing
                    </a>
                    <a href="<?= baseUrl('modules/listings/index.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(122,154,106,.12);color:#4e7a46"><svg viewBox="0 0 18 18" width="15" fill="none"><rect x="2" y="4" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M5 8h8M5 11h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
                        Manage Listings
                    </a>
                    <a href="<?= baseUrl('modules/reservations/index.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(196,145,62,.1);color:var(--amber)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M9 6v3.5l2 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
                        Incoming Reservations
                        <?php if ($stats['reserved'] > 0): ?>
                        <span class="tab-nav__count" style="margin-left:auto"><?= $stats['reserved'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(181,96,74,.1);color:var(--terra)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 16v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
                        Business Profile
                    </a>
                </nav>
            </div>
        </div>

        <!-- Impact preview -->
        <div class="impact-preview">
            <div class="impact-preview__title">
                <span>Your Impact</span>
                <a href="<?= baseUrl('modules/dashboard/impact.php') ?>">Full report →</a>
            </div>
            <div class="impact-row">
                <span class="impact-row__label">
                    <svg viewBox="0 0 16 16" width="14" fill="none"><path d="M8 2a6 6 0 100 12A6 6 0 008 2zm0 3v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                    Pickups completed
                </span>
                <span class="impact-row__value"><?= number_format($stats['collected']) ?></span>
            </div>
            <div class="impact-row">
                <span class="impact-row__label">
                    <svg viewBox="0 0 16 16" width="14" fill="none"><path d="M8 14s-5-3-5-7a5 5 0 0110 0c0 4-5 7-5 7z" stroke="currentColor" stroke-width="1.3"/></svg>
                    Est. meals saved
                </span>
                <span class="impact-row__value"><?= number_format($impact['meals'] ?? 0) ?></span>
            </div>
            <div class="impact-row">
                <span class="impact-row__label">
                    <svg viewBox="0 0 16 16" width="14" fill="none"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.3"/><path d="M5.5 10.5l5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                    Est. kg rescued
                </span>
                <span class="impact-row__value"><?= number_format($impact['kg'] ?? 0, 1) ?></span>
            </div>
            <div class="impact-row">
                <span class="impact-row__label">
                    <svg viewBox="0 0 16 16" width="14" fill="none"><path d="M8 3 L10 7 L14 7.5 L11 10.5 L12 14.5 L8 12.5 L4 14.5 L5 10.5 L2 7.5 L6 7Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
                    CO₂ saved (kg)
                </span>
                <span class="impact-row__value"><?= number_format($impact['co2'] ?? 0, 1) ?></span>
            </div>
        </div>

    </div>
</div>

<?php /* ════════════════════════════════════════════════════════
       GENERAL USER DASHBOARD
════════════════════════════════════════════════════════ */ ?>
<?php elseif ($role === 'general_user'): ?>

<!-- Welcome banner -->
<div class="dash-welcome dash-welcome--user">
    <div class="dash-welcome__inner">
        <div>
            <div class="dash-welcome__eyebrow">
                <span class="role-badge role-badge--light">Member</span>
                <span class="dash-welcome__date"><?= date('l, d F Y') ?></span>
            </div>
            <div class="dash-welcome__greeting"><?= $greeting ?>, <?= e(currentUserName()) ?></div>
            <p class="dash-welcome__sub">
                <?php if ($stats['available'] > 0): ?>
                    <strong style="color:#fff"><?= $stats['available'] ?> free listing<?= $stats['available'] !== 1 ? 's' : '' ?></strong> available right now in your community.
                <?php else: ?>
                    Browse food listings and make your first reservation.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="dash-welcome__cta">
            <svg viewBox="0 0 18 18" width="14" fill="none"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M14 14l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Browse Food
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card stat-card--olive">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M15 15l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['available'] ?></div>
        <div class="stat-card__label">Listings Available</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse now →</a></div>
    </div>
    <div class="stat-card stat-card--sage">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="4" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M7 8h6M7 11.5h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['total_res'] ?></div>
        <div class="stat-card__label">Total Reservations</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/reservations/my.php') ?>">View history →</a></div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M10 7v3l2.5 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['active_res'] ?></div>
        <div class="stat-card__label">Active Pickups</div>
        <div class="stat-card__sub">
            <?php if ($stats['active_res'] > 0): ?>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>">View codes →</a>
            <?php else: ?>
                None pending
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card stat-card--terra">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['collected'] ?></div>
        <div class="stat-card__label">Meals Collected</div>
        <div class="stat-card__sub">Lifetime total</div>
    </div>
</div>

<!-- Browse CTA Band -->
<div class="dash-cta-band">
    <div class="dash-cta-band__icon">
        <svg viewBox="0 0 28 28" width="26" fill="none"><path d="M4 22 Q14 8 24 22" stroke="#4a6741" stroke-width="1.5" fill="none"/><ellipse cx="14" cy="22" rx="10" ry="3" fill="rgba(74,103,65,0.12)"/><circle cx="10" cy="16" r="2.5" fill="#7a9a6a" opacity=".7"/><circle cx="14" cy="12" r="2" fill="#4a6741" opacity=".8"/><circle cx="18" cy="16" r="2.5" fill="#a8c098" opacity=".6"/></svg>
    </div>
    <div class="dash-cta-band__body">
        <h3>Fresh listings are available near you</h3>
        <p>Food businesses post surplus every day. Reserve for free and collect from your local area.</p>
    </div>
    <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">
        <svg viewBox="0 0 16 16" width="13" fill="none"><circle cx="7" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M12 12l-2.5-2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Browse Listings
    </a>
</div>

<!-- Recent Reservations -->
<div class="card">
    <div class="card-header">
        <h2>My Reservations</h2>
        <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="btn btn-sm btn-outline">View all</a>
    </div>
    <?php if (!empty($recent)): ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Status</th>
                    <th>Pickup Code</th>
                    <th>Pickup Window</th>
                    <th>Reserved</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <td style="font-weight:600">
                        <span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block"><?= e($row['category'] ?? '') ?></span>
                        <?= e(truncate($row['title'], 38)) ?>
                    </td>
                    <td><span class="status-badge status-badge--<?= statusClass($row['reservation_status']) ?>"><?= statusLabel($row['reservation_status']) ?></span></td>
                    <td>
                        <code style="font-size:.8rem;background:rgba(74,103,65,.08);color:var(--olive);padding:.2rem .55rem;border-radius:5px;font-weight:800;letter-spacing:.06em">
                            <?= e($row['pickup_code']) ?>
                        </code>
                    </td>
                    <td style="font-size:.82rem"><?= formatDate($row['pickup_start'], 'd M, H:i') ?></td>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?= formatDate($row['reserved_at'], 'd M Y') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icon"><svg viewBox="0 0 24 24" width="28" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M15 15l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
        <h3>No reservations yet</h3>
        <p>Browse available listings and reserve your first free food pickup.</p>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Start Browsing</a>
    </div>
    <?php endif; ?>
</div>

<?php /* ════════════════════════════════════════════════════════
       CHARITY DASHBOARD
════════════════════════════════════════════════════════ */ ?>
<?php elseif ($role === 'charity'): ?>

<!-- Welcome banner -->
<div class="dash-welcome dash-welcome--charity">
    <div class="dash-welcome__inner">
        <div>
            <div class="dash-welcome__eyebrow">
                <span class="role-badge role-badge--light">Charity Organisation</span>
                <span class="dash-welcome__date"><?= date('l, d F Y') ?></span>
            </div>
            <div class="dash-welcome__greeting"><?= $greeting ?>, <?= e(currentUserName()) ?></div>
            <p class="dash-welcome__sub">
                <?php if ($stats['available'] > 0): ?>
                    <strong style="color:#fff"><?= $stats['available'] ?> listing<?= $stats['available'] !== 1 ? 's' : '' ?></strong> currently available for collection in your community.
                <?php else: ?>
                    Collect surplus food for your community — check listings daily.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="dash-welcome__cta">
            <svg viewBox="0 0 18 18" width="14" fill="none"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M14 14l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Browse Listings
        </a>
    </div>
</div>

<!-- Notices -->
<?php if (($profile['verification_status'] ?? '') === 'pending'): ?>
<div class="notice notice--warning">
    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong>Verification pending</strong>
        Complete your organisation profile to speed up the admin review process.
        <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>">Complete profile →</a>
    </div>
</div>
<?php endif; ?>

<?php if (isset($completion) && $completion < 100): ?>
<div class="completion-strip">
    <span class="completion-strip__label">Profile complete</span>
    <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
    <span class="completion-strip__pct"><?= $completion ?>%</span>
    <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" class="btn btn-sm btn-outline">Complete Profile</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card stat-card--olive">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M15 15l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['available'] ?></div>
        <div class="stat-card__label">Available Now</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse →</a></div>
    </div>
    <div class="stat-card stat-card--sage">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3a7 7 0 100 14A7 7 0 0010 3z" stroke="currentColor" stroke-width="1.4"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['total_col'] ?></div>
        <div class="stat-card__label">Total Collections</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/reservations/my.php') ?>">View all →</a></div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M10 7v3l2.5 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['active_col'] ?></div>
        <div class="stat-card__label">Active Collections</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/reservations/my.php?status=reserved') ?>">View →</a></div>
    </div>
    <div class="stat-card stat-card--terra">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['collected'] ?></div>
        <div class="stat-card__label">Pickups Done</div>
        <div class="stat-card__sub">Lifetime</div>
    </div>
</div>

<!-- Quick actions + Browse band -->
<div class="dash-cols">
    <!-- Left: Collections list -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2>Recent Collections</h2>
                <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="btn btn-sm btn-outline">View all</a>
            </div>
            <?php if (!empty($recent)): ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Listing</th><th>Status</th><th>Code</th><th>Pickup from</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $row): ?>
                        <tr>
                            <td style="font-weight:600"><?= e(truncate($row['title'], 36)) ?></td>
                            <td><span class="status-badge status-badge--<?= statusClass($row['reservation_status']) ?>"><?= statusLabel($row['reservation_status']) ?></span></td>
                            <td><code style="font-size:.79rem;background:rgba(74,103,65,.08);color:var(--olive);padding:.18rem .5rem;border-radius:5px;font-weight:800"><?= e($row['pickup_code']) ?></code></td>
                            <td style="font-size:.82rem"><?= formatDate($row['pickup_start'], 'd M, H:i') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:2.5rem 1.5rem">
                <h3>No collections yet</h3>
                <p>Browse available listings and make your first reservation.</p>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Browse Food</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">
        <div class="dash-cta-band" style="flex-direction:column;align-items:flex-start">
            <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted)">Daily Food Available</div>
            <div style="font-family:var(--f-display);font-size:2rem;font-weight:700;color:var(--olive-deep)"><?= $stats['available'] ?></div>
            <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:.85rem">Active listings ready for collection</p>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary btn-sm">Reserve Now</a>
        </div>
        <div class="card">
            <div class="card-body" style="padding:.75rem">
                <nav class="side-actions">
                    <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(74,103,65,.1);color:var(--olive)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.4"/><path d="M14 14l-2.5-2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                        Browse All Listings
                    </a>
                    <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(196,145,62,.1);color:var(--amber)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M6 9l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        My Collections
                        <?php if ($stats['active_col'] > 0): ?><span class="tab-nav__count" style="margin-left:auto"><?= $stats['active_col'] ?></span><?php endif; ?>
                    </a>
                    <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(181,96,74,.1);color:var(--terra)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 16v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
                        Organisation Profile
                    </a>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php /* ════════════════════════════════════════════════════════
       ADMIN DASHBOARD
════════════════════════════════════════════════════════ */ ?>
<?php elseif ($role === 'admin'): ?>

<!-- Welcome banner -->
<div class="dash-welcome dash-welcome--admin">
    <div class="dash-welcome__inner">
        <div>
            <div class="dash-welcome__eyebrow">
                <span class="role-badge role-badge--light">Administrator</span>
                <span class="dash-welcome__date"><?= date('l, d F Y') ?></span>
            </div>
            <div class="dash-welcome__greeting"><?= $greeting ?>, <?= e(currentUserName()) ?></div>
            <p class="dash-welcome__sub">Platform health overview. <?= $stats['users'] ?> registered users &middot; <?= $stats['active'] ?> live listings.</p>
        </div>
        <a href="<?= baseUrl('modules/admin/dashboard.php') ?>" class="dash-welcome__cta">
            <svg viewBox="0 0 18 18" width="14" fill="none"><rect x="2" y="2" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="10" y="2" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="2" y="10" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="10" y="10" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/></svg>
            Admin Panel
        </a>
    </div>
</div>

<!-- Pending alerts -->
<?php if (($pendingBusiness + $pendingCharity) > 0): ?>
<div class="notice notice--info">
    <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong><?= $pendingBusiness + $pendingCharity ?> profile<?= ($pendingBusiness + $pendingCharity) !== 1 ? 's' : '' ?> awaiting verification</strong>
        <?= $pendingBusiness ?> business<?= $pendingBusiness !== 1 ? 'es' : '' ?> and <?= $pendingCharity ?> charit<?= $pendingCharity !== 1 ? 'ies' : 'y' ?> need review.
        <a href="<?= baseUrl('modules/admin/users.php') ?>">Review now →</a>
    </div>
</div>
<?php endif; ?>
<?php if ($stats['reports'] > 0): ?>
<div class="notice notice--warning">
    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong><?= $stats['reports'] ?> open report<?= $stats['reports'] !== 1 ? 's' : '' ?></strong>
        Pending moderation action.
        <a href="<?= baseUrl('modules/admin/reports.php') ?>">Review reports →</a>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card stat-card--olive">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="7" cy="7" r="3.5" stroke="currentColor" stroke-width="1.4"/><path d="M1 17v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M15 8v6M18 11h-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['users'] ?></div>
        <div class="stat-card__label">Registered Users</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php') ?>">Manage →</a></div>
    </div>
    <div class="stat-card stat-card--sage">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="4" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M7 8h6M7 11.5h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['active'] ?></div>
        <div class="stat-card__label">Live Listings</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/listings.php') ?>">View all →</a></div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 9v3m0 2h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['reports'] ?></div>
        <div class="stat-card__label">Open Reports</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/reports.php') ?>">Moderate →</a></div>
    </div>
    <div class="stat-card stat-card--terra">
        <div class="stat-card__icon-box"><svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="stat-card__value"><?= $stats['pickups'] ?></div>
        <div class="stat-card__label">Total Pickups</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/dashboard/impact.php') ?>">Impact →</a></div>
    </div>
</div>

<!-- Two-col: Quick actions + Audit log -->
<div class="dash-cols">
    <!-- Quick actions -->
    <div style="display:flex;flex-direction:column;gap:1rem">
        <div class="card">
            <div class="card-header"><h2>Admin Actions</h2></div>
            <div class="card-body" style="padding:.75rem">
                <nav class="side-actions">
                    <a href="<?= baseUrl('modules/admin/users.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(74,103,65,.1);color:var(--olive)"><svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 16v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
                        Manage Users
                        <?php if ($pendingBusiness + $pendingCharity > 0): ?><span class="tab-nav__count" style="margin-left:auto;background:rgba(196,145,62,.15);color:var(--amber)"><?= $pendingBusiness + $pendingCharity ?></span><?php endif; ?>
                    </a>
                    <a href="<?= baseUrl('modules/admin/listings.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(122,154,106,.12);color:#4e7a46"><svg viewBox="0 0 18 18" width="15" fill="none"><rect x="2" y="3" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M5 8h8M5 11h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
                        All Listings
                    </a>
                    <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(196,145,62,.1);color:var(--amber)"><svg viewBox="0 0 18 18" width="15" fill="none"><path d="M9 2L2 15h14L9 2z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9 8v3m0 2h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                        Reports & Moderation
                        <?php if ($stats['reports'] > 0): ?><span class="tab-nav__count" style="margin-left:auto;background:rgba(196,145,62,.15);color:var(--amber)"><?= $stats['reports'] ?></span><?php endif; ?>
                    </a>
                    <a href="<?= baseUrl('modules/dashboard/impact.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(181,96,74,.1);color:var(--terra)"><svg viewBox="0 0 18 18" width="15" fill="none"><path d="M3 14l3.5-4 3 3 4.5-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        Impact Dashboard
                    </a>
                    <a href="<?= baseUrl('modules/admin/dashboard.php') ?>" class="side-action">
                        <div class="side-action__icon" style="background:rgba(74,103,65,.08);color:var(--olive)"><svg viewBox="0 0 18 18" width="15" fill="none"><rect x="2" y="2" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="10" y="2" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="2" y="10" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="10" y="10" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.4"/></svg></div>
                        Full Admin Dashboard
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Audit log -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2>Recent Activity</h2>
            </div>
            <?php if (!empty($recent)): ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>User</th><th>Action</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $row): ?>
                        <tr>
                            <td>
                                <span style="font-weight:600;font-size:.85rem"><?= e($row['full_name'] ?? 'System') ?></span>
                                <?php if ($row['role']): ?>
                                <span class="role-badge role-badge--<?= roleBadgeClass($row['role']) ?>" style="margin-left:.3rem"><?= e(roleLabel($row['role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:.77rem;background:rgba(0,0,0,.05);padding:.15rem .4rem;border-radius:4px"><?= e($row['action']) ?></code></td>
                            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap"><?= formatDate($row['created_at'], 'd M, H:i') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:2rem 1.5rem"><p>No audit activity yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
