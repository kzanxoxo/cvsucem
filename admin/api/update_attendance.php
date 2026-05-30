<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$participantId = (int)($input['id'] ?? 0);
$status        = $input['status'] ?? '';

if (!$participantId || !in_array($status, ['registered', 'attended', 'absent'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, e.organizer_id FROM participants p
    JOIN events e ON p.event_id = e.id
    WHERE p.id = ?
");
$stmt->execute([$participantId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Participant not found']);
    exit;
}

if (getUserRole() !== 'admin' && (int)$row['organizer_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$checkedIn = $status === 'attended' ? date('Y-m-d H:i:s') : null;
if ($status !== 'attended') {
    $pdo->prepare("UPDATE participants SET attendance_status = ?, checked_in_at = NULL WHERE id = ?")
        ->execute([$status, $participantId]);
} else {
    $pdo->prepare("UPDATE participants SET attendance_status = 'attended', checked_in_at = COALESCE(checked_in_at, NOW()) WHERE id = ?")
        ->execute([$participantId]);
}

logActivity($pdo, $_SESSION['user_id'], 'attendance_updated', "Participant #$participantId set to $status");
echo json_encode(['success' => true, 'status' => $status]);
