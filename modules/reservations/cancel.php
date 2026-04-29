<?php
/**
 * ResQFood — Cancel Reservation (POST handler)
 * ───────────────────────────────────────────────
 * Owner cancels their own reservation.
 * Sets listing back to "available" if it was reserved.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['general_user', 'charity']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('modules/reservations/my.php'));
}

verifyCsrf();

$uid           = currentUserId();
$reservationId = (int) ($_POST['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    setFlash('error', 'Invalid request.');
    redirect(baseUrl('modules/reservations/my.php'));
}

$reservation = getReservation($reservationId);

if (!$reservation) {
    setFlash('error', 'Reservation not found.');
    redirect(baseUrl('modules/reservations/my.php'));
}

// Ownership check
if ((int) $reservation['reserved_by'] !== $uid) {
    setFlash('error', 'You cannot cancel another user\'s reservation.');
    redirect(baseUrl('modules/reservations/my.php'));
}

// Status check
if (!isCancellable($reservation['reservation_status'])) {
    setFlash('error', 'This reservation cannot be cancelled (status: ' . statusLabel($reservation['reservation_status']) . ').');
    redirect(baseUrl('modules/reservations/my.php'));
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $cancelledQty = (float) ($reservation['reserved_quantity'] ?? 1);

    // Update reservation status
    $pdo->prepare('
        UPDATE reservations
        SET    reservation_status = "cancelled", updated_at = NOW()
        WHERE  id = ?
    ')->execute([$reservationId]);

    // Log the status change
    logReservationStatus($reservationId, 'reserved', 'cancelled', $uid, 'Cancelled by reserver');

    // Restore quantity to the listing (if still within a valid state)
    $listingStmt = $pdo->prepare('
        SELECT status, available_quantity, quantity, unit
        FROM   food_listings
        WHERE  id = ?
        FOR    UPDATE
    ');
    $listingStmt->execute([$reservation['listing_id']]);
    $listingRow = $listingStmt->fetch();

    if ($listingRow && !in_array($listingRow['status'], ['expired', 'cancelled', 'collected'])) {
        $restoredAvail = (float) $listingRow['available_quantity'] + $cancelledQty;
        // Cap at original quantity to avoid exceeding total
        $restoredAvail = min($restoredAvail, (float) $listingRow['quantity']);
        $newListingStatus = $restoredAvail > 0 ? 'available' : $listingRow['status'];

        $pdo->prepare('
            UPDATE food_listings
            SET    available_quantity = ?, status = ?, updated_at = NOW()
            WHERE  id = ?
        ')->execute([$restoredAvail, $newListingStatus, $reservation['listing_id']]);
    }

    // Notify the business owner
    createNotification(
        (int) $reservation['business_user_id'],
        'Reservation Cancelled',
        currentUserName() . ' cancelled their reservation of '
            . formatQty($cancelledQty) . ' ' . ($listingRow['unit'] ?? '')
            . ' from "' . truncate($reservation['title'], 45) . '". Quantity restored.',
        baseUrl('modules/listings/view.php?id=' . $reservation['listing_id'])
    );

    auditLog('reservation_cancel', 'res_id=' . $reservationId, $uid);
    $pdo->commit();

    setFlash('success', 'Reservation cancelled. The listing has been made available again.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[ResQFood Cancel] ' . $e->getMessage());
    setFlash('error', 'Could not cancel the reservation. Please try again.');
}

// Redirect back to whichever page sent the request (listing view or my reservations)
$from = sanitize($_POST['from'] ?? '');
if ($from === 'listing' && $reservation) {
    redirect(baseUrl('modules/listings/view.php?id=' . $reservation['listing_id']));
}
redirect(baseUrl('modules/reservations/my.php'));
