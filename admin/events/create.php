<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'create-event';
$categories = getCategories($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title            = trim($_POST['title'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $categoryId       = (int)($_POST['category_id'] ?? 0) ?: null;
    $eventDate        = trim($_POST['event_date'] ?? '');
    $startTime        = trim($_POST['start_time'] ?? '');
    $endTime          = trim($_POST['end_time'] ?? '') ?: null;
    $location         = trim($_POST['location'] ?? '');
    $maxParticipants  = max(0, (int)($_POST['max_participants'] ?? 0));
    $status           = $_POST['status'] ?? 'upcoming';
    $isFeatured       = isset($_POST['is_featured']) ? 1 : 0;

    if (!$title)           $errors[] = 'Event title is required.';
    if (!$eventDate)       $errors[] = 'Event date is required.';
    if (!$startTime)       $errors[] = 'Start time is required.';
    if (!$location)        $errors[] = 'Location is required.';
    if (!in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'], true)) {
        $status = 'upcoming';
    }

    $imageFile = null;
    if (!empty($_FILES['image']['name'])) {
        $imageFile = uploadEventImage($_FILES['image']);
        if (!$imageFile) {
            $errors[] = 'Invalid image. Use JPEG, PNG, WebP, or GIF.';
        }
    }

    if (empty($errors)) {
        $qrToken = generateToken(16);
        $stmt = $pdo->prepare("
            INSERT INTO events (organizer_id, title, description, category_id, event_date, start_time, end_time,
                                location, max_participants, image, status, qr_token, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $description,
            $categoryId,
            $eventDate,
            $startTime,
            $endTime,
            $location,
            $maxParticipants,
            $imageFile,
            $status,
            $qrToken,
            $isFeatured,
        ]);
        $eventId = (int)$pdo->lastInsertId();
        logActivity($pdo, $_SESSION['user_id'], 'event_created', "Created event #$eventId: $title");
        setFlash('success', 'Event created successfully. Share the registration link with students.');
        redirect(SITE_URL . '/admin/events/index.php');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Event | <?= e(SITE_NAME) ?></title>
  <?php include __DIR__ . '/../../includes/head-admin.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>
<div class="admin-layout">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main" role="main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <?php include __DIR__ . '/../../includes/sidebar-toggle.php'; ?>
        <div class="admin-page-title">Create Event</div>
      </div>
      <a href="index.php" class="btn btn-secondary btn-sm">← Back to Events</a>
    </div>
    <div class="admin-content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="admin-content-header">
        <h1>New Campus Event</h1>
        <p>Add an event for <?= e(SITE_SHORT) ?> students. A registration link and check-in QR are generated automatically.</p>
      </div>

      <form method="POST" enctype="multipart/form-data" class="admin-form-maximized card card-body">
        <div class="form-container-grid">
          
          <div class="form-column">
            <div class="form-section">
              <div class="form-group">
                <label class="form-label" for="title">Event Title <span class="required">*</span></label>
                <input type="text" name="title" id="title" class="form-control form-control-lg" required
                       value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. CvSU Tech Summit 2026">
              </div>
              <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-control form-control-lg" rows="5"
                          placeholder="Event details, agenda, requirements..."><?= e($_POST['description'] ?? '') ?></textarea>
              </div>
            </div>

            <div class="form-section">
              <h3 class="form-section-title">📅 Date & Time</h3>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="event_date">Date <span class="required">*</span></label>
                  <input type="date" name="event_date" id="event_date" class="form-control form-control-lg" required
                         value="<?= e($_POST['event_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label" for="start_time">Start Time <span class="required">*</span></label>
                  <input type="time" name="start_time" id="start_time" class="form-control form-control-lg" required
                         value="<?= e($_POST['start_time'] ?? '08:00') ?>">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="end_time">End Time</label>
                  <input type="time" name="end_time" id="end_time" class="form-control form-control-lg"
                         value="<?= e($_POST['end_time'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label" for="location">Venue / Location <span class="required">*</span></label>
                  <input type="text" name="location" id="location" class="form-control form-control-lg" required
                         value="<?= e($_POST['location'] ?? '') ?>" placeholder="e.g. CvSU Gymnasium, Indang Campus">
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3 class="form-section-title">🎨 Event Details</h3>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="category_id">Category</label>
                  <select name="category_id" id="category_id" class="form-control form-control-lg">
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= $cat['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="status">Status</label>
                  <select name="status" id="status" class="form-control form-control-lg">
                    <?php foreach (['upcoming','ongoing','completed','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= ($_POST['status'] ?? 'upcoming') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="max_participants">Max Participants</label>
                  <input type="number" name="max_participants" id="max_participants" class="form-control form-control-lg" min="0"
                         value="<?= e($_POST['max_participants'] ?? '0') ?>">
                  <div class="form-help">0 = unlimited capacity</div>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:8px;">
                  <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:0.95rem;">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($_POST['is_featured']) ? 'checked' : '' ?>>
                    <span>⭐ Feature on homepage</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          
          <div class="form-column">
            <div class="form-section">
              <h3 class="form-section-title">📸 Event Banner</h3>
              <div class="upload-area-enhanced" id="uploadArea">
                <input type="file" name="image" id="eventImage" accept="image/*" hidden>
                <div class="upload-content">
                  <div class="upload-icon">🖼️</div>
                  <img id="imagePreview" src="" alt="" style="display:none;max-width:100%;max-height:300px;border-radius:8px;margin-bottom:16px;">
                  <p style="font-size:.95rem;color:var(--text-muted);font-weight:500;">Click or drag image here</p>
                  <p style="font-size:.8rem;color:var(--text-muted);margin-top:8px;">Supported: JPEG, PNG, WebP, GIF</p>
                </div>
              </div>
            </div>

            <div class="form-section" style="background: var(--accent-soft); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--accent);">
              <h4 style="color: var(--accent); margin-bottom: 12px; font-size: 0.95rem; font-weight: 600;">✨ Event Summary</h4>
              <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--text-muted);">Title:</span>
                  <span id="summaryTitle" style="color: var(--text-primary); font-weight: 500;">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--text-muted);">Date:</span>
                  <span id="summaryDate" style="color: var(--text-primary); font-weight: 500;">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--text-muted);">Time:</span>
                  <span id="summaryTime" style="color: var(--text-primary); font-weight: 500;">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--text-muted);">Location:</span>
                  <span id="summaryLocation" style="color: var(--text-primary); font-weight: 500;">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--text-muted);">Capacity:</span>
                  <span id="summaryCapacity" style="color: var(--text-primary); font-weight: 500;">—</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-lg icon-inline" id="saveEventBtn"><?= icon('save', 18) ?> Create Event</button>
          <a href="index.php" class="btn btn-secondary btn-lg">← Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
