// Initialize components when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar with event loading
    initCalendar();
    
    // Initialize notification handling
    initNotifications();
    
    // Initialize mini calendar in sidebar
    initMiniCalendar();
    
    // Setup course modals
    setupCourseModals();
});

// Initialize the main calendar with events
function initCalendar() {
    var calendarEl = document.getElementById('calendar');
    
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: 'php/get_calendar_events.php',
            eventClick: function(info) {
                // Handle event click - show details in a modal or navigate
                if (info.event.url) {
                    window.location.href = info.event.url;
                    return false;
                }
            }
        });
        
        window.calendar = calendar;
        calendar.render();
    }
}

// Initialize the mini calendar in the sidebar
function initMiniCalendar() {
    var miniCalendarEl = document.getElementById('mini-calendar');
    
    if (miniCalendarEl) {
        var miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: ''
            },
            height: 'auto',
            events: 'php/get_calendar_events.php'
        });
        
        window.miniCalendar = miniCalendar;
        miniCalendar.render();
    }
}

// Initialize notification handling
function initNotifications() {
    // Check for notification badge updates
    updateNotificationBadge();
    
    // Set interval to check for new notifications
    setInterval(updateNotificationBadge, 60000); // Check every minute
}

// Update the notification badge count
function updateNotificationBadge() {
    $.ajax({
        url: '../../common/notification_api.php?action=count',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.count > 0) {
                $('#notification-badge').text(response.count).show();
            } else {
                $('#notification-badge').hide();
            }
        }
    });
}

// Setup modal interactions
function setupCourseModals() {
    // Show add course modal
    $('#showForm').on('click', function(e) {
        e.preventDefault();
        $('#addModal').fadeIn();
    });
    
    // Hide add course modal
    $('#closeForm').on('click', function() {
        $('#addModal').fadeOut();
    });
    
    // Hide view course modal
    $('#closeViewModal').on('click', function() {
        $('#viewModal').fadeOut();
    });
    
    // Hide edit course modal
    $('#closeEditForm').on('click', function() {
        $('#editModal').fadeOut();
    });
    
    // Setup course card actions
    setupCourseCardActions();
    
    // Setup form submissions
    setupFormHandlers();
}

function showPopup() {
    document.getElementById('popup').style.display = 'block';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

// Setup form submissions handlers
function setupFormHandlers() {
    // Add course form submission
    $('#addCourseForm').on('submit', function(e) {
        e.preventDefault();
        submitAddCourseForm();
    });
    
    // Edit course form submission
    $('#editCourseForm').on('submit', function(e) {
        e.preventDefault();
        submitEditCourseForm();
    });
    
    // Delete course confirmation
    $('.delete-course-confirm').on('click', function() {
        var courseId = $(this).data('id');
        deleteCourse(courseId);
    });
}

// Handle adding a new course
function submitAddCourseForm() {
    var formData = new FormData($('#addCourseForm')[0]);
    formData.append('action', 'add');
    
    $.ajax({
        url: 'php/course_functions.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Add the new course card to the grid
                $('#course-grid').append(response.html);
                
                // Reset form and hide modal
                $('#addCourseForm')[0].reset();
                $('#addModal').fadeOut();
                
                // Refresh calendars if available
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }
                if (window.miniCalendar) {
                    window.miniCalendar.refetchEvents();
                }
                
                // Show success message
                showNotification('Course Added', response.message, 'success');
                
                // Update notification badge
                updateNotificationBadge();
            } else {
                // Show error message
                showNotification('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error', 'Failed to add course: ' + error, 'danger');
        }
    });
}






// Initialize course card actions (edit, view, delete buttons)
function setupCourseCardActions() {
    // Using event delegation for dynamically added course cards
    $(document).on('click', '.view-course', function(e) {
        e.preventDefault();
        e.stopPropagation();
        viewCourse($(this).data('id'));
    });
    
    $(document).on('click', '.edit-course', function(e) {
        e.preventDefault();
        e.stopPropagation();
        editCourse($(this).data('id'));
    });
    
    $(document).on('click', '.delete-course', function(e) {
        e.preventDefault();
        e.stopPropagation();
        deleteCourse($(this).data('id'));
    });
}

// Format date for displaying in the UI
function formatDate(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleDateString();
}

// Show notification using either jQuery Notify plugin or basic alert
function showNotification(title, message, type) {
    // If jQuery notify plugin exists, use it
    if ($.notify) {
        $.notify({
            title: `<strong>${title}</strong>`,
            message: message
        }, {
            type: type || 'info',
            placement: {
                from: 'top',
                align: 'right'
            },
            animate: {
                enter: 'animated fadeInDown',
                exit: 'animated fadeOutUp'
            },
            delay: 5000
        });
    } else {
        // Fallback to basic alert
        alert(`${title}: ${message}`);
    }
}
// Handle viewing course details
function viewCourse(courseId) {
    $.ajax({
        url: 'php/course_functions.php',
        type: 'GET',
        data: {
            action: 'view',
            id: courseId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the view modal with course details
                $('#viewTitle').text(response.course.courseTitle);
                $('#viewCode').text(response.course.courseCode);
                $('#viewDescription').text(response.course.courseDescription || 'No description available');
                $('#viewCredits').text(response.course.credits);
                $('#viewSemester').text(response.course.semester);
                $('#viewStartDate').text(formatDate(response.course.startDate));
                $('#viewEndDate').text(formatDate(response.course.endDate));
                
                // Build student list if available
                var studentList = $('#viewStudents');
                studentList.empty();
                
                if (response.students && response.students.length > 0) {
                    $.each(response.students, function(i, student) {
                        studentList.append(`<li>${student.full_name} <span class="enrollment-date">Enrolled: ${formatDate(student.enrollment_date)}</span></li>`);
                    });
                } else {
                    studentList.append('<li>No students enrolled</li>');
                }
                
                // Build assignment list if available
                var assignmentList = $('#viewAssignments');
                assignmentList.empty();
                
                if (response.assignments && response.assignments.length > 0) {
                    $.each(response.assignments, function(i, assignment) {
                        assignmentList.append(`<li>${assignment.title} <span class="due-date">Due: ${formatDate(assignment.due_date)}</span></li>`);
                    });
                } else {
                    assignmentList.append('<li>No assignments created</li>');
                }
                
                // Show the view modal
                $('#viewModal').fadeIn();
            } else {
                showNotification('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error', 'Failed to load course details: ' + error, 'danger');
        }
    });
}

// Handle editing a course
function editCourse(courseId) {
    $.ajax({
        url: 'php/course_functions.php',
        type: 'GET',
        data: {
            action: 'view',
            id: courseId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the edit form with course details
                $('#editCourseId').val(response.course.id);
                $('#editCourseTitle').val(response.course.courseTitle);
                $('#editCourseCode').val(response.course.courseCode);
                $('#editCourseDescription').val(response.course.courseDescription || '');
                $('#editCredits').val(response.course.credits);
                $('#editSemester').val(response.course.semester);
                $('#editStartDate').val(response.course.startDate);
                $('#editEndDate').val(response.course.endDate);
                
                // Show the edit modal
                $('#editModal').fadeIn();
            } else {
                showNotification('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error', 'Failed to load course details: ' + error, 'danger');
        }
    });
}

// Submit course edit form
function submitEditCourseForm() {
    var formData = new FormData($('#editCourseForm')[0]);
    formData.append('action', 'update');
    
    $.ajax({
        url: 'php/course_functions.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update the course card in the UI
                var course = response.course;
                var courseCard = $(`.course-card[data-id="${course.id}"]`);
                
                courseCard.find('h3').text(course.courseTitle);
                courseCard.find('.course-code').text(course.courseCode);
                courseCard.find('.course-description').text(course.courseDescription || '');
                courseCard.find('.stat:nth-child(2) span').text(formatDate(course.startDate) + ' to ' + formatDate(course.endDate));
                
                // Hide modal and show success message
                $('#editModal').fadeOut();
                showNotification('Course Updated', response.message, 'success');
                
                // Refresh calendars to show updated events
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }
                if (window.miniCalendar) {
                    window.miniCalendar.refetchEvents();
                }
                
                // Update notification badge
                updateNotificationBadge();
            } else {
                showNotification('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error', 'Failed to update course: ' + error, 'danger');
        }
    });
}

// Handle deleting a course
function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course? This will remove all assignments, grades, and enrollments associated with this course.')) {
        $.ajax({
            url: 'php/course_functions.php',
            type: 'POST',
            data: {
                action: 'delete',
                id: courseId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove the course card from UI
                    $(`.course-card[data-id="${courseId}"]`).fadeOut(function() {
                        $(this).remove();
                    });
                    
                    // Show success message
                    showNotification('Course Deleted', response.message, 'success');
                    
                    // Refresh calendars to remove deleted events
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }
                    if (window.miniCalendar) {
                        window.miniCalendar.refetchEvents();
                    }
                    
                    // Update notification badge
                    updateNotificationBadge();
                } else {
                    showNotification('Error', response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error', 'Failed to delete course: ' + error, 'danger');
            }
        });
    }
}




function updateCardElement(card, updateImage, updateCourseTitle, updateDescription) {
    card.querySelector('.progress img').src = updateImage ? updateImage.name : card.querySelector('.progress img').src;
    card.querySelector('h4').innerText = updateCourseTitle;
    card.querySelector('p').innerText = updateDescription;
}


function selectCard(card) {
    // Deselect any previously selected card
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
       selectedCard.classList.remove('selected');
    }
 
    // Select the clicked card
    card.classList.add('selected');
 
    // Populate the update form fields with card data
    document.getElementById('updateImage').value = ''; // Set to empty string
    document.getElementById('updateCourseTitle').value = card.querySelector('h4').innerText;
    document.getElementById('updateDescription').value = card.querySelector('p').innerText;
 }
 

function deleteCard(deleteOverlay) {
    // Find the parent card and remove it
    var cardToDelete = deleteOverlay.parentElement;
    cardToDelete.remove();

    // Make an AJAX request to delete the card
    fetch('course-dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&id=${cardToDelete.dataset.ID}`,
    })
    .then(response => response.text())
    .then(data => console.log(data))
    .catch(error => console.error('Error:', error));
}

function deleteSelectedCard() {
    // Find the selected card and remove it
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        selectedCard.remove();

        // Make an AJAX request to delete the selected card
        fetch('course-dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${selectedCard.dataset.ID}`,
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
    }
}
