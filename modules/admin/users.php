<?php
/**
 * ResQFood — Admin User Management
 * List, search, filter, and manage all platform users.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';

requireRole(['admin']);

$perPage = 20;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$filters = [
    'role'    => sanitize($_GET['role']    ?? ''),
    'status'  => sanitize($_GET['status']  ?? ''),
    'keyword' => sanitize($_GET['q']       ?? ''),
];

$users      = adminGetUsers($filters, $perPage, $offset);
$total      = adminCountUsers($filters);
$totalPages = (int) ceil($total / $perPage);

$allRoles    = ['business', 'general_user', 'charity', 'admin'];
$allStatuses = ['active', 'pending', 'inactive', 'suspended'];

function userListUrl(int $pg, array $f): string {
    $q = array_filter(['page' => $pg > 1 ? $pg : null, 'role' => $f['role'], 'status' => $f['status'], 'q' => $f['keyword']]);
    return baseUrl('modules/admin/users.php') . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle = 'User Management';
$hasFilters = $filters['role'] || $filters['status'] || $filters['keyword'];
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="breadcrumb">
    <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a>
    <span>Users</span>
</div>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>User Management</h1>
            <p class="text-muted">
                <strong style="color:var(--text-mid)"><?= number_format($total) ?></strong>
                user<?= $total !== 1 ? 's' : '' ?> found<?= $hasFilters ? ' — filtered' : '' ?>.
            </p>
        </div>
    </div>
</div>

<!-- ── Filter strip ────────────────────────────────────────── -->
<form method="GET" action="">
    <div class="admin-filter">
        <div class="form-group">
            <label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" class="form-control"
                   value="<?= e($filters['keyword']) ?>" placeholder="Name or email…" autocomplete="off">
        </div>
        <div class="form-group" style="max-width:160px">
            <label class="form-label" for="role">Role</label>
            <select id="role" name="role" class="form-control">
                <option value="">All roles</option>
                <?php foreach ($allRoles as $r): ?>
                    <option value="<?= e($r) ?>" <?= $filters['role'] === $r ? 'selected' : '' ?>><?= roleLabel($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="max-width:160px">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All statuses</option>
                <?php foreach ($allStatuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($hasFilters): ?>
            <a href="<?= baseUrl('modules/admin/users.php') ?>" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card" style="overflow:hidden">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 80 80" width="64" fill="none">
            <circle cx="40" cy="30" r="15" stroke="#4a6741" stroke-width="2"/>
            <path d="M10 70c0-16.6 13.4-30 30-30s30 13.4 30 30" stroke="#4a6741" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.3rem">No users match your filters</h3>
        <p style="color:var(--text-muted);font-size:.85rem">Try adjusting the search or role/status filter.</p>
        <?php if ($hasFilters): ?>
        <a href="<?= baseUrl('modules/admin/users.php') ?>" class="btn btn-outline">Clear filters</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Profile / Org</th>
                    <th>Activity</th>
                    <th>Joined</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar user-avatar--<?= e($u['role']) ?>">
                                <?= strtoupper(mb_substr($u['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <a class="user-cell__name"
                                   href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>">
                                    <?= e(truncate($u['full_name'], 24)) ?>
                                </a>
                                <span class="user-cell__email"><?= e(truncate($u['email'], 30)) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge role-badge--<?= roleBadgeClass($u['role']) ?>">
                            <?= roleLabel($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-badge--<?= statusClass($u['status']) ?>">
                            <?= statusLabel($u['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:.82rem">
                        <?php if ($u['role'] === 'business' && ($u['business_name'] ?? null)): ?>
                            <div style="font-weight:600;color:var(--text-mid)"><?= e(truncate($u['business_name'], 22)) ?></div>
                            <span class="status-badge status-badge--<?= statusClass($u['biz_verif'] ?? 'pending') ?>" style="font-size:.65rem;margin-top:.2rem">
                                <?= statusLabel($u['biz_verif'] ?? 'pending') ?>
                            </span>
                        <?php elseif ($u['role'] === 'charity' && ($u['organization_name'] ?? null)): ?>
                            <div style="font-weight:600;color:var(--text-mid)"><?= e(truncate($u['organization_name'], 22)) ?></div>
                            <span class="status-badge status-badge--<?= statusClass($u['charity_verif'] ?? 'pending') ?>" style="font-size:.65rem;margin-top:.2rem">
                                <?= statusLabel($u['charity_verif'] ?? 'pending') ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem">
                        <?php if (($u['listing_count'] ?? 0) > 0): ?>
                        <div style="color:var(--olive);font-weight:700"><?= $u['listing_count'] ?> <span style="font-weight:400;color:var(--text-muted)">listings</span></div>
                        <?php endif; ?>
                        <?php if (($u['res_count'] ?? 0) > 0): ?>
                        <div style="color:var(--olive);font-weight:700"><?= $u['res_count'] ?> <span style="font-weight:400;color:var(--text-muted)">reservations</span></div>
                        <?php endif; ?>
                        <?php if (!($u['listing_count'] ?? 0) && !($u['res_count'] ?? 0)): ?>
                        <span class="text-muted">No activity</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap">
                        <?= formatDate($u['created_at'], 'd M Y') ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>"
                           class="btn btn-xs btn-outline">View</a>

                        <?php if ($u['role'] !== 'admin'): ?>
                        <?php if ($u['status'] === 'active'): ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline"
                              data-confirm="Suspend <?= e(addslashes($u['full_name'])) ?>?">
                            <input type="hidden" name="action"     value="user_status">
                            <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                            <input type="hidden" name="new_status" value="suspended">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"   value="<?= e(currentPage()) ?>">
                            <button class="btn btn-xs btn-danger" type="submit">Suspend</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline">
                            <input type="hidden" name="action"     value="user_status">
                            <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                            <input type="hidden" name="new_status" value="active">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"   value="<?= e(currentPage()) ?>">
                            <button class="btn btn-xs btn-outline" type="submit">Activate</button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-pagination">
        <nav class="pagination">
            <?php if ($page > 1): ?><a href="<?= userListUrl($page - 1, $filters) ?>">&larr;</a><?php else: ?><span class="disabled">&larr;</span><?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <?php if ($p === $page): ?><span class="current"><?= $p ?></span><?php else: ?><a href="<?= userListUrl($p, $filters) ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= userListUrl($page + 1, $filters) ?>">&rarr;</a><?php else: ?><span class="disabled">&rarr;</span><?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
