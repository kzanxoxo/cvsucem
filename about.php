<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle  = 'About ' . SITE_NAME;
$pageDesc   = 'About the ' . UNIVERSITY_NAME . ' Campus Event Management System.';
$activePage = 'about';

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="hero-eyebrow fade-up" style="margin-bottom:20px;">About Us</div>
    <h1 class="fade-up" style="font-size:clamp(2rem,5vw,3.5rem);">
      <?= e(UNIVERSITY_NAME) ?><br>
      <span class="gradient-text">Event Management System</span>
    </h1>
    <p class="fade-up" style="max-width:560px;font-size:1.05rem;margin-top:14px;">
      <?= e(SITE_NAME) ?> is the digital platform for <?= e(SITE_SHORT) ?> to create events, manage participants, track attendance via QR check-in, and generate reports — built for Truth, Excellence, and Service.
    </p>
  </div>
</div>

<section class="section" aria-label="Mission">
  <div class="container">
    <div class="about-grid">
      <div class="fade-up">
        <div class="section-eyebrow">Our Mission</div>
        <h2 class="section-title">Making Campus Events Accessible to Everyone</h2>
        <p style="margin-bottom:20px;">
          Every CvSU student deserves an easy way to discover, join, and celebrate campus life. This system bridges organizers and students through a seamless registration experience — without requiring student accounts.
        </p>
        <p style="margin-bottom:28px;">
          From academic seminars to inter-department sports competitions, our platform handles everything — event creation, registration, attendance, and digital certificates — all in one place.
        </p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="events.php" class="btn btn-primary icon-inline" id="exploreEventsBtn"><?= icon('calendar', 18) ?> Explore Events</a>
          <a href="register.php" class="btn btn-secondary icon-inline" id="getStartedBtn"><?= icon('settings', 18) ?> Get Started</a>
        </div>
      </div>
      <!-- feature cards removed per request -->
    </div>
  </div>
</section>

<section class="section" style="background:rgba(255,255,255,.015);border-top:1px solid var(--glass-border);border-bottom:1px solid var(--glass-border);" aria-label="Platform stats">
  <div class="container text-center">
    <div class="section-eyebrow fade-up">By the Numbers</div>
    <h2 class="section-title fade-up" style="margin-bottom:40px;">Platform at a Glance</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;">
      <?php
        $stats = [
          ['calendar', $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(), 'Total Events Created'],
          ['users', $pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn(), 'Student Registrations'],
          ['check-circle', $pdo->query("SELECT COUNT(*) FROM participants WHERE attendance_status='attended'")->fetchColumn(), 'Confirmed Attendances'],
        ];
        $tones = ['purple', 'pink', 'green'];
        foreach ($stats as $i => [$iconName, $val, $label]):
      ?>
        <div class="stat-card fade-up <?= $tones[$i] ?>" style="transition-delay:<?= $i * .08 ?>s;text-align:center;">
          <div class="stat-pill-icon"><?= icon($iconName, 22) ?></div>
          <div class="stat-card-value" data-count="<?= (int)$val ?>"><?= (int)$val ?></div>
          <div class="stat-card-label"><?= $label ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-label="QR Check-in feature">
  <div class="container">
    <div class="section-header text-center fade-up">
      <div class="section-eyebrow">Unique Feature</div>
      <h2 class="section-title">QR Code Attendance + Digital Certificates</h2>
      <p class="section-subtitle">Our standout features for <?= e(SITE_SHORT) ?> — QR attendance and instant digital certificates.</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">
      <?php
        $steps = [
          ['qr-code', '1. Event QR Code', 'Each event automatically gets a unique QR code upon creation. Organizers display it on screens at the venue entrance.'],
          ['smartphone', '2. Student Scans', 'Students open their phone camera, scan the QR code, and land on the check-in page — no app download needed.'],
          ['key', '3. Enter Token', 'The student enters their registration token (received at signup) to verify their identity and mark attendance.'],
          ['award', '4. Get Certificate', 'After successful check-in, students instantly download a personalized digital participation certificate.'],
        ];
        foreach ($steps as $i => [$iconName, $title, $desc]):
      ?>
        <div class="fade-up" style="transition-delay:<?= $i*.08 ?>s;">
          <div style="padding:28px;background:var(--bg-card);border:1px solid var(--glass-border);border-radius:var(--radius-lg);height:100%;transition:all var(--transition);"
               onmouseover="this.style.borderColor='rgba(102,126,234,.4)';this.style.background='rgba(102,126,234,.05)'"
               onmouseout="this.style.borderColor='';this.style.background=''">
            <div class="feature-icon-wrap" style="margin-bottom:14px;"><?= icon($iconName, 26) ?></div>
            <h3 style="font-size:1rem;margin-bottom:8px;"><?= $title ?></h3>
            <p style="font-size:.85rem;"><?= $desc ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Technology / "Built With" section removed per request -->

<section class="section fade-up" aria-label="Call to action">
  <div class="container">
    <div style="background:var(--grad-hero);border:1px solid rgba(102,126,234,.3);border-radius:var(--radius-xl);padding:60px 40px;text-align:center;">
      <h2 style="font-size:clamp(1.6rem,3vw,2.5rem);margin-bottom:12px;">
        Ready to Transform Your<br><span class="gradient-text">Campus Events?</span>
      </h2>
      <p style="color:var(--text-secondary);margin-bottom:28px;max-width:460px;margin-left:auto;margin-right:auto;">
        CvSU organizers use this system to streamline event management across colleges and campuses.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="register.php" class="btn btn-primary btn-lg icon-inline" id="aboutCtaRegisterBtn"><?= icon('settings', 20) ?> Register as Organizer</a>
        <a href="events.php"   class="btn btn-secondary btn-lg icon-inline" id="aboutCtaEventsBtn"><?= icon('calendar', 20) ?> Browse Events</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
