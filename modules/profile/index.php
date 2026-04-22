<?php
/**
 * ResQFood — User Profile
 * TODO: Display and update user profile, role-specific details, password change.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireLogin();

$user = getCurrentUser();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> / <span>Profile</span>
    </div>
    <h1>My Profile</h1>
    <p class="text-muted">Manage your account details.</p>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header"><h3>Account Information</h3></div>
    <div class="card-body">
        <dl style="display:grid;grid-template-columns:140px 1fr;gap:.65rem 1.5rem;font-size:.9rem">
            <dt class="text-muted" style="font-weight:600">Full Name</dt>
            <dd><?= e($user['full_name'] ?? '—') ?></dd>

            <dt class="text-muted" style="font-weight:600">Email</dt>
            <dd><?= e($user['email'] ?? '—') ?></dd>

            <dt class="text-muted" style="font-weight:600">Phone</dt>
            <dd><?= e($user['phone'] ?? '—') ?></dd>

            <dt class="text-muted" style="font-weight:600">Role</dt>
            <dd>
                <span class="role-badge role-badge--<?= e(roleBadgeClass($user['role'])) ?>">
                    <?= e(roleLabel($user['role'])) ?>
                </span>
            </dd>

            <dt class="text-muted" style="font-weight:600">Account Status</dt>
            <dd>
                <span class="status-badge status-badge--<?= statusClass($user['status']) ?>">
                    <?= statusLabel($user['status']) ?>
                </span>
            </dd>

            <dt class="text-muted" style="font-weight:600">Joined</dt>
            <dd><?= formatDate($user['created_at'], 'd F Y') ?></dd>
        </dl>
    </div>
    <div class="card-footer">
        <p class="text-muted" style="font-size:.82rem">
            Profile editing coming soon.
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
