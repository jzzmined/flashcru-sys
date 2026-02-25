<?php
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getStatusBadge($status_id) {
    $badges = [
        1 => ['label' => 'Pending',    'color' => 'warning'],
        2 => ['label' => 'Assigned',   'color' => 'info'],
        3 => ['label' => 'Responding', 'color' => 'primary'],
        4 => ['label' => 'Resolved',   'color' => 'success'],
        5 => ['label' => 'Cancelled',  'color' => 'secondary'],
    ];
    
    $data = $badges[$status_id] ?? ['label' => 'Unknown', 'color' => 'secondary'];
    return "<span class='badge bg-{$data['color']}'>" . $data['label'] . "</span>";
}

function logActivity($user_id, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}

function countByStatus($status_label) {
    global $conn;
    $status_map = ['pending' => 1, 'assigned' => 2, 'responding' => 3, 'resolved' => 4, 'cancelled' => 5];
    $id = $status_map[$status_label] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM incidents WHERE status_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'];
}

function totalIncidents() {
    global $conn;
    return $conn->query("SELECT COUNT(*) AS c FROM incidents")->fetch_assoc()['c'];
}

function totalUsers() {
    global $conn;
    return $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
}

function totalTeams() {
    global $conn;
    return $conn->query("SELECT COUNT(*) AS c FROM teams")->fetch_assoc()['c'];
}
?>