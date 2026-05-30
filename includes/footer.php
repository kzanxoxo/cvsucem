<?php ?>
<footer class="footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-brand">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <?php $brandMarkSize = 32; $brandMarkClass = ''; include __DIR__ . '/brand-mark.php'; ?>
        <span class="navbar-brand-name"><?= e(SITE_SHORT) ?><span>Events</span></span>
      </div>
      <p><?= e(UNIVERSITY_NAME) ?> (<?= e(SITE_SHORT) ?>) — <?= e(UNIVERSITY_TAGLINE) ?>. Your digital gateway to campus events and activities.</p>
    </div>

    <div>
      <div class="footer-heading">Quick Links</div>
      <ul class="footer-links">
        <li><a href="<?= SITE_URL ?>/index.php" class="icon-inline"><?= icon('home', 16) ?> Home</a></li>
        <li><a href="<?= SITE_URL ?>/events.php" class="icon-inline"><?= icon('calendar', 16) ?> Events</a></li>
        <li><a href="<?= SITE_URL ?>/about.php" class="icon-inline"><?= icon('file-text', 16) ?> About</a></li>
        <li><a href="<?= SITE_URL ?>/login.php" class="icon-inline"><?= icon('lock', 16) ?> Admin Login</a></li>
      </ul>
    </div>

    <div>
      <div class="footer-heading">Contact</div>
      <ul class="footer-links">
        <li><a href="mailto:<?= e(CONTACT_EMAIL) ?>" class="icon-inline"><?= icon('mail', 16) ?> <?= e(CONTACT_EMAIL) ?></a></li>
        <li><a href="tel:+639123456789" class="icon-inline"><?= icon('phone', 16) ?> 09123456789</a></li>
        <li class="icon-inline"><?= icon('map-pin', 16) ?> <span>Indang, Cavite, Philippines</span></li>
        <li class="icon-inline"><?= icon('clock', 16) ?> <span>Mon–Fri, 8AM–5PM</span></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <span>© <?= date('Y') ?> <?= e(UNIVERSITY_NAME) ?> — <?= e(SITE_NAME) ?></span>
    <span>Campus Event Management System</span>
  </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
