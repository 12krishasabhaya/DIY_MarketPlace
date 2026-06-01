<?php
declare(strict_types=1);

$pageTitle = 'Products - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
$viewerId = isset($user) && $user ? (int)$user['id'] : 0;
?>

<style>
  .filter-sidebar { position: sticky; top: 100px; height: fit-content; }
  .product-card { transition: all 0.3s ease; height: 100%; }
  .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important; }
  /* Match home page card image style: consistent crop + size */
  .product-img {
    height: clamp(200px, 18vw, 260px);
    width: 100%;
    object-fit: cover;
    object-position: center;
    background: #f8f9fa;
  }
  .wishlist-btn { position: absolute; top: 10px; left: 10px; background: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); color: #666; cursor: pointer; z-index: 5; transition: all 0.2s; border: none; }
  .wishlist-btn:hover { transform: scale(1.1); color: #dc3545; }
  .action-btns { position: absolute; bottom: 10px; right: 10px; display: flex; gap: 5px; opacity: 0; transition: all 0.3s ease; }
  .product-card:hover .action-btns { opacity: 1; }
  .action-btn { background: white; border-radius: 4px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); color: #666; cursor: pointer; border: none; font-size: 0.8rem; }
  .action-btn:hover { background: #f8f9fa; color: var(--primary); }
</style>

<div class="container py-5">
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm p-3">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
          <input type="text" id="searchBox" class="form-control border-start-0" placeholder="Search products by name or description..." oninput="applyFilters()">
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm filter-sidebar p-3">
        <h6 class="fw-bold mb-3">Categories</h6>
        <div id="categoryFilters">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="all" id="catAll" checked onchange="applyFilters()">
            <label class="form-check-label" for="catAll">All Categories</label>
          </div>
        </div>
        <hr>
        <div class="mb-4">
          <label class="form-label fw-bold">Price Range</label>
          <input type="range" class="form-range" min="0" max="5000" id="priceRange" oninput="applyFilters()">
          <div class="d-flex justify-content-between">
            <span class="small text-muted">₹0</span>
            <span class="small text-muted">₹5000+</span>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">Condition</label>
          <select class="form-select" id="conditionFilter" onchange="applyFilters()">
            <option value="all">All Conditions</option>
            <option value="new">New</option>
            <option value="like-new">Like New</option>
            <option value="used-good">Used (Good)</option>
            <option value="fair">Fair</option>
          </select>
        </div>
        <button class="btn btn-primary w-100 mt-2" onclick="applyFilters()">Apply Filters</button>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="mb-0 text-muted" id="counterText">Showing <strong>0</strong> of 0 items</p>
        <div class="d-flex align-items-center gap-2">
          <span class="text-nowrap small text-muted">Sort by:</span>
          <select class="form-select form-select-sm" style="width: 150px;" id="sortSelect" onchange="applyFilters()">
            <option value="newest">Newest</option>
            <option value="low-high">Price: Low to High</option>
            <option value="high-low">Price: High to Low</option>
          </select>
        </div>
      </div>

      <div class="row g-4" id="productGrid"></div>
    </div>
  </div>
</div>

<script>
  window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
  let allProducts = [];
  let wishlistIds = [];
  let allCategories = [];

  document.addEventListener('DOMContentLoaded', async () => {
    const pr = document.getElementById('priceRange');
    if (pr && (!pr.value || Number(pr.value) === 0)) {
      pr.value = pr.max || '5000';
    }
    await Promise.all([loadCategories(), loadProducts(), loadWishlist()]);
    applyCategoryFromUrl();
    renderProducts(allProducts);
    applyFilters();
  });

  async function loadCategories() {
    const res = await fetch('api/categories.php', { credentials: 'same-origin' });
    const data = await res.json();
    const categories = data.categories || [];
    allCategories = categories;
    const container = document.getElementById('categoryFilters');
    if (!container) return;
    const allHtml = `
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" value="all" id="catAll" checked onchange="toggleAllCats(this)">
        <label class="form-check-label" for="catAll">All Categories</label>
      </div>`;
    const catsHtml = categories.map((cat, index) => `
      <div class="form-check mb-2">
        <input class="form-check-input category-check" type="checkbox" value="${cat.name}" id="cat${index}" onchange="applyFilters()">
        <label class="form-check-label" for="cat${index}">${cat.name}</label>
      </div>
    `).join('');
    container.innerHTML = allHtml + catsHtml;
  }

  async function loadProducts() {
    const res = await fetch('api/listings.php', { credentials: 'same-origin' });
    const data = await res.json();
    allProducts = data.listings || [];
  }

  async function loadWishlist() {
    try {
      const res = await fetch('api/wishlist.php?action=list', { credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      wishlistIds = data.ids || [];
    } catch (e) {}
  }

  function applyCategoryFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const catParam = urlParams.get('category');
    if (!catParam) return;
    let targetName = catParam;
    if (/^\d+$/.test(catParam)) {
      const found = allCategories.find(c => String(c.id) === String(catParam));
      if (found && found.name) targetName = found.name;
    }
    const checkboxes = document.querySelectorAll('.category-check');
    checkboxes.forEach(check => {
      if (check.value.toLowerCase() === String(targetName).toLowerCase()) {
        check.checked = true;
        const all = document.getElementById('catAll');
        if (all) all.checked = false;
      }
    });
  }

  function toggleAllCats(allBox) {
    if (allBox.checked) {
      document.querySelectorAll('.category-check').forEach(c => c.checked = false);
    }
    applyFilters();
  }

  function updateProductCount(filtered, total) {
    const el = document.getElementById('counterText');
    if (!el) return;
    if (filtered === 0) el.innerHTML = 'Showing <strong>0</strong> of ' + total + ' items';
    else el.innerHTML = 'Showing <strong>1-' + filtered + '</strong> of ' + total + ' items';
  }

  function applyFilters() {
    let filtered = [...allProducts];
    const allChecked = document.getElementById('catAll')?.checked;
    const checkedCats = Array.from(document.querySelectorAll('.category-check:checked')).map(i => i.value.toLowerCase());
    if (!allChecked && checkedCats.length > 0) {
      filtered = filtered.filter(p => checkedCats.includes(p.category.toLowerCase()));
    }

    const searchTerm = document.getElementById('searchBox')?.value.toLowerCase() || '';
    if (searchTerm) {
      filtered = filtered.filter(p =>
        p.title.toLowerCase().includes(searchTerm) ||
        (p.description && p.description.toLowerCase().includes(searchTerm))
      );
    }

    const maxPrice = parseFloat(document.getElementById('priceRange')?.value || '5000');
    filtered = filtered.filter(p => parseFloat(p.price) <= maxPrice);

    const condition = document.getElementById('conditionFilter')?.value || 'all';
    if (condition !== 'all') {
      const map = { 'new': 'New', 'like-new': 'Like New', 'used-good': 'Used (Good)', 'fair': 'Fair' };
      filtered = filtered.filter(p => p.condition === map[condition]);
    }

    const sortBy = document.getElementById('sortSelect')?.value || 'newest';
    if (sortBy === 'low-high') filtered.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
    else if (sortBy === 'high-low') filtered.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
    else filtered.sort((a, b) => parseInt(b.id) - parseInt(a.id));

    renderProducts(filtered);
  }

  function renderProducts(products) {
    const grid = document.getElementById('productGrid');
    if (!grid) return;
    if (products.length === 0) {
      grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No products found matching your criteria.</p></div>';
      updateProductCount(0, allProducts.length);
      return;
    }
    updateProductCount(products.length, allProducts.length);
    grid.innerHTML = products.map(p => `
      <div class="col-md-4">
        <div class="card product-card border-0 shadow-sm">
          <div class="position-relative">
            <span class="badge bg-primary position-absolute m-2 top-0 end-0">${p.category}</span>
            <button class="wishlist-btn" data-product-id="${p.id}" onclick="toggleWishlist(${p.id})">
              <i class="${wishlistIds.includes(parseInt(p.id)) ? 'fas' : 'far'} fa-heart"></i>
            </button>
            <img src="${p.img}" class="card-img-top product-img" alt="${escapeHtml(p.title)}">
            <div class="action-btns">
              <button class="action-btn" onclick="openChat('${escapeJs(p.seller)}')" title="Chat with Seller"><i class="fas fa-comment-dots"></i></button>
              <button class="action-btn" onclick="openReport(${p.id}, '${escapeJs(p.title)}')" title="Report Item"><i class="fas fa-flag"></i></button>
            </div>
          </div>
          <div class="card-body">
            <h6 class="card-title fw-bold">${escapeHtml(p.title)}</h6>
            <p class="text-primary fw-bold mb-2">₹${Number(p.price).toLocaleString()}</p>
            <div class="small text-muted mb-3">Condition: ${escapeHtml(p.condition)}</div>
            <div class="d-grid gap-2 d-flex">
              <a href="product-details.php?id=${p.id}" class="btn btn-sm btn-outline-secondary w-100">View</a>
              <button class="btn btn-sm btn-primary w-100" onclick="addToCart(${p.id})">Add</button>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  }

  async function addToCart(listingId) {
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(listingId));
    const res = await fetch('api/cart.php?action=add', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    if (!res.ok) { alert('Unable to add to cart'); return; }
    window.location.href = 'checkout.php';
  }

  async function toggleWishlist(listingId) {
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(listingId));
    const res = await fetch('api/wishlist.php?action=toggle', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const data = await res.json();
    if (data.active) {
      if (!wishlistIds.includes(listingId)) wishlistIds.unshift(listingId);
    } else {
      wishlistIds = wishlistIds.filter(id => parseInt(id) !== parseInt(listingId));
    }
    applyFilters();
  }

  function openChat(seller) {
    openChatModal(seller);
  }

  function openReport(listingId, title) {
    openReportModal(listingId, title);
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  }
  function escapeJs(str) { return String(str).replace(/'/g, "\\'"); }
</script>

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
        <button class="btn btn-danger w-100" onclick="submitReportFromModal()">Submit Report</button>
      </div>
    </div>
  </div>
</div>

<script>
  let CHAT_SELLER_EMAIL = null;
  let CHAT_SELLER_USER_ID = null;
  let CHAT_LISTING_ID = null;

  async function openChatModal(sellerEmail) {
    CHAT_SELLER_EMAIL = sellerEmail;
    document.getElementById('chatSellerEmail').textContent = sellerEmail;
    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
    modal.show();
    await loadChatThread();
    setTimeout(() => document.getElementById('chatInput')?.focus(), 200);
  }

  async function loadChatThread() {
    const thread = document.getElementById('chatThread');
    if (!thread) return;
    const sellerRes = await fetch('api/user-lookup.php?email=' + encodeURIComponent(CHAT_SELLER_EMAIL), { credentials: 'same-origin' });
    if (sellerRes.status === 401) { window.location.href = 'login.php'; return; }
    if (!sellerRes.ok) { thread.innerHTML = '<div class="text-muted small">Unable to load chat.</div>'; return; }
    const seller = await sellerRes.json();
    if (!seller.user_id) { thread.innerHTML = '<div class="text-muted small">Seller not found.</div>'; return; }
    CHAT_SELLER_USER_ID = seller.user_id;

    // No listing context on products page; show general thread
    const res = await fetch(`api/messages.php?action=thread&other_user_id=${seller.user_id}`, { credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const data = await res.json();
    const msgs = data.messages || [];
    if (msgs.length === 0) { thread.innerHTML = '<div class="text-muted small">No messages yet. Say hello!</div>'; return; }
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
    if (!CHAT_SELLER_USER_ID) { await loadChatThread(); if (!CHAT_SELLER_USER_ID) return; }
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('to_user_id', String(CHAT_SELLER_USER_ID));
    form.append('body', body);
    const res = await fetch('api/messages.php?action=send', { method: 'POST', body: form, credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    input.value = '';
    await loadChatThread();
  }

  function openReportModal(listingId, title) {
    CHAT_LISTING_ID = listingId;
    document.getElementById('reportReason').value = '';
    document.getElementById('reportDetails').value = '';
    const status = document.getElementById('reportStatus');
    if (status) status.classList.add('d-none');
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    modal.show();
  }

  async function submitReportFromModal() {
    const reason = (document.getElementById('reportReason')?.value || '').trim();
    const details = (document.getElementById('reportDetails')?.value || '').trim();
    const status = document.getElementById('reportStatus');
    if (!reason) {
      if (status) { status.className = 'alert alert-warning'; status.textContent = 'Please enter a reason.'; status.classList.remove('d-none'); }
      return;
    }
    const form = new FormData();
    form.append('csrf_token', window.CSRF_TOKEN);
    form.append('listing_id', String(CHAT_LISTING_ID));
    form.append('reason', reason);
    if (details) form.append('details', details);
    const res = await fetch('api/reports.php?action=create', { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (status) { status.className = 'alert alert-danger'; status.textContent = data.error || 'Report failed'; status.classList.remove('d-none'); }
      return;
    }
    if (status) { status.className = 'alert alert-success'; status.textContent = 'Report submitted. Admin will review.'; status.classList.remove('d-none'); }
  }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

