<?php

/**
 * Sanitize output to prevent XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user role
 */
function getUserRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Redirect to URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Flash message: set
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash message: get and clear
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate a cryptographically secure random token
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Format date nicely
 */
function formatDate(string $date): string {
    return date('F j, Y', strtotime($date));
}

/**
 * Format time nicely
 */
function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

/**
 * Format date and time
 */
function formatDateTime(string $date, string $time): string {
    return formatDate($date) . ' at ' . formatTime($time);
}

/**
 * Count participants for an event
 */
function getParticipantCount(PDO $pdo, int $eventId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE event_id = ?");
    $stmt->execute([$eventId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Count attended participants for an event
 */
function getAttendedCount(PDO $pdo, int $eventId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE event_id = ? AND attendance_status = 'attended'");
    $stmt->execute([$eventId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Check if email is already registered for an event
 */
function isAlreadyRegistered(PDO $pdo, int $eventId, string $email): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE event_id = ? AND student_email = ?");
    $stmt->execute([$eventId, $email]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Get event by ID
 */
function getEvent(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT e.*, u.name AS organizer_name, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    return $event ?: null;
}

/**
 * Get all categories
 */
function getCategories(PDO $pdo): array {
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Log activity
 */
function logActivity(PDO $pdo, ?int $userId, string $action, string $description = ''): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $description, $ip]);
}

/**
 * Get event status badge HTML
 */
function statusBadge(string $status): string {
    $map = [
        'upcoming'  => ['class' => 'badge-upcoming',  'label' => 'Upcoming'],
        'ongoing'   => ['class' => 'badge-ongoing',   'label' => 'Ongoing'],
        'completed' => ['class' => 'badge-completed', 'label' => 'Completed'],
        'cancelled' => ['class' => 'badge-cancelled', 'label' => 'Cancelled'],
    ];
    $b = $map[$status] ?? ['class' => '', 'label' => ucfirst($status)];
    return "<span class=\"badge {$b['class']}\">{$b['label']}</span>";
}

/**
 * Handle file upload for event images
 */
function uploadEventImage(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes)) return null;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateToken(16) . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return null;
}

/**
 * Generate QR code image URL using Google Charts API
 */
function getQRCodeUrl(string $data, int $size = 200): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}

/**
 * Auto-update event statuses based on date
 */
function syncEventStatuses(PDO $pdo): void {
    $today = date('Y-m-d');
    $pdo->prepare("UPDATE events SET status = 'completed' WHERE event_date < ? AND status NOT IN ('cancelled','completed')")->execute([$today]);
    $pdo->prepare("UPDATE events SET status = 'ongoing' WHERE event_date = ? AND status = 'upcoming'")->execute([$today]);
}

/**
 * Public shareable URL for student event registration (no account required)
 */
function getEventRegistrationUrl(int $eventId): string {
    return SITE_URL . '/register-event.php?event=' . $eventId;
}

/**
 * Check-in QR URL for an event
 */
function getEventCheckinUrl(string $qrToken): string {
    return SITE_URL . '/checkin.php?token=' . urlencode($qrToken);
}

/**
 * Whether the logged-in user may manage this event
 */
function canManageEvent(array $event): bool {
    if (!isLoggedIn()) {
        return false;
    }
    if (getUserRole() === 'admin') {
        return true;
    }
    return (int)($event['organizer_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Require event management permission or redirect
 */
function requireEventAccess(PDO $pdo, int $eventId): array {
    $event = getEvent($pdo, $eventId);
    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect(SITE_URL . '/admin/events/index.php');
    }
    if (!canManageEvent($event)) {
        setFlash('error', 'You do not have permission to manage this event.');
        redirect(SITE_URL . '/admin/events/index.php');
    }
    return $event;
}

require_once __DIR__ . '/icons.php';
