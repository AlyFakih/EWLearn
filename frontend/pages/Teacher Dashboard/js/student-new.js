$(document).ready(function() {
    setupModalHandlers();
    setupTableHandlers();
    initNotifications();
    
    // Poll for notifications every 60 seconds
    setInterval(updateNotificationCount, 60000);
});

// Setup modal handlers
function setupModalHandlers() {
    // Show the add form when the "Add" button is clicked
    $('#showForm').on('click', function() {
        $('#addModal').modal('show');
    });
    
    // Close modal buttons
    $('.modal-close, .cancel-modal').click(function() {
        $(this).closest('.modal').modal('hide');
    });
    
    // Add new student form submission
    $('#adddata').on('click', function() {
        // Get form values
        const studentID = $('#newstudentID').val();
        const name = $('#newName').val();
        const email = $('#newEmail').val();
        const mobile = $('#newMobile').val();
        const country = $('#newCountry').val();
        
        // Validate inputs
        if (!studentID || !name || !email || !mobile || !country) {
            showNotification('Error', 'All fields are required', 'danger');
            return;
        }
        
        // Send AJAX request
        $.ajax({
            url: 'php/student_functions.php',
            type: 'POST',
            data: {
                newstudentID: studentID,
                newName: name,
                newEmail: email,
                newMobile: mobile,
                newCountry: country
            },
            success: function(response) {
                try {
                    // Check if response is JSON or HTML
                    const jsonResponse = JSON.parse(response);
                    
                    if (!jsonResponse.success) {
                        showNotification('Error', jsonResponse.message, 'danger');
                    }
                } catch (e) {
                    // HTML response (success)
                    if (response.trim().startsWith('<tr>')) {
                        // If table shows "no students" message, clear it
                        if ($('#ajax-response tr td.text-center').length) {
                            $('#ajax-response').empty();
                        }
                        
                        $('#ajax-response').append(response);
                        $('#addModal').modal('hide');
                        $('#addForm')[0].reset();
                        
                        showNotification('Success', 'Student added successfully!', 'success');
                        
                        // Update notification badge
                        updateNotificationCount();
                    } else {
                        showNotification('Error', 'An unexpected error occurred', 'danger');
                    }
                }
            },
            error: function() {
                showNotification('Error', 'Failed to add student. Please try again.', 'danger');
            }
        });
    });
    
    // View student details
    $(document).on('click', '.view-student', function() {
        const studentId = $(this).data('id');
        
        $.ajax({
            url: 'php/student_functions.php',
            type: 'GET',
            data: {
                action: 'view',
                id: studentId
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    
                    if (result.success) {
                        // Populate student info
                        $('#view_student_id').text(result.student.ID);
                        $('#view_student_name').text(result.student.NAME);
                        $('#view_student_email').text(result.student.EMAIL);
                        $('#view_student_mobile').text(result.student.MOBILE);
                        $('#view_student_country').text(result.student.COUNTRY);
                        
                        // Populate courses
                        if (result.courses && result.courses.length > 0) {
                            let coursesHtml = '<ul class="list-group">';
                            result.courses.forEach(course => {
                                coursesHtml += `<li class="list-group-item">${course.courseCode} - ${course.courseTitle}</li>`;
                            });
                            coursesHtml += '</ul>';
                            $('#view_courses').html(coursesHtml);
                        } else {
                            $('#view_courses').html('<p class="text-muted">No courses found</p>');
                        }
                        
                        // Populate grades
                        if (result.grades && result.grades.length > 0) {
                            let gradesHtml = '<table class="table table-sm">';
                            gradesHtml += '<thead><tr><th>Course</th><th>Grade</th></tr></thead><tbody>';
                            result.grades.forEach(grade => {
                                gradesHtml += `<tr>
                                    <td>${grade.courseCode} - ${grade.courseTitle}</td>
                                    <td>${grade.grade}</td>
                                </tr>`;
                            });
                            gradesHtml += '</tbody></table>';
                            $('#view_grades').html(gradesHtml);
                        } else {
                            $('#view_grades').html('<p class="text-muted">No grades found</p>');
                        }
                        
                        // Populate attendance
                        if (result.attendance && result.attendance.length > 0) {
                            let attendanceHtml = '<table class="table table-sm">';
                            attendanceHtml += '<thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead><tbody>';
                            result.attendance.forEach(record => {
                                // Define status badge color
                                let statusClass = '';
                                switch (record.status) {
                                    case 'present': statusClass = 'success'; break;
                                    case 'absent': statusClass = 'danger'; break;
                                    case 'online': statusClass = 'info'; break;
                                    default: statusClass = 'secondary';
                                }
                                
                                attendanceHtml += `<tr>
                                    <td>${record.date}</td>
                                    <td>${record.courseCode}</td>
                                    <td><span class="badge badge-${statusClass}">${record.status}</span></td>
                                </tr>`;
                            });
                            attendanceHtml += '</tbody></table>';
                            $('#view_attendance').html(attendanceHtml);
                        } else {
                            $('#view_attendance').html('<p class="text-muted">No attendance records found</p>');
                        }
                        
                        // Show modal
                        $('#viewModal').modal('show');
                    } else {
                        showNotification('Error', result.message, 'danger');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showNotification('Error', 'Failed to load student details', 'danger');
                }
            },
            error: function() {
                showNotification('Error', 'Failed to load student details', 'danger');
            }
        });
    });
}

// Setup table handlers
function setupTableHandlers() {
    // Search functionality
    $('#searchinstructor').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        
        $('#table1 tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchText) > -1);
        });
        
        // Show "no results" message if needed
        if ($('#table1 tbody tr:visible').length === 0) {
            if ($('#table1 tbody tr.no-results').length === 0) {
                $('#table1 tbody').append('<tr class="no-results"><td colspan="6" class="text-center">No matching students found</td></tr>');
            }
        } else {
            $('#table1 tbody tr.no-results').remove();
        }
    });
    
    // Edit student button
    $(document).on('click', '#table1 .edit', function() {
        $('#table1').find('.save, .cancel').hide();
        $('#table1').find('.edit').show();
        $(this).hide().siblings('.save, .cancel').show();
        
        $(this).closest('tr').find('td[data-id]').each(function() {
            // Don't allow editing of student ID
            if ($(this).data('id') !== 'student_id') {
                $(this).attr('contenteditable', 'true');
            }
        });
        
        // Focus on the name field
        $(this).closest('tr').find('td[data-id="student_name"]').focus();
    });
    
    // Cancel edit
    $(document).on('click', '#table1 .cancel', function() {
        $('#table1').find('.save, .cancel').hide();
        $(this).hide().siblings('.edit').show();
        
        $(this).closest('tr').find('td[data-id]').each(function() {
            $(this).attr('contenteditable', 'false');
        });
    });
    
    // Save student changes
    $(document).on('click', '#table1 .save', function() {
        const $btn = $(this);
        $('#table1').find('.save, .cancel').hide();
        $btn.hide().siblings('.edit').show();
        
        const studentId = $btn.data('id');
        const row = $btn.closest('tr');
        const name = row.find('td[data-id="student_name"]').text();
        const email = row.find('td[data-id="student_email"]').text();
        const mobile = row.find('td[data-id="student_mobile"]').text();
        const country = row.find('td[data-id="student_country"]').text();
        
        // Make all cells non-editable
        row.find('td[data-id]').each(function() {
            $(this).attr('contenteditable', 'false');
        });
        
        $.ajax({
            url: 'php/student_functions.php',
            type: 'POST',
            data: {
                action: 'update',
                student_id: studentId,
                student_name: name,
                student_email: email,
                student_mobile: mobile,
                student_country: country
            },
            success: function(response) {
                try {
                    // Check if response is HTML (table refresh)
                    if (response.trim().startsWith('<tr>')) {
                        $('#ajax-response').html(response);
                        showNotification('Success', 'Student updated successfully!', 'success');
                        
                        // Update notification badge
                        updateNotificationCount();
                    } else {
                        // Try to parse as JSON in case of error
                        const result = JSON.parse(response);
                        if (!result.success) {
                            showNotification('Error', result.message, 'danger');
                        }
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    showNotification('Error', 'An unexpected error occurred. Please try again.', 'danger');
                }
            },
            error: function() {
                showNotification('Error', 'Failed to update student. Please try again.', 'danger');
            }
        });
    });
    
    // Delete student
    $(document).on('click', '#table1 .del', function() {
        const ele = this;
        const deleteId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this student? This action cannot be undone and will remove all associated records (grades, attendance, etc).')) {
            $.ajax({
                url: 'php/student_functions.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: deleteId
                },
                success: function(response) {
                    if (response == '1') {
                        $(ele).closest('tr').css('background', '#ff6b6b');
                        $(ele).closest('tr').fadeOut(800, function() {
                            $(this).remove();
                            
                            // Show empty state if no more rows
                            if ($('#table1 tbody tr').length === 0) {
                                $('#table1 tbody').append('<tr><td colspan="6" class="text-center">No students available</td></tr>');
                            }
                        });
                        
                        showNotification('Success', 'Student deleted successfully', 'success');
                        
                        // Update notification badge
                        updateNotificationCount();
                    } else {
                        try {
                            const result = JSON.parse(response);
                            showNotification('Error', result.message, 'danger');
                        } catch (e) {
                            showNotification('Error', 'Failed to delete student. Please try again.', 'danger');
                        }
                    }
                },
                error: function() {
                    showNotification('Error', 'Failed to delete student. Please try again.', 'danger');
                }
            });
        }
    });
}

// Initialize notifications
function initNotifications() {
    // Initial check for notifications
    updateNotificationCount();
}

// Update notification count
function updateNotificationCount() {
    $.ajax({
        url: '../common/get_notifications_count.php',
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
    // Toast notification using Bootstrap's toast component
    const toast = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header bg-${type}">
                <strong class="mr-auto text-white">${title}</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Add toast to container
    const toastContainer = $('.toast-container');
    if (toastContainer.length === 0) {
        $('body').append('<div class="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
    }
    $('.toast-container').append(toast);
    
    // Show the toast
    $('.toast').toast('show');
    
    // Remove toast after it's hidden
    $('.toast').on('hidden.bs.toast', function() {
        $(this).remove();
    });
}
