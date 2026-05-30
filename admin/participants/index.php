<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$adminActivePage = 'participants';
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$where  = $userRole === 'admin' ? '1=1' : 'e.organizer_id = ?';
$params = $userRole === 'admin' ? [] : [$userId];

$search = trim($_GET['search'] ?? '');
if ($search) {
    $where .= " AND (p.student_name LIKE ? OR p.student_email LIKE ? OR p.student_id LIKE ? OR e.title LIKE ?)";
    $q = "%$search%";
    $params = array_merge($params, [$q, $q, $q, $q]);
}

$sql = "
    SELECT p.*, e.title AS event_title, e.event_date, e.id AS event_id
    FROM participants p
    JOIN events e ON p.event_id = e.id
    WHERE $where
    ORDER BY p.registered_at DESC
    LIMIT 500
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participants | <?= e(SITE_NAME) ?></title>
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
        <div class="admin-page-title">Participant Management</div>
      </div>
      <a href="<?= SITE_URL ?>/admin/reports/index.php" class="btn btn-secondary btn-sm icon-inline"><?= icon('bar-chart', 16) ?> Export Reports</a>
    </div>
    <div class="admin-content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <div class="admin-content-header">
        <h1>All Participants</h1>
        <p>Registration logs for <?= e(UNIVERSITY_NAME) ?> campus events (students register without accounts).</p>
      </div>

      <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;max-width:480px;">
        <input type="text" name="search" class="form-control" placeholder="Search name, email, ID, event..."
               value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?>
          <a href="index.php" class="btn btn-secondary btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <div class="table-wrapper">
        <table aria-label="All participants">
          <thead>
            <tr>
              <th>Student</th>
              <th>Event</th>
              <th>Course / Year</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($participants)): ?>
              <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No participants found.</td></tr>
            <?php else: foreach ($participants as $p): ?>
              <tr data-search="<?= e(strtolower($p['student_name'].' '.$p['student_email'].' '.$p['event_title'])) ?>">
                <td>
                  <strong><?= e($p['student_name']) ?></strong>
                  <div style="font-size:.75rem;color:var(--text-muted);"><?= e($p['student_id']) ?> · <?= e($p['student_email']) ?></div>
                </td>
                <td>
                  <a href="event.php?id=<?= $p['event_id'] ?>"><?= e($p['event_title']) ?></a>
                  <div style="font-size:.72rem;color:var(--text-muted);"><?= formatDate($p['event_date']) ?></div>
                </td>
                <td style="font-size:.85rem;">
                  <?= e($p['course'] ?: '—') ?><br>
                  <span style="color:var(--text-muted);"><?= e($p['year_level'] ?: '') ?></span>
                </td>
                <td class="status-cell">
                  <?php if ($p['attendance_status'] === 'attended'): ?>
                    <?= attendanceBadge('attended') ?>
                  <?php elseif ($p['attendance_status'] === 'absent'): ?>
                    <span class="badge badge-cancelled">Absent</span>
                  <?php else: ?>
                    <span class="badge badge-upcoming">Registered</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:var(--text-muted);">
                  <?= date('M j, Y g:i A', strtotime($p['registered_at'])) ?>
                </td>
                <td>
                  <a href="<?= SITE_URL ?>/certificate.php?token=<?= urlencode($p['registration_token']) ?>"
                     target="_blank" class="btn btn-icon btn-secondary btn-sm" title="Certificate"><?= icon('award', 18) ?></a>
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
