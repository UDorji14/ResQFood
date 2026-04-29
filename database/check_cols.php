<?php
require_once __DIR__ . '/../config/db.php';
$pdo = db();
echo "food_listings columns:\n";
foreach ($pdo->query('SHOW COLUMNS FROM food_listings')->fetchAll() as $r) {
    echo '  ' . $r['Field'] . ' — ' . $r['Type'] . "\n";
}
echo "\nreservations columns:\n";
foreach ($pdo->query('SHOW COLUMNS FROM reservations')->fetchAll() as $r) {
    echo '  ' . $r['Field'] . ' — ' . $r['Type'] . "\n";
}

// Try a test insert
echo "\nTest INSERT:\n";
try {
    $pdo->exec("INSERT INTO food_listings 
        (business_user_id, title, category, quantity, available_quantity, unit, pickup_start, pickup_end, status)
        VALUES (1, 'Test', null, 5.00, 5.00, 'kg', '2026-05-01 10:00:00', '2026-05-01 18:00:00', 'available')");
    $id = $pdo->lastInsertId();
    echo "  Insert OK, id=$id\n";
    $pdo->exec("DELETE FROM food_listings WHERE id=$id");
    echo "  Cleanup OK\n";
} catch (Throwable $e) {
    echo "  FAIL: " . $e->getMessage() . "\n";
}
