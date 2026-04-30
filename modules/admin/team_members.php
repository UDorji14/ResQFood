<?php
/**
 * ResQFood — Admin: Team Members Management
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole(['admin']);
$pdo = db();

// Ensure table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(120) NOT NULL,
        role_title VARCHAR(120) DEFAULT '',
        short_description TEXT DEFAULT '',
        image_path VARCHAR(255) DEFAULT '',
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── Handle POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'delete') {
        $delId = (int)($_POST['member_id'] ?? 0);
        // Delete image if exists
        $row = $pdo->prepare("SELECT image_path FROM team_members WHERE id = ?");
        $row->execute([$delId]);
        $r = $row->fetch();
        if ($r && $r['image_path']) {
            $f = __DIR__ . '/../../' . $r['image_path'];
            if (file_exists($f)) @unlink($f);
        }
        $pdo->prepare("DELETE FROM team_members WHERE id = ?")->execute([$delId]);
        setFlash('success', 'Team member deleted.');
        redirect('modules/admin/team_members.php');
    }

    if ($postAction === 'toggle') {
        $togId  = (int)($_POST['member_id'] ?? 0);
        $togVal = (int)($_POST['is_active'] ?? 0);
        $pdo->prepare("UPDATE team_members SET is_active = ? WHERE id = ?")->execute([$togVal ? 0 : 1, $togId]);
        redirect('modules/admin/team_members.php');
    }

    if (in_array($postAction, ['add', 'edit'])) {
        $name  = sanitize($_POST['full_name'] ?? '');
        $role  = sanitize($_POST['role_title'] ?? '');
        $bio   = sanitize($_POST['short_description'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 1);
        $memberId = (int)($_POST['member_id'] ?? 0);

        if (!$name) {
            setFlash('error', 'Full name is required.');
            redirect('modules/admin/team_members.php?action=' . ($postAction === 'edit' ? 'edit&id=' . $memberId : 'add'));
        }

        // Handle image upload
        $imagePath = sanitize($_POST['existing_image'] ?? '');
        if (isset($_FILES['member_image']) && $_FILES['member_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['member_image']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['member_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed) && $_FILES['member_image']['size'] <= 3 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../uploads/team/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileName = 'member_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                    // Delete old image
                    if ($imagePath) {
                        $old = __DIR__ . '/../../' . $imagePath;
                        if (file_exists($old)) @unlink($old);
                    }
                    $imagePath = 'uploads/team/' . $fileName;
                }
            } else {
                setFlash('error', 'Invalid image. Use JPG/PNG/WebP under 3MB.');
            }
        }

        if ($postAction === 'add') {
            $pdo->prepare("INSERT INTO team_members (full_name, role_title, short_description, image_path, display_order, is_active) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $role, $bio, $imagePath, $order, $active]);
            setFlash('success', 'Team member added successfully.');
        } else {
            $pdo->prepare("UPDATE team_members SET full_name=?, role_title=?, short_description=?, image_path=?, display_order=?, is_active=? WHERE id=?")
                ->execute([$name, $role, $bio, $imagePath, $order, $active, $memberId]);
            setFlash('success', 'Team member updated successfully.');
        }
        redirect('modules/admin/team_members.php');
    }
}

// ── Fetch data ───────────────────────────────────────────────
$members = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC")->fetchAll();
$editMember = null;
if ($action === 'edit' && $editId) {
    $s = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $s->execute([$editId]);
    $editMember = $s->fetch();
    if (!$editMember) redirect('modules/admin/team_members.php');
}

$pageTitle = 'Team Members';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/admin_shell.php';
renderAdminShellStart('settings', 'Team Members', 'Manage the team shown on the About Us page.');
?>

<style>
.tm-layout { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start; }
.tm-card { background: #fff; border-radius: 14px; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 0.75rem; transition: box-shadow 0.2s; }
.tm-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.1); }
.tm-card__photo { width: 52px; height: 52px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #e8f4e0; display: flex; align-items: center; justify-content: center; }
.tm-card__photo img { width: 100%; height: 100%; object-fit: cover; }
.tm-card__info { flex: 1; min-width: 0; }
.tm-card__name { font-weight: 700; font-size: 0.95rem; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tm-card__role { font-size: 0.78rem; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
.tm-card__bio { font-size: 0.82rem; color: var(--text-mid); margin-top: 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tm-card__actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
.tm-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
.tm-badge--on { background: #e0f2d8; color: #2d6a2d; }
.tm-badge--off { background: #f3e8e8; color: #8b2020; }
.tm-empty { padding: 3rem; text-align: center; color: var(--text-mid); background: var(--surface); border-radius: 14px; }
.form-panel { background: #fff; border-radius: 14px; padding: 1.75rem; box-shadow: 0 2px 12px rgba(0,0,0,0.07); position: sticky; top: 1.5rem; }
.form-panel h3 { font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-dark); }
.form-panel .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-mid); margin-bottom: 0.35rem; }
.form-panel .form-control { width: 100%; padding: 0.6rem 0.85rem; border: 1.5px solid var(--border); border-radius: 8px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
.form-panel .form-control:focus { border-color: var(--accent); }
.form-panel textarea.form-control { resize: vertical; min-height: 80px; }
.form-panel .form-group { margin-bottom: 1rem; }
.img-preview { width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: #e8f4e0; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; }
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
@media (max-width: 900px) { .tm-layout { grid-template-columns: 1fr; } .form-panel { position: static; } }
</style>

<div class="tm-layout">
    <!-- LEFT: Member list -->
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h2 style="font-size:1rem;font-weight:700;color:var(--text-dark);margin:0">Current Team (<?= count($members) ?>)</h2>
            <a href="<?= baseUrl('modules/admin/team_members.php?action=add') ?>" class="btn btn-primary" style="font-size:0.85rem;padding:0.5rem 1rem">+ Add Member</a>
        </div>

        <?php if (empty($members)): ?>
        <div class="tm-empty">
            <svg viewBox="0 0 48 48" width="44" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.35;margin-bottom:1rem"><circle cx="24" cy="18" r="9"/><path d="M6 42c0-9.9 8.1-18 18-18s18 8.1 18 18"/></svg>
            <p style="margin:0;font-size:0.9rem">No team members yet. Add your first one →</p>
        </div>
        <?php else: ?>
        <?php foreach ($members as $m): ?>
        <div class="tm-card">
            <div class="tm-card__photo">
                <?php if ($m['image_path']): ?>
                    <img src="<?= url($m['image_path']) ?>" alt="<?= e($m['full_name']) ?>">
                <?php else: ?>
                    <svg viewBox="0 0 24 24" width="24" fill="none" stroke="#4a6741" stroke-width="1.5"><circle cx="12" cy="8" r="5"/><path d="M3 21c0-5 4-9 9-9s9 4 9 9"/></svg>
                <?php endif; ?>
            </div>
            <div class="tm-card__info">
                <div class="tm-card__name"><?= e($m['full_name']) ?></div>
                <?php if ($m['role_title']): ?><div class="tm-card__role"><?= e($m['role_title']) ?></div><?php endif; ?>
                <?php if ($m['short_description']): ?><div class="tm-card__bio"><?= e($m['short_description']) ?></div><?php endif; ?>
            </div>
            <div class="tm-card__actions">
                <span class="tm-badge <?= $m['is_active'] ? 'tm-badge--on' : 'tm-badge--off' ?>"><?= $m['is_active'] ? 'Active' : 'Hidden' ?></span>
                <a href="<?= baseUrl('modules/admin/team_members.php?action=edit&id=' . $m['id']) ?>" class="btn btn-sm" style="font-size:0.8rem;padding:0.35rem 0.75rem">Edit</a>
                <form method="POST" action="<?= baseUrl('modules/admin/team_members.php') ?>" style="display:inline" onsubmit="return confirm('Delete <?= e(addslashes($m['full_name'])) ?>?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="font-size:0.8rem;padding:0.35rem 0.75rem;background:#fde8e8;color:#8b2020;border:none;cursor:pointer">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:1.5rem">
            <a href="<?= baseUrl('modules/admin/settings.php') ?>" style="font-size:0.85rem;color:var(--accent)">← Back to Site Settings</a>
        </div>
    </div>

    <!-- RIGHT: Add/Edit form -->
    <div class="form-panel">
        <?php if ($action === 'edit' && $editMember): ?>
        <h3>Edit Team Member</h3>
        <form method="POST" action="<?= baseUrl('modules/admin/team_members.php') ?>" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="member_id" value="<?= $editMember['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= e($editMember['image_path']) ?>">
        <?php else: ?>
        <h3>Add Team Member</h3>
        <form method="POST" action="<?= baseUrl('modules/admin/team_members.php') ?>" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="add">
        <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Photo</label>
                <?php $imgVal = $editMember['image_path'] ?? ''; ?>
                <?php if ($imgVal): ?>
                <div class="img-preview"><img src="<?= url($imgVal) ?>" alt="Photo"></div>
                <?php endif; ?>
                <input type="file" name="member_image" class="form-control" accept="image/png,image/jpeg,image/webp" style="padding:0.45rem">
                <small style="color:var(--text-mid);font-size:0.75rem;margin-top:0.3rem;display:block">JPG/PNG/WebP, max 3MB</small>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required value="<?= e($editMember['full_name'] ?? '') ?>" placeholder="e.g. Sarah Johnson">
            </div>
            <div class="form-group">
                <label class="form-label">Role / Title</label>
                <input type="text" name="role_title" class="form-control" value="<?= e($editMember['role_title'] ?? '') ?>" placeholder="e.g. Co-Founder & CEO">
            </div>
            <div class="form-group">
                <label class="form-label">Short Bio</label>
                <textarea name="short_description" class="form-control" placeholder="Brief description…"><?= e($editMember['short_description'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
                <div>
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= (int)($editMember['display_order'] ?? 0) ?>" min="0">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($editMember['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= isset($editMember['is_active']) && !$editMember['is_active'] ? 'selected' : '' ?>>Hidden</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:0.7rem"><?= ($action === 'edit') ? 'Save Changes' : 'Add Member' ?></button>
            <?php if ($action === 'edit'): ?>
            <a href="<?= baseUrl('modules/admin/team_members.php') ?>" style="display:block;text-align:center;margin-top:0.75rem;font-size:0.85rem;color:var(--text-mid)">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php
renderAdminShellEnd();
require_once __DIR__ . '/../../partials/footer.php';
?>
