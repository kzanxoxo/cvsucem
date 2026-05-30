<?php
require_once __DIR__ . '/../includes/auth-guard.php';

$adminActivePage = 'dashboard';
syncEventStatuses($pdo);

$totalEvents    = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status != 'cancelled'")->fetchColumn();
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
$totalPart      = (int)$pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn();
$attended       = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE attendance_status = 'attended'")->fetchColumn();
$todayReg       = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE DATE(registered_at) = CURDATE()")->fetchColumn();
$ongoingEvents  = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'ongoing'")->fetchColumn();

$recentPart = $pdo->query("
    SELECT p.student_name, p.student_id, p.registered_at, p.attendance_status,
           e.title AS event_title, e.id AS event_id
    FROM participants p
    JOIN events e ON p.event_id = e.id
    ORDER BY p.registered_at DESC
    LIMIT 8
")->fetchAll();

$upcomingList = $pdo->query("
    SELECT e.*, c.name AS category_name, c.icon AS category_icon,
           (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS participant_count
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE e.status IN ('upcoming','ongoing')
    ORDER BY e.event_date ASC
    LIMIT 5
")->fetchAll();

$breakdown = $pdo->query("
    SELECT status, COUNT(*) as cnt FROM events GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$attendRate = $totalPart > 0 ? round(($attended / $totalPart) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | <?= e(SITE_NAME) ?></title>
  <?php include __DIR__ . '/../includes/head-admin.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>
<div class="admin-layout">

  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main" role="main">
    
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <?php include __DIR__ . '/../includes/sidebar-toggle.php'; ?>
        <div>
          <div class="admin-page-title">Dashboard</div>
          <div class="meta-line">
            <?= date('l, F j, Y') ?>
          </div>
        </div>
      </div>
      <div class="admin-topbar-actions">
        <a href="<?= SITE_URL ?>/admin/events/create.php" class="btn btn-primary btn-sm icon-inline" id="quickCreateBtn">
          <?= icon('plus', 16) ?> New Event
        </a>
      </div>
    </div>

    <div class="admin-content">

      
      <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" role="alert"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      
      <div class="page-intro" style="margin-bottom:28px;">
        <h1>Welcome back, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?>!</h1>
        <p>Here's what's happening on your campus today.</p>
      </div>

      
      <div class="dashboard-stats">
        <div class="dash-stat">
          <div class="dash-stat-top">
            <?= dashIcon('calendar', 'purple') ?>
            <span class="dash-stat-trend up">Live</span>
          </div>
          <div class="dash-stat-value"><?= $totalEvents ?></div>
          <div class="dash-stat-label">Total Events</div>
          <div class="dash-stat-bar"><div class="dash-stat-bar-fill purple" style="width:<?= min(100,$totalEvents*5) ?>%"></div></div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-top">
            <?= dashIcon('zap', 'cyan') ?>
            <?php if ($ongoingEvents): ?>
              <span class="dash-stat-trend up">Ongoing: <?= $ongoingEvents ?></span>
            <?php endif; ?>
          </div>
          <div class="dash-stat-value"><?= $upcomingEvents ?></div>
          <div class="dash-stat-label">Upcoming Events</div>
          <div class="dash-stat-bar"><div class="dash-stat-bar-fill cyan" style="width:<?= $totalEvents ? min(100,round($upcomingEvents/$totalEvents*100)) : 0 ?>%"></div></div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-top">
            <?= dashIcon('users', 'pink') ?>
            <span class="dash-stat-trend up">+<?= $todayReg ?> today</span>
          </div>
          <div class="dash-stat-value"><?= number_format($totalPart) ?></div>
          <div class="dash-stat-label">Total Registrations</div>
          <div class="dash-stat-bar"><div class="dash-stat-bar-fill pink" style="width:<?= min(100,$totalPart/5) ?>%"></div></div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat-top">
            <?= dashIcon('check-circle', 'green') ?>
            <span class="dash-stat-trend <?= $attendRate >= 50 ? 'up' : 'down' ?>"><?= $attendRate ?>%</span>
          </div>
          <div class="dash-stat-value"><?= number_format($attended) ?></div>
          <div class="dash-stat-label">Confirmed Attendances</div>
          <div class="dash-stat-bar"><div class="dash-stat-bar-fill green" style="width:<?= $attendRate ?>%"></div></div>
        </div>
      </div>

      
      <div class="dashboard-grid">

        
        <div class="dashboard-panel">
          <div class="panel-header">
            <div class="panel-title icon-inline"><?= icon('file-text', 18) ?> Recent Registrations</div>
            <a href="<?= SITE_URL ?>/admin/participants/index.php" class="btn btn-sm btn-secondary">View All</a>
          </div>
          <div style="overflow-x:auto;">
            <table aria-label="Recent registrations">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Event</th>
                  <th>Status</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentPart)): ?>
                  <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px;">No registrations yet.</td></tr>
                <?php else: foreach ($recentPart as $p): ?>
                  <tr>
                    <td>
                      <strong><?= e($p['student_name']) ?></strong>
                      <div style="font-size:.75rem;color:var(--text-muted);"><?= e($p['student_id']) ?></div>
                    </td>
                    <td>
                      <a href="<?= SITE_URL ?>/admin/participants/event.php?id=<?= $p['event_id'] ?>"
                         style="color:var(--text-primary);font-size:.85rem;"><?= e($p['event_title']) ?></a>
                    </td>
                    <td>
                      <?php if ($p['attendance_status'] === 'attended'): ?>
                        <?= attendanceBadge('attended') ?>
                      <?php elseif ($p['attendance_status'] === 'absent'): ?>
                        <span class="badge badge-cancelled">Absent</span>
                      <?php else: ?>
                        <span class="badge badge-upcoming">Registered</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--text-muted);">
                      <?= date('M j, g:i A', strtotime($p['registered_at'])) ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        
        <div style="display:flex;flex-direction:column;gap:20px;">

          
          <div class="dashboard-panel">
            <div class="panel-header">
              <div class="panel-title icon-inline"><?= icon('bar-chart', 18) ?> Event Status</div>
            </div>
            <div class="panel-body">
              <div class="event-progress">
                <?php
                  $statusConfig = [
                    'upcoming'  => ['label'=>'Upcoming',  'color'=>'var(--grad-primary)'],
                    'ongoing'   => ['label'=>'Ongoing',   'color'=>'var(--grad-green)'],
                    'completed' => ['label'=>'Completed', 'color'=>'linear-gradient(135deg,#9999b3,#55556a)'],
                    'cancelled' => ['label'=>'Cancelled', 'color'=>'var(--grad-pink)'],
                  ];
                  $totalAll = array_sum($breakdown) ?: 1;
                  foreach ($statusConfig as $key => $cfg):
                    $cnt = $breakdown[$key] ?? 0;
                    $pct = round($cnt / $totalAll * 100);
                ?>
                  <div class="event-progress-item">
                    <div class="event-progress-top">
                      <span class="event-progress-label"><?= statusDot($key) ?><?= $cfg['label'] ?></span>
                      <span class="event-progress-count"><?= $cnt ?></span>
                    </div>
                    <div class="event-progress-bar">
                      <div class="event-progress-fill" data-fill="<?= $pct ?>"
                           style="width:0%;background:<?= $cfg['color'] ?>"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          
          <div class="dashboard-panel">
            <div class="panel-header">
              <div class="panel-title icon-inline"><?= icon('clock', 18) ?> Next Events</div>
              <a href="<?= SITE_URL ?>/admin/events/index.php" class="btn btn-sm btn-secondary">All</a>
            </div>
            <div class="panel-body" style="padding-top:8px;">
              <?php if (empty($upcomingList)): ?>
                <p style="color:var(--text-muted);font-size:.85rem;">No upcoming events.</p>
              <?php else: foreach ($upcomingList as $ev):
                $cnt = (int)$ev['participant_count'];
                $max = (int)$ev['max_participants'];
                $pct = $max > 0 ? min(100, round($cnt/$max*100)) : 0;
              ?>
                <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);">
                  <div style="display:flex;justify-content:space-between;align-items:start;gap:8px;">
                    <div style="flex:1;min-width:0;">
                      <div style="font-size:.85rem;font-weight:600;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($ev['title']) ?>
                      </div>
                      <div style="font-size:.74rem;color:var(--text-muted);margin-top:2px;">
                        <?= formatDate($ev['event_date']) ?>
                      </div>
                    </div>
                    <span class="badge <?= $ev['status']==='ongoing'?'badge-ongoing':'badge-upcoming' ?>" style="white-space:nowrap;flex-shrink:0;">
                      <?= ucfirst($ev['status']) ?>
                    </span>
                  </div>
                  <?php if ($max > 0): ?>
                    <div style="margin-top:6px;">
                      <div class="capacity-bar" style="height:3px;">
                        <div class="capacity-bar-fill" data-fill="<?= $pct ?>" style="width:0%;height:3px;"></div>
                      </div>
                      <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;"><?= $cnt ?>/<?= $max ?> registered</div>
                    </div>
                  <?php else: ?>
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px;"><?= $cnt ?> registered</div>
                  <?php endif; ?>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

          
          <div class="dashboard-panel">
            <div class="panel-header">
              <div class="panel-title icon-inline"><?= icon('zap', 18) ?> Quick Stats</div>
            </div>
            <div class="panel-body">
              <div class="quick-stats">
                <div class="quick-stat-item">
                  <span class="quick-stat-label">Attendance Rate</span>
                  <span class="quick-stat-value" style="color:var(--success);"><?= $attendRate ?>%</span>
                </div>
                <div class="quick-stat-item">
                  <span class="quick-stat-label">Today's Registrations</span>
                  <span class="quick-stat-value"><?= $todayReg ?></span>
                </div>
                <div class="quick-stat-item">
                  <span class="quick-stat-label">Ongoing Events</span>
                  <span class="quick-stat-value" style="color:var(--success);"><?= $ongoingEvents ?></span>
                </div>
                <div class="quick-stat-item">
                  <span class="quick-stat-label">Avg. Participants/Event</span>
                  <span class="quick-stat-value"><?= $totalEvents > 0 ? round($totalPart/$totalEvents,1) : 0 ?></span>
                </div>
                <div class="quick-stat-item">
                  <span class="quick-stat-label">Registered Users</span>
                  <span class="quick-stat-value"><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?></span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
