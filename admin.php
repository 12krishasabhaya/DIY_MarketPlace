<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/store.php';

$admin = auth_require_admin();
$pageTitle = 'Admin Panel - Campus Market';
$pdo = db();

if (is_post()) {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'ban_user') {
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id > 0 && $id !== (int)$admin['id']) {
      $row = $pdo->prepare('SELECT status, role FROM users WHERE id=?'); $row->execute([$id]); $u = $row->fetch();
      if ($u && ($u['role'] ?? '') !== 'admin') {
        $new = ($u['status'] === 'active') ? 'inactive' : 'active';
        $pdo->prepare('UPDATE users SET status=? WHERE id=?')->execute([$new, $id]);
      }
    }
    redirect('admin.php');
  }

  if ($action === 'delete_listing') {
    $id = (int)($_POST['listing_id'] ?? 0);
    if ($id > 0) $pdo->prepare('DELETE FROM listings WHERE id=?')->execute([$id]);
    redirect('admin.php');
  }

  if ($action === 'add_category') {
    $name = trim((string)($_POST['name'] ?? ''));
    $icon = trim((string)($_POST['icon'] ?? 'fas fa-tag'));
    if ($name !== '') {
      $pdo->prepare('INSERT INTO categories (name, icon) VALUES (?, ?) ON DUPLICATE KEY UPDATE icon=VALUES(icon)')->execute([$name, $icon]);
    }
    redirect('admin.php');
  }

  if ($action === 'delete_category') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id > 0) {
      $cnt = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE category_id=?'); $cnt->execute([$id]);
      if ((int)$cnt->fetchColumn() === 0) $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
      else flash_set('error', 'Cannot delete category with existing listings.');
    }
    redirect('admin.php');
  }

  if ($action === 'dismiss_report') {
    $id = (int)($_POST['report_id'] ?? 0);
    if ($id > 0) report_dismiss($id);
    redirect('admin.php');
  }

  if ($action === 'delete_review') {
    $id = (int)($_POST['review_id'] ?? 0);
    if ($id > 0) $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
    redirect('admin.php');
  }

  if ($action === 'update_order_status') {
    $ref = (string)($_POST['order_ref'] ?? '');
    $status = (string)($_POST['status'] ?? '');
    if ($ref !== '' && $status !== '') order_update_status($ref, $status);
    redirect('admin.php');
  }
}

$users = $pdo->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC')->fetchAll();
$listings = $pdo->query('
  SELECT l.id, l.title, l.price, l.status, l.image_path, c.name AS category_name, u.email AS seller_email
  FROM listings l
  JOIN categories c ON c.id = l.category_id
  JOIN users u ON u.id = l.seller_user_id
  ORDER BY l.id DESC
')->fetchAll();
$categories = $pdo->query('SELECT id, name, icon FROM categories ORDER BY id ASC')->fetchAll();
$reports = reports_list();
$orders = orders_list_all();
$reviews = reviews_list_all();

$unreadMessagesCount = count_unread_messages((int)$admin['id']);
$pendingOrdersCount = 0;
foreach ($orders as $o) {
    if ($o['status'] === 'pending') $pendingOrdersCount++;
}
$reportsCount = count($reports);

$recentReviewsCount = 0;
foreach ($reviews as $rev) {
    if (strtotime($rev['created_at']) > strtotime('-24 hours')) $recentReviewsCount++;
}

$err = flash_get('error');

require_once __DIR__ . '/partials/header.php';
?>
<style>
  :root { --primary:#0d6efd; --secondary:#6c757d; }
  .stat-card { border-bottom: 4px solid var(--primary); }
  .sidebar-link { transition: all 0.2s; border-radius: 8px; color: var(--secondary); padding: 12px 15px; display:block; text-decoration:none; }
  .sidebar-link:hover,.sidebar-link.active { background: rgba(13,110,253,0.1); color: var(--primary); font-weight:bold; }
</style>

<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-graduation-cap me-2"></i>Campus Market <span class="badge bg-primary ms-1 small" style="font-size:0.6rem;">Admin</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">Product</a></li>
        <li class="nav-item"><a class="nav-link" href="category.php">Category</a></li>
        <li class="nav-item ms-lg-3"><a href="logout.php" class="btn btn-outline-primary btn-sm px-3">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container pb-5">
  <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
  <div class="row">
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-center mb-4 pb-3 border-bottom">
          <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle fw-bold" style="width:80px;height:80px;font-size:2rem;">A</div>
          <h5 class="fw-bold mb-0"><?= e($admin['email']) ?></h5>
          <p class="text-muted small mb-0">System Administrator</p>
        </div>
        <nav class="nav flex-column gap-1">
          <a href="#" class="sidebar-link active" onclick="showSection('dashboard', this)"><i class="fas fa-th-large me-2"></i>Dashboard</a>
          <a href="#" class="sidebar-link" onclick="showSection('users', this)"><i class="fas fa-users me-2"></i>Users</a>
          <a href="#" class="sidebar-link" onclick="showSection('listings', this)"><i class="fas fa-shopping-bag me-2"></i>Listings</a>
          <a href="#" class="sidebar-link" onclick="showSection('categories', this)"><i class="fas fa-tags me-2"></i>Categories</a>
          <a href="#" class="sidebar-link" onclick="showSection('reports', this)"><i class="fas fa-flag me-2"></i>Reports</a>
          <a href="#" class="sidebar-link" onclick="showSection('reviews', this)"><i class="fas fa-star me-2"></i>Reviews</a>
          <a href="#" class="sidebar-link" onclick="showSection('messages', this)">
            <i class="fas fa-envelope me-2"></i>Messages
            <?php if ($unreadMessagesCount > 0): ?><span id="messages-badge" class="badge bg-danger rounded-pill float-end"><?= $unreadMessagesCount ?></span><?php endif; ?>
          </a>
          <a href="#" class="sidebar-link" onclick="showSection('orders', this)"><i class="fas fa-shopping-cart me-2"></i>Orders</a>
          <a href="logout.php" class="sidebar-link text-danger mt-3 border-top pt-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </nav>
      </div>
    </div>

    <div class="col-lg-9">
      <div id="dashboard-section" class="content-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="fw-bold mb-0">Dashboard Overview</h3>
          <div class="d-flex gap-2">
            <button class="btn btn-white bg-white shadow-sm btn-sm" onclick="window.print()"><i class="fas fa-download me-2"></i>Export</button>
          </div>
        </div>

        <div class="row g-4 mb-5">
          <div class="col-md-3"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1"><?= count($users) ?></h2><p class="text-muted mb-0">Total Users</p></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1"><?= count($listings) ?></h2><p class="text-muted mb-0">Total Listings</p></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1"><?= count($reports) ?></h2><p class="text-muted mb-0">Reports</p></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm p-4 stat-card bg-white h-100"><h2 class="fw-bold text-primary mb-1">₹<?= number_format((float)($pdo->query('SELECT COALESCE(SUM(price_at_purchase),0) FROM `order`')->fetchColumn() ?? 0), 0) ?></h2><p class="text-muted mb-0">Transactions</p></div></div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0">Recent Users</h6></div>
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead><tr><th class="ps-4">Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach (array_slice($users, 0, 5) as $u): ?>
                  <tr>
                    <td class="ps-4"><div class="d-flex align-items-center"><div class="avatar me-2 <?= $u['role']==='admin' ? 'bg-primary' : 'bg-secondary' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;"><?= e(substr($u['name'],0,1)) ?></div><span><?= e($u['name']) ?></span></div></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td><span class="badge <?= $u['status']==='active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> px-3"><?= e($u['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="users-section" class="content-section d-none">
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold mb-0">User Management</h3></div>
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="bg-light"><tr><th class="ps-4">Name</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td class="ps-4"><div class="d-flex align-items-center"><div class="avatar me-2 <?= $u['role']==='admin' ? 'bg-primary' : 'bg-secondary' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;"><?= e(substr($u['name'],0,1)) ?></div><span><?= e($u['name']) ?></span></div></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td><span class="badge <?= $u['status']==='active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> px-3"><?= e($u['status']) ?></span></td>
                    <td class="text-end pe-4">
                      <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="ban_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button class="btn btn-sm btn-light border-0 text-danger" type="submit" title="Toggle Active/Inactive" <?= ((int)$u['id']===(int)$admin['id'] || $u['role']==='admin') ? 'disabled' : '' ?>><i class="fas fa-ban"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="listings-section" class="content-section d-none">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="fw-bold mb-0">All Listings</h3>
          <a href="post-item.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i>Add Listing</a>
        </div>
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="bg-light"><tr><th class="ps-4">Product</th><th>Price</th><th>Seller</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead>
              <tbody>
                <?php foreach ($listings as $l): ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center">
                        <img src="<?= e($l['image_path'] ?: 'img/placeholder.png') ?>" class="rounded me-2" width="40" height="40" style="object-fit:cover;" alt="">
                        <div>
                          <div class="fw-bold"><?= e($l['title']) ?></div>
                          <div class="text-muted small"><?= e($l['category_name']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td class="fw-bold">₹<?= number_format((float)$l['price'], 0) ?></td>
                    <td><?= e($l['seller_email']) ?></td>
                    <td><span class="badge <?= $l['status']==='active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> px-3"><?= e($l['status']) ?></span></td>
                    <td class="text-end pe-4">
                      <a href="edit-listing.php?id=<?= (int)$l['id'] ?>" class="btn btn-sm btn-light border-0 text-primary me-1" title="Edit Listing"><i class="fas fa-edit"></i></a>
                      <form method="post" class="d-inline" onsubmit="return confirm('Remove this listing?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_listing">
                        <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                        <button class="btn btn-sm btn-light border-0 text-danger" type="submit" title="Delete Listing"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="categories-section" class="content-section d-none">
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold mb-0">All Categories</h3></div>
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
              <div class="table-responsive">
                <table class="table mb-0 align-middle">
                  <thead class="bg-light"><tr><th class="ps-4">Icon</th><th>Name</th><th class="text-end pe-4">Action</th></tr></thead>
                  <tbody>
                    <?php foreach ($categories as $c): ?>
                      <tr>
                        <td class="ps-4 text-primary fs-5"><i class="<?= e($c['icon']) ?>"></i></td>
                        <td class="fw-bold"><?= e($c['name']) ?></td>
                        <td class="text-end pe-4">
                          <form method="post" class="d-inline" onsubmit="return confirm('Delete category?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-sm btn-light border-0 text-danger" type="submit" title="Delete Category"><i class="fas fa-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4">
              <h5 class="fw-bold mb-3">Add New Category</h5>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_category">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Category Name</label>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Electronics">
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Icon Class (FontAwesome)</label>
                  <input type="text" name="icon" class="form-control" placeholder="fas fa-robot">
                </div>
                <button class="btn btn-primary w-100 mt-2" type="submit">Add Category</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div id="reports-section" class="content-section d-none">
        <h3 class="fw-bold mb-4">Reported Items</h3>
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="bg-light"><tr><th class="ps-4">Item</th><th>Reason</th><th>Reported By</th><th>Date</th><th class="text-end pe-4">Actions</th></tr></thead>
              <tbody>
                <?php if (count($reports) === 0): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted small">No reports.</td></tr>
                <?php else: ?>
                  <?php foreach ($reports as $r): ?>
                    <tr>
                      <td class="ps-4 fw-bold"><?= e($r['listing_title']) ?></td>
                      <td><?= e($r['reason']) ?></td>
                      <td><?= e($r['reported_by_email'] ?? 'Guest') ?></td>
                      <td><?= e($r['created_at']) ?></td>
                      <td class="text-end pe-4">
                        <form method="post" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="dismiss_report">
                          <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                          <button class="btn btn-success btn-sm me-2" type="submit"><i class="fas fa-check me-1"></i>Dismiss</button>
                        </form>
                        <a class="btn btn-danger btn-sm" href="product-details.php?id=<?= (int)$r['listing_id'] ?>"><i class="fas fa-eye me-1"></i>View</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="reviews-section" class="content-section d-none">
        <h3 class="fw-bold mb-4">All Reviews</h3>
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="bg-light"><tr><th class="ps-4">Listing</th><th>User</th><th>Rating</th><th>Comment</th><th>Date</th><th class="text-end pe-4">Actions</th></tr></thead>
              <tbody>
                <?php if (count($reviews) === 0): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted small">No reviews found.</td></tr>
                <?php else: ?>
                  <?php foreach ($reviews as $r): ?>
                    <tr>
                      <td class="ps-4 fw-bold"><a href="product-details.php?id=<?= (int)$r['listing_id'] ?>" class="text-decoration-none"><?= e($r['listing_title']) ?></a></td>
                      <td><?= e($r['user_email']) ?></td>
                      <td class="text-warning"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></td>
                      <td><div style="max-width:200px;" class="text-truncate" title="<?= e($r['comment']) ?>"><?= e($r['comment']) ?></div></td>
                      <td><?= e($r['created_at']) ?></td>
                      <td class="text-end pe-4">
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this review?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete_review">
                          <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                          <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash me-1"></i>Delete</button>
                        </form>
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
        <h3 class="fw-bold mb-4">All Orders</h3>
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="bg-light"><tr><th class="ps-4">Order Ref</th><th>Product</th><th>Buyer Email</th><th>Date</th><th>Total</th><th class="text-end pe-4">Status</th></tr></thead>
              <tbody>
                <?php if (count($orders) === 0): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted small">No orders found.</td></tr>
                <?php else: ?>
                  <?php foreach ($orders as $o): ?>
                    <tr>
                      <td class="ps-4 fw-bold">#<?= e($o['id']) ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= e($o['image_path'] ?: 'img/placeholder.png') ?>" class="rounded shadow-sm me-2" width="40" height="40" style="object-fit:cover;" alt="">
                          <div class="small fw-bold text-truncate" style="max-width:150px;" title="<?= e($o['product_titles']) ?>"><?= e($o['product_titles']) ?></div>
                        </div>
                      </td>
                      <td><?= e($o['buyer_email']) ?></td>
                      <td><?= e($o['created_at']) ?></td>
                      <td class="fw-bold">₹<?= number_format((float)$o['total_amount'], 0) ?></td>
                      <td class="text-end pe-4">
                        <form method="post" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="update_order_status">
                          <input type="hidden" name="order_ref" value="<?= e($o['id']) ?>">
                          <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="pending" <?= $o['status']==='pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $o['status']==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="cancelled" <?= $o['status']==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                          </select>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="messages-section" class="content-section d-none">
        <h3 class="fw-bold mb-4">Messages</h3>
        <div class="card border-0 shadow-sm p-5 text-center">
          <div class="text-muted">Loading messages...</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Admin Chat Modal -->
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

  function showSection(sectionId, el) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.add('d-none'));
    const target = document.getElementById(sectionId + '-section');
    if (target) target.classList.remove('d-none');
    if (el) {
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      el.classList.add('active');
    }
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
    
    let html = `<h3 class="fw-bold mb-4">Messages</h3>`;
    
    if (threads.length === 0) {
      html += `
        <div class="card border-0 shadow-sm p-5 text-center">
          <div class="display-1 text-muted mb-4"><i class="fas fa-envelope"></i></div>
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
      const mine = m.from_user_id == <?= (int)$admin['id'] ?>;
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
    renderInbox();
  }
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

