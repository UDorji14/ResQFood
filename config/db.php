<?php
/**
 * ResQFood — Database Configuration
 * ──────────────────────────────────
 * Returns a singleton PDO connection.
 * Usage anywhere: $pdo = db();
 *
 * Defaults are XAMPP-compatible (localhost / root / no password).
 * Change DB_PASS for production.
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'resqfood_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the real error — never expose it to the browser
            error_log('[ResQFood DB] Connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('Service temporarily unavailable. Please try again later.');
        }
    }

    return $pdo;
}
