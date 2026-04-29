<?php
/**
 * ResQFood — Impact Dashboard
 * ─────────────────────────────
 * Accessible to: admin (global view), business (per-business view).
 * General users and charities are redirected to dashboard.
 *
 * Shows approximate environmental and social impact metrics.
 * All figures are clearly labelled as estimates.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/admin.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['admin', 'business']);

$role = currentUserRole();
$uid  = currentUserId();

if ($role === 'admin') {
    // Global stats
    $stats = getGlobalImpactStats();

    // Platform listing stats by status for admin
    $pdo = db();
    $listing_stats = [];
    foreach ($pdo->query('SELECT status, COUNT(*) AS cnt FROM food_listings GROUP BY status')->fetchAll() as $r) {
        $listing_stats[$r['status']] = (int) $r['cnt'];
    }

    // Category breakdown of collected listings
    $catRows = $pdo->query('
        SELECT category, COUNT(*) AS cnt
        FROM   food_listings
        WHERE  status = "collected" AND category IS NOT NULL
        GROUP  BY category
        ORDER  BY cnt DESC
        LIMIT  8
    ')->fetchAll();

    // Recent impact records
    $recentImpact = $pdo->query('
        SELECT ir.*, fl.title, fl.category, bp.business_name
        FROM   impact_records ir
        JOIN   food_listings fl ON fl.id = ir.listing_id
        LEFT   JOIN business_profiles bp ON bp.user_id = fl.business_user_id
        ORDER  BY ir.recorded_at DESC
        LIMIT  10
    ')->fetchAll();

    // Top contributing businesses
    $topBiz = $pdo->query('
        SELECT bp.business_name, bp.city, COUNT(fl.id) AS pickups,
               COALESCE(SUM(ir.estimated_meals_saved), 0) AS meals,
               COALESCE(SUM(ir.estimated_kg_saved),    0) AS kg
        FROM   food_listings fl
        JOIN   business_profiles bp ON bp.user_id = fl.business_user_id
        LEFT   JOIN impact_records ir ON ir.listing_id = fl.id
        WHERE  fl.status = "collected"
        GROUP  BY bp.business_name, bp.city
        ORDER  BY pickups DESC
        LIMIT  8
    ')->fetchAll();

} else {
    // Business: own stats only
    $stats = getBusinessImpactStats($uid);

    $pdo = db();
    $listing_stats = [];
    $lsStmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM food_listings WHERE business_user_id = ? GROUP BY status');
    $lsStmt->execute([$uid]);
    foreach ($lsStmt->fetchAll() as $r) {
        $listing_stats[$r['status']] = (int) $r['cnt'];
    }

    $recentImpact = $pdo->prepare('
        SELECT ir.*, fl.title, fl.category
        FROM   impact_records ir
        JOIN   food_listings fl ON fl.id = ir.listing_id
        WHERE  fl.business_user_id = ?
        ORDER  BY ir.recorded_at DESC
        LIMIT  10
    ');
    $recentImpact->execute([$uid]);
    $recentImpact = $recentImpact->fetchAll();

    $catRows = $pdo->prepare('
        SELECT category, COUNT(*) AS cnt
        FROM   food_listings
        WHERE  business_user_id = ? AND status = "collected" AND category IS NOT NULL
        GROUP  BY category ORDER BY cnt DESC
    ');
    $catRows->execute([$uid]);
    $catRows = $catRows->fetchAll();

    $topBiz = [];
}

$pageTitle = $role === 'admin' ? 'Impact Dashboard' : 'My Impact';
require_once __DIR__ . '/../../partials/header.php';
if ($role === 'business') {
    require_once __DIR__ . '/../../partials/business_shell.php';
    renderBusinessShellStart('impact', 'My Impact', 'Track the measurable outcomes of your rescued-food operations.');
}
?>
<?php if ($role !== 'business'): ?>
<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1><?= $role === 'admin' ? 'Platform Impact Dashboard' : 'My Impact Summary' ?></h1>
            <p class="text-muted"><?= $role === 'admin' ? 'Environmental and social metrics across all ResQFood activity.' : 'Your contribution to reducing food waste and feeding communities.' ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Approximation disclaimer ──────────────────────────── -->
<div class="impact-disclaimer">
    <svg viewBox="0 0 18 18" width="16" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M9 8v4m0-6h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    All figures marked <strong style="color:var(--olive)">&sim;</strong> are approximate estimates based on food quantity and standard conversion factors (~350g per meal, ~2.5 kg CO₂ per kg food). They are not scientifically certified measurements.
</div>

<!-- ── Hero Impact Numbers ──────────────────────────────────── -->
<div class="impact-hero-grid">

    <div class="impact-metric">
        <div class="impact-metric__icon">
            <svg viewBox="0 0 24 24" width="22" fill="none"><path d="M9 12l2 2 4-4m-6 8a9 9 0 110-18 9 9 0 010 18z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="impact-metric__number"><?= number_format($stats['total_pickups']) ?></div>
        <div class="impact-metric__label">Successful Pickups</div>
        <div class="impact-metric__approx">Listings collected</div>
    </div>

    <div class="impact-metric">
        <div class="impact-metric__icon">
            <svg viewBox="0 0 24 24" width="22" fill="none"><path d="M12 3c-3.9 2-7 5.8-7 9.5C5 16.6 8.1 20 12 20s7-3.4 7-7.5C19 8.8 15.9 5 12 3z" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <div class="impact-metric__number"><?= number_format($stats['meals_saved'], 0) ?></div>
        <div class="impact-metric__label">Meals Saved &sim;</div>
        <div class="impact-metric__approx">~350g per meal</div>
    </div>

    <div class="impact-metric">
        <div class="impact-metric__icon">
            <svg viewBox="0 0 24 24" width="22" fill="none"><path d="M20 7H4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="currentColor" stroke-width="1.8"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <div class="impact-metric__number">
            <?= number_format($stats['kg_saved'], 1) ?><span>kg</span>
        </div>
        <div class="impact-metric__label">Food Diverted &sim;</div>
        <div class="impact-metric__approx">From landfill</div>
    </div>

    <div class="impact-metric">
        <div class="impact-metric__icon">
            <svg viewBox="0 0 24 24" width="22" fill="none"><path d="M12 21a9 9 0 100-18 9 9 0 000 18z" stroke="currentColor" stroke-width="1.8"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <div class="impact-metric__number">
            <?= number_format($stats['co2_reduced'], 1) ?><span>kg</span>
        </div>
        <div class="impact-metric__label">CO&#8322; Avoided &sim;</div>
        <div class="impact-metric__approx">~2.5 kg CO&#8322;/kg food</div>
    </div>

</div>

<!-- ── Breakdown Charts ──────────────────────────────────────── -->
<div class="admin-2col">

    <!-- Listings by status -->
    <div class="card">
        <div class="card-header">
            <h3>Listings by Status</h3>
            <span style="font-size:.78rem;color:var(--text-muted)"><?= number_format($stats['total_listings']) ?> total</span>
        </div>
        <div class="card-body">
            <?php
            $statusBarMap = [
                'available' => ['Available', 'olive'],
                'reserved'  => ['Reserved',  'amber'],
                'collected' => ['Collected', 'collected'],
                'expired'   => ['Expired',   'terra'],
                'cancelled' => ['Cancelled', 'muted'],
            ];
            $total_ls = array_sum($listing_stats) ?: 1;
            foreach ($statusBarMap as $s => [$slabel, $fill]):
                $cnt = $listing_stats[$s] ?? 0;
                $pct = round($cnt / $total_ls * 100, 1);
            ?>
            <div class="progress-item">
                <div class="progress-item__header">
                    <span class="progress-item__name"><?= $slabel ?></span>
                    <span class="progress-item__count"><?= number_format($cnt) ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill progress-fill--<?= $fill ?>"
                         style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top collected categories -->
    <div class="card">
        <div class="card-header"><h3>Top Collected Categories</h3></div>
        <div class="card-body">
            <?php if (empty($catRows)): ?>
                <div class="empty-state" style="padding:1.5rem 0">
                    <p style="color:var(--text-muted);font-size:.85rem;margin:0">No collected listings recorded yet.</p>
                </div>
            <?php else: ?>
                <?php $maxCat = max(array_column($catRows, 'cnt')) ?: 1; ?>
                <?php foreach ($catRows as $cat): ?>
                <div class="progress-item">
                    <div class="progress-item__header">
                        <span class="progress-item__name"><?= e($cat['category'] ?? 'Other') ?></span>
                        <span class="progress-item__count"><?= number_format($cat['cnt']) ?> pickups</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill progress-fill--olive"
                             style="width:<?= round($cat['cnt']/$maxCat*100, 1) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Top Contributing Businesses (admin only) ─────────────── -->
<?php if ($role === 'admin' && !empty($topBiz)): ?>
<div class="card" style="margin-bottom:1.75rem">
    <div class="card-header">
        <h3>Top Contributing Businesses</h3>
        <span class="status-badge status-badge--green"><?= count($topBiz) ?> businesses</span>
    </div>
    <div class="card-body" style="padding:1rem 1.4rem">
        <?php foreach ($topBiz as $i => $biz): ?>
        <div class="biz-row">
            <div class="biz-row__rank biz-row__rank--<?= $i + 1 ?>"><?= $i + 1 ?></div>
            <div>
                <div class="biz-row__name"><?= e($biz['business_name']) ?></div>
                <?php if ($biz['city']): ?>
                <div class="biz-row__city"><?= e($biz['city']) ?></div>
                <?php endif; ?>
            </div>
            <div class="biz-row__stats">
                <strong><?= number_format($biz['pickups']) ?></strong> pickups<br>
                <span style="color:var(--text-muted);font-size:.75rem">
                    <?= number_format($biz['meals'], 0) ?> meals &sim; &middot;
                    <?= number_format($biz['kg'], 1) ?> kg &sim;
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Recent Impact Records ────────────────────────────────── -->
<?php if (!empty($recentImpact)): ?>
<div class="card">
    <div class="card-header">
        <h3>Recent Impact Records</h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <?php if ($role === 'admin'): ?><th>Business</th><?php endif; ?>
                    <th>Category</th>
                    <th>Meals &sim;</th>
                    <th>kg &sim;</th>
                    <th>CO&#8322; &sim;</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentImpact as $ir): ?>
                <tr>
                    <td style="font-weight:600;font-size:.87rem"><?= e(truncate($ir['title'], 36)) ?></td>
                    <?php if ($role === 'admin'): ?>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?= e($ir['business_name'] ?? '-') ?></td>
                    <?php endif; ?>
                    <td style="font-size:.82rem"><?= e($ir['category'] ?? '-') ?></td>
                    <td style="color:var(--olive);font-weight:700"><?= number_format($ir['estimated_meals_saved'], 1) ?></td>
                    <td style="font-size:.83rem"><?= number_format($ir['estimated_kg_saved'], 2) ?> kg</td>
                    <td style="font-size:.83rem"><?= number_format($ir['estimated_co2_reduced'], 2) ?> kg</td>
                    <td style="font-size:.77rem;color:var(--text-muted);white-space:nowrap"><?= formatDate($ir['recorded_at'], 'd M Y') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'business') renderBusinessShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
