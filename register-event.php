<?php

require_once 'config/database.php';
require_once 'config/functions.php';

$eventId = (int)($_GET['event'] ?? $_GET['id'] ?? 0);
if (!$eventId) {
    setFlash('error', 'Invalid registration link.');
    redirect(SITE_URL . '/events.php');
}

$event = getEvent($pdo, $eventId);
if (!$event) {
    setFlash('error', 'Event not found.');
    redirect(SITE_URL . '/events.php');
}

syncEventStatuses($pdo);
$event = getEvent($pdo, $eventId);

$count  = getParticipantCount($pdo, $eventId);
$max    = (int)$event['max_participants'];
$isFull = ($max > 0 && $count >= $max);
$canRegister = !$isFull && !in_array($event['status'], ['cancelled', 'completed']);

$errors   = [];
$success  = false;
$regToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && $canRegister) {
    $name      = trim($_POST['student_name'] ?? '');
    $email     = strtolower(trim($_POST['student_email'] ?? ''));
    $studentId = trim($_POST['student_id'] ?? '');
    $course    = trim($_POST['course'] ?? '');
    $yearLevel = trim($_POST['year_level'] ?? '');

    if (!$name)      $errors[] = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!$studentId) $errors[] = 'Student ID is required.';

    if (empty($errors)) {
        if (isAlreadyRegistered($pdo, $eventId, $email)) {
            $errors[] = 'This email is already registered for this event.';
        } else {
            $freshCount = getParticipantCount($pdo, $eventId);
            if ($max > 0 && $freshCount >= $max) {
                $errors[] = 'Sorry, the event is now full.';
            } else {
                $token = generateToken(16);
                $pdo->prepare("
                    INSERT INTO participants (event_id, student_name, student_email, student_id, course, year_level, registration_token)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([$eventId, $name, $email, $studentId, $course, $yearLevel, $token]);
                logActivity($pdo, null, 'student_registered', "$name registered for event #$eventId via register-event");
                $success  = true;
                $regToken = $token;
                $count    = getParticipantCount($pdo, $eventId);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $cancelToken = trim($_POST['reg_token'] ?? '');
    if ($cancelToken) {
        $stmt = $pdo->prepare('DELETE FROM participants WHERE registration_token = ? AND event_id = ?');
        $stmt->execute([$cancelToken, $eventId]);
        setFlash($stmt->rowCount() ? 'success' : 'error',
            $stmt->rowCount() ? 'Your registration has been cancelled.' : 'Token not found.');
        redirect(SITE_URL . '/register-event.php?event=' . $eventId);
    }
}

$pageTitle  = 'Register — ' . $event['title'];
$pageDesc   = 'Register for ' . $event['title'] . ' at ' . UNIVERSITY_NAME;
$activePage = 'events';

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a><span class="sep">›</span>
      <a href="events.php">Events</a><span class="sep">›</span>
      <span>Registration</span>
    </nav>
    <h1>Event Registration</h1>
    <p><?= e(UNIVERSITY_NAME) ?> — no student account required</p>
  </div>
</div>

<section class="section" style="padding-top:0;">
  <div class="container" style="max-width:640px;">
    <div class="card card-body fade-up" style="margin-bottom:24px;">
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
        <?= statusBadge($event['status']) ?>
        <?php if ($event['category_name']): ?>
          <span class="badge badge-category"><?= e($event['category_icon']) ?> <?= e($event['category_name']) ?></span>
        <?php endif; ?>
      </div>
      <h2 style="font-size:1.4rem;margin-bottom:12px;"><?= e($event['title']) ?></h2>
      <div class="event-card-info">
        <div class="event-card-info-item icon-inline"><?= icon('calendar', 18) ?> <?= formatDate($event['event_date']) ?></div>
        <div class="event-card-info-item icon-inline"><?= icon('clock', 18) ?> <?= formatTime($event['start_time']) ?></div>
        <div class="event-card-info-item icon-inline"><?= icon('map-pin', 18) ?> <?= e($event['location']) ?></div>
      </div>
      <?php if ($max > 0): ?>
        <p style="font-size:.85rem;margin-top:12px;color:var(--text-muted);"><?= $count ?> / <?= $max ?> registered</p>
      <?php endif; ?>
      <a href="event-detail.php?id=<?= $eventId ?>" class="btn btn-sm btn-secondary" style="margin-top:12px;">View full event details</a>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($success && $regToken): ?>
      <div class="card card-body text-center" style="border-color:rgba(67,233,123,.3);">
        <?= stateIcon('check-circle', 'success') ?>
        <h2 style="color:var(--success);">Registration Confirmed!</h2>
        <p style="font-size:.88rem;margin:12px 0;">Save your token for check-in and cancellation:</p>
        <div style="font-family:monospace;padding:12px;background:rgba(255,255,255,.05);border-radius:8px;word-break:break-all;color:var(--success);">
          <?= e($regToken) ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm w-full mt-4 icon-inline" data-copy="<?= e($regToken) ?>"><?= icon('copy', 16) ?> Copy Token</button>
      </div>

    <?php elseif (!$canRegister): ?>
      <div class="card card-body text-center">
        <h2><?= $isFull ? 'Event Full' : 'Registration Closed' ?></h2>
        <p style="margin-top:8px;">This event is not accepting registrations.</p>
        <a href="events.php" class="btn btn-primary mt-4">Browse Other Events</a>
      </div>

    <?php else: ?>
      <div class="card card-body">
        <h2 style="font-size:1.1rem;margin-bottom:16px;">Your Details</h2>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error" id="formError">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-error" id="formError" style="display:none;">Please fix the errors above.</div>
        <?php endif; ?>
        <form method="POST" id="registrationForm" novalidate>
          <input type="hidden" name="register" value="1">
          <div class="form-group">
            <label class="form-label" for="student_name">Full Name <span class="required">*</span></label>
            <input type="text" name="student_name" id="student_name" class="form-control" required
                   value="<?= e($_POST['student_name'] ?? '') ?>" placeholder="Juan Dela Cruz">
          </div>
          <div class="form-group">
            <label class="form-label" for="student_email">Email <span class="required">*</span></label>
                 <input type="email" name="student_email" id="student_email" class="form-control" required
                   value="<?= e($_POST['student_email'] ?? '') ?>" placeholder="you@cvsu.edu.ph">
          </div>
          <div class="form-group">
            <label class="form-label" for="student_id">Student ID <span class="required">*</span></label>
                 <input type="text" name="student_id" id="student_id" class="form-control" required
                   value="<?= e($_POST['student_id'] ?? '') ?>" placeholder="202305575">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="course">Course / Program</label>
              <input type="text" name="course" id="course" class="form-control"
                     value="<?= e($_POST['course'] ?? '') ?>" placeholder="BSIT">
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
          <button type="submit" name="register" class="btn btn-primary w-full">Confirm Registration</button>
        </form>
      </div>

      <div class="card card-body" style="margin-top:20px;">
        <h3 style="font-size:1rem;margin-bottom:10px;">Cancel Registration</h3>
        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;">
          <input type="text" name="reg_token" class="form-control" placeholder="Registration token" style="flex:1;" required>
          <button type="submit" name="cancel" class="btn btn-danger btn-sm"
                  onclick="return confirm('Cancel your registration?')">Cancel</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
