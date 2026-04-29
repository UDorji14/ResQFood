<?php
/**
 * ResQFood — Admin: View & Manage a Single User
 * Shows full profile, activity stats, and admin controls.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';

requireRole(['admin']);

$userId = (int) ($_GET['id'] ?? 0);
$user   = adminGetUser($userId);

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(baseUrl('modules/admin/users.php'));
}

$pdo = db();

// Activity stats
$listingCount = (int) $pdo->prepare('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?')->execute([$userId]) ? 0 : 0;
$lcStmt = $pdo->prepare('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?');
$lcStmt->execute([$userId]);
$listingCount = (int) $lcStmt->fetchColumn();

$resStmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?');
$resStmt->execute([$userId]);
$resCount = (int) $resStmt->fetchColumn();

// Recent listings (if business)
$recentListings = [];
if ($user['role'] === 'business') {
    $rlStmt = $pdo->prepare('SELECT * FROM food_listings WHERE business_user_id = ? ORDER BY created_at DESC LIMIT 8');
    $rlStmt->execute([$userId]);
    $recentListings = $rlStmt->fetchAll();
}

// Recent reservations (if general_user or charity)
$recentReservations = [];
if (in_array($user['role'], ['general_user', 'charity'])) {
    $rrStmt = $pdo->prepare('
        SELECT r.*, fl.title, fl.quantity, fl.unit
        FROM   reservations r JOIN food_listings fl ON fl.id = r.listing_id
        WHERE  r.reserved_by = ?
        ORDER  BY r.reserved_at DESC LIMIT 8
    ');
    $rrStmt->execute([$userId]);
    $recentReservations = $rrStmt->fetchAll();
}

$statusOptions = ['active', 'pending', 'inactive', 'suspended'];

$pageTitle = 'User: ' . $user['full_name'];
require_once __DIR__ . '/../../partials/header.php';
?>

<?php
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart(
    'users',
    'User Profile',
    'Admin account view, verification controls, and activity history.'
);
?>

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a> /
        <a href="<?= baseUrl('modules/admin/users.php') ?>">Users</a> /
        <span><?= e($user['full_name']) ?></span>
    </div>
    <div class="page-head__top">
        <div>
            <h1><?= e($user['full_name']) ?></h1>
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.25rem">
                <span class="role-badge role-badge--<?= roleBadgeClass($user['role']) ?>"><?= roleLabel($user['role']) ?></span>
                <span class="status-badge status-badge--<?= statusClass($user['status']) ?>"><?= statusLabel($user['status']) ?></span>
                <span class="text-muted" style="font-size:.82rem">Joined <?= formatDate($user['created_at'], 'd M Y') ?></span>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">

    <!-- Left: Info + Activity -->
    <div>

        <!-- Core info -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><h3>Account Details</h3></div>
            <div class="card-body">
                <dl class="listing-meta-list">
                    <dt>Full name</dt><dd><?= e($user['full_name']) ?></dd>
                    <dt>Email</dt><dd><?= e($user['email']) ?></dd>
                    <dt>Phone</dt><dd><?= e($user['phone'] ?? '-') ?></dd>
                    <dt>Status</dt><dd><span class="status-badge status-badge--<?= statusClass($user['status']) ?>"><?= statusLabel($user['status']) ?></span></dd>
                    <dt>Role</dt><dd><span class="role-badge role-badge--<?= roleBadgeClass($user['role']) ?>"><?= roleLabel($user['role']) ?></span></dd>
                    <dt>Registered</dt><dd><?= formatDate($user['created_at'], 'd M Y, H:i') ?></dd>
                    <dt>Last updated</dt><dd><?= formatDate($user['updated_at'], 'd M Y, H:i') ?></dd>
                    <dt>Activity</dt>
                    <dd>
                        <?= $listingCount ?> listing<?= $listingCount !== 1 ? 's' : '' ?> &middot;
                        <?= $resCount ?> reservation<?= $resCount !== 1 ? 's' : '' ?>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Business profile -->
        <?php if ($user['role'] === 'business' && $user['biz_profile_id']): ?>
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h3>Business Profile</h3>
                <span class="status-badge status-badge--<?= statusClass($user['biz_verif']) ?>">
                    <?= statusLabel($user['biz_verif']) ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="listing-meta-list">
                    <dt>Business name</dt><dd><?= e($user['business_name'] ?? '-') ?></dd>
                    <dt>Type</dt><dd><?= e($user['business_type'] ?? '-') ?></dd>
                    <dt>Address</dt><dd><?= e($user['biz_address'] ?? '-') ?></dd>
                    <dt>City</dt><dd><?= e($user['biz_city'] ?? '-') ?></dd>
                    <dt>Description</dt><dd><?= e($user['biz_desc'] ?? '-') ?></dd>
                    <dt>Verification</dt>
                    <dd>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap">
                            <input type="hidden" name="action"   value="verify_profile">
                            <input type="hidden" name="user_id"  value="<?= $userId ?>">
                            <input type="hidden" name="type"     value="business">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect" value="modules/admin/view_user.php?id=<?= $userId ?>">
                            <select name="verif_status" class="form-control" style="width:auto">
                                <?php foreach (['pending', 'verified', 'rejected'] as $vs): ?>
                                    <option value="<?= $vs ?>" <?= $user['biz_verif'] === $vs ? 'selected' : '' ?>><?= statusLabel($vs) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Update</button>
                        </form>
                    </dd>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <!-- Charity profile -->
        <?php if ($user['role'] === 'charity' && $user['charity_profile_id']): ?>
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h3>Charity Profile</h3>
                <span class="status-badge status-badge--<?= statusClass($user['charity_verif']) ?>">
                    <?= statusLabel($user['charity_verif']) ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="listing-meta-list">
                    <dt>Organisation</dt><dd><?= e($user['organization_name'] ?? '-') ?></dd>
                    <dt>Contact</dt><dd><?= e($user['contact_person'] ?? '-') ?></dd>
                    <dt>Address</dt><dd><?= e($user['charity_address'] ?? '-') ?></dd>
                    <dt>City</dt><dd><?= e($user['charity_city'] ?? '-') ?></dd>
                    <dt>Description</dt><dd><?= e($user['charity_desc'] ?? '-') ?></dd>
                    <dt>Verification</dt>
                    <dd>
                        <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap">
                            <input type="hidden" name="action"   value="verify_profile">
                            <input type="hidden" name="user_id"  value="<?= $userId ?>">
                            <input type="hidden" name="type"     value="charity">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="redirect" value="modules/admin/view_user.php?id=<?= $userId ?>">
                            <select name="verif_status" class="form-control" style="width:auto">
                                <?php foreach (['pending', 'verified', 'rejected'] as $vs): ?>
                                    <option value="<?= $vs ?>" <?= $user['charity_verif'] === $vs ? 'selected' : '' ?>><?= statusLabel($vs) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Update</button>
                        </form>
                    </dd>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity table -->
        <?php if (!empty($recentListings)): ?>
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <h3>Recent Listings</h3>
                <a href="<?= baseUrl('modules/admin/listings.php?user_id=' . $userId) ?>" style="font-size:.8rem;color:var(--olive)">All →</a>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Title</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentListings as $l): ?>
                        <tr>
                            <td><a href="<?= baseUrl('modules/admin/view_listing.php?id=' . $l['id']) ?>" style="font-weight:600;color:var(--olive)"><?= e(truncate($l['title'], 40)) ?></a></td>
                            <td><span class="status-badge status-badge--<?= statusClass($l['status']) ?>"><?= statusLabel($l['status']) ?></span></td>
                            <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($l['created_at'], 'd M Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($recentReservations)): ?>
        <div class="card">
            <div class="card-header"><h3>Recent Reservations</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Listing</th><th>Qty</th><th>Status</th><th>Reserved</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentReservations as $r): ?>
                        <tr>
                            <td style="font-size:.87rem;font-weight:600"><?= e(truncate($r['title'], 35)) ?></td>
                            <td style="font-size:.82rem"><?= e($r['quantity'] . ' ' . $r['unit']) ?></td>
                            <td><span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>"><?= statusLabel($r['reservation_status']) ?></span></td>
                            <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($r['reserved_at'], 'd M Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Admin Controls -->
    <?php if ($user['role'] !== 'admin'): ?>
    <div>
        <div class="card">
            <div class="card-header"><h3>Account Control</h3></div>
            <div class="card-body">
                <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1rem">
                    Current status: <strong><?= statusLabel($user['status']) ?></strong>
                </p>
                <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>">
                    <input type="hidden" name="action"   value="user_status">
                    <input type="hidden" name="user_id"  value="<?= $userId ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="redirect" value="modules/admin/view_user.php?id=<?= $userId ?>">
                    <div class="form-group">
                        <label class="form-label">Set Status</label>
                        <select name="new_status" class="form-control">
                            <?php foreach ($statusOptions as $s): ?>
                                <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admin Note (optional)</label>
                        <textarea name="admin_note" class="form-control" rows="2" placeholder="Reason for this action…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"
                            onclick="return confirm('Update this user\'s status?')">
                        Apply Status Change
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php renderAdminShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
