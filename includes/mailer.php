<?php
/**
 * SMTP mailer (Brevo-compatible) with graceful failure handling.
 */

require_once __DIR__ . '/app_url.php';

if (!function_exists('mail_config')) {
    function mail_config(): array
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }
        $cfg = require __DIR__ . '/../config/mail.php';
        return is_array($cfg) ? $cfg : [];
    }
}

if (!function_exists('mail_is_configured')) {
    function mail_is_configured(array $cfg): bool
    {
        return !empty($cfg['enabled'])
            && !empty($cfg['host'])
            && !empty($cfg['port'])
            && !empty($cfg['username'])
            && !empty($cfg['password'])
            && !empty($cfg['from_email']);
    }
}

if (!function_exists('is_localhost_app_url')) {
    function is_localhost_app_url(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return true;
        }
        return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    }
}

if (!function_exists('smtp_expect')) {
    function smtp_expect($socket, array $validCodes): array
    {
        $line = '';
        while (!feof($socket)) {
            $chunk = fgets($socket, 515);
            if ($chunk === false) {
                break;
            }
            $line .= $chunk;
            if (isset($chunk[3]) && $chunk[3] === ' ') {
                break;
            }
        }
        $code = (int) substr(trim($line), 0, 3);
        return ['ok' => in_array($code, $validCodes, true), 'code' => $code, 'line' => trim($line)];
    }
}

if (!function_exists('smtp_write')) {
    function smtp_write($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }
}

if (!function_exists('smtp_auth_plain')) {
    function smtp_auth_plain($socket, string $username, string $password): array
    {
        $token = base64_encode("\0" . $username . "\0" . $password);
        smtp_write($socket, 'AUTH PLAIN ' . $token);
        $resp = smtp_expect($socket, [235]);
        if ($resp['ok']) {
            return ['ok' => true, 'error' => null];
        }
        return ['ok' => false, 'error' => 'AUTH PLAIN rejected: ' . $resp['line']];
    }
}

if (!function_exists('smtp_auth_login')) {
    function smtp_auth_login($socket, string $username, string $password): array
    {
        smtp_write($socket, 'AUTH LOGIN');
        $loginStart = smtp_expect($socket, [334]);
        if (!$loginStart['ok']) {
            return ['ok' => false, 'error' => 'AUTH LOGIN failed: ' . $loginStart['line']];
        }
        smtp_write($socket, base64_encode($username));
        $userResp = smtp_expect($socket, [334]);
        if (!$userResp['ok']) {
            return ['ok' => false, 'error' => 'SMTP username rejected: ' . $userResp['line']];
        }
        smtp_write($socket, base64_encode($password));
        $passResp = smtp_expect($socket, [235]);
        if (!$passResp['ok']) {
            return ['ok' => false, 'error' => 'SMTP password rejected: ' . $passResp['line']];
        }
        return ['ok' => true, 'error' => null];
    }
}

if (!function_exists('smtp_send_mail')) {
    function smtp_send_mail(array $cfg, string $toEmail, string $subject, string $html, string $text, array $embeddedImages = []): array
    {
        $host = (string) $cfg['host'];
        $port = (int) $cfg['port'];
        $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 15);
        if (!$socket) {
            return ['success' => false, 'error' => 'SMTP connect failed: ' . $errstr];
        }
        stream_set_timeout($socket, 20);

        $greet = smtp_expect($socket, [220]);
        if (!$greet['ok']) return ['success' => false, 'error' => 'SMTP greeting failed'];

        $server = (string) ($_SERVER['SERVER_NAME'] ?? 'localhost');
        smtp_write($socket, 'EHLO ' . $server);
        $ehlo = smtp_expect($socket, [250]);
        if (!$ehlo['ok']) {
            smtp_write($socket, 'HELO ' . $server);
            $helo = smtp_expect($socket, [250]);
            if (!$helo['ok']) {
                return ['success' => false, 'error' => 'EHLO/HELO failed: ' . $ehlo['line']];
            }
        }

        if (($cfg['encryption'] ?? 'tls') === 'tls') {
            smtp_write($socket, 'STARTTLS');
            $tls = smtp_expect($socket, [220]);
            if (!$tls['ok']) return ['success' => false, 'error' => 'STARTTLS failed'];
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true) return ['success' => false, 'error' => 'TLS negotiation failed'];
            smtp_write($socket, 'EHLO ' . $server);
            $ehlo2 = smtp_expect($socket, [250]);
            if (!$ehlo2['ok']) return ['success' => false, 'error' => 'EHLO after TLS failed'];
        }

        $username = (string) $cfg['username'];
        $password = (string) $cfg['password'];
        $auth = smtp_auth_plain($socket, $username, $password);
        if (!$auth['ok']) {
            $auth = smtp_auth_login($socket, $username, $password);
            if (!$auth['ok']) {
                return ['success' => false, 'error' => $auth['error']];
            }
        }

        $fromEmail = (string) $cfg['from_email'];
        $fromName  = (string) ($cfg['from_name'] ?? 'ResQFoodddd');
        smtp_write($socket, 'MAIL FROM:<' . $fromEmail . '>');
        if (!smtp_expect($socket, [250])['ok']) return ['success' => false, 'error' => 'MAIL FROM rejected'];
        smtp_write($socket, 'RCPT TO:<' . $toEmail . '>');
        if (!smtp_expect($socket, [250, 251])['ok']) return ['success' => false, 'error' => 'RCPT TO rejected'];
        smtp_write($socket, 'DATA');
        if (!smtp_expect($socket, [354])['ok']) return ['success' => false, 'error' => 'DATA command rejected'];

        $relatedBoundary = 'resqfood_related_' . bin2hex(random_bytes(8));
        $altBoundary     = 'resqfood_alt_' . bin2hex(random_bytes(8));
        $messageIdHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost.localdomain');
        $messageIdHost = preg_replace('/:\d+$/', '', $messageIdHost);
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . $messageIdHost . '>';

        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'To: <' . $toEmail . '>';
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'Message-ID: ' . $messageId;
        $headers[] = 'Reply-To: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'X-Mailer: ResQFoodddd Transactional Mailer';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/related; boundary="' . $relatedBoundary . '"';

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= '--' . $relatedBoundary . "\r\n";
        $body .= 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . "\r\n\r\n";
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $text . "\r\n\r\n";
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= '--' . $altBoundary . "--\r\n";

        foreach ($embeddedImages as $img) {
            $path = (string) ($img['path'] ?? '');
            $cid  = (string) ($img['cid'] ?? '');
            if ($path === '' || $cid === '' || !is_file($path)) {
                continue;
            }
            $mime = (string) ($img['mime'] ?? 'image/png');
            $name = (string) ($img['name'] ?? basename($path));
            $raw = @file_get_contents($path);
            if ($raw === false) {
                continue;
            }
            $encoded = chunk_split(base64_encode($raw));
            $body .= '--' . $relatedBoundary . "\r\n";
            $body .= 'Content-Type: ' . $mime . '; name="' . $name . '"' . "\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= 'Content-ID: <' . $cid . '>' . "\r\n";
            $body .= 'Content-Disposition: inline; filename="' . $name . '"' . "\r\n\r\n";
            $body .= $encoded . "\r\n";
        }
        $body .= '--' . $relatedBoundary . "--\r\n.";

        smtp_write($socket, $body);
        if (!smtp_expect($socket, [250])['ok']) return ['success' => false, 'error' => 'SMTP DATA send failed'];
        smtp_write($socket, 'QUIT');
        fclose($socket);
        return ['success' => true, 'error' => null];
    }
}

if (!function_exists('send_platform_email')) {
    function send_platform_email(string $toEmail, string $subject, string $html, string $text = '', array $embeddedImages = []): array
    {
        $cfg = mail_config();
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid recipient email'];
        }
        if (!mail_is_configured($cfg)) {
            return ['success' => false, 'error' => 'Mail configuration incomplete'];
        }
        if ($text === '') {
            $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html));
        }

        try {
            return smtp_send_mail($cfg, $toEmail, $subject, $html, $text, $embeddedImages);
        } catch (Throwable $e) {
            error_log('[ResQFoodddd Mailer] ' . $e->getMessage());
            return ['success' => false, 'error' => 'Email transport failure'];
        }
    }
}
