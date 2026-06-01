<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/store.php';
require_once __DIR__ . '/lib/db.php';

$user = auth_require_login();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    die('Not found');
}

$stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$listing = $stmt->fetch();
if (!$listing) {
    http_response_code(404);
    die('Not found');
}

$isOwner = (int)$listing['seller_user_id'] === (int)$user['id'];
$isAdmin = ($user['role'] ?? '') === 'admin';
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    die('Forbidden');
}

if (is_post()) {
    csrf_verify();
    $action = (string)($_POST['action'] ?? 'update');

    if ($action === 'delete') {
        $pdo->prepare('UPDATE listings SET status = ? WHERE id = ?')->execute(['inactive', $id]);
        flash_set('success', 'Listing removed.');
        redirect('dashboard.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $location = trim((string)($_POST['location'] ?? ''));
    $cond = (string)($_POST['item_condition'] ?? 'Used (Good)');
    $desc = trim((string)($_POST['description'] ?? ''));
    $status = (string)($_POST['status'] ?? 'active');

    if ($title === '' || $price <= 0 || $categoryId <= 0 || $location === '') {
        flash_set('error', 'Please fill all required fields.');
        redirect('edit-listing.php?id=' . $id);
    }

    if (!in_array($status, ['active', 'inactive', 'sold'], true)) {
        $status = 'active';
    }

    $imagePath = (string)($listing['image_path'] ?? '');
    $imageGallery = null; // Default to not overwriting gallery

    if (!empty($_FILES['photos']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        $count = count($_FILES['photos']['name']);
        $newPrimary = null;
        $newGallery = [];
        
        for ($i = 0; $i < $count; $i++) {
            if (is_uploaded_file($_FILES['photos']['tmp_name'][$i])) {
                if ($_FILES['photos']['size'][$i] > 2_000_000) {
                    continue; // Skip large files
                }
                
                $ext = strtolower(pathinfo((string)$_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                $imgInfo = @getimagesize((string)$_FILES['photos']['tmp_name'][$i]);
                
                if (in_array($ext, $allowed, true) && $imgInfo !== false) {
                    $fname = 'listing_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = __DIR__ . '/uploads/' . $fname;
                    
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) {
                        $savedPath = 'uploads/' . $fname;
                        if ($newPrimary === null) {
                            $newPrimary = $savedPath;
                        } else {
                            $newGallery[] = $savedPath;
                        }
                    }
                }
            }
        }
        
        if ($newPrimary !== null) {
            $imagePath = $newPrimary;
            $imageGallery = count($newGallery) > 0 ? json_encode($newGallery) : null;
        }
    }

    $updateFields = 'category_id=?, title=?, description=?, price=?, item_condition=?, location=?, status=?, image_path=?';
    $params = [$categoryId, $title, $desc, $price, $cond, $location, $status, ($imagePath !== '' ? $imagePath : null)];

    if ($imageGallery !== null || isset($_FILES['photos']['name'][0]) && !empty($_FILES['photos']['name'][0])) {
        $updateFields .= ', image_gallery=?';
        $params[] = $imageGallery; // Replace gallery completely if new files uploaded
    }
    
    $params[] = $id;

    $upd = $pdo->prepare("UPDATE listings SET $updateFields WHERE id=?");
    $upd->execute($params);

    flash_set('success', 'Listing updated successfully.');
    if ($isAdmin && !$isOwner) {
        redirect('admin.php');
    } else {
        redirect('dashboard.php');
    }
}

$categories = list_categories();
$pageTitle = 'Edit Listing - Campus Market';
$err = flash_get('error');
$ok = flash_get('success');

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-end mb-4">
    <div>
      <h2 class="fw-bold mb-0">Edit Listing</h2>
      <p class="text-muted mb-0">#<?= (int)$listing['id'] ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm p-4">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">

      <div class="mb-3">
        <label class="form-label fw-bold">Title</label>
        <input type="text" class="form-control" name="title" value="<?= e($listing['title']) ?>" required>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-bold">Price</label>
          <input type="number" class="form-control" name="price" min="1" value="<?= e((string)$listing['price']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Category</label>
          <select class="form-select" name="category_id" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ((int)$listing['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Status</label>
          <select class="form-select" name="status">
            <option value="active" <?= $listing['status'] === 'active' ? 'selected' : '' ?>>active</option>
            <option value="inactive" <?= $listing['status'] === 'inactive' ? 'selected' : '' ?>>inactive</option>
            <option value="sold" <?= $listing['status'] === 'sold' ? 'selected' : '' ?>>sold</option>
          </select>
        </div>
      </div>

      <div class="mb-3 mt-3">
        <label class="form-label fw-bold">Location</label>
        <input type="text" class="form-control" name="location" value="<?= e((string)($listing['location'] ?? '')) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Condition</label>
        <select class="form-select" name="item_condition">
          <?php foreach (['New','Like New','Used (Good)','Fair'] as $c): ?>
            <option value="<?= e($c) ?>" <?= $listing['item_condition'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Description</label>
        <textarea class="form-control" rows="5" name="description"><?= e((string)($listing['description'] ?? '')) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Replace Photos (optional, selects multiple)</label>
        <input type="file" name="photos[]" multiple class="form-control" accept="image/*">
        <div class="small text-muted mt-1">Note: Uploading new photos will replace all existing photos.</div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>

    <form method="post" class="mt-3" onsubmit="return confirm('Remove this listing?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button type="submit" class="btn btn-outline-danger">Remove</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

