<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect(SITE_URL . '/admin/events/index.php');
}

$event = requireEventAccess($pdo, $id);

if ($event['image'] && file_exists(UPLOAD_DIR . $event['image'])) {
    @unlink(UPLOAD_DIR . $event['image']);
}

$stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
$stmt->execute([$id]);

logActivity($pdo, $_SESSION['user_id'], 'event_deleted', "Deleted event #$id: {$event['title']}");
setFlash('success', 'Event deleted successfully.');
redirect(SITE_URL . '/admin/events/index.php');
