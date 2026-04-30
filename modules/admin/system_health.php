<?php
/**
 * ResQFood — System Health Check
 * Internal Admin Tool to verify core routes, database status, and tables.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['admin']);

$pdo = db();

// ---------------------------------------------------------
// Helper: File / Route Check
// ---------------------------------------------------------
function checkFileExists($relativePath) {
    $fullPath = realpath(__DIR__ . '/../../' . ltrim($relativePath, '/'));
    if ($fullPath && file_exists($fullPath)) {
        return ['status' => 'pass', 'msg' => 'File exists: ' . e($relativePath)];
    }
    return ['status' => 'fail', 'msg' => 'Missing file: ' . e($relativePath)];
}

// ---------------------------------------------------------
// Helper: Table Check
// ---------------------------------------------------------
function checkTableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '" . addslashes($tableName) . "'");
        if ($stmt->rowCount() > 0) {
            // Get rough count safely
            $countStmt = $pdo->query('SELECT COUNT(*) FROM ' . $tableName);
            $count = $countStmt->fetchColumn();
            return ['status' => 'pass', 'msg' => 'Exists (' . number_format($count) . ' rows)'];
        }
        return ['status' => 'fail', 'msg' => 'Table missing: ' . $tableName];
    } catch (Throwable $e) {
        return ['status' => 'fail', 'msg' => 'Error querying table: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------
// Define Tests
// ---------------------------------------------------------
$results = [];

// A. Database Connection
try {
    $pdo->query('SELECT 1');
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $results['Database'][] = [
        'name' => 'Database Connection',
        'result' => ['status' => 'pass', 'msg' => 'Connected successfully to ' . e($dbName)]
    ];
} catch (Throwable $e) {
    $results['Database'][] = [
        'name' => 'Database Connection',
        'result' => ['status' => 'fail', 'msg' => 'Connection failed: ' . $e->getMessage()]
    ];
}

// B. Route / Page Checks
$routes = [
    'Public Landing' => 'index.php',
    'Login Page' => 'login.php',
    'Register Page' => 'register.php',
    'Admin Dashboard' => 'modules/admin/dashboard.php',
    'Listings Browse' => 'modules/listings/browse.php',
    'Listings Create' => 'modules/listings/create.php',
    'My Reservations' => 'modules/reservations/my.php',
    'Reports Index' => 'modules/reports/index.php',
    'Profile Page' => 'modules/profile/index.php',
];

foreach ($routes as $label => $path) {
    $results['Routes & Pages'][] = [
        'name' => $label,
        'result' => checkFileExists($path)
    ];
}

// C. Important Tables
$tables = [
    'users',
    'business_profiles',
    'charity_profiles',
    'food_listings',
    'listing_images',
    'reservations',
    'reservation_status_logs',
    'reports',
    'impact_records',
    'audit_logs'
];
foreach ($tables as $t) {
    $results['Database Tables'][] = [
        'name' => 'Table: ' . $t,
        'result' => checkTableExists($pdo, $t)
    ];
}

// D. Role/Account System Checks
try {
    $stmt = $pdo->query('SELECT DISTINCT role FROM users');
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $results['Role System'][] = [
        'name' => 'Active Roles in DB',
        'result' => ['status' => 'pass', 'msg' => 'Roles found: ' . implode(', ', array_map('e', $roles))]
    ];
    
    $adminCount = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
    $results['Role System'][] = [
        'name' => 'Admin Accounts',
        'result' => $adminCount > 0 ? 
            ['status' => 'pass', 'msg' => $adminCount . ' admin(s) exist'] : 
            ['status' => 'fail', 'msg' => 'No admin accounts found!']
    ];
} catch (Throwable $e) {
    $results['Role System'][] = [
        'name' => 'Role Check',
        'result' => ['status' => 'warning', 'msg' => 'Could not fetch roles']
    ];
}

// E. Core Helpers/Dependencies
$helpers = [
    'Session helper' => 'includes/session.php',
    'Auth helper' => 'includes/auth.php',
    'DB Config' => 'config/db.php',
    'Validation helper' => 'includes/validation.php',
    'CSRF helper' => 'includes/csrf.php',
];
foreach ($helpers as $label => $path) {
    $results['Core Modules'][] = [
        'name' => $label,
        'result' => checkFileExists($path)
    ];
}

// Optional content / Assets
$uploadDir = __DIR__ . '/../../uploads';
$hasUploads = is_dir($uploadDir) && is_writable($uploadDir);
$results['System Health'][] = [
    'name' => 'Uploads Directory',
    'result' => $hasUploads ? 
        ['status' => 'pass', 'msg' => 'Writable directory at /uploads'] :
        ['status' => 'warning', 'msg' => 'Missing or un-writable /uploads folder']
];

// Calculate Summaries
$totals = ['total' => 0, 'pass' => 0, 'fail' => 0, 'warning' => 0];
foreach ($results as $group => $checks) {
    foreach ($checks as $check) {
        $totals['total']++;
        $totals[$check['result']['status']]++;
    }
}

$pageTitle = 'System Health Check';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/admin_shell.php';

$actions = '<a href="?' . time() . '" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:0.4rem;"><svg viewBox="0 0 20 20" width="16" fill="none"><path d="M10 3a7 7 0 107 7h-2a5 5 0 11-5-5V3z" fill="currentColor"/><path d="M10 0v6l4-3-4-3z" fill="currentColor"/></svg>Run Tests Again</a>';
renderAdminShellStart('health', 'System Health Check', 'Internal diagnostics and route validation tool.', $actions);
?>

<style>
.test-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.test-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.test-card__val {
    font-size: 2.5rem;
    font-weight: 800;
    font-family: var(--f-display);
    line-height: 1;
    margin-bottom: 0.5rem;
}
.test-card__lbl {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}
.test-card--pass .test-card__val { color: #10b981; }
.test-card--fail .test-card__val { color: #ef4444; }
.test-card--warn .test-card__val { color: #f59e0b; }

.test-group {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    margin-bottom: 2rem;
    overflow: hidden;
}
.test-group__head {
    background: rgba(0,0,0,0.02);
    padding: 1rem 1.5rem;
    font-weight: 700;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    color: var(--text-dark);
}
.test-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.03);
}
.test-row:last-child {
    border-bottom: none;
}
.test-row__main {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}
.test-row__name {
    font-weight: 600;
    color: var(--text-dark);
    min-width: 180px;
}
.test-row__msg {
    font-size: 0.85rem;
    color: var(--text-muted);
}
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.badge-pass { background: rgba(16,185,129,0.1); color: #059669; }
.badge-fail { background: rgba(239,68,68,0.1); color: #dc2626; }
.badge-warn { background: rgba(245,158,11,0.1); color: #d97706; }
</style>

<!-- Summaries -->
<div class="test-summary">
    <div class="test-card">
        <div class="test-card__val"><?= $totals['total'] ?></div>
        <div class="test-card__lbl">Total Checks</div>
    </div>
    <div class="test-card test-card--pass">
        <div class="test-card__val"><?= $totals['pass'] ?></div>
        <div class="test-card__lbl">Passed</div>
    </div>
    <div class="test-card test-card--fail">
        <div class="test-card__val"><?= $totals['fail'] ?></div>
        <div class="test-card__lbl">Failed</div>
    </div>
    <div class="test-card test-card--warn">
        <div class="test-card__val"><?= $totals['warning'] ?></div>
        <div class="test-card__lbl">Warnings</div>
    </div>
</div>

<!-- Test Groups -->
<?php foreach ($results as $groupName => $checks): ?>
<div class="test-group">
    <div class="test-group__head"><?= e($groupName) ?></div>
    <div>
        <?php foreach ($checks as $check): ?>
            <?php 
                $status = $check['result']['status'];
                $badgeClass = $status === 'pass' ? 'badge-pass' : ($status === 'fail' ? 'badge-fail' : 'badge-warn');
            ?>
            <div class="test-row">
                <div class="test-row__main">
                    <span class="status-badge <?= $badgeClass ?>"><?= strtoupper($status) ?></span>
                    <span class="test-row__name"><?= e($check['name']) ?></span>
                    <span class="test-row__msg"><?= e($check['result']['msg']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
renderAdminShellEnd();
require_once __DIR__ . '/../../partials/footer.php';
?>
