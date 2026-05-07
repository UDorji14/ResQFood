<?php
require_once __DIR__ . '/app_url.php';
require_once __DIR__ . '/mailer.php';

function email_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function email_detect_logo_asset(): ?array
{
    if (function_exists('setting')) {
        $logoPath = (string) setting('logo_path', '');
        if ($logoPath !== '' && is_file(__DIR__ . '/../' . ltrim($logoPath, '/'))) {
            $full = realpath(__DIR__ . '/../' . ltrim($logoPath, '/'));
            if ($full) {
                return [
                    'path' => $full,
                    'public' => app_url($logoPath),
                    'name' => basename($full),
                ];
            }
        }
    }
    $candidates = [
        'uploads/branding/logo_1777527357.png',
        'uploads/branding/logo_1777527133.jpg',
        'uploads/branding/logo.png',
        'uploads/branding/logo.jpg',
        'uploads/branding/logo.jpeg',
        'assets/images/logo.png',
        'assets/images/logo.svg',
        'assets/images/logo.jpg',
        'assets/images/logo.webp',
    ];
    foreach ($candidates as $path) {
        $full = realpath(__DIR__ . '/../' . $path);
        if ($full && is_file($full)) {
            return [
                'path' => $full,
                'public' => app_url($path),
                'name' => basename($full),
            ];
        }
    }
    return null;
}

function mime_from_logo_path(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        default => 'image/png',
    };
}

function get_email_logo_reference(): array
{
    $logo = email_detect_logo_asset();
    if (!$logo) {
        return ['type' => 'text', 'html' => '<div style="font-size:22px;font-weight:700;line-height:1;color:#ffffff;letter-spacing:0.3px;">ResQFoodddd</div>', 'embedded_images' => []];
    }

    $cid = 'resqfood_logo';
    $embedded = [[
        'path' => $logo['path'],
        'cid'  => $cid,
        'name' => $logo['name'],
        'mime' => mime_from_logo_path($logo['path']),
    ]];
    $html = '<img src="cid:' . $cid . '" alt="ResQFoodddd logo" width="150" style="display:block;width:150px;max-width:150px;height:auto;border:0;outline:none;text-decoration:none;">';

    $base = app_base_url();
    if (!is_localhost_app_url($base)) {
        $html = '<img src="cid:' . $cid . '" alt="ResQFoodddd logo" width="150" style="display:block;width:150px;max-width:150px;height:auto;border:0;outline:none;text-decoration:none;">';
    }

    return ['type' => 'cid', 'html' => $html, 'embedded_images' => $embedded];
}

function render_email_preheader(string $text): string
{
    $safe = email_escape($text);
    return '<div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;">' . $safe . str_repeat('&nbsp;&zwnj;', 32) . '</div>';
}

function render_email_header(string $brandHtml): string
{
    return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
      <tr>
        <td style="padding:22px 24px;background:#2f4630;background:linear-gradient(135deg,#2f4630 0%,#49664a 60%,#5a7a50 100%);">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
            <tr>
              <td align="left" valign="middle">' . $brandHtml . '</td>
              <td align="right" valign="middle" style="font-size:11px;line-height:1.4;color:#dce9d8;letter-spacing:0.4px;">Food Rescue Notification</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';
}

function render_email_intro(string $title, string $intro): string
{
    return '<h1 style="margin:0 0 10px 0;color:#1f2d1f;font-family:Arial,sans-serif;font-size:24px;line-height:1.3;font-weight:700;">' . email_escape($title) . '</h1>
    <p style="margin:0 0 16px 0;color:#4e5d4f;font-size:15px;line-height:1.6;">' . email_escape($intro) . '</p>';
}

function render_email_status_badge(string $status): string
{
    $low = strtolower(trim($status));
    $bg = '#e7f3e3';
    $fg = '#2f5c2f';
    if (in_array($low, ['reserved', 'under review', 'pending'], true)) {
        $bg = '#fff3d9';
        $fg = '#7f5a10';
    } elseif (in_array($low, ['failed', 'cancelled', 'dismissed'], true)) {
        $bg = '#fde4e4';
        $fg = '#8a2f2f';
    }
    return '<span style="display:inline-block;background:' . $bg . ';color:' . $fg . ';padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;">' . email_escape($status) . '</span>';
}

function render_email_button(string $text, string $url): string
{
    return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
      <tr>
        <td align="center" bgcolor="#4a6741" style="border-radius:10px;">
          <a href="' . email_escape($url) . '" style="display:inline-block;padding:12px 20px;font-size:14px;line-height:1.2;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;background:#4a6741;">' . email_escape($text) . '</a>
        </td>
      </tr>
    </table>';
}

function render_email_detail_rows(array $rows): string
{
    $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;background:#f7faf7;border:1px solid #dfe8df;border-radius:12px;">';
    foreach ($rows as $label => $value) {
        if ($value === null || $value === '') continue;
        $displayValue = is_array($value) ? '' : (string) $value;
        $html .= '<tr><td style="padding:11px 14px;border-bottom:1px solid #dfe8df;width:34%;font-weight:700;color:#4b5b49;font-size:13px;line-height:1.4;">' . email_escape((string) $label) . '</td><td style="padding:11px 14px;border-bottom:1px solid #dfe8df;color:#203120;font-size:13px;line-height:1.45;">';
        if (is_array($value) && isset($value['badge'])) {
            $html .= render_email_status_badge((string) $value['badge']);
        } else {
            $html .= email_escape($displayValue);
        }
        $html .= '</td></tr>';
    }
    return $html . '</table>';
}

function render_email_footer(string $footerText): string
{
    return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
      <tr><td style="padding:16px 24px;background:#f2f5f1;border-top:1px solid #dde6de;">
        <p style="margin:0 0 6px 0;color:#637263;font-size:12px;line-height:1.5;">' . email_escape($footerText) . '</p>
        <p style="margin:0;color:#849284;font-size:12px;line-height:1.4;">ResQFoodddd &copy; ' . date('Y') . '</p>
      </td></tr>
    </table>';
}

function render_plain_text_from_data(string $title, string $intro, array $rows = [], ?string $buttonText = null, ?string $buttonUrl = null, ?string $footerText = null): string
{
    $lines = [];
    $lines[] = 'ResQFoodddd';
    $lines[] = str_repeat('=', 22);
    $lines[] = '';
    $lines[] = $title;
    $lines[] = '';
    $lines[] = $intro;
    if (!empty($rows)) {
        $lines[] = '';
        foreach ($rows as $k => $v) {
            if ($v === null || $v === '') continue;
            $val = is_array($v) ? ((string) ($v['badge'] ?? '')) : (string) $v;
            $lines[] = $k . ': ' . $val;
        }
    }
    if ($buttonText && $buttonUrl) {
        $lines[] = '';
        $lines[] = $buttonText . ': ' . $buttonUrl;
    }
    $lines[] = '';
    $lines[] = $footerText ?: 'You are receiving this email because you have an account on ResQFoodddd.';
    return implode(PHP_EOL, $lines);
}

function render_email_layout(string $title, string $preheaderText, string $introText, string $bodyHtml, ?string $buttonText = null, ?string $buttonUrl = null, ?string $footerText = null): array
{
    $logoRef = get_email_logo_reference();
    $footer = $footerText ?: 'You are receiving this email because you have an account on ResQFoodddd.';
    $button = ($buttonText && $buttonUrl) ? render_email_button($buttonText, $buttonUrl) : '';

    $html = '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:0;background:#ecf1ea;font-family:Arial,Helvetica,sans-serif;">
    ' . render_email_preheader($preheaderText) . '
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;background:#ecf1ea;padding:18px 0;">
      <tr><td align="center" style="padding:0 12px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;border-collapse:separate;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dbe4db;">
          <tr><td>' . render_email_header($logoRef['html']) . '</td></tr>
          <tr><td style="padding:24px;">
            ' . render_email_intro($title, $introText) . '
            ' . $bodyHtml . '
            ' . ($button !== '' ? ('<div style="padding-top:18px;">' . $button . '</div>') : '') . '
          </td></tr>
          <tr><td>' . render_email_footer($footer) . '</td></tr>
        </table>
      </td></tr>
    </table></body></html>';

    return [
        'html' => $html,
        'embedded_images' => (array) ($logoRef['embedded_images'] ?? []),
    ];
}

function email_template_new_listing(array $d): array
{
    $rowsData = [
        'Food' => $d['title'] ?? '',
        'Quantity' => $d['quantity'] ?? '',
        'Pickup' => $d['pickup_address'] ?? '',
        'Expiry' => $d['expiry_time'] ?? '',
        'Donor' => $d['business_name'] ?? '',
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'A new surplus food listing is available';
    $title = 'New Listing Available';
    $intro = 'A new surplus food listing has just been posted on ResQFoodddd.';
    $url = (string) ($d['listing_url'] ?? app_url('modules/listings/browse.php'));
    $layout = render_email_layout($title, 'View the latest food listing on ResQFoodddd.', $intro, $rows, 'View Listing', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'View Listing', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_reservation_created_for_business(array $d): array
{
    $rowsData = [
        'Food' => $d['title'] ?? '',
        'Reserved Quantity' => $d['reserved_quantity'] ?? '',
        'Reserved By' => $d['reserved_by'] ?? '',
        'Status' => ['badge' => $d['status'] ?? 'Reserved'],
        'Pickup' => $d['pickup_address'] ?? '',
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'Your listing received a new reservation';
    $title = 'New Reservation Received';
    $intro = 'A user or charity has reserved food from your listing.';
    $url = (string) ($d['url'] ?? app_url('modules/reservations/index.php'));
    $layout = render_email_layout($title, 'A reservation update is ready for your listing.', $intro, $rows, 'View Reservation', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'View Reservation', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_reservation_confirmation_for_user(array $d): array
{
    $rowsData = [
        'Food' => $d['title'] ?? '',
        'Reserved Quantity' => $d['reserved_quantity'] ?? '',
        'Business' => $d['business_name'] ?? '',
        'Pickup Address' => $d['pickup_address'] ?? '',
        'Status' => ['badge' => $d['status'] ?? 'Reserved'],
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'Your reservation has been received';
    $title = 'Reservation Confirmed';
    $intro = 'Your reservation is now recorded. Please keep your pickup details handy.';
    $url = (string) ($d['url'] ?? app_url('modules/reservations/my.php'));
    $layout = render_email_layout($title, 'Your reservation details are ready.', $intro, $rows, 'View Reservation', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'View Reservation', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_delivery_completed(array $d): array
{
    $rowsData = [
        'Food' => $d['title'] ?? '',
        'Quantity' => $d['reserved_quantity'] ?? '',
        'Status' => ['badge' => 'Completed'],
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'Food pickup completed successfully';
    $title = 'Pickup Completed';
    $intro = 'Food pickup has been marked as completed. Thank you for helping reduce food waste and support the community.';
    $url = (string) ($d['url'] ?? app_url('dashboard.php'));
    $layout = render_email_layout($title, 'Food pickup status has been updated.', $intro, $rows, 'Open Dashboard', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'Open Dashboard', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_report_submitted_admin(array $d): array
{
    $rowsData = [
        'Report Type' => $d['reason'] ?? '',
        'Submitted By' => $d['submitted_by'] ?? '',
        'Related Listing' => $d['listing_title'] ?? '',
        'Related User' => $d['reported_user_email'] ?? '',
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'New report submitted for admin review';
    $title = 'New Report Submitted';
    $intro = 'A user submitted a report that may require moderation.';
    $url = (string) ($d['url'] ?? app_url('modules/admin/reports.php'));
    $layout = render_email_layout($title, 'A moderation report is waiting in the admin panel.', $intro, $rows, 'Open Admin Panel', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'Open Admin Panel', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_welcome_user(array $d): array
{
    $title = 'Welcome to ResQFoodddd';
    $subject = 'Welcome to ResQFoodddd';
    $name = (string) ($d['name'] ?? 'there');
    $intro = 'Hi ' . $name . ', your account has been created successfully.';
    $body = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;"><tr><td style="padding:12px 14px;background:#f7faf7;border:1px solid #dfe8df;border-radius:12px;color:#324332;font-size:14px;line-height:1.6;">You can now browse listings, reserve food, and track your impact from your dashboard.</td></tr></table>';
    $url = app_url('dashboard.php');
    $layout = render_email_layout($title, 'Welcome to ResQFoodddd — your account is ready.', $intro, $body, 'Open Dashboard', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, ['Account Status' => 'Active'], 'Open Dashboard', $url), 'embedded_images' => $layout['embedded_images']];
}

function email_template_new_user_admin(array $d): array
{
    $rowsData = [
        'Name' => $d['name'] ?? '',
        'Email' => $d['email'] ?? '',
        'Role' => $d['role'] ?? '',
    ];
    $rows = render_email_detail_rows($rowsData);
    $subject = 'New user registration notification';
    $title = 'New User Registration';
    $intro = 'A new account was registered on ResQFoodddd.';
    $url = app_url('modules/admin/users.php');
    $layout = render_email_layout($title, 'A new user has joined your platform.', $intro, $rows, 'Open Users', $url);
    return ['subject' => $subject, 'html' => $layout['html'], 'text' => render_plain_text_from_data($title, $intro, $rowsData, 'Open Users', $url), 'embedded_images' => $layout['embedded_images']];
}
