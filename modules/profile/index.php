<?php
/**
 * ResQFood — General User Profile
 * Allows general_user accounts to update personal details and password.
 * Business and charity accounts have their own dedicated profile pages.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['general_user', 'admin']);

$uid      = currentUserId();
$pdo      = db();
$errors   = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();
    $action = $_POST['_action'] ?? '';

    // ── Update account details ────────────────────────────────────────────
    if ($action === 'update_account') {

        $data = [
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'phone'     => sanitize($_POST['phone']     ?? ''),
        ];

        validateRequired($data, ['full_name'], $errors);
        validateMaxLength($data['full_name'], 120, 'full_name', $errors);
        if ($data['phone'] !== '') validatePhone($data['phone'], 'phone', $errors);

        if (empty($errors)) {
            $pdo->prepare('
                UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?
            ')->execute([
                $data['full_name'],
                $data['phone'] !== '' ? $data['phone'] : null,
                $uid,
            ]);
            $_SESSION['user_name'] = $data['full_name'];
            auditLog('account_update', null, $uid);
            setFlash('success', 'Account details updated.');
            redirect(baseUrl('modules/profile/index.php'));
        }

    // ── Change password ───────────────────────────────────────────────────
    } elseif ($action === 'change_password') {

        $currentPw = $_POST['current_password'] ?? '';
        $newPw     = $_POST['new_password']     ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if ($currentPw === '') $pwErrors['current_password'] = 'Current password is required.';
        if ($newPw     === '') $pwErrors['new_password']     = 'New password is required.';

        if (empty($pwErrors)) {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row  = $stmt->fetch();

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
            redirect(baseUrl('modules/profile/index.php'));
        }
    }
}

// Load user
$user = getCurrentUser();
$old  = ['full_name' => $user['full_name'] ?? '', 'phone' => $user['phone'] ?? ''];
if (!empty($errors) && isset($_POST['_action']) && $_POST['_action'] === 'update_account') {
    $old['full_name'] = sanitize($_POST['full_name'] ?? $old['full_name']);
    $old['phone']     = sanitize($_POST['phone']     ?? $old['phone']);
}

$resStmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE reserved_by = ?');
$resStmt->execute([$uid]);
$totalReservations = (int) $resStmt->fetchColumn();

$colStmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE reserved_by = ? AND reservation_status = "collected"');
$colStmt->execute([$uid]);
$collectedReservations = (int) $colStmt->fetchColumn();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../../partials/header.php';
if (currentUserRole() === 'general_user') {
    require_once __DIR__ . '/../../partials/user_shell.php';
    renderUserShellStart('profile', 'My Profile', 'Manage your account details and security settings.');
}
?>
<?php $roleClass = currentUserRole() === 'general_user' ? 'user' : currentUserRole(); ?>
<div class="dash-welcome dash-welcome--<?= e($roleClass) ?>">
    <div class="dash-welcome__inner" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:1.5rem;">
            <div style="background:rgba(255,255,255,0.2); width:64px; height:64px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:2rem; font-weight:800; border:2px solid rgba(255,255,255,0.4);">
                <?= e(mb_strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1))) ?>
            </div>
            <div>
                <div class="dash-welcome__greeting"><?= e($user['full_name'] ?? 'My Profile') ?></div>
                <p class="dash-welcome__sub">
                    <?= e($user['email'] ?? '') ?>
                    <?php if (!empty($user['phone'])): ?> &middot; <?= e($user['phone']) ?><?php endif; ?>
                    &middot; Member since <?= formatDate($user['created_at'] ?? '', 'M Y') ?>
                </p>
            </div>
        </div>
        <div>
            <span class="role-badge role-badge--light" style="background:rgba(255,255,255,0.2);color:#fff;padding:0.4rem 0.8rem;border-radius:12px;font-size:0.85rem;font-weight:600;">
                <?= e(roleLabel($user['role'] ?? 'general_user')) ?>
            </span>
        </div>
    </div>
</div>

<div class="profile-layout" <?= !empty($pwErrors) ? 'data-default-tab="password"' : '' ?>>
    <aside class="profile-sidebar">
        <div class="card">
            <div class="card-body" style="padding:.75rem">
                <nav class="profile-tabs" id="profile-tabs">
                    <button class="profile-tab active" data-tab="account">
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

        <div class="card">
            <div class="card-body" style="padding:1rem 1.1rem">
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-bottom:.75rem">Your Activity</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <span style="font-size:.84rem;color:var(--text-muted)">Total reservations</span>
                    <span style="font-weight:800;color:var(--olive-deep)"><?= $totalReservations ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:.84rem;color:var(--text-muted)">Collected pickups</span>
                    <span style="font-weight:800;color:var(--olive-deep)"><?= $collectedReservations ?></span>
                </div>
            </div>
        </div>
    </aside>

    <div>
        <div class="profile-section active" id="tab-account">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="update_account">
                <div class="card">
                    <div class="card-header"><h3>Account Details</h3></div>
                    <div class="card-body">
                        <p class="form-section-title">Personal Information</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" value="<?= e($old['full_name']) ?>" maxlength="120" required>
                                <?php if (isset($errors['full_name'])): ?><span class="form-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e($old['phone']) ?>" autocomplete="tel">
                                <?php if (isset($errors['phone'])): ?><span class="form-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" disabled>
                            <span class="form-hint">Email address cannot be changed. Contact support if needed.</span>
                        </div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-primary">Save Account Details</button></div>
                </div>
            </form>
        </div>

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
                            <span>Please fix the errors below before saving.</span>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password <span class="required">*</span></label>
                            <div class="input-with-btn">
                                <input type="password" id="current_password" name="current_password" class="form-control <?= isset($pwErrors['current_password']) ? 'is-invalid' : '' ?>" autocomplete="current-password" required>
                                <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                                    <svg class="icon-eye" viewBox="0 0 20 20" width="15" fill="none"><path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/></svg>
                                    <svg class="icon-eye-off" viewBox="0 0 20 20" width="15" fill="none"><path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                                </button>
                            </div>
                            <?php if (isset($pwErrors['current_password'])): ?><span class="form-error"><?= e($pwErrors['current_password']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password <span class="required">*</span></label>
                                <div class="input-with-btn">
                                    <input type="password" id="new_password" name="new_password" class="form-control <?= isset($pwErrors['new_password']) ? 'is-invalid' : '' ?>" autocomplete="new-password" required>
                                    <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                                        <svg class="icon-eye" viewBox="0 0 20 20" width="15" fill="none"><path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/></svg>
                                        <svg class="icon-eye-off" viewBox="0 0 20 20" width="15" fill="none"><path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                                <span class="form-hint">Min 8 characters, 1 letter and 1 number.</span>
                                <?php if (isset($pwErrors['new_password'])): ?><span class="form-error"><?= e($pwErrors['new_password']) ?></span><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password <span class="required">*</span></label>
                                <div class="input-with-btn">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control <?= isset($pwErrors['password_confirm']) ? 'is-invalid' : '' ?>" autocomplete="new-password" required>
                                    <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                                        <svg class="icon-eye" viewBox="0 0 20 20" width="15" fill="none"><path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/></svg>
                                        <svg class="icon-eye-off" viewBox="0 0 20 20" width="15" fill="none"><path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                                <?php if (isset($pwErrors['password_confirm'])): ?><span class="form-error"><?= e($pwErrors['password_confirm']) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-primary">Update Password</button></div>
                </div>
            </form>
        </div>
    </div>
</div>


<?php if (currentUserRole() === 'general_user') renderUserShellEnd(); ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
