<?php
/**
 * ResQFood — Global Website Settings (Admin)
 * Redesigned with proper spacing, grouping, and Team Members integration.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['admin']);
$pdo = db();
$mailConfig = require __DIR__ . '/../../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fields = [
        'site_name', 'contact_email', 'contact_phone', 'business_address',
        'footer_text', 'copyright_text', 'facebook_url', 'instagram_url', 'twitter_url'
    ];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            updateSetting($pdo, $field, sanitize($_POST[$field]));
        }
    }

    // Logo upload
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (in_array($ext, $allowed) && $_FILES['logo_upload']['size'] <= 2 * 1024 * 1024) {
            $dir = __DIR__ . '/../../uploads/branding/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fileName = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $dir . $fileName)) {
                updateSetting($pdo, 'logo_path', 'uploads/branding/' . $fileName);
            } else {
                setFlash('error', 'Failed to save logo.');
            }
        } else {
            setFlash('error', 'Invalid logo file. Must be an image under 2MB.');
        }
    }

    setFlash('success', 'Settings saved successfully.');
    redirect('modules/admin/settings.php');
}

// Team member count for the sidebar link
$teamCount = 0;
try {
    $teamCount = (int)$pdo->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
} catch (Throwable $e) {}

$pageTitle = 'Website Settings';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart('settings', 'Website Settings', 'Manage your brand identity, contact details, and global references.');
?>

<style>
/* ── Settings page polish ── */
.settings-layout { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
.settings-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.settings-card__header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1.5px solid var(--border, #e8ede4); }
.settings-card__icon { width: 40px; height: 40px; border-radius: 10px; background: var(--accent-light, #e8f4e0); display: flex; align-items: center; justify-content: center; color: var(--accent, #4a6741); flex-shrink: 0; }
.settings-card__title { font-size: 1rem; font-weight: 700; color: var(--text-dark); margin: 0; }
.settings-card__subtitle { font-size: 0.8rem; color: var(--text-mid); margin: 0; }
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.settings-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
.settings-grid--full { grid-template-columns: 1fr; }
.settings-field { display: flex; flex-direction: column; gap: 0.4rem; }
.settings-field label { font-size: 0.8rem; font-weight: 600; color: var(--text-mid, #5a6a55); }
.settings-field input, .settings-field textarea, .settings-field select { padding: 0.65rem 0.9rem; border: 1.5px solid var(--border, #dde8d5); border-radius: 9px; font-size: 0.9rem; color: var(--text-dark); background: var(--bg-off, #fafcf9); outline: none; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; width: 100%; }
.settings-field input:focus, .settings-field textarea:focus { border-color: var(--accent, #4a6741); box-shadow: 0 0 0 3px rgba(74,103,65,0.1); }
.settings-field textarea { resize: vertical; min-height: 80px; }
.settings-field small { font-size: 0.75rem; color: var(--text-mid); }
.logo-preview-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.logo-preview-box { width: 64px; height: 64px; border-radius: 10px; background: #f0f4ec; border: 1.5px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.logo-preview-box img { width: 100%; height: 100%; object-fit: contain; }
.social-icon-label { display: flex; align-items: center; gap: 0.45rem; }
.team-cta-box { background: linear-gradient(135deg, #e8f4e0, #f0f8ea); border-radius: 14px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.team-cta-box__info h4 { font-size: 0.95rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem; }
.team-cta-box__info p { font-size: 0.85rem; color: var(--text-mid); margin: 0; }
.settings-save-bar { background: #fff; border-radius: 16px; padding: 1.25rem 2rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 1rem; }
.settings-save-bar p { margin: 0; font-size: 0.85rem; color: var(--text-mid); }
@media (max-width: 700px) {
    .settings-grid, .settings-grid--3 { grid-template-columns: 1fr; }
    .settings-card { padding: 1.5rem; }
}
</style>

<form method="POST" action="<?= baseUrl('modules/admin/settings.php') ?>" enctype="multipart/form-data">
<?= csrfField() ?>

<div class="settings-layout">

    <!-- ── Branding ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10" cy="10" r="8"/><path d="M10 6v4l2.5 2.5"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Branding</p>
                <p class="settings-card__subtitle">Site name and logo</p>
            </div>
        </div>
        <div class="settings-grid">
            <div class="settings-field">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name" value="<?= e(setting('site_name')) ?>" placeholder="ResQFood" required>
            </div>
            <div class="settings-field">
                <label>Current Logo</label>
                <div class="logo-preview-row">
                    <div class="logo-preview-box">
                        <?php if (setting('logo_path')): ?>
                            <img src="<?= url(setting('logo_path')) ?>" alt="Logo">
                        <?php else: ?>
                            <svg viewBox="0 0 28 28" width="20" fill="#4a6741" opacity=".4"><path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z"/><path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" opacity=".55"/></svg>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <input type="file" name="logo_upload" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="font-size:0.85rem;padding:0.45rem">
                        <small>PNG/JPG/WebP/SVG — max 2MB. Leave empty to keep current.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Contact Info ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="16" height="12" rx="2"/><path d="m2 7 8 5 8-5"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Contact Information</p>
                <p class="settings-card__subtitle">Displayed on Contact page and footer</p>
            </div>
        </div>
        <div class="settings-grid">
            <div class="settings-field">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= e(setting('contact_email')) ?>" placeholder="hello@resqfood.org">
            </div>
            <div class="settings-field">
                <label for="contact_phone">Contact Phone</label>
                <input type="text" id="contact_phone" name="contact_phone" value="<?= e(setting('contact_phone')) ?>" placeholder="+1 (555) 123-4567">
            </div>
            <div class="settings-field" style="grid-column:1/-1">
                <label for="business_address">Business / Office Address</label>
                <input type="text" id="business_address" name="business_address" value="<?= e(setting('business_address')) ?>" placeholder="123 Rescue St, Food City">
            </div>
        </div>
    </div>

    <!-- ── Footer & Company ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="14" width="16" height="4" rx="1"/><path d="M2 10h16M2 6h10"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Footer & Company</p>
                <p class="settings-card__subtitle">Tagline and copyright shown in the footer</p>
            </div>
        </div>
        <div class="settings-grid">
            <div class="settings-field" style="grid-column:1/-1">
                <label for="footer_text">Footer Tagline / Description</label>
                <textarea id="footer_text" name="footer_text" rows="2"><?= e(setting('footer_text')) ?></textarea>
            </div>
            <div class="settings-field" style="grid-column:1/-1">
                <label for="copyright_text">Copyright Text</label>
                <input type="text" id="copyright_text" name="copyright_text" value="<?= e(setting('copyright_text')) ?>" placeholder="© 2026 ResQFood. All rights reserved.">
            </div>
        </div>
    </div>

    <!-- ── Social Links ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="15" cy="5" r="2"/><circle cx="5" cy="10" r="2"/><circle cx="15" cy="15" r="2"/><path d="m7 9 6-3M7 11l6 3"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Social Links</p>
                <p class="settings-card__subtitle">Icons appear in the footer when URLs are set</p>
            </div>
        </div>
        <div class="settings-grid settings-grid--3">
            <div class="settings-field">
                <label class="social-icon-label">
                    <svg viewBox="0 0 20 20" width="14" fill="#1877f2"><path d="M18 10C18 5.58 14.42 2 10 2S2 5.58 2 10c0 4.01 2.94 7.34 6.78 7.94V12.5H6.78v-2.5h2V8.25C8.78 6.38 9.88 5.42 11.6 5.42c.83 0 1.7.15 1.7.15v1.87h-.96c-.94 0-1.24.59-1.24 1.19V10h2.1l-.33 2.5h-1.77v5.44C15.06 17.34 18 14.01 18 10z"/></svg>
                    Facebook URL
                </label>
                <input type="url" name="facebook_url" value="<?= e(setting('facebook_url')) ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="settings-field">
                <label class="social-icon-label">
                    <svg viewBox="0 0 20 20" width="14" fill="#000"><path d="M15.2 2h2.7L11.5 8.97 19 18h-5.6l-4.3-5.63L4.5 18H1.8l6.7-7.66L1 2h5.75l3.9 5.14L15.2 2zm-.96 14.4h1.5L6.82 3.55H5.22l9.02 12.85z"/></svg>
                    Twitter / X URL
                </label>
                <input type="url" name="twitter_url" value="<?= e(setting('twitter_url')) ?>" placeholder="https://twitter.com/...">
            </div>
            <div class="settings-field">
                <label class="social-icon-label">
                    <svg viewBox="0 0 20 20" width="14" fill="url(#ig)"><defs><linearGradient id="ig" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#f09433"/><stop offset="50%" stop-color="#e6683c"/><stop offset="100%" stop-color="#bc1888"/></linearGradient></defs><path d="M10 2.16c2.67 0 2.99.01 4.04.06 2.71.12 3.97 1.41 4.1 4.1.05 1.05.06 1.37.06 4.04s-.01 2.99-.06 4.04c-.13 2.69-1.39 3.98-4.1 4.1-1.05.05-1.37.06-4.04.06s-2.99-.01-4.04-.06c-2.71-.12-3.97-1.41-4.1-4.1C1.81 12.99 1.8 12.67 1.8 10s.01-2.99.06-4.04c.13-2.69 1.39-3.98 4.1-4.1C6.01 2.17 6.33 2.16 10 2.16m0-1.8C7.28 0 6.94.01 5.88.06 2.25.23.23 2.25.06 5.88.01 6.94 0 7.28 0 10s.01 3.06.06 4.12c.17 3.63 2.19 5.65 5.82 5.82 1.06.05 1.4.06 4.12.06s3.06-.01 4.12-.06c3.63-.17 5.65-2.19 5.82-5.82.05-1.06.06-1.4.06-4.12s-.01-3.06-.06-4.12C19.77 2.25 17.75.23 14.12.06 13.06.01 12.72 0 10 0z"/><path d="M10 4.87A5.13 5.13 0 1015.13 10 5.13 5.13 0 0010 4.87zm0 8.46A3.33 3.33 0 1113.33 10 3.33 3.33 0 0110 13.33z"/><circle cx="15.31" cy="4.69" r="1.2"/></svg>
                    Instagram URL
                </label>
                <input type="url" name="instagram_url" value="<?= e(setting('instagram_url')) ?>" placeholder="https://instagram.com/...">
            </div>
        </div>
    </div>

    <!-- ── Team Members CTA ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="8" cy="6" r="3"/><path d="M2 17c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="16" cy="7" r="2"/><path d="M14 17c0-2.2 1.3-4 3-4.5"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Team Members</p>
                <p class="settings-card__subtitle">Shown on the About Us page</p>
            </div>
        </div>
        <div class="team-cta-box">
            <div class="team-cta-box__info">
                <h4><?= $teamCount ?> team member<?= $teamCount !== 1 ? 's' : '' ?> configured</h4>
                <p>Add, edit, reorder, or remove team members shown on the public About Us page.</p>
            </div>
            <a href="<?= baseUrl('modules/admin/team_members.php') ?>" class="btn btn-primary">Manage Team →</a>
        </div>
    </div>

    <!-- ── Email Settings (Read-only) ── -->
    <div class="settings-card">
        <div class="settings-card__header">
            <div class="settings-card__icon">
                <svg viewBox="0 0 20 20" width="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="16" height="12" rx="2"/><path d="m2 7 8 5 8-5"/></svg>
            </div>
            <div>
                <p class="settings-card__title">Email Settings</p>
                <p class="settings-card__subtitle">Current mail configuration visibility (read-only)</p>
            </div>
        </div>
        <?php
            $mailEnabled = !empty($mailConfig['enabled']);
            $mailFromName = (string) ($mailConfig['from_name'] ?? '');
            $mailFromEmail = (string) ($mailConfig['from_email'] ?? '');
            $mailAdminEmail = (string) ($mailConfig['admin_email'] ?? '');
            $mailProvider = (string) ($mailConfig['provider'] ?? '');
            $mailHost = (string) ($mailConfig['host'] ?? '');
            $mailPort = (string) ($mailConfig['port'] ?? '');
            $mailEncryption = (string) ($mailConfig['encryption'] ?? '');
            $mailAppUrl = trim((string) ($mailConfig['app_url'] ?? ''));
            $mailAppUrlMode = $mailAppUrl !== '' ? 'Configured (fixed APP URL)' : 'Automatic detection (recommended for localhost project folders)';
        ?>
        <div class="settings-grid">
            <div class="settings-field">
                <label>Mail Enabled</label>
                <input type="text" value="<?= $mailEnabled ? 'Yes' : 'No' ?>" disabled>
            </div>
            <div class="settings-field">
                <label>Provider</label>
                <input type="text" value="<?= e($mailProvider !== '' ? $mailProvider : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>From Name</label>
                <input type="text" value="<?= e($mailFromName !== '' ? $mailFromName : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>From Email</label>
                <input type="text" value="<?= e($mailFromEmail !== '' ? $mailFromEmail : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>Admin Alert Email</label>
                <input type="text" value="<?= e($mailAdminEmail !== '' ? $mailAdminEmail : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>Encryption</label>
                <input type="text" value="<?= e($mailEncryption !== '' ? strtoupper($mailEncryption) : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>SMTP Host</label>
                <input type="text" value="<?= e($mailHost !== '' ? $mailHost : '-') ?>" disabled>
            </div>
            <div class="settings-field">
                <label>SMTP Port</label>
                <input type="text" value="<?= e($mailPort !== '' ? $mailPort : '-') ?>" disabled>
            </div>
            <div class="settings-field" style="grid-column:1/-1">
                <label>APP URL Mode</label>
                <input type="text" value="<?= e($mailAppUrlMode) ?>" disabled>
                <small>
                    <?php if ($mailAppUrl !== ''): ?>
                        Current APP URL: <?= e($mailAppUrl) ?>
                    <?php else: ?>
                        `app_url.php` will auto-detect base URL from the current request and project folder.
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>

    <!-- ── Save Bar ── -->
    <div class="settings-save-bar">
        <p>Changes to branding, contact info, footer, and social links are saved here.</p>
        <button type="submit" class="btn btn-primary" style="padding:0.75rem 2.5rem;font-size:0.95rem">Save All Settings</button>
    </div>

</div>
</form>

<?php
renderAdminShellEnd();
require_once __DIR__ . '/../../partials/footer.php';
?>
