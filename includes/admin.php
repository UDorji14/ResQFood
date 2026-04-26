<?php
/**
 * ResQFood — Admin Helper Functions
 * ────────────────────────────────────
 * Centralised queries and utilities used across the admin module.
 * Include after: session.php, config/db.php, functions.php, auth.php.
 */

// ── System Stats ─────────────────────────────────────────────────────────

/**
 * Return an associative array of platform-wide aggregate counts.
 */
function getSystemStats(): array
{
    $pdo = db();

    // User breakdown by role + overall active users
    $userRows = $pdo->query('
        SELECT role, status, COUNT(*) AS cnt
        FROM   users
        GROUP  BY role, status
    ')->fetchAll();

    $byRole   = [];
    $byStatus = [];
    $total    = 0;
    foreach ($userRows as $r) {
        $byRole[$r['role']]     = ($byRole[$r['role']]     ?? 0) + (int) $r['cnt'];
        $byStatus[$r['status']] = ($byStatus[$r['status']] ?? 0) + (int) $r['cnt'];
        $total                 += (int) $r['cnt'];
    }

    // Listing breakdown by status
    $listingRows = $pdo->query('
        SELECT status, COUNT(*) AS cnt FROM food_listings GROUP BY status
    ')->fetchAll();
    $listingsByStatus = [];
    $totalListings    = 0;
    foreach ($listingRows as $r) {
        $listingsByStatus[$r['status']] = (int) $r['cnt'];
        $totalListings                 += (int) $r['cnt'];
    }

    // Reservation breakdown by status
    $resRows = $pdo->query('
        SELECT reservation_status, COUNT(*) AS cnt FROM reservations GROUP BY reservation_status
    ')->fetchAll();
    $resByStatus  = [];
    $totalRes     = 0;
    foreach ($resRows as $r) {
        $resByStatus[$r['reservation_status']] = (int) $r['cnt'];
        $totalRes                             += (int) $r['cnt'];
    }

    // Pending verifications (business/charity profiles awaiting review)
    $pendingVerif = (int) $pdo->query('
        SELECT (SELECT COUNT(*) FROM business_profiles WHERE verification_status = "pending") +
               (SELECT COUNT(*) FROM charity_profiles  WHERE verification_status = "pending")
    ')->fetchColumn();

    // Open reports
    $openReports = (int) $pdo->query('
        SELECT COUNT(*) FROM reports WHERE report_status = "open"
    ')->fetchColumn();

    // Impact totals from impact_records
    $impact = $pdo->query('
        SELECT COUNT(*)                           AS total_records,
               COALESCE(SUM(estimated_meals_saved), 0) AS meals,
               COALESCE(SUM(estimated_kg_saved),    0) AS kg,
               COALESCE(SUM(estimated_co2_reduced),  0) AS co2
        FROM   impact_records
    ')->fetch();

    return [
        'users'             => $total,
        'by_role'           => $byRole,
        'by_status'         => $byStatus,
        'listings_total'    => $totalListings,
        'listings'          => $listingsByStatus,
        'reservations_total'=> $totalRes,
        'reservations'      => $resByStatus,
        'pending_verif'     => $pendingVerif,
        'open_reports'      => $openReports,
        'impact'            => $impact,
    ];
}

// ── User Management ───────────────────────────────────────────────────────

/**
 * Return paginated user list with optional role/status/keyword filters.
 */
function adminGetUsers(array $filters = [], int $limit = 25, int $offset = 0): array
{
    $where  = ['1'];
    $params = [];

    if (!empty($filters['role'])) {
        $where[]  = 'u.role = ?';
        $params[] = $filters['role'];
    }
    if (!empty($filters['status'])) {
        $where[]  = 'u.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['keyword'])) {
        $where[]  = '(u.full_name LIKE ? OR u.email LIKE ?)';
        $kw       = '%' . $filters['keyword'] . '%';
        $params[] = $kw;
        $params[] = $kw;
    }

    $sql = '
        SELECT u.*,
               bp.business_name, bp.verification_status AS biz_verif,
               cp.organization_name, cp.verification_status AS charity_verif,
               (SELECT COUNT(*) FROM food_listings fl WHERE fl.business_user_id = u.id) AS listing_count,
               (SELECT COUNT(*) FROM reservations r WHERE r.reserved_by = u.id) AS res_count
        FROM   users u
        LEFT   JOIN business_profiles bp ON bp.user_id = u.id
        LEFT   JOIN charity_profiles  cp ON cp.user_id = u.id
        WHERE  ' . implode(' AND ', $where) . '
        ORDER  BY u.created_at DESC
        LIMIT  ? OFFSET ?
    ';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Count users matching filters (for pagination).
 */
function adminCountUsers(array $filters = []): int
{
    $where  = ['1'];
    $params = [];
    if (!empty($filters['role']))    { $where[] = 'role = ?';                        $params[] = $filters['role']; }
    if (!empty($filters['status']))  { $where[] = 'status = ?';                      $params[] = $filters['status']; }
    if (!empty($filters['keyword'])) {
        $where[] = '(full_name LIKE ? OR email LIKE ?)';
        $kw = '%' . $filters['keyword'] . '%';
        $params[] = $kw; $params[] = $kw;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Fetch one user with full profile data.
 */
function adminGetUser(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT u.*,
               bp.id AS biz_profile_id, bp.business_name, bp.business_type,
               bp.address AS biz_address, bp.city AS biz_city,
               bp.description AS biz_desc, bp.verification_status AS biz_verif,
               cp.id AS charity_profile_id, cp.organization_name, cp.contact_person,
               cp.address AS charity_address, cp.city AS charity_city,
               cp.description AS charity_desc, cp.verification_status AS charity_verif
        FROM   users u
        LEFT   JOIN business_profiles bp ON bp.user_id = u.id
        LEFT   JOIN charity_profiles  cp ON cp.user_id = u.id
        WHERE  u.id = ?
        LIMIT  1
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Listing Oversight ─────────────────────────────────────────────────────

/**
 * Return all listings for admin with owner info, filtered optionally.
 */
function adminGetListings(array $filters = [], int $limit = 25, int $offset = 0): array
{
    $where  = ['1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[]  = 'fl.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['keyword'])) {
        $where[]  = '(fl.title LIKE ? OR bp.business_name LIKE ?)';
        $kw       = '%' . $filters['keyword'] . '%';
        $params[] = $kw;
        $params[] = $kw;
    }

    $stmt = db()->prepare('
        SELECT fl.*,
               u.full_name AS owner_name, u.email AS owner_email,
               bp.business_name, bp.city AS business_city,
               (SELECT COUNT(*) FROM reservations r WHERE r.listing_id = fl.id) AS total_reservations
        FROM   food_listings fl
        JOIN   users u ON u.id = fl.business_user_id
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE  ' . implode(' AND ', $where) . '
        ORDER  BY fl.created_at DESC
        LIMIT  ? OFFSET ?
    ');
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Report Management ─────────────────────────────────────────────────────

/**
 * Return reports list with reporter and listing data.
 */
function adminGetReports(string $statusFilter = '', int $limit = 25, int $offset = 0): array
{
    $where  = ['1'];
    $params = [];
    if ($statusFilter !== '' && $statusFilter !== 'all') {
        $where[]  = 'rp.report_status = ?';
        $params[] = $statusFilter;
    }
    $stmt = db()->prepare('
        SELECT rp.*,
               u.full_name  AS reporter_name,
               u.email      AS reporter_email,
               fl.title     AS listing_title,
               ru.full_name AS reported_user_name,
               au.full_name AS reviewer_name
        FROM   reports rp
        JOIN   users u  ON u.id = rp.report_by
        LEFT   JOIN food_listings fl ON fl.id  = rp.listing_id
        LEFT   JOIN users ru         ON ru.id  = rp.reported_user
        LEFT   JOIN users au         ON au.id  = rp.reviewed_by
        WHERE  ' . implode(' AND ', $where) . '
        ORDER  BY rp.created_at DESC
        LIMIT  ? OFFSET ?
    ');
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Fetch a single report with all related data.
 */
function adminGetReport(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT rp.*,
               u.full_name  AS reporter_name,
               u.email      AS reporter_email,
               u.role       AS reporter_role,
               fl.title     AS listing_title,
               fl.status    AS listing_status,
               ru.full_name AS reported_user_name,
               ru.email     AS reported_user_email,
               au.full_name AS reviewer_name
        FROM   reports rp
        JOIN   users u  ON u.id = rp.report_by
        LEFT   JOIN food_listings fl ON fl.id = rp.listing_id
        LEFT   JOIN users ru ON ru.id = rp.reported_user
        LEFT   JOIN users au ON au.id = rp.reviewed_by
        WHERE  rp.id = ?
        LIMIT  1
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Impact Queries ────────────────────────────────────────────────────────

/**
 * Return global impact stats.
 * Uses impact_records where available; derives fallback from collected listings.
 */
function getGlobalImpactStats(): array
{
    $pdo = db();

    // Primary: sum from impact_records
    $impact = $pdo->query('
        SELECT COUNT(*)                               AS recorded_pickups,
               COALESCE(SUM(estimated_meals_saved), 0) AS meals,
               COALESCE(SUM(estimated_kg_saved),    0) AS kg,
               COALESCE(SUM(estimated_co2_reduced),  0) AS co2
        FROM   impact_records
    ')->fetch();

    // Fallback: collected listings not in impact_records
    // Estimate 8 meals and 2.8 kg per uncounted collected listing
    $uncounted = (int) $pdo->query('
        SELECT COUNT(*) FROM food_listings fl
        WHERE  fl.status = "collected"
          AND  fl.id NOT IN (SELECT DISTINCT listing_id FROM impact_records)
    ')->fetchColumn();

    $totalPickups = (int) $pdo->query('
        SELECT COUNT(*) FROM food_listings WHERE status = "collected"
    ')->fetchColumn();
    $totalListings = (int) $pdo->query('SELECT COUNT(*) FROM food_listings')->fetchColumn();

    return [
        'total_listings'  => $totalListings,
        'total_pickups'   => $totalPickups,
        'meals_saved'     => round((float) $impact['meals'] + $uncounted * 8,   1),
        'kg_saved'        => round((float) $impact['kg']    + $uncounted * 2.8, 1),
        'co2_reduced'     => round((float) $impact['co2']   + $uncounted * 7.0, 1),
    ];
}

/**
 * Return per-business impact stats for a business user.
 */
function getBusinessImpactStats(int $businessUserId): array
{
    $pdo = db();

    $impactStmt = $pdo->prepare('
        SELECT COALESCE(SUM(ir.estimated_meals_saved), 0) AS meals,
               COALESCE(SUM(ir.estimated_kg_saved),    0) AS kg,
               COALESCE(SUM(ir.estimated_co2_reduced),  0) AS co2
        FROM   impact_records ir
        JOIN   food_listings fl ON fl.id = ir.listing_id
        WHERE  fl.business_user_id = ?
    ');
    $impactStmt->execute([$businessUserId]);
    $row = $impactStmt->fetch();

    $uncStmt = $pdo->prepare('
        SELECT COUNT(*) FROM food_listings
        WHERE  business_user_id = ?
          AND  status = "collected"
          AND  id NOT IN (SELECT DISTINCT listing_id FROM impact_records)
    ');
    $uncStmt->execute([$businessUserId]);
    $uncounted = (int) $uncStmt->fetchColumn();

    $tpStmt = $pdo->prepare('
        SELECT COUNT(*) FROM food_listings WHERE business_user_id = ? AND status = "collected"
    ');
    $tpStmt->execute([$businessUserId]);
    $totalPickups = (int) $tpStmt->fetchColumn();

    $tlStmt = $pdo->prepare('SELECT COUNT(*) FROM food_listings WHERE business_user_id = ?');
    $tlStmt->execute([$businessUserId]);
    $totalListings = (int) $tlStmt->fetchColumn();

    return [
        'total_listings' => $totalListings,
        'total_pickups'  => $totalPickups,
        'meals_saved'    => round((float) $row['meals'] + $uncounted * 8,   1),
        'kg_saved'       => round((float) $row['kg']    + $uncounted * 2.8, 1),
        'co2_reduced'    => round((float) $row['co2']   + $uncounted * 7.0, 1),
    ];
}

// ── Recent Activity ───────────────────────────────────────────────────────

/**
 * Get recent audit log entries for admin dashboard.
 */
function fetchRecentAuditLogs(int $limit = 15): array
{
    $stmt = db()->prepare('
        SELECT al.*, u.full_name, u.role
        FROM   audit_logs al
        LEFT   JOIN users u ON u.id = al.user_id
        ORDER  BY al.created_at DESC
        LIMIT  ?
    ');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get recent registered users.
 */
function getRecentUsers(int $limit = 8): array
{
    $stmt = db()->prepare('
        SELECT id, full_name, email, role, status, created_at
        FROM   users
        ORDER  BY created_at DESC
        LIMIT  ?
    ');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
