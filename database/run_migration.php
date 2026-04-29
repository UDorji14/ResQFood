<?php
/**
 * ResQFood — Direct migration runner (run via browser: localhost/ResQFood/database/run_migration.php)
 */
$host    = 'localhost';
$dbname  = 'resqfood_db';
$user    = 'root';
$pass    = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die('DB connect failed: ' . $e->getMessage());
}

$log = [];

// Check + add available_quantity
$cols = $pdo->query("SHOW COLUMNS FROM food_listings LIKE 'available_quantity'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE food_listings ADD COLUMN available_quantity DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER quantity");
    $pdo->exec("UPDATE food_listings SET available_quantity = quantity WHERE status = 'available'");
    $pdo->exec("UPDATE food_listings SET available_quantity = 0.00 WHERE status IN ('reserved','collected','expired','cancelled')");
    $log[] = "✅ Added food_listings.available_quantity and seeded values";
} else {
    $log[] = "⏭ food_listings.available_quantity already exists";
}

// Check + add reserved_quantity
$cols2 = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'reserved_quantity'")->fetchAll();
if (empty($cols2)) {
    $pdo->exec("ALTER TABLE reservations ADD COLUMN reserved_quantity DECIMAL(8,2) NOT NULL DEFAULT 1.00 AFTER pickup_code");
    $pdo->exec("UPDATE reservations r JOIN food_listings fl ON fl.id = r.listing_id SET r.reserved_quantity = fl.quantity WHERE r.reserved_quantity = 1.00");
    $log[] = "✅ Added reservations.reserved_quantity and seeded values";
} else {
    $log[] = "⏭ reservations.reserved_quantity already exists";
}

// Verify
echo "<pre>\n";
echo "=== Migration Results ===\n\n";
foreach ($log as $l) echo $l . "\n";
echo "\n=== food_listings columns ===\n";
foreach ($pdo->query('SHOW COLUMNS FROM food_listings')->fetchAll() as $r) {
    echo "  {$r['Field']} — {$r['Type']}\n";
}
echo "\n=== reservations columns ===\n";
foreach ($pdo->query('SHOW COLUMNS FROM reservations')->fetchAll() as $r) {
    echo "  {$r['Field']} — {$r['Type']}\n";
}
echo "\nDone! Delete this file.\n</pre>";
