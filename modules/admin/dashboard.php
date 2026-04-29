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
require_once __DIR__ . '/../../includes/csrf.php';
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

<?php
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart(
    'dashboard',
    'Admin Dashboard',
    'Platform overview, verification queues, and recent audit activity.'
);
?>



<!-- ── Users Stats ──────────────────────────────────────────── -->
<div class="admin-section-label">Users</div>
<div class="stat-grid" style="margin-bottom:1.75rem">
    <div class="stat-card">
        <div class="stat-card__icon-box" style="background:rgba(74,103,65,.1);color:var(--olive)">
            <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M3 18c0-3.9 3.1-7 7-7s7 3.1 7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card__value"><?= number_format($stats['users']) ?></div>
        <div class="stat-card__label">Total Users</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php') ?>">View all →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-box" style="background:rgba(74,103,65,.1);color:var(--olive)">
            <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="5" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 5V4a3 3 0 016 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card__value" style="color:var(--olive)"><?= number_format($byRole['business'] ?? 0) ?></div>
        <div class="stat-card__label">Businesses</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=business') ?>">View →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-box" style="background:rgba(196,145,62,.1);color:#8a5c0e">
            <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 4c-2.8 1.5-5 4-5 6.5C5 13.5 7.2 16 10 16s5-2.5 5-5.5c0-2.5-2.2-5-5-6.5z" stroke="currentColor" stroke-width="1.5"/></svg>
        </div>
        <div class="stat-card__value" style="color:#8a5c0e"><?= number_format($byRole['charity'] ?? 0) ?></div>
        <div class="stat-card__label">Charities</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=charity') ?>">View →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-box" style="background:rgba(74,103,65,.06);color:var(--text-mid)">
            <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="8" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 17c0-3.3 2.7-6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="15" cy="13" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M13 13h4M15 11v4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div class="stat-card__value"><?= number_format($byRole['general_user'] ?? 0) ?></div>
        <div class="stat-card__label">General Users</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/users.php?role=general_user') ?>">View →</a></div>
    </div>
</div>

<!-- ── Listings Stats ───────────────────────────────────────── -->
<div class="admin-section-label">Listings</div>
<div class="stat-grid" style="margin-bottom:1.75rem">
    <div class="stat-card">
        <div class="stat-card__value"><?= number_format($stats['listings_total']) ?></div>
        <div class="stat-card__label">Total</div>
        <div class="stat-card__sub"><a href="<?= baseUrl('modules/admin/listings.php') ?>">View all →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--olive)"><?= number_format($listings['available'] ?? 0) ?></div>
        <div class="stat-card__label">Available</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:#b8860b"><?= number_format($listings['reserved'] ?? 0) ?></div>
        <div class="stat-card__label">Reserved</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:#3d6b34"><?= number_format($listings['collected'] ?? 0) ?></div>
        <div class="stat-card__label">Collected</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--terra)"><?= number_format($listings['expired'] ?? 0) ?></div>
        <div class="stat-card__label">Expired</div>
    </div>
</div>

<!-- ── Reservations + Impact ────────────────────────────────── -->
<div class="admin-2col">

    <!-- Reservations breakdown -->
    <div class="card">
        <div class="card-header">
            <h3>Reservations</h3>
            <span class="status-badge status-badge--amber"><?= number_format(array_sum($res)) ?> total</span>
        </div>
        <div class="card-body" style="padding:1rem 1.25rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem">
                <?php foreach ([
                    'reserved'  => ['Pending Pickup', 'amber'],
                    'collected' => ['Collected',      'green'],
                    'cancelled' => ['Cancelled',      'muted'],
                    'expired'   => ['Expired',        'terra'],
                    'no_show'   => ['No-show',        'muted'],
                ] as $key => [$label, $accent]): ?>
                <div style="padding:.75rem;background:var(--bg-base);border-radius:var(--r-md);text-align:center;border:1px solid var(--line)">
                    <div style="font-size:1.5rem;font-weight:900;color:var(--text-dark);line-height:1"><?= number_format($res[$key] ?? 0) ?></div>
                    <div style="font-size:.7rem;font-weight:700;color:var(--text-muted);margin-top:.3rem"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Impact mini summary -->
    <div class="card" style="background:linear-gradient(135deg,rgba(74,103,65,.05),rgba(74,103,65,.01))">
        <div class="card-header">
            <h3>Estimated Impact</h3>
            <a href="<?= baseUrl('modules/admin/impact.php') ?>"
               style="font-size:.8rem;color:var(--olive);text-decoration:none;font-weight:600">Full report &rarr;</a>
        </div>
        <div class="card-body" style="padding:1rem 1.25rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem">
                <?php foreach ([
                    [$impact['total_pickups'],              'Pickups',      ''],
                    [number_format($impact['meals_saved'],0),'Meals~',      ''],
                    [$impact['kg_saved'],                   'kg Diverted~', ' kg'],
                    [$impact['co2_reduced'],                'CO₂ Avoided~', ' kg'],
                ] as [$val, $label, $unit]): ?>
                <div style="padding:.75rem;background:rgba(74,103,65,.07);border-radius:var(--r-md);text-align:center">
                    <div style="font-size:1.4rem;font-weight:900;color:var(--olive);line-height:1">
                        <?= is_numeric($val) ? number_format((float)$val, 0) : $val ?><?= e($unit) ?>
                    </div>
                    <div style="font-size:.7rem;font-weight:700;color:var(--text-muted);margin-top:.3rem"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Newest Members + Audit Log ───────────────────────────── -->
<div class="admin-2col">

    <div class="card">
        <div class="card-header">
            <h3>Newest Members</h3>
            <a href="<?= baseUrl('modules/admin/users.php') ?>"
               style="font-size:.8rem;color:var(--olive);text-decoration:none;font-weight:600">All users &rarr;</a>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newUsers as $u): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar user-avatar--<?= e($u['role']) ?>">
                                    <?= strtoupper(mb_substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <a class="user-cell__name"
                                       href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>">
                                        <?= e(truncate($u['full_name'], 22)) ?>
                                    </a>
                                    <span class="user-cell__email"><?= e(truncate($u['email'], 28)) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><span class="role-badge role-badge--<?= roleBadgeClass($u['role']) ?>"><?= roleLabel($u['role']) ?></span></td>
                        <td><span class="status-badge status-badge--<?= statusClass($u['status']) ?>"><?= statusLabel($u['status']) ?></span></td>
                        <td style="font-size:.77rem;color:var(--text-muted);white-space:nowrap"><?= formatDate($u['created_at'], 'd M Y') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($newUsers)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:1.5rem;color:var(--text-muted)">No users yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Recent System Activity</h3></div>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Action</th><th>User</th><th>When</th></tr></thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td>
                            <span style="font-size:.79rem;font-weight:700;font-family:var(--f-mono,'Courier New',monospace);color:var(--olive)">
                                <?= e($log['action']) ?>
                            </span>
                            <?php if ($log['details']): ?>
                            <div style="font-size:.71rem;color:var(--text-muted);margin-top:.1rem">
                                <?= e(truncate($log['details'], 38)) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-mid)">
                            <?= $log['full_name'] ? e(truncate($log['full_name'], 18)) : '<span class="text-muted">System</span>' ?>
                        </td>
                        <td style="font-size:.74rem;color:var(--text-muted);white-space:nowrap">
                            <?= formatDate($log['created_at'], 'd M, H:i') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:1.5rem;color:var(--text-muted)">No activity yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderAdminShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
