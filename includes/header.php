<?php

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDesc  = $pageDesc  ?? UNIVERSITY_NAME . ' — discover and register for campus events online.';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDesc) ?>">
  <meta name="theme-color" content="#006633">
  <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
<?php include __DIR__ . '/head-public.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>

<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="navbar-inner">
    <a href="<?= SITE_URL ?>/index.php" class="navbar-brand" aria-label="<?= e(SITE_NAME) ?> Home">
      <?php $brandMarkClass = 'navbar-logo'; include __DIR__ . '/brand-mark.php'; ?>
      <span class="navbar-brand-name"><?= e(SITE_SHORT) ?><span>Events</span></span>
    </a>

    <ul class="navbar-nav" id="mainNav" role="menubar">
      <li role="none"><a href="<?= SITE_URL ?>/index.php"   class="<?= $activePage==='home'   ? 'active' : '' ?>" role="menuitem">Home</a></li>
      <li role="none"><a href="<?= SITE_URL ?>/events.php"  class="<?= $activePage==='events' ? 'active' : '' ?>" role="menuitem">Events</a></li>
      <li role="none"><a href="<?= SITE_URL ?>/about.php"   class="<?= $activePage==='about'  ? 'active' : '' ?>" role="menuitem">About</a></li>
    </ul>

    <div class="navbar-actions">
      <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-sm btn-secondary icon-inline"><?= icon('layout-dashboard', 16) ?> Dashboard</a>
        <a href="<?= SITE_URL ?>/logout.php" class="btn btn-sm btn-secondary">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn btn-sm btn-primary" id="loginBtn">Admin Login</a>
      <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
