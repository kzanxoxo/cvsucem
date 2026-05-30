<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'events';
syncEventStatuses($pdo);

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$where  = $userRole === 'admin' ? '1=1' : 'e.organizer_id = ?';
$params = $userRole === 'admin' ? [] : [$userId];

$events = $pdo->prepare("
    SELECT e.*, c.name AS category_name, c.icon AS category_icon,
           u.name AS organizer_name,
           (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS participant_count
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.organizer_id = u.id
    WHERE $where
    ORDER BY e.event_date DESC
");
$events->execute($params);
$events = $events->fetchAll();

$flash = getFlash();
$checkinBase = SITE_URL . '/checkin.php?token=';
$regBase     = SITE_URL . '/register-event.php?event=';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management | <?= e(SITE_NAME) ?></title>
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
        <div class="admin-page-title">Event Management</div>
      </div>
      <a href="create.php" class="btn btn-primary btn-sm icon-inline" id="createEventBtn"><?= icon('plus', 16) ?> Create Event</a>
    </div>

    <div class="admin-content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" role="alert"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <div class="admin-content-header">
        <div>
          <h1>All Events</h1>
          <p><?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> total</p>
        </div>
      </div>

      
      <div style="margin-bottom:20px;">
        <div class="search-wrapper" style="max-width:340px;">
          <span class="search-icon"><?= icon('search', 18) ?></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Search events..."
                 aria-label="Search events">
        </div>
      </div>

      <?php if (empty($events)): ?>
        <div class="empty-state">
          <?= stateIcon('inbox', 'default') ?>
          <h3>No events yet</h3>
          <p>Create your first campus event to get started.</p>
          <a href="create.php" class="btn btn-primary icon-inline" id="firstEventBtn"><?= icon('plus', 18) ?> Create First Event</a>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table aria-label="Events list">
            <thead>
              <tr>
                <th>Event</th>
                <th>Date & Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>Participants</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $ev):
                $cnt = (int)$ev['participant_count'];
                $max = (int)$ev['max_participants'];
                $pct = $max > 0 ? min(100,round($cnt/$max*100)) : 0;
                $checkinUrl = $checkinBase . $ev['qr_token'];
                $regUrl     = $regBase . $ev['id'];
              ?>
                <tr data-search="<?= e(strtolower($ev['title'].' '.$ev['location'].' '.($ev['category_name']??''))) ?>">
                  <td style="max-width:260px;">
                    <div style="display:flex;align-items:start;gap:10px;">
                      <?php if ($ev['image']): ?>
                        <img src="<?= UPLOAD_URL . e($ev['image']) ?>" alt=""
                             style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;border:1px solid var(--glass-border);">
                      <?php else: ?>
                        <div style="flex-shrink:0;opacity:.9;">
                          <?= categoryMark(['name' => $ev['category_name'] ?? 'Event', 'color' => $ev['category_color'] ?? '#1a9e5c'], 18) ?>
                        </div>
                      <?php endif; ?>
                      <div style="min-width:0;">
                        <strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;"><?= e($ev['title']) ?></strong>
                        <span style="font-size:.75rem;color:var(--text-muted);">By <?= e($ev['organizer_name']) ?></span>
                        <?php if ($ev['is_featured']): ?>
                          <?= featuredBadge() ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style="font-size:.85rem;font-weight:600;color:var(--text-primary);"><?= formatDate($ev['event_date']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);"><?= formatTime($ev['start_time']) ?></div>
                  </td>
                  <td>
                    <div style="font-size:.85rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($ev['location']) ?>
                    </div>
                  </td>
                  <td><?= statusBadge($ev['status']) ?></td>
                  <td>
                    <div style="font-size:.88rem;font-weight:600;color:var(--text-primary);"><?= $cnt ?><?= $max > 0 ? "/$max" : '' ?></div>
                    <?php if ($max > 0): ?>
                      <div class="capacity-bar" style="width:80px;margin-top:4px;">
                        <div class="capacity-bar-fill" data-fill="<?= $pct ?>" style="width:0%;"></div>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="table-actions">
                      <a href="<?= SITE_URL ?>/event-detail.php?id=<?= $ev['id'] ?>" target="_blank"
                         class="btn btn-icon btn-secondary btn-sm" title="View public page"
                         id="viewPublic<?= $ev['id'] ?>"><?= icon('eye', 18) ?></a>
                      <a href="edit.php?id=<?= $ev['id'] ?>"
                         class="btn btn-icon btn-secondary btn-sm" title="Edit event"
                         id="editEvent<?= $ev['id'] ?>"><?= icon('edit', 18) ?></a>
                      <button type="button" class="btn btn-icon btn-secondary btn-sm" title="Registration link"
                              data-reg-btn
                              data-reg-url="<?= e($regUrl) ?>"
                              data-event-title="<?= e($ev['title']) ?>"
                              id="regBtn<?= $ev['id'] ?>"><?= icon('link', 18) ?></button>
                      <button type="button" class="btn btn-icon btn-secondary btn-sm" title="Check-in QR"
                              data-qr-btn
                              data-checkin-url="<?= e($checkinUrl) ?>"
                              data-event-title="<?= e($ev['title']) ?>"
                              id="qrBtn<?= $ev['id'] ?>"><?= icon('qr-code', 18) ?></button>
                      <a href="<?= SITE_URL ?>/admin/participants/event.php?id=<?= $ev['id'] ?>"
                         class="btn btn-icon btn-secondary btn-sm" title="View participants"
                         id="partBtn<?= $ev['id'] ?>"><?= icon('users', 18) ?></a>
                      <a href="delete.php?id=<?= $ev['id'] ?>"
                         class="btn btn-icon btn-danger btn-sm" title="Delete event"
                         data-confirm="Delete '<?= e($ev['title']) ?>'? This will remove all participant records."
                         id="delBtn<?= $ev['id'] ?>"><?= icon('trash', 18) ?></a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<div class="modal-overlay" id="regModal" role="dialog" aria-modal="true" aria-labelledby="regEventName">
  <div class="modal">
    <h3 id="regEventName">Student Registration Link</h3>
    <p>Share this link with CvSU students. No account required to register.</p>
    <input type="text" id="regLinkField" class="form-control" readonly style="font-size:.8rem;margin-bottom:12px;">
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <button type="button" class="btn btn-sm btn-primary icon-inline" data-copy="" id="copyRegLinkBtn"><?= icon('copy', 16) ?> Copy Link</button>
      <button class="btn btn-sm btn-secondary" data-modal-close>Close</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="qrModal" role="dialog" aria-modal="true" aria-labelledby="qrEventName">
  <div class="modal">
    <h3 id="qrEventName">Event Check-In QR</h3>
    <p>Display this QR code at the event venue for student self check-in.</p>
    <img id="qrImage" src="" alt="QR Code" class="qr-image" style="margin:0 auto 16px;">
    <div style="font-size:.72rem;word-break:break-all;color:var(--text-muted);background:rgba(255,255,255,.04);padding:8px;border-radius:8px;margin-bottom:16px;">
      <a id="qrCheckinUrl" href="#" target="_blank" style="color:var(--accent);">Loading...</a>
    </div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <button type="button" class="btn btn-sm btn-secondary icon-inline" data-copy-btn id="copyUrlBtn"><?= icon('copy', 16) ?> Copy Link</button>
      <button class="btn btn-sm btn-secondary" data-modal-close id="closeQrBtn">Close</button>
    </div>
  </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script>

document.getElementById('copyUrlBtn')?.addEventListener('click', function() {
  const url = document.getElementById('qrCheckinUrl').href;
  navigator.clipboard.writeText(url).then(() => {
    const label = <?= json_encode(icon('copy', 16) . ' Copy Link') ?>;
    const done = <?= json_encode(icon('check', 16) . ' Copied!') ?>;
    this.innerHTML = done;
    setTimeout(() => { this.innerHTML = label; }, 2000);
  });
});
</script>
</body>
</html>
