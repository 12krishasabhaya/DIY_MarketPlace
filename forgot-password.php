<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';

// Ensure session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'Forgot Password - Campus Market';

if (is_post()) {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'request_reset') {
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '') {
            flash_set('error', 'Please enter your email address.');
            redirect('forgot-password.php');
        }

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn()) {
            // Generate a secure reset token
            $token = bin2hex(random_bytes(16));
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;

            // Simulate email delivery for demo purposes
            $resetLink = 'forgot-password.php?token=' . urlencode($token);
            flash_set('success', 'Email simulation: A password reset request was made. <a href="' . $resetLink . '" class="fw-bold text-decoration-underline text-success">Click here to reset your password.</a>');
        } else {
            // Security best practice: Do not reveal if the email exists. Show same success.
            flash_set('success', 'If an account exists with that email, a reset link has been sent.');
        }
        redirect('forgot-password.php');
    }

    if ($action === 'reset_password') {
        $token = (string)($_POST['token'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // Verify session token
        $sessionToken = $_SESSION['reset_token'] ?? '';
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            flash_set('error', 'Invalid or expired password reset token.');
            redirect('forgot-password.php');
        }

        // Verify input
        if (strlen($newPassword) < 6) {
            flash_set('error', 'Password must be at least 6 characters.');
            redirect('forgot-password.php?token=' . urlencode($token));
        }
        if ($newPassword !== $confirmPassword) {
            flash_set('error', 'Passwords do not match.');
            redirect('forgot-password.php?token=' . urlencode($token));
        }

        // Hash and update
        $emailToReset = $_SESSION['reset_email'] ?? '';
        if ($emailToReset) {
            $pdo = db();
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
            $stmt->execute([$hash, $emailToReset]);

            // Clear session tokens
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_email']);

            flash_set('success', 'Your password has been updated successfully! Please log in with your new password.');
            redirect('login.php');
        } else {
            flash_set('error', 'Unable to reset password. Session email missing.');
            redirect('forgot-password.php');
        }
    }
}

// Logic to determine which view to show
$showResetForm = false;
$tokenParam = (string)($_GET['token'] ?? '');
if ($tokenParam !== '') {
    $sessionToken = $_SESSION['reset_token'] ?? '';
    if ($sessionToken !== '' && hash_equals($sessionToken, $tokenParam)) {
        $showResetForm = true;
    } else {
        flash_set('error', 'The reset link is invalid or has expired.');
        redirect('forgot-password.php');
    }
}

$err = flash_get('error');
$ok = flash_get('success');

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm p-4">
        
        <?php if ($err): ?>
          <div class="alert alert-danger mb-4"><?= e($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
          <div class="alert alert-success mb-4"><?= $ok // Note: Allow HTML for the mock email link ?></div>
        <?php endif; ?>

        <?php if ($showResetForm): ?>
          <h3 class="fw-bold mb-3">Create New Password</h3>
          <p class="text-muted small mb-4">Enter your new password below. It must be at least 6 characters long.</p>
          
          <form method="post" class="needs-validation">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="token" value="<?= e($tokenParam) ?>">
            
            <div class="mb-3">
              <label class="form-label fw-bold small">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Enter new password">
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold small">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Confirm new password">
            </div>
            
            <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">Reset Password</button>
          </form>

        <?php else: ?>
          <h3 class="fw-bold mb-3">Forgot Password</h3>
          <p class="text-muted small mb-4">Enter the email address associated with your account. We will simulate sending a password reset link.</p>
          
          <form method="post" class="needs-validation">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_reset">
            
            <div class="mb-4">
              <label class="form-label fw-bold small">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="name@college.edu" required>
            </div>
            
            <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">Send Reset Link</button>
            
            <div class="text-center mt-3">
              <a href="login.php" class="text-decoration-none small"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
            </div>
          </form>

        <?php endif; ?>
        
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
