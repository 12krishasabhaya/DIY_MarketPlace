<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/store.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ? AND status = "active" LIMIT 1');
$stmt->execute([$id]);
$seller = $stmt->fetch();

if (!$seller) {
    http_response_code(404);
    $pageTitle = 'Seller not found - Campus Market';
    require_once __DIR__ . '/partials/header.php';
    require_once __DIR__ . '/partials/navbar.php';
    echo '<div class="container py-5"><h3 class="fw-bold">Seller not found</h3><p class="text-muted">The user may have been removed or deactivated.</p></div>';
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $seller['name'] . ' - Campus Market Profile';
$viewer = auth_user();
$viewerId = $viewer ? (int)$viewer['id'] : 0;

$revSummary = seller_reviews_summary((int)$seller['id']);
$revCount = (int)$revSummary['count'];
$revAvg = (float)$revSummary['avg'];

$activeListings = list_listings(['seller_id' => $seller['id']]);

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<style>
  body { background: #f8f9fa; }
  .profile-header { background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%); color: white; padding: 3rem 0; margin-bottom: 2rem; }
  .avatar-lg { width: 100px; height: 100px; font-size: 3rem; background: white; color: var(--primary); }
  .listing-card { transition: transform 0.2s, box-shadow 0.2s; }
  .listing-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
</style>

<div class="profile-header shadow-sm">
  <div class="container text-center text-md-start">
    <div class="row align-items-center">
      <div class="col-md-auto mb-3 mb-md-0 d-flex justify-content-center">
        <div class="avatar-lg rounded-circle d-flex align-items-center justify-content-center shadow fw-bold">
          <?= e(strtoupper(substr($seller['name'], 0, 1))) ?>
        </div>
      </div>
      <div class="col-md">
        <h2 class="fw-bold mb-1"><?= e($seller['name']) ?></h2>
        <p class="mb-2 opacity-75"><i class="fas fa-envelope me-2"></i><?= e($seller['email']) ?></p>
        <div class="d-flex align-items-center justify-content-center justify-content-md-start">
          <div class="text-warning me-2 fs-5">
            <?php
              $full = (int)floor($revAvg);
              $half = ($revAvg - $full) >= 0.5 ? 1 : 0;
              $empty = 5 - $full - $half;
              for ($i=0; $i<$full; $i++) echo '<i class="fas fa-star"></i>';
              if ($half) echo '<i class="fas fa-star-half-alt"></i>';
              for ($i=0; $i<$empty; $i++) echo '<i class="far fa-star"></i>';
            ?>
          </div>
          <span class="opacity-75"><?= number_format($revAvg, 1) ?> (<?= $revCount ?> Reviews)</span>
        </div>
      </div>
      <div class="col-md-auto mt-4 mt-md-0">
        <?php if ($viewerId && $viewerId !== (int)$seller['id']): ?>
          <button class="btn btn-light btn-lg text-primary fw-bold px-4 shadow-sm" onclick="openReviewModal()"><i class="fas fa-star me-2"></i>Review Seller</button>
        <?php elseif (!$viewerId): ?>
          <a class="btn btn-light btn-lg text-primary fw-bold px-4 shadow-sm" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Login to Review</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="container pb-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <h4 class="fw-bold mb-4">Active Listings <span class="badge bg-secondary ms-2 rounded-pill fs-6"><?= count($activeListings) ?></span></h4>
      <?php if (count($activeListings) === 0): ?>
        <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
          <div class="display-1 text-light mb-3"><i class="fas fa-box-open"></i></div>
          <h5 class="text-muted">No active listings available.</h5>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($activeListings as $l): ?>
            <div class="col-md-6">
              <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden listing-card">
                <a href="product-details.php?id=<?= (int)$l['id'] ?>" class="text-decoration-none text-dark">
                  <div class="position-relative" style="height:200px; background:#fff;">
                    <img src="<?= e($l['image_path'] ?: 'img/placeholder.png') ?>" class="w-100 h-100" style="object-fit:cover;" alt="">
                    <div class="position-absolute top-0 end-0 m-2">
                       <span class="badge bg-white text-dark shadow-sm px-2 py-1"><i class="<?= e($l['category_name']==='Electronics'?'fas fa-microchip':($l['category_name']==='Lab Material'?'fas fa-flask':($l['category_name']==='Notes'?'fas fa-book':'fas fa-tag'))) ?> me-1 text-primary"></i><?= e($l['category_name']) ?></span>
                    </div>
                  </div>
                  <div class="card-body p-4">
                    <h5 class="fw-bold text-truncate mb-1"><?= e($l['title']) ?></h5>
                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= e($l['location'] ?: 'Campus') ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                      <h4 class="fw-bold text-primary mb-0">₹<?= number_format((float)$l['price'], 0) ?></h4>
                      <span class="badge <?= $l['item_condition']==='Brand New'?'bg-success':'bg-secondary' ?>"><?= e($l['item_condition']) ?></span>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 position-sticky" style="top: 100px;">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
          <h5 class="fw-bold mb-0">Seller Reviews</h5>
        </div>
        <div class="card-body p-4">
          <div id="reviewsList">
            <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Review <?= e($seller['name']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 pt-3">
        <div class="mb-3">
          <label class="form-label fw-bold small text-muted">Rating</label>
          <select id="reviewRating" class="form-select form-select-lg">
            <option value="5">⭐⭐⭐⭐⭐ - Excellent</option>
            <option value="4">⭐⭐⭐⭐ - Good</option>
            <option value="3">⭐⭐⭐ - Fair</option>
            <option value="2">⭐⭐ - Poor</option>
            <option value="1">⭐ - Terrible</option>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold small text-muted">Comments</label>
          <textarea id="reviewComment" class="form-control" rows="4" placeholder="How was your experience with this seller?"></textarea>
        </div>
        <div id="reviewStatus" class="alert d-none"></div>
        <button class="btn btn-primary btn-lg w-100 fw-bold" onclick="submitReview()">Post Review</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.SELLER_ID = <?= (int)$seller['id'] ?>;
  window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;

  document.addEventListener('DOMContentLoaded', loadReviews);

  async function loadReviews() {
    const box = document.getElementById('reviewsList');
    if (!box) return;
    try {
      const res = await fetch(`api/seller-reviews.php?action=list&seller_id=${window.SELLER_ID}`, { credentials: 'same-origin' });
      const data = await res.json();
      const reviews = data.reviews || [];
      if (reviews.length === 0) {
        box.innerHTML = '<div class="text-muted text-center py-4"><i class="far fa-comment-dots fs-1 mb-3 opacity-25 d-block"></i>No reviews yet. Be the first to rate!</div>';
        return;
      }
      box.innerHTML = reviews.map(r => {
        const stars = '★★★★★'.slice(0, r.rating) + '☆☆☆☆☆'.slice(0, 5 - r.rating);
        return `<div class="mb-4 pb-3 border-bottom">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold">${escapeHtml(r.reviewer_name)}</span>
            <span class="text-warning small">${escapeHtml(stars)}</span>
          </div>
          <div class="text-muted small mb-2">${escapeHtml(r.comment)}</div>
          <div class="text-muted opacity-50" style="font-size:0.7rem;">${escapeHtml(r.created_at)}</div>
        </div>`;
      }).join('');
    } catch(e) {
      box.innerHTML = '<div class="text-danger small">Failed to load reviews.</div>';
    }
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  }

  function openReviewModal() {
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    const st = document.getElementById('reviewStatus');
    if (st) st.classList.add('d-none');
    document.getElementById('reviewComment').value = '';
    modal.show();
  }

  async function submitReview() {
    const rating = parseInt(document.getElementById('reviewRating').value || '5');
    const comment = document.getElementById('reviewComment').value.trim();
    const status = document.getElementById('reviewStatus');
    
    if (!comment) {
        status.className = 'alert alert-warning'; 
        status.textContent = 'Please enter a comment.'; 
        status.classList.remove('d-none');
        return;
    }
    
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('seller_id', String(window.SELLER_ID));
    form.append('rating', String(rating));
    form.append('comment', comment);
    
    try {
        const res = await fetch('api/seller-reviews.php?action=create', { method: 'POST', body: form, credentials: 'same-origin' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            status.className = 'alert alert-danger'; 
            status.textContent = data.error || 'Failed to submit review.'; 
            status.classList.remove('d-none');
            return;
        }
        status.className = 'alert alert-success'; 
        status.textContent = 'Review successfully submitted!'; 
        status.classList.remove('d-none');
        
        loadReviews();
        
        // Reload page after a short delay to update aggregate header
        setTimeout(() => window.location.reload(), 1500);
    } catch(e) {
        status.className = 'alert alert-danger';
        status.textContent = 'Network error.';
        status.classList.remove('d-none');
    }
  }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
