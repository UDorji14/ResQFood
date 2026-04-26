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
?>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <div class="breadcrumb">
                <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>Impact</span>
            </div>
            <h1><?= $role === 'admin' ? 'Platform Impact Dashboard' : 'My Impact Summary' ?></h1>
            <p class="text-muted">
                <?= $role === 'admin' ? 'Aggregate environmental and social metrics across all ResQFood activity.' : 'Your contribution to reducing food waste and feeding communities.' ?>
                <strong style="color:var(--olive)">All figures are approximate estimates.</strong>
            </p>
        </div>
    </div>
</div>

<!-- ── Hero Impact Numbers ── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem">

    <div class="card" style="text-align:center;padding:2rem 1rem;background:linear-gradient(135deg,rgba(74,103,65,.08),rgba(74,103,65,.02))">
        <div style="font-size:3rem;font-weight:900;color:var(--olive);line-height:1"><?= number_format($stats['total_pickups']) ?></div>
        <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-top:.5rem">Successful Pickups</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">Listings collected</div>
    </div>

    <div class="card" style="text-align:center;padding:2rem 1rem;background:linear-gradient(135deg,rgba(74,103,65,.08),rgba(74,103,65,.02))">
        <div style="font-size:3rem;font-weight:900;color:var(--olive);line-height:1"><?= number_format($stats['meals_saved'], 0) ?></div>
        <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-top:.5rem">Meals Saved ~</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">~350g per meal</div>
    </div>

    <div class="card" style="text-align:center;padding:2rem 1rem;background:linear-gradient(135deg,rgba(74,103,65,.08),rgba(74,103,65,.02))">
        <div style="font-size:3rem;font-weight:900;color:var(--olive);line-height:1"><?= number_format($stats['kg_saved'], 1) ?><span style="font-size:1.5rem">kg</span></div>
        <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-top:.5rem">Food Diverted ~</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">From landfill</div>
    </div>

    <div class="card" style="text-align:center;padding:2rem 1rem;background:linear-gradient(135deg,rgba(74,103,65,.08),rgba(74,103,65,.02))">
        <div style="font-size:3rem;font-weight:900;color:var(--olive);line-height:1"><?= number_format($stats['co2_reduced'], 1) ?><span style="font-size:1.5rem">kg</span></div>
        <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-top:.5rem">CO₂ Avoided ~</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">~2.5 kg CO₂/kg food</div>
    </div>

</div>

<!-- ── Listing Status Breakdown + Category Breakdown ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.75rem">

    <!-- Listing status -->
    <div class="card">
        <div class="card-header"><h3>Listings by Status</h3></div>
        <div class="card-body">
            <?php
            $statusColors = [
                'available' => 'var(--olive)', 'reserved' => '#b8860b',
                'collected' => 'var(--olive)', 'expired' => 'var(--terra)',
                'cancelled' => '#999',
            ];
            $total_ls = array_sum($listing_stats) ?: 1;
            foreach (['available', 'reserved', 'collected', 'expired', 'cancelled'] as $s):
                $cnt  = $listing_stats[$s] ?? 0;
                $pct  = round($cnt / $total_ls * 100, 1);
                $col  = $statusColors[$s] ?? '#999';
            ?>
            <div style="margin-bottom:.85rem">
                <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.3rem">
                    <span style="font-weight:600;text-transform:capitalize"><?= statusLabel($s) ?></span>
                    <span style="color:var(--text-muted)"><?= number_format($cnt) ?> (<?= $pct ?>%)</span>
                </div>
                <div style="height:8px;background:var(--sand);border-radius:var(--r-pill);overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:var(--r-pill);transition:width 600ms ease"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.75rem">
                Total listings: <?= number_format($stats['total_listings']) ?>
            </div>
        </div>
    </div>

    <!-- Category breakdown -->
    <div class="card">
        <div class="card-header"><h3>Top Collected Categories</h3></div>
        <div class="card-body">
            <?php if (empty($catRows)): ?>
                <p class="text-muted" style="font-size:.85rem">No collected listings yet.</p>
            <?php else: ?>
                <?php
                $maxCat = max(array_column($catRows, 'cnt')) ?: 1;
                foreach ($catRows as $cat): ?>
                <div style="margin-bottom:.85rem">
                    <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:.3rem">
                        <span style="font-weight:600"><?= e($cat['category'] ?? 'Other') ?></span>
                        <span style="color:var(--text-muted)"><?= number_format($cat['cnt']) ?> pickups</span>
                    </div>
                    <div style="height:8px;background:var(--sand);border-radius:var(--r-pill);overflow:hidden">
                        <div style="height:100%;width:<?= round($cat['cnt']/$maxCat*100, 1) ?>%;background:var(--olive);border-radius:var(--r-pill)"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Top Businesses (admin only) ── -->
<?php if ($role === 'admin' && !empty($topBiz)): ?>
<div class="card" style="margin-bottom:1.75rem">
    <div class="card-header"><h3>Top Contributing Businesses</h3></div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>Business</th><th>City</th><th>Pickups</th><th>Meals~</th><th>kg Diverted~</th></tr>
            </thead>
            <tbody>
                <?php foreach ($topBiz as $biz): ?>
                <tr>
                    <td style="font-weight:700"><?= e($biz['business_name']) ?></td>
                    <td style="font-size:.83rem;color:var(--text-muted)"><?= e($biz['city'] ?? '—') ?></td>
                    <td style="font-weight:700;color:var(--olive)"><?= number_format($biz['pickups']) ?></td>
                    <td><?= number_format($biz['meals'], 0) ?> ~</td>
                    <td><?= number_format($biz['kg'], 1) ?> kg ~</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Recent Impact Records ── -->
<?php if (!empty($recentImpact)): ?>
<div class="card">
    <div class="card-header"><h3>Recent Impact Records</h3></div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <?php if ($role === 'admin'): ?><th>Business</th><?php endif; ?>
                    <th>Category</th>
                    <th>Meals ~</th>
                    <th>kg ~</th>
                    <th>CO₂ ~</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentImpact as $ir): ?>
                <tr>
                    <td style="font-weight:600;font-size:.87rem"><?= e(truncate($ir['title'], 35)) ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td style="font-size:.82rem;color:var(--text-muted)"><?= e($ir['business_name'] ?? '—') ?></td>
                    <?php endif; ?>
                    <td style="font-size:.82rem"><?= e($ir['category'] ?? '—') ?></td>
                    <td style="color:var(--olive);font-weight:700"><?= number_format($ir['estimated_meals_saved'], 1) ?></td>
                    <td><?= number_format($ir['estimated_kg_saved'], 2) ?> kg</td>
                    <td><?= number_format($ir['estimated_co2_reduced'], 2) ?> kg</td>
                    <td style="font-size:.78rem;color:var(--text-muted)"><?= formatDate($ir['recorded_at'], 'd M Y') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<p style="margin-top:1.25rem;font-size:.78rem;color:var(--text-muted);text-align:center">
    All figures marked ~ are estimates based on food type, quantity, and standard conversion factors. They are not scientifically certified.
</p>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
