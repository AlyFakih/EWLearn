<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$major = trim($_POST['major'] ?? '');

if ($id <= 0 || $name === '' || $major === '') {
    echo json_encode(['status' => 'error', 'message' => 'Name and major are required']);
    exit;
}

$stmt = $conn->prepare("SELECT image FROM team_members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if (!$existing) {
    echo json_encode(['status' => 'error', 'message' => 'Team member not found']);
    exit;
}

$uploadDir = __DIR__ . '/../frontend/assets/images/team/';
$newFilename = null;

// Image is optional on update: only replace it if a new file was actually chosen.
if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file = $_FILES['image'];

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Image must be smaller than 5MB']);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Image must be jpg, jpeg, png, gif or webp']);
        exit;
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMime, true)) {
        echo json_encode(['status' => 'error', 'message' => 'File is not a valid image']);
        exit;
    }

    $newFilename = uniqid('team_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
        echo json_encode(['status' => 'error', 'message' => 'Could not save image']);
        exit;
    }
}

$imageToStore = $newFilename ?? $existing['image'];

$stmt = $conn->prepare("UPDATE team_members SET name = ?, major = ?, image = ? WHERE id = ?");
$stmt->bind_param("sssi", $name, $major, $imageToStore, $id);

if ($stmt->execute()) {
    // Old image is only removed after the row update succeeds and only when replaced.
    if ($newFilename !== null && $existing['image'] !== '' && file_exists($uploadDir . $existing['image'])) {
        unlink($uploadDir . $existing['image']);
    }
    echo json_encode([
        'status' => 'success',
        'message' => 'Team member updated successfully',
        'member' => ['id' => $id, 'name' => $name, 'major' => $major, 'image' => $imageToStore],
    ]);
} else {
    if ($newFilename !== null) {
        unlink($uploadDir . $newFilename);
    }
    echo json_encode(['status' => 'error', 'message' => 'Error updating team member']);
}
?>
