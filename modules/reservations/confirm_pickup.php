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

        // Create impact record (uses reserved_quantity from this reservation)
        createImpactRecord($reservation['listing_id'], $reservationId);

        // Mark listing as collected only when:
        // - no more available quantity AND no other active 'reserved' reservations
        $checkStmt = $pdo->prepare('
            SELECT available_quantity,
                   (SELECT COUNT(*) FROM reservations
                    WHERE  listing_id = ? AND reservation_status = "reserved") AS active_res
            FROM   food_listings
            WHERE  id = ?
            LIMIT  1
        ');
        $checkStmt->execute([$reservation['listing_id'], $reservation['listing_id']]);
        $listingCheck = $checkStmt->fetch();

        if ($listingCheck
            && (float) $listingCheck['available_quantity'] <= 0
            && (int) $listingCheck['active_res'] === 0
        ) {
            $pdo->prepare('
                UPDATE food_listings
                SET    status = "collected", updated_at = NOW()
                WHERE  id = ?
            ')->execute([$reservation['listing_id']]);
        }

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

<div class="breadcrumb">
    <a href="<?= baseUrl('modules/reservations/index.php') ?>">Reservations</a>
    <span>Confirm Pickup</span>
</div>

<div class="confirm-screen">

    <!-- ── Summary card ────────────────────────────────────── -->
    <div class="confirm-card" style="margin-bottom:1.25rem">
        <div class="confirm-card__band">
            <div class="confirm-card__icon-wrap">
                <svg viewBox="0 0 24 24" width="28" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 12l3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="confirm-card__title">Confirm Pickup</div>
            <div class="confirm-card__sub">
                Reservation #<?= $reservationId ?> &middot;
                <span class="status-badge status-badge--amber" style="vertical-align:middle">
                    <?= statusLabel($reservation['reservation_status']) ?>
                </span>
            </div>
        </div>
        <div class="confirm-card__body">

            <!-- Listing info row -->
            <div style="padding:.85rem 1rem;background:rgba(248,244,234,.6);border-radius:var(--r-lg);margin-bottom:1.1rem">
                <div style="font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light);margin-bottom:.45rem">Listing</div>
                <a href="<?= baseUrl('modules/listings/view.php?id=' . $reservation['listing_id']) ?>"
                   style="font-weight:700;font-size:1rem;color:var(--olive);text-decoration:none;display:block;margin-bottom:.25rem">
                    <?= e($reservation['title']) ?>
                </a>
                <div style="display:flex;gap:1.25rem;flex-wrap:wrap;font-size:.81rem;color:var(--text-muted)">
                    <span>
                        <strong style="color:var(--text-mid)">Reserved:</strong>
                        <?= e(formatQty((float)($reservation['reserved_quantity'] ?? 1)) . ' ' . $reservation['unit']) ?>
                    </span>
                    <span><strong style="color:var(--text-mid)">Window:</strong> <?= formatDate($reservation['pickup_start'], 'd M, H:i') ?> &ndash; <?= formatDate($reservation['pickup_end'], 'H:i') ?></span>
                </div>
            </div>

            <!-- Reserver info row -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;padding:.85rem 1rem;border:1px solid var(--line);border-radius:var(--r-lg);margin-bottom:1.1rem">
                <div>
                    <div style="font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light);margin-bottom:.3rem">Reserved By</div>
                    <div style="font-weight:700;font-size:.93rem;color:var(--text-dark)"><?= e($reservation['reserved_by_name']) ?></div>
                    <div style="font-size:.79rem;color:var(--text-muted)"><?= e($reservation['reserved_by_email']) ?></div>
                </div>
                <span class="role-badge role-badge--<?= roleBadgeClass($reservation['reserver_role']) ?>">
                    <?= roleLabel($reservation['reserver_role']) ?>
                </span>
            </div>

            <!-- The actual code (for reference) -->
            <div class="pickup-banner" style="margin-bottom:1.25rem">
                <div class="pickup-banner__label">Customer's Pickup Code</div>
                <div class="pickup-banner__code" data-copy title="Click to copy">
                    <?= e($reservation['pickup_code']) ?>
                </div>
                <div class="pickup-banner__hint">
                    The customer should present this code. Verify it matches before confirming.
                </div>
            </div>

        </div>
    </div>

    <!-- ── Confirmation form ─────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h3>Verify &amp; Confirm</h3>
        </div>
        <div class="card-body">

            <div class="notice notice--info" style="margin-bottom:1.25rem">
                <svg viewBox="0 0 20 20" width="18" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 9v5m0-7h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <div class="notice__body" style="font-size:.84rem">
                    Ask the customer to show their code. Enter it below to verify — or leave blank and confirm on good faith.
                </div>
            </div>

            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">

                <div class="form-group">
                    <label class="form-label" for="pickup_code">
                        Enter Customer Code
                        <span class="text-muted" style="font-weight:400;font-size:.8em">(optional)</span>
                    </label>
                    <input type="text" id="pickup_code" name="pickup_code"
                           class="form-control"
                           style="font-size:1.35rem;font-family:'Courier New',monospace;letter-spacing:.18em;max-width:200px;text-transform:uppercase;text-align:center;font-weight:700"
                           placeholder="______"
                           maxlength="8" autocomplete="off" spellcheck="false">
                    <span class="form-hint">Leave blank to confirm without code verification.</span>
                </div>

                <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.4rem">
                    <button type="submit" class="btn btn-primary btn-lg"
                            style="flex:1;min-width:160px">
                        <svg viewBox="0 0 18 18" width="16" fill="none" style="margin-right:.4rem"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Confirm Pickup
                    </button>
                    <a href="<?= baseUrl('modules/reservations/index.php') ?>"
                       class="btn btn-outline">Cancel</a>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeInput = document.getElementById('pickup_code');
    if (codeInput) {
        codeInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
