<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/app_url.php';

function email_logs_table_exists(PDO $pdo): bool
{
    static $exists = null;
    if ($exists !== null) return $exists;
    try {
        $pdo->query('SELECT id FROM email_logs LIMIT 1');
        $exists = true;
    } catch (Throwable $e) {
        $exists = false;
    }
    return $exists;
}

function user_email_notifications_enabled(PDO $pdo, int $userId): bool
{
    try {
        $st = $pdo->prepare('SELECT email_notifications_enabled FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $v = $st->fetchColumn();
        return $v === false ? true : ((int) $v === 1);
    } catch (Throwable $e) {
        return true;
    }
}

function send_notification_email(?int $recipientUserId, string $recipientEmail, string $templateName, string $subject, string $html, string $text, ?string $relatedType = null, ?int $relatedId = null, array $embeddedImages = []): array
{
    $pdo = db();
    $logId = null;
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email'];
    }

    if ($recipientUserId && !user_email_notifications_enabled($pdo, $recipientUserId)) {
        return ['success' => false, 'error' => 'Notifications disabled'];
    }

    if (email_logs_table_exists($pdo)) {
        try {
            $ins = $pdo->prepare('INSERT INTO email_logs (recipient_user_id, recipient_email, subject, template_name, related_type, related_id, status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
            $ins->execute([$recipientUserId, $recipientEmail, $subject, $templateName, $relatedType, $relatedId]);
            $logId = (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
        }
    }

    $result = send_platform_email($recipientEmail, $subject, $html, $text, $embeddedImages);
    if ($logId) {
        try {
            $status = $result['success'] ? 'sent' : 'failed';
            $err = $result['success'] ? null : (string) ($result['error'] ?? 'send failed');
            $up = $pdo->prepare('UPDATE email_logs SET status = ?, error_message = ?, sent_at = ? WHERE id = ?');
            $up->execute([$status, $err, $result['success'] ? date('Y-m-d H:i:s') : null, $logId]);
        } catch (Throwable $e) {
        }
    }
    return $result;
}

function notify_new_food_listing(int $listingId): void
{
    $pdo = db();
    try {
        $st = $pdo->prepare('SELECT fl.id, fl.title, fl.quantity, fl.unit, fl.pickup_address, fl.expiry_time, bp.business_name
            FROM food_listings fl
            LEFT JOIN business_profiles bp ON bp.user_id = fl.business_user_id
            WHERE fl.id = ? LIMIT 1');
        $st->execute([$listingId]);
        $listing = $st->fetch();
        if (!$listing) return;

        $recipients = $pdo->query('SELECT id, email FROM users WHERE role IN ("general_user","charity") AND status = "active"')->fetchAll();
        foreach ($recipients as $r) {
            if (!filter_var($r['email'], FILTER_VALIDATE_EMAIL)) continue;
            $tpl = email_template_new_listing([
                'title' => $listing['title'],
                'quantity' => formatQty((float) $listing['quantity']) . ' ' . $listing['unit'],
                'pickup_address' => $listing['pickup_address'] ?? '',
                'expiry_time' => $listing['expiry_time'] ? formatDate($listing['expiry_time']) : 'Not specified',
                'business_name' => $listing['business_name'] ?? 'Business',
                'listing_url' => app_url('modules/listings/view.php?id=' . $listingId),
            ]);
            send_notification_email((int) $r['id'], (string) $r['email'], 'new_listing', $tpl['subject'], $tpl['html'], $tpl['text'], 'listing', $listingId, (array) ($tpl['embedded_images'] ?? []));
        }
    } catch (Throwable $e) {
        error_log('[ResQFoodddd notify_new_food_listing] ' . $e->getMessage());
    }
}

function notify_reservation_created(int $reservationId): void
{
    $pdo = db();
    try {
        $st = $pdo->prepare('SELECT r.id, r.reserved_quantity, r.reservation_status, u.id AS user_id, u.full_name AS user_name, u.email AS user_email,
            fl.id AS listing_id, fl.title, fl.pickup_address, fl.unit, bu.id AS business_id, bu.email AS business_email, bp.business_name
            FROM reservations r
            JOIN users u ON u.id = r.reserved_by
            JOIN food_listings fl ON fl.id = r.listing_id
            JOIN users bu ON bu.id = fl.business_user_id
            LEFT JOIN business_profiles bp ON bp.user_id = bu.id
            WHERE r.id = ? LIMIT 1');
        $st->execute([$reservationId]);
        $row = $st->fetch();
        if (!$row) return;

        $qty = formatQty((float) $row['reserved_quantity']) . ' ' . ($row['unit'] ?? '');
        $bizTpl = email_template_reservation_created_for_business([
            'title' => $row['title'],
            'reserved_quantity' => $qty,
            'reserved_by' => $row['user_name'],
            'status' => statusLabel($row['reservation_status']),
            'pickup_address' => $row['pickup_address'] ?? '',
            'url' => app_url('modules/reservations/index.php'),
        ]);
        send_notification_email((int) $row['business_id'], (string) $row['business_email'], 'reservation_created_business', $bizTpl['subject'], $bizTpl['html'], $bizTpl['text'], 'reservation', $reservationId, (array) ($bizTpl['embedded_images'] ?? []));

        $usrTpl = email_template_reservation_confirmation_for_user([
            'title' => $row['title'],
            'reserved_quantity' => $qty,
            'business_name' => $row['business_name'] ?: 'Business',
            'pickup_address' => $row['pickup_address'] ?? '',
            'status' => statusLabel($row['reservation_status']),
            'url' => app_url('modules/reservations/my.php'),
        ]);
        send_notification_email((int) $row['user_id'], (string) $row['user_email'], 'reservation_confirmation_user', $usrTpl['subject'], $usrTpl['html'], $usrTpl['text'], 'reservation', $reservationId, (array) ($usrTpl['embedded_images'] ?? []));
    } catch (Throwable $e) {
        error_log('[ResQFoodddd notify_reservation_created] ' . $e->getMessage());
    }
}

function notify_reservation_confirmed(int $reservationId): void
{
    notify_food_delivered($reservationId);
}

function notify_food_delivered(int $reservationId): void
{
    $pdo = db();
    try {
        $st = $pdo->prepare('SELECT r.id, r.reserved_quantity, u.id user_id, u.email user_email, bu.id business_id, bu.email business_email, fl.title
            FROM reservations r
            JOIN users u ON u.id = r.reserved_by
            JOIN food_listings fl ON fl.id = r.listing_id
            JOIN users bu ON bu.id = fl.business_user_id
            WHERE r.id = ? LIMIT 1');
        $st->execute([$reservationId]);
        $row = $st->fetch();
        if (!$row) return;
        $tpl = email_template_delivery_completed([
            'title' => $row['title'],
            'reserved_quantity' => formatQty((float) $row['reserved_quantity']),
            'url' => app_url('modules/reservations/my.php'),
        ]);
        send_notification_email((int) $row['user_id'], (string) $row['user_email'], 'delivery_completed_user', $tpl['subject'], $tpl['html'], $tpl['text'], 'reservation', $reservationId, (array) ($tpl['embedded_images'] ?? []));
        send_notification_email((int) $row['business_id'], (string) $row['business_email'], 'delivery_completed_business', $tpl['subject'], $tpl['html'], $tpl['text'], 'reservation', $reservationId, (array) ($tpl['embedded_images'] ?? []));
    } catch (Throwable $e) {
        error_log('[ResQFoodddd notify_food_delivered] ' . $e->getMessage());
    }
}

function notify_report_submitted(int $reportId): void
{
    $pdo = db();
    $cfg = mail_config();
    if (empty($cfg['admin_email']) || !filter_var($cfg['admin_email'], FILTER_VALIDATE_EMAIL)) return;
    try {
        $st = $pdo->prepare('SELECT r.reason, r.details, u.full_name, u.email, fl.title AS listing_title
            FROM reports r
            JOIN users u ON u.id = r.report_by
            LEFT JOIN food_listings fl ON fl.id = r.listing_id
            WHERE r.id = ? LIMIT 1');
        $st->execute([$reportId]);
        $row = $st->fetch();
        if (!$row) return;
        $tpl = email_template_report_submitted_admin([
            'reason' => $row['reason'],
            'submitted_by' => $row['full_name'] . ' (' . $row['email'] . ')',
            'listing_title' => $row['listing_title'] ?? '',
            'url' => app_url('modules/admin/reports.php'),
        ]);
        send_notification_email(null, (string) $cfg['admin_email'], 'report_submitted_admin', $tpl['subject'], $tpl['html'], $tpl['text'], 'report', $reportId, (array) ($tpl['embedded_images'] ?? []));
    } catch (Throwable $e) {
        error_log('[ResQFoodddd notify_report_submitted] ' . $e->getMessage());
    }
}

function notify_new_user_registered(int $userId): void
{
    $pdo = db();
    $cfg = mail_config();
    try {
        $st = $pdo->prepare('SELECT id, full_name, email, role FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $u = $st->fetch();
        if (!$u) return;

        $wel = email_template_welcome_user(['name' => $u['full_name']]);
        send_notification_email((int) $u['id'], (string) $u['email'], 'welcome_user', $wel['subject'], $wel['html'], $wel['text'], 'user', $userId, (array) ($wel['embedded_images'] ?? []));

        if (!empty($cfg['admin_email']) && filter_var($cfg['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $adm = email_template_new_user_admin(['name' => $u['full_name'], 'email' => $u['email'], 'role' => roleLabel($u['role'])]);
            send_notification_email(null, (string) $cfg['admin_email'], 'new_user_admin', $adm['subject'], $adm['html'], $adm['text'], 'user', $userId, (array) ($adm['embedded_images'] ?? []));
        }
    } catch (Throwable $e) {
        error_log('[ResQFoodddd notify_new_user_registered] ' . $e->getMessage());
    }
}
