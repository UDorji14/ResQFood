<?php
/**
 * ResQFood — Charity Profile Management
 * ────────────────────────────────────────
 * Charity users can view and update their organisation profile.
 * Verification status is controlled by admins and displayed read-only.
 *
 * Forms on this page:
 *   _action=update_profile  → updates users + charity_profiles
 *   _action=change_password → updates password in users table
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/profile.php';

requireRole(['charity']);

$uid      = currentUserId();
$pdo      = db();
$errors   = [];
$pwErrors = [];

// ─────────────────────────────────────────────────────────────────────────
// POST Handler
// ─────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();
    $action = $_POST['_action'] ?? '';

    // ── Update Profile ────────────────────────────────────────────────────
    if ($action === 'update_profile') {

        $data = [
            'full_name'         => sanitize($_POST['full_name']         ?? ''),
            'phone'             => sanitize($_POST['phone']             ?? ''),
            'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? 1 : 0,
            'organization_name' => sanitize($_POST['organization_name'] ?? ''),
            'contact_person'    => sanitize($_POST['contact_person']    ?? ''),
            'address'           => sanitize($_POST['address']           ?? ''),
            'city'              => sanitize($_POST['city']              ?? ''),
            'description'       => sanitize($_POST['description']       ?? ''),
        ];

        // Validate
        validateRequired($data, ['full_name', 'organization_name'], $errors);
        validateMaxLength($data['full_name'],         120, 'full_name',         $errors);
        validateMaxLength($data['organization_name'], 160, 'organization_name', $errors);
        validateMaxLength($data['contact_person'],    120, 'contact_person',    $errors);
        validateMaxLength($data['city'],              80,  'city',              $errors);

        if ($data['phone'] !== '') {
            validatePhone($data['phone'], 'phone', $errors);
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update core user record
                $pdo->prepare('
                    UPDATE users
                    SET    full_name = ?, phone = ?, email_notifications_enabled = ?, updated_at = NOW()
                    WHERE  id = ?
                ')->execute([
                    $data['full_name'],
                    $data['phone'] !== '' ? $data['phone'] : null,
                    $data['email_notifications_enabled'],
                    $uid,
                ]);

                // Update charity profile
                $pdo->prepare('
                    UPDATE charity_profiles
                    SET    organization_name = ?,
                           contact_person    = ?,
                           address           = ?,
                           city              = ?,
                           description       = ?,
                           updated_at        = NOW()
                    WHERE  user_id = ?
                ')->execute([
                    $data['organization_name'],
                    $data['contact_person'] !== '' ? $data['contact_person'] : null,
                    $data['address']        !== '' ? $data['address']        : null,
                    $data['city']           !== '' ? $data['city']           : null,
                    $data['description']    !== '' ? $data['description']    : null,
                    $uid,
                ]);

                // Reflect name change in active session
                $_SESSION['user_name'] = $data['full_name'];

                $pdo->commit();
                auditLog('charity_profile_update', null, $uid);
                setFlash('success', 'Organisation profile updated successfully.');

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[ResQFood CharityProfile] ' . $e->getMessage());
                setFlash('error', 'Could not save profile. Please try again.');
            }

            redirect(baseUrl('modules/profile/charity_profile.php'));
        }

    // ── Change Password ───────────────────────────────────────────────────
    } elseif ($action === 'change_password') {

        $currentPw = $_POST['current_password'] ?? '';
        $newPw     = $_POST['new_password']     ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if ($currentPw === '') $pwErrors['current_password'] = 'Current password is required.';
        if ($newPw     === '') $pwErrors['new_password']     = 'New password is required.';

        if (empty($pwErrors)) {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($currentPw, $row['password_hash'])) {
                $pwErrors['current_password'] = 'Current password is incorrect.';
            } else {
                validatePassword($newPw, 'new_password', $pwErrors);
                if (empty($pwErrors['new_password'])) {
                    validatePasswordMatch($newPw, $confirmPw, $pwErrors);
                }
            }
        }

        if (empty($pwErrors)) {
            $pdo->prepare('
                UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?
            ')->execute([
                password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]),
                $uid,
            ]);
            auditLog('password_change', null, $uid);
            setFlash('success', 'Password changed successfully.');
            redirect(baseUrl('modules/profile/charity_profile.php'));
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Load Data
// ─────────────────────────────────────────────────────────────────────────
$profile    = getCharityProfile($uid);
$completion = charityProfileCompletion($uid);

$old = [
    'full_name'         => $profile['full_name']         ?? '',
    'phone'             => $profile['phone']             ?? '',
    'organization_name' => $profile['organization_name'] ?? '',
    'contact_person'    => $profile['contact_person']    ?? '',
    'address'           => $profile['address']           ?? '',
    'city'              => $profile['city']              ?? '',
    'description'       => $profile['description']       ?? '',
];

if (!empty($errors)) {
    foreach ($old as $k => $_) {
        if (isset($_POST[$k])) $old[$k] = sanitize($_POST[$k]);
    }
}

$pageTitle = 'Organisation Profile';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/charity_shell.php';
renderCharityShellStart('profile', 'Charity Profile', 'Manage your organisation details and security settings.');
?>

<!-- Profile Hero -->
<div class="dash-welcome dash-welcome--charity">
    <div class="dash-welcome__inner" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:1.5rem;">
            <div style="background:rgba(255,255,255,0.2); width:64px; height:64px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:2rem; font-weight:800; border:2px solid rgba(255,255,255,0.4);">
                <?= e(mb_strtoupper(mb_substr($profile['organization_name'] ?? $profile['full_name'] ?? 'C', 0, 1))) ?>
            </div>
            <div>
                <div class="dash-welcome__greeting"><?= e($profile['organization_name'] ?: ($profile['full_name'] ?? 'Your Organisation')) ?></div>
                <p class="dash-welcome__sub">
                    <?= e($profile['email'] ?? '') ?>
                    <?php if (!empty($profile['city'])): ?> &middot; <?= e($profile['city']) ?><?php endif; ?>
                    <?php if (!empty($profile['contact_person'])): ?> &middot; Contact: <?= e($profile['contact_person']) ?><?php endif; ?>
                    &middot; Since <?= formatDate($profile['joined_at'] ?? '', 'M Y') ?>
                </p>
            </div>
        </div>
        <div>
            <?= verificationBadge($profile['verification_status'] ?? 'pending') ?>
        </div>
    </div>
</div>

<?php if ($completion < 100): ?>
<div class="completion-strip">
    <span class="completion-strip__label">Profile completeness</span>
    <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
    <span class="completion-strip__pct"><?= $completion ?>%</span>
</div>
<?php endif; ?>

<div class="profile-layout" <?= !empty($pwErrors) ? 'data-default-tab="password"' : '' ?>>

    <!-- ── Sidebar ── -->
    <aside class="profile-sidebar">

        <div class="card">
            <div class="card-body" style="padding:.75rem">
                <nav class="profile-tabs" id="profile-tabs">
                    <button class="profile-tab active" data-tab="details">
                        <svg viewBox="0 0 18 18" width="15" fill="none"><rect x="2" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6 7h6M6 10h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                        Organisation Details
                    </button>
                    <button class="profile-tab" data-tab="account">
                        <svg viewBox="0 0 18 18" width="15" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 16v-1a6 6 0 0112 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        Account Details
                    </button>
                    <button class="profile-tab" data-tab="password">
                        <svg viewBox="0 0 18 18" width="15" fill="none"><rect x="4" y="8" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6 8V6a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        Change Password
                    </button>
                </nav>
            </div>
        </div>

        <div class="card" style="overflow:hidden">
            <div style="padding:.8rem 1.1rem;background:linear-gradient(135deg,rgba(181,96,74,.05),rgba(196,145,62,.08));border-bottom:1px solid var(--line)">
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-bottom:.55rem">Verification Status</div>
                <?= verificationBadge($profile['verification_status'] ?? 'pending') ?>
            </div>
            <div style="padding:.85rem 1.1rem">
                <p style="font-size:.78rem;color:var(--text-muted);line-height:1.55">
                    <?php if (($profile['verification_status'] ?? '') === 'verified'): ?>
                        Your organisation is verified and fully active on ResQFood.
                    <?php else: ?>
                        A complete profile helps the admin team verify your organisation faster.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Activity stats -->
        <?php
            $colStmt = db()->prepare('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"');
            $colStmt->execute([$uid]);
            $totalCollected = (int) $colStmt->fetchColumn();
        ?>
        <div class="card">
            <div class="card-body" style="padding:1rem 1.1rem">
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-bottom:.75rem">Your Activity</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <span style="font-size:.84rem;color:var(--text-muted)">Total collections</span>
                    <span style="font-weight:800;color:var(--olive-deep)"><?= $totalCollected ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:.84rem;color:var(--text-muted)">Profile complete</span>
                    <span style="font-weight:800;color:var(--olive-deep)"><?= $completion ?>%</span>
                </div>
            </div>
        </div>

    </aside>

    <!-- ── Main Area ── -->
    <div>

        <!-- ╌ Organisation Details Tab ╌ -->
        <div class="profile-section active" id="tab-details">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="update_profile">
                <!-- Preserve account fields so they are not overwritten -->
                <input type="hidden" name="full_name" value="<?= e($old['full_name']) ?>">
                <input type="hidden" name="phone"     value="<?= e($old['phone']) ?>">
                <input type="hidden" name="email_notifications_enabled" value="<?= ((int)($profile['email_notifications_enabled'] ?? 1) === 1) ? '1' : '0' ?>">

                <div class="card">
                    <div class="card-header"><h3>Organisation Information</h3></div>
                    <div class="card-body">

                        <p class="form-section-title">About Your Organisation</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="organization_name">
                                    Organisation Name <span class="required">*</span>
                                </label>
                                <input type="text" id="organization_name" name="organization_name"
                                       class="form-control <?= isset($errors['organization_name']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['organization_name']) ?>"
                                       maxlength="160" required>
                                <?php if (isset($errors['organization_name'])): ?>
                                    <span class="form-error"><?= e($errors['organization_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contact_person">Primary Contact Person</label>
                                <input type="text" id="contact_person" name="contact_person"
                                       class="form-control"
                                       value="<?= e($old['contact_person']) ?>"
                                       maxlength="120"
                                       placeholder="Name of contact for pickups">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">About Your Organisation</label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="3"
                                      placeholder="Describe your charity's mission, the communities you serve, and how you use the food you collect…"><?= e($old['description']) ?></textarea>
                        </div>

                        <p class="form-section-title" style="margin-top:1.5rem">Location</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <input type="text" id="address" name="address"
                                       class="form-control"
                                       value="<?= e($old['address']) ?>"
                                       placeholder="Street address or area">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="city">City / Town</label>
                                <input type="text" id="city" name="city"
                                       class="form-control"
                                       value="<?= e($old['city']) ?>"
                                       maxlength="80">
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Organisation Details</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ╌ Account Details Tab ╌ -->
        <div class="profile-section" id="tab-account">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="update_profile">
                <!-- Preserve org fields -->
                <input type="hidden" name="organization_name" value="<?= e($old['organization_name']) ?>">
                <input type="hidden" name="contact_person"    value="<?= e($old['contact_person']) ?>">
                <input type="hidden" name="address"           value="<?= e($old['address']) ?>">
                <input type="hidden" name="city"              value="<?= e($old['city']) ?>">
                <input type="hidden" name="description"       value="<?= e($old['description']) ?>">

                <div class="card">
                    <div class="card-header"><h3>Account Details</h3></div>
                    <div class="card-body">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="full_name">
                                    Full Name <span class="required">*</span>
                                </label>
                                <input type="text" id="full_name" name="full_name"
                                       class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['full_name']) ?>"
                                       maxlength="120" required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <span class="form-error"><?= e($errors['full_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['phone']) ?>"
                                       autocomplete="tel">
                                <?php if (isset($errors['phone'])): ?>
                                    <span class="form-error"><?= e($errors['phone']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control"
                                   value="<?= e($profile['email'] ?? '') ?>"
                                   disabled>
                            <span class="form-hint">Email cannot be changed. Contact support if needed.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email_notifications_enabled">Email Notifications</label>
                            <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;color:var(--text-mid)">
                                <input type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled" value="1" <?= ((int)($profile['email_notifications_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
                                Receive email notifications
                            </label>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Account Details</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ╌ Change Password Tab ╌ -->
        <div class="profile-section" id="tab-password">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="change_password">

                <div class="card">
                    <div class="card-header"><h3>Change Password</h3></div>
                    <div class="card-body">

                        <?php if (!empty($pwErrors)): ?>
                        <div class="flash flash--error mb-3">
                            <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>Please fix the errors below.</span>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="current_password">
                                Current Password <span class="required">*</span>
                            </label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control <?= isset($pwErrors['current_password']) ? 'is-invalid' : '' ?>"
                                   autocomplete="current-password" required>
                            <?php if (isset($pwErrors['current_password'])): ?>
                                <span class="form-error"><?= e($pwErrors['current_password']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="new_password">
                                    New Password <span class="required">*</span>
                                </label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-control <?= isset($pwErrors['new_password']) ? 'is-invalid' : '' ?>"
                                       autocomplete="new-password" required>
                                <span class="form-hint">Min 8 characters, 1 letter and 1 number.</span>
                                <?php if (isset($pwErrors['new_password'])): ?>
                                    <span class="form-error"><?= e($pwErrors['new_password']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">
                                    Confirm New Password <span class="required">*</span>
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="form-control <?= isset($pwErrors['password_confirm']) ? 'is-invalid' : '' ?>"
                                       autocomplete="new-password" required>
                                <?php if (isset($pwErrors['password_confirm'])): ?>
                                    <span class="form-error"><?= e($pwErrors['password_confirm']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </div>
            </form>
        </div>

    </div><!-- /.profile-main -->
</div><!-- /.profile-layout -->


<?php renderCharityShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
