<?php
$host = 'localhost'; $db = 'resqfood_db'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute(['admin@resqfood.local']);
$row = $exists->fetch();

if ($row) {
    $pdo->prepare('UPDATE users SET password_hash = ?, status = "active" WHERE email = ?')
        ->execute([$hash, 'admin@resqfood.local']);
    echo '✅ Admin password updated. Login: admin@resqfood.local / Admin@1234';
} else {
    $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?,?,?,?,?)')
        ->execute(['Administrator', 'admin@resqfood.local', $hash, 'admin', 'active']);
    echo '✅ Admin user created. Login: admin@resqfood.local / Admin@1234';
}
