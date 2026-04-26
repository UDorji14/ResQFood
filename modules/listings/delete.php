<?php
/**
 * ResQFood — Delete Listing Handler (POST only)
 * ───────────────────────────────────────────────
 * Hard-deletes listings that have no active reservations.
 * Listings with active reservations are cancelled instead (soft delete).
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

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('modules/listings/index.php'));
}

verifyCsrf();

$uid       = currentUserId();
$listingId = (int) ($_POST['listing_id'] ?? 0);
$listing   = getListing($listingId);

if (!$listing) {
    setFlash('error', 'Listing not found.');
    redirect(baseUrl('modules/listings/index.php'));
}

// Ownership check
if (currentUserRole() === 'business' && (int) $listing['business_user_id'] !== $uid) {
    setFlash('error', 'You do not have permission to delete that listing.');
    redirect(baseUrl('modules/listings/index.php'));
}

// Only deletable if no active (reserved) reservations
if (!in_array($listing['status'], ['available', 'expired', 'cancelled'])) {
    setFlash('error', 'Listings with active reservations cannot be deleted. Cancel them first.');
    redirect(baseUrl('modules/listings/view.php?id=' . $listingId));
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Remove associated images from disk
    $images = $pdo->prepare('SELECT image_path FROM listing_images WHERE listing_id = ?');
    $images->execute([$listingId]);
    foreach ($images->fetchAll() as $img) {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img['image_path']);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // Hard delete (CASCADE handles listing_images, reservations, etc.)
    $pdo->prepare('DELETE FROM food_listings WHERE id = ?')->execute([$listingId]);

    auditLog('listing_delete', 'id=' . $listingId . ' title=' . $listing['title'], $uid);
    $pdo->commit();

    setFlash('success', 'Listing "' . truncate($listing['title'], 40) . '" deleted.');

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[ResQFood DeleteListing] ' . $e->getMessage());
    setFlash('error', 'Could not delete the listing. Please try again.');
}

redirect(baseUrl('modules/listings/index.php'));
