<?php
session_start();
require_once "notifications.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$notification_manager = new NotificationManager();
$user_id = $_SESSION['user_id'];

// Handle different API actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get':
        // Get notifications for the user
        $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $notifications = $notification_manager->getUserNotifications($user_id, $unread_only, $limit);
        echo json_encode($notifications);
        break;
    
    case 'count':
        // Count unread notifications
        $count = $notification_manager->countUnreadNotifications($user_id);
        echo json_encode(['count' => $count]);
        break;
    
    case 'mark_read':
        // Mark notification as read
        if (isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];
            $success = $notification_manager->markAsRead($notification_id, $user_id);
            echo json_encode(['success' => $success]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing notification_id']);
        }
        break;
    
    case 'mark_all_read':
        // Mark all notifications as read
        $success = $notification_manager->markAllAsRead($user_id);
        echo json_encode(['success' => $success]);
        break;
    
    case 'delete':
        // Delete notification
        if (isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];
            $success = $notification_manager->deleteNotification($notification_id, $user_id);
            echo json_encode(['success' => $success]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing notification_id']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
