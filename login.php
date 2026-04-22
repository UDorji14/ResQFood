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
    $password =          $_POST['password'] ?? '';   // Do NOT sanitize passwords

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
            // Generic message — do not reveal which field was wrong
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
    <title>Log In — ResQFood</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/app.css') ?>">
</head>
<body class="auth-page">

<div class="auth-card">

    <div class="auth-card__brand">
        <a href="<?= baseUrl() ?>" class="brand-mark">
            <svg viewBox="0 0 28 28" width="26" fill="none" aria-hidden="true">
                <path d="M14 3C8 3 3 8.5 3 15c0 5 3.5 9 11 10V3z" fill="#4a6741" opacity=".9"/>
                <path d="M14 3c6 0 11 5.5 11 12 0 5-3.5 9-11 10V3z" fill="#7a9a6a" opacity=".6"/>
            </svg>
            ResQFood
        </a>
        <p>Sign in to your account</p>
    </div>

    <div class="auth-card__body">

        <?= displayFlash() ?>

        <?php if (!empty($errors['_general'])): ?>
            <div class="flash flash--error">
                <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span><?= e($errors['_general']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email address <span class="required">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= e($old['email']) ?>"
                    autocomplete="email"
                    autofocus
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="form-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    autocomplete="current-password"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="form-error"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Log In
                </button>
            </div>
        </form>

    </div>

    <div class="auth-card__footer">
        Don't have an account? <a href="<?= baseUrl('register.php') ?>">Create one</a>
        &nbsp;·&nbsp;
        <a href="<?= baseUrl() ?>">Back to home</a>
    </div>

</div>

</body>
</html>
