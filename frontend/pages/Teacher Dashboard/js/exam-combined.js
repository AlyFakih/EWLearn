$(document).ready(function() {
    let calendar;
    initCalendar();
    setupModalHandlers();
    setupFormHandlers();
    setupTableHandlers();
    initNotifications();
    
    // Initialize the main calendar with exams as events
    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                // Get calendar events (calendar_api.php already returns
                // FullCalendar-ready objects: start/end/title/color)
                $.ajax({
                    url: '../common/calendar_api.php',
                    type: 'GET',
                    data: {
                        action: 'get_events',
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr
                    },
                    success: function(result) {
                        try {
                            const events = typeof result === 'string' ? JSON.parse(result) : result;
                            successCallback(events);
                        } catch (e) {
                            console.error('Error parsing calendar events:', e);
                            failureCallback(e);
                        }
                    },
                    error: function(error) {
                        console.error('Error fetching calendar events:', error);
                        failureCallback(error);
                    }
                });
            },
            eventClick: function(info) {
                // Show exam details when clicking on an event
                const eventId = info.event.id;
                // Extract exam ID from the event
                const examId = eventId.replace('exam-', '');
                viewExamDetails(examId);
            }
        });
        
        calendar.render();
        
        // Also initialize mini calendar in sidebar if it exists
        const miniCalendarEl = document.getElementById('mini-calendar');
        if (miniCalendarEl) {
            const miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: ''
                },
                events: {
                    url: '../common/calendar_api.php',
                    method: 'GET',
                    extraParams: { action: 'get_events' }
                },
                contentHeight: 'auto'
            });
            miniCalendar.render();
        }
    }
    
    // Setup modal handlers (open/close)
    function setupModalHandlers() {
        // Add exam button
        $('#add-exam-btn').click(function() {
            $('#add-exam-modal').css('display', 'block');
        });
        
        // Close modal buttons
        $('.modal-close, .cancel-modal').click(function() {
            $(this).closest('.modal').css('display', 'none');
        });
        
        // Close modal when clicking outside
        $(window).click(function(e) {
            $('.modal').each(function() {
                if (e.target == this) {
                    $(this).css('display', 'none');
                }
            });
        });
    }
    
    // Setup form submission handlers
    function setupFormHandlers() {
        // Add new exam form submission
        $('#add-exam-form').submit(function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'php/exam_functions.php',
                type: 'POST',
                data: formData + '&action=add',
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            // Add new row to table
                            if ($('#exams-table tbody .no-data').length) {
                                $('#exams-table tbody').empty();
                            }
                            $('#exams-table tbody').append(result.html);
                            
                            // Show success message
                            showNotification('Success', 'Exam added successfully!', 'success');
                            
                            // Reset form and close modal
                            $('#add-exam-form')[0].reset();
                            $('#add-exam-modal').css('display', 'none');
                            
                            // Refresh the calendar to show new exam
                            if (calendar) {
                                calendar.refetchEvents();
                            }
                            
                            // Update notification badge
                            updateNotificationCount();
                        } else {
                            showNotification('Error', result.message, 'danger');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        showNotification('Error', 'Failed to add exam. Please try again.', 'danger');
                    }
                },
                error: function() {
                    showNotification('Error', 'Failed to add exam. Please try again.', 'danger');
                }
            });
        });
        
        // Edit exam form submission
        $('#edit-exam-form').submit(function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'php/exam_functions.php',
                type: 'POST',
                data: formData + '&action=update',
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            // Update the row in the table
                            const exam = result.exam;
                            const row = $(`#exams-table tr[data-id="${exam.id}"]`);
                            
                            row.find('td[data-field="course"]').text(`${exam.courseCode} - ${exam.courseTitle}`);
                            row.find('td[data-field="subject"]').text(exam.subject);
                            row.find('td[data-field="date"]').text(exam.formatted_date);
                            row.find('td[data-field="time"]').text(exam.formatted_time);
                            row.find('td[data-field="room"]').text(exam.room);
                            
                            // Show success message
                            showNotification('Success', 'Exam updated successfully!', 'success');
                            
                            // Close modal
                            $('#edit-exam-modal').css('display', 'none');
                            
                            // Refresh the calendar
                            if (calendar) {
                                calendar.refetchEvents();
                            }
                            
                            // Update notification badge
                            updateNotificationCount();
                        } else {
                            showNotification('Error', result.message, 'danger');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        showNotification('Error', 'Failed to update exam. Please try again.', 'danger');
                    }
                },
                error: function() {
                    showNotification('Error', 'Failed to update exam. Please try again.', 'danger');
                }
            });
        });
    }
    
    // Table handlers for the exam dashboard
    function setupTableHandlers() {
        // Search functionality
        $('#search-exams').on('input', function() {
            const searchText = $(this).val().toLowerCase();
            
            $('#exams-table tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchText) > -1);
            });
            
            // Show "no results" message if needed
            if ($('#exams-table tbody tr:visible').length === 0) {
                if ($('#exams-table tbody .no-results').length === 0) {
                    $('#exams-table tbody').append('<tr class="no-results"><td colspan="6">No matching exams found</td></tr>');
                }
            } else {
                $('#exams-table tbody .no-results').remove();
            }
        });
        
        // View exam details
        $(document).on('click', '.view-exam', function() {
            const examId = $(this).data('id');
            viewExamDetails(examId);
        });
        
        // Edit exam button
        $(document).on('click', '.edit-exam', function() {
            const examId = $(this).data('id');
            
            // Get exam details and populate the edit form
            $.ajax({
                url: 'php/exam_functions.php',
                type: 'GET',
                data: {
                    action: 'view',
                    id: examId
                },
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            const exam = result.exam;
                            
                            // Populate form fields
                            $('#edit_exam_id').val(exam.id);
                            $('#edit_course_id').val(exam.course_id);
                            $('#edit_subject').val(exam.subject);
                            $('#edit_exam_date').val(exam.date);
                            $('#edit_exam_time').val(exam.time);
                            $('#edit_room').val(exam.room);
                            $('#edit_duration').val(exam.duration || 60);
                            
                            // Show the modal
                            $('#edit-exam-modal').css('display', 'block');
                        } else {
                            showNotification('Error', result.message, 'danger');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        showNotification('Error', 'Failed to load exam details', 'danger');
                    }
                },
                error: function() {
                    showNotification('Error', 'Failed to load exam details', 'danger');
                }
            });
        });
        
        // Delete exam button
        $(document).on('click', '.delete-exam', function() {
            const examId = $(this).data('id');
            const row = $(this).closest('tr');
            
            if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
                $.ajax({
                    url: 'php/exam_functions.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: examId
                    },
                    success: function(response) {
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            
                            if (result.success) {
                                // Remove the row with animation
                                row.fadeOut(400, function() {
                                    $(this).remove();
                                    
                                    // Show empty state if no more rows
                                    if ($('#exams-table tbody tr').length === 0) {
                                        $('#exams-table tbody').append('<tr><td colspan="6" class="no-data">No exams scheduled yet</td></tr>');
                                    }
                                });
                                
                                // Show success message
                                showNotification('Success', 'Exam deleted successfully', 'success');
                                
                                // Refresh calendar
                                if (calendar) {
                                    calendar.refetchEvents();
                                }
                                
                                // Update notification count
                                updateNotificationCount();
                            } else {
                                showNotification('Error', result.message, 'danger');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showNotification('Error', 'Failed to delete exam', 'danger');
                        }
                    },
                    error: function() {
                        showNotification('Error', 'Failed to delete exam', 'danger');
                    }
                });
            }
        });
    }
    
    // View exam details
    function viewExamDetails(examId) {
        $.ajax({
            url: 'php/exam_functions.php',
            type: 'GET',
            data: {
                action: 'view',
                id: examId
            },
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        const exam = result.exam;
                        
                        // Populate the view modal
                        $('#view_course').text(`${exam.courseCode} - ${exam.courseTitle}`);
                        $('#view_subject').text(exam.subject);
                        $('#view_date').text(exam.formatted_date);
                        $('#view_time').text(exam.formatted_time);
                        $('#view_room').text(exam.room);
                        $('#view_duration').text(`${exam.duration} minutes`);
                        
                        // Populate enrolled students list
                        let studentHtml = '';
                        
                        if (result.students && result.students.length > 0) {
                            studentHtml = '<ul class="student-list">';
                            result.students.forEach(student => {
                                studentHtml += `<li>${student.full_name} (Enrolled: ${formatDate(student.enrollment_date)})</li>`;
                            });
                            studentHtml += '</ul>';
                        } else {
                            studentHtml = '<p>No students enrolled in this course yet.</p>';
                        }
                        
                        $('#enrolled-students-list').html(studentHtml);
                        
                        // Show the modal
                        $('#view-exam-modal').css('display', 'block');
                    } else {
                        showNotification('Error', result.message, 'danger');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showNotification('Error', 'Failed to load exam details', 'danger');
                }
            },
            error: function() {
                showNotification('Error', 'Failed to load exam details', 'danger');
            }
        });
    }
    
    // Initialize notifications
    function initNotifications() {
        // Initial check for notifications. No polling interval here: this
        // page also loads the shared notifications.js widget (via
        // header_includes.php), which already polls the same
        // notification_api.php?action=count endpoint every 30s and updates
        // the same #notification-badge element - a second interval here was
        // a duplicate, redundant poll of the same endpoint/element.
        updateNotificationCount();
    }
    
    // Update notification count
    function updateNotificationCount() {
        $.ajax({
            url: '../common/notification_api.php?action=count',
            type: 'GET',
            success: function(response) {
                try {
                    const count = parseInt(response);
                    const badge = $('.notification-badge');
                    
                    if (count > 0) {
                        badge.text(count).show();
                    } else {
                        badge.hide();
                    }
                } catch (e) {
                    console.error('Error updating notification count:', e);
                }
            }
        });
    }
    
    // Show notification
    function showNotification(title, message, type) {
        // First try to use $.notify if available (preferred)
        if (typeof $.notify !== 'undefined') {
            $.notify({
                title: `<strong>${title}</strong>`,
                message: message
            }, {
                type: type || 'info',
                placement: {
                    from: 'top',
                    align: 'center'
                },
                animate: {
                    enter: 'animated fadeInDown',
                    exit: 'animated fadeOutUp'
                }
            });
        } else {
            // Fallback to alert
            alert(`${title}: ${message}`);
        }
    }
    
    // Format date helper
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
});
