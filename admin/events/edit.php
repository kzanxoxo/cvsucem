<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'events';
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect(SITE_URL . '/admin/events/index.php');
}

$event = requireEventAccess($pdo, $id);
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

    if (!$title)     $errors[] = 'Event title is required.';
    if (!$eventDate) $errors[] = 'Event date is required.';
    if (!$startTime) $errors[] = 'Start time is required.';
    if (!$location)  $errors[] = 'Location is required.';
    if (!in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'], true)) {
        $status = 'upcoming';
    }

    $imageFile = $event['image'];
    if (!empty($_FILES['image']['name'])) {
        $uploaded = uploadEventImage($_FILES['image']);
        if ($uploaded) {
            if ($imageFile && file_exists(UPLOAD_DIR . $imageFile)) {
                @unlink(UPLOAD_DIR . $imageFile);
            }
            $imageFile = $uploaded;
        } else {
            $errors[] = 'Invalid image. Use JPEG, PNG, WebP, or GIF.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE events SET title=?, description=?, category_id=?, event_date=?, start_time=?, end_time=?,
                              location=?, max_participants=?, image=?, status=?, is_featured=?
            WHERE id=?
        ");
        $stmt->execute([
            $title, $description, $categoryId, $eventDate, $startTime, $endTime,
            $location, $maxParticipants, $imageFile, $status, $isFeatured, $id,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'event_updated', "Updated event #$id: $title");
        setFlash('success', 'Event updated successfully.');
        redirect(SITE_URL . '/admin/events/index.php');
    }
    $event = array_merge($event, $_POST);
} else {
    $_POST = $event;
}

$regUrl = getEventRegistrationUrl($id);
$checkinUrl = getEventCheckinUrl($event['qr_token']);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Event | <?= e(SITE_NAME) ?></title>
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
        <div class="admin-page-title">Edit Event</div>
      </div>
      <a href="index.php" class="btn btn-secondary btn-sm">← All Events</a>
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

      <div class="card card-body" style="margin-bottom:24px;max-width:720px;">
        <h2 style="font-size:1rem;margin-bottom:12px;" class="icon-inline"><?= icon('link', 18) ?> Student Links</h2>
        <div style="margin-bottom:12px;">
          <div class="form-label">Registration Link</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" class="form-control" readonly value="<?= e($regUrl) ?>" id="regLinkInput" style="flex:1;min-width:200px;font-size:.8rem;">
            <button type="button" class="btn btn-sm btn-secondary icon-inline" data-copy="<?= e($regUrl) ?>"><?= icon('copy', 16) ?> Copy</button>
          </div>
        </div>
        <div>
          <div class="form-label">Check-In QR / Link</div>
          <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            <img src="<?= e(getQRCodeUrl($checkinUrl, 120)) ?>" alt="Check-in QR" width="120" height="120" style="border-radius:8px;background:#fff;padding:6px;">
            <div style="flex:1;min-width:180px;">
              <input type="text" class="form-control" readonly value="<?= e($checkinUrl) ?>" style="font-size:.8rem;margin-bottom:8px;">
              <a href="<?= e($checkinUrl) ?>" target="_blank" class="btn btn-sm btn-secondary">Open Check-In Page</a>
            </div>
          </div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" class="admin-form card card-body" style="max-width:720px;">
        <div class="form-group">
          <label class="form-label" for="title">Event Title <span class="required">*</span></label>
          <input type="text" name="title" id="title" class="form-control" required value="<?= e($event['title']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="description">Description</label>
          <textarea name="description" id="description" class="form-control" rows="4"><?= e($event['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (int)($event['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-control">
              <?php foreach (['upcoming','ongoing','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($event['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="event_date">Date <span class="required">*</span></label>
            <input type="date" name="event_date" id="event_date" class="form-control" required
                   value="<?= e($event['event_date']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="start_time">Start Time <span class="required">*</span></label>
            <input type="time" name="start_time" id="start_time" class="form-control" required
                   value="<?= e(substr($event['start_time'], 0, 5)) ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="end_time">End Time</label>
            <input type="time" name="end_time" id="end_time" class="form-control"
                   value="<?= $event['end_time'] ? e(substr($event['end_time'], 0, 5)) : '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="location">Venue / Location <span class="required">*</span></label>
          <input type="text" name="location" id="location" class="form-control" required value="<?= e($event['location']) ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="max_participants">Max Participants</label>
            <input type="number" name="max_participants" id="max_participants" class="form-control" min="0"
                   value="<?= (int)$event['max_participants'] ?>">
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:8px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="is_featured" value="1" <?= !empty($event['is_featured']) ? 'checked' : '' ?>>
              Feature on homepage
            </label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Event Banner</label>
          <?php if ($event['image']): ?>
            <img src="<?= UPLOAD_URL . e($event['image']) ?>" alt="" id="imagePreview"
                 style="display:block;max-width:100%;max-height:160px;border-radius:8px;margin-bottom:10px;">
          <?php else: ?>
            <img id="imagePreview" src="" alt="" style="display:none;max-width:100%;max-height:160px;border-radius:8px;margin-bottom:10px;">
          <?php endif; ?>
          <div class="upload-area" id="uploadArea">
            <input type="file" name="image" id="eventImage" accept="image/*" hidden>
            <p style="font-size:.85rem;color:var(--text-muted);">Upload new image to replace</p>
          </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary icon-inline"><?= icon('save', 18) ?> Save Changes</button>
          <a href="index.php" class="btn btn-secondary">Cancel</a>
          <a href="delete.php?id=<?= $id ?>" class="btn btn-danger icon-inline"
             data-confirm="Delete this event and all participant records?"><?= icon('trash', 16) ?> Delete</a>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
