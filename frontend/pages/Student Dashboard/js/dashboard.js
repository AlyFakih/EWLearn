$(document).ready(function() {
    // Fetch assignment count
    $.ajax({
        url: 'php/get_assignment_count.php',
        type: 'GET',
        success: function(response) {
            $('#assignment-count').text(response);
        },
        error: function() {
            $('#assignment-count').text('N/A');
        }
    });

    // Fetch attendance rate
    $.ajax({
        url: 'php/get_attendance_rate.php',
        type: 'GET',
        success: function(response) {
            $('#attendance-rate').text(response + '%');
        },
        error: function() {
            $('#attendance-rate').text('N/A');
        }
    });

    // Fetch average grade
    $.ajax({
        url: 'php/get_average_grade.php',
        type: 'GET',
        success: function(response) {
            $('#average-grade').text(response);
        },
        error: function() {
            $('#average-grade').text('N/A');
        }
    });

    // Fetch upcoming deadlines
    $.ajax({
        url: 'php/get_deadlines.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var deadlinesHtml = '';
            if (response.length > 0) {
                response.forEach(function(deadline) {
                    deadlinesHtml += `
                        <div class="deadline-item">
                            <div class="deadline-info">
                                <h4>${deadline.title}</h4>
                                <p>${deadline.course_name} - ${deadline.type}</p>
                            </div>
                            <span class="deadline-date">${deadline.due_date}</span>
                        </div>
                    `;
                });
            } else {
                deadlinesHtml = '<p class="no-data">No upcoming deadlines!</p>';
            }
            $('#deadlines-container').html(deadlinesHtml);
        },
        error: function() {
            $('#deadlines-container').html('<p class="error">Failed to load deadlines.</p>');
        }
    });

    // Fetch recent announcements
    $.ajax({
        url: 'php/get_announcements.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var announcementsHtml = '';
            if (response.length > 0) {
                response.forEach(function(announcement) {
                    // Add important class if announcement is marked as important
                    const importantClass = announcement.important == 1 ? 'important' : '';
                    const importantBadge = announcement.important == 1 ? '<span class="important-badge">Important</span>' : '';
                    
                    announcementsHtml += `
                        <div class="announcement-item ${importantClass}">
                            <div class="announcement-header">
                                <h4>
                                    ${announcement.title}
                                    ${importantBadge}
                                </h4>
                                <span>${announcement.date} - ${announcement.course_name}</span>
                            </div>
                            <p>${announcement.content}</p>
                            <div class="announcement-footer">
                                <span class="posted-by">Posted by: ${announcement.posted_by}</span>
                            </div>
                        </div>
                    `;
                });
            } else {
                announcementsHtml = '<p class="no-data">No recent announcements!</p>';
            }
            $('#announcements-container').html(announcementsHtml);
        },
        error: function() {
            $('#announcements-container').html('<p class="error">Failed to load announcements.</p>');
        }
    });
});
