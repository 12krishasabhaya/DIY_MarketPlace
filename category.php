<?php
declare(strict_types=1);

$pageTitle = 'Categories - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/lib/store.php';

$categories = list_categories();
?>

<section class="py-5 bg-light border-bottom">
  <div class="container">
    <h1 class="fw-bold">Categories</h1>
    <p class="text-muted mb-0">Browse all available categories.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4" id="categoryGrid">
      <?php if (count($categories) === 0): ?>
        <div class="col-12 text-center py-5"><p class="text-muted">No categories available.</p></div>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
          <div class="col-md-4">
            <div class="card category-card h-100 shadow-sm border-0 text-center p-4">
              <div class="category-icon-wrapper mb-4 mx-auto d-flex align-items-center justify-content-center bg-primary-subtle rounded-circle" style="width: 100px; height: 100px;">
                <i class="<?= e($cat['icon']) ?> fa-3x text-primary"></i>
              </div>
              <div class="card-body p-0">
                <h5 class="card-title fw-bold"><?= e($cat['name']) ?></h5>
                <a href="products.php?category=<?= urlencode((string)$cat['id']) ?>" class="btn btn-primary px-4 mt-3">Browse <?= e($cat['name']) ?></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

