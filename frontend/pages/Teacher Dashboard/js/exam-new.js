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
                // Get exam events from database
                $.ajax({
                    url: '../common/get_calendar_events.php',
                    type: 'GET',
                    data: {
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                        type: 'exam'
                    },
                    success: function(result) {
                        try {
                            const events = JSON.parse(result);
                            successCallback(events.map(event => ({
                                id: event.id,
                                title: event.title,
                                start: event.start_date,
                                end: event.end_date,
                                allDay: false,
                                backgroundColor: '#810000',
                                borderColor: '#810000'
                            })));
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
                events: '../common/get_calendar_events.php',
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
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            $.ajax({
                url: 'php/exam_functions.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
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
    }
})
