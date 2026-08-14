-- Create the academic calendar events table
CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    all_day BOOLEAN DEFAULT FALSE,
    event_type VARCHAR(20) NOT NULL,  -- 'assignment', 'exam', 'holiday', 'class', 'meeting'
    course_id INT,                    -- NULL for school-wide events
    color VARCHAR(20),                -- For calendar display
    created_by INT NOT NULL,          -- user_id who created the event
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for performance
CREATE INDEX idx_calendar_date ON academic_calendar(start_date, end_date);
CREATE INDEX idx_calendar_course ON academic_calendar(course_id);
CREATE INDEX idx_calendar_type ON academic_calendar(event_type);
