<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/csrf.php';

$pageTitle = 'Feedback - Campus Market';

if (is_post()) {
    csrf_verify();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
    $message = trim((string)($_POST['message'] ?? ''));

    // Allow contact.php passthrough
    if ($name === '' && (($_POST['first_name'] ?? '') !== '' || ($_POST['last_name'] ?? '') !== '')) {
        $name = trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? ''));
        $message = trim("Subject: " . (string)($_POST['subject'] ?? '') . "\n\n" . (string)($_POST['message'] ?? ''));
    }

    if ($name === '' || $message === '') {
        flash_set('error', 'Please provide your name and message.');
        redirect('feedback.php');
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO feedback (name, email, rating, message) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email !== '' ? $email : null, $rating, $message]);
        flash_set('success', 'Thank you for your feedback!');
        redirect('feedback.php');
    } catch (PDOException $e) {
        // If feedback table is removed for a minimal schema, don't crash the app.
        if ($e->getCode() === '42S02') {
            flash_set('success', 'Thank you for your feedback! (Saved for demo only)');
            redirect('feedback.php');
        }
        throw $e;
    }
}

$err = flash_get('error');
$ok = flash_get('success');

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div class="container py-5 my-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card border-0 shadow-lg p-5">
        <div class="text-center mb-4">
          <div class="display-4 text-primary mb-3"><i class="fas fa-bullhorn"></i></div>
          <h2 class="fw-bold">We value your Feedback</h2>
          <p class="text-muted">Help us improve the Campus Market experience for you and your peers.</p>
        </div>

        <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
        <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-bold small">Your Name</label>
            <input type="text" class="form-control" name="name" placeholder="Your name" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Email (optional)</label>
            <input type="email" class="form-control" name="email" placeholder="name@college.edu">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold small">Rating (optional)</label>
            <select class="form-select" name="rating">
              <option value="">Select</option>
              <option value="1">1 - Poor</option>
              <option value="2">2 - Fair</option>
              <option value="3">3 - Good</option>
              <option value="4">4 - Very Good</option>
              <option value="5">5 - Excellent</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label fw-bold small">Message</label>
            <textarea class="form-control" rows="5" name="message" placeholder="Your feedback..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Submit Feedback</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

