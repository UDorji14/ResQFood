<?php
/**
 * ResQFood — Listing Helper Functions
 * ─────────────────────────────────────
 * Shared queries and utilities for the listings module.
 * Include after: session.php, config/db.php, functions.php.
 */

// ── Fetch Helpers ─────────────────────────────────────────────────────────

/**
 * Fetch a single listing with its business profile data.
 * Returns null if not found.
 */
function getListing(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT fl.*,
               u.full_name  AS business_owner_name,
               u.email      AS business_email,
               bp.business_name,
               bp.business_type,
               bp.city      AS business_city,
               bp.pickup_notes AS default_pickup_notes,
               bp.verification_status,
               (SELECT li.image_path
                FROM   listing_images li
                WHERE  li.listing_id = fl.id AND li.is_primary = 1
                LIMIT  1)  AS primary_image
        FROM  food_listings fl
        JOIN  users u ON u.id = fl.business_user_id
        LEFT  JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE fl.id = ?
        LIMIT 1
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Return all listings for a business user, newest first.
 * Optionally filtered by status string ('' = all).
 */
function getListingsByBusiness(int $userId, string $statusFilter = ''): array
{
    $sql    = '
        SELECT fl.*,
               (SELECT COUNT(*)
                FROM   reservations r
                WHERE  r.listing_id = fl.id
                  AND  r.reservation_status = "reserved") AS active_reservations,
               (SELECT li.image_path
                FROM   listing_images li
                WHERE  li.listing_id = fl.id AND li.is_primary = 1
                LIMIT  1) AS primary_image
        FROM   food_listings fl
        WHERE  fl.business_user_id = ?
    ';
    $params = [$userId];

    if ($statusFilter !== '' && $statusFilter !== 'all') {
        $sql   .= ' AND fl.status = ?';
        $params[] = $statusFilter;
    }
    $sql .= ' ORDER BY fl.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Browse available listings with keyword/category/city filters.
 * Only returns truly live listings (status=available AND pickup_end in the future).
 */
function browseListings(array $filters = [], int $limit = 16, int $offset = 0): array
{
    [$where, $params] = buildBrowseWhere($filters);

    $stmt = db()->prepare('
        SELECT fl.*,
               bp.business_name,
               bp.city AS business_city,
               bp.business_type,
               (SELECT li.image_path
                FROM   listing_images li
                WHERE  li.listing_id = fl.id AND li.is_primary = 1
                LIMIT  1) AS primary_image
        FROM   food_listings fl
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE  ' . implode(' AND ', $where) . '
        ORDER  BY fl.created_at DESC
        LIMIT  ? OFFSET ?
    ');
    $stmt->execute(array_merge($params, [$limit, $offset]));
    return $stmt->fetchAll();
}

/**
 * Count live listings matching the same filters (for pagination).
 */
function countBrowseListings(array $filters = []): int
{
    [$where, $params] = buildBrowseWhere($filters);

    $stmt = db()->prepare('
        SELECT COUNT(*)
        FROM   food_listings fl
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        WHERE  ' . implode(' AND ', $where)
    );
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** @internal Build WHERE + params for browse queries */
function buildBrowseWhere(array $f): array
{
    $where  = ['fl.status = "available"', 'fl.pickup_end > NOW()'];
    $params = [];

    if (!empty($f['keyword'])) {
        $where[]  = '(fl.title LIKE ? OR fl.description LIKE ? OR fl.category LIKE ? OR bp.business_name LIKE ?)';
        $kw       = '%' . $f['keyword'] . '%';
        $params   = array_merge($params, [$kw, $kw, $kw, $kw]);
    }
    if (!empty($f['category'])) {
        $where[]  = 'fl.category = ?';
        $params[] = $f['category'];
    }
    if (!empty($f['city'])) {
        $where[]  = 'bp.city LIKE ?';
        $params[] = '%' . $f['city'] . '%';
    }

    return [$where, $params];
}

/**
 * Distinct categories from all published listings (for the browse filter).
 */
function getListingCategories(): array
{
    return db()
        ->query('SELECT DISTINCT category FROM food_listings WHERE category IS NOT NULL AND status = "available" ORDER BY category')
        ->fetchAll(PDO::FETCH_COLUMN);
}

// ── Expiry ────────────────────────────────────────────────────────────────

/**
 * Mark all overdue "available" listings as expired.
 * Call once at the top of browse/manage pages.
 */
function expireOldListings(): void
{
    db()->prepare('
        UPDATE food_listings
        SET    status = "expired", updated_at = NOW()
        WHERE  status = "available"
          AND  pickup_end < NOW()
    ')->execute([]);
}

// ── Access / Business Rules ───────────────────────────────────────────────

/**
 * Check whether a user can reserve a given listing.
 * Returns '' on success, or a human-readable error string.
 */
function canReserve(int $userId, string $role, array $listing): string
{
    if (!in_array($role, ['general_user', 'charity'], true)) {
        return 'Only general users and charities may reserve listings.';
    }
    if ($listing['status'] !== 'available') {
        return 'This listing is no longer available.';
    }
    if (strtotime($listing['pickup_end']) < time()) {
        return 'The pickup window for this listing has passed.';
    }
    if ($userId === (int) $listing['business_user_id']) {
        return 'You cannot reserve your own listing.';
    }

    // Duplicate check
    $stmt = db()->prepare('
        SELECT id FROM reservations
        WHERE  listing_id = ? AND reserved_by = ?
        LIMIT  1
    ');
    $stmt->execute([$listing['id'], $userId]);
    if ($stmt->fetch()) {
        return 'You have already reserved this listing.';
    }

    return '';
}

// ── Image Upload ──────────────────────────────────────────────────────────

/**
 * Validate and persist an uploaded image for a listing.
 * Returns the web-relative path saved to DB, or throws RuntimeException on failure.
 */
function uploadListingImage(array $file, int $listingId, bool $isPrimary = false): string
{
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Image must be 5 MB or smaller.');
    }

    // Trust the real MIME type, not the browser-supplied type
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $realType = $finfo->file($file['tmp_name']);
    if (!in_array($realType, $allowed, true)) {
        throw new RuntimeException('Invalid image type. Allowed: JPG, PNG, WebP, GIF.');
    }

    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext      = $extMap[$realType] ?? 'jpg';
    $filename = 'lst_' . $listingId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    // Absolute filesystem path
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'listings' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    $webPath = 'uploads/listings/' . $filename;

    db()->prepare('
        INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)
    ')->execute([$listingId, $webPath, $isPrimary ? 1 : 0]);

    return $webPath;
}

// ── Display Helpers ───────────────────────────────────────────────────────

/**
 * Human-readable label for the remaining pickup time.
 */
function pickupTimeLabel(string $start, string $end): string
{
    $sTs = strtotime($start);
    $eTs = strtotime($end);
    $now = time();

    if ($now > $eTs) return 'Pickup window passed';

    if ($now < $sTs) {
        $diff = $sTs - $now;
        if ($diff < 3600)  return 'Starts in ' . ceil($diff / 60) . ' min';
        if ($diff < 86400) return 'Starts in ' . ceil($diff / 3600) . ' hr';
        return 'Starts ' . date('d M, H:i', $sTs);
    }

    $rem = $eTs - $now;
    if ($rem < 3600) return 'Closes in ' . ceil($rem / 60) . ' min';
    return 'Open until ' . date('H:i', $eTs);
}

/**
 * Convert HTML datetime-local input value to MySQL DATETIME string.
 * Input: '2026-04-27T14:30' → Output: '2026-04-27 14:30:00'
 */
function normaliseDatetime(string $dt): string
{
    $dt = trim($dt);
    if ($dt === '') return '';
    // Handle both 'T' separator (HTML) and space separator (MySQL display)
    $dt = str_replace('T', ' ', $dt);
    // Ensure seconds are present
    return strlen($dt) === 16 ? $dt . ':00' : $dt;
}

/**
 * Convert a MySQL DATETIME string to HTML datetime-local value.
 * Input: '2026-04-27 14:30:00' → Output: '2026-04-27T14:30'
 */
function datetimeToInput(string $dt): string
{
    return $dt ? substr(str_replace(' ', 'T', $dt), 0, 16) : '';
}

/**
 * Fixed list of food categories.
 */
function listingCategoryOptions(): array
{
    return ['Bakery', 'Produce', 'Dairy', 'Prepared Meals', 'Canned Goods',
            'Beverages', 'Snacks', 'Grains & Pasta', 'Meat & Poultry', 'Other'];
}

/**
 * Fixed list of quantity units.
 */
function listingUnitOptions(): array
{
    return ['portions', 'kg', 'g', 'bags', 'boxes', 'trays', 'items', 'litres', 'packs'];
}

/**
 * Render a listing image or SVG placeholder.
 */
function listingImageTag(?string $path, string $alt = '', string $class = ''): string
{
    if ($path) {
        $src  = htmlspecialchars(baseUrl($path), ENT_QUOTES, 'UTF-8');
        $alt  = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="' . $alt . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }
    // SVG placeholder
    return '<div class="listing-img-placeholder ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true">
        <svg viewBox="0 0 80 60" width="52" fill="none">
            <rect x="8" y="4" width="64" height="52" rx="6" fill="#e8e0cc"/>
            <path d="M8 36l16-12 12 10 12-14 24 16" fill="none" stroke="#b8a880" stroke-width="2" stroke-linejoin="round"/>
            <circle cx="24" cy="20" r="6" fill="#b8a880" opacity=".6"/>
        </svg>
    </div>';
}
