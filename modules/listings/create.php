<?php
/**
 * ResQFood — Create Food Listing
 * Only accessible to verified business users.
 * Handles the form + image upload in a single POST.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/profile.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['business']);

$uid    = currentUserId();
$pdo    = db();
$errors = [];

$old = [
    'title'          => '', 'category'    => '', 'quantity'       => '',
    'unit'           => 'portions', 'description' => '',
    'pickup_address' => '', 'pickup_start' => '', 'pickup_end'    => '',
    'expiry_time'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $data = [
        'title'          => sanitize($_POST['title']          ?? ''),
        'category'       => sanitize($_POST['category']       ?? ''),
        'quantity'       => sanitize($_POST['quantity']       ?? ''),
        'unit'           => sanitize($_POST['unit']           ?? 'portions'),
        'description'    => sanitize($_POST['description']    ?? ''),
        'pickup_address' => sanitize($_POST['pickup_address'] ?? ''),
        'pickup_start'   => normaliseDatetime($_POST['pickup_start'] ?? ''),
        'pickup_end'     => normaliseDatetime($_POST['pickup_end']   ?? ''),
        'expiry_time'    => normaliseDatetime($_POST['expiry_time']  ?? ''),
    ];
    $old = $data;

    // ── Validation ────────────────────────────────────────────────────────
    validateRequired($data, ['title', 'quantity', 'unit', 'pickup_start', 'pickup_end'], $errors);
    validateMaxLength($data['title'], 200, 'title', $errors);

    if ($data['quantity'] !== '') {
        validateNumeric($data['quantity'], 'quantity', $errors, 0.01);
    }
    if ($data['pickup_start'] !== '' && $data['pickup_end'] !== '') {
        validateDateOrder($data['pickup_start'], $data['pickup_end'], 'pickup_end', $errors);
    }
    if ($data['expiry_time'] !== '' && $data['pickup_end'] !== '') {
        if (strtotime($data['expiry_time']) < strtotime($data['pickup_end'])) {
            $errors['expiry_time'] = 'Expiry time should be at or after the pickup end time.';
        }
    }
    if ($data['category'] !== '') {
        validateEnum($data['category'], listingCategoryOptions(), 'category', $errors);
    }
    if ($data['unit'] !== '') {
        validateEnum($data['unit'], listingUnitOptions(), 'unit', $errors);
    }

    // ── Persist ───────────────────────────────────────────────────────────
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $qty = (float) $data['quantity'];
            $stmt = $pdo->prepare('
                INSERT INTO food_listings
                    (business_user_id, title, category, quantity, available_quantity, unit,
                     description, pickup_address, pickup_start, pickup_end, expiry_time, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "available")
            ');
            $stmt->execute([
                $uid,
                $data['title'],
                $data['category']       ?: null,
                $qty,
                $qty,  // available_quantity starts equal to quantity
                $data['unit'],
                $data['description']    ?: null,
                $data['pickup_address'] ?: null,
                $data['pickup_start'],
                $data['pickup_end'],
                $data['expiry_time']    ?: null,
            ]);
            $listingId = (int) $pdo->lastInsertId();

            // Handle optional image upload
            $imageError = '';
            if (!empty($_FILES['image']['name'])) {
                try {
                    uploadListingImage($_FILES['image'], $listingId, true);
                } catch (RuntimeException $e) {
                    $imageError = $e->getMessage();
                }
            }

            auditLog('listing_create', 'id=' . $listingId, $uid);
            $pdo->commit();

            $msg = 'Listing "' . truncate($data['title'], 40) . '" published successfully.';
            if ($imageError !== '') {
                $msg .= ' (Note: Image could not be saved — ' . $imageError . ')';
            }
            setFlash('success', $msg);
            redirect(baseUrl('modules/listings/view.php?id=' . $listingId));

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[ResQFood CreateListing] ' . $e->getMessage());
            $errors['_general'] = 'Could not save the listing. Please try again.';
        }
    }
}

$pageTitle = 'Post New Listing';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="breadcrumb">
    <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a>
    <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a>
    <span>Post New Listing</span>
</div>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Post a New Listing</h1>
            <p class="text-muted">Share your surplus food — someone nearby needs it today.</p>
        </div>
    </div>
</div>

<?php if (!empty($errors['_general'])): ?>
<div class="flash flash--error mb-3">
    <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <span><?= e($errors['_general']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" novalidate id="listing-form">
    <?= csrfField() ?>

    <div class="listing-form-grid">

        <!-- ── LEFT: main details ─────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.1rem">

            <!-- Section 1: Food Details -->
            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">1</span>
                        Food Details
                    </div>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label" for="title">
                            Listing title <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="title" name="title"
                               class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['title']) ?>"
                               placeholder="e.g. Fresh sourdough loaves — end of day"
                               maxlength="200" required autocomplete="off">
                        <span class="form-hint">Be specific — good titles get reserved faster.</span>
                        <?php if (isset($errors['title'])): ?>
                            <span class="form-error"><?= e($errors['title']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="category">Category</label>
                            <select id="category" name="category" class="form-control">
                                <option value="">— Select —</option>
                                <?php foreach (listingCategoryOptions() as $cat): ?>
                                    <option value="<?= e($cat) ?>" <?= $old['category'] === $cat ? 'selected' : '' ?>>
                                        <?= e($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Quantity <span class="required" aria-hidden="true">*</span>
                            </label>
                            <div class="qty-row">
                                <input type="number" name="quantity" step="0.01" min="0.01"
                                       class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['quantity']) ?>"
                                       placeholder="e.g. 12" required>
                                <select name="unit" class="form-control">
                                    <?php foreach (listingUnitOptions() as $unit): ?>
                                        <option value="<?= e($unit) ?>" <?= $old['unit'] === $unit ? 'selected' : '' ?>>
                                            <?= e($unit) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (isset($errors['quantity'])): ?>
                                <span class="form-error"><?= e($errors['quantity']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"
                                  placeholder="Describe the food — its condition, variety, packaging, anything helpful…"
                                  data-autoresize><?= e($old['description']) ?></textarea>
                        <span class="form-hint">Optional but strongly recommended — helps people decide quickly.</span>
                    </div>

                </div>
            </div>

            <!-- Section 2: Pickup Information -->
            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">2</span>
                        Pickup Information
                    </div>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label" for="pickup_address">Pickup address</label>
                        <input type="text" id="pickup_address" name="pickup_address"
                               class="form-control"
                               value="<?= e($old['pickup_address']) ?>"
                               placeholder="e.g. 12 Baker Street, rear entrance">
                        <span class="form-hint">Leave blank to use your business profile address. Only shown after reservation.</span>
                    </div>

                </div>
            </div>

        </div>

        <!-- ── RIGHT: schedule + photo + actions ──────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.1rem">

            <!-- Pickup Schedule -->
            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">3</span>
                        Pickup Schedule
                    </div>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label" for="pickup_start">
                            Pickup opens <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="datetime-local" id="pickup_start" name="pickup_start"
                               class="form-control <?= isset($errors['pickup_start']) ? 'is-invalid' : '' ?>"
                               value="<?= e(datetimeToInput($old['pickup_start'])) ?>" required>
                        <?php if (isset($errors['pickup_start'])): ?>
                            <span class="form-error"><?= e($errors['pickup_start']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pickup_end">
                            Pickup closes <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="datetime-local" id="pickup_end" name="pickup_end"
                               class="form-control <?= isset($errors['pickup_end']) ? 'is-invalid' : '' ?>"
                               value="<?= e(datetimeToInput($old['pickup_end'])) ?>" required>
                        <?php if (isset($errors['pickup_end'])): ?>
                            <span class="form-error"><?= e($errors['pickup_end']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="expiry_time">
                            Food expires at
                            <span class="text-muted" style="font-weight:400;font-size:.8em">(optional)</span>
                        </label>
                        <input type="datetime-local" id="expiry_time" name="expiry_time"
                               class="form-control <?= isset($errors['expiry_time']) ? 'is-invalid' : '' ?>"
                               value="<?= e(datetimeToInput($old['expiry_time'])) ?>">
                        <span class="form-hint">Helps recipients judge urgency.</span>
                        <?php if (isset($errors['expiry_time'])): ?>
                            <span class="form-error"><?= e($errors['expiry_time']) ?></span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Photo Upload -->
            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">4</span>
                        Photo
                        <span class="text-muted" style="font-weight:400;text-transform:none;font-size:.85em;letter-spacing:0">(optional)</span>
                    </div>
                </div>
                <div class="card-body" style="padding-bottom:1.25rem">
                    <div class="upload-zone" id="upload-zone">
                        <input type="file" name="image" id="image-input"
                               accept="image/jpeg,image/png,image/webp"
                               aria-label="Upload listing photo">
                        <img class="upload-zone__preview" id="img-preview" src="" alt="Preview">
                        <div class="upload-zone__placeholder">
                            <svg class="upload-zone__icon" viewBox="0 0 48 48" width="40" fill="none">
                                <rect x="4" y="8" width="40" height="32" rx="4" stroke="currentColor" stroke-width="2"/>
                                <circle cx="17" cy="20" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4 36l10-10 7 7 7-9 12 12" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path d="M30 12l4-4m0 0l4 4m-4-4v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="upload-zone__label">Click or drag to upload</div>
                            <div class="upload-zone__hint">JPG, PNG or WebP &middot; Max 5 MB</div>
                        </div>
                        <div class="upload-zone__change-btn" id="change-img-btn" style="display:none">
                            Change photo
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;flex-direction:column;gap:.6rem">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <svg viewBox="0 0 20 20" width="16" fill="none" style="margin-right:.4rem"><path d="M10 3v10m0 0l-3-3m3 3l3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Publish Listing
                </button>
                <a href="<?= baseUrl('modules/listings/index.php') ?>"
                   class="btn btn-outline btn-block">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
(function () {
    const zone    = document.getElementById('upload-zone');
    const input   = document.getElementById('image-input');
    const preview = document.getElementById('img-preview');
    const changeBtn = document.getElementById('change-img-btn');
    if (!zone || !input) return;

    function showPreview(file) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            zone.classList.add('has-preview');
            changeBtn.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    input.addEventListener('change', function () {
        if (this.files[0]) showPreview(this.files[0]);
    });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            showPreview(file);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
