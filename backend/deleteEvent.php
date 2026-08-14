<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event id']);
    exit;
}

$stmt = $conn->prepare("SELECT image FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if (!$existing) {
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $uploadDir = __DIR__ . '/../frontend/assets/images/events_uploads/';
    if (!empty($existing['image']) && file_exists($uploadDir . $existing['image'])) {
        unlink($uploadDir . $existing['image']);
    }
    echo json_encode(['status' => 'success', 'message' => 'Event deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting event']);
}
?>
