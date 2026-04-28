<?php
/**
 * ResQFood — Registration
 * ────────────────────────
 * Public registration for: business, general_user, charity.
 * Admin accounts are created manually — not available here.
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

    validateEnum($data['role'], ['business', 'general_user', 'charity'], 'role', $errors);

    if ($data['phone'] !== '') {
        validatePhone($data['phone'], 'phone', $errors);
    }

    if (empty($errors)) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

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
            if (in_array($data['role'], ['business', 'charity'])) {
                $msg .= ' Your profile is pending admin verification — complete it after logging in to speed up the process.';
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
    <meta name="description" content="Create your free ResQFood account and join the food rescue community.">
    <title>Create Account — ResQFood</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet" media="print" onload="this.media='all'">
    <style>
        /* Role selector cards */
        .role-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.6rem;
            margin-top: 0.4rem;
        }
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }
        .role-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            padding: 0.85rem 0.5rem;
            border: 1.5px solid var(--line-strong);
            border-radius: var(--r-lg);
            cursor: pointer;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-mid);
            transition: border-color 140ms, background 140ms, color 140ms, box-shadow 140ms;
        }
        .role-option label:hover {
            border-color: var(--olive);
            background: var(--olive-light);
        }
        .role-option input:checked + label {
            border-color: var(--olive);
            background: rgba(74,103,65,0.08);
            color: var(--olive);
            box-shadow: 0 0 0 3px rgba(74,103,65,0.1);
        }
        .role-option label svg { opacity: 0.5; transition: opacity 140ms; }
        .role-option input:checked + label svg { opacity: 1; }
        .role-name { font-size: 0.78rem; font-weight: 700; }
        .role-desc { font-size: 0.67rem; font-weight: 500; color: var(--text-muted); line-height: 1.3; }
        .role-option input:checked + label .role-desc { color: rgba(74,103,65,0.75); }

        /* Role notice */
        .role-notice {
            display: none;
            padding: 0.65rem 0.9rem;
            border-radius: var(--r-md);
            font-size: 0.79rem;
            color: #7a5210;
            background: rgba(196,145,62,0.08);
            border: 1px solid rgba(196,145,62,0.28);
            margin-top: 0.65rem;
            line-height: 1.55;
        }
        .role-notice.visible { display: block; }

        /* Password strength indicator */
        .pw-strength {
            display: flex;
            gap: 3px;
            margin-top: 6px;
        }
        .pw-strength-bar {
            flex: 1;
            height: 3px;
            border-radius: 2px;
            background: var(--line);
            transition: background 300ms;
        }
        .pw-strength-bar.active-weak   { background: var(--terra); }
        .pw-strength-bar.active-medium { background: var(--amber); }
        .pw-strength-bar.active-strong { background: var(--olive); }

        @media (max-width: 420px) {
            .role-options { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <!-- ══════════════════════════════════════════
         LEFT — Brand Panel (desktop only)
    ══════════════════════════════════════════ -->
    <div class="auth-brand-panel">
        <div class="auth-brand-panel__inner">

            <a href="<?= baseUrl() ?>" class="auth-brand-logo">
                <svg viewBox="0 0 28 28" width="24" fill="none" aria-hidden="true">
                    <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="rgba(255,255,255,0.95)"/>
                    <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="rgba(255,255,255,0.45)"/>
                </svg>
                ResQFood
            </a>

            <h2>Every plate tells a rescue story.</h2>
            <p>
                Whether you're a food business with surplus, a charity serving your
                community, or an individual who wants to help — ResQFood connects you.
            </p>

            <!-- SVG illustration -->
            <div class="auth-brand-illustration" aria-hidden="true">
                <svg viewBox="0 0 340 200" width="100%" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Community ring -->
                    <circle cx="170" cy="110" r="70" stroke="rgba(255,255,255,0.08)" stroke-width="2"/>
                    <circle cx="170" cy="110" r="48" stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>
                    <!-- Business node -->
                    <circle cx="170" cy="50" r="18" fill="rgba(255,255,255,0.15)"/>
                    <rect x="163" y="45" width="14" height="10" rx="1.5" stroke="rgba(255,255,255,0.7)" stroke-width="1.3" fill="none"/>
                    <rect x="166" y="52" width="4" height="3" rx=".5" fill="rgba(255,255,255,0.6)"/>
                    <rect x="172" y="52" width="4" height="3" rx=".5" fill="rgba(255,255,255,0.6)"/>
                    <path d="M163 45 L165 40 L175 40 L177 45" stroke="rgba(255,255,255,0.7)" stroke-width="1.2" stroke-linejoin="round" fill="none"/>
                    <!-- Charity node -->
                    <circle cx="104" cy="145" r="18" fill="rgba(255,255,255,0.12)"/>
                    <path d="M104 137 Q107 133 110 137 Q114 133 114 138 Q114 143 104 150 Q94 143 94 138 Q94 133 98 133 Q101 133 104 137z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="none"/>
                    <!-- User node -->
                    <circle cx="236" cy="145" r="18" fill="rgba(255,255,255,0.12)"/>
                    <circle cx="236" cy="141" r="5" stroke="rgba(255,255,255,0.7)" stroke-width="1.3" fill="none"/>
                    <path d="M224 155 Q224 149 236 149 Q248 149 248 155" stroke="rgba(255,255,255,0.7)" stroke-width="1.3" fill="none"/>
                    <!-- Connection lines -->
                    <line x1="170" y1="68" x2="118" y2="130" stroke="rgba(255,255,255,0.2)" stroke-width="1.2" stroke-dasharray="4 3"/>
                    <line x1="170" y1="68" x2="222" y2="130" stroke="rgba(255,255,255,0.2)" stroke-width="1.2" stroke-dasharray="4 3"/>
                    <line x1="120" y1="148" x2="218" y2="148" stroke="rgba(255,255,255,0.15)" stroke-width="1" stroke-dasharray="3 3"/>
                    <!-- Center label -->
                    <rect x="144" y="100" width="52" height="20" rx="10" fill="rgba(255,255,255,0.15)"/>
                    <text x="170" y="114" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-size="9" font-family="sans-serif" font-weight="700" letter-spacing=".5">PLATFORM</text>
                    <!-- Sparkles -->
                    <circle cx="85"  cy="65"  r="2.5" fill="rgba(255,255,255,0.2)"/>
                    <circle cx="262" cy="72"  r="3"   fill="rgba(255,255,255,0.15)"/>
                    <circle cx="290" cy="130" r="2"   fill="rgba(255,255,255,0.15)"/>
                    <circle cx="60"  cy="130" r="2"   fill="rgba(255,255,255,0.12)"/>
                </svg>
            </div>

            <div class="auth-brand-highlights">
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <rect x="3" y="7" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M7 7V5a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.3"/>
                        </svg>
                    </div>
                    <span>Free to join — no subscription needed</span>
                </div>
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <path d="M10 2l2 5h5l-4 3 1.5 5L10 12l-4.5 3L7 10 3 7h5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span>Trusted by businesses and charities alike</span>
                </div>
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <path d="M10 18s-7-4-7-9a7 7 0 0114 0c0 5-7 9-7 9z" stroke="currentColor" stroke-width="1.3"/>
                            <circle cx="10" cy="9" r="2" fill="currentColor" opacity=".7"/>
                        </svg>
                    </div>
                    <span>Local, community-driven impact every day</span>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════
         RIGHT — Form Panel
    ══════════════════════════════════════════ -->
    <div class="auth-form-panel">
        <div class="auth-form-panel__inner">

            <!-- Mobile-only logo -->
            <a href="<?= baseUrl() ?>" class="auth-mobile-brand">
                <svg viewBox="0 0 28 28" width="22" fill="none" aria-hidden="true">
                    <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="#4a6741"/>
                    <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="#7a9a6a" opacity=".6"/>
                </svg>
                ResQFood
            </a>

            <h1 class="auth-form-title">Create your account</h1>
            <p class="auth-form-subtitle">Join the food rescue community — free forever.</p>

            <?= displayFlash() ?>

            <?php if (!empty($errors['_general'])): ?>
            <div class="flash flash--error mb-3" role="alert">
                <svg viewBox="0 0 20 20" width="15" fill="none" aria-hidden="true">
                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span><?= e($errors['_general']) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate id="reg-form">
                <?= csrfField() ?>

                <!-- Role Selection -->
                <div class="form-group">
                    <label class="form-label">
                        I am a&hellip; <span class="required" aria-hidden="true">*</span>
                    </label>

                    <div class="role-options" role="radiogroup" aria-label="Account type">
                        <div class="role-option">
                            <input type="radio" name="role" id="role_business" value="business"
                                   <?= $old['role'] === 'business' ? 'checked' : '' ?>>
                            <label for="role_business">
                                <svg viewBox="0 0 24 24" width="22" stroke="currentColor" fill="none" stroke-width="1.5">
                                    <path d="M3 9l1-5h16l1 5H3z"/><rect x="5" y="9" width="14" height="12" rx="1"/>
                                    <path d="M9 21V13h6v8"/>
                                </svg>
                                <span class="role-name">Food Business</span>
                                <span class="role-desc">Post surplus food</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" name="role" id="role_user" value="general_user"
                                   <?= $old['role'] === 'general_user' ? 'checked' : '' ?>>
                            <label for="role_user">
                                <svg viewBox="0 0 24 24" width="22" stroke="currentColor" fill="none" stroke-width="1.5">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M4 21v-1a8 8 0 0116 0v1"/>
                                </svg>
                                <span class="role-name">General User</span>
                                <span class="role-desc">Browse & reserve</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" name="role" id="role_charity" value="charity"
                                   <?= $old['role'] === 'charity' ? 'checked' : '' ?>>
                            <label for="role_charity">
                                <svg viewBox="0 0 24 24" width="22" stroke="currentColor" fill="none" stroke-width="1.5">
                                    <path d="M12 21s-8-4.5-8-11a8 8 0 0116 0c0 6.5-8 11-8 11z"/>
                                </svg>
                                <span class="role-name">Charity</span>
                                <span class="role-desc">Collect for community</span>
                            </label>
                        </div>
                    </div>

                    <?php if (isset($errors['role'])): ?>
                        <span class="form-error"><?= e($errors['role']) ?></span>
                    <?php endif; ?>

                    <div class="role-notice" id="notice-business" role="status">
                        <strong style="display:block;margin-bottom:.15rem">Requires verification</strong>
                        Your business profile will need admin approval before you can post food listings.
                        You can complete your profile immediately after signing up.
                    </div>
                    <div class="role-notice" id="notice-charity" role="status">
                        <strong style="display:block;margin-bottom:.15rem">Requires verification</strong>
                        Your charity profile needs admin approval to unlock full access. Completing your
                        profile details speeds up the process significantly.
                    </div>
                </div>

                <!-- Personal details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">
                            Full name <span class="required" aria-hidden="true">*</span>
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
                            Phone <span class="text-muted text-sm" style="font-weight:400">(optional)</span>
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
                        Email address <span class="required" aria-hidden="true">*</span>
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
                            Password <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-with-btn">
                        <input type="password" id="password" name="password"
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               autocomplete="new-password" required>
                        <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                            <svg class="icon-eye" viewBox="0 0 20 20" width="16" fill="none"><path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/></svg>
                            <svg class="icon-eye-off" viewBox="0 0 20 20" width="16" fill="none"><path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                        </button>
                        </div>
                        <!-- Password strength bars -->
                        <div class="pw-strength" id="pw-strength" aria-hidden="true">
                            <div class="pw-strength-bar" id="pb1"></div>
                            <div class="pw-strength-bar" id="pb2"></div>
                            <div class="pw-strength-bar" id="pb3"></div>
                            <div class="pw-strength-bar" id="pb4"></div>
                        </div>
                        <span class="form-hint">At least 8 characters with a letter and number.</span>
                        <?php if (isset($errors['password'])): ?>
                            <span class="form-error"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirm">
                            Confirm password <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-with-btn">
                        <input type="password" id="password_confirm" name="password_confirm"
                               class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                               autocomplete="new-password" required>
                        <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                            <svg class="icon-eye" viewBox="0 0 20 20" width="16" fill="none"><path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/></svg>
                            <svg class="icon-eye-off" viewBox="0 0 20 20" width="16" fill="none"><path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                        </button>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <span class="form-error"><?= e($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <svg viewBox="0 0 18 18" width="15" fill="none" aria-hidden="true">
                            <circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M6 9l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Create My Account
                    </button>
                </div>
            </form>

            <div class="auth-form-footer">
                Already have an account? <a href="<?= baseUrl('login.php') ?>">Sign in</a>
                <br>
                <a href="<?= baseUrl('index.php') ?>" style="margin-top:.5rem;display:inline-block;opacity:.6;font-size:.8rem">&larr; Back to home</a>
            </div>

        </div>
    </div>

</div><!-- /.auth-shell -->

<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
<script>
(function () {
    /* Role notice toggling */
    var radios  = document.querySelectorAll('[name="role"]');
    var notices = {
        business:    document.getElementById('notice-business'),
        charity:     document.getElementById('notice-charity')
    };

    function updateNotice() {
        Object.values(notices).forEach(function (n) {
            if (n) n.classList.remove('visible');
        });
        var checked = document.querySelector('[name="role"]:checked');
        if (checked && notices[checked.value]) {
            notices[checked.value].classList.add('visible');
        }
    }

    radios.forEach(function (r) { r.addEventListener('change', updateNotice); });
    updateNotice();

    /* Password strength indicator */
    var pwInput = document.getElementById('password');
    var bars    = [
        document.getElementById('pb1'),
        document.getElementById('pb2'),
        document.getElementById('pb3'),
        document.getElementById('pb4'),
    ];

    function getStrength(pw) {
        var score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw) || /[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return score;
    }

    function colorClass(score, i) {
        if (i >= score) return '';
        if (score <= 1) return 'active-weak';
        if (score <= 2) return 'active-medium';
        return 'active-strong';
    }

    if (pwInput) {
        pwInput.addEventListener('input', function () {
            var score = getStrength(this.value);
            bars.forEach(function (bar, i) {
                bar.className = 'pw-strength-bar ' + colorClass(score, i);
            });
        });
    }
}());
</script>
</body>
</html>
