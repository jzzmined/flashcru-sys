<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$result = $conn->query("
    SELECT i.id, it.name AS type_name, u.full_name AS reporter,
           b.name AS barangay, i.created_at
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN users           u  ON i.user_id = u.user_id
    LEFT JOIN barangays       b  ON i.barangay_id = b.id
    WHERE i.status_id = 1
    ORDER BY i.created_at DESC
    LIMIT 3
");

$notifs = [];
while ($row = $result->fetch_assoc()) {
    $notifs[] = [
        'id'       => $row['id'],
        'type'     => $row['type_name'],
        'reporter' => $row['reporter'] ?? 'Unknown',
        'barangay' => $row['barangay'] ?? '',
        'time'     => date('M d, Y \a\t h:i A', strtotime($row['created_at'])),
    ];
}

echo json_encode(['count' => count($notifs), 'items' => $notifs]);
?>