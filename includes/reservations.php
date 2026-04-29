<?php
/**
 * ResQFood — Reservation Helper Functions
 * ──────────────────────────────────────────
 * Shared logic for creating, transitioning, and logging reservations.
 * Include after: session.php, config/db.php, functions.php, auth.php.
 */

// ── Fetch ─────────────────────────────────────────────────────────────────

/**
 * Fetch a single reservation with full listing + user data.
 */
function getReservation(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT r.*,
               fl.title,      fl.category,    fl.quantity,     fl.available_quantity, fl.unit,
               fl.description, fl.pickup_address,
               fl.pickup_start, fl.pickup_end,  fl.status AS listing_status,
               fl.business_user_id,
               u.full_name    AS reserved_by_name,
               u.email        AS reserved_by_email,
               u.role         AS reserver_role,
               bu.full_name   AS business_owner_name,
               bp.business_name, bp.city AS business_city,
               bp.pickup_notes AS business_pickup_notes
        FROM   reservations r
        JOIN   food_listings fl ON fl.id  = r.listing_id
        JOIN   users u           ON u.id  = r.reserved_by
        JOIN   users bu          ON bu.id = fl.business_user_id
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE  r.id = ?
        LIMIT  1
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all reservations for the current user, newest first.
 */
function getMyReservations(int $userId, string $statusFilter = ''): array
{
    $sql    = '
        SELECT r.*,
               fl.title, fl.category, fl.quantity, fl.available_quantity, fl.unit,
               fl.pickup_address, fl.pickup_start, fl.pickup_end,
               fl.status AS listing_status,
               bp.business_name, bp.city AS business_city
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE  r.reserved_by = ?
    ';
    $params = [$userId];

    if ($statusFilter !== '' && $statusFilter !== 'all') {
        $sql   .= ' AND r.reservation_status = ?';
        $params[] = $statusFilter;
    }
    $sql .= ' ORDER BY r.reserved_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get all incoming reservations for a business user's listings.
 */
function getBusinessReservations(int $businessUserId, string $statusFilter = ''): array
{
    $sql    = '
        SELECT r.*,
               fl.title, fl.category, fl.quantity, fl.available_quantity, fl.unit,
               fl.pickup_start, fl.pickup_end,
               u.full_name AS reserved_by_name,
               u.email     AS reserved_by_email,
               u.role      AS reserver_role
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        JOIN   users u          ON u.id  = r.reserved_by
        WHERE  fl.business_user_id = ?
    ';
    $params = [$businessUserId];

    if ($statusFilter !== '' && $statusFilter !== 'all') {
        $sql   .= ' AND r.reservation_status = ?';
        $params[] = $statusFilter;
    }
    $sql .= ' ORDER BY r.reserved_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Status Transitions ────────────────────────────────────────────────────

/**
 * Write a row to reservation_status_logs.
 *
 * @param int         $reservationId
 * @param string|null $oldStatus     null for the initial creation entry
 * @param string      $newStatus
 * @param int|null    $changedBy     user_id who triggered the change (null = system)
 * @param string|null $note          free-text context
 */
function logReservationStatus(
    int    $reservationId,
    ?string $oldStatus,
    string  $newStatus,
    ?int    $changedBy = null,
    ?string $note      = null
): void {
    db()->prepare('
        INSERT INTO reservation_status_logs
               (reservation_id, old_status, new_status, changed_by, note)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([$reservationId, $oldStatus, $newStatus, $changedBy, $note]);
}

// ── Notification Helper ───────────────────────────────────────────────────

/**
 * Insert a notification for a user.
 * Silent — logs errors but never throws.
 */
function createNotification(int $userId, string $title, string $message, ?string $link = null): void
{
    try {
        db()->prepare('
            INSERT INTO notifications (user_id, title, message, link)
            VALUES (?, ?, ?, ?)
        ')->execute([$userId, $title, $message, $link]);
    } catch (Throwable $e) {
        error_log('[ResQFood Notification] ' . $e->getMessage());
    }
}

// ── Impact Records ────────────────────────────────────────────────────────

/**
 * Create an impact record when a reservation is confirmed as collected.
 * Uses the reservation's reserved_quantity (not the full listing quantity)
 * so partial pickups are tracked accurately.
 */
function createImpactRecord(int $listingId, int $reservationId): void
{
    $stmt = db()->prepare('
        SELECT r.reserved_quantity, fl.unit
        FROM   reservations r
        JOIN   food_listings fl ON fl.id = r.listing_id
        WHERE  r.id = ? LIMIT 1
    ');
    $stmt->execute([$reservationId]);
    $row = $stmt->fetch();
    if (!$row) return;

    $qty  = (float) $row['reserved_quantity'];
    $unit = strtolower(trim($row['unit'] ?? 'portions'));

    // Rough unit-to-kg conversion factors
    $kgPerUnit = match (true) {
        str_contains($unit, 'kg')      => 1.0,
        str_contains($unit, 'litre')   => 1.0,
        str_contains($unit, 'g')       => 0.001,
        str_contains($unit, 'portion') => 0.35,
        str_contains($unit, 'bag')     => 0.80,
        str_contains($unit, 'box')     => 1.50,
        str_contains($unit, 'tray')    => 0.60,
        str_contains($unit, 'pack')    => 0.50,
        default                         => 0.40,
    };

    $kgSaved    = round($qty * $kgPerUnit, 3);
    $mealsSaved = round($kgSaved / 0.35, 2);   // ~350 g per meal
    $co2Reduced = round($kgSaved * 2.50, 3);   // ~2.5 kg CO2 per kg food saved

    // Skip if record already exists for this reservation
    $exists = db()->prepare('SELECT id FROM impact_records WHERE reservation_id = ? LIMIT 1');
    $exists->execute([$reservationId]);
    if ($exists->fetch()) return;

    db()->prepare('
        INSERT INTO impact_records
               (listing_id, reservation_id, estimated_meals_saved, estimated_kg_saved, estimated_co2_reduced)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([$listingId, $reservationId, $mealsSaved, $kgSaved, $co2Reduced]);
}

// ── Display Helpers ───────────────────────────────────────────────────────

/**
 * Return true if the reservation can still be cancelled by the owner.
 */
function isCancellable(string $reservationStatus): bool
{
    return $reservationStatus === 'reserved';
}

/**
 * Return true if the reservation can be confirmed as collected by business/admin.
 */
function isConfirmable(string $reservationStatus): bool
{
    return $reservationStatus === 'reserved';
}

/**
 * Render a styled pickup-code badge.
 */
function pickupCodeBadge(string $code): string
{
    return '<span class="pickup-code">' . e($code) . '</span>';
}
