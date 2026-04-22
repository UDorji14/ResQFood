<?php
/**
 * ResQFood — Main Dashboard
 * Role-aware: shows different stats and quick actions per role.
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/csrf.php';

requireLogin();

$role  = currentUserRole();
$pdo   = db();
$uid   = currentUserId();
$stats = [];
$recent = [];

// ── Fetch role-specific stats ─────────────────────────────────────────────

if ($role === 'business') {

    $stats['total_listings'] = $pdo->prepare('
        SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?
    ');
    $stats['total_listings']->execute([$uid]);
    $stats['total_listings'] = (int) $stats['total_listings']->fetchColumn();

    $stats['active_listings'] = $pdo->prepare('
        SELECT COUNT(*) FROM food_listings
        WHERE  business_user_id = ? AND status = "available"
    ');
    $stats['active_listings']->execute([$uid]);
    $stats['active_listings'] = (int) $stats['active_listings']->fetchColumn();

    $stats['pending_pickups'] = $pdo->prepare('
        SELECT COUNT(*) FROM reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  fl.business_user_id = ? AND r.reservation_status = "reserved"
    ');
    $stats['pending_pickups']->execute([$uid]);
    $stats['pending_pickups'] = (int) $stats['pending_pickups']->fetchColumn();

    $stats['completed'] = $pdo->prepare('
        SELECT COUNT(*) FROM reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  fl.business_user_id = ? AND r.reservation_status = "collected"
    ');
    $stats['completed']->execute([$uid]);
    $stats['completed'] = (int) $stats['completed']->fetchColumn();

    // Recent listings
    $stmt = $pdo->prepare('
        SELECT id, title, status, quantity, unit, pickup_start, created_at
        FROM   food_listings
        WHERE  business_user_id = ?
        ORDER  BY created_at DESC
        LIMIT  5
    ');
    $stmt->execute([$uid]);
    $recent = $stmt->fetchAll();

} elseif ($role === 'general_user' || $role === 'charity') {

    $stats['total_reservations'] = $pdo->prepare('
        SELECT COUNT(*) FROM reservations WHERE reserved_by = ?
    ');
    $stats['total_reservations']->execute([$uid]);
    $stats['total_reservations'] = (int) $stats['total_reservations']->fetchColumn();

    $stats['active_reservations'] = $pdo->prepare('
        SELECT COUNT(*) FROM reservations
        WHERE  reserved_by = ? AND reservation_status = "reserved"
    ');
    $stats['active_reservations']->execute([$uid]);
    $stats['active_reservations'] = (int) $stats['active_reservations']->fetchColumn();

    $stats['collected'] = $pdo->prepare('
        SELECT COUNT(*) FROM reservations
        WHERE  reserved_by = ? AND reservation_status = "collected"
    ');
    $stats['collected']->execute([$uid]);
    $stats['collected'] = (int) $stats['collected']->fetchColumn();

    $stats['available_nearby'] = (int) $pdo->query('
        SELECT COUNT(*) FROM food_listings WHERE status = "available"
    ')->fetchColumn();

    // Recent reservations
    $stmt = $pdo->prepare('
        SELECT r.id, r.reservation_status, r.reserved_at, r.pickup_code,
               fl.title, fl.pickup_start, fl.pickup_end
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC
        LIMIT  5
    ');
    $stmt->execute([$uid]);
    $recent = $stmt->fetchAll();

} elseif ($role === 'admin') {

    $stats['total_users']     = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status != "suspended"')->fetchColumn();
    $stats['active_listings'] = (int) $pdo->query('SELECT COUNT(*) FROM food_listings WHERE status = "available"')->fetchColumn();
    $stats['open_reports']    = (int) $pdo->query('SELECT COUNT(*) FROM reports WHERE report_status = "open"')->fetchColumn();
    $stats['total_collected'] = (int) $pdo->query('SELECT COUNT(*) FROM reservations WHERE reservation_status = "collected"')->fetchColumn();

    // Recent audit log
    $recent = $pdo->query('
        SELECT al.action, al.details, al.ip_address, al.created_at,
               u.full_name, u.role
        FROM   audit_logs al
        LEFT JOIN users u ON u.id = al.user_id
        ORDER  BY al.created_at DESC
        LIMIT  10
    ')->fetchAll();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Welcome back, <?= e(currentUserName()) ?></h1>
            <p class="text-muted">
                <?= e(roleLabel($role)) ?> dashboard &mdash; <?= date('l, d F Y') ?>
            </p>
        </div>
        <?php if ($role === 'business'): ?>
            <a href="<?= baseUrl('modules/listings/create.php') ?>" class="btn btn-primary">
                + New Listing
            </a>
        <?php elseif ($role === 'general_user' || $role === 'charity'): ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>" class="btn btn-primary">
                Browse Food
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Stats ── -->
<div class="stat-grid">
    <?php if ($role === 'business'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['total_listings'] ?></div>
            <div class="stat-label">Total Listings</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['active_listings'] ?></div>
            <div class="stat-label">Active Now</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['pending_pickups'] ?></div>
            <div class="stat-label">Pending Pickups</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['completed'] ?></div>
            <div class="stat-label">Completed Pickups</div>
        </div>

    <?php elseif ($role === 'general_user' || $role === 'charity'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['available_nearby'] ?></div>
            <div class="stat-label">Available Listings</div>
        </div>
        <div class="stat-card stat-card--sage">
            <div class="stat-value"><?= $stats['total_reservations'] ?></div>
            <div class="stat-label">Total Reservations</div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-value"><?= $stats['active_reservations'] ?></div>
            <div class="stat-label">Active Reservations</div>
        </div>
        <div class="stat-card stat-card--terra">
            <div class="stat-value"><?= $stats['collected'] ?></div>
            <div class="stat-label">Meals Collected</div>
        </div>

    <?php elseif ($role === 'admin'): ?>
        <div class="stat-card stat-card--olive">
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Active Users</div>
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
            <div class="stat-value"><?= $stats['total_collected'] ?></div>
            <div class="stat-label">Total Pickups</div>
        </div>
    <?php endif; ?>
</div>

<!-- ── Quick Actions ── -->
<div class="d-flex gap-2 mb-4" style="flex-wrap:wrap">
    <?php if ($role === 'business'): ?>
        <a href="<?= baseUrl('modules/listings/index.php') ?>"     class="btn btn-outline">My Listings</a>
        <a href="<?= baseUrl('modules/reservations/index.php') ?>" class="btn btn-outline">Reservations</a>
        <a href="<?= baseUrl('modules/profile/index.php') ?>"      class="btn btn-outline">Business Profile</a>
    <?php elseif ($role === 'general_user' || $role === 'charity'): ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>"    class="btn btn-outline">Browse Listings</a>
        <a href="<?= baseUrl('modules/reservations/my.php') ?>"    class="btn btn-outline">My Reservations</a>
        <a href="<?= baseUrl('modules/profile/index.php') ?>"      class="btn btn-outline">My Profile</a>
    <?php elseif ($role === 'admin'): ?>
        <a href="<?= baseUrl('modules/admin/users.php') ?>"        class="btn btn-outline">Manage Users</a>
        <a href="<?= baseUrl('modules/admin/listings.php') ?>"     class="btn btn-outline">All Listings</a>
        <a href="<?= baseUrl('modules/reports/index.php') ?>"      class="btn btn-outline">Reports</a>
        <a href="<?= baseUrl('modules/admin/impact.php') ?>"       class="btn btn-outline">Impact Data</a>
    <?php endif; ?>
</div>

<!-- ── Recent Activity ── -->
<?php if (!empty($recent)): ?>
<div class="card">
    <div class="card-header">
        <h2><?php
            if ($role === 'business')       echo 'Recent Listings';
            elseif ($role === 'admin')      echo 'Recent Activity';
            else                            echo 'Recent Reservations';
        ?></h2>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <?php if ($role === 'business'): ?>
                        <th>Title</th><th>Status</th><th>Quantity</th><th>Pickup Starts</th><th>Created</th>
                    <?php elseif ($role === 'admin'): ?>
                        <th>User</th><th>Role</th><th>Action</th><th>IP</th><th>Time</th>
                    <?php else: ?>
                        <th>Listing</th><th>Status</th><th>Pickup Code</th><th>Pickup Window</th><th>Reserved</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <?php if ($role === 'business'): ?>
                        <td><?= e($row['title']) ?></td>
                        <td><span class="status-badge status-badge--<?= statusClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td>
                        <td><?= e($row['quantity'] . ' ' . $row['unit']) ?></td>
                        <td><?= formatDate($row['pickup_start']) ?></td>
                        <td><?= formatDate($row['created_at']) ?></td>
                    <?php elseif ($role === 'admin'): ?>
                        <td><?= e($row['full_name'] ?? 'System') ?></td>
                        <td><?= $row['role'] ? '<span class="role-badge role-badge--' . e(roleBadgeClass($row['role'])) . '">' . e(roleLabel($row['role'])) . '</span>' : '—' ?></td>
                        <td><?= e($row['action']) ?></td>
                        <td><?= e($row['ip_address'] ?? '—') ?></td>
                        <td><?= formatDate($row['created_at']) ?></td>
                    <?php else: ?>
                        <td><?= e($row['title']) ?></td>
                        <td><span class="status-badge status-badge--<?= statusClass($row['reservation_status']) ?>"><?= statusLabel($row['reservation_status']) ?></span></td>
                        <td><code><?= e($row['pickup_code']) ?></code></td>
                        <td><?= formatDate($row['pickup_start'], 'd M, H:i') ?> – <?= formatDate($row['pickup_end'], 'H:i') ?></td>
                        <td><?= formatDate($row['reserved_at']) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center" style="padding:3rem">
        <svg viewBox="0 0 48 48" width="48" fill="none" style="margin:0 auto 1rem;opacity:.35">
            <rect x="8" y="12" width="32" height="28" rx="3" stroke="#4a6741" stroke-width="2"/>
            <path d="M16 12V9a2 2 0 012-2h12a2 2 0 012 2v3" stroke="#4a6741" stroke-width="2"/>
            <path d="M18 24h12M18 30h8" stroke="#4a6741" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p class="text-muted">No activity yet. Get started by using the links above.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
