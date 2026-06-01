<?php
declare(strict_types=1);

$pageTitle = 'Login / Signup - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';

$tab = ($_GET['tab'] ?? 'login') === 'signup' ? 'signup' : 'login';

if (is_post()) {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (auth_login($email, $password)) {
            $user = auth_user();
            if ($user && ($user['role'] ?? '') === 'admin') {
                redirect('admin.php');
            }
            redirect('dashboard.php');
        }
        flash_set('error', 'Invalid email or password.');
        redirect('login.php');
    }

    if ($action === 'signup') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            flash_set('error', 'Please fill all fields (password min 6 characters).');
            redirect('login.php?tab=signup');
        }

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            flash_set('error', 'This email is already registered.');
            redirect('login.php?tab=signup');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            flash_set('error', 'Unable to create account. Try again.');
            redirect('login.php?tab=signup');
        }

        $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?,?,?,?,?)');
        $ins->execute([$name, $email, $hash, 'student', 'active']);

        flash_set('success', 'Account created successfully. Please login.');
        redirect('login.php');
    }
}

$err = flash_get('error');
$ok = flash_get('success');
?>

<div class="container py-5 mt-5">
  <div class="auth-card" style="max-width: 450px; margin: auto;">
    <div class="card border-0 shadow-sm p-4">

      <?php if ($err): ?>
        <div class="alert alert-danger mb-3"><?= e($err) ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert alert-success mb-3"><?= e($ok) ?></div>
      <?php endif; ?>

      <nav class="nav nav-pills nav-justified mb-4" role="tablist">
        <a class="nav-link <?= $tab === 'login' ? 'active' : '' ?>" href="login.php">Login</a>
        <a class="nav-link <?= $tab === 'signup' ? 'active' : '' ?>" href="login.php?tab=signup">Sign Up</a>
      </nav>

      <?php if ($tab === 'login'): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">
          <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" required placeholder="name@college.edu">
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Enter your password">
            <div class="text-end mt-1">
              <a href="forgot-password.php" class="small text-decoration-none">Forgot password?</a>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
        </form>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="signup">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
          </div>
          <div class="mb-3">
            <label class="form-label">College Email</label>
            <input type="email" name="email" class="form-control" placeholder="john@university.edu" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Create Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Create Account</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

