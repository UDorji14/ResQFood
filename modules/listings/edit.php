<?php
/**
 * ResQFood — Edit Listing
 * Only the listing owner (business) can edit.
 * Cannot edit collected/cancelled listings.
 */
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/listings.php';

requireRole(['business', 'admin']);

$uid       = currentUserId();
$listingId = (int) ($_GET['id'] ?? $_POST['listing_id'] ?? 0);
$listing   = getListing($listingId);

// Security: exists and owned
if (!$listing) {
    setFlash('error', 'Listing not found.');
    redirect(baseUrl('modules/listings/index.php'));
}
// Admins can edit any; businesses only their own
if (currentUserRole() === 'business' && (int) $listing['business_user_id'] !== $uid) {
    setFlash('error', 'You do not have permission to edit that listing.');
    redirect(baseUrl('modules/listings/index.php'));
}
if (in_array($listing['status'], ['collected', 'cancelled'])) {
    setFlash('error', 'Collected or cancelled listings cannot be edited.');
    redirect(baseUrl('modules/listings/view.php?id=' . $listingId));
}

$errors = [];

// How much quantity is currently tied up in active reservations?
$reservedQtyStmt = db()->prepare('
    SELECT COALESCE(SUM(reserved_quantity), 0) AS total_reserved
    FROM   reservations
    WHERE  listing_id = ? AND reservation_status = "reserved"
');
$reservedQtyStmt->execute([$listingId]);
$totalReserved = (float) $reservedQtyStmt->fetchColumn();

$old = [
    'title'          => $listing['title'],
    'category'       => $listing['category']       ?? '',
    'quantity'       => $listing['quantity'],
    'unit'           => $listing['unit'],
    'description'    => $listing['description']    ?? '',
    'pickup_address' => $listing['pickup_address'] ?? '',
    'pickup_start'   => $listing['pickup_start'],
    'pickup_end'     => $listing['pickup_end'],
    'expiry_time'    => $listing['expiry_time']    ?? '',
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

    validateRequired($data, ['title', 'quantity', 'unit', 'pickup_start', 'pickup_end'], $errors);
    validateMaxLength($data['title'], 200, 'title', $errors);
    if ($data['quantity'] !== '') validateNumeric($data['quantity'], 'quantity', $errors, 0.01);
    if ($data['pickup_start'] !== '' && $data['pickup_end'] !== '') {
        validateDateOrder($data['pickup_start'], $data['pickup_end'], 'pickup_end', $errors);
    }
    if ($data['category'] !== '') validateEnum($data['category'], listingCategoryOptions(), 'category', $errors);
    if ($data['unit'] !== '')     validateEnum($data['unit'],     listingUnitOptions(),    'unit',     $errors);

    // Quantity cannot be reduced below already-reserved amount
    $newQty = (float) $data['quantity'];
    if (empty($errors['quantity']) && $newQty < $totalReserved) {
        $errors['quantity'] = 'Cannot reduce quantity below what is already reserved ('
            . formatQty($totalReserved) . ' ' . $data['unit'] . ' reserved). Cancel reservations first.';
    }

    if (empty($errors)) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            // available_quantity = new total – already-reserved portion
            $newAvailable = max(0, $newQty - $totalReserved);
            // If all available is gone, keep listing as 'reserved'; else set available
            $newStatus = $listing['status'];
            if ($newAvailable <= 0 && $listing['status'] === 'available') {
                $newStatus = 'reserved';
            } elseif ($newAvailable > 0 && $listing['status'] === 'reserved') {
                $newStatus = 'available';
            }

            $pdo->prepare('
                UPDATE food_listings
                SET    title = ?, category = ?, quantity = ?, available_quantity = ?,
                       unit = ?, description = ?, pickup_address = ?,
                       pickup_start = ?, pickup_end = ?, expiry_time = ?,
                       status = ?, updated_at = NOW()
                WHERE  id = ?
            ')->execute([
                $data['title'],
                $data['category']       ?: null,
                $newQty,
                $newAvailable,
                $data['unit'],
                $data['description']    ?: null,
                $data['pickup_address'] ?: null,
                $data['pickup_start'],
                $data['pickup_end'],
                $data['expiry_time']    ?: null,
                $newStatus,
                $listingId,
            ]);

            // Optional new image upload
            if (!empty($_FILES['image']['name'])) {
                try {
                    uploadListingImage($_FILES['image'], $listingId, false);
                } catch (RuntimeException $e) {
                    setFlash('warning', 'Listing saved, but image could not be uploaded: ' . $e->getMessage());
                }
            }

            auditLog('listing_edit', 'id=' . $listingId, $uid);
            $pdo->commit();

            setFlash('success', 'Listing updated successfully.');
            redirect(baseUrl('modules/listings/view.php?id=' . $listingId));

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[ResQFood EditListing] ' . $e->getMessage());
            $errors['_general'] = 'Could not save changes. Please try again.';
        }
    }
}

$pageTitle = 'Edit Listing';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="breadcrumb">
    <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a>
    <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a>
    <a href="<?= baseUrl('modules/listings/view.php?id=' . $listingId) ?>"><?= e(truncate($listing['title'], 32)) ?></a>
    <span>Edit</span>
</div>

<div class="page-head">
    <div class="page-head__top">
        <div>
            <h1>Edit Listing</h1>
            <p class="text-muted">Update your surplus food post.</p>
        </div>
        <span class="status-badge status-badge--<?= statusClass($listing['status']) ?>">
            <?= statusLabel($listing['status']) ?>
        </span>
    </div>
</div>

<?php if (!empty($errors['_general'])): ?>
<div class="flash flash--error mb-3">
    <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <span><?= e($errors['_general']) ?></span>
</div>
<?php endif; ?>

<?php if ($totalReserved > 0): ?>
<div class="notice notice--warning" style="margin-bottom:1.25rem">
    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M10 3L2 17h16L10 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 9v4m0 2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <div class="notice__body">
        <strong><?= formatQty($totalReserved) ?> <?= e($listing['unit']) ?> already reserved.</strong>
        You may increase the quantity freely, but cannot reduce it below the reserved amount.
    </div>
</div>
<?php endif ?>

<form method="POST" action="" enctype="multipart/form-data" novalidate id="edit-form">
    <?= csrfField() ?>
    <input type="hidden" name="listing_id" value="<?= $listingId ?>">

    <div class="listing-form-grid">

        <!-- ── LEFT ───────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.1rem">

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
                               value="<?= e($old['title']) ?>" maxlength="200" required>
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
                            <label class="form-label">Quantity <span class="required" aria-hidden="true">*</span></label>
                            <div class="qty-row">
                                <input type="number" name="quantity" step="0.01" min="0.01"
                                       class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($old['quantity']) ?>" required>
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
                                  data-autoresize><?= e($old['description']) ?></textarea>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">2</span>
                        Pickup Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="pickup_address">Pickup address</label>
                        <input type="text" id="pickup_address" name="pickup_address"
                               class="form-control" value="<?= e($old['pickup_address']) ?>"
                               placeholder="Leave blank to use business profile address">
                        <span class="form-hint">Only shown to the person who reserves.</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── RIGHT ──────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.1rem">

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
                        <?php if (isset($errors['expiry_time'])): ?>
                            <span class="form-error"><?= e($errors['expiry_time']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Photo section -->
            <div class="card">
                <div class="card-header">
                    <div class="form-section-label">
                        <span class="form-section-label__num">4</span>
                        Photo
                    </div>
                </div>
                <div class="card-body" style="padding-bottom:1.25rem">
                    <div class="upload-zone <?= ($listing['primary_image'] ?? null) ? 'has-preview' : '' ?>" id="upload-zone">
                        <input type="file" name="image" id="image-input"
                               accept="image/jpeg,image/png,image/webp"
                               aria-label="Replace listing photo">
                        <img class="upload-zone__preview" id="img-preview"
                             src="<?= ($listing['primary_image'] ?? null) ? baseUrl(e($listing['primary_image'])) : '' ?>"
                             alt="Current photo">
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
                        <div class="upload-zone__change-btn" id="change-img-btn"
                             style="<?= ($listing['primary_image'] ?? null) ? '' : 'display:none' ?>">
                            <?= ($listing['primary_image'] ?? null) ? 'Replace photo' : 'Change photo' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;flex-direction:column;gap:.6rem">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Save Changes</button>
                <a href="<?= baseUrl('modules/listings/view.php?id=' . $listingId) ?>"
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
            changeBtn.textContent = 'Change photo';
        };
        reader.readAsDataURL(file);
    }
    input.addEventListener('change', function () { if (this.files[0]) showPreview(this.files[0]); });
    zone.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
    zone.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault(); this.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; showPreview(file); }
    });
})();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
