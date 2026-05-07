<?php
/**
 * App URL helpers for localhost and live deployments.
 */

if (!function_exists('app_mail_config_url')) {
    function app_mail_config_url(): string
    {
        static $cfgUrl = null;
        if ($cfgUrl !== null) {
            return $cfgUrl;
        }
        $mailCfg = __DIR__ . '/../config/mail.php';
        if (is_file($mailCfg)) {
            $config = require $mailCfg;
            if (is_array($config) && !empty($config['app_url'])) {
                $cfgUrl = rtrim((string) $config['app_url'], '/');
                return $cfgUrl;
            }
        }
        $cfgUrl = '';
        return $cfgUrl;
    }
}

if (!function_exists('app_detect_base_path')) {
    function app_detect_base_path(): string
    {
        $projectRoot = realpath(__DIR__ . '/..');
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;

        if ($projectRoot && $documentRoot) {
            $projectRootNorm = str_replace('\\', '/', $projectRoot);
            $documentRootNorm = rtrim(str_replace('\\', '/', $documentRoot), '/');
            if ($documentRootNorm !== '' && str_starts_with($projectRootNorm, $documentRootNorm)) {
                $relative = trim(substr($projectRootNorm, strlen($documentRootNorm)), '/');
                return $relative === '' ? '' : '/' . $relative;
            }
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $parts = explode('/', trim($scriptName, '/'));
        return isset($parts[0]) && $parts[0] !== '' ? '/' . $parts[0] : '';
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        static $baseUrl = null;
        if ($baseUrl !== null) {
            return $baseUrl;
        }

        $configured = app_mail_config_url();
        if ($configured !== '') {
            $baseUrl = $configured;
            return $baseUrl;
        }

        $https = (string) ($_SERVER['HTTPS'] ?? '');
        $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $baseUrl = rtrim($scheme . '://' . $host . app_detect_base_path(), '/');
        return $baseUrl;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $base = app_base_url();
        if ($path === '') {
            return $base;
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }
        $clean = ltrim($path, '/');
        return $base . '/' . $clean;
    }
}
