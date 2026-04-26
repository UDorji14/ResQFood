<?php
/**
 * ResQFood — Admin Dashboard
 * Platform-wide statistics, pending actions, and recent activity.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/admin.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['admin']);

// Auto-expire stale listings
expireOldListings();

$stats      = getSystemStats();
$impact     = getGlobalImpactStats();
$recentLogs = fetchRecentAuditLogs(10);
$newUsers   = getRecentUsers(6);

$byRole   = $stats['by_role'];
$listings = $stats['listings'];
$res      = $stats['reservations'];

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="text-muted">Platform overview and moderation centre.</p>
        </div>
        <div style="display:flex;gap:.65rem;flex-wrap:wrap">
            <a href="<?= baseUrl('modules/admin/reports.php') ?>" class="btn btn-outline">
                Reports
                <?php if ($stats['open_reports'] > 0): ?>
                    <span style="background:var(--terra);color:#fff;border-radius:var(--r-pill);padding:.1rem .45rem;font-size:.72rem;font-weight:800;margin-left:.3rem"><?= $stats['open_reports'] ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= baseUrl('modules/admin/users.php') ?>" class="btn btn-primary">Manage Users</a>
        </div>
    </div>
</div>

<!-- ── Alerts ── -->
<?php if ($stats['pending_verif'] > 0): ?>
<div class="notice notice--warning" style="margin-bottom:1.25rem">
    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong><?= $stats['pending_verif'] ?> profile<?= $stats['pending_verif'] !== 1 ? 's' : '' ?> awaiting verification.</strong>
        Review them in <a href="<?= baseUrl('modules/admin/users.php?role=business') ?>">Business</a>
        or <a href="<?= baseUrl('modules/admin/users.php?role=charity') ?>">Charity</a> user lists.
    </div>
</div>
<?php endif; ?>
<?php if ($stats['open_reports'] > 0): ?>
<div class="notice notice--danger" style="margin-bottom:1.25rem">
    <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong><?= $stats['open_reports'] ?> open report<?= $stats['open_reports'] !== 1 ? 's' : '' ?> require attention.</strong>
        <a href="<?= baseUrl('modules/admin/reports.php?status=open') ?>">Review now →</a>
    </div>
</div>
<?php endif; ?>

<!-- ── Users Grid ── -->
<div style="margin-bottom:.5rem;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted)">Users</div>
<div class="stat-grid" style="margin-bottom:1.75rem">
    <div class="stat-card">
        <div class="stat-card__value"><?= number_format($stats['users']) ?></div>
        <div class="stat-card__label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--olive)"><?= number_format($byRole['business'] ?? 0) ?></div>
        <div class="stat-card__label">Businesses</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=business') ?>">View →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--olive)"><?= number_format($byRole['charity'] ?? 0) ?></div>
        <div class="stat-card__label">Charities</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=charity') ?>">View →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= number_format($byRole['general_user'] ?? 0) ?></div>
        <div class="stat-card__label">General Users</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=general_user') ?>">View →</a></div>
    </div>
</div>

<!-- ── Listings Grid ── -->
<div style="margin-bottom:.5rem;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted)">Listings</div>
<div class="stat-grid" style="margin-bottom:1.75rem">
    <div class="stat-card">
        <div class="stat-card__value"><?= number_format($stats['listings_total']) ?></div>
        <div class="stat-card__label">Total Listings</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--olive)"><?= number_format($listings['available'] ?? 0) ?></div>
        <div class="stat-card__label">Available Now</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:#b8860b"><?= number_format($listings['reserved'] ?? 0) ?></div>
        <div class="stat-card__label">Reserved</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= number_format($listings['collected'] ?? 0) ?></div>
        <div class="stat-card__label">Collected</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--terra)"><?= number_format($listings['expired'] ?? 0) ?></div>
        <div class="stat-card__label">Expired</div>
    </div>
</div>

<!-- ── Reservations + Impact ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.75rem">

    <!-- Reservations -->
    <div class="card">
        <div class="card-header"><h3>Reservations</h3></div>
        <div class="card-body" style="padding:1rem 1.25rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <?php foreach (['reserved' => 'Pending Pickup', 'collected' => 'Collected', 'cancelled' => 'Cancelled', 'expired' => 'Expired', 'no_show' => 'No-show'] as $key => $label): ?>
                <div style="padding:.75rem;background:var(--bg-base);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--text)"><?= number_format($res[$key] ?? 0) ?></div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-muted)"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Impact summary -->
    <div class="card" style="background:linear-gradient(135deg,rgba(74,103,65,.06),rgba(74,103,65,.02))">
        <div class="card-header">
            <h3>Estimated Impact</h3>
            <a href="<?= baseUrl('modules/dashboard/impact.php') ?>" style="font-size:.8rem;color:var(--olive)">Full report →</a>
        </div>
        <div class="card-body" style="padding:1rem 1.25rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div style="padding:.75rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--olive)"><?= number_format($impact['total_pickups']) ?></div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-muted)">Successful Pickups</div>
                </div>
                <div style="padding:.75rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--olive)"><?= number_format($impact['meals_saved'], 0) ?></div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-muted)">Meals Saved~</div>
                </div>
                <div style="padding:.75rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--olive)"><?= number_format($impact['kg_saved'], 1) ?> kg</div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-muted)">Food Diverted~</div>
                </div>
                <div style="padding:.75rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--olive)"><?= number_format($impact['co2_reduced'], 1) ?> kg</div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-muted)">CO₂ Reduced~</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Bottom: Recent Users + Audit Log ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

    <!-- Recent users -->
    <div class="card">
        <div class="card-header">
            <h3>Newest Members</h3>
            <a href="<?= baseUrl('modules/admin/users.php') ?>" style="font-size:.8rem;color:var(--olive)">All users →</a>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                    <?php foreach ($newUsers as $u): ?>
                    <tr>
                        <td>
                            <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>"
                               style="font-weight:600;color:var(--olive)"><?= e($u['full_name']) ?></a>
                            <div style="font-size:.73rem;color:var(--text-muted)"><?= e($u['email']) ?></div>
                        </td>
                        <td><span class="role-badge role-badge--<?= roleBadgeClass($u['role']) ?>"><?= roleLabel($u['role']) ?></span></td>
                        <td><span class="status-badge status-badge--<?= statusClass($u['status']) ?>"><?= statusLabel($u['status']) ?></span></td>
                        <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($u['created_at'], 'd M Y') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent audit log -->
    <div class="card">
        <div class="card-header"><h3>Recent System Activity</h3></div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Action</th><th>User</th><th>When</th></tr></thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td>
                            <span style="font-size:.8rem;font-weight:700;font-family:monospace;color:var(--olive)"><?= e($log['action']) ?></span>
                            <?php if ($log['details']): ?>
                                <div style="font-size:.72rem;color:var(--text-muted)"><?= e(truncate($log['details'], 40)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.82rem">
                            <?= $log['full_name'] ? e($log['full_name']) : '<span class="text-muted">System</span>' ?>
                        </td>
                        <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap">
                            <?= formatDate($log['created_at'], 'd M, H:i') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentLogs)): ?>
                        <tr><td colspan="3" class="text-muted" style="text-align:center;padding:1rem">No activity yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
