<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$type     = $_GET['type'] ?? 'participants';
$eventId  = (int)($_GET['event_id'] ?? 0);

$organizerFilter = $userRole === 'admin' ? '' : ' AND e.organizer_id = ' . (int)$userId;

function sendCsv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    logActivity($GLOBALS['pdo'], $_SESSION['user_id'], 'report_export', "Exported $filename");
    exit;
}

if ($eventId) {
    $event = requireEventAccess($pdo, $eventId);
    $stmt = $pdo->prepare("
        SELECT p.student_name, p.student_email, p.student_id, p.course, p.year_level,
               p.attendance_status, p.registered_at, p.checked_in_at, p.registration_token
        FROM participants p WHERE p.event_id = ?
        ORDER BY p.registered_at
    ");
    $stmt->execute([$eventId]);
    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [
            $r['student_name'], $r['student_email'], $r['student_id'],
            $r['course'], $r['year_level'], $r['attendance_status'],
            $r['registered_at'], $r['checked_in_at'] ?? '', $r['registration_token'],
        ];
    }
    $safeTitle = preg_replace('/[^a-z0-9_-]+/i', '_', substr($event['title'], 0, 40));
    sendCsv(
        "cvsu_event_{$eventId}_{$safeTitle}_" . date('Y-m-d') . '.csv',
        ['Name', 'Email', 'Student ID', 'Course', 'Year', 'Status', 'Registered At', 'Checked In At', 'Token'],
        $rows
    );
}

if ($type === 'events') {
    $sql = "
        SELECT e.title, e.event_date, e.start_time, e.location, e.status, e.max_participants,
               u.name AS organizer,
               (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS registrations,
               (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id AND p.attendance_status = 'attended') AS attended
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        WHERE 1=1 $organizerFilter
        ORDER BY e.event_date DESC
    ";
    $rows = [];
    foreach ($pdo->query($sql)->fetchAll() as $r) {
        $rows[] = [
            $r['title'], $r['event_date'], $r['start_time'], $r['location'], $r['status'],
            $r['max_participants'], $r['organizer'], $r['registrations'], $r['attended'],
        ];
    }
    sendCsv(
        'cvsu_events_summary_' . date('Y-m-d') . '.csv',
        ['Title', 'Date', 'Start Time', 'Location', 'Status', 'Max Participants', 'Organizer', 'Registrations', 'Attended'],
        $rows
    );
}

if ($type === 'attendance') {
    $sql = "
        SELECT e.title, e.event_date, p.student_name, p.student_email, p.student_id,
               p.course, p.year_level, p.checked_in_at
        FROM participants p
        JOIN events e ON p.event_id = e.id
        WHERE p.attendance_status = 'attended' $organizerFilter
        ORDER BY p.checked_in_at DESC
    ";
    $rows = [];
    foreach ($pdo->query($sql)->fetchAll() as $r) {
        $rows[] = [
            $r['title'], $r['event_date'], $r['student_name'], $r['student_email'],
            $r['student_id'], $r['course'], $r['year_level'], $r['checked_in_at'],
        ];
    }
    sendCsv(
        'cvsu_attendance_' . date('Y-m-d') . '.csv',
        ['Event', 'Event Date', 'Name', 'Email', 'Student ID', 'Course', 'Year', 'Checked In At'],
        $rows
    );
}

$sql = "
    SELECT e.title, e.event_date, p.student_name, p.student_email, p.student_id,
           p.course, p.year_level, p.attendance_status, p.registered_at, p.checked_in_at
    FROM participants p
    JOIN events e ON p.event_id = e.id
    WHERE 1=1 $organizerFilter
    ORDER BY p.registered_at DESC
";
$rows = [];
foreach ($pdo->query($sql)->fetchAll() as $r) {
    $rows[] = [
        $r['title'], $r['event_date'], $r['student_name'], $r['student_email'],
        $r['student_id'], $r['course'], $r['year_level'], $r['attendance_status'],
        $r['registered_at'], $r['checked_in_at'] ?? '',
    ];
}
sendCsv(
    'cvsu_participants_' . date('Y-m-d') . '.csv',
    ['Event', 'Event Date', 'Name', 'Email', 'Student ID', 'Course', 'Year', 'Status', 'Registered At', 'Checked In At'],
    $rows
);
