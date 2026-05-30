<?php

$adminActivePage = $adminActivePage ?? '';
$userName = $_SESSION['user_name'] ?? 'Admin';
$userRole = $_SESSION['role']      ?? 'admin';
$userInitial = strtoupper(substr($userName, 0, 1));

$pendingCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM participants WHERE attendance_status = 'registered'");
    $pendingCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer' AND is_active = 0");
    $pendingUserCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {}
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="Admin navigation">
  <div class="sidebar-header">
    <?php $brandMarkSize = 34; include __DIR__ . '/brand-mark.php'; ?>
    <div class="sidebar-brand"><?= e(SITE_SHORT) ?><span>Events</span></div>
  </div>

  <div class="sidebar-user">
    <div class="sidebar-avatar" aria-hidden="true"><?= e($userInitial) ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($userName) ?></div>
      <div class="sidebar-user-role"><?= e($userRole) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main</div>

    <a href="<?= SITE_URL ?>/admin/dashboard.php"
       class="sidebar-link <?= $adminActivePage === 'dashboard' ? 'active' : '' ?>"
       id="nav-dashboard">
      <?= navIcon('layout-dashboard') ?>
      Dashboard
    </a>

    <div class="sidebar-section-label">Events</div>

    <a href="<?= SITE_URL ?>/admin/events/index.php"
       class="sidebar-link <?= $adminActivePage === 'events' ? 'active' : '' ?>"
       id="nav-events">
      <?= navIcon('calendar') ?>
      All Events
    </a>

    <a href="<?= SITE_URL ?>/admin/events/create.php"
       class="sidebar-link <?= $adminActivePage === 'create-event' ? 'active' : '' ?>"
       id="nav-create-event">
      <?= navIcon('plus') ?>
      Create Event
    </a>

    <div class="sidebar-section-label">People</div>

    <a href="<?= SITE_URL ?>/admin/participants/index.php"
       class="sidebar-link <?= $adminActivePage === 'participants' ? 'active' : '' ?>"
       id="nav-participants">
      <?= navIcon('users') ?>
      Participants
      <?php if ($pendingCount > 0): ?>
        <span class="sidebar-badge"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>

    <?php if ($userRole === 'admin'): ?>
    <a href="<?= SITE_URL ?>/admin/users/index.php"
       class="sidebar-link <?= $adminActivePage === 'users' ? 'active' : '' ?>"
       id="nav-users">
      <?= navIcon('user-circle') ?>
      Organizer Requests
      <?php if ($pendingUserCount > 0): ?>
        <span class="sidebar-badge"><?= $pendingUserCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Reports</div>

    <a href="<?= SITE_URL ?>/admin/reports/index.php"
       class="sidebar-link <?= $adminActivePage === 'reports' ? 'active' : '' ?>"
       id="nav-reports">
      <?= navIcon('bar-chart') ?>
      Reports & Export
    </a>

    <div class="sidebar-section-label">Site</div>

    <a href="<?= SITE_URL ?>/index.php" class="sidebar-link" target="_blank" rel="noopener" id="nav-view-site">
      <?= navIcon('globe') ?>
      View Public Site
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= SITE_URL ?>/logout.php" class="btn btn-sm btn-secondary w-full icon-inline" id="logoutBtn"
       style="justify-content:center;">
      <?= icon('log-out', 16) ?> Logout
    </a>
  </div>
</aside>
