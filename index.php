<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle  = 'Home — ' . UNIVERSITY_NAME;
$pageDesc   = SITE_NAME . ' — discover, register, and participate in ' . SITE_SHORT . ' campus events online.';
$activePage = 'home';

syncEventStatuses($pdo);

$featured = $pdo->query("
    SELECT e.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
           u.name AS organizer_name,
           (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS participant_count
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.organizer_id = u.id
    WHERE e.status IN ('upcoming','ongoing') AND e.is_featured = 1
    ORDER BY e.event_date ASC
    LIMIT 3
")->fetchAll();

$totalEvents  = $pdo->query("SELECT COUNT(*) FROM events WHERE status != 'cancelled'")->fetchColumn();
$totalPart    = $pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn();
$upcomingEvts = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
$categories   = getCategories($pdo);

include 'includes/header.php';
?>

<section class="hero" aria-label="Hero banner">
  <div class="container">
    <div class="hero-eyebrow fade-up">
      <?= e(UNIVERSITY_NAME) ?> · <?= e(UNIVERSITY_TAGLINE) ?>
    </div>
    <h1 class="fade-up" style="transition-delay:.05s">
      Discover What's<br>
      <span class="gradient-text">Happening at CvSU</span>
    </h1>
    <p class="fade-up" style="transition-delay:.1s">
      Official campus event portal for Cavite State University students and organizers — tech summits, sports fests, seminars, and more. Register in seconds, no student account needed.
    </p>
    <div class="hero-actions fade-up" style="transition-delay:.15s">
      <a href="events.php" class="btn btn-primary btn-lg icon-inline" id="browseEventsBtn"><?= icon('calendar', 20) ?> Browse Events</a>
      <a href="about.php"  class="btn btn-secondary btn-lg" id="learnMoreBtn">Learn More</a>
    </div>

    <div class="hero-stats fade-up" style="transition-delay:.2s">
      <div class="hero-stat">
        <div class="hero-stat-number" data-count="<?= $totalEvents ?>">0</div>
        <div class="hero-stat-label">Total Events</div>
      </div>
      <div class="hero-divider" aria-hidden="true"></div>
      <div class="hero-stat">
        <div class="hero-stat-number" data-count="<?= $upcomingEvts ?>">0</div>
        <div class="hero-stat-label">Upcoming</div>
      </div>
      <div class="hero-divider" aria-hidden="true"></div>
      <div class="hero-stat">
        <div class="hero-stat-number" data-count="<?= $totalPart ?>">0</div>
        <div class="hero-stat-label">Participants</div>
      </div>
      <div class="hero-divider" aria-hidden="true"></div>
      <div class="hero-stat">
        <div class="hero-stat-number" data-count="<?= count($categories) ?>">0</div>
        <div class="hero-stat-label">Categories</div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($featured)): ?>
<section class="section" aria-label="Featured events">
  <div class="container">
    <div class="section-header fade-up">
      <div class="section-eyebrow">Featured</div>
      <h2 class="section-title">Events You Can't Miss</h2>
      <p class="section-subtitle">Hand-picked upcoming events from our organizers — register now before spots fill up.</p>
    </div>

    <div class="event-grid">
      <?php foreach ($featured as $i => $ev): ?>
        <?php
          $count   = (int)$ev['participant_count'];
          $max     = (int)$ev['max_participants'];
          $pct     = ($max > 0) ? min(100, round(($count / $max) * 100)) : 0;
          $isFull  = ($max > 0 && $count >= $max);
          $imgUrl  = $ev['image'] ? UPLOAD_URL . e($ev['image']) : null;
          $gradients = ['var(--grad-primary)', 'var(--grad-pink)', 'var(--grad-cyan)'];
          $grad    = $gradients[$i % count($gradients)];
        ?>
        <article class="event-card fade-up" style="transition-delay:<?= $i * 0.08 ?>s"
                 data-title="<?= e($ev['title']) ?>" data-status="<?= e($ev['status']) ?>"
                 data-category="<?= e(strtolower($ev['category_name'] ?? '')) ?>">

          <?php if ($imgUrl): ?>
            <img src="<?= $imgUrl ?>" alt="<?= e($ev['title']) ?>" class="event-card-image" loading="lazy">
          <?php else: ?>
            <div class="event-card-image-placeholder" style="background:<?= $grad ?>;opacity:0.15;">
              <?= categoryHeroMark(['name' => $ev['category_name'] ?? 'Event', 'color' => $ev['category_color'] ?? '#1a9e5c']) ?>
            </div>
          <?php endif; ?>

          <div class="event-card-body">
            <div class="event-card-meta">
              <?= statusBadge($ev['status']) ?>
              <?php if ($ev['category_name']): ?>
                <span class="badge badge-category"><?= e($ev['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
            <div class="event-card-info">
              <div class="event-card-info-item icon-inline"><?= icon('calendar', 18) ?> <?= formatDate($ev['event_date']) ?></div>
              <div class="event-card-info-item icon-inline"><?= icon('clock', 18) ?> <?= formatTime($ev['start_time']) ?><?php if ($ev['end_time']): ?> – <?= formatTime($ev['end_time']) ?><?php endif; ?></div>
              <div class="event-card-info-item icon-inline"><?= icon('map-pin', 18) ?> <?= e($ev['location']) ?></div>
            </div>
            <?php if ($max > 0): ?>
              <div style="margin-top:6px;">
                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);margin-bottom:4px;">
                  <span><?= $count ?> registered</span>
                  <span><?= $isFull ? 'Full' : "$max max" ?></span>
                </div>
                <div class="capacity-bar">
                  <div class="capacity-bar-fill" data-fill="<?= $pct ?>" style="width:0%"></div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="event-card-footer">
            <span style="font-size:.8rem;color:var(--text-muted);">By <?= e($ev['organizer_name']) ?></span>
            <?php if ($isFull): ?>
              <span class="badge badge-cancelled">Full</span>
            <?php else: ?>
              <a href="event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary" id="viewEvent<?= $ev['id'] ?>">Register →</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
      <a href="events.php" class="btn btn-secondary btn-lg" id="viewAllBtn">View All Events →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="background:rgba(255,255,255,0.015);border-top:1px solid var(--glass-border);border-bottom:1px solid var(--glass-border);" aria-label="Event categories">
  <div class="container">
    <div class="section-header fade-up text-center">
      <div class="section-eyebrow">Categories</div>
      <h2 class="section-title">Events for Every Interest</h2>
    </div>
    <div class="category-grid">
      <?php foreach ($categories as $i => $cat): ?>
        <a href="events.php?category=<?= $cat['id'] ?>" id="cat<?= $cat['id'] ?>"
           class="fade-up category-card-link" style="text-decoration:none;transition-delay:<?= $i * 0.04 ?>s;">
          <div class="category-card"
               onmouseover="this.style.borderColor='<?= e($cat['color']) ?>50';this.style.background='<?= e($cat['color']) ?>10'"
               onmouseout="this.style.borderColor='';this.style.background=''">
            <div class="category-card-icon"><?= categoryMark($cat, 28) ?></div>
            <div class="category-card-name"><?= e($cat['name']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-label="How it works">
  <div class="container">
    <div class="section-header fade-up text-center">
      <div class="section-eyebrow">Simple Process</div>
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">Participate in campus events in just a few easy steps — no account required for students.</p>
    </div>
    <div class="how-steps-grid">
      <?php
        $steps = [
          ['search', 'Browse Events', 'Explore all upcoming campus events filtered by category, date, or keyword.'],
          ['edit', 'Register', 'Fill in your name, student ID, and email — no account needed.'],
          ['qr-code', 'Get Confirmed', 'Receive your registration token for check-in on the day of the event.'],
          ['check-circle', 'Attend & Check In', 'Scan the QR code at the venue to mark your attendance instantly.'],
        ];
        foreach ($steps as $i => [$iconName, $title, $desc]):
      ?>
        <div class="fade-up" style="transition-delay:<?= $i * 0.08 ?>s;">
          <div class="how-step-card">
            <div class="feature-icon-wrap how-step-icon"><?= icon($iconName, 26) ?></div>
            <div class="how-step-label">Step <?= $i+1 ?></div>
            <h3><?= $title ?></h3>
            <p><?= $desc ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section fade-up" aria-label="Call to action">
  <div class="container">
    <div style="background:var(--grad-hero);border:1px solid rgba(102,126,234,0.3);border-radius:var(--radius-xl);padding:64px 40px;text-align:center;position:relative;overflow:hidden;">
      <div style="position:absolute;inset:0;background:radial-gradient(circle at 50% 50%,rgba(102,126,234,0.1) 0%,transparent 70%);pointer-events:none;"></div>
      <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin-bottom:14px;position:relative;">
        Ready to Join the<br><span class="gradient-text">Campus Experience?</span>
      </h2>
      <p style="color:var(--text-secondary);max-width:480px;margin:0 auto 32px;position:relative;">
        Explore upcoming events and register today. No account needed — just bring your student ID!
      </p>
      <a href="events.php" class="btn btn-primary btn-lg icon-inline" style="position:relative;" id="ctaBrowseBtn"><?= icon('search', 20) ?> Find Events Now</a>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
