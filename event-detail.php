<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/events.php'); }

$event = getEvent($pdo, $id);
if (!$event) {
    setFlash('error', 'Event not found.');
    redirect(SITE_URL . '/events.php');
}

syncEventStatuses($pdo);
$event = getEvent($pdo, $id); 

$pdo->prepare("UPDATE events SET views = views + 1 WHERE id = ?")->execute([$id]);

$count  = getParticipantCount($pdo, $id);
$max    = (int)$event['max_participants'];
$pct    = ($max > 0) ? min(100, round(($count / $max) * 100)) : 0;
$isFull = ($max > 0 && $count >= $max);
$canRegister = !$isFull && !in_array($event['status'], ['cancelled','completed']);

$errors  = [];
$success = false;
$regToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && $canRegister) {
    $name      = trim($_POST['student_name']  ?? '');
    $email     = strtolower(trim($_POST['student_email'] ?? ''));
    $studentId = trim($_POST['student_id']    ?? '');
    $course    = trim($_POST['course']        ?? '');
    $yearLevel = trim($_POST['year_level']    ?? '');

    
    if (!$name)      $errors[] = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!$studentId) $errors[] = 'Student ID is required.';

    if (empty($errors)) {
        if (isAlreadyRegistered($pdo, $id, $email)) {
            $errors[] = 'This email is already registered for this event.';
        } else {
            
            $freshCount = getParticipantCount($pdo, $id);
            if ($max > 0 && $freshCount >= $max) {
                $errors[] = 'Sorry, the event is now full.';
            } else {
                $token = generateToken(16);
                $stmt  = $pdo->prepare("
                    INSERT INTO participants (event_id, student_name, student_email, student_id, course, year_level, registration_token)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id, $name, $email, $studentId, $course, $yearLevel, $token]);
                logActivity($pdo, null, 'student_registered', "$name registered for event #$id");
                $success  = true;
                $regToken = $token;
                $count    = getParticipantCount($pdo, $id);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $cancelToken = trim($_POST['reg_token'] ?? '');
    if ($cancelToken) {
        $stmt = $pdo->prepare("DELETE FROM participants WHERE registration_token = ? AND event_id = ?");
        $stmt->execute([$cancelToken, $id]);
        if ($stmt->rowCount()) {
            setFlash('success', 'Your registration has been cancelled successfully.');
        } else {
            setFlash('error', 'Registration token not found for this event.');
        }
        redirect(SITE_URL . "/event-detail.php?id=$id");
    }
}

$flash = getFlash();
$checkinUrl = SITE_URL . '/checkin.php?token=' . $event['qr_token'];

$pageTitle  = e($event['title']);
$pageDesc   = substr(strip_tags($event['description'] ?? ''), 0, 160);
$activePage = 'events';

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep">›</span>
      <a href="events.php">Events</a>
      <span class="sep">›</span>
      <span><?= e($event['title']) ?></span>
    </nav>
  </div>
</div>

<section class="section" style="padding-top:0;" aria-label="Event detail">
  <div class="container">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:32px;align-items:start;" class="detail-grid">

      
      <div>
        <?php if ($event['image']): ?>
          <img src="<?= UPLOAD_URL . e($event['image']) ?>" alt="<?= e($event['title']) ?>"
               style="width:100%;height:320px;object-fit:cover;border-radius:var(--radius-lg);border:1px solid var(--glass-border);margin-bottom:28px;">
        <?php else: ?>
          <div style="width:100%;height:220px;background:linear-gradient(135deg,<?= e($event['category_color'] ?? '#667eea') ?>22,<?= e($event['category_color'] ?? '#764ba2') ?>11);border-radius:var(--radius-lg);border:1px solid var(--glass-border);display:flex;align-items:center;justify-content:center;margin-bottom:28px;">
            <?= categoryHeroMark(['name' => $event['category_name'] ?? 'Event', 'color' => $event['category_color'] ?? '#1a9e5c']) ?>
          </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
          <?= statusBadge($event['status']) ?>
          <?php if ($event['category_name']): ?>
            <span class="badge badge-category"><?= e($event['category_name']) ?></span>
          <?php endif; ?>
          <?php if ($event['is_featured']): ?>
            <?= featuredBadge() ?>
          <?php endif; ?>
        </div>

        <h1 style="font-size:clamp(1.6rem,3vw,2.4rem);margin-bottom:20px;"><?= e($event['title']) ?></h1>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:28px;">
          <?php
            $details = [
              ['calendar', 'Date', formatDate($event['event_date'])],
              ['clock', 'Time', formatTime($event['start_time']) . ($event['end_time'] ? ' – ' . formatTime($event['end_time']) : '')],
              ['map-pin', 'Location', $event['location']],
              ['user', 'Organizer', $event['organizer_name']],
            ];
            foreach ($details as [$iconName, $label, $val]):
          ?>
            <div style="padding:14px;background:var(--bg-card);border:1px solid var(--glass-border);border-radius:var(--radius);">
              <div class="detail-meta-label"><?= icon($iconName, 14) ?> <?= $label ?></div>
              <div style="font-size:.92rem;font-weight:600;color:var(--text-primary);"><?= e($val) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($event['description']): ?>
          <div class="card card-body" style="margin-bottom:28px;">
            <h2 style="font-size:1rem;margin-bottom:12px;">About This Event</h2>
            <div style="font-size:.9rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap;"><?= nl2br(e($event['description'])) ?></div>
          </div>
        <?php endif; ?>

        
        <div class="card card-body" style="margin-bottom:28px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="font-size:1rem;" class="icon-inline"><?= icon('users', 18) ?> Participants</h2>
            <span style="font-size:.9rem;font-weight:700;color:var(--text-primary);">
              <?= $count ?><?= $max > 0 ? " / $max" : '' ?>
            </span>
          </div>
          <?php if ($max > 0): ?>
            <div class="capacity-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Capacity">
              <div class="capacity-bar-fill" data-fill="<?= $pct ?>" style="width:0%;height:8px;border-radius:4px;"></div>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:6px;">
              <?= $max - $count ?> spots remaining
            </div>
          <?php else: ?>
            <p style="font-size:.85rem;">Unlimited spots available</p>
          <?php endif; ?>
        </div>

        
        <div class="card card-body" style="margin-bottom:28px;">
          <h2 style="font-size:1rem;margin-bottom:12px;" class="icon-inline"><?= icon('ban', 18) ?> Cancel Participation</h2>
          <p style="font-size:.85rem;margin-bottom:14px;">If you registered but can no longer attend, enter your registration token to cancel.</p>
          <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;" id="cancelForm">
            <input type="text" name="reg_token" class="form-control" placeholder="Your registration token"
                   style="flex:1;min-width:200px;" required id="cancelTokenInput" aria-label="Registration token">
            <button type="submit" name="cancel" class="btn btn-danger btn-sm" id="cancelBtn"
                    onclick="return confirm('Cancel your registration? This cannot be undone.')">
              Cancel Registration
            </button>
          </form>
        </div>
      </div>

      
      <div id="register" style="position:sticky;top:88px;">

        <?php if ($flash): ?>
          <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="alert">
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>

        <?php if ($success && $regToken): ?>
          <div class="card card-body" style="text-align:center;border-color:rgba(67,233,123,.3);background:rgba(67,233,123,.04);">
            <?= stateIcon('check-circle', 'success') ?>
            <h2 style="margin-bottom:8px;color:var(--success);">You're Registered!</h2>
            <p style="font-size:.88rem;margin-bottom:16px;">Save your token — you'll need it for check-in and to cancel your registration.</p>
            <div style="background:rgba(255,255,255,.05);border:1px solid var(--glass-border);border-radius:var(--radius);padding:12px;font-family:monospace;font-size:.9rem;letter-spacing:.05em;word-break:break-all;margin-bottom:16px;color:var(--success);">
              <?= e($regToken) ?>
            </div>
            <button type="button" class="btn btn-sm btn-secondary w-full icon-inline" data-copy="<?= e($regToken) ?>" id="copyTokenBtn"><?= icon('copy', 16) ?> Copy Token</button>
            <hr style="border-color:var(--glass-border);margin:16px 0;">
            <p style="font-size:.8rem;color:var(--text-muted);">Scan the QR code at the event venue to mark your attendance.</p>
          </div>

        <?php elseif ($event['status'] === 'cancelled'): ?>
          <div class="card card-body text-center">
            <?= stateIcon('x-circle', 'error') ?>
            <h2>Event Cancelled</h2>
            <p>This event has been cancelled by the organizer.</p>
          </div>

        <?php elseif ($event['status'] === 'completed'): ?>
          <div class="card card-body text-center">
            <?= stateIcon('check-circle', 'success') ?>
            <h2>Event Completed</h2>
            <p>This event has already taken place. Check out upcoming events!</p>
            <a href="events.php" class="btn btn-primary mt-4 w-full" id="moreEventsBtn">Browse Events</a>
          </div>

        <?php elseif ($isFull): ?>
          <div class="card card-body text-center">
            <?= stateIcon('users', 'warning') ?>
            <h2>Event Full</h2>
            <p>All spots have been taken. Check back if cancellations occur.</p>
          </div>

        <?php else: ?>
          
          <div class="card card-body">
            <h2 style="font-size:1.2rem;margin-bottom:4px;" class="icon-inline"><?= icon('edit', 18) ?> Register for Event</h2>
            <p style="font-size:.82rem;margin-bottom:20px;">No account needed — just fill in your details.</p>

            <?php if (!empty($errors)): ?>
              <div id="formError" class="alert alert-error" role="alert">
                <div>
                  <?php foreach ($errors as $err): ?>
                    <div>• <?= e($err) ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <div id="formError" class="alert alert-error" style="display:none;" role="alert">Please fix the errors above.</div>
            <?php endif; ?>

            <form method="POST" id="registrationForm" novalidate>
              <input type="hidden" name="register" value="1">
              <div class="form-group">
                <label class="form-label" for="student_name">Full Name <span class="required">*</span></label>
                <input type="text" name="student_name" id="student_name" class="form-control"
                       placeholder="Juan Dela Cruz"
                       value="<?= e($_POST['student_name'] ?? '') ?>" required autocomplete="name">
              </div>
              <div class="form-group">
                <label class="form-label" for="student_email">Email Address <span class="required">*</span></label>
                  <input type="email" name="student_email" id="student_email" class="form-control"
                    placeholder="you@cvsu.edu.ph"
                    value="<?= e($_POST['student_email'] ?? '') ?>" required autocomplete="email">
              </div>
              <div class="form-group">
                <label class="form-label" for="student_id">Student ID <span class="required">*</span></label>
                  <input type="text" name="student_id" id="student_id" class="form-control"
                    placeholder="202305575"
                    value="<?= e($_POST['student_id'] ?? '') ?>" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="course">Course / Program</label>
                  <input type="text" name="course" id="course" class="form-control"
                         placeholder="BSIT" value="<?= e($_POST['course'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label" for="year_level">Year Level</label>
                  <select name="year_level" id="year_level" class="form-control">
                    <option value="">Select</option>
                    <?php foreach (['1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate'] as $yr): ?>
                      <option value="<?= $yr ?>" <?= ($_POST['year_level'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <button type="submit" name="register" class="btn btn-primary w-full" id="submitRegBtn"
                      style="margin-top:8px;">
                Confirm Registration
              </button>
              <p style="font-size:.75rem;color:var(--text-muted);margin-top:10px;text-align:center;">
                By registering, you agree to attend. Save your token after registration.
              </p>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<style>
@media (max-width:768px) {
  .detail-grid { grid-template-columns: 1fr !important; }
  .detail-grid > div:last-child { position: static !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
