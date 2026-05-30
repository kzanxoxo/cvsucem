<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!$password) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['is_active'] == 0) {
            $errors[] = 'Your organizer application is pending approval by ' . ADMIN_EMAIL . '.';
        } elseif ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];
            session_regenerate_id(true);

            logActivity($pdo, $user['id'], 'login', 'User logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            redirect(SITE_URL . '/admin/dashboard.php');
        } else {
            $errors[] = 'Invalid email or password. Please try again.';
        }
    }
}

$flash      = getFlash();
$pageTitle  = 'Admin Login';
$activePage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | <?= e(SITE_NAME) ?></title>
  <meta name="description" content="Admin and Organizer login for <?= e(UNIVERSITY_NAME) ?> event management.">
  <?php include __DIR__ . '/includes/head-public.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>

<main class="auth-page" aria-label="Admin login">
  <div class="auth-card fade-up">

    <div class="auth-logo">
      <?php $brandMarkSize = 56; include __DIR__ . '/includes/brand-mark.php'; ?>
      <h1 class="auth-title">CvSU Organizer Login</h1>
      <p class="auth-subtitle"><?= e(UNIVERSITY_NAME) ?> — Admin Panel</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" role="alert">
        <?php foreach ($errors as $err): ?>
          <div>• <?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" class="form-control"
               placeholder="admin@cvsu.edu.ph"
               value="<?= e($_POST['email'] ?? '') ?>"
               required autocomplete="email" autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div style="position:relative;">
          <input type="password" name="password" id="password" class="form-control"
                 placeholder="••••••••" required autocomplete="current-password"
                 style="padding-right:44px;">
          <button type="button" id="togglePwd" class="password-toggle"
                  aria-label="Toggle password visibility"><?= icon('eye', 20) ?></button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-full icon-inline" id="loginBtn" style="margin-top:4px;">
        <?= icon('lock', 18) ?> Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php" id="registerLink">Register as Organizer</a>
    </div>
    <div class="auth-footer" style="margin-top:6px;">
      <a href="index.php" style="color:var(--text-muted);">← Back to Public Site</a>
    </div>
  </div>
</main>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
  const pwd = document.getElementById('password');
  const show = pwd.type === 'password';
  pwd.type = show ? 'text' : 'password';
  this.innerHTML = show ? <?= json_encode(icon('eye-off', 20)) ?> : <?= json_encode(icon('eye', 20)) ?>;
});
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
