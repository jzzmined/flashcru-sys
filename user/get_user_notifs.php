<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$uid = (int) $_SESSION['user_id'];

$result = $conn->query("
    SELECT i.id, it.name AS type_name, i.status_id,
           t.team_name, i.updated_at
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    WHERE i.user_id = $uid
      AND i.status_id IN (2, 3, 4, 5)
    ORDER BY i.updated_at DESC
    LIMIT 3
");

$notifs = [];
while ($row = $result->fetch_assoc()) {
    $msg  = '';
    $icon = 'bi-bell-fill';
    $color = '#3b82f6';

    if ($row['status_id'] == 2 || $row['status_id'] == 3) {
        $team  = $row['team_name'] ? htmlspecialchars($row['team_name']) : 'a response team';
        $label = $row['status_id'] == 2 ? 'Assigned' : 'Responding';
        $msg   = "Report #" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ") assigned to {$team}. Status: {$label}.";
        $icon  = 'bi-people-fill';
        $color = '#f59e0b';
    } elseif ($row['status_id'] == 4) {
        $msg   = "Report #" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ") has been Resolved. Thank you!";
        $icon  = 'bi-check-circle-fill';
        $color = '#22c55e';
    } elseif ($row['status_id'] == 5) {
        $msg   = "Report #" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ") has been Cancelled.";
        $icon  = 'bi-x-circle-fill';
        $color = '#94a3b8';
    }

    if ($msg) {
        $notifs[] = [
            'id'    => $row['id'],
            'msg'   => $msg,
            'icon'  => $icon,
            'color' => $color,
            'time'  => date('M d, Y \a\t h:i A', strtotime($row['updated_at'])),
        ];
    }
}

echo json_encode(['count' => count($notifs), 'items' => $notifs, 'ids' => implode(',', array_column($notifs, 'time'))]);
?>