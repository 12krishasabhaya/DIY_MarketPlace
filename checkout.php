<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/store.php';

$user = auth_require_login();
$pageTitle = 'Checkout - Campus Market';

$orderPlaced = false;
$placedOrderId = null;

if (is_post()) {
    csrf_verify();
    // Build a simple meet-up note from the frontend-style fields
    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $payment = (string)($_POST['payment_method'] ?? 'card');
    $noteParts = [];
    if ($location !== '') $noteParts[] = 'Location: ' . $location;
    $noteParts[] = 'Payment: ' . ($payment === 'cod' ? 'Cash on pickup/delivery' : 'Card');
    $note = implode(' | ', $noteParts);
    try {
        $orderId = create_order_from_cart((int)$user['id'], $note !== '' ? $note : null);
        $orderPlaced = true;
        $placedOrderId = $orderId;
    } catch (Throwable $e) {
        flash_set('error', 'Checkout failed: ' . $e->getMessage());
        redirect('checkout.php');
    }
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';

$cart = cart_get((int)$user['id']);
$items = $cart['items'];
$total = (float)$cart['total'];
$err = flash_get('error');
?>

<script>
  document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bg-light'));
</script>

<div class="container py-5">
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="row g-5">
    <div class="col-md-7">
      <div class="card border-0 shadow-sm p-4">
        <h4 class="fw-bold mb-4">Checkout</h4>

        <?php if (count($items) === 0): ?>
          <div class="text-center py-4">
            <p class="text-muted mb-3">Your cart is empty.</p>
            <a href="products.php" class="btn btn-primary px-5">Back to Marketplace</a>
          </div>
        <?php else: ?>
          <form id="checkoutForm" method="post">
            <?= csrf_field() ?>
            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label">Hostel Address / Location</label>
                <input type="text" name="location" class="form-control" placeholder="Block A, Room 302" required>
              </div>
            </div>

            <hr class="my-4">

            <h5 class="fw-bold mb-3">Payment Method</h5>
            <div class="my-3">
              <div class="form-check mb-3">
                <input id="credit" name="payment_method" value="card" type="radio" class="form-check-input" checked required onclick="togglePaymentFields('card')">
                <label class="form-check-label" for="credit">Credit Card / Debit Card</label>
              </div>
              <div class="form-check">
                <input id="cod" name="payment_method" value="cod" type="radio" class="form-check-input" required onclick="togglePaymentFields('cod')">
                <label class="form-check-label" for="cod">Cash on Pickup / Delivery</label>
              </div>
            </div>

            <div id="cardFields" class="row gy-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Name on card</label>
                <input type="text" class="form-control border-primary-subtle" placeholder="" id="cardName">
                <small class="text-muted">Full name as displayed on card</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Credit card number</label>
                <input type="text" class="form-control border-primary-subtle" placeholder="XXXX-XXXX-XXXX-XXXX" id="cardNumber">
              </div>
              <div class="col-md-3">
                <label class="form-label">Expiration</label>
                <input type="text" class="form-control border-primary-subtle" placeholder="MM/YY" id="cardExp">
              </div>
              <div class="col-md-3">
                <label class="form-label">CVV</label>
                <input type="text" class="form-control border-primary-subtle" placeholder="123" id="cardCvv">
              </div>
            </div>

            <div id="codMessage" class="alert alert-info mt-3 d-none">
              <i class="fas fa-info-circle me-2"></i> You will pay the seller directly when you pick up the item at the mutually agreed campus location.
            </div>

            <button class="w-100 btn btn-primary btn-lg mt-5" type="submit">Place Order</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card border-0 shadow-sm p-4">
        <h5 class="fw-bold mb-4">Your Order</h5>
        <ul class="list-group list-group-flush mb-3" id="checkoutList">
          <?php foreach ($items as $it): ?>
            <li class="list-group-item d-flex justify-content-between lh-sm bg-transparent px-0 border-light-subtle">
              <div>
                <h6 class="my-0 small fw-bold"><?= e($it['title']) ?></h6>
                <small class="text-muted">Qty: 1</small>
              </div>
              <span class="text-muted">₹<?= number_format((float)$it['price'], 0) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="d-flex justify-content-between p-2">
          <span>Total (INR)</span>
          <strong class="text-primary fs-5" id="checkoutTotal">₹<?= number_format($total, 0) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="orderSuccessModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow text-center p-4">
      <div class="modal-body">
        <div class="display-1 text-success mb-3"><i class="fas fa-check-circle"></i></div>
        <h3 class="fw-bold">Order Confirmed!</h3>
        <p class="text-muted">Thank you for your purchase. Order #<?= $placedOrderId ? (int)$placedOrderId : '' ?>.</p>
        <a href="index.php" class="btn btn-primary mt-4 px-5">Back to Home</a>
      </div>
    </div>
  </div>
</div>

<script>
  function togglePaymentFields(type) {
    const cardFields = document.getElementById('cardFields');
    const codMsg = document.getElementById('codMessage');
    if (!cardFields || !codMsg) return;
    if (type === 'card') { cardFields.classList.remove('d-none'); codMsg.classList.add('d-none'); }
    else { cardFields.classList.add('d-none'); codMsg.classList.remove('d-none'); }
  }
  <?php if ($orderPlaced): ?>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
      modal.show();
    });
  <?php endif; ?>
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

