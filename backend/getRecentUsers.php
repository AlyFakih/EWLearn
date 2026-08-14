<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// Most recently added accounts, used to populate the admin notification
// bell with real activity instead of the previous hardcoded demo content.
// users has no created_at column, so id (auto-increment) is used as the
// insertion-order proxy - the same assumption already used elsewhere in
// this app for "most recent" ordering.
$result = $conn->query("SELECT fullName, role, image FROM users ORDER BY id DESC LIMIT 4");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users, JSON_UNESCAPED_SLASHES);
?>
