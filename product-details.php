<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/store.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $id ? get_listing($id) : null;

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Not found - Campus Market';
    require_once __DIR__ . '/partials/header.php';
    require_once __DIR__ . '/partials/navbar.php';
    echo '<div class="container py-5"><h3 class="fw-bold">Listing not found</h3><p class="text-muted">The item may have been removed.</p></div>';
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $product['title'] . ' - Campus Market';
$viewer = auth_user();
$viewerId = $viewer ? (int)$viewer['id'] : 0;
$revSummary = reviews_summary((int)$product['id']);
$revCount = (int)$revSummary['count'];
$revAvg = (float)$revSummary['avg'];
?>

<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<style>
  .product-main-img { max-height: 500px; object-fit: contain; background: #fff; }
  .seller-card { border-left: 4px solid var(--primary); }
</style>

<script>
  // Ensure same background as frontend UI
  document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bg-light'));
  window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
  window.PRODUCT_ID = <?= (int)$product['id'] ?>;
  window.SELLER_EMAIL = <?= json_encode((string)$product['seller_email']) ?>;
</script>

<div class="container py-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="products.php">Products</a></li>
      <li class="breadcrumb-item active" id="breadcrumb-title" aria-current="page"><?= e($product['title']) ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <div class="col-lg-6">
      <div id="productCarousel" class="carousel slide shadow-sm rounded-4 overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="<?= e($product['image_path'] ?: 'img/placeholder.png') ?>" class="d-block w-100 product-main-img" id="main-product-img" alt="<?= e($product['title']) ?>">
          </div>
          <?php 
          $gallery = (!empty($product['image_gallery'])) ? json_decode($product['image_gallery'], true) : [];
          if (is_array($gallery)): 
            foreach ($gallery as $gImg): ?>
              <div class="carousel-item">
                <img src="<?= e($gImg) ?>" class="d-block w-100 product-main-img" alt="<?= e($product['title']) ?>">
              </div>
          <?php 
            endforeach;
          endif; 
          ?>
        </div>
        <?php if (!empty($gallery) && is_array($gallery) && count($gallery) > 0): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="ps-lg-3">
        <h1 class="fw-bold mb-2" id="product-title"><?= e($product['title']) ?></h1>
        <div class="d-flex align-items-center mb-3">
          <div class="text-warning me-2">
            <?php
              $full = (int)floor($revAvg);
              $half = ($revAvg - $full) >= 0.5 ? 1 : 0;
              $empty = 5 - $full - $half;
              for ($i=0; $i<$full; $i++) echo '<i class="fas fa-star"></i>';
              if ($half) echo '<i class="fas fa-star-half-alt"></i>';
              for ($i=0; $i<$empty; $i++) echo '<i class="far fa-star"></i>';
            ?>
          </div>
          <span class="text-muted small">(<?= (int)$revCount ?> Reviews)</span>
        </div>

        <h2 class="text-primary fw-bold mb-4" id="product-price">₹<?= number_format((float)$product['price'], 0) ?></h2>

        <div class="mb-4">
          <h6 class="fw-bold">Condition: <span class="text-success" id="product-condition"><?= e($product['item_condition']) ?></span></h6>
          <p class="text-muted" id="product-description"><?= e((string)($product['description'] ?: 'No description provided by the seller.')) ?></p>
        </div>

        <div class="d-grid gap-3 mb-5" id="action-buttons">
          <button class="btn btn-success btn-lg" onclick="buyNow(<?= (int)$product['id'] ?>)">
            <i class="fas fa-bolt me-2"></i>Buy Now
          </button>
          <button class="btn btn-primary btn-lg" onclick="addToCart(<?= (int)$product['id'] ?>)">
            <i class="fas fa-cart-plus me-2"></i>Add to Cart
          </button>
          <div class="row g-2">
            <div class="col-6">
              <button class="btn btn-outline-secondary w-100" id="wishlistBtn" data-product-id="<?= (int)$product['id'] ?>" onclick="toggleWishlist(<?= (int)$product['id'] ?>)">
                <i class="far fa-heart me-2"></i>Wishlist
              </button>
            </div>
            <div class="col-6">
              <button class="btn btn-outline-secondary w-100" onclick="openChatModal()">
                <i class="far fa-comment-dots me-2"></i>Chat Seller
              </button>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm seller-card p-3 mb-4">
          <a href="seller.php?id=<?= (int)$product['seller_user_id'] ?>" class="text-decoration-none text-dark d-block">
            <div class="d-flex align-items-center">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm fw-bold" style="width: 50px; height: 50px; font-size: 1.5rem;">
                <?= e(strtoupper(substr((string)($product['seller_name'] ?: $product['seller_email']), 0, 1))) ?>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0 fw-bold d-flex align-items-center" id="seller-name">
                  <?= e((string)($product['seller_name'] ?: $product['seller_email'])) ?>
                  <i class="fas fa-check-circle text-primary ms-1 small" title="Verified Seller"></i>
                </h6>
                <p class="text-muted small mb-0" id="seller-role">View Profile & Listings <i class="fas fa-chevron-right ms-1" style="font-size:0.7rem;"></i></p>
              </div>
              <?php if ($viewerId): ?>
                <button class="btn btn-sm btn-outline-danger fw-bold ms-2" onclick="event.preventDefault(); openReportModal()">Report</button>
              <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline-danger fw-bold ms-2">Report</a>
              <?php endif; ?>
            </div>
          </a>
        </div>

        <div class="mt-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0">Reviews</h5>
            <?php if ($viewerId): ?>
              <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal()">Write a Review</button>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-primary" href="login.php">Login to Review</a>
            <?php endif; ?>
          </div>
          <div id="reviewsList" class="card border-0 shadow-sm p-3">
            <div class="text-muted small">Loading reviews...</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Chat with Seller</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="small text-muted mb-2">Seller: <strong id="chatSellerEmail"></strong></div>
        <div id="chatThread" class="border rounded p-3 bg-light" style="height: 260px; overflow:auto;">
          <div class="text-muted small">Loading...</div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <input type="text" id="chatInput" class="form-control" placeholder="Type a message...">
          <button class="btn btn-primary" onclick="sendChatMessage()"><i class="fas fa-paper-plane me-2"></i>Send</button>
        </div>
        <div class="small text-muted mt-2">Note: this is a simple in-app chat for the project demo.</div>
      </div>
    </div>
  </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Report Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold small">Reason</label>
          <input type="text" id="reportReason" class="form-control" placeholder="e.g. Inappropriate content">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold small">Details (optional)</label>
          <textarea id="reportDetails" class="form-control" rows="3" placeholder="Add more details..."></textarea>
        </div>
        <div id="reportStatus" class="alert d-none"></div>
        <button class="btn btn-danger w-100" onclick="submitReportModal()">Submit Report</button>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Write a Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold small">Rating</label>
          <select id="reviewRating" class="form-select">
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Very Good</option>
            <option value="3">3 - Good</option>
            <option value="2">2 - Fair</option>
            <option value="1">1 - Poor</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold small">Comment</label>
          <textarea id="reviewComment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
        </div>
        <div id="reviewStatus" class="alert d-none"></div>
        <button class="btn btn-primary w-100" onclick="submitReview()">Submit Review</button>
      </div>
    </div>
  </div>
</div>

<script>
  (async function initWishlistIcon() {
    try {
      const res = await fetch('api/wishlist.php?action=list', { credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      const ids = data.ids || [];
      const btn = document.getElementById('wishlistBtn');
      if (!btn) return;
      const id = parseInt(btn.dataset.productId || '0');
      const icon = btn.querySelector('i');
      if (icon && ids.includes(id)) icon.className = 'fas fa-heart me-2';
    } catch (e) {}
  })();

  document.addEventListener('DOMContentLoaded', () => {
    loadReviews();
  });

  async function addToCart(listingId) {
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(listingId));
    const res = await fetch('api/cart.php?action=add', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    if (!res.ok) { alert('Unable to add to cart'); return; }
    window.location.href = 'checkout.php';
  }

  async function buyNow(listingId) {
    await addToCart(listingId);
    window.location.href = 'checkout.php';
  }

  async function toggleWishlist(listingId) {
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(listingId));
    const res = await fetch('api/wishlist.php?action=toggle', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const data = await res.json();
    const btn = document.getElementById('wishlistBtn');
    const icon = btn ? btn.querySelector('i') : null;
    if (icon) icon.className = (data.active ? 'fas' : 'far') + ' fa-heart me-2';
  }

  function openChatModal() {
    document.getElementById('chatSellerEmail').textContent = window.SELLER_EMAIL;
    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
    modal.show();
    loadChatThread();
    setTimeout(() => document.getElementById('chatInput')?.focus(), 200);
  }

  async function loadChatThread() {
    const thread = document.getElementById('chatThread');
    if (!thread) return;
    const sellerRes = await fetch('api/user-lookup.php?email=' + encodeURIComponent(window.SELLER_EMAIL), { credentials: 'same-origin' });
    if (!sellerRes.ok) { thread.innerHTML = '<div class="text-muted small">Unable to load chat.</div>'; return; }
    const seller = await sellerRes.json();
    if (!seller.user_id) { thread.innerHTML = '<div class="text-muted small">Seller not found.</div>'; return; }
    window.SELLER_USER_ID = seller.user_id;

    const res = await fetch(`api/messages.php?action=thread&other_user_id=${seller.user_id}&listing_id=${window.PRODUCT_ID}`, { credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const data = await res.json();
    const msgs = data.messages || [];
    if (msgs.length === 0) {
      thread.innerHTML = '<div class="text-muted small">No messages yet. Say hello!</div>';
      return;
    }
    thread.innerHTML = msgs.map(m => {
      const mine = m.from_user_id == <?= (int)$viewerId ?>;
      return `<div class="mb-2 d-flex ${mine ? 'justify-content-end' : 'justify-content-start'}">
        <div class="px-3 py-2 rounded ${mine ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 80%;">
          <div class="small">${escapeHtml(m.body)}</div>
          <div class="text-muted small mt-1" style="font-size: 0.7rem;">${escapeHtml(m.created_at)}</div>
        </div>
      </div>`;
    }).join('');
    thread.scrollTop = thread.scrollHeight;
  }

  async function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const body = (input?.value || '').trim();
    if (!body) return;
    if (!window.SELLER_USER_ID) {
      await loadChatThread();
      if (!window.SELLER_USER_ID) return;
    }
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('to_user_id', String(window.SELLER_USER_ID));
    form.append('listing_id', String(window.PRODUCT_ID));
    form.append('body', body);
    const res = await fetch('api/messages.php?action=send', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    input.value = '';
    await loadChatThread();
  }

  function openReportModal() {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    document.getElementById('reportStatus')?.classList.add('d-none');
    modal.show();
  }

  async function submitReportModal() {
    const reason = (document.getElementById('reportReason')?.value || '').trim();
    const details = (document.getElementById('reportDetails')?.value || '').trim();
    const status = document.getElementById('reportStatus');
    if (!reason) {
      if (status) { status.className = 'alert alert-warning'; status.textContent = 'Please enter a reason.'; status.classList.remove('d-none'); }
      return;
    }
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(window.PRODUCT_ID));
    form.append('reason', reason);
    if (details) form.append('details', details);
    const res = await fetch('api/reports.php?action=create', { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (status) { status.className = 'alert alert-danger'; status.textContent = data.error || 'Report failed'; status.classList.remove('d-none'); }
      return;
    }
    if (status) { status.className = 'alert alert-success'; status.textContent = 'Report submitted. Admin will review.'; status.classList.remove('d-none'); }
    setTimeout(() => window.location.reload(), 1500);
  }

  async function loadReviews() {
    const box = document.getElementById('reviewsList');
    if (!box) return;
    const res = await fetch('api/reviews.php?action=list&listing_id=' + window.PRODUCT_ID, { credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    const reviews = data.reviews || [];
    if (reviews.length === 0) {
      box.innerHTML = '<div class="text-muted small">No reviews yet.</div>';
      return;
    }
    box.innerHTML = reviews.map(r => {
      const stars = '★★★★★'.slice(0, r.rating) + '☆☆☆☆☆'.slice(0, 5 - r.rating);
      return `<div class="border-bottom py-2">
        <div class="d-flex justify-content-between">
          <div class="fw-bold">${escapeHtml(r.user_name || 'Student')}</div>
          <div class="text-warning small">${escapeHtml(stars)}</div>
        </div>
        <div class="text-muted small">${escapeHtml(r.comment)}</div>
        <div class="text-muted small" style="font-size:0.75rem;">${escapeHtml(r.created_at)}</div>
      </div>`;
    }).join('');
  }

  function openReviewModal() {
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    const st = document.getElementById('reviewStatus');
    if (st) st.classList.add('d-none');
    modal.show();
  }

  async function submitReview() {
    const rating = parseInt(document.getElementById('reviewRating')?.value || '5');
    const comment = (document.getElementById('reviewComment')?.value || '').trim();
    const status = document.getElementById('reviewStatus');
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(window.PRODUCT_ID));
    form.append('rating', String(rating));
    form.append('comment', comment);
    const res = await fetch('api/reviews.php?action=create', { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (status) { status.className = 'alert alert-danger'; status.textContent = data.error || 'Review failed'; status.classList.remove('d-none'); }
      return;
    }
    if (status) { status.className = 'alert alert-success'; status.textContent = 'Review submitted.'; status.classList.remove('d-none'); }
    await loadReviews();
    
    // Reload page after a short delay to update aggregate header
    setTimeout(() => window.location.reload(), 1500);
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

