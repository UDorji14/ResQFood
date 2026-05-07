<?php
/**
 * ResQFoodddd Mail Configuration
 * Local overrides: config/mail.local.php
 */

$config = [
    'provider'   => getenv('MAIL_PROVIDER') ?: 'brevo',
    'host'       => getenv('MAIL_HOST') ?: 'smtp-relay.brevo.com',
    'port'       => (int) (getenv('MAIL_PORT') ?: 587),
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: '',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'ResQFoodddd',
    'admin_email'=> getenv('ADMIN_EMAIL') ?: '',
    'app_url'    => getenv('APP_URL') ?: '',
    'enabled'    => true,
];

$localPath = __DIR__ . '/mail.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

return $config;
