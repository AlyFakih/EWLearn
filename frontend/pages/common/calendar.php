<?php
require_once "../Student Dashboard/php/dbcontroller.php";

class CalendarManager {
    private $db_handle;
    
    public function __construct() {
        $this->db_handle = new DBController();
    }
    
    /**
     * Create a new calendar event
     * 
     * @param string $title Event title
     * @param string $description Event description
     * @param string $start_date Start date (YYYY-MM-DD HH:MM:SS)
     * @param string $end_date End date (YYYY-MM-DD HH:MM:SS) 
     * @param bool $all_day Whether it's an all-day event
     * @param string $event_type Type of event (assignment, exam, holiday, class, meeting)
     * @param int|null $course_id Related course ID (null for school-wide events)
     * @param string|null $color Event color for display
     * @param int $created_by User ID who created the event
     * @return int|bool ID of created event or false on failure
     */
    public function createEvent($title, $description, $start_date, $end_date, $all_day, $event_type, $course_id, $color, $created_by) {
        $query = "INSERT INTO academic_calendar (title, description, start_date, end_date, all_day, event_type, course_id, color, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $types = "ssssisiis";
        $all_day_int = $all_day ? 1 : 0;
        $params = [$title, $description, $start_date, $end_date, $all_day_int, $event_type, $course_id, $color, $created_by];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params);
    }
    
    /**
     * Update an existing calendar event
     * 
     * @param int $id Event ID to update
     * @param string $title Event title
     * @param string $description Event description
     * @param string $start_date Start date (YYYY-MM-DD HH:MM:SS)
     * @param string $end_date End date (YYYY-MM-DD HH:MM:SS) 
     * @param bool $all_day Whether it's an all-day event
     * @param string $event_type Type of event (assignment, exam, holiday, class, meeting)
     * @param int|null $course_id Related course ID (null for school-wide events)
     * @param string|null $color Event color for display
     * @return bool Success status
     */
    public function updateEvent($id, $title, $description, $start_date, $end_date, $all_day, $event_type, $course_id, $color) {
        $query = "UPDATE academic_calendar 
                  SET title = ?, description = ?, start_date = ?, end_date = ?, 
                      all_day = ?, event_type = ?, course_id = ?, color = ? 
                  WHERE id = ?";
        
        $types = "ssssisisi";
        $all_day_int = $all_day ? 1 : 0;
        $params = [$title, $description, $start_date, $end_date, $all_day_int, $event_type, $course_id, $color, $id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params) > 0;
    }
    
    /**
     * Delete a calendar event
     * 
     * @param int $id Event ID to delete
     * @return bool Success status
     */
    public function deleteEvent($id) {
        $query = "DELETE FROM academic_calendar WHERE id = ?";
        $types = "i";
        $params = [$id];
        
        return $this->db_handle->executeUpdatePrepared($query, $types, $params) > 0;
    }
    
    /**
     * Get all events in a date range
     * 
     * @param string $start_date Range start date (YYYY-MM-DD)
     * @param string $end_date Range end date (YYYY-MM-DD)
     * @param int|null $course_id Filter by course ID (optional)
     * @return array Events array
     */
    public function getEventsByDateRange($start_date, $end_date, $course_id = null) {
        $query = "SELECT ac.*, c.courseTitle 
                  FROM academic_calendar ac
                  LEFT JOIN courses c ON ac.course_id = c.id
                  WHERE (ac.start_date BETWEEN ? AND ? OR 
                         ac.end_date BETWEEN ? AND ? OR 
                         ? BETWEEN ac.start_date AND ac.end_date)";
        
        $types = "sssss";
        $params = [$start_date, $end_date, $start_date, $end_date, $start_date];
        
        if ($course_id !== null) {
            $query .= " AND (ac.course_id = ? OR ac.course_id IS NULL)";
            $types .= "i";
            $params[] = $course_id;
        }
        
        $query .= " ORDER BY ac.start_date ASC";
        
        return $this->db_handle->executeSelectPrepared($query, $types, $params);
    }
    
    /**
     * Get events for a specific course
     * 
     * @param int $course_id Course ID
     * @return array Events array
     */
    public function getEventsByCourse($course_id) {
        $query = "SELECT * FROM academic_calendar WHERE course_id = ? ORDER BY start_date ASC";
        $types = "i";
        $params = [$course_id];
        
        return $this->db_handle->executeSelectPrepared($query, $types, $params);
    }
    
    /**
     * Get upcoming events
     * 
     * @param int $limit Maximum number of events to return
     * @param int|null $course_id Filter by course ID (optional)
     * @return array Events array
     */
    public function getUpcomingEvents($limit = 5, $course_id = null) {
        $today = date('Y-m-d H:i:s');
        
        $query = "SELECT ac.*, c.courseTitle 
                  FROM academic_calendar ac
                  LEFT JOIN courses c ON ac.course_id = c.id
                  WHERE ac.end_date >= ?";
        
        $types = "s";
        $params = [$today];
        
        if ($course_id !== null) {
            $query .= " AND (ac.course_id = ? OR ac.course_id IS NULL)";
            $types .= "i";
            $params[] = $course_id;
        }
        
        $query .= " ORDER BY ac.start_date ASC LIMIT ?";
        $types .= "i";
        $params[] = $limit;
        
        return $this->db_handle->executeSelectPrepared($query, $types, $params);
    }
    
    /**
     * Get a specific event by ID
     * 
     * @param int $id Event ID
     * @return array|null Event data or null if not found
     */
    public function getEventById($id) {
        $query = "SELECT ac.*, c.courseTitle 
                  FROM academic_calendar ac
                  LEFT JOIN courses c ON ac.course_id = c.id
                  WHERE ac.id = ?";
        $types = "i";
        $params = [$id];
        
        $result = $this->db_handle->executeSelectPrepared($query, $types, $params);
        
        if (!empty($result)) {
            return $result[0];
        }
        
        return null;
    }
}
?>
