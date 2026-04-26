<?php
/**
 * ResQFood — Confirm Pickup
 * ────────────────────────────
 * Business users (or admins) confirm that a reserved listing has been collected.
 *
 * GET  ?id=N  → show confirmation form with optional pickup code verification
 * POST        → confirm pickup, update statuses, create impact record, notify user
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['business', 'admin']);

$uid = currentUserId();

// ── POST: confirm ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $enteredCode   = strtoupper(trim($_POST['pickup_code'] ?? ''));
    $reservation   = getReservation($reservationId);

    if (!$reservation) {
        setFlash('error', 'Reservation not found.');
        redirect(baseUrl('modules/reservations/index.php'));
    }

    // Business can only confirm pickups on their own listings
    if (currentUserRole() === 'business' && (int) $reservation['business_user_id'] !== $uid) {
        setFlash('error', 'You can only confirm pickups for your own listings.');
        redirect(baseUrl('modules/reservations/index.php'));
    }

    if (!isConfirmable($reservation['reservation_status'])) {
        setFlash('error', 'This reservation cannot be confirmed (status: ' . statusLabel($reservation['reservation_status']) . ').');
        redirect(baseUrl('modules/reservations/index.php'));
    }

    // Pickup code verification — only required if the business entered one
    if ($enteredCode !== '' && $enteredCode !== strtoupper($reservation['pickup_code'])) {
        setFlash('error', 'The pickup code you entered does not match. Please ask the customer to show their code.');
        redirect(baseUrl('modules/reservations/confirm_pickup.php?id=' . $reservationId));
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Mark reservation as collected
        $pdo->prepare('
            UPDATE reservations
            SET    reservation_status = "collected",
                   collected_at       = NOW(),
                   updated_at         = NOW()
            WHERE  id = ?
        ')->execute([$reservationId]);

        logReservationStatus($reservationId, 'reserved', 'collected', $uid, 'Pickup confirmed by ' . currentUserName());

        // Mark listing as collected
        $pdo->prepare('
            UPDATE food_listings
            SET    status = "collected", updated_at = NOW()
            WHERE  id = ?
        ')->execute([$reservation['listing_id']]);

        // Create impact record
        createImpactRecord($reservation['listing_id'], $reservationId);

        // Notify the person who reserved
        createNotification(
            (int) $reservation['reserved_by'],
            'Pickup Confirmed',
            'Your pickup of "' . truncate($reservation['title'], 50) . '" has been confirmed. Thank you for reducing food waste!',
            baseUrl('modules/reservations/my.php')
        );

        auditLog('reservation_confirm', 'res_id=' . $reservationId, $uid);
        $pdo->commit();

        setFlash('success', 'Pickup confirmed for "' . truncate($reservation['title'], 40) . '"! Impact recorded.');
        redirect(baseUrl('modules/reservations/index.php'));

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[ResQFood ConfirmPickup] ' . $e->getMessage());
        setFlash('error', 'Could not confirm pickup. Please try again.');
        redirect(baseUrl('modules/reservations/confirm_pickup.php?id=' . $reservationId));
    }
}

// ── GET: show confirmation form ────────────────────────────────────────────
$reservationId = (int) ($_GET['id'] ?? 0);
$reservation   = getReservation($reservationId);

if (!$reservation) {
    setFlash('error', 'Reservation not found.');
    redirect(baseUrl('modules/reservations/index.php'));
}

if (currentUserRole() === 'business' && (int) $reservation['business_user_id'] !== $uid) {
    setFlash('error', 'You can only view pickups for your own listings.');
    redirect(baseUrl('modules/reservations/index.php'));
}

if (!isConfirmable($reservation['reservation_status'])) {
    setFlash('warning', 'This reservation is already ' . statusLabel($reservation['reservation_status']) . '.');
    redirect(baseUrl('modules/reservations/index.php'));
}

$pageTitle = 'Confirm Pickup';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('modules/reservations/index.php') ?>">Reservations</a> / <span>Confirm Pickup</span>
    </div>
    <h1>Confirm Pickup</h1>
    <p class="text-muted">Verify the collection and mark the listing as collected.</p>
</div>

<div style="max-width:680px;margin:0 auto">

    <!-- Reservation Summary -->
    <div class="res-card" style="margin-bottom:1.5rem">
        <div class="res-card__head">
            <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="3" width="14" height="14" rx="3" stroke="currentColor" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h3>Reservation #<?= $reservationId ?></h3>
            <span class="status-badge status-badge--amber" style="margin-left:auto"><?= statusLabel($reservation['reservation_status']) ?></span>
        </div>
        <div class="res-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <div class="res-card__field">
                        <div class="res-card__label">Listing</div>
                        <div class="res-card__value">
                            <a href="<?= baseUrl('modules/listings/view.php?id=' . $reservation['listing_id']) ?>"
                               style="color:var(--olive)">
                                <?= e($reservation['title']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="res-card__field">
                        <div class="res-card__label">Quantity</div>
                        <div class="res-card__value"><?= e($reservation['quantity'] . ' ' . $reservation['unit']) ?></div>
                    </div>
                    <div class="res-card__field">
                        <div class="res-card__label">Pickup window</div>
                        <div class="res-card__value" style="font-size:.85rem">
                            <?= formatDate($reservation['pickup_start'], 'd M Y, H:i') ?> →
                            <?= formatDate($reservation['pickup_end'], 'H:i') ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="res-card__field">
                        <div class="res-card__label">Reserved by</div>
                        <div class="res-card__value"><?= e($reservation['reserved_by_name']) ?></div>
                    </div>
                    <div class="res-card__field">
                        <div class="res-card__label">Role</div>
                        <div class="res-card__value">
                            <span class="role-badge role-badge--<?= roleBadgeClass($reservation['reserver_role']) ?>">
                                <?= roleLabel($reservation['reserver_role']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="res-card__field">
                        <div class="res-card__label">Email</div>
                        <div class="res-card__value" style="font-size:.82rem"><?= e($reservation['reserved_by_email']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Form -->
    <div class="card">
        <div class="card-header"><h3>Confirm Collection</h3></div>
        <div class="card-body">

            <div class="notice notice--info" style="margin-bottom:1.5rem">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <div class="notice__body">
                    Ask the customer to show their pickup code. You may enter it below to verify,
                    or click <strong>Confirm without code</strong> to proceed on good faith.
                </div>
            </div>

            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">

                <div class="form-group">
                    <label class="form-label" for="pickup_code">
                        Customer's Pickup Code
                        <span class="text-muted" style="font-weight:400">(optional — leave blank to skip verification)</span>
                    </label>
                    <input type="text" id="pickup_code" name="pickup_code"
                           class="form-control"
                           style="font-size:1.2rem;font-family:'Courier New',monospace;letter-spacing:.15em;max-width:220px;text-transform:uppercase"
                           placeholder="e.g. A3B9F2"
                           maxlength="8" autocomplete="off">
                    <span class="form-hint">
                        Correct code:
                        <span class="pickup-code" style="font-size:1rem;padding:.25rem .75rem"><?= e($reservation['pickup_code']) ?></span>
                        (shown here for your reference)
                    </span>
                </div>

                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1.5rem">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Confirm Pickup
                    </button>
                    <a href="<?= baseUrl('modules/reservations/index.php') ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
