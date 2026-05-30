<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'reports';
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$eventWhere  = $userRole === 'admin' ? '1=1' : 'organizer_id = ?';
$eventParams = $userRole === 'admin' ? [] : [$userId];

$events = $pdo->prepare("SELECT id, title, event_date, status FROM events WHERE $eventWhere ORDER BY event_date DESC");
$events->execute($eventParams);
$events = $events->fetchAll();

$totalPart = (int)$pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn();
$totalAtt  = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE attendance_status = 'attended'")->fetchColumn();

if ($userRole !== 'admin') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM participants p JOIN events e ON p.event_id = e.id WHERE e.organizer_id = ?
    ");
    $stmt->execute([$userId]);
    $totalPart = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM participants p JOIN events e ON p.event_id = e.id
        WHERE e.organizer_id = ? AND p.attendance_status = 'attended'
    ");
    $stmt->execute([$userId]);
    $totalAtt = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports | <?= e(SITE_NAME) ?></title>
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
        <div class="admin-page-title">Reports & Export</div>
      </div>
    </div>
    <div class="admin-content">
      <div class="admin-content-header">
        <h1>Generate Reports</h1>
        <p>Export participant and attendance data for <?= e(SITE_SHORT) ?> events (CSV format).</p>
      </div>

      <div class="dashboard-stats" style="margin-bottom:28px;">
        <div class="dash-stat">
          <div class="dash-stat-value"><?= $totalPart ?></div>
          <div class="dash-stat-label">Total Registrations</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-value" style="color:var(--success);"><?= $totalAtt ?></div>
          <div class="dash-stat-label">Confirmed Attendances</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-value"><?= $totalPart > 0 ? round($totalAtt / $totalPart * 100) : 0 ?>%</div>
          <div class="dash-stat-label">Overall Attendance Rate</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:32px;">
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-bottom:8px;" class="icon-inline"><?= icon('file-text', 18) ?> All Participants</h2>
          <p style="font-size:.85rem;margin-bottom:16px;">Full registration log across all your events.</p>
          <a href="export.php?type=participants" class="btn btn-primary btn-sm">⬇ Download CSV</a>
        </div>
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-bottom:8px;" class="icon-inline"><?= icon('check-circle', 18) ?> Attendance Report</h2>
          <p style="font-size:.85rem;margin-bottom:16px;">Students who checked in (attended status only).</p>
          <a href="export.php?type=attendance" class="btn btn-primary btn-sm">⬇ Download CSV</a>
        </div>
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-bottom:8px;" class="icon-inline"><?= icon('calendar', 18) ?> Events Summary</h2>
          <p style="font-size:.85rem;margin-bottom:16px;">Event list with registration and attendance counts.</p>
          <a href="export.php?type=events" class="btn btn-primary btn-sm">⬇ Download CSV</a>
        </div>
      </div>

      <div class="card card-body">
        <h2 style="font-size:1rem;margin-bottom:16px;">Per-Event Export</h2>
        <?php if (empty($events)): ?>
          <p style="color:var(--text-muted);">No events available.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>Event</th><th>Date</th><th>Status</th><th>Export</th></tr>
              </thead>
              <tbody>
                <?php foreach ($events as $ev): ?>
                  <tr>
                    <td><?= e($ev['title']) ?></td>
                    <td><?= formatDate($ev['event_date']) ?></td>
                    <td><?= statusBadge($ev['status']) ?></td>
                    <td>
                      <a href="export.php?event_id=<?= $ev['id'] ?>" class="btn btn-sm btn-secondary">⬇ Participants CSV</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
