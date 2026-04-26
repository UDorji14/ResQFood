<?php
/**
 * ResQFood — Business Profile Management
 * ────────────────────────────────────────
 * Business users can view and update their profile details.
 * Admins control verification_status — it is displayed here but not editable.
 *
 * Two forms on this page:
 *   _action=update_profile  → updates users + business_profiles
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

requireRole(['business']);

$uid    = currentUserId();
$pdo    = db();
$errors = [];
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
            'full_name'     => sanitize($_POST['full_name']     ?? ''),
            'phone'         => sanitize($_POST['phone']         ?? ''),
            'business_name' => sanitize($_POST['business_name'] ?? ''),
            'business_type' => sanitize($_POST['business_type'] ?? ''),
            'address'       => sanitize($_POST['address']       ?? ''),
            'city'          => sanitize($_POST['city']          ?? ''),
            'description'   => sanitize($_POST['description']   ?? ''),
            'pickup_notes'  => sanitize($_POST['pickup_notes']  ?? ''),
        ];

        // Validate
        validateRequired($data, ['full_name', 'business_name'], $errors);
        validateMaxLength($data['full_name'],     120, 'full_name',     $errors);
        validateMaxLength($data['business_name'], 160, 'business_name', $errors);
        validateMaxLength($data['business_type'], 80,  'business_type', $errors);
        validateMaxLength($data['city'],          80,  'city',          $errors);

        if ($data['phone'] !== '') {
            validatePhone($data['phone'], 'phone', $errors);
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update core user record
                $pdo->prepare('
                    UPDATE users
                    SET    full_name = ?, phone = ?, updated_at = NOW()
                    WHERE  id = ?
                ')->execute([
                    $data['full_name'],
                    $data['phone'] !== '' ? $data['phone'] : null,
                    $uid,
                ]);

                // Update business profile
                $pdo->prepare('
                    UPDATE business_profiles
                    SET    business_name = ?,
                           business_type = ?,
                           address       = ?,
                           city          = ?,
                           description   = ?,
                           pickup_notes  = ?,
                           updated_at    = NOW()
                    WHERE  user_id = ?
                ')->execute([
                    $data['business_name'],
                    $data['business_type'] !== '' ? $data['business_type'] : null,
                    $data['address']       !== '' ? $data['address']       : null,
                    $data['city']          !== '' ? $data['city']          : null,
                    $data['description']   !== '' ? $data['description']   : null,
                    $data['pickup_notes']  !== '' ? $data['pickup_notes']  : null,
                    $uid,
                ]);

                // Reflect name change in active session
                $_SESSION['user_name'] = $data['full_name'];

                $pdo->commit();
                auditLog('business_profile_update', null, $uid);
                setFlash('success', 'Business profile updated successfully.');

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[ResQFood BusinessProfile] ' . $e->getMessage());
                setFlash('error', 'Could not save profile. Please try again.');
            }

            // PRG — redirect to prevent form resubmission on refresh
            redirect(baseUrl('modules/profile/business_profile.php'));
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
            redirect(baseUrl('modules/profile/business_profile.php'));
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Load Data (runs on GET or when validation failed)
// ─────────────────────────────────────────────────────────────────────────
$profile    = getBusinessProfile($uid);
$completion = businessProfileCompletion($uid);

// Merge POST data if profile-form validation failed (preserves user input)
$old = [
    'full_name'     => $profile['full_name']     ?? '',
    'phone'         => $profile['phone']         ?? '',
    'business_name' => $profile['business_name'] ?? '',
    'business_type' => $profile['business_type'] ?? '',
    'address'       => $profile['address']       ?? '',
    'city'          => $profile['city']          ?? '',
    'description'   => $profile['description']   ?? '',
    'pickup_notes'  => $profile['pickup_notes']  ?? '',
];

if (!empty($errors)) {
    foreach ($old as $k => $_) {
        if (isset($_POST[$k])) {
            $old[$k] = sanitize($_POST[$k]);
        }
    }
}

$pageTitle = 'Business Profile';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>Business Profile</span>
    </div>
    <div class="page-head__top">
        <div>
            <h1>Business Profile</h1>
            <p class="text-muted">Keep your details accurate so customers can find and trust you.</p>
        </div>
        <?= verificationBadge($profile['verification_status'] ?? 'pending') ?>
    </div>
</div>

<?php if ($completion < 100): ?>
<div class="completion-strip">
    <span class="completion-strip__label">Profile completeness</span>
    <div class="completion-strip__bar"><?= profileProgressBar($completion) ?></div>
    <span class="completion-strip__pct"><?= $completion ?>%</span>
</div>
<?php endif; ?>

<div class="profile-layout">

    <!-- ── Sidebar ── -->
    <aside class="profile-sidebar">
        <div class="card">
            <div class="card-body" style="text-align:center;padding:1.75rem 1.5rem">
                <div class="profile-sidebar__avatar" style="margin:0 auto .85rem">
                    <?= e(mb_strtoupper(mb_substr($profile['full_name'] ?? 'U', 0, 1))) ?>
                </div>
                <div class="profile-sidebar__name"><?= e($profile['full_name'] ?? '') ?></div>
                <div class="profile-sidebar__email"><?= e($profile['email']     ?? '') ?></div>
                <div class="profile-sidebar__joined">Joined <?= formatDate($profile['joined_at'] ?? '', 'd M Y') ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding:.75rem">
                <nav class="profile-tabs" id="profile-tabs">
                    <button class="profile-tab active" data-tab="details">
                        <svg viewBox="0 0 18 18" width="15" fill="none"><rect x="2" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6 7h6M6 10h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                        Business Details
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

        <div class="card">
            <div class="card-body" style="padding:1rem 1.25rem">
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:.65rem">Verification</div>
                <?= verificationBadge($profile['verification_status'] ?? 'pending') ?>
                <p style="font-size:.76rem;color:var(--text-muted);margin-top:.5rem;line-height:1.5">
                    Verification is managed by the platform admin. Contact support if you have questions.
                </p>
            </div>
        </div>
    </aside>

    <!-- ── Main Area ── -->
    <div>

        <!-- ╌ Business Details Tab ╌ -->
        <div class="profile-section active" id="tab-details">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="update_profile">

                <div class="card">
                    <div class="card-header"><h3>Business Information</h3></div>
                    <div class="card-body">

                        <p class="form-section-title">About Your Business</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="business_name">
                                    Business Name <span class="required">*</span>
                                </label>
                                <input type="text" id="business_name" name="business_name"
                                       class="form-control <?= isset($errors['business_name']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['business_name']) ?>"
                                       maxlength="160" required>
                                <?php if (isset($errors['business_name'])): ?>
                                    <span class="form-error"><?= e($errors['business_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="business_type">Business Type</label>
                                <select id="business_type" name="business_type" class="form-control">
                                    <option value="">— Select type —</option>
                                    <?php foreach (['Restaurant', 'Bakery', 'Cafe', 'Supermarket', 'Hotel', 'Catering', 'Grocery', 'Other'] as $type): ?>
                                        <option value="<?= e($type) ?>" <?= $old['business_type'] === $type ? 'selected' : '' ?>>
                                            <?= e($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">About Your Business</label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="3"
                                      placeholder="Briefly describe your business, what food you typically have available…"><?= e($old['description']) ?></textarea>
                        </div>

                        <p class="form-section-title" style="margin-top:1.5rem">Location & Pickup</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="address">Street Address</label>
                                <input type="text" id="address" name="address"
                                       class="form-control"
                                       value="<?= e($old['address']) ?>"
                                       placeholder="123 Main Street">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="city">City / Town</label>
                                <input type="text" id="city" name="city"
                                       class="form-control"
                                       value="<?= e($old['city']) ?>"
                                       maxlength="80">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pickup_notes">Default Pickup Instructions</label>
                            <textarea id="pickup_notes" name="pickup_notes"
                                      class="form-control" rows="2"
                                      placeholder="e.g. Come to the side entrance. Ring the bell. Pickup from 5–7 pm."><?= e($old['pickup_notes']) ?></textarea>
                            <span class="form-hint">These appear on all your listings automatically.</span>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Business Details</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ╌ Account Details Tab ╌ -->
        <div class="profile-section" id="tab-account">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="_action" value="update_profile">
                <!-- Re-include business fields as hidden so we don't overwrite them -->
                <input type="hidden" name="business_name" value="<?= e($old['business_name']) ?>">
                <input type="hidden" name="business_type" value="<?= e($old['business_type']) ?>">
                <input type="hidden" name="address"       value="<?= e($old['address']) ?>">
                <input type="hidden" name="city"          value="<?= e($old['city']) ?>">
                <input type="hidden" name="description"   value="<?= e($old['description']) ?>">
                <input type="hidden" name="pickup_notes"  value="<?= e($old['pickup_notes']) ?>">

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
                            <span class="form-hint">Email address cannot be changed. Contact support if needed.</span>
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
                            <span>Please fix the errors below before saving.</span>
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

<script>
// Tab switching logic
(function () {
    var tabs    = document.querySelectorAll('#profile-tabs .profile-tab');
    var sections = document.querySelectorAll('.profile-section');

    // If password errors exist, open the password tab automatically
    <?php if (!empty($pwErrors)): ?>
    switchTab('password');
    <?php endif; ?>

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchTab(btn.dataset.tab);
        });
    });

    function switchTab(tabId) {
        tabs.forEach(function (b) { b.classList.remove('active'); });
        sections.forEach(function (s) { s.classList.remove('active'); });

        var activeBtn = document.querySelector('[data-tab="' + tabId + '"]');
        var activeSection = document.getElementById('tab-' + tabId);
        if (activeBtn)     activeBtn.classList.add('active');
        if (activeSection) activeSection.classList.add('active');
    }
}());
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
