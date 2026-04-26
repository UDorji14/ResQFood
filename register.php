<?php
/**
 * ResQFood — Registration
 * ────────────────────────
 * Public registration for: business, general_user, charity.
 * Admin accounts are created manually — not available here.
 *
 * Flow:
 *  GET  → render form (prefill from $old if returning after error)
 *  POST → validate → insert user + role profile → flash → redirect to login
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/csrf.php';

redirectIfLoggedIn();

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => '', 'role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $data = [
        'full_name'        => sanitize($_POST['full_name']        ?? ''),
        'email'            => sanitize($_POST['email']            ?? ''),
        'phone'            => sanitize($_POST['phone']            ?? ''),
        'role'             => sanitize($_POST['role']             ?? ''),
        'password'         =>          $_POST['password']         ?? '',
        'password_confirm' =>          $_POST['password_confirm'] ?? '',
    ];
    $old = $data;

    // ── Validation ────────────────────────────────────────────────────────
    validateRequired($data, ['full_name', 'email', 'role', 'password', 'password_confirm'], $errors);
    validateMaxLength($data['full_name'], 120, 'full_name', $errors);

    if (empty($errors['email'])) {
        validateEmail($data['email'], 'email', $errors);
        if (empty($errors['email']) && emailExists($data['email'])) {
            $errors['email'] = 'That email address is already registered.';
        }
    }

    if (empty($errors['password'])) {
        validatePassword($data['password'], 'password', $errors);
    }

    if (empty($errors['password']) && empty($errors['password_confirm'])) {
        validatePasswordMatch($data['password'], $data['password_confirm'], $errors);
    }

    // Only allow public registration for these three roles
    validateEnum($data['role'], ['business', 'general_user', 'charity'], 'role', $errors);

    if ($data['phone'] !== '') {
        validatePhone($data['phone'], 'phone', $errors);
    }

    // ── Persist ───────────────────────────────────────────────────────────
    if (empty($errors)) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // Insert users row — everyone starts as 'active'
            // business_profiles.verification_status controls listing access separately
            $stmt = $pdo->prepare('
                INSERT INTO users (full_name, email, phone, password_hash, role, status)
                VALUES (?, ?, ?, ?, ?, "active")
            ');
            $stmt->execute([
                $data['full_name'],
                strtolower($data['email']),
                $data['phone'] !== '' ? $data['phone'] : null,
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['role'],
            ]);
            $userId = (int) $pdo->lastInsertId();

            // Create the role-specific profile stub
            if ($data['role'] === 'business') {
                $pdo->prepare('
                    INSERT INTO business_profiles (user_id, business_name, verification_status)
                    VALUES (?, ?, "pending")
                ')->execute([$userId, $data['full_name']]);

            } elseif ($data['role'] === 'charity') {
                $pdo->prepare('
                    INSERT INTO charity_profiles (user_id, organization_name, verification_status)
                    VALUES (?, ?, "pending")
                ')->execute([$userId, $data['full_name']]);
            }

            auditLog('user_register', 'role=' . $data['role'], $userId);

            $pdo->commit();

            $msg = 'Account created! You can now log in.';
            if ($data['role'] === 'business' || $data['role'] === 'charity') {
                $msg .= ' Your profile is pending admin verification — complete your profile after logging in to speed up the process.';
            }
            setFlash('success', $msg);
            redirect(baseUrl('login.php'));

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[ResQFood Register] ' . $e->getMessage());
            $errors['_general'] = 'Registration failed due to a server error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — ResQFood</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
    <style>
        /* ── Role selector cards ── */
        .role-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: .65rem; margin-top: .4rem; }
        .role-option input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .role-option label {
            display: flex; flex-direction: column; align-items: center; gap: .4rem;
            padding: .9rem .5rem;
            border: 1.5px solid var(--line-strong); border-radius: var(--r-lg);
            cursor: pointer; text-align: center; font-size: .8rem; font-weight: 600;
            color: var(--text-mid);
            transition: border-color 140ms, background 140ms, color 140ms;
        }
        .role-option input:checked + label {
            border-color: var(--olive); background: rgba(74,103,65,.07); color: var(--olive);
        }
        .role-option label svg { opacity: .55; transition: opacity 140ms; }
        .role-option input:checked + label svg { opacity: 1; }
        @media (max-width: 420px) { .role-options { grid-template-columns: 1fr; } }

        /* ── Role-specific notice shown after role selection ── */
        .role-notice {
            display: none; padding: .7rem 1rem; border-radius: var(--r-md);
            font-size: .8rem; color: #7a5a10;
            background: rgba(196,145,62,.08); border: 1px solid rgba(196,145,62,.3);
            margin-top: .75rem; line-height: 1.5;
        }
        .role-notice.visible { display: block; }
    </style>
</head>
<body class="auth-page">

<div class="auth-card" style="max-width:500px">

    <div class="auth-card__brand">
        <a href="<?= baseUrl() ?>" class="brand-mark">
            <svg viewBox="0 0 28 28" width="26" fill="none" aria-hidden="true">
                <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="#4a6741" opacity=".9"/>
                <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="#7a9a6a" opacity=".6"/>
            </svg>
            ResQFood
        </a>
        <p>Join the food rescue community</p>
    </div>

    <div class="auth-card__body">

        <?php if (!empty($errors['_general'])): ?>
        <div class="flash flash--error mb-3">
            <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span><?= e($errors['_general']) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate id="reg-form">
            <?= csrfField() ?>

            <!-- ── Step 1: Choose role ── -->
            <div class="form-group">
                <label class="form-label">I am a&hellip; <span class="required">*</span></label>

                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" name="role" id="role_business" value="business"
                               <?= $old['role'] === 'business' ? 'checked' : '' ?>>
                        <label for="role_business">
                            <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l1-5h16l1 5H3z"/><rect x="5" y="9" width="14" height="12" rx="1"/>
                                <path d="M9 21V13h6v8"/>
                            </svg>
                            Food Business
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" id="role_user" value="general_user"
                               <?= $old['role'] === 'general_user' ? 'checked' : '' ?>>
                        <label for="role_user">
                            <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="7" r="4"/><path d="M4 21v-1a8 8 0 0116 0v1"/>
                            </svg>
                            General User
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" id="role_charity" value="charity"
                               <?= $old['role'] === 'charity' ? 'checked' : '' ?>>
                        <label for="role_charity">
                            <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 21s-8-4.5-8-11a8 8 0 0116 0c0 6.5-8 11-8 11z"/>
                            </svg>
                            Charity
                        </label>
                    </div>
                </div>

                <?php if (isset($errors['role'])): ?>
                    <span class="form-error"><?= e($errors['role']) ?></span>
                <?php endif; ?>

                <div class="role-notice" id="notice-business">
                    Your business profile will require admin verification before you can post food listings.
                    You can complete it right after signing up.
                </div>
                <div class="role-notice" id="notice-charity">
                    Your charity profile will need admin verification. Complete your profile details after sign-up to speed up the process.
                </div>
            </div>

            <!-- ── Personal details ── -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="full_name">
                        Full name <span class="required">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($old['full_name']) ?>"
                           autocomplete="name" maxlength="120" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="form-error"><?= e($errors['full_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">
                        Phone <span class="text-muted" style="font-weight:400">(optional)</span>
                    </label>
                    <input type="tel" id="phone" name="phone"
                           class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           value="<?= e($old['phone']) ?>" autocomplete="tel">
                    <?php if (isset($errors['phone'])): ?>
                        <span class="form-error"><?= e($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">
                    Email address <span class="required">*</span>
                </label>
                <input type="email" id="email" name="email"
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       value="<?= e($old['email']) ?>" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="form-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">
                        Password <span class="required">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           autocomplete="new-password" required>
                    <span class="form-hint">At least 8 characters, 1 letter and 1 number.</span>
                    <?php if (isset($errors['password'])): ?>
                        <span class="form-error"><?= e($errors['password']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirm">
                        Confirm password <span class="required">*</span>
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                           autocomplete="new-password" required>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="form-error"><?= e($errors['password_confirm']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </div>
        </form>

    </div>

    <div class="auth-card__footer">
        Already have an account? <a href="<?= baseUrl('login.php') ?>">Log in</a>
        &nbsp;&middot;&nbsp;
        <a href="<?= baseUrl() ?>">Back to home</a>
    </div>

</div>

<script>
// Show role-appropriate notice and auto-fill business name hint
(function () {
    var radios   = document.querySelectorAll('[name="role"]');
    var notices  = { business: document.getElementById('notice-business'), charity: document.getElementById('notice-charity') };

    function updateNotice() {
        Object.values(notices).forEach(function (n) { if (n) n.classList.remove('visible'); });
        var checked = document.querySelector('[name="role"]:checked');
        if (checked && notices[checked.value]) {
            notices[checked.value].classList.add('visible');
        }
    }

    radios.forEach(function (r) { r.addEventListener('change', updateNotice); });
    updateNotice(); // Run on load in case browser restores checked state
}());
</script>

</body>
</html>
