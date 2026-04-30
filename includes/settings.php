<?php
/**
 * ResQFood — Global Settings Manager
 * Fetches dynamic website settings from the database.
 */

require_once __DIR__ . '/../config/db.php';

if (!function_exists('getSettings')) {
    function getSettings($pdo = null) {
        static $settings = null;
        if ($settings !== null) {
            return $settings;
        }
        
        if (!$pdo) {
            if (!function_exists('db')) {
                return []; // fallback if DB isn't loaded yet
            }
            $pdo = db();
        }
        
        // Auto-create table if it doesn't exist
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS site_settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            
            // Seed defaults
            if (empty($settings)) {
                $defaults = [
                    'site_name' => 'ResQFood',
                    'contact_email' => 'hello@resqfood.org',
                    'contact_phone' => '+1 (555) 123-4567',
                    'business_address' => '123 Rescue St, Food City, FC 12345',
                    'footer_text' => 'Connecting excess food with those who need it most.',
                    'copyright_text' => '© ' . date('Y') . ' ResQFood. All rights reserved.',
                    'facebook_url' => '',
                    'instagram_url' => '',
                    'twitter_url' => '',
                    'logo_path' => ''
                ];
                foreach ($defaults as $k => $v) {
                    $insertStmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                    $insertStmt->execute([$k, $v]);
                    $settings[$k] = $v;
                }
            }
        } catch (Throwable $e) {
            $settings = [];
        }
        
        return $settings;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, string $default = '') {
        $settings = getSettings();
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('updateSetting')) {
    function updateSetting($pdo, string $key, string $value) {
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
        
        // Force refresh next time
        $settings = getSettings($pdo);
        $settings[$key] = $value;
    }
}
