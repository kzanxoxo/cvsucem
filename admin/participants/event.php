<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'participants';
$eventId = (int)($_GET['id'] ?? 0);
if (!$eventId) {
    redirect(SITE_URL . '/admin/participants/index.php');
}

$event = requireEventAccess($pdo, $eventId);

$participants = $pdo->prepare("
    SELECT * FROM participants WHERE event_id = ? ORDER BY registered_at DESC
");
$participants->execute([$eventId]);
$participants = $participants->fetchAll();

$count    = count($participants);
$attended = count(array_filter($participants, fn($p) => $p['attendance_status'] === 'attended'));
$flash    = getFlash();
$regUrl   = getEventRegistrationUrl($eventId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participants — <?= e($event['title']) ?> | <?= e(SITE_NAME) ?></title>
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
        <div>
          <div class="admin-page-title">Event Participants</div>
          <div style="font-size:.75rem;color:var(--text-muted);"><?= e($event['title']) ?></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= SITE_URL ?>/admin/reports/export.php?event_id=<?= $eventId ?>" class="btn btn-secondary btn-sm">⬇ CSV Export</a>
        <a href="index.php" class="btn btn-secondary btn-sm">← All Participants</a>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <div class="dashboard-stats" style="margin-bottom:24px;">
        <div class="dash-stat">
          <div class="dash-stat-value"><?= $count ?></div>
          <div class="dash-stat-label">Registered</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-value" style="color:var(--success);"><?= $attended ?></div>
          <div class="dash-stat-label">Attended</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-value"><?= $count > 0 ? round($attended / $count * 100) : 0 ?>%</div>
          <div class="dash-stat-label">Attendance Rate</div>
        </div>
      </div>

      <div class="card card-body" style="margin-bottom:20px;">
        <strong>Registration link:</strong>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
          <input type="text" class="form-control" readonly value="<?= e($regUrl) ?>" style="flex:1;font-size:.8rem;">
          <button type="button" class="btn btn-sm btn-secondary icon-inline" data-copy="<?= e($regUrl) ?>"><?= icon('copy', 16) ?> Copy</button>
        </div>
      </div>

      <div style="margin-bottom:16px;">
        <input type="text" id="tableSearch" class="form-control" placeholder="Filter participants..." style="max-width:320px;">
      </div>

      <div class="table-wrapper">
        <table aria-label="Event participants">
          <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Course</th>
              <th>Attended?</th>
              <th>Registered</th>
              <th>Check-in</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($participants)): ?>
              <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No registrations yet.</td></tr>
            <?php else: foreach ($participants as $p): ?>
              <tr data-search="<?= e(strtolower($p['student_name'].' '.$p['student_email'].' '.$p['student_id'])) ?>">
                <td>
                  <strong><?= e($p['student_name']) ?></strong>
                  <div style="font-size:.75rem;color:var(--text-muted);"><?= e($p['student_id']) ?></div>
                </td>
                <td style="font-size:.85rem;"><?= e($p['student_email']) ?></td>
                <td style="font-size:.85rem;"><?= e($p['course'] ?: '—') ?> <?= $p['year_level'] ? '· '.e($p['year_level']) : '' ?></td>
                <td>
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" class="attendance-check"
                           data-id="<?= $p['id'] ?>"
                           <?= $p['attendance_status'] === 'attended' ? 'checked' : '' ?>>
                    <span class="status-cell">
                      <?php if ($p['attendance_status'] === 'attended'): ?>
                        <?= attendanceBadge('attended') ?>
                      <?php else: ?>
                        <span class="badge badge-upcoming">Registered</span>
                      <?php endif; ?>
                    </span>
                  </label>
                </td>
                <td style="font-size:.78rem;color:var(--text-muted);">
                  <?= date('M j, Y', strtotime($p['registered_at'])) ?>
                </td>
                <td style="font-size:.78rem;color:var(--text-muted);">
                  <?= $p['checked_in_at'] ? date('M j, g:i A', strtotime($p['checked_in_at'])) : '—' ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
