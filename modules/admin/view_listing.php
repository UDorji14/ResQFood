<?php
/**
 * ResQFood — Admin: View a Single Listing
 * Full detail with reservation history and admin actions.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['admin']);

$listingId = (int) ($_GET['id'] ?? 0);
$listing   = getListing($listingId);

if (!$listing) {
    setFlash('error', 'Listing not found.');
    redirect(baseUrl('modules/admin/listings.php'));
}

$pdo = db();

// All reservations on this listing
$resStmt = $pdo->prepare('
    SELECT r.*, u.full_name AS reserver_name, u.email AS reserver_email, u.role AS reserver_role
    FROM   reservations r
    JOIN   users u ON u.id = r.reserved_by
    WHERE  r.listing_id = ?
    ORDER  BY r.reserved_at DESC
');
$resStmt->execute([$listingId]);
$reservations = $resStmt->fetchAll();

// Status logs
$logStmt = $pdo->prepare('
    SELECT rsl.*, u.full_name AS changed_by_name
    FROM   reservation_status_logs rsl
    LEFT   JOIN users u ON u.id = rsl.changed_by
    WHERE  rsl.reservation_id IN (SELECT id FROM reservations WHERE listing_id = ?)
    ORDER  BY rsl.created_at DESC
    LIMIT  20
');
$logStmt->execute([$listingId]);
$statusLogs = $logStmt->fetchAll();

// Impact record for this listing
$impactStmt = $pdo->prepare('SELECT * FROM impact_records WHERE listing_id = ? LIMIT 1');
$impactStmt->execute([$listingId]);
$impact = $impactStmt->fetch();

$pageTitle = 'Listing: ' . $listing['title'];
require_once __DIR__ . '/../../partials/header.php';
?>

<?php
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart(
    'listings',
    'Listing Review',
    'Reservations history and admin controls.'
);
?>

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a> /
        <a href="<?= baseUrl('modules/admin/listings.php') ?>">Listings</a> /
        <span><?= e(truncate($listing['title'], 40)) ?></span>
    </div>
    <div class="page-head__top">
        <div>
            <h1><?= e($listing['title']) ?></h1>
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.3rem">
                <span class="status-badge status-badge--<?= statusClass($listing['status']) ?>"><?= statusLabel($listing['status']) ?></span>
                <?php if ($listing['category']): ?>
                    <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)"><?= e($listing['category']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:.65rem">
            <?php if (in_array($listing['status'], ['available', 'reserved'])): ?>
            <form method="POST" action="<?= baseUrl('modules/admin/actions.php') ?>"
                  onsubmit="return confirm('Cancel this listing? Active reservations will be notified.')">
                <input type="hidden" name="action"     value="listing_cancel">
                <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="redirect"   value="modules/admin/view_listing.php?id=<?= $listingId ?>">
                <button class="btn btn-danger" type="submit">Admin Cancel Listing</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">

    <!-- Main -->
    <div>

        <!-- Listing details -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><h3>Listing Details</h3></div>
            <div class="card-body">
                <?php if ($listing['primary_image']): ?>
                <img src="<?= baseUrl(e($listing['primary_image'])) ?>"
                     alt="<?= e($listing['title']) ?>"
                     style="width:100%;max-height:240px;object-fit:cover;border-radius:var(--r-md);margin-bottom:1.25rem">
                <?php endif; ?>
                <dl class="listing-meta-list">
                    <dt>Title</dt><dd><?= e($listing['title']) ?></dd>
                    <dt>Category</dt><dd><?= e($listing['category'] ?? '-') ?></dd>
                    <dt>Quantity</dt><dd><?= e($listing['quantity'] . ' ' . $listing['unit']) ?></dd>
                    <dt>Description</dt><dd><?= nl2br(e($listing['description'] ?? '-')) ?></dd>
                    <dt>Pickup address</dt><dd><?= e($listing['pickup_address'] ?? '-') ?></dd>
                    <dt>Pickup label</dt><dd><?= e($listing['pickup_location_label'] ?? '-') ?></dd>
                    <dt>Latitude</dt><dd><?= $listing['pickup_latitude'] !== null ? e((string) $listing['pickup_latitude']) : '-' ?></dd>
                    <dt>Longitude</dt><dd><?= $listing['pickup_longitude'] !== null ? e((string) $listing['pickup_longitude']) : '-' ?></dd>
                    <dt>Pickup window</dt>
                    <dd>
                        <?= formatDate($listing['pickup_start'], 'd M Y, H:i') ?> →
                        <?= formatDate($listing['pickup_end'], 'd M Y, H:i') ?>
                    </dd>
                    <dt>Expires</dt><dd><?= $listing['expiry_time'] ? formatDate($listing['expiry_time'], 'd M Y, H:i') : '-' ?></dd>
                    <dt>Status</dt><dd><span class="status-badge status-badge--<?= statusClass($listing['status']) ?>"><?= statusLabel($listing['status']) ?></span></dd>
                    <dt>Created</dt><dd><?= formatDate($listing['created_at'], 'd M Y, H:i') ?></dd>
                    <dt>Updated</dt><dd><?= formatDate($listing['updated_at'], 'd M Y, H:i') ?></dd>
                </dl>

                <?php if (!empty($listing['pickup_latitude']) && !empty($listing['pickup_longitude'])): ?>
                <div style="margin-top:1rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.6rem">
                        <strong style="font-size:.86rem">Pickup map preview</strong>
                        <button type="button" id="admin-open-google-maps" class="btn btn-outline btn-sm">Open in Google Maps</button>
                    </div>
                    <div id="adminFoodLocationMap"
                         class="listing-map"
                         data-food-location-map="1"
                         data-lat="<?= e((string) $listing['pickup_latitude']) ?>"
                         data-lng="<?= e((string) $listing['pickup_longitude']) ?>"
                         data-address="<?= e($listing['pickup_address'] ?: 'Pickup point') ?>"
                         data-label="<?= e($listing['pickup_location_label'] ?? '') ?>"
                         data-directions-btn-id="admin-open-google-maps"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reservations -->
        <?php if (!empty($reservations)): ?>
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header"><h3>Reservations (<?= count($reservations) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Reserver</th><th>Role</th><th>Code</th><th>Status</th><th>Reserved</th><th>Collected</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $r['reserved_by']) ?>"
                                   style="font-weight:600;color:var(--olive)"><?= e($r['reserver_name']) ?></a>
                                <div style="font-size:.73rem;color:var(--text-muted)"><?= e($r['reserver_email']) ?></div>
                            </td>
                            <td><span class="role-badge role-badge--<?= roleBadgeClass($r['reserver_role']) ?>"><?= roleLabel($r['reserver_role']) ?></span></td>
                            <td><span class="pickup-code" style="font-size:.85rem;padding:.2rem .5rem"><?= e($r['pickup_code']) ?></span></td>
                            <td><span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>"><?= statusLabel($r['reservation_status']) ?></span></td>
                            <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($r['reserved_at'], 'd M Y, H:i') ?></td>
                            <td style="font-size:.78rem;color:var(--text-muted)"><?= $r['collected_at'] ? formatDate($r['collected_at'], 'd M Y, H:i') : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status history -->
        <?php if (!empty($statusLogs)): ?>
        <div class="card">
            <div class="card-header"><h3>Status Log</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Change</th><th>By</th><th>Note</th><th>When</th></tr></thead>
                    <tbody>
                        <?php foreach ($statusLogs as $log): ?>
                        <tr>
                            <td style="font-size:.82rem;white-space:nowrap">
                                <span class="status-badge status-badge--<?= statusClass($log['old_status'] ?? 'default') ?>" style="font-size:.68rem"><?= e($log['old_status'] ?? 'initial') ?></span>
                                → <span class="status-badge status-badge--<?= statusClass($log['new_status']) ?>" style="font-size:.68rem"><?= e($log['new_status']) ?></span>
                            </td>
                            <td style="font-size:.82rem"><?= e($log['changed_by_name'] ?? 'System') ?></td>
                            <td style="font-size:.78rem;color:var(--text-muted)"><?= e($log['note'] ?? '') ?></td>
                            <td style="font-size:.75rem;color:var(--text-muted)"><?= formatDate($log['created_at'], 'd M, H:i') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar: Business + Impact -->
    <div style="display:flex;flex-direction:column;gap:1rem">

        <!-- Business info -->
        <div class="card">
            <div class="card-header"><h3>Business</h3></div>
            <div class="card-body" style="font-size:.875rem">
                <p style="font-weight:700;margin-bottom:.2rem"><?= e($listing['business_name'] ?? $listing['business_owner_name']) ?></p>
                <?php if ($listing['business_type']): ?><p class="text-muted"><?= e($listing['business_type']) ?></p><?php endif; ?>
                <?php if ($listing['business_city']): ?><p class="text-muted"><?= e($listing['business_city']) ?></p><?php endif; ?>
                <a href="<?= baseUrl('modules/admin/view_user.php?id=' . $listing['business_user_id']) ?>"
                   class="btn btn-sm btn-outline" style="margin-top:.75rem">View Owner Profile</a>
            </div>
        </div>

        <!-- Impact for this listing -->
        <?php if ($impact): ?>
        <div class="card" style="background:rgba(74,103,65,.04)">
            <div class="card-header"><h3>Recorded Impact</h3></div>
            <div class="card-body" style="font-size:.875rem">
                <div style="display:flex;flex-direction:column;gap:.5rem">
                    <div style="display:flex;justify-content:space-between">
                        <span class="text-muted">Meals saved~</span>
                        <strong><?= number_format($impact['estimated_meals_saved'], 1) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="text-muted">Food diverted~</span>
                        <strong><?= number_format($impact['estimated_kg_saved'], 2) ?> kg</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="text-muted">CO₂ reduced~</span>
                        <strong><?= number_format($impact['estimated_co2_reduced'], 2) ?> kg</strong>
                    </div>
                </div>
                <p style="font-size:.72rem;color:var(--text-muted);margin-top:.75rem">~ Approximate estimates only.</p>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php renderAdminShellEnd(); ?>
<?php if (!empty($listing['pickup_latitude']) && !empty($listing['pickup_longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('js/food-location-map.js') ?>"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
