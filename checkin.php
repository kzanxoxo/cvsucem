<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$qrToken = trim($_GET['token'] ?? '');
$event   = null;
$error   = null;
$participant = null;
$alreadyCheckedIn = false;

if ($qrToken) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE qr_token = ?");
    $stmt->execute([$qrToken]);
    $event = $stmt->fetch();
    if (!$event) {
        $error = 'Invalid or expired check-in link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $regToken = trim($_POST['reg_token'] ?? '');
    if (!$regToken) {
      $error = 'Please enter your registration token or your Student ID.';
    } else {
        
        $stmt = $pdo->prepare("SELECT p.* FROM participants p WHERE p.event_id = ? AND p.registration_token = ? LIMIT 1");
        $stmt->execute([$event['id'], $regToken]);
        $participant = $stmt->fetch();

        
        if (!$participant) {
          $stmt = $pdo->prepare("SELECT p.* FROM participants p WHERE p.event_id = ? AND p.student_id = ? AND p.attendance_status != 'attended' LIMIT 1");
          $stmt->execute([$event['id'], $regToken]);
          $participant = $stmt->fetch();
        }

        
        if (!$participant) {
          $stmt = $pdo->prepare("SELECT p.* FROM participants p WHERE p.event_id = ? AND p.student_id = ? LIMIT 1");
          $stmt->execute([$event['id'], $regToken]);
          $maybe = $stmt->fetch();
          if ($maybe) {
            $participant = $maybe;
            if ($participant['attendance_status'] === 'attended') {
              $alreadyCheckedIn = true;
            }
          } else {
            $error = 'Registration token or Student ID not found for this event. Please check and try again.';
          }
        }

        
        if (!$error && $participant && !$alreadyCheckedIn) {
          $upd = $pdo->prepare("UPDATE participants SET attendance_status = 'attended', checked_in_at = NOW() WHERE id = ? AND attendance_status != 'attended'");
          $upd->execute([$participant['id']]);
          if ($upd->rowCount()) {
            $participant['attendance_status'] = 'attended';
            $participant['checked_in_at'] = date('Y-m-d H:i:s');
            logActivity($pdo, null, 'checkin', $participant['student_name'] . ' checked in for event #' . $event['id']);
          } else {
            
            $alreadyCheckedIn = true;
          }
        }
    }
}

if ($event && !$participant && !$error) {
    $stmt = $pdo->prepare("
        SELECT e.*, c.name AS category_name, c.color AS category_color
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event['id']]);
    $event = $stmt->fetch();
}

$pageTitle  = 'Event Check-In';
$activePage = '';
include 'includes/header.php';
?>

<div class="container">
  <div class="checkin-card fade-up" style="max-width:520px;margin:60px auto;text-align:center;">

    <?php if ($error && !$event): ?>
      <?= stateIcon('x-circle', 'error') ?>
      <div class="card card-body">
        <h1 style="font-size:1.4rem;margin-bottom:10px;color:var(--danger);">Check-In Failed</h1>
        <p><?= e($error) ?></p>
        <a href="events.php" class="btn btn-primary mt-6" id="backToEventsBtn">Back to Events</a>
      </div>

    <?php elseif ($participant && $participant['attendance_status'] === 'attended'): ?>
      <div class="checkin-icon state-icon state-icon--success"><?= icon('check-circle', 48) ?></div>
      <div class="card card-body" style="border-color:rgba(67,233,123,.3);background:rgba(67,233,123,.04);">
        <h1 style="font-size:1.6rem;margin-bottom:8px;color:var(--success);">
          <?= $alreadyCheckedIn ? 'Already Checked In!' : 'Check-In Successful!' ?>
        </h1>
        <p style="font-size:.95rem;">
          Welcome, <strong><?= e($participant['student_name']) ?></strong>!<br>
          You're confirmed for <strong><?= e($event['title']) ?></strong>.
        </p>
        <div style="margin:20px 0;padding:16px;background:rgba(255,255,255,.04);border:1px solid var(--glass-border);border-radius:var(--radius);">
          <div class="meta-line" style="margin-bottom:2px;">Check-in Time</div>
          <div style="font-weight:700;color:var(--text-primary);">
            <?= $participant['checked_in_at'] ? date('F j, Y g:i A', strtotime($participant['checked_in_at'])) : 'Just now' ?>
          </div>
        </div>
        <a href="certificate.php?token=<?= urlencode($participant['registration_token']) ?>"
           class="btn btn-primary w-full icon-inline" id="getCertBtn"><?= icon('award', 18) ?> Download Certificate</a>
      </div>

    <?php elseif ($event): ?>
      <div style="margin-bottom:24px;">
        <div class="icon-inline" style="justify-content:center;font-weight:600;color:var(--accent);margin-bottom:6px;">
          <?= categoryMark(['name' => $event['category_name'] ?? 'Event', 'color' => $event['category_color'] ?? '#1a9e5c'], 18) ?>
          <?= e($event['category_name'] ?? 'Event') ?>
        </div>
        <h1 style="font-size:clamp(1.4rem,3vw,2rem);margin-bottom:8px;"><?= e($event['title']) ?></h1>
        <p class="icon-inline" style="justify-content:center;font-size:var(--text-base);color:var(--text-secondary);">
          <?= icon('calendar', 16) ?> <?= formatDate($event['event_date']) ?>
          <span style="opacity:.5;">·</span>
          <?= icon('map-pin', 16) ?> <?= e($event['location']) ?>
        </p>
      </div>

      <div class="card card-body" style="text-align:left;">
        <div style="text-align:center;margin-bottom:20px;">
          <?= stateIcon('qr-code', 'default') ?>
          <h2 style="font-size:1.2rem;">Enter your Registration Token or Student ID</h2>
          <p class="subtitle-line">You received this token when you registered — or simply enter your Student ID.</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="checkinForm">
          <div class="form-group">
                 <label class="form-label" for="reg_token">Registration Token or Student ID</label>
                 <input type="text" name="reg_token" id="reg_token" class="form-control"
                   placeholder="Paste token or enter Student ID" required
                   style="font-family:monospace;letter-spacing:.04em;"
                   autofocus autocomplete="off">
          </div>
          <button type="submit" class="btn btn-primary w-full icon-inline" id="checkinSubmitBtn">
            <?= icon('check', 18) ?> Check In Now
          </button>
        </form>
        <p class="meta-line" style="margin-top:14px;text-align:center;">
          Don't have a token? <a href="event-detail.php?id=<?= $event['id'] ?>">Register first</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
