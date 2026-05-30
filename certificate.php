<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$token = trim($_GET['token'] ?? '');

if (!$token) {
    setFlash('error', 'Invalid certificate link.');
    redirect(SITE_URL . '/events.php');
}

$stmt = $pdo->prepare("
    SELECT p.*, e.title AS event_title, e.event_date, e.start_time, e.end_time,
           e.location, e.id AS event_id,
           c.name AS category_name, c.icon AS category_icon,
           u.name AS organizer_name
    FROM participants p
    JOIN events e ON p.event_id = e.id
    LEFT JOIN categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.organizer_id = u.id
    WHERE p.registration_token = ?
");
$stmt->execute([$token]);
$data = $stmt->fetch();

if (!$data) {
    setFlash('error', 'Certificate not found. Check your registration token.');
    redirect(SITE_URL . '/events.php');
}

$attended  = $data['attendance_status'] === 'attended';
$certDate  = $data['checked_in_at'] ? date('F j, Y', strtotime($data['checked_in_at'])) : formatDate($data['event_date']);

$pageTitle  = 'Participation Certificate';
$activePage = '';
include 'includes/header.php';
?>

<div class="container" style="padding:40px 24px;">

  
  <div class="no-print" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="events.php">Events</a>
        <span class="sep">›</span>
        <a href="event-detail.php?id=<?= $data['event_id'] ?>">Event</a>
        <span class="sep">›</span>
        <span>Certificate</span>
      </nav>
      <h1 style="font-size:1.6rem;" class="icon-inline"><?= icon('award', 22) ?> Participation Certificate</h1>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="button" onclick="window.print()" class="btn btn-primary icon-inline" id="printCertBtn"><?= icon('print', 18) ?> Print Certificate</button>
      <a href="events.php" class="btn btn-secondary no-print" id="backEventsBtn">← Events</a>
    </div>
  </div>

  <?php if (!$attended): ?>
    <div class="alert-note no-print" role="alert">
      <?= icon('alert-circle', 18) ?>
      <span><strong>Note:</strong> Your attendance has not been confirmed yet. This is a pre-certificate. Your check-in must be recorded to receive a full participation certificate.</span>
    </div>
  <?php endif; ?>

  
  <div class="certificate-wrapper fade-up">
    <div class="certificate" id="certificate">
      <div class="certificate-border" aria-hidden="true"></div>

      <?php $brandMarkSize = 64; $brandMarkClass = 'certificate-logo'; include __DIR__ . '/includes/brand-mark.php'; ?>
      <div class="certificate-school"><?= e(UNIVERSITY_NAME) ?></div>
      <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:24px;">Campus Event Management System · <?= e(SITE_SHORT) ?></div>

      <div class="certificate-presents">This certificate is proudly presented to</div>

      <div class="certificate-name"><?= e($data['student_name']) ?></div>
      <div class="certificate-id">Student ID: <?= e($data['student_id']) ?><?= $data['course'] ? ' · ' . e($data['course']) : '' ?><?= $data['year_level'] ? ' · ' . e($data['year_level']) : '' ?></div>

      <div class="certificate-body">
        In recognition of <?= $attended ? 'active participation' : 'registration' ?> in the campus event:
      </div>

      <div class="certificate-event"><?= e($data['event_title']) ?></div>
      <div class="certificate-date">
        <?= formatDate($data['event_date']) ?>
        <?php if ($data['start_time']): ?>
          · <?= formatTime($data['start_time']) ?><?= $data['end_time'] ? ' – ' . formatTime($data['end_time']) : '' ?>
        <?php endif; ?>
        <br><span class="icon-inline" style="justify-content:center;"><?= icon('map-pin', 16) ?> <?= e($data['location']) ?></span>
      </div>

      <div class="certificate-footer">
        <div class="certificate-signature">
          <div style="font-size:1.3rem;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);margin-bottom:8px;font-style:italic;"><?= e($data['organizer_name']) ?></div>
          <div class="certificate-signature-line" style="margin:0 auto 8px;"></div>
          <div class="certificate-signature-label">Event Organizer</div>
        </div>

        <div style="text-align:center;">
          <div style="margin-bottom:8px;"><?= icon('award', 32) ?></div>
          <div style="font-size:.7rem;color:var(--text-muted);">Issued: <?= $certDate ?></div>
          <div style="font-size:.65rem;color:var(--text-muted);margin-top:2px;font-family:monospace;">
            REF: <?= strtoupper(substr($data['registration_token'], 0, 12)) ?>
          </div>
        </div>

        <div class="certificate-signature">
          <div style="font-size:1.3rem;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);margin-bottom:8px;font-style:italic;"><?= e(SITE_SHORT) ?> Events</div>
          <div class="certificate-signature-line" style="margin:0 auto 8px;"></div>
          <div class="certificate-signature-label">Office of Student Affairs</div>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-8 no-print">
    <p style="font-size:.85rem;color:var(--text-muted);">
      Save this page or print it as PDF to keep your certificate.
      Reference: <code style="color:var(--accent);"><?= e(substr($data['registration_token'], 0, 16)) ?>...</code>
    </p>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
