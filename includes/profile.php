<?php
/**
 * ResQFood — Profile Helper Functions
 * ─────────────────────────────────────
 * Shared utilities for loading, checking, and rendering profile data.
 * Include after: session.php, config/db.php, functions.php, auth.php.
 */

// ── Profile Loaders ───────────────────────────────────────────────────────

/**
 * Fetch the business profile joined with user data for a given user_id.
 * Returns null if the profile row does not exist.
 */
function getBusinessProfile(int $userId): ?array
{
    $stmt = db()->prepare('
        SELECT bp.*,
               u.full_name, u.email, u.phone,
               u.status    AS account_status,
               u.created_at AS joined_at
        FROM   business_profiles bp
        JOIN   users u ON u.id = bp.user_id
        WHERE  bp.user_id = ?
        LIMIT  1
    ');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Fetch the charity profile joined with user data for a given user_id.
 */
function getCharityProfile(int $userId): ?array
{
    $stmt = db()->prepare('
        SELECT cp.*,
               u.full_name, u.email, u.phone,
               u.status    AS account_status,
               u.created_at AS joined_at
        FROM   charity_profiles cp
        JOIN   users u ON u.id = cp.user_id
        WHERE  cp.user_id = ?
        LIMIT  1
    ');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

// ── Completeness Checks ───────────────────────────────────────────────────

/**
 * Determine how complete a business profile is (0–100).
 * Used for the dashboard progress bar.
 */
function businessProfileCompletion(int $userId): int
{
    $p = getBusinessProfile($userId);
    if (!$p) return 0;

    $fields  = ['business_name', 'business_type', 'address', 'city', 'description', 'pickup_notes'];
    $filled  = count(array_filter($fields, fn($f) => trim((string)($p[$f] ?? '')) !== ''));
    return (int) round($filled / count($fields) * 100);
}

/**
 * Determine how complete a charity profile is (0–100).
 */
function charityProfileCompletion(int $userId): int
{
    $p = getCharityProfile($userId);
    if (!$p) return 0;

    $fields = ['organization_name', 'contact_person', 'address', 'city', 'description'];
    $filled = count(array_filter($fields, fn($f) => trim((string)($p[$f] ?? '')) !== ''));
    return (int) round($filled / count($fields) * 100);
}

/**
 * True when a business profile has the minimum required fields for posting listings.
 */
function isBusinessProfileComplete(int $userId): bool
{
    $p = getBusinessProfile($userId);
    return $p
        && trim((string)($p['business_name'] ?? '')) !== ''
        && trim((string)($p['address']       ?? '')) !== ''
        && trim((string)($p['city']          ?? '')) !== '';
}

/**
 * True when a charity profile has the minimum required fields.
 */
function isCharityProfileComplete(int $userId): bool
{
    $p = getCharityProfile($userId);
    return $p
        && trim((string)($p['organization_name'] ?? '')) !== ''
        && trim((string)($p['city']              ?? '')) !== '';
}

// ── Access Guards ─────────────────────────────────────────────────────────

/**
 * Redirect a business user to their profile page if it is not complete.
 * Call at the top of listing-creation pages.
 */
function requireBusinessProfileComplete(): void
{
    if (!isBusinessProfileComplete(currentUserId())) {
        setFlash('warning', 'Please complete your business profile before posting listings.');
        redirect(baseUrl('modules/profile/business_profile.php'));
    }
}

/**
 * Redirect a business user to the dashboard if their profile is not yet
 * verified by an admin.
 */
function requireBusinessVerified(): void
{
    $p = getBusinessProfile(currentUserId());
    if (!$p || $p['verification_status'] !== 'verified') {
        setFlash('warning', 'Your business account must be verified by an administrator before you can post listings.');
        redirect(baseUrl('dashboard.php'));
    }
}

// ── Render Helpers ────────────────────────────────────────────────────────

/**
 * Return an HTML <span> badge for a profile verification status.
 */
function verificationBadge(string $status): string
{
    $map = [
        'pending'  => ['status-badge--amber', 'Pending Verification'],
        'verified' => ['status-badge--green', 'Verified'],
        'rejected' => ['status-badge--red',   'Rejected'],
    ];
    [$class, $label] = $map[$status] ?? ['status-badge--default', ucfirst($status)];
    return '<span class="status-badge ' . $class . '">' . e($label) . '</span>';
}

/**
 * Render an HTML progress bar for a profile completion percentage.
 *
 * @param int    $pct    0–100
 * @param string $colour CSS custom property value (default: var(--olive))
 */
function profileProgressBar(int $pct, string $colour = 'var(--olive)'): string
{
    $safe = max(0, min(100, $pct));
    return sprintf(
        '<div class="progress-bar" role="progressbar" aria-valuenow="%1$d" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar__fill" style="width:%1$d%%;background:%2$s"></div>
        </div>',
        $safe,
        htmlspecialchars($colour, ENT_QUOTES, 'UTF-8')
    );
}
