<?php
session_start();
require_once "calendar.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$calendar_manager = new CalendarManager();
$user_id = $_SESSION['user_id'];
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] == 1;

// Handle different API actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_events':
        // Get events in date range
        $start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
        $end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+30 days'));
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
        
        $events = $calendar_manager->getEventsByDateRange($start_date, $end_date, $course_id);
        
        // Format for Full Calendar
        $formattedEvents = [];
        foreach ($events as $event) {
            $formattedEvent = [
                'id' => $event['id'],
                'title' => $event['title'],
                'start' => $event['start_date'],
                'end' => $event['end_date'],
                'allDay' => (bool)$event['all_day'],
                'description' => $event['description'],
                'eventType' => $event['event_type'],
                'courseId' => $event['course_id'],
                'courseTitle' => $event['courseTitle'] ?? 'School-wide',
                'color' => $event['color'] ?: getColorForEventType($event['event_type'])
            ];
            $formattedEvents[] = $formattedEvent;
        }
        
        echo json_encode($formattedEvents);
        break;
    
    case 'upcoming_events':
        // Get upcoming events
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
        
        $events = $calendar_manager->getUpcomingEvents($limit, $course_id);
        echo json_encode($events);
        break;
    
    case 'get_event':
        // Get specific event details
        if (isset($_GET['id'])) {
            $event_id = (int)$_GET['id'];
            $event = $calendar_manager->getEventById($event_id);
            
            if ($event) {
                echo json_encode(['success' => true, 'event' => $event]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Event not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing event ID']);
        }
        break;
    
    case 'create_event':
        // Only teachers can create events
        if (!$isTeacher) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            break;
        }
        
        // Create a new event
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            
            // Validate required fields
            if (empty($data['title']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['event_type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                break;
            }
            
            $result = $calendar_manager->createEvent(
                $data['title'],
                $data['description'] ?? '',
                $data['start_date'],
                $data['end_date'],
                isset($data['all_day']) ? (bool)$data['all_day'] : false,
                $data['event_type'],
                !empty($data['course_id']) ? (int)$data['course_id'] : null,
                $data['color'] ?? null,
                $user_id
            );
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $result]);
                
                // If this is an assignment event, create notifications for all students in the course
                if ($data['event_type'] === 'assignment' && !empty($data['course_id'])) {
                    createAssignmentNotifications((int)$data['course_id'], $data['title'], $data['start_date'], $result);
                }
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create event']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    
    case 'update_event':
        // Only teachers can update events
        if (!$isTeacher) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            break;
        }
        
        // Update an existing event
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $data = $_POST;
            $event_id = (int)$data['id'];
            
            // Validate required fields
            if (empty($data['title']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['event_type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                break;
            }
            
            $result = $calendar_manager->updateEvent(
                $event_id,
                $data['title'],
                $data['description'] ?? '',
                $data['start_date'],
                $data['end_date'],
                isset($data['all_day']) ? (bool)$data['all_day'] : false,
                $data['event_type'],
                !empty($data['course_id']) ? (int)$data['course_id'] : null,
                $data['color'] ?? null
            );
            
            if ($result) {
                echo json_encode(['success' => true]);
                
                // If this is an assignment event, update notifications for all students in the course
                if ($data['event_type'] === 'assignment' && !empty($data['course_id'])) {
                    updateAssignmentNotifications((int)$data['course_id'], $event_id, $data['title'], $data['start_date']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update event']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    
    case 'delete_event':
        // Only teachers can delete events
        if (!$isTeacher) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            break;
        }
        
        // Delete an event
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $event_id = (int)$_POST['id'];
            $result = $calendar_manager->deleteEvent($event_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete event']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * Get default color based on event type
 * 
 * @param string $eventType Event type
 * @return string Color hex code
 */
function getColorForEventType($eventType) {
    switch ($eventType) {
        case 'assignment':
            return '#4285f4'; // Blue
        case 'exam':
            return '#DB4437'; // Red
        case 'holiday':
            return '#0F9D58'; // Green
        case 'class':
            return '#F4B400'; // Yellow
        case 'meeting':
            return '#673AB7'; // Purple
        default:
            return '#810000'; // School maroon color
    }
}

/**
 * Create notifications for students about a new assignment
 * 
 * @param int $courseId Course ID
 * @param string $title Assignment title
 * @param string $dueDate Due date
 * @param int $eventId Event ID
 */
function createAssignmentNotifications($courseId, $title, $dueDate, $eventId) {
    require_once "notifications.php";
    $notificationManager = new NotificationManager();
    $db = new DBController();
    
    // Get all students enrolled in the course
    $query = "SELECT sc.student_id 
              FROM studentcourse sc 
              WHERE sc.course_id = ?";
    
    $students = $db->executeSelectPrepared($query, "i", [$courseId]);
    
    // Create a notification for each student
    $formattedDate = date('M j, Y', strtotime($dueDate));
    foreach ($students as $student) {
        $notificationManager->createNotification(
            $student['student_id'],
            "New Assignment: $title",
            "A new assignment \"$title\" has been added with due date $formattedDate.",
            "assignment",
            $eventId
        );
    }
}

/**
 * Update notifications for students about an updated assignment
 * 
 * @param int $courseId Course ID
 * @param int $eventId Event ID
 * @param string $title Assignment title
 * @param string $dueDate Due date
 */
function updateAssignmentNotifications($courseId, $eventId, $title, $dueDate) {
    require_once "notifications.php";
    $notificationManager = new NotificationManager();
    $db = new DBController();
    
    // Get all students enrolled in the course
    $query = "SELECT sc.student_id 
              FROM studentcourse sc 
              WHERE sc.course_id = ?";
    
    $students = $db->executeSelectPrepared($query, "i", [$courseId]);
    
    // Create an update notification for each student
    $formattedDate = date('M j, Y', strtotime($dueDate));
    foreach ($students as $student) {
        $notificationManager->createNotification(
            $student['student_id'],
            "Assignment Updated: $title",
            "The assignment \"$title\" has been updated. New due date: $formattedDate.",
            "assignment",
            $eventId
        );
    }
}
?>
