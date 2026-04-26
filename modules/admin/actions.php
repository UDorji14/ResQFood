<?php
/**
 * ResQFood — Admin Action Handler (POST only)
 * ─────────────────────────────────────────────────
 * Single endpoint for all admin state-mutations.
 * Every action here:
 *   1. Requires admin role
 *   2. Verifies CSRF token
 *   3. Validates inputs
 *   4. Executes within a transaction where needed
 *   5. Writes to audit_logs
 *   6. Redirects with a flash message (PRG pattern)
 *
 * Supported actions:
 *   user_status      — change a user's account status
 *   verify_profile   — approve or reject a business/charity profile
 *   listing_cancel   — admin force-cancels a listing
 *   report_update    — change a report's status + add admin note
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/reservations.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('modules/admin/dashboard.php'));
}

verifyCsrf();

$action   = sanitize($_POST['action'] ?? '');
$adminId  = currentUserId();

// Safe redirect target — only allow relative paths we own
$rawRedirect = sanitize($_POST['redirect'] ?? '');
$redirectUrl = $rawRedirect !== '' ? baseUrl($rawRedirect) : baseUrl('modules/admin/dashboard.php');

$pdo = db();

// ── action: user_status ───────────────────────────────────────────────────
if ($action === 'user_status') {

    $userId    = (int) ($_POST['user_id']    ?? 0);
    $newStatus = sanitize($_POST['new_status'] ?? '');
    $note      = sanitize($_POST['admin_note'] ?? '');
    $allowed   = ['active', 'pending', 'inactive', 'suspended'];

    if ($userId <= 0 || !in_array($newStatus, $allowed, true)) {
        setFlash('error', 'Invalid status change request.');
        redirect($redirectUrl);
    }

    // Cannot change own account status
    if ($userId === $adminId) {
        setFlash('error', 'You cannot change your own account status.');
        redirect($redirectUrl);
    }

    // Fetch current status for logging
    $userStmt = $pdo->prepare('SELECT full_name, status, role FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $target = $userStmt->fetch();

    if (!$target) {
        setFlash('error', 'User not found.');
        redirect($redirectUrl);
    }
    if ($target['role'] === 'admin') {
        setFlash('error', 'Admin accounts cannot be managed through this interface.');
        redirect($redirectUrl);
    }

    $pdo->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$newStatus, $userId]);

    auditLog(
        'admin_user_status',
        'user_id=' . $userId . ' name=' . $target['full_name'] . ' ' . $target['status'] . '->' . $newStatus . ($note ? ' note=' . truncate($note, 80) : ''),
        $adminId
    );

    setFlash('success', e($target['full_name']) . '\'s status changed to "' . statusLabel($newStatus) . '".');
    redirect($redirectUrl);
}

// ── action: verify_profile ────────────────────────────────────────────────
if ($action === 'verify_profile') {

    $userId      = (int) ($_POST['user_id']      ?? 0);
    $type        = sanitize($_POST['type']        ?? '');
    $verifStatus = sanitize($_POST['verif_status'] ?? '');
    $allowed     = ['pending', 'verified', 'rejected'];

    if ($userId <= 0 || !in_array($type, ['business', 'charity'], true) || !in_array($verifStatus, $allowed, true)) {
        setFlash('error', 'Invalid verification request.');
        redirect($redirectUrl);
    }

    if ($type === 'business') {
        $pdo->prepare('UPDATE business_profiles SET verification_status = ?, updated_at = NOW() WHERE user_id = ?')
            ->execute([$verifStatus, $userId]);
    } else {
        $pdo->prepare('UPDATE charity_profiles SET verification_status = ?, updated_at = NOW() WHERE user_id = ?')
            ->execute([$verifStatus, $userId]);
    }

    auditLog(
        'admin_profile_verify',
        'user_id=' . $userId . ' type=' . $type . ' verif=' . $verifStatus,
        $adminId
    );

    setFlash('success', ucfirst($type) . ' profile verification status set to "' . statusLabel($verifStatus) . '".');
    redirect($redirectUrl);
}

// ── action: listing_cancel ────────────────────────────────────────────────
if ($action === 'listing_cancel') {

    $listingId = (int) ($_POST['listing_id'] ?? 0);

    if ($listingId <= 0) {
        setFlash('error', 'Invalid listing ID.');
        redirect($redirectUrl);
    }

    $listingStmt = $pdo->prepare('SELECT title, status, business_user_id FROM food_listings WHERE id = ? LIMIT 1');
    $listingStmt->execute([$listingId]);
    $listing = $listingStmt->fetch();

    if (!$listing) {
        setFlash('error', 'Listing not found.');
        redirect($redirectUrl);
    }
    if (!in_array($listing['status'], ['available', 'reserved', 'expired'])) {
        setFlash('error', 'This listing cannot be cancelled (status: ' . statusLabel($listing['status']) . ').');
        redirect($redirectUrl);
    }

    try {
        $pdo->beginTransaction();

        // Cancel active reservations on this listing
        $activeResStmt = $pdo->prepare('
            SELECT id, reserved_by FROM reservations
            WHERE  listing_id = ? AND reservation_status = "reserved"
        ');
        $activeResStmt->execute([$listingId]);
        $activeReservations = $activeResStmt->fetchAll();

        foreach ($activeReservations as $res) {
            $pdo->prepare('UPDATE reservations SET reservation_status = "cancelled", updated_at = NOW() WHERE id = ?')
                ->execute([$res['id']]);

            logReservationStatus($res['id'], 'reserved', 'cancelled', $adminId, 'Admin cancelled the listing');

            createNotification(
                (int) $res['reserved_by'],
                'Reservation Cancelled',
                'Your reservation was cancelled because the listing "' . truncate($listing['title'], 50) . '" was removed by an administrator.',
                baseUrl('modules/reservations/my.php')
            );
        }

        // Cancel the listing itself
        $pdo->prepare('UPDATE food_listings SET status = "cancelled", updated_at = NOW() WHERE id = ?')
            ->execute([$listingId]);

        auditLog('admin_listing_cancel', 'listing_id=' . $listingId . ' title=' . truncate($listing['title'], 60), $adminId);
        $pdo->commit();

        $msg = 'Listing "' . truncate($listing['title'], 40) . '" cancelled.';
        if (!empty($activeReservations)) {
            $msg .= ' ' . count($activeReservations) . ' reservation(s) cancelled and notified.';
        }
        setFlash('success', $msg);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[ResQFood AdminAction listing_cancel] ' . $e->getMessage());
        setFlash('error', 'Could not cancel the listing. Please try again.');
    }

    redirect($redirectUrl);
}

// ── action: report_update ─────────────────────────────────────────────────
if ($action === 'report_update') {

    $reportId     = (int) ($_POST['report_id']     ?? 0);
    $reportStatus = sanitize($_POST['report_status'] ?? '');
    $adminNote    = sanitize($_POST['admin_note']   ?? '');
    $allowed      = ['open', 'under_review', 'resolved', 'dismissed'];

    if ($reportId <= 0 || !in_array($reportStatus, $allowed, true)) {
        setFlash('error', 'Invalid report update request.');
        redirect($redirectUrl);
    }

    $pdo->prepare('
        UPDATE reports
        SET    report_status = ?,
               admin_note   = ?,
               reviewed_by  = ?,
               updated_at   = NOW()
        WHERE  id = ?
    ')->execute([$reportStatus, $adminNote ?: null, $adminId, $reportId]);

    auditLog('admin_report_update', 'report_id=' . $reportId . ' status=' . $reportStatus, $adminId);

    // Notify reporter if resolved/dismissed
    if (in_array($reportStatus, ['resolved', 'dismissed'])) {
        $repRow = $pdo->prepare('SELECT report_by, reason FROM reports WHERE id = ? LIMIT 1');
        $repRow->execute([$reportId]);
        $rep = $repRow->fetch();
        if ($rep) {
            createNotification(
                (int) $rep['report_by'],
                'Report ' . ucfirst($reportStatus),
                'Your report "' . truncate($rep['reason'], 60) . '" has been ' . $reportStatus . '.' . ($adminNote ? ' Admin note: ' . truncate($adminNote, 80) : ''),
                baseUrl('modules/admin/reports.php')
            );
        }
    }

    setFlash('success', 'Report #' . $reportId . ' status updated to "' . statusLabel($reportStatus) . '".');
    redirect($redirectUrl);
}

// ── Unknown action fallback ───────────────────────────────────────────────
setFlash('error', 'Unknown admin action: ' . e($action));
redirect(baseUrl('modules/admin/dashboard.php'));
