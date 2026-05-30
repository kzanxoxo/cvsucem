<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (isLoggedIn()) { redirect(SITE_URL . '/admin/dashboard.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']      ?? '');
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';
    $role     = $_POST['role']      ?? 'organizer';

    if (!$name)                                          $errors[] = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 8)                           $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                          $errors[] = 'Passwords do not match.';
    if (!preg_match('/[A-Z]/', $password))               $errors[] = 'Password must contain at least one uppercase letter.';
    if (!preg_match('/[0-9]/', $password))               $errors[] = 'Password must contain at least one number.';
    $role = 'organizer';

    if (empty($errors)) {
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $email, $hash, $role]);
            $uid = $pdo->lastInsertId();
            logActivity($pdo, $uid, 'register', "New organizer application submitted: $email");
            setFlash('success', 'Your application has been submitted and is pending approval by ' . ADMIN_EMAIL . '.');
            redirect(SITE_URL . '/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizer Register | <?= e(SITE_NAME) ?></title>
  <meta name="description" content="Register as a CvSU event organizer (admin accounts are created by IT).">
  <?php include __DIR__ . '/includes/head-public.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>

<main class="auth-page" aria-label="Registration">
  <div class="auth-card fade-up" style="max-width:500px;">

    <div class="auth-logo">
      <?php $brandMarkSize = 56; include __DIR__ . '/includes/brand-mark.php'; ?>
      <h1 class="auth-title">Organizer Registration</h1>
      <p class="auth-subtitle"><?= e(SITE_SHORT) ?> staff & faculty — event organizers only</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" role="alert">
        <?php foreach ($errors as $err): ?>
          <div>• <?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="registerForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="name">Full Name <span class="required">*</span></label>
        <input type="text" name="name" id="name" class="form-control"
               placeholder="Juan Dela Cruz"
               value="<?= e($_POST['name'] ?? '') ?>" required autocomplete="name" autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
        <input type="email" name="email" id="email" class="form-control"
               placeholder="organizer@cvsu.edu.ph"
               value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>

      <input type="hidden" name="role" value="organizer">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password <span class="required">*</span></label>
          <input type="password" name="password" id="password" class="form-control"
                 placeholder="Min. 8 chars" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm">Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm" id="confirm" class="form-control"
                 placeholder="Repeat password" required autocomplete="new-password">
        </div>
      </div>

      <div id="passwordRules" style="background:rgba(255,255,255,.04);border:1px solid var(--glass-border);border-radius:var(--radius);padding:16px;margin-bottom:18px;font-size:.88rem;color:var(--text-muted);">
        <div style="font-weight:700;margin-bottom:8px;color:var(--text-primary);">Password requirements</div>
        <div id="ruleLength" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;color:#ff5252;">• <span>Minimum 8 characters</span></div>
        <div id="ruleUpper" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;color:#ff5252;">• <span>At least one uppercase letter</span></div>
        <div id="ruleNumber" style="display:flex;align-items:center;gap:8px;color:#ff5252;">• <span>At least one number</span></div>
      </div>
      <div style="background:rgba(255,255,255,.04);border:1px solid var(--glass-border);border-radius:var(--radius);padding:12px;margin-bottom:18px;font-size:.78rem;color:var(--text-muted);">
        After applying, your organizer account will remain pending approval by <?= e(ADMIN_EMAIL) ?>.
      </div>

      <div style="margin-bottom:18px;" id="strengthWrap" style="display:none;">
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px;">Password Strength: <span id="strengthLabel">—</span></div>
        <div style="height:4px;background:rgba(255,255,255,.06);border-radius:2px;">
          <div id="strengthBar" style="height:100%;border-radius:2px;transition:all .3s;width:0%;"></div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-full" id="createAccountBtn">
        Create Account
      </button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php" id="loginLink">Sign In</a>
    </div>
    <div class="auth-footer" style="margin-top:6px;">
      <a href="index.php" style="color:var(--text-muted);">← Back to Public Site</a>
    </div>
  </div>
</main>

<script>
const pwdInput      = document.getElementById('password');
const strengthBar   = document.getElementById('strengthBar');
const strengthLabel = document.getElementById('strengthLabel');
const strengthWrap  = document.getElementById('strengthWrap');
const ruleLength    = document.getElementById('ruleLength');
const ruleUpper     = document.getElementById('ruleUpper');
const ruleNumber    = document.getElementById('ruleNumber');

pwdInput.addEventListener('input', function() {
  const val = this.value;
  strengthWrap.style.display = val ? 'block' : 'none';

  const hasLength = val.length >= 8;
  const hasUpper  = /[A-Z]/.test(val);
  const hasNumber = /[0-9]/.test(val);

  ruleLength.style.color = hasLength ? '#43e97b' : '#ff5252';
  ruleUpper.style.color  = hasUpper  ? '#43e97b' : '#ff5252';
  ruleNumber.style.color = hasNumber ? '#43e97b' : '#ff5252';

  ruleLength.querySelector('span').style.textDecoration = hasLength ? 'line-through' : 'none';
  ruleUpper.querySelector('span').style.textDecoration  = hasUpper  ? 'line-through' : 'none';
  ruleNumber.querySelector('span').style.textDecoration = hasNumber ? 'line-through' : 'none';

  let score = 0;
  if (hasLength) score++;
  if (hasUpper)  score++;
  if (hasNumber) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const colors = ['#ff5252','#ff9800','#ffd60a','#43e97b'];
  const labels = ['Weak','Fair','Good','Strong'];
  const color  = colors[Math.max(0, Math.min(score - 1, colors.length - 1))];
  const label  = labels[Math.max(0, Math.min(score - 1, labels.length - 1))];

  strengthBar.style.width   = (score * 25) + '%';
  strengthBar.style.background = color;
  strengthLabel.textContent     = label;
  strengthLabel.style.color     = color;
});
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
