<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/store.php';

$user = auth_require_login();
$pageTitle = 'Post Item - Campus Market';

if (is_post()) {
    csrf_verify();
    $title = trim((string)($_POST['title'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $location = trim((string)($_POST['location'] ?? ''));
    $cond = (string)($_POST['item_condition'] ?? 'Used (Good)');
    $desc = trim((string)($_POST['description'] ?? ''));

    if ($title === '' || $price <= 0 || $categoryId <= 0 || $location === '') {
        flash_set('error', 'Please fill all required fields.');
        redirect('post-item.php');
    }

    if (empty($_FILES['photos']['name'][0])) {
        flash_set('error', 'Please upload at least one photo.');
        redirect('post-item.php');
    }

    $imagePath = null;
    $imageGallery = [];

    if (!empty($_FILES['photos']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        $count = count($_FILES['photos']['name']);
        
        for ($i = 0; $i < $count; $i++) {
            if (is_uploaded_file($_FILES['photos']['tmp_name'][$i])) {
                if ($_FILES['photos']['size'][$i] > 2_000_000) {
                    continue; // Skip files larger than 2MB
                }
                
                $ext = strtolower(pathinfo((string)$_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                $imgInfo = @getimagesize((string)$_FILES['photos']['tmp_name'][$i]);
                
                if (in_array($ext, $allowed, true) && $imgInfo !== false) {
                    $fname = 'listing_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = __DIR__ . '/uploads/' . $fname;
                    
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) {
                        $savedPath = 'uploads/' . $fname;
                        if ($imagePath === null) {
                            $imagePath = $savedPath; // First valid image becomes primary
                        } else {
                            $imageGallery[] = $savedPath; // Rest go to gallery
                        }
                    }
                }
            }
        }
    }

    $id = create_listing((int)$user['id'], [
        'category_id' => $categoryId,
        'title' => $title,
        'description' => $desc,
        'price' => $price,
        'item_condition' => $cond,
        'location' => $location,
        'image_path' => $imagePath,
        'image_gallery' => count($imageGallery) > 0 ? json_encode($imageGallery) : null,
    ]);

    flash_set('success', 'Listing published (#' . $id . ').');
    redirect('dashboard.php');
}

$categories = list_categories();
$err = flash_get('error');
$ok = flash_get('success');

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card border-0 shadow-sm p-4 p-lg-5">
        <h2 class="fw-bold mb-4">Sell an Item</h2>

        <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
        <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="mb-4">
            <label class="form-label fw-bold">Item Title</label>
            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Ultrasonic Sensor Module" required>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-bold">Price (₹)</label>
              <input type="number" name="price" class="form-control" placeholder="0.00" required min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Category</label>
              <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Room 101, Engineering Block" required>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Condition</label>
            <select name="item_condition" class="form-select">
              <option>New</option>
              <option>Like New</option>
              <option selected>Used (Good)</option>
              <option>Fair</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="5" placeholder="Tell us about the project/item..."></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Upload Photos (mandatory, multiple allowed)</label>
            <input type="file" name="photos[]" multiple class="form-control" accept="image/*" required>
            <div class="small text-muted mt-1">Accepted: jpg, png, webp, gif, avif.</div>
          </div>

          <div class="d-grid shadow-sm">
            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 px-5 transition-all">Publish Listing</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

