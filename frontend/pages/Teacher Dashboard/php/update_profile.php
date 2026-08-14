<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once __DIR__ . "/../../../core/DBController.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// --- Profile photo upload (multipart/form-data with a "profile_image" file field) ---
if (isset($_FILES['profile_image'])) {
    if ($_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => 'No file selected']);
        exit();
    }

    require_once __DIR__ . "/../../common/file_handler.php";

    // users.image is stored relative to frontend/images/ (e.g. "users/instructors/instructor02.jpg"),
    // a different base directory than the assignments uploads/ folder - anchor with __DIR__ rather
    // than a bare relative path to avoid the include/cwd resolution issues hit elsewhere in this app.
    $fileHandler = new FileHandler(
        ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        5242880, // 5MB
        __DIR__ . '/../../../images/'
    );

    $newFilename = 'instructor_' . $user_id . '_' . time();
    $uploadResult = $fileHandler->uploadFile($_FILES['profile_image'], 'users/instructors/', $newFilename);

    if (!$uploadResult['success']) {
        echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
        exit();
    }

    $imagePath = $uploadResult['file_path'];

    $db_handle->executeUpdatePrepared(
        "UPDATE users SET image = ? WHERE id = ? AND role = 'instructor'",
        "si",
        [$imagePath, $user_id]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Profile photo updated successfully',
        'image' => '../../images/' . $imagePath,
    ]);
    exit();
}

// --- Inline field update (Name / Contact / Email / Country) ---
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Whitelist client-supplied field names to real column names - never
// interpolate the client value directly into SQL.
$allowedFields = [
    'fullName' => 'fullName',
    'mobile'   => 'mobile',
    'email'    => 'email',
    'country'  => 'country',
];

if (!isset($allowedFields[$field])) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit();
}

if ($value === '') {
    echo json_encode(['success' => false, 'message' => 'Value cannot be empty']);
    exit();
}

if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$column = $allowedFields[$field];

// affected_rows is 0 both on failure and on a successful no-op (value unchanged
// from before), so it can't be used alone to detect failure here - the earlier
// session/role check is what actually authorizes this update.
$db_handle->executeUpdatePrepared(
    "UPDATE users SET {$column} = ? WHERE id = ? AND role = 'instructor'",
    "si",
    [$value, $user_id]
);

echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'value' => $value]);
exit();
