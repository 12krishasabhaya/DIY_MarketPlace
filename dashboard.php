<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/store.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/csrf.php';

$user = auth_require_login();
$pageTitle = 'User Dashboard - Campus Market';

$pdo = db();
$myListingsStmt = $pdo->prepare('
  SELECT l.id, l.title, l.price, l.status, l.created_at, l.image_path
  FROM listings l
  WHERE l.seller_user_id = ?
  ORDER BY l.id DESC
');
$myListingsStmt->execute([(int)$user['id']]);
$myListings = $myListingsStmt->fetchAll();

$myOrders = orders_list_for_user((int)$user['id']);
$wishlistCount = count(wishlist_ids((int)$user['id']));

$unreadMessagesCount = count_unread_messages((int)$user['id']);
$pendingOrdersCount = 0;
foreach ($myOrders as $o) {
    if ($o['status'] === 'pending') $pendingOrdersCount++;
}

$ok = flash_get('success');
$err = flash_get('error');

require_once __DIR__ . '/partials/header.php';
?>

<style>
  .stat-card { border-bottom: 4px solid var(--primary); }
  .sidebar-link { transition: all 0.2s; border-radius: 8px; color: var(--secondary); padding: 12px 15px; display:block; text-decoration:none; }
  .sidebar-link:hover, .sidebar-link.active { background: rgba(13, 110, 253, 0.1); color: var(--primary); font-weight: bold; }
</style>

<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top mb-4">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="fas fa-graduation-cap me-2"></i>Campus Market</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="products.php">Product</a></li>
          <li class="nav-item"><a class="nav-link" href="category.php">Category</a></li>
          <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
          <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
          <li class="nav-item ms-lg-3"><a class="nav-link position-relative" href="cart.php"><i class="fas fa-shopping-cart fs-5"></i><span class="badge rounded-pill bg-danger cart-badge" id="cart-count">0</span></a></li>
          <li class="nav-item ms-lg-3"><a href="logout.php" class="btn btn-outline-primary btn-sm px-3">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container pb-5">
    <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-3">
        <div class="card border-0 shadow-sm p-3">
          <div class="text-center mb-4 pb-3 border-bottom">
            <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle fw-bold" style="width: 80px; height: 80px; font-size: 2rem;"><i class="fas fa-user"></i></div>
            <h5 class="fw-bold mb-0 sidebar-name"><?= e($user['name']) ?></h5>
            <p class="text-muted small mb-0">Student Member since 2026</p>
          </div>
          <nav class="nav flex-column gap-1" id="sidebar-nav">
            <a href="#" class="sidebar-link active" onclick="showSection('dashboard', this)"><i class="fas fa-th-large me-2"></i> Dashboard</a>
            <a href="#" class="sidebar-link" onclick="showSection('listings', this)"><i class="fas fa-list me-2"></i> My Listings</a>
            <a href="#" class="sidebar-link" onclick="showSection('orders', this)"><i class="fas fa-shopping-bag me-2"></i> My Orders</a>
            <a href="#" class="sidebar-link" onclick="showSection('messages', this)">
              <i class="fas fa-envelope me-2"></i> Messages
              <?php if ($unreadMessagesCount > 0): ?><span id="messages-badge" class="badge bg-danger rounded-pill float-end"><?= $unreadMessagesCount ?></span><?php endif; ?>
            </a>
            <a href="#" class="sidebar-link" onclick="showSection('favourites', this)"><i class="fas fa-heart me-2"></i> Favourites</a>
            <a href="#" class="sidebar-link" onclick="showSection('settings', this)"><i class="fas fa-cog me-2"></i> Settings</a>
            <a href="logout.php" class="sidebar-link text-danger mt-3 border-top pt-3"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
          </nav>
        </div>
      </div>

      <div class="col-lg-9">
        <div id="dashboard-section" class="content-section">
          <div class="row g-4 mb-5">
            <div class="col-md-4"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1">00</h2><p class="text-muted mb-0">Items Sold</p></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1"><?= str_pad((string)count($myOrders), 2, '0', STR_PAD_LEFT) ?></h2><p class="text-muted mb-0">Active Purchases</p></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1"><?= str_pad((string)$wishlistCount, 2, '0', STR_PAD_LEFT) ?></h2><p class="text-muted mb-0">Wishlist Count</p></div></div>
          </div>

          <h5 class="fw-bold mb-4">Recent Activity</h5>
          <div class="card border-0 shadow-sm p-0 overflow-hidden">
            <table class="table mb-0">
              <thead class="bg-light">
                <tr><th class="ps-4">Order Ref</th><th>Product</th><th>Date</th><th>Status</th><th class="pe-4 text-end">Price</th></tr>
              </thead>
              <tbody>
                <?php if (count($myOrders) === 0): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted small">No recent activity detected.</td></tr>
                <?php else: ?>
                  <?php foreach (array_slice($myOrders, 0, 5) as $o): ?>
                    <tr>
                      <td class="ps-4 fw-bold">#<?= (int)$o['id'] ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= e($o['image_path'] ?: 'img/placeholder.png') ?>" class="rounded shadow-sm me-2" width="40" height="40" style="object-fit:cover;" alt="">
                          <div class="small fw-bold text-truncate" style="max-width:120px;" title="<?= e($o['product_titles']) ?>"><?= e($o['product_titles']) ?></div>
                        </div>
                      </td>
                      <td><?= e($o['created_at']) ?></td>
                      <td><span class="badge bg-secondary"><?= e(ucfirst($o['status'])) ?></span></td>
                      <td class="pe-4 text-end fw-bold">₹<?= number_format((float)$o['total_amount'], 0) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div id="listings-section" class="content-section d-none">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">My Listings</h5>
            <a href="post-item.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i>Add New Item</a>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="table-responsive">
              <table class="table mb-0 align-middle">
                <thead class="bg-light"><tr><th class="ps-4">Product</th><th>Price</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead>
                <tbody>
                  <?php if (count($myListings) === 0): ?>
                    <tr><td colspan="4" class="text-center py-4">You have no active listings.</td></tr>
                  <?php else: ?>
                    <?php foreach ($myListings as $l): ?>
                      <tr>
                        <td class="ps-4">
                          <div class="d-flex align-items-center">
                            <img src="<?= e($l['image_path'] ?: 'img/placeholder.png') ?>" class="rounded me-2" width="40" height="40" style="object-fit: cover;" alt="">
                            <span><?= e($l['title']) ?></span>
                          </div>
                        </td>
                        <td>₹<?= number_format((float)$l['price'], 0) ?></td>
                        <td><span class="badge <?= $l['status']==='active' ? 'bg-success' : 'bg-warning' ?>"><?= e($l['status']) ?></span></td>
                        <td class="text-end pe-4">
                          <div class="btn-group">
                            <a class="btn btn-sm btn-light text-primary" href="edit-listing.php?id=<?= (int)$l['id'] ?>"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div id="orders-section" class="content-section d-none">
          <h5 class="fw-bold mb-4">My Orders</h5>
          <?php if (count($myOrders) === 0): ?>
            <div class="card border-0 shadow-sm p-5 text-center">
              <div class="display-1 text-light mb-4"><i class="fas fa-shopping-bag"></i></div>
              <h4>No Orders Yet</h4>
              <p class="text-muted">You haven't made any purchases yet.</p>
              <div class="mt-3"><a href="products.php" class="btn btn-primary px-4">Browse Marketplace</a></div>
            </div>
          <?php else: ?>
            <div class="card border-0 shadow-sm p-0 overflow-hidden">
              <table class="table mb-0">
                <thead class="bg-light"><tr><th class="ps-4">Order Ref</th><th>Product</th><th>Date</th><th>Status</th><th class="pe-4 text-end">Total</th></tr></thead>
                <tbody>
                  <?php foreach ($myOrders as $o): ?>
                    <tr>
                      <td class="ps-4 fw-bold">#<?= (int)$o['id'] ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= e($o['image_path'] ?: 'img/placeholder.png') ?>" class="rounded shadow-sm me-2" width="40" height="40" style="object-fit:cover;" alt="">
                          <div class="small fw-bold text-truncate" style="max-width:150px;" title="<?= e($o['product_titles']) ?>"><?= e($o['product_titles']) ?></div>
                        </div>
                      </td>
                      <td><?= e($o['created_at']) ?></td>
                      <td><span class="badge bg-secondary"><?= e(ucfirst($o['status'])) ?></span></td>
                      <td class="pe-4 text-end fw-bold">₹<?= number_format((float)$o['total_amount'], 0) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div id="messages-section" class="content-section d-none">
          <h5 class="fw-bold mb-4">Messages</h5>
          <div class="card border-0 shadow-sm p-5 text-center">
            <div class="text-muted">Loading messages...</div>
          </div>
        </div>

        <div id="favourites-section" class="content-section d-none">
          <h5 class="fw-bold mb-4">Favourites</h5>
          <div id="favGrid" class="row g-4"></div>
        </div>

        <div id="settings-section" class="content-section d-none">
          <h5 class="fw-bold mb-4">Profile Settings</h5>
          <div class="card border-0 shadow-sm p-4">
            <form method="post" action="profile.php">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email Address</label>
                  <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="col-12"><hr class="my-3"><h6 class="fw-bold mb-3">Change Password</h6></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Current Password</label><input type="password" class="form-control" name="current_password"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">New Password</label><input type="password" class="form-control" name="new_password"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Confirm New Password</label><input type="password" class="form-control" name="confirm_password"></div>
                <div class="col-12 mt-4 text-end"><button type="submit" class="btn btn-primary px-4">Save Changes</button></div>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

<!-- Dashboard Chat Modal -->
<div class="modal fade" id="dashboardChatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Chat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
          <img id="chatListingImg" src="" class="rounded me-2 shadow-sm" style="width:50px; height:50px; object-fit:cover;" alt="">
          <div>
            <div class="fw-bold mb-0" id="chatListingTitle"></div>
            <div class="small text-muted" id="chatOtherUser"></div>
          </div>
        </div>
        <div id="dashboardChatThread" class="border rounded p-3 bg-light" style="height: 260px; overflow:auto;">
          <div class="text-muted small">Loading...</div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <input type="text" id="dashboardChatInput" class="form-control" placeholder="Type a message...">
          <button class="btn btn-primary" onclick="sendDashboardChatMessage()"><i class="fas fa-paper-plane me-2"></i>Send</button>
        </div>
      </div>
    </div>
  </div>
</div>

  <script>
    window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    function showSection(sectionId, element) {
      document.querySelectorAll('.content-section').forEach(section => section.classList.add('d-none'));
      document.getElementById(sectionId + '-section').classList.remove('d-none');
      if (element) {
        document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
        element.classList.add('active');
      }
      if (sectionId === 'favourites') renderWishlist();
      if (sectionId === 'messages') {
        const badge = document.getElementById('messages-badge');
        if (badge) badge.classList.add('d-none');
        renderInbox();
      }
    }
    
    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }
    
    async function renderInbox() {
      const container = document.getElementById('messages-section');
      if (!container) return;
      
      const res = await fetch('api/messages.php?action=inbox', { credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      const threads = data.inbox || [];
      
      let html = `<h5 class="fw-bold mb-4">Messages</h5>`;
      
      if (threads.length === 0) {
        html += `
          <div class="card border-0 shadow-sm p-5 text-center">
            <div class="display-1 text-light mb-4"><i class="fas fa-envelope"></i></div>
            <h4>Inbox Empty</h4>
            <p class="text-muted">No messages yet. Chat with sellers or buyers to start a conversation.</p>
          </div>
        `;
      } else {
        html += `<div class="card border-0 shadow-sm"><div class="list-group list-group-flush">`;
        threads.forEach(t => {
          const img = t.listing_image || 'img/placeholder.png';
          html += `
            <button class="list-group-item list-group-item-action p-4 border-bottom text-start" onclick="openDashboardChat(${t.other_user_id}, ${t.listing_id || 'null'}, '${escapeHtml(t.other_user_name)}', '${escapeHtml(t.listing_title || 'General Inquiry')}', '${escapeHtml(img)}')">
              <div class="d-flex w-100 justify-content-between mb-2">
                <div class="d-flex align-items-center">
                  <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">${escapeHtml(t.other_user_name).charAt(0)}</div>
                  <div>
                    <h6 class="mb-0 fw-bold">${escapeHtml(t.other_user_name)}</h6>
                    <small class="text-muted"><i class="fas fa-box me-1"></i>${escapeHtml(t.listing_title || 'General Inquiry')}</small>
                  </div>
                </div>
                <small class="text-muted">${escapeHtml(t.created_at)}</small>
              </div>
              <p class="mb-0 text-muted small text-truncate ps-5 ms-2">${escapeHtml(t.body)}</p>
            </button>
          `;
        });
        html += `</div></div>`;
      }
      container.innerHTML = html;
    }
    
    let currentChat = { otherUserId: null, listingId: null };
    
    function openDashboardChat(otherUserId, listingId, otherUserName, listingTitle, listingImg) {
      currentChat.otherUserId = otherUserId;
      currentChat.listingId = listingId;
      
      document.getElementById('chatOtherUser').textContent = otherUserName;
      document.getElementById('chatListingTitle').textContent = listingTitle;
      document.getElementById('chatListingImg').src = listingImg;
      document.getElementById('dashboardChatInput').value = '';
      
      const modal = new bootstrap.Modal(document.getElementById('dashboardChatModal'));
      modal.show();
      
      loadDashboardChatThread();
      setTimeout(() => document.getElementById('dashboardChatInput').focus(), 200);
    }
    
    async function loadDashboardChatThread() {
      const thread = document.getElementById('dashboardChatThread');
      if (!thread || !currentChat.otherUserId) return;
      
      const lId = currentChat.listingId && currentChat.listingId !== 'null' ? currentChat.listingId : '';
      const res = await fetch(`api/messages.php?action=thread&other_user_id=${currentChat.otherUserId}&listing_id=${lId}`, { credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      const msgs = data.messages || [];
      
      if (msgs.length === 0) {
        thread.innerHTML = '<div class="text-muted small">No messages yet.</div>';
        return;
      }
      
      thread.innerHTML = msgs.map(m => {
        const mine = m.from_user_id == <?= (int)$user['id'] ?>;
        return `<div class="mb-2 d-flex ${mine ? 'justify-content-end' : 'justify-content-start'}">
          <div class="px-3 py-2 rounded ${mine ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 80%;">
            <div class="small">${escapeHtml(m.body)}</div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">${escapeHtml(m.created_at)}</div>
          </div>
        </div>`;
      }).join('');
      thread.scrollTop = thread.scrollHeight;
    }
    
    async function sendDashboardChatMessage() {
      const input = document.getElementById('dashboardChatInput');
      const body = (input.value || '').trim();
      if (!body || !currentChat.otherUserId) return;
      
      const form = new FormData();
      form.append('csrf_token', window.CSRF_TOKEN);
      form.append('to_user_id', String(currentChat.otherUserId));
      if (currentChat.listingId && currentChat.listingId !== 'null') form.append('listing_id', String(currentChat.listingId));
      form.append('body', body);
      
      const res = await fetch('api/messages.php?action=send', { method: 'POST', body: form, credentials: 'same-origin' });
      if (!res.ok) return;
      input.value = '';
      await loadDashboardChatThread();
      renderInbox(); // refresh inbox in background to show latest message
    }
    
    async function renderWishlist() {
      const container = document.getElementById('favGrid');
      if (!container) return;
      const res = await fetch('api/wishlist-items.php', { credentials: 'same-origin' });
      if (!res.ok) { container.innerHTML = ''; return; }
      const data = await res.json();
      const items = data.items || [];
      if (items.length === 0) {
        container.innerHTML = `
          <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center">
              <div class="display-1 text-light mb-4"><i class="fas fa-heart text-danger opacity-25"></i></div>
              <h4>Your Wishlist is Empty</h4>
              <p class="text-muted">Save items you like to see them here.</p>
              <div class="mt-3"><a href="products.php" class="btn btn-outline-primary px-4">Explore Items</a></div>
            </div>
          </div>`;
        return;
      }
      container.innerHTML = items.map(item => `
        <div class="col-md-6 col-xl-4">
          <div class="card border-0 shadow-sm h-100">
            <img src="${item.img}" class="card-img-top" height="150" style="object-fit: cover;" alt="">
            <div class="card-body">
              <h6 class="fw-bold mb-1">${item.title}</h6>
              <p class="text-primary fw-bold mb-2">₹${Number(item.price).toLocaleString()}</p>
              <div class="d-grid gap-2 d-flex">
                <a href="product-details.php?id=${item.id}" class="btn btn-sm btn-outline-secondary w-100">View</a>
                <button class="btn btn-sm btn-danger w-100" onclick="removeFav(${item.id})">Remove</button>
              </div>
            </div>
          </div>
        </div>`).join('');
    }

    async function removeFav(id) {
      const fd = new FormData();
      fd.append('csrf_token', window.CSRF_TOKEN);
      fd.append('listing_id', String(id));
      await fetch('api/wishlist.php?action=toggle', { method: 'POST', body: fd, credentials: 'same-origin' });
      renderWishlist();
    }
  </script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

