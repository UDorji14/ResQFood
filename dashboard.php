<?php
/**
 * ResQFood — Role-Aware Dashboard
 * ──────────────────────────────────
 * Central hub that loads role-specific stats, banners, and recent activity.
 * All four roles (business, general_user, charity, admin) are served here.
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/profile.php';

requireLogin();

$role   = currentUserRole();
$uid    = currentUserId();
$pdo    = db();
$stats  = [];
$recent = [];

// ─────────────────────────────────────────────────────────────────────────
// Business Stats
// ─────────────────────────────────────────────────────────────────────────
if ($role === 'business') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    })();

    $stats = [
        'total_listings'  => $q('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?', [$uid]),
        'active_listings' => $q('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ? AND status = "available"', [$uid]),
        'pending_pickups' => $q('SELECT COUNT(*) FROM reservations r JOIN food_listings fl ON fl.id = r.listing_id WHERE fl.business_user_id = ? AND r.reservation_status = "reserved"', [$uid]),
        'completed'       => $q('SELECT COUNT(*) FROM reservations r JOIN food_listings fl ON fl.id = r.listing_id WHERE fl.business_user_id = ? AND r.reservation_status = "collected"', [$uid]),
    ];

    $profile    = getBusinessProfile($uid);
    $completion = businessProfileCompletion($uid);

    $stmt = $pdo->prepare('
        SELECT id, title, status, quantity, unit, pickup_start, created_at
        FROM   food_listings WHERE business_user_id = ?
        ORDER  BY created_at DESC LIMIT 6
    ');
    $stmt->execute([$uid]);
    $recent = $stmt->fetchAll();

// ─────────────────────────────────────────────────────────────────────────
// General User Stats
// ─────────────────────────────────────────────────────────────────────────
} elseif ($role === 'general_user') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    })();

    $stats = [
        'available_now'        => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'total_reservations'   => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?', [$uid]),
        'active_reservations'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "reserved"', [$uid]),
        'collected'            => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"', [$uid]),
    ];

    $stmt = $pdo->prepare('
        SELECT r.id, r.reservation_status, r.reserved_at, r.pickup_code,
               fl.title, fl.pickup_start, fl.pickup_end, fl.status AS listing_status
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC LIMIT 6
    ');
    $stmt->execute([$uid]);
    $recent = $stmt->fetchAll();

// ─────────────────────────────────────────────────────────────────────────
// Charity Stats
// ─────────────────────────────────────────────────────────────────────────
} elseif ($role === 'charity') {

    $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    })();

    $stats = [
        'available_now'       => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'total_collections'   => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?', [$uid]),
        'active_collections'  => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "reserved"', [$uid]),
        'completed'           => $q('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"', [$uid]),
    ];

    $profile    = getCharityProfile($uid);
    $completion = charityProfileCompletion($uid);

    $stmt = $pdo->prepare('
        SELECT r.id, r.reservation_status, r.reserved_at, r.pickup_code,
               fl.title, fl.pickup_start, fl.pickup_end
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC LIMIT 6
    ');
    $stmt->execute([$uid]);
    $recent = $stmt->fetchAll();

// ─────────────────────────────────────────────────────────────────────────
// Admin Stats
// ─────────────────────────────────────────────────────────────────────────
} elseif ($role === 'admin') {

    $q = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();

    $stats = [
        'total_users'         => $q('SELECT COUNT(*) FROM users'),
        'active_listings'     => $q('SELECT COUNT(*) FROM food_listings WHERE status = "available"'),
        'open_reports'        => $q('SELECT COUNT(*) FROM reports WHERE report_status = "open"'),
        'total_pickups'       => $q('SELECT COUNT(*) FROM reservations WHERE reservation_status = "collected"'),
    ];

    // Pending verifications (banner-worthy)
    $pendingBusiness = $q('SELECT COUNT(*) FROM business_profiles WHERE verification_status = "pending"');
    $pendingCharity  = $q('SELECT COUNT(*) FROM charity_profiles  WHERE verification_status = "pending"');
    $pendingUsers    = $q('SELECT COUNT(*) FROM users WHERE status = "pending"');

    $recent = $pdo->query('
        SELECT al.action, al.details, al.ip_address, al.created_at,
               u.full_name, u.role
        FROM   audit_logs al
        LEFT JOIN users u ON u.id = al.user_id
        ORDER  BY al.created_at DESC LIMIT 10
    ')->fetchAll();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>

<!-- ── Welcome Banner ────────────────────────────────────────── -->
<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>
                Welcome back, <?= e(currentUserName()) ?>
                <span class="role-badge role-badge--<?= e(roleBadgeClass($role)) ?>" style="vertical-align:middle;margin-left:.5rem">
                    <?= e(roleLabel($role)) ?>
                </span>
            </h1>
            <p class="text-muted"><?= date('l, d F Y') ?></p>
        </div>
        <?php if ($role === 'business'): ?>
            <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">+ New Listing</a>
        <?php elseif ($role === 'general_user' || $role === 'charity'): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">Browse Food</a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Notice Banners ────────────────────────────────────────── -->

<?php if ($role === 'business' && isset($profile)): ?>

    <?php if ($profile['verification_status'] === 'pending'): ?>
    <div class="notice notice--warning">
        <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <div class="notice__body">
            <strong>Business verification pending</strong>
            Your business account is awaiting admin verification. You can complete your profile while you wait — once verified, you'll be able to post food listings.
            <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" style="font-weight:700;margin-left:.5rem">Complete profile →</a>
        </div>
    </div>
    <?php elseif ($profile['verification_status'] === 'rejected'): ?>
    <div class="notice notice--danger">
        <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M7 7l6 6M13 7l-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <div class="notice__body">
            <strong>Verification rejected</strong>
            Your business verification was not approved. Please update your profile with accurate information and contact support.
            <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" style="font-weight:700;margin-left:.5rem">Update profile →</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($completion < 100): ?>
    <div class="completion-strip">
        <span class="completion-strip__label">Profile complete</span>
        <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
        <span class="completion-strip__pct"><?= $completion ?>%</span>
        <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" class="btn btn-sm btn-outline">Edit Profile</a>
    </div>
    <?php endif; ?>

<?php elseif ($role === 'charity' && isset($profile)): ?>

    <?php if ($profile['verification_status'] === 'pending'): ?>
    <div class="notice notice--warning">
        <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <div class="notice__body">
            <strong>Charity verification pending</strong>
            Your organisation is awaiting admin verification. Complete your profile to speed up the process.
            <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" style="font-weight:700;margin-left:.5rem">Complete profile →</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($completion) && $completion < 100): ?>
    <div class="completion-strip">
        <span class="completion-strip__label">Profile complete</span>
        <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
        <span class="completion-strip__pct"><?= $completion ?>%</span>
        <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" class="btn btn-sm btn-outline">Edit Profile</a>
    </div>
    <?php endif; ?>

<?php elseif ($role === 'admin'): ?>
    <?php if ($pendingBusiness > 0 || $pendingCharity > 0): ?>
    <div class="notice notice--info">
        <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <div class="notice__body">
            <strong>Pending verifications</strong>
            <?= $pendingBusiness ?> business<?= $pendingBusiness !== 1 ? 'es' : '' ?> and <?= $pendingCharity ?> charit<?= $pendingCharity !== 1 ? 'ies' : 'y' ?> are awaiting verification.
            <a href="<?= baseUrl('modules/admin/users.php') ?>" style="font-weight:700;margin-left:.5rem">Review now →</a>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ── Stats Grid ─────────────────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:2rem">

    <?php if ($role === 'business'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['total_listings'] ?></div>
            <div class="stat-label">Total Listings</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['active_listings'] ?></div>
            <div class="stat-label">Available Now</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['pending_pickups'] ?></div>
            <div class="stat-label">Pending Pickups</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['completed'] ?></div>
            <div class="stat-label">Completed Pickups</div>
        </div>

    <?php elseif ($role === 'general_user'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['available_now'] ?></div>
            <div class="stat-label">Listings Available</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['total_reservations'] ?></div>
            <div class="stat-label">My Reservations</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['active_reservations'] ?></div>
            <div class="stat-label">Active Pickups</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['collected'] ?></div>
            <div class="stat-label">Meals Collected</div>
        </div>

    <?php elseif ($role === 'charity'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['available_now'] ?></div>
            <div class="stat-label">Listings Available</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['total_collections'] ?></div>
            <div class="stat-label">Total Collections</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['active_collections'] ?></div>
            <div class="stat-label">Active Reservations</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['completed'] ?></div>
            <div class="stat-label">Completed Pickups</div>
        </div>

    <?php elseif ($role === 'admin'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['active_listings'] ?></div>
            <div class="stat-label">Live Listings</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['open_reports'] ?></div>
            <div class="stat-label">Open Reports</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['total_pickups'] ?></div>
            <div class="stat-label">Total Pickups</div>
        </div>
    <?php endif; ?>

</div>

<!-- ── Quick Actions ──────────────────────────────────────────── -->
<div class="quick-actions">

    <?php if ($role === 'business'): ?>
        <a href="<?= baseUrl('modules/listings/create.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--olive">
                <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Post a Listing</div>
            <div class="action-card__desc">Share surplus food with your community</div>
        </a>
        <a href="<?= baseUrl('modules/listings/index.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--sage">
                <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 5V4a1 1 0 012 0v1M11 5V4a1 1 0 012 0v1" stroke="currentColor" stroke-width="1.5"/></svg>
            </div>
            <div class="action-card__label">My Listings</div>
            <div class="action-card__desc">View and manage your posted food</div>
        </a>
        <a href="<?= baseUrl('modules/reservations/index.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--amber">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 7v4l2.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Reservations</div>
            <div class="action-card__desc">Track who reserved your listings</div>
        </a>
        <a href="<?= baseUrl('modules/profile/business_profile.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--terra">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 18v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Business Profile</div>
            <div class="action-card__desc">Update your business details</div>
        </a>
        <a href="<?= baseUrl('modules/dashboard/impact.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--olive">
                <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M4 15l4-4 3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="action-card__label">My Impact</div>
            <div class="action-card__desc">See your food rescue impact stats</div>
        </a>

    <?php elseif ($role === 'general_user'): ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--olive">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="9" cy="9" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M15 15l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Browse Food</div>
            <div class="action-card__desc">Find available surplus food near you</div>
        </a>
        <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--amber">
                <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 9l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="action-card__label">My Reservations</div>
            <div class="action-card__desc">Track your upcoming pickups</div>
        </a>
        <a href="<?= baseUrl('modules/profile/index.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--sage">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 18v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">My Profile</div>
            <div class="action-card__desc">Manage your account settings</div>
        </a>

    <?php elseif ($role === 'charity'): ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--olive">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="9" cy="9" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M15 15l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Browse Listings</div>
            <div class="action-card__desc">Find available food for your organisation</div>
        </a>
        <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--amber">
                <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 9l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="action-card__label">My Collections</div>
            <div class="action-card__desc">View your active and past reservations</div>
        </a>
        <a href="<?= baseUrl('modules/profile/charity_profile.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--terra">
                <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3a7 7 0 100 14A7 7 0 0010 3z" stroke="currentColor" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="action-card__label">Organisation Profile</div>
            <div class="action-card__desc">Update your charity details</div>
        </a>

    <?php elseif ($role === 'admin'): ?>
        <a href="<?= baseUrl('modules/admin/users.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--olive">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="7" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M1 17v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 7v6M19 10h-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Manage Users</div>
            <div class="action-card__desc">View accounts, verify businesses</div>
        </a>
        <a href="<?= baseUrl('modules/admin/listings.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--sage">
                <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 9h6M7 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">All Listings</div>
            <div class="action-card__desc">Platform-wide food listing overview</div>
        </a>
        <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--amber">
                <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="action-card__label">Reports</div>
            <div class="action-card__desc">Review and moderate user reports</div>
        </a>
        <a href="<?= baseUrl('modules/dashboard/impact.php') ?>" class="action-card">
            <div class="action-card__icon action-card__icon--terra">
                <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M4 15l4-4 3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="action-card__label">Impact Data</div>
            <div class="action-card__desc">Environmental metrics overview</div>
        </a>
    <?php endif; ?>

</div>

<!-- ── Recent Activity ────────────────────────────────────────── -->
<?php if (!empty($recent)): ?>
<div class="card">
    <div class="card-header">
        <h2><?php
            if ($role === 'business')         echo 'Recent Listings';
            elseif ($role === 'admin')        echo 'Recent Activity Log';
            elseif ($role === 'charity')      echo 'Recent Collections';
            else                              echo 'Recent Reservations';
        ?></h2>
        <?php if ($role === 'business'): ?>
            <a href="<?= baseUrl('modules/listings/index.php') ?>" class="btn btn-sm btn-outline">View all</a>
        <?php elseif ($role !== 'admin'): ?>
            <a href="<?= baseUrl('modules/reservations/my.php') ?>" class="btn btn-sm btn-outline">View all</a>
        <?php endif; ?>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <?php if ($role === 'business'): ?>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Quantity</th>
                        <th>Pickup Starts</th>
                        <th>Posted</th>
                    <?php elseif ($role === 'admin'): ?>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>IP</th>
                        <th>When</th>
                    <?php else: ?>
                        <th>Listing</th>
                        <th>Status</th>
                        <th>Pickup Code</th>
                        <th>Pickup Window</th>
                        <th>Reserved</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <?php if ($role === 'business'): ?>
                        <td>
                            <a href="<?= baseUrl('modules/listings/index.php') ?>" style="font-weight:600;color:var(--olive)">
                                <?= e(truncate($row['title'], 40)) ?>
                            </a>
                        </td>
                        <td><span class="status-badge status-badge--<?= statusClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td>
                        <td><?= e($row['quantity'] . ' ' . $row['unit']) ?></td>
                        <td><?= formatDate($row['pickup_start'], 'd M, H:i') ?></td>
                        <td><?= formatDate($row['created_at'], 'd M Y') ?></td>

                    <?php elseif ($role === 'admin'): ?>
                        <td style="font-weight:600"><?= e($row['full_name'] ?? 'System') ?></td>
                        <td>
                            <?php if ($row['role']): ?>
                                <span class="role-badge role-badge--<?= e(roleBadgeClass($row['role'])) ?>"><?= e(roleLabel($row['role'])) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><code style="font-size:.8rem;background:rgba(0,0,0,.05);padding:.15rem .45rem;border-radius:4px"><?= e($row['action']) ?></code></td>
                        <td style="color:var(--text-muted);font-size:.82rem"><?= e($row['ip_address'] ?? '—') ?></td>
                        <td style="font-size:.82rem"><?= formatDate($row['created_at'], 'd M, H:i') ?></td>

                    <?php else: ?>
                        <td style="font-weight:600"><?= e(truncate($row['title'], 40)) ?></td>
                        <td><span class="status-badge status-badge--<?= statusClass($row['reservation_status']) ?>"><?= statusLabel($row['reservation_status']) ?></span></td>
                        <td>
                            <code style="font-size:.82rem;background:rgba(74,103,65,.08);color:var(--olive);padding:.2rem .5rem;border-radius:5px;font-weight:700;letter-spacing:.05em">
                                <?= e($row['pickup_code']) ?>
                            </code>
                        </td>
                        <td style="font-size:.82rem"><?= formatDate($row['pickup_start'], 'd M, H:i') ?> – <?= formatDate($row['pickup_end'], 'H:i') ?></td>
                        <td style="font-size:.82rem"><?= formatDate($row['reserved_at'], 'd M Y') ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center" style="padding:3.5rem 2rem">
        <svg viewBox="0 0 64 64" width="56" fill="none" style="margin:0 auto 1.25rem;opacity:.25">
            <rect x="10" y="16" width="44" height="36" rx="4" stroke="#4a6741" stroke-width="2"/>
            <path d="M22 16v-3a2 2 0 012-2h16a2 2 0 012 2v3" stroke="#4a6741" stroke-width="2"/>
            <path d="M24 32h16M24 40h10" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p class="text-muted" style="font-size:.9rem">
            <?php if ($role === 'business'): ?>
                No listings yet. <a href="<?= baseUrl('modules/listings/create.php') ?>">Post your first listing →</a>
            <?php else: ?>
                No activity yet. <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse available food →</a>
            <?php endif; ?>
        </p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
