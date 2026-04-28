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

<div class="breadcrumb">
    <?php if ($role === 'business'): ?>
        <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a>
    <?php else: ?>
        <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Listings</a>
    <?php endif; ?>
    <span><?= e(truncate($listing['title'], 42)) ?></span>
</div>

<!-- ── Hero image ────────────────────────────────────────────── -->
<div class="listing-hero">
    <?php if (!empty($listing['primary_image'])): ?>
        <img src="<?= baseUrl(e($listing['primary_image'])) ?>"
             alt="<?= e($listing['title']) ?>">
    <?php else: ?>
        <div class="listing-hero__placeholder">
            <svg viewBox="0 0 80 80" width="72" fill="none" style="opacity:.18">
                <path d="M14 56C14 40 24 18 40 12c16 6 26 28 26 44" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round"/>
                <ellipse cx="40" cy="56" rx="26" ry="10" stroke="#4a6741" stroke-width="2"/>
                <path d="M28 44c3-6 7-11 12-12m12 0c3 2 7 6 10 12" stroke="#4a6741" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
    <?php endif; ?>
    <?php if ($listing['category']): ?>
        <span class="listing-hero__cat"><?= e($listing['category']) ?></span>
    <?php endif; ?>
    <div class="listing-hero__status">
        <span class="status-badge status-badge--<?= statusClass($listing['status']) ?>">
            <?= statusLabel($listing['status']) ?>
        </span>
    </div>
</div>

<!-- ── Two-column layout ─────────────────────────────────────── -->
<div class="listing-view-grid">

    <!-- ── Main Content ────────────────────────────────────── -->
    <div>

        <!-- Title + description -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-body" style="padding:1.5rem 1.6rem">

                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.85rem">
                    <div>
                        <h1 style="font-size:1.45rem;font-family:var(--f-display);font-weight:700;line-height:1.25;margin:0 0 .3rem">
                            <?= e($listing['title']) ?>
                        </h1>
                        <div style="font-size:.83rem;color:var(--text-muted)">
                            By <strong style="color:var(--text-mid)"><?= e($listing['business_name'] ?? $listing['business_owner_name']) ?></strong>
                            <?php if ($listing['business_city']): ?>&nbsp;&middot;&nbsp;<?= e($listing['business_city']) ?><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($listing['description']): ?>
                <p style="color:var(--text-mid);line-height:1.75;font-size:.93rem;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--line)">
                    <?= nl2br(e($listing['description'])) ?>
                </p>
                <?php endif; ?>

                <!-- Meta grid -->
                <div class="listing-meta-grid">
                    <div class="listing-meta-item">
                        <div class="listing-meta-item__label">Quantity</div>
                        <div class="listing-meta-item__value listing-meta-item__value--accent">
                            <?= e($listing['quantity'] . ' ' . $listing['unit']) ?>
                        </div>
                    </div>
                    <div class="listing-meta-item">
                        <div class="listing-meta-item__label">Pickup opens</div>
                        <div class="listing-meta-item__value">
                            <?= formatDate($listing['pickup_start'], 'd M, H:i') ?>
                        </div>
                    </div>
                    <div class="listing-meta-item">
                        <div class="listing-meta-item__label">Pickup closes</div>
                        <div class="listing-meta-item__value">
                            <?= formatDate($listing['pickup_end'], 'd M, H:i') ?>
                        </div>
                    </div>
                    <?php if ($listing['expiry_time']): ?>
                    <div class="listing-meta-item">
                        <div class="listing-meta-item__label">Expires at</div>
                        <div class="listing-meta-item__value"><?= formatDate($listing['expiry_time'], 'd M, H:i') ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($listing['pickup_address'] || $listing['business_city']): ?>
                    <div class="listing-meta-item" style="grid-column:1/-1">
                        <div class="listing-meta-item__label">Pickup location</div>
                        <div class="listing-meta-item__value">
                            <?= e($listing['pickup_address'] ?: $listing['business_city']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pickup window callout -->
                <div style="display:flex;align-items:center;gap:.55rem;font-size:.82rem;color:var(--olive);font-weight:600;background:rgba(74,103,65,.06);border-radius:var(--r-md);padding:.6rem .9rem">
                    <svg viewBox="0 0 18 18" width="14" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M9 5.5V9l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <?= pickupTimeLabel($listing['pickup_start'], $listing['pickup_end']) ?>
                </div>

                <?php if ($listing['default_pickup_notes']): ?>
                <div style="margin-top:.85rem;padding:.85rem 1rem;background:rgba(248,244,234,.7);border-radius:var(--r-md);border:1px solid var(--line)">
                    <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.35rem">Pickup Notes</div>
                    <p style="font-size:.85rem;color:var(--text-mid);line-height:1.6;margin:0"><?= nl2br(e($listing['default_pickup_notes'])) ?></p>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Reservations table (business owner view) -->
        <?php if (!empty($listingReservations)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Reservations</h3>
                <span class="status-badge status-badge--amber"><?= count($listingReservations) ?> total</span>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Pickup Code</th>
                            <th>Reserved</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listingReservations as $r): ?>
                        <tr>
                            <td style="font-weight:600"><?= e($r['reserver_name']) ?></td>
                            <td>
                                <span class="role-badge role-badge--<?= e(roleBadgeClass($r['reserver_role'])) ?>">
                                    <?= e(roleLabel($r['reserver_role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-badge--<?= statusClass($r['reservation_status']) ?>">
                                    <?= statusLabel($r['reservation_status']) ?>
                                </span>
                            </td>
                            <td><?= pickupCodeBadge($r['pickup_code']) ?></td>
                            <td style="font-size:.8rem;color:var(--text-muted)">
                                <?= formatDate($r['reserved_at'], 'd M, H:i') ?>
                            </td>
                            <td style="text-align:right">
                                <?php if ($r['reservation_status'] === 'reserved'): ?>
                                <a href="<?= baseUrl('modules/reservations/confirm_pickup.php?id=' . $r['id']) ?>"
                                   class="btn btn-sm btn-primary">Confirm Pickup</a>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:.8rem">—</span>
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

    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <div class="listing-sidebar-sticky">

        <!-- ── Action panel ──────────────────────────────────── -->
        <?php if ($role === 'business' && (int) $listing['business_user_id'] === $uid): ?>

        <div class="reserve-panel" style="margin-bottom:1rem">
            <div class="reserve-panel__strip">
                <span style="font-size:.78rem;font-weight:700;color:var(--text-mid)">Your Listing</span>
                <span class="status-badge status-badge--<?= statusClass($listing['status']) ?>"><?= statusLabel($listing['status']) ?></span>
            </div>
            <div class="reserve-panel__body">
                <?php if (in_array($listing['status'], ['available', 'reserved'])): ?>
                <a href="<?= baseUrl('modules/listings/edit.php?id=' . $listingId) ?>"
                   class="btn btn-outline btn-block" style="margin-bottom:.6rem">
                    <svg viewBox="0 0 18 18" width="14" fill="none" style="margin-right:.3rem"><path d="M13 3l2 2-9 9H4v-2L13 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    Edit Listing
                </a>
                <?php endif; ?>
                <?php if (in_array($listing['status'], ['available', 'expired', 'cancelled'])): ?>
                <form method="POST" action="<?= baseUrl('modules/listings/delete.php') ?>"
                      data-confirm="Delete this listing? This cannot be undone.">
                    <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="btn btn-danger btn-block" type="submit">
                        <svg viewBox="0 0 18 18" width="14" fill="none" style="margin-right:.3rem"><path d="M3 5h12M8 5V3h2v2m-5 1v9h6V6H5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Delete Listing
                    </button>
                </form>
                <?php endif; ?>
                <p class="text-muted" style="font-size:.77rem;text-align:center;margin-top:.75rem;line-height:1.5">
                    <?= e(number_format((float)$listing['quantity'], 0)) ?>&nbsp;<?= e($listing['unit']) ?> available
                </p>
            </div>
        </div>

        <?php elseif ($myRes !== null && $myRes !== false): ?>

        <!-- Already reserved: show pickup code prominently -->
        <div class="reserve-panel" style="margin-bottom:1rem">
            <div class="reserve-panel__strip">
                <span style="font-size:.78rem;font-weight:700;color:var(--text-mid)">Your Reservation</span>
                <span class="status-badge status-badge--<?= statusClass($myRes['reservation_status']) ?>">
                    <?= statusLabel($myRes['reservation_status']) ?>
                </span>
            </div>
            <div class="reserve-panel__body">
                <div class="pickup-banner">
                    <div class="pickup-banner__label">Your Pickup Code</div>
                    <div class="pickup-banner__code" data-copy title="Click to copy">
                        <?= e($myRes['pickup_code']) ?>
                    </div>
                    <div class="pickup-banner__hint">
                        Show this code to the business when collecting your order.
                    </div>
                </div>

                <?php if ($myRes['reservation_status'] === 'reserved'): ?>
                <form method="POST" action="<?= baseUrl('modules/reservations/cancel.php') ?>"
                      data-confirm="Cancel this reservation? The listing will be made available again."
                      style="margin-top:.85rem">
                    <input type="hidden" name="reservation_id" value="<?= $myRes['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="btn btn-outline btn-block" type="submit"
                            style="color:var(--terra);border-color:rgba(181,96,74,.35)">
                        Cancel Reservation
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif (in_array($role, ['general_user', 'charity']) && $reserveError === ''): ?>

        <!-- Reserve CTA -->
        <div class="reserve-panel" style="margin-bottom:1rem">
            <div class="reserve-panel__strip">
                <div>
                    <div class="reserve-panel__free">Free</div>
                    <div class="reserve-panel__qty-label"><?= e($listing['quantity'] . ' ' . $listing['unit']) ?> available</div>
                </div>
                <span class="status-badge status-badge--green">Available</span>
            </div>
            <div class="reserve-panel__body">
                <p style="font-size:.84rem;color:var(--text-muted);line-height:1.6;margin-bottom:1.1rem">
                    Reserve now to secure a pickup slot. A unique pickup code will be sent to you immediately.
                </p>
                <form method="POST" action="<?= baseUrl('modules/reservations/reserve.php') ?>">
                    <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="btn btn-primary btn-block btn-lg" type="submit">
                        Reserve This Listing
                    </button>
                </form>
                <p style="font-size:.73rem;color:var(--text-light);text-align:center;margin-top:.75rem">
                    Free &middot; No account payment required
                </p>
            </div>
        </div>

        <?php elseif ($listing['status'] !== 'available'): ?>

        <div class="reserve-panel" style="margin-bottom:1rem">
            <div class="reserve-panel__body" style="padding:1.4rem">
                <div class="notice notice--info" style="margin:0">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <div class="notice__body">
                        <strong>Not reservable</strong><br>
                        This listing is currently <em><?= e(statusLabel($listing['status'])) ?></em>.
                    </div>
                </div>
            </div>
        </div>

        <?php elseif (!empty($reserveError)): ?>

        <div class="reserve-panel" style="margin-bottom:1rem">
            <div class="reserve-panel__body" style="padding:1.4rem">
                <div class="notice notice--warning" style="margin:0">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <div class="notice__body"><?= e($reserveError) ?></div>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Business info card -->
        <div class="card">
            <div class="card-body" style="padding:1.2rem 1.3rem">
                <div style="font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.09em;color:var(--text-light);margin-bottom:.65rem">
                    From the Business
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.6rem">
                    <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--olive-deep),var(--olive));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.95rem;flex-shrink:0">
                        <?= strtoupper(mb_substr($listing['business_name'] ?? $listing['business_owner_name'] ?? 'B', 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:var(--text-dark)">
                            <?= e($listing['business_name'] ?? $listing['business_owner_name']) ?>
                        </div>
                        <?php if ($listing['business_type']): ?>
                        <div style="font-size:.78rem;color:var(--text-muted)"><?= e($listing['business_type']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($listing['business_city']): ?>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-muted);margin-bottom:.45rem">
                    <svg viewBox="0 0 14 14" width="11" fill="none"><circle cx="7" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M7 11C4 11 2.5 9 2.5 6.5a4.5 4.5 0 019 0C11.5 9 10 11 7 11z" stroke="currentColor" stroke-width="1.2"/></svg>
                    <?= e($listing['business_city']) ?>
                </div>
                <?php endif; ?>
                <?php if (($listing['verification_status'] ?? '') === 'verified'): ?>
                <div style="display:inline-flex;align-items:center;gap:.35rem;font-size:.74rem;font-weight:700;color:var(--olive);background:rgba(74,103,65,.08);padding:.22rem .65rem;border-radius:var(--r-pill);border:1px solid rgba(74,103,65,.18)">
                    <svg viewBox="0 0 14 14" width="12" fill="none"><path d="M2.5 7l3 3 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Verified business
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
