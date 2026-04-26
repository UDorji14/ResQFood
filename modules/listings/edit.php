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

    if (empty($errors)) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $pdo->prepare('
                UPDATE food_listings
                SET    title = ?, category = ?, quantity = ?, unit = ?,
                       description = ?, pickup_address = ?,
                       pickup_start = ?, pickup_end = ?, expiry_time = ?,
                       updated_at = NOW()
                WHERE  id = ?
            ')->execute([
                $data['title'],
                $data['category']       ?: null,
                $data['quantity'],
                $data['unit'],
                $data['description']    ?: null,
                $data['pickup_address'] ?: null,
                $data['pickup_start'],
                $data['pickup_end'],
                $data['expiry_time']    ?: null,
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

<div class="page-head">
    <div class="breadcrumb">
        <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a> /
        <a href="<?= baseUrl('modules/listings/index.php') ?>">My Listings</a> /
        <a href="<?= baseUrl('modules/listings/view.php?id=' . $listingId) ?>"><?= e(truncate($listing['title'], 30)) ?></a> /
        <span>Edit</span>
    </div>
    <h1>Edit Listing</h1>
</div>

<?php if (!empty($errors['_general'])): ?>
<div class="flash flash--error mb-3">
    <svg viewBox="0 0 20 20" width="16" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4m0 3.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <span><?= e($errors['_general']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="listing_id" value="<?= $listingId ?>">

    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">

        <div class="card">
            <div class="card-header"><h3>Listing Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label class="form-label" for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title"
                           class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                           value="<?= e($old['title']) ?>" maxlength="200" required>
                    <?php if (isset($errors['title'])): ?><span class="form-error"><?= e($errors['title']) ?></span><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">— Select category —</option>
                            <?php foreach (listingCategoryOptions() as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $old['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity <span class="required">*</span></label>
                        <div style="display:flex;gap:.5rem">
                            <input type="number" name="quantity" step="0.01" min="0.01"
                                   class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['quantity']) ?>" style="flex:1" required>
                            <select name="unit" class="form-control" style="width:120px">
                                <?php foreach (listingUnitOptions() as $unit): ?>
                                    <option value="<?= e($unit) ?>" <?= $old['unit'] === $unit ? 'selected' : '' ?>><?= e($unit) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($errors['quantity'])): ?><span class="form-error"><?= e($errors['quantity']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= e($old['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pickup_address">Pickup Address</label>
                    <input type="text" id="pickup_address" name="pickup_address"
                           class="form-control" value="<?= e($old['pickup_address']) ?>">
                </div>

            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1rem">

            <div class="card">
                <div class="card-header"><h3>Pickup Window</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="pickup_start">Starts <span class="required">*</span></label>
                        <input type="datetime-local" id="pickup_start" name="pickup_start"
                               class="form-control <?= isset($errors['pickup_start']) ? 'is-invalid' : '' ?>"
                               value="<?= e(datetimeToInput($old['pickup_start'])) ?>" required>
                        <?php if (isset($errors['pickup_start'])): ?><span class="form-error"><?= e($errors['pickup_start']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pickup_end">Ends <span class="required">*</span></label>
                        <input type="datetime-local" id="pickup_end" name="pickup_end"
                               class="form-control <?= isset($errors['pickup_end']) ? 'is-invalid' : '' ?>"
                               value="<?= e(datetimeToInput($old['pickup_end'])) ?>" required>
                        <?php if (isset($errors['pickup_end'])): ?><span class="form-error"><?= e($errors['pickup_end']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expiry_time">Food expires</label>
                        <input type="datetime-local" id="expiry_time" name="expiry_time"
                               class="form-control"
                               value="<?= e(datetimeToInput($old['expiry_time'])) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Add Photo</h3></div>
                <div class="card-body">
                    <?php if ($listing['primary_image'] ?? null): ?>
                    <img src="<?= baseUrl(e($listing['primary_image'])) ?>"
                         alt="Current photo" style="width:100%;border-radius:var(--r-md);margin-bottom:.75rem">
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <span class="form-hint">Upload a new image to replace the current one.</span>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Save Changes</button>
                <a href="<?= baseUrl('modules/listings/view.php?id=' . $listingId) ?>"
                   class="btn btn-outline btn-block mt-1">Cancel</a>
            </div>

        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
