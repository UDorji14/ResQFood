<?php
/**
 * ResQFood — Reserve a Listing (POST handler)
 * ───────────────────────────────────────────────
 * Creates a reservation atomically using a transaction + row lock
 * to prevent double-reservations under concurrent requests.
 *
 * After success, redirects back to the listing view page so the
 * user sees their pickup code immediately (PRG pattern).
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';
require_once __DIR__ . '/../../includes/reservations.php';

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

$pdo = db();

try {
    $pdo->beginTransaction();

    // ── Lock the listing row so concurrent requests queue here ───────────
    $stmt = $pdo->prepare('
        SELECT id, title, status, pickup_end, business_user_id
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

    // ── Business rules ─────────────────────────────────────────────────
    $err = canReserve($uid, $role, $listing);
    if ($err !== '') {
        $pdo->rollBack();
        setFlash('error', $err);
        redirect($backUrl);
    }

    // ── Create reservation ─────────────────────────────────────────────
    $pickupCode = strtoupper(bin2hex(random_bytes(3))); // 6-char hex code

    $pdo->prepare('
        INSERT INTO reservations (listing_id, reserved_by, reservation_status, pickup_code, reserved_at)
        VALUES (?, ?, "reserved", ?, NOW())
    ')->execute([$listingId, $uid, $pickupCode]);

    $reservationId = (int) $pdo->lastInsertId();

    // ── Mark listing as reserved ───────────────────────────────────────
    $pdo->prepare('
        UPDATE food_listings SET status = "reserved", updated_at = NOW() WHERE id = ?
    ')->execute([$listingId]);

    // ── Log initial status ─────────────────────────────────────────────
    logReservationStatus($reservationId, null, 'reserved', $uid, 'Initial reservation');

    // ── Notify the business owner ──────────────────────────────────────
    $businessUserId = (int) $listing['business_user_id'];
    createNotification(
        $businessUserId,
        'New Reservation',
        currentUserName() . ' has reserved your listing "' . truncate($listing['title'], 50) . '".',
        baseUrl('modules/listings/view.php?id=' . $listingId)
    );

    auditLog('reservation_create', 'res_id=' . $reservationId . ' listing_id=' . $listingId, $uid);
    $pdo->commit();

    setFlash('success', 'Reservation confirmed! Your pickup code is: <strong>' . e($pickupCode) . '</strong>. Show it at the business when you collect.');
    redirect($backUrl);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[ResQFood Reserve] ' . $e->getMessage());
    setFlash('error', 'Could not complete the reservation. Please try again.');
    redirect($backUrl);
}
