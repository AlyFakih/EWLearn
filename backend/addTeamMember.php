<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$name = trim($_POST['name'] ?? '');
$major = trim($_POST['major'] ?? '');

if ($name === '' || $major === '' || empty($_FILES['image']['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Name, major and image are all required']);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
    exit;
}

// Reject anything over 5MB before touching the filesystem.
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Image must be smaller than 5MB']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Image must be jpg, jpeg, png, gif or webp']);
    exit;
}

// getimagesize() decodes the actual image data, so a renamed non-image file
// (e.g. a .php shell with a .jpg extension) is rejected here even though the
// extension check above passed.
$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMime, true)) {
    echo json_encode(['status' => 'error', 'message' => 'File is not a valid image']);
    exit;
}

$uploadDir = __DIR__ . '/../frontend/assets/images/team/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Filename is generated server-side (never the client-supplied name) so it
// cannot be used for path traversal or to overwrite another file.
$filename = uniqid('team_', true) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    echo json_encode(['status' => 'error', 'message' => 'Could not save image']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO team_members (name, major, image) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $major, $filename);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Team member added successfully',
        'member' => ['id' => $conn->insert_id, 'name' => $name, 'major' => $major, 'image' => $filename],
    ]);
} else {
    unlink($uploadDir . $filename);
    echo json_encode(['status' => 'error', 'message' => 'Error adding team member']);
}
?>
