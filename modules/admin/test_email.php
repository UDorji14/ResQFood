<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/email_templates.php';
require_once __DIR__ . '/../../includes/notification_service.php';
require_once __DIR__ . '/../../includes/app_url.php';

requireRole(['admin']);
$cfg = mail_config();
$target = currentUserEmail();
if (!empty($cfg['admin_email']) && filter_var($cfg['admin_email'], FILTER_VALIDATE_EMAIL)) {
    $target = $cfg['admin_email'];
}

$selectedTemplate = sanitize($_GET['template'] ?? 'welcome_user');
$previewHtml = '';
$templateOptions = [
    'welcome_user' => 'Welcome User',
    'new_listing' => 'New Listing',
    'reservation_business' => 'Reservation (Business)',
    'reservation_user' => 'Reservation (User)',
    'delivery_completed' => 'Delivery Completed',
    'report_admin' => 'Report Admin',
];

if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    $sample = [
        'name' => 'Alex',
        'title' => 'Fresh bakery surplus',
        'quantity' => '12 portions',
        'pickup_address' => '12 Main Street, Downtown',
        'expiry_time' => date('d M Y, H:i', strtotime('+4 hours')),
        'business_name' => 'Green Bakery',
        'reserved_quantity' => '3 portions',
        'reserved_by' => 'Aivrasol User',
        'status' => 'Reserved',
        'reason' => 'Pickup issue',
        'submitted_by' => 'Aivrasol User (user@example.com)',
        'listing_title' => 'Fresh bakery surplus',
        'email' => 'user@example.com',
        'role' => 'General User',
        'listing_url' => app_url('modules/listings/view.php?id=1'),
        'url' => app_url('dashboard.php'),
    ];

    $tpl = match ($selectedTemplate) {
        'new_listing' => email_template_new_listing($sample),
        'reservation_business' => email_template_reservation_created_for_business($sample),
        'reservation_user' => email_template_reservation_confirmation_for_user($sample),
        'delivery_completed' => email_template_delivery_completed($sample),
        'report_admin' => email_template_report_submitted_admin($sample),
        default => email_template_welcome_user($sample),
    };
    $previewHtml = (string) ($tpl['html'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $target = sanitize($_POST['target_email'] ?? $target);
    $tpl = email_template_welcome_user(['name' => currentUserName()]);
    $res = send_notification_email(currentUserId(), $target, 'admin_test_email', $tpl['subject'], $tpl['html'], $tpl['text'], 'admin_test', currentUserId(), (array) ($tpl['embedded_images'] ?? []));
    setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Test email sent successfully.' : ('Test email failed: ' . ($res['error'] ?? 'unknown')));
    redirect(baseUrl('modules/admin/test_email.php'));
}

$pageTitle = 'Test Email';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart('test_email', 'Test Email', 'Send a test email using current SMTP settings.');
?>
<div class="card" style="max-width:700px;">
  <div class="card-header"><h3>SMTP Test</h3></div>
  <div class="card-body">
    <p class="text-muted">This sends a safe test email using configured Brevo SMTP credentials.</p>
    <div class="notice notice--info" style="margin-bottom:1rem;">
      <div class="notice__body">
        Inbox placement also depends on Brevo/domain setup (SPF, DKIM, DMARC), not code alone.
      </div>
    </div>
    <?php if (str_contains(strtolower(app_base_url()), 'localhost') || str_contains(app_base_url(), '127.0.0.1')): ?>
    <div class="notice notice--warning" style="margin-bottom:1rem;">
      <div class="notice__body">
        Localhost detected: external image URLs to localhost do not render in inboxes. Embedded logo or text fallback is used.
      </div>
    </div>
    <?php endif; ?>
    <div class="card" style="margin-bottom:1rem;background:var(--bg-base);">
      <div class="card-body" style="padding:.85rem 1rem;">
        <p style="margin:0 0 .45rem 0;font-weight:700;">Mail Diagnostics</p>
        <p style="margin:0;font-size:.85rem;color:var(--text-muted);">Enabled: <strong><?= !empty($cfg['enabled']) ? 'Yes' : 'No' ?></strong> · From: <strong><?= e(($cfg['from_name'] ?? 'ResQFoodddd') . ' <' . ($cfg['from_email'] ?? '-') . '>') ?></strong> · Admin: <strong><?= e($cfg['admin_email'] ?? '-') ?></strong> · APP URL: <strong><?= e(($cfg['app_url'] ?? '') !== '' ? (string) $cfg['app_url'] : 'auto-detect') ?></strong></p>
      </div>
    </div>
    <form method="POST" action="">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="target_email">Recipient Email</label>
        <input id="target_email" name="target_email" type="email" class="form-control" value="<?= e($target) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Send Test Email</button>
    </form>
  </div>
</div>
<div class="card" style="max-width:900px;margin-top:1rem;">
  <div class="card-header"><h3>Email Preview</h3></div>
  <div class="card-body">
    <form method="GET" action="" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin:0;min-width:240px;">
        <label class="form-label" for="template">Template</label>
        <select id="template" name="template" class="form-control">
          <?php foreach ($templateOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $selectedTemplate === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="hidden" name="preview" value="1">
      <button type="submit" class="btn btn-outline">Preview Template</button>
    </form>
    <?php if ($previewHtml !== ''): ?>
      <div style="margin-top:1rem;border:1px solid var(--line);border-radius:10px;overflow:auto;max-height:560px;background:#ecf1ea;">
        <?= $previewHtml ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php renderAdminShellEnd(); require_once __DIR__ . '/../../partials/footer.php'; ?>
