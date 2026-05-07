<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['admin']);
$pdo = db();
$status = sanitize($_GET['status'] ?? 'all');
$allowed = ['all', 'pending', 'sent', 'failed'];
if (!in_array($status, $allowed, true)) $status = 'all';

$sql = 'SELECT * FROM email_logs';
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$rows = [];
try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
} catch (Throwable $e) {
    setFlash('error', 'email_logs table not found. Run migration: database/migrations/add_email_notification_system.sql');
}

$pageTitle = 'Email Logs';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart('email_logs', 'Email Logs', 'Recent outgoing email delivery attempts.');
?>
<div class="notice notice--info" style="margin-bottom:1rem;">
  <div class="notice__body">
    If inbox placement is poor, check Brevo/domain setup (SPF, DKIM, DMARC) in addition to app code.
  </div>
</div>
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">
    <h3>Delivery History</h3>
    <form method="GET" action="">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <?php foreach ($allowed as $opt): ?>
          <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Date</th><th>Recipient</th><th>Subject</th><th>Template</th><th>Status</th><th>Related</th><th>Error</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td style="white-space:nowrap"><?= e(formatDate($r['created_at'], 'd M Y H:i')) ?></td>
          <td><?= e($r['recipient_email']) ?></td>
          <td><?= e(truncate($r['subject'], 48)) ?></td>
          <td><?= e($r['template_name']) ?></td>
          <td><span class="status-badge status-badge--<?= statusClass($r['status']) ?>"><?= e(statusLabel($r['status'])) ?></span></td>
          <td><?= e(($r['related_type'] ?: '-') . ($r['related_id'] ? (' #' . $r['related_id']) : '')) ?></td>
          <td><?= e(truncate((string) ($r['error_message'] ?: '-'), 60)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7" style="text-align:center;padding:1.2rem;color:var(--text-muted)">No email logs found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php renderAdminShellEnd(); require_once __DIR__ . '/../../partials/footer.php'; ?>
