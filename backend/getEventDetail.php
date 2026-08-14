<?php
// Public endpoint (see getEvents.php for why this has no auth gate).
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Event id is required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nameType, linkInfo, Date, description, image FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    exit;
}

echo json_encode($event, JSON_UNESCAPED_SLASHES);
?>
