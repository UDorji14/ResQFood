<?php
/**
 * ResQFood — Listing Detail Page
 * Accessible to all logged-in users.
 * Shows full listing info and — for eligible users — the Reserve button.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireLogin();

$listingId = (int) ($_GET['id'] ?? 0);
$listing   = getListing($listingId);

if (!$listing) {
    setFlash('error', 'Listing not found.');
    redirect(baseUrl('modules/listings/browse.php'));
}

$uid  = currentUserId();
$role = currentUserRole();

// Check reservation eligibility
$reserveError = '';
$myRes        = null;

if (in_array($role, ['general_user', 'charity'])) {
    $reserveError = canReserve($uid, $role, $listing);

    // Check if THIS user already has a reservation
    $stmt = db()->prepare('
        SELECT r.* FROM reservations r
        WHERE  r.listing_id = ? AND r.reserved_by = ?
        LIMIT 1
    ');
    $stmt->execute([$listingId, $uid]);
    $myRes = $stmt->fetch() ?: null;
    if ($myRes) {
        $reserveError = 'already_reserved'; // sentinel so we show pickup code
    }
}

// For business owner — show reservations on this listing
$listingReservations = [];
if ($role === 'business' && (int) $listing['business_user_id'] === $uid) {
    $stmt = db()->prepare('
        SELECT r.*, u.full_name AS reserver_name, u.email AS reserver_email, u.role AS reserver_role
        FROM   reservations r
        JOIN   users u ON u.id = r.reserved_by
        WHERE  r.listing_id = ?
        ORDER  BY r.reserved_at DESC
    ');
    $stmt->execute([$listingId]);
    $listingReservations = $stmt->fetchAll();
}

$pageTitle = $listing['title'];
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="breadcrumb">
        <?php if ($role === 'business'): ?>
            <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a>
        <?php else: ?>
            <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Listings</a>
        <?php endif; ?>
        / <span><?= e(truncate($listing['title'], 40)) ?></span>
    </div>
</div>

<div class="listing-detail">

    <!-- ── Main Content ── -->
    <div>

        <!-- Image -->
        <div class="listing-detail__image">
            <?php if ($listing['primary_image']): ?>
                <img src="<?= baseUrl(e($listing['primary_image'])) ?>"
                     alt="<?= e($listing['title']) ?>">
            <?php else: ?>
                <?= listingImageTag(null, '', '') ?>
            <?php endif; ?>
        </div>

        <!-- Details card -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
                <div>
                    <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                        <h2 style="font-size:1.3rem;margin:0"><?= e($listing['title']) ?></h2>
                        <?php if ($listing['category']): ?>
                            <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:.2rem .7rem;border-radius:var(--r-pill);background:rgba(74,103,65,.1);color:var(--olive)">
                                <?= e($listing['category']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.82rem;color:var(--text-muted);margin-top:.25rem">
                        By <strong><?= e($listing['business_name'] ?? $listing['business_owner_name']) ?></strong>
                        <?php if ($listing['business_city']): ?> · <?= e($listing['business_city']) ?><?php endif; ?>
                    </div>
                </div>
                <span class="status-badge status-badge--<?= statusClass($listing['status']) ?>">
                    <?= statusLabel($listing['status']) ?>
                </span>
            </div>
            <div class="card-body">

                <?php if ($listing['description']): ?>
                <p style="color:var(--text-mid);line-height:1.7;margin-bottom:1.25rem"><?= nl2br(e($listing['description'])) ?></p>
                <?php endif; ?>

                <dl class="listing-meta-list">
                    <dt>Quantity</dt>
                    <dd><?= e($listing['quantity'] . ' ' . $listing['unit']) ?></dd>

                    <dt>Pickup window</dt>
                    <dd>
                        <?= formatDate($listing['pickup_start'], 'd M Y, H:i') ?>
                        &rarr; <?= formatDate($listing['pickup_end'], 'd M Y, H:i') ?>
                        <br>
                        <span style="font-size:.78rem;color:var(--olive);font-weight:600">
                            <?= pickupTimeLabel($listing['pickup_start'], $listing['pickup_end']) ?>
                        </span>
                    </dd>

                    <?php if ($listing['pickup_address'] || $listing['business_city']): ?>
                    <dt>Pickup address</dt>
                    <dd><?= e($listing['pickup_address'] ?: $listing['business_city']) ?></dd>
                    <?php endif; ?>

                    <?php if ($listing['default_pickup_notes']): ?>
                    <dt>Pickup notes</dt>
                    <dd><?= nl2br(e($listing['default_pickup_notes'])) ?></dd>
                    <?php endif; ?>

                    <?php if ($listing['expiry_time']): ?>
                    <dt>Food expires</dt>
                    <dd><?= formatDate($listing['expiry_time'], 'd M Y, H:i') ?></dd>
                    <?php endif; ?>

                    <dt>Posted</dt>
                    <dd><?= formatDate($listing['created_at'], 'd M Y, H:i') ?></dd>
                </dl>

            </div>
        </div>

        <!-- Business's reservations table -->
        <?php if (!empty($listingReservations)): ?>
        <div class="card">
            <div class="card-header"><h3>Reservations for this listing</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Role</th><th>Status</th><th>Code</th><th>Reserved</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listingReservations as $r): ?>
                        <tr>
                            <td><?= e($r['reserver_name']) ?></td>
                            <td><span class="role-badge role-badge--<?= e(roleBadgeClass($r['reserver_role'])) ?>"><?= e(roleLabel($r['reserver_role'])) ?></span></td>
                            <td><span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>"><?= statusLabel($r['reservation_status']) ?></span></td>
                            <td><?= pickupCodeBadge($r['pickup_code']) ?></td>
                            <td style="font-size:.82rem"><?= formatDate($r['reserved_at'], 'd M, H:i') ?></td>
                            <td>
                                <?php if ($r['reservation_status'] === 'reserved'): ?>
                                <a href="<?= baseUrl('modules/reservations/confirm_pickup.php?id=' . $r['id']) ?>"
                                   class="btn btn-sm btn-primary">Confirm Pickup</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ── Sidebar ── -->
    <div style="display:flex;flex-direction:column;gap:1rem">

        <!-- Reserve / Status panel -->
        <div class="card">
            <div class="card-body" style="padding:1.5rem">

                <?php if ($role === 'business' && (int) $listing['business_user_id'] === $uid): ?>
                    <!-- Owner: editing actions -->
                    <p class="text-muted" style="font-size:.82rem;margin-bottom:1rem">This is your listing.</p>
                    <?php if (in_array($listing['status'], ['available', 'reserved'])): ?>
                    <a href="<?= baseUrl('modules/listings/edit.php?id=' . $listingId) ?>"
                       class="btn btn-outline btn-block">Edit Listing</a>
                    <?php endif; ?>
                    <?php if (in_array($listing['status'], ['available', 'expired', 'cancelled'])): ?>
                    <form method="POST" action="<?= baseUrl('modules/listings/delete.php') ?>"
                          style="margin-top:.65rem"
                          onsubmit="return confirm('Delete this listing?')">
                        <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-danger btn-block" type="submit">Delete Listing</button>
                    </form>
                    <?php endif; ?>

                <?php elseif ($myRes !== null && $myRes !== false): ?>
                    <!-- Already reserved — show pickup code -->
                    <div class="pickup-code-section">
                        <div style="font-size:.8rem;font-weight:700;color:var(--text-muted);margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.07em">Your Pickup Code</div>
                        <?= pickupCodeBadge($myRes['pickup_code']) ?>
                        <p>Show this code to the business when you collect your order.</p>
                    </div>
                    <div style="font-size:.82rem;color:var(--text-muted);text-align:center;margin-bottom:.75rem">
                        Status: <strong><?= statusLabel($myRes['reservation_status']) ?></strong>
                    </div>
                    <?php if ($myRes['reservation_status'] === 'reserved'): ?>
                    <form method="POST" action="<?= baseUrl('modules/reservations/cancel.php') ?>"
                          onsubmit="return confirm('Cancel this reservation? The listing will be made available again.')">
                        <input type="hidden" name="reservation_id" value="<?= $myRes['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-outline btn-block" type="submit"
                                style="color:var(--terra);border-color:rgba(181,96,74,.4)">
                            Cancel Reservation
                        </button>
                    </form>
                    <?php endif; ?>

                <?php elseif (in_array($role, ['general_user', 'charity']) && $reserveError === ''): ?>
                    <!-- Reserve action -->
                    <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.5">
                        Reserve now to secure a pickup slot. A unique pickup code will be generated for you.
                    </p>
                    <form method="POST" action="<?= baseUrl('modules/reservations/reserve.php') ?>">
                        <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-primary btn-block btn-lg" type="submit">
                            Reserve This Listing
                        </button>
                    </form>

                <?php elseif (!isLoggedIn()): ?>
                    <p class="text-muted">Please <a href="<?= baseUrl('login.php') ?>">log in</a> to reserve.</p>

                <?php elseif ($listing['status'] !== 'available'): ?>
                    <div class="notice notice--info" style="margin-bottom:0">
                        <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <div class="notice__body">
                            <strong>Not reservable</strong>
                            This listing is currently <?= e(statusLabel($listing['status'])) ?>.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="notice notice--warning" style="margin-bottom:0">
                        <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <div class="notice__body"><?= e($reserveError) ?></div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Business info card -->
        <div class="card">
            <div class="card-header"><h3>From the Business</h3></div>
            <div class="card-body" style="font-size:.875rem">
                <p style="font-weight:700;margin-bottom:.2rem"><?= e($listing['business_name'] ?? $listing['business_owner_name']) ?></p>
                <?php if ($listing['business_type']): ?><p class="text-muted"><?= e($listing['business_type']) ?></p><?php endif; ?>
                <?php if ($listing['business_city']): ?><p class="text-muted"><?= e($listing['business_city']) ?></p><?php endif; ?>
                <?php if ($listing['verification_status'] === 'verified'): ?>
                <div style="margin-top:.75rem;display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--olive);font-weight:700">
                    <svg viewBox="0 0 16 16" width="14" fill="none"><path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Verified business
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
