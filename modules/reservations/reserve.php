<?php
/**
 * ResQFood — Reserve a Listing (POST handler) — Partial Quantity Support
 * ────────────────────────────────────────────────────────────────────────
 * A user specifies how much of the listing they want to reserve.
 * The listing stays available while available_quantity > 0.
 * Uses a row-level FOR UPDATE lock to prevent race conditions.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';
require_once __DIR__ . '/../../includes/notification_service.php';

requireRole(['general_user', 'charity']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('modules/listings/browse.php'));
}

verifyCsrf();

$uid       = currentUserId();
$role      = currentUserRole();
$listingId = (int) ($_POST['listing_id'] ?? 0);
$backUrl   = baseUrl('modules/listings/view.php?id=' . $listingId);

if ($listingId <= 0) {
    setFlash('error', 'Invalid request.');
    redirect(baseUrl('modules/listings/browse.php'));
}

// Validate requested quantity
$requestedQtyRaw = trim($_POST['reserved_quantity'] ?? '');
if ($requestedQtyRaw === '' || !is_numeric($requestedQtyRaw)) {
    setFlash('error', 'Please enter a valid quantity to reserve.');
    redirect($backUrl);
}
$requestedQty = (float) $requestedQtyRaw;
if ($requestedQty <= 0) {
    setFlash('error', 'Reserved quantity must be greater than zero.');
    redirect($backUrl);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // ── Lock the listing row ──────────────────────────────────────────────
    $stmt = $pdo->prepare('
        SELECT id, title, status, available_quantity, quantity, unit, pickup_end, business_user_id
        FROM   food_listings
        WHERE  id = ?
        FOR    UPDATE
    ');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();

    if (!$listing) {
        $pdo->rollBack();
        setFlash('error', 'Listing not found.');
        redirect(baseUrl('modules/listings/browse.php'));
    }

    // ── Business rules (including quantity check) ─────────────────────────
    $err = canReserve($uid, $role, $listing, $requestedQty);
    if ($err !== '') {
        $pdo->rollBack();
        setFlash('error', $err);
        redirect($backUrl);
    }

    $availQty = (float) $listing['available_quantity'];

    // Clamp to available (extra safety after lock)
    if ($requestedQty > $availQty) {
        $pdo->rollBack();
        setFlash('error', 'Only ' . formatQty($availQty) . ' ' . $listing['unit'] . ' is available. Please reduce your request.');
        redirect($backUrl);
    }

    // ── Create reservation ────────────────────────────────────────────────
    $pickupCode = strtoupper(bin2hex(random_bytes(3)));

    $pdo->prepare('
        INSERT INTO reservations
               (listing_id, reserved_by, reservation_status, pickup_code, reserved_quantity, reserved_at)
        VALUES (?, ?, "reserved", ?, ?, NOW())
    ')->execute([$listingId, $uid, $pickupCode, $requestedQty]);

    $reservationId = (int) $pdo->lastInsertId();

    // ── Deduct from available_quantity ────────────────────────────────────
    $newAvailable = $availQty - $requestedQty;
    $newStatus    = $newAvailable <= 0 ? 'reserved' : 'available';

    $pdo->prepare('
        UPDATE food_listings
        SET    available_quantity = ?, status = ?, updated_at = NOW()
        WHERE  id = ?
    ')->execute([$newAvailable, $newStatus, $listingId]);

    // ── Log initial status ────────────────────────────────────────────────
    logReservationStatus($reservationId, null, 'reserved', $uid, 'Initial reservation — ' . formatQty($requestedQty) . ' ' . $listing['unit']);

    // ── Notify the business owner ─────────────────────────────────────────
    $businessUserId = (int) $listing['business_user_id'];
    createNotification(
        $businessUserId,
        'New Reservation',
        currentUserName() . ' reserved ' . formatQty($requestedQty) . ' ' . $listing['unit']
            . ' from "' . truncate($listing['title'], 45) . '".'
            . ($newAvailable > 0 ? ' ' . formatQty($newAvailable) . ' ' . $listing['unit'] . ' still available.' : ' Listing fully reserved.'),
        baseUrl('modules/listings/view.php?id=' . $listingId)
    );

    auditLog('reservation_create', 'res_id=' . $reservationId . ' listing_id=' . $listingId . ' qty=' . $requestedQty, $uid);
    $pdo->commit();

    setFlash('success', 'Reserved <strong>' . e(formatQty($requestedQty) . ' ' . $listing['unit']) . '</strong>! Your pickup code is: <strong>' . e($pickupCode) . '</strong>. Show it at the business when you collect.');
    try {
        notify_reservation_created($reservationId);
    } catch (Throwable $e) {
        error_log('[ResQFoodddd Reserve Notify] ' . $e->getMessage());
    }
    redirect($backUrl);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[ResQFood Reserve] ' . $e->getMessage());
    setFlash('error', 'Could not complete the reservation. Please try again.');
    redirect($backUrl);
}
