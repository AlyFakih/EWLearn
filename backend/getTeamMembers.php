<?php
// Public endpoint: the team roster is marketing content shown on the public
// homepage, so unlike the other admin CRUD endpoints in this file this one
// has no auth gate by design - it only ever reads non-sensitive data.
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$result = $conn->query("SELECT id, name, major, image FROM team_members ORDER BY id ASC");

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

echo json_encode($members, JSON_UNESCAPED_SLASHES);
?>
