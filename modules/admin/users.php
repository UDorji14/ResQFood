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
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb"><a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a> / <span>Users</span></div>
            <h1>User Management</h1>
            <p class="text-muted"><?= number_format($total) ?> user<?= $total !== 1 ? 's' : '' ?> found.</p>
        </div>
    </div>
</div>

<!-- Filter bar -->
<form method="GET" action="" class="filter-bar">
    <div class="form-group">
        <label class="form-label" for="q">Search</label>
        <input type="text" id="q" name="q" class="form-control"
               value="<?= e($filters['keyword']) ?>" placeholder="Name or email…">
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
    <?php if ($filters['role'] || $filters['status'] || $filters['keyword']): ?>
        <a href="<?= baseUrl('modules/admin/users.php') ?>" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 64 64" width="48" fill="none"><circle cx="32" cy="24" r="12" stroke="#4a6741" stroke-width="2"/><path d="M8 56c0-13.25 10.75-24 24-24s24 10.75 24 24" stroke="#4a6741" stroke-width="2" stroke-linecap="round"/></svg>
        <p>No users match your filters.</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Profile</th>
                    <th>Activity</th>
                    <th>Joined</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>"
                           style="font-weight:700;color:var(--olive)"><?= e($u['full_name']) ?></a>
                        <div style="font-size:.75rem;color:var(--text-muted)"><?= e($u['email']) ?></div>
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
                    <td>
                        <?php if ($u['role'] === 'business'): ?>
                            <span style="font-size:.78rem"><?= e($u['business_name'] ?? '—') ?></span><br>
                            <span class="status-badge status-badge--<?= statusClass($u['biz_verif'] ?? 'pending') ?>" style="font-size:.66rem">
                                <?= statusLabel($u['biz_verif'] ?? 'pending') ?>
                            </span>
                        <?php elseif ($u['role'] === 'charity'): ?>
                            <span style="font-size:.78rem"><?= e($u['organization_name'] ?? '—') ?></span><br>
                            <span class="status-badge status-badge--<?= statusClass($u['charity_verif'] ?? 'pending') ?>" style="font-size:.66rem">
                                <?= statusLabel($u['charity_verif'] ?? 'pending') ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:.8rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem">
                        <?php if ($u['listing_count'] > 0): ?>
                            <span style="color:var(--olive);font-weight:700"><?= $u['listing_count'] ?></span> listings<br>
                        <?php endif; ?>
                        <?php if ($u['res_count'] > 0): ?>
                            <span style="color:var(--olive);font-weight:700"><?= $u['res_count'] ?></span> reservations
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($u['created_at'], 'd M Y') ?></td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $u['id']) ?>"
                           class="btn btn-sm btn-outline">View</a>

                        <?php if ($u['role'] !== 'admin'): ?>
                        <?php if ($u['status'] === 'active'): ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline"
                              onsubmit="return confirm('Suspend <?= e(addslashes($u['full_name'])) ?>?')">
                            <input type="hidden" name="action"      value="user_status">
                            <input type="hidden" name="user_id"     value="<?= $u['id'] ?>">
                            <input type="hidden" name="new_status"  value="suspended">
                            <input type="hidden" name="csrf_token"  value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"    value="<?= e(currentPage()) ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Suspend</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:inline">
                            <input type="hidden" name="action"      value="user_status">
                            <input type="hidden" name="user_id"     value="<?= $u['id'] ?>">
                            <input type="hidden" name="new_status"  value="active">
                            <input type="hidden" name="csrf_token"  value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect"    value="<?= e(currentPage()) ?>">
                            <button class="btn btn-sm btn-outline" type="submit">Activate</button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--line)">
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= userListUrl($page - 1, $filters) ?>">&larr;</a>
            <?php else: ?>
                <span class="disabled">&larr;</span>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= userListUrl($p, $filters) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= userListUrl($page + 1, $filters) ?>">&rarr;</a>
            <?php else: ?>
                <span class="disabled">&rarr;</span>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
