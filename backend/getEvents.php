<?php
// Public endpoint: events are marketing content shown on the public
// Events.html page, so like getTeamMembers.php/getInstructorsList.php this
// has no auth gate by design.
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$result = $conn->query("SELECT id, nameType, linkInfo, Date, description, image FROM events ORDER BY id DESC");

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events, JSON_UNESCAPED_SLASHES);
?>
