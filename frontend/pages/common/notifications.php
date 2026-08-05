<?php
require_once "../Student Dashboard/php/dbcontroller.php";

class NotificationManager {
    private $db_handle;
    
    public function __construct() {
        $this->db_handle = new DBController();
    }
    
    /**
     * Create a new notification for a user
     * 
     * @param int $user_id User ID to send notification to
     * @param string $title Notification title
     * @param string $message Notification message content
     * @param string $type Type of notification (assignment, grade, attendance, announcement)
     * @param int|null $related_id Related item ID (e.g., assignment_id, course_id)
     * @return int|bool ID of created notification or false on failure
     */
    public function createNotification($user_id, $title, $message, $type, $related_id = null) {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
        $types = "isssi";
        $params = [$user_id, $title, $message, $type, $related_id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params);
    }
    
    /**
     * Get notifications for a specific user
     * 
     * @param int $user_id User ID to get notifications for
     * @param bool $unreadOnly Whether to only get unread notifications
     * @param int $limit Maximum number of notifications to get
     * @return array Notifications array
     */
    public function getUserNotifications($user_id, $unreadOnly = false, $limit = 10) {
        $query = "SELECT * FROM notifications WHERE user_id = ?";
        $types = "i";
        $params = [$user_id];
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        $types .= "i";
        $params[] = $limit;
        
        return $this->db_handle->executeSelectPrepared($query, $types, $params);
    }
    
    /**
     * Count unread notifications for a user
     * 
     * @param int $user_id User ID to count notifications for
     * @return int Number of unread notifications
     */
    public function countUnreadNotifications($user_id) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $types = "i";
        $params = [$user_id];
        
        $result = $this->db_handle->executeSelectPrepared($query, $types, $params);
        
        if (!empty($result)) {
            return $result[0]['count'];
        }
        
        return 0;
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notification_id Notification ID to mark as read
     * @param int $user_id User ID for security check
     * @return bool Success status
     */
    public function markAsRead($notification_id, $user_id) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $types = "ii";
        $params = [$notification_id, $user_id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params) > 0;
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function markAllAsRead($user_id) {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $types = "i";
        $params = [$user_id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params) > 0;
    }
    
    /**
     * Delete a notification
     * 
     * @param int $notification_id Notification ID to delete
     * @param int $user_id User ID for security check
     * @return bool Success status
     */
    public function deleteNotification($notification_id, $user_id) {
        $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $types = "ii";
        $params = [$notification_id, $user_id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params) > 0;
    }
}
?>
