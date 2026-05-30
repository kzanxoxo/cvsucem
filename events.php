<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle  = 'Browse Events';
$pageDesc   = 'Discover all upcoming campus events — filter by category, date, and status.';
$activePage = 'events';

syncEventStatuses($pdo);

$search    = trim($_GET['search']   ?? '');
$catFilter = (int)($_GET['category'] ?? 0);
$status    = trim($_GET['status']   ?? '');

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($catFilter) {
    $where[]  = "e.category_id = ?";
    $params[] = $catFilter;
}
if ($status) {
    $where[]  = "e.status = ?";
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
           u.name AS organizer_name,
           (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS participant_count
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.organizer_id = u.id
    WHERE $whereSql
    ORDER BY
        CASE e.status WHEN 'ongoing' THEN 0 WHEN 'upcoming' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END,
        e.event_date ASC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = getCategories($pdo);

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep" aria-hidden="true">›</span>
      <span>Events</span>
    </nav>
    <h1><?= e(SITE_SHORT) ?> Campus Events</h1>
    <p>Browse and register for <?= e(UNIVERSITY_NAME) ?> events. No student account required.</p>
  </div>
</div>

<section class="section" style="padding-top:0;" aria-label="Event listings">
  <div class="container">

    
    <form method="GET" action="events.php" class="filter-bar" id="filterForm" role="search" aria-label="Filter events">
      <div class="filter-search">
        <label for="eventSearch" class="sr-only">Search events</label>
        <input type="text" name="search" id="eventSearch" class="form-control"
               placeholder="Search events, locations…" value="<?= e($search) ?>">
      </div>
      <div>
        <label for="categoryFilter" class="sr-only">Filter by category</label>
        <select name="category" id="categoryFilter" class="form-control" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="statusFilter" class="sr-only">Filter by status</label>
        <select name="status" id="statusFilter" class="form-control" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="upcoming"  <?= $status === 'upcoming'  ? 'selected' : '' ?>>Upcoming</option>
          <option value="ongoing"   <?= $status === 'ongoing'   ? 'selected' : '' ?>>Ongoing</option>
          <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm" id="searchBtn">Search</button>
      <?php if ($search || $catFilter || $status): ?>
        <a href="events.php" class="btn btn-secondary btn-sm icon-inline" id="clearFilterBtn"><?= icon('x', 16) ?> Clear</a>
      <?php endif; ?>
    </form>

    
    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:24px;">
      Found <strong style="color:var(--text-primary);"><?= count($events) ?></strong> event<?= count($events) !== 1 ? 's' : '' ?>
      <?php if ($search): ?> for "<em><?= e($search) ?></em>"<?php endif; ?>
    </p>

    
    <?php if (empty($events)): ?>
      <div class="empty-state" id="noResults">
        <?= stateIcon('search', 'default') ?>
        <h3>No events found</h3>
        <p>Try adjusting your filters or search terms.</p>
        <a href="events.php" class="btn btn-primary" id="clearFiltersBtn2">Clear All Filters</a>
      </div>
    <?php else: ?>
      <div class="event-grid" id="eventGrid">
        <?php foreach ($events as $i => $ev): ?>
          <?php
            $count  = (int)$ev['participant_count'];
            $max    = (int)$ev['max_participants'];
            $pct    = ($max > 0) ? min(100, round(($count / $max) * 100)) : 0;
            $isFull = ($max > 0 && $count >= $max);
            $imgUrl = $ev['image'] ? UPLOAD_URL . e($ev['image']) : null;
            $cancelled = $ev['status'] === 'cancelled';
            $completed = $ev['status'] === 'completed';
          ?>
          <article class="event-card fade-up" style="transition-delay:<?= min($i,8) * 0.06 ?>s"
                   data-title="<?= e($ev['title']) ?>"
                   data-location="<?= e($ev['location']) ?>"
                   data-status="<?= e($ev['status']) ?>"
                   data-category="<?= e(strtolower($ev['category_name'] ?? '')) ?>">

            <?php if ($imgUrl): ?>
              <img src="<?= $imgUrl ?>" alt="<?= e($ev['title']) ?>" class="event-card-image" loading="lazy">
            <?php else: ?>
              <div class="event-card-image-placeholder"
                   style="background:linear-gradient(135deg,<?= e($ev['category_color'] ?? '#667eea') ?>22,<?= e($ev['category_color'] ?? '#764ba2') ?>11);">
                <?= categoryHeroMark(['name' => $ev['category_name'] ?? 'Event', 'color' => $ev['category_color'] ?? '#1a9e5c']) ?>
              </div>
            <?php endif; ?>

            <div class="event-card-body">
              <div class="event-card-meta">
                <?= statusBadge($ev['status']) ?>
                <?php if ($ev['category_name']): ?>
                  <span class="badge badge-category"><?= e($ev['category_name']) ?></span>
                <?php endif; ?>
                <?php if ($ev['is_featured']): ?>
                  <?= featuredBadge() ?>
                <?php endif; ?>
              </div>

              <h2 class="event-card-title"><?= e($ev['title']) ?></h2>

              <div class="event-card-info">
                <div class="event-card-info-item icon-inline"><?= icon('calendar', 18) ?> <?= formatDate($ev['event_date']) ?></div>
                <div class="event-card-info-item icon-inline"><?= icon('clock', 18) ?> <?= formatTime($ev['start_time']) ?><?php if ($ev['end_time']): ?> – <?= formatTime($ev['end_time']) ?><?php endif; ?></div>
                <div class="event-card-info-item icon-inline"><?= icon('map-pin', 18) ?> <?= e($ev['location']) ?></div>
                <div class="event-card-info-item icon-inline"><?= icon('user', 18) ?> By <?= e($ev['organizer_name']) ?></div>
              </div>

              <?php if ($max > 0): ?>
                <div style="margin-top:6px;">
                  <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);margin-bottom:4px;">
                    <span><?= $count ?> / <?= $max ?> registered</span>
                    <span><?= $pct ?>%</span>
                  </div>
                  <div class="capacity-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="capacity-bar-fill" data-fill="<?= $pct ?>" style="width:0%"></div>
                  </div>
                </div>
              <?php else: ?>
                <div class="event-card-info-item icon-inline" style="font-size:var(--text-sm);color:var(--text-muted);"><?= icon('users', 16) ?> <?= $count ?> registered · Unlimited spots</div>
              <?php endif; ?>
            </div>

            <div class="event-card-footer">
              <a href="event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-secondary" id="detailsBtn<?= $ev['id'] ?>">View Details</a>
              <?php if ($cancelled): ?>
                <span class="badge badge-cancelled">Cancelled</span>
              <?php elseif ($completed): ?>
                <span class="badge badge-completed">Completed</span>
              <?php elseif ($isFull): ?>
                <span class="badge badge-cancelled">Full</span>
              <?php else: ?>
                <a href="event-detail.php?id=<?= $ev['id'] ?>#register" class="btn btn-sm btn-primary" id="registerBtn<?= $ev['id'] ?>">Register →</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
