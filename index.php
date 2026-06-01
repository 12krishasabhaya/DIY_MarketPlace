<?php
declare(strict_types=1);

$pageTitle = 'Campus Market - Home';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/lib/store.php';

$categories = list_categories();
$popularCategories = array_slice($categories, 0, 3);
$featured = array_slice(list_listings(['sort' => 'newest']), 0, 3);
?>

<section class="hero-section">
  <div class="container">
    <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInDown">Buy, Sell & Trade DIY Projects</h1>
    <p class="lead mb-5 animate__animated animate__fadeInUp">The ultimate marketplace for students to trade electronics components, lab equipment, dorm essentials, and project materials within campus.</p>
    <div class="d-flex justify-content-center gap-3">
      <a href="products.php" class="btn btn-light btn-lg px-5 fw-bold">Explore Marketplace</a>
      <a href="post-item.php" class="btn btn-outline-light btn-lg px-5">Post a Project</a>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="fw-bold">Popular Categories</h2>
        <p class="text-muted mb-0">Browse through our most traded segments.</p>
      </div>
      <a href="category.php" class="text-primary fw-bold text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div id="categoryGrid" class="row g-4">
      <?php if (count($popularCategories) === 0): ?>
        <div class="col-12 text-center py-5"><p class="text-muted">No categories available.</p></div>
      <?php else: ?>
        <?php foreach ($popularCategories as $cat): ?>
          <div class="col-md-4">
            <div class="card category-card h-100 shadow-sm border-0 text-center p-4 transition-transform">
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

<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="fw-bold mb-5">Why Campus Market?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="p-4 bg-white rounded shadow-sm h-100 border-0">
          <div class="display-5 text-primary mb-3"><i class="fas fa-shield-alt"></i></div>
          <h5 class="fw-bold">Safe & Secure</h5>
          <p class="text-muted small">Verified student profiles and campus-only exchanges ensure a safe environment.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white rounded shadow-sm h-100 border-0">
          <div class="display-5 text-success mb-3"><i class="fas fa-piggy-bank"></i></div>
          <h5 class="fw-bold">Student Friendly Prices</h5>
          <p class="text-muted small">Save money on textbooks and materials by buying directly from seniors.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white rounded shadow-sm h-100 border-0">
          <div class="display-5 text-info mb-3"><i class="fas fa-users-cog"></i></div>
          <h5 class="fw-bold">Community Driven</h5>
          <p class="text-muted small">Ask for help, share notes, and collaborate on projects with ease.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="fw-bold">Latest Listings</h2>
        <p class="text-muted mb-0">Fresh posts from the community.</p>
      </div>
      <a href="products.php" class="text-primary fw-bold text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4">
      <?php if (count($featured) === 0): ?>
        <div class="col-12 text-center py-5"><p class="text-muted">No listings available.</p></div>
      <?php else: ?>
        <?php foreach ($featured as $p): ?>
          <div class="col-md-4">
            <div class="card product-card border-0 shadow-sm">
              <div class="position-relative">
                <span class="badge bg-primary position-absolute m-2 top-0 end-0"><?= e($p['category_name']) ?></span>
                <img src="<?= e($p['image_path'] ?: 'img/placeholder.png') ?>" class="card-img-top product-img" alt="<?= e($p['title']) ?>">
              </div>
              <div class="card-body">
                <h6 class="card-title fw-bold"><?= e($p['title']) ?></h6>
                <p class="text-primary fw-bold mb-2">₹<?= number_format((float)$p['price'], 0) ?></p>
                <div class="small text-muted mb-3">Condition: <?= e($p['item_condition']) ?></div>
                <div class="d-grid gap-2 d-flex">
                  <a href="product-details.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary w-100">View</a>
                  <form method="post" action="cart.php" class="w-100">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="listing_id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Add</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

