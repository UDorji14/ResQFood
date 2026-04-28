<?php
/**
 * ResQFood — Login Page
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/csrf.php';

redirectIfLoggedIn();

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $email    = sanitize($_POST['email']    ?? '');
    $password =          $_POST['password'] ?? '';

    $old = ['email' => $email];

    if ($email === '')    $errors['email']    = 'Email is required.';
    if ($password === '') $errors['password'] = 'Password is required.';

    if (empty($errors)) {

        $stmt = db()->prepare('
            SELECT id, full_name, email, password_hash, role, status
            FROM   users
            WHERE  email = ?
            LIMIT  1
        ');
        $stmt->execute([strtolower($email)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {

            if ($user['status'] === 'suspended') {
                $errors['email'] = 'Your account has been suspended. Please contact support.';

            } elseif ($user['status'] === 'pending') {
                $errors['email'] = 'Your account is pending approval. Please check back soon.';

            } elseif ($user['status'] === 'inactive') {
                $errors['email'] = 'Your account is inactive.';

            } else {
                auditLog('user_login', null, (int) $user['id']);
                loginUser($user);
                setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect(baseUrl('dashboard.php'));
            }

        } else {
            $errors['email'] = 'Invalid email address or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to your ResQFood account and start rescuing food.">
    <title>Log In — ResQFood</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet" media="print" onload="this.media='all'">
</head>
<body>

<div class="auth-shell">

    <!-- ══════════════════════════════════════════
         LEFT — Brand Panel (hidden on mobile)
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

            <h2>Good food shouldn't go to waste.</h2>
            <p>
                Join thousands of food businesses, charities, and community
                members already making a difference through surplus food rescue.
            </p>

            <!-- Illustration -->
            <div class="auth-brand-illustration" aria-hidden="true">
                <svg viewBox="0 0 340 200" width="100%" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Plate base -->
                    <ellipse cx="170" cy="155" rx="110" ry="20" fill="rgba(255,255,255,0.07)"/>
                    <!-- Jar left -->
                    <rect x="48" y="90" width="38" height="55" rx="4" fill="rgba(255,255,255,0.15)"/>
                    <rect x="52" y="80" width="30" height="14" rx="3" fill="rgba(255,255,255,0.2)"/>
                    <ellipse cx="67" cy="120" rx="10" ry="14" fill="rgba(255,255,255,0.08)"/>
                    <!-- Basket center -->
                    <path d="M120 145h100v-40c0-8-6-14-14-14H134c-8 0-14 6-14 14v40z" fill="rgba(255,255,255,0.18)"/>
                    <path d="M118 130h104" stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
                    <path d="M140 91 L133 130M155 91 L150 130M170 91 L170 130M185 91 L190 130M200 91 L207 130" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                    <!-- Bread loaf -->
                    <path d="M135 92c0-12 10-22 35-22s35 10 35 22" fill="rgba(255,255,255,0.2)"/>
                    <!-- Tomato right -->
                    <circle cx="255" cy="125" r="28" fill="rgba(181,96,74,0.35)"/>
                    <circle cx="255" cy="125" r="18" fill="rgba(181,96,74,0.2)"/>
                    <path d="M255 97 Q258 92 261 97" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none"/>
                    <!-- Leafy greens -->
                    <path d="M80 95 Q60 75 75 55 Q90 75 80 95z" fill="rgba(168,192,152,0.35)"/>
                    <path d="M73 92 Q55 70 68 50 Q83 72 73 92z" fill="rgba(168,192,152,0.25)"/>
                    <!-- Small dots decoration -->
                    <circle cx="100" cy="50" r="4" fill="rgba(255,255,255,0.15)"/>
                    <circle cx="240" cy="65" r="6" fill="rgba(255,255,255,0.1)"/>
                    <circle cx="290" cy="90" r="3" fill="rgba(255,255,255,0.12)"/>
                    <circle cx="55"  cy="70" r="3" fill="rgba(255,255,255,0.1)"/>
                    <!-- Arrow/flow -->
                    <path d="M170 30 L170 50M164 44 L170 50 L176 44" stroke="rgba(255,255,255,0.3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- Text label "rescued" -->
                    <rect x="138" y="150" width="64" height="18" rx="9" fill="rgba(255,255,255,0.15)"/>
                    <text x="170" y="163" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="9" font-family="sans-serif" font-weight="700" letter-spacing="1">RESCUED</text>
                </svg>
            </div>

            <div class="auth-brand-highlights">
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm4 9H6v-2h8v2z" fill="currentColor" opacity=".8"/>
                        </svg>
                    </div>
                    <span>Businesses post surplus food for free</span>
                </div>
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span>Charities and users reserve in seconds</span>
                </div>
                <div class="auth-brand-highlight">
                    <div class="auth-brand-highlight__icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none">
                            <path d="M10 3 L12.5 8.5 L18 9 L14 13 L15 18.5 L10 16 L5 18.5 L6 13 L2 9 L7.5 8.5 Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span>Track your real community impact</span>
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

            <h1 class="auth-form-title">Welcome back</h1>
            <p class="auth-form-subtitle">Sign in to manage your food rescue activity.</p>

            <!-- Server-side flash messages -->
            <?= displayFlash() ?>

            <?php if (!empty($errors['email']) && isset($_POST['email'])): ?>
            <div class="flash flash--error mb-3" role="alert">
                <svg viewBox="0 0 20 20" width="15" fill="none" aria-hidden="true">
                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span><?= e($errors['email']) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="email">
                        Email address <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control <?= isset($errors['email']) && !empty($old['email']) ? 'is-invalid' : '' ?>"
                        value="<?= e($old['email']) ?>"
                        autocomplete="email"
                        autofocus
                        required
                        aria-required="true"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        Password <span class="required" aria-hidden="true">*</span>
                    </label>
                    <div class="input-with-btn">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            autocomplete="current-password"
                            required
                            aria-required="true"
                        >
                        <button type="button" class="btn-input-action" data-toggle-pw aria-label="Show password">
                            <svg class="icon-eye" viewBox="0 0 20 20" width="16" fill="none">
                                <path d="M1 10s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" stroke="currentColor" stroke-width="1.4"/>
                                <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 20 20" width="16" fill="none">
                                <path d="M3 3l14 14M8.5 8.6A2.5 2.5 0 0011.4 11.5M6.3 5.3C4.3 6.5 2.7 8.4 1 10c0 0 3.5 7 9 7 1.6 0 3-.4 4.2-1.1M13.9 13.9C16 12.6 17.5 10.8 19 10c0 0-3.5-7-9-7-1.2 0-2.3.2-3.3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="form-error"><?= e($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <svg viewBox="0 0 18 18" width="15" fill="none" aria-hidden="true">
                            <path d="M3 9h12M10 4l5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Sign In
                    </button>
                </div>
            </form>

            <div class="auth-form-footer">
                Don't have an account? <a href="<?= baseUrl('register.php') ?>">Create one — it's free</a>
                <br>
                <a href="<?= baseUrl('index.php') ?>" style="margin-top:.5rem;display:inline-block;opacity:.6;font-size:.8rem">&larr; Back to home</a>
            </div>

        </div>
    </div>

</div><!-- /.auth-shell -->

<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
