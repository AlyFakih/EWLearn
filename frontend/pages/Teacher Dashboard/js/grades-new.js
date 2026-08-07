$(document).ready(function() {
    setupModalHandlers();
    setupFormHandlers();
    setupTableHandlers();
    initNotifications();
    
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
    }
    
    // Setup form handlers
    function setupFormHandlers() {
        // Add new grade form submission
        $('#adddata').on('click', function() {
            const formData = $('#addForm').serialize();
            
            $.ajax({
                url: 'php/grade_functions.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        // Check if response is JSON or HTML
                        if (response.trim().startsWith('<tr>')) {
                            // If response is HTML (row to be appended)
                            if ($('#ajax-response tr td.text-center').length) {
                                $('#ajax-response').empty();
                            }
                            
                            $('#ajax-response').append(response);
                            $('#addModal').modal('hide');
                            $('#addForm')[0].reset();
                            
                            showNotification('Success', 'Grade added successfully!', 'success');
                            
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
                    showNotification('Error', 'Failed to add grade. Please try again.', 'danger');
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
                    $('#table1 tbody').append('<tr class="no-results"><td colspan="4" class="text-center">No matching grades found</td></tr>');
                }
            } else {
                $('#table1 tbody tr.no-results').remove();
            }
        });
        
        // View grade details
        $(document).on('click', '.view-grade', function() {
            const studentId = $(this).data('id');
            
            $.ajax({
                url: 'php/grade_functions.php',
                type: 'GET',
                data: {
                    action: 'view',
                    id: studentId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        
                        if (result.success) {
                            const grade = result.grade;
                            
                            // Populate view modal
                            $('#view_student_id').text(grade.student_id);
                            $('#view_student_name').text(grade.student_name);
                            $('#view_student_email').text(grade.student_email);
                            $('#view_course').text(`${grade.courseCode} - ${grade.courseTitle}`);
                            $('#view_term').text(grade.term);
                            $('#view_date').text(grade.created_at);
                            $('#view_grade').text(grade.grade);
                            
                            // Show the modal
                            $('#viewModal').modal('show');
                        } else {
                            showNotification('Error', result.message, 'danger');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        showNotification('Error', 'Failed to load grade details', 'danger');
                    }
                },
                error: function() {
                    showNotification('Error', 'Failed to load grade details', 'danger');
                }
            });
        });
        
        // Edit grade button
        $(document).on('click', '#table1 .edit', function() {
            $('#table1').find('.save, .cancel').hide();
            $('#table1').find('.edit').show();
            $(this).hide().siblings('.save, .cancel').show();
            
            $(this).closest('tr').find('td[data-id]').each(function() {
                // Only make the grade column editable, not the ID or name
                if ($(this).data('id') === 'student_grade') {
                    $(this).attr('contenteditable', 'true').focus();
                }
            });
        });
        
        // Cancel edit
        $(document).on('click', '#table1 .cancel', function() {
            $('#table1').find('.save, .cancel').hide();
            $(this).hide().siblings('.edit').show();
            
            $(this).closest('tr').find('td[data-id]').each(function() {
                $(this).attr('contenteditable', 'false');
            });
        });
        
        // Save grade changes
        $(document).on('click', '#table1 .save', function() {
            const $btn = $(this);
            $('#table1').find('.save, .cancel').hide();
            $btn.hide().siblings('.edit').show();
            
            const studentId = $btn.data('id');
            const grade = $btn.closest('tr').find('td[data-id="student_grade"]').text();
            
            // Make all cells non-editable
            $btn.closest('tr').find('td[data-id]').each(function() {
                $(this).attr('contenteditable', 'false');
            });
            
            $.ajax({
                url: 'php/grade_functions.php',
                type: 'POST',
                data: {
                    student_id: studentId,
                    student_grade: grade,
                    action: 'update'
                },
                success: function(response) {
                    try {
                        // Check if response is HTML (table refresh)
                        if (response.trim().startsWith('<tr>')) {
                            $('#ajax-response').html(response);
                            showNotification('Success', 'Grade updated successfully!', 'success');
                            
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
                    showNotification('Error', 'Failed to update grade. Please try again.', 'danger');
                }
            });
        });
        
        // Delete grade
        $(document).on('click', '#table1 .del', function() {
            const ele = this;
            const deleteId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this grade? This action cannot be undone.')) {
                $.ajax({
                    url: 'php/grade_functions.php',
                    type: 'POST',
                    data: {
                        id: deleteId,
                        action: 'delete'
                    },
                    success: function(response) {
                        if (response == '1') {
                            $(ele).closest('tr').css('background', '#ff6b6b');
                            $(ele).closest('tr').fadeOut(800, function() {
                                $(this).remove();
                                
                                // Show empty state if no more rows
                                if ($('#table1 tbody tr').length === 0) {
                                    $('#table1 tbody').append('<tr><td colspan="4" class="text-center">No grades available</td></tr>');
                                }
                            });
                            
                            showNotification('Success', 'Grade deleted successfully', 'success');
                            
                            // Update notification badge
                            updateNotificationCount();
                        } else {
                            showNotification('Error', 'Failed to delete grade. Please try again.', 'danger');
                        }
                    },
                    error: function() {
                        showNotification('Error', 'Failed to delete grade. Please try again.', 'danger');
                    }
                });
            }
        });
    }
    
    // Initialize notifications
    function initNotifications() {
        // Initial check for notifications
        updateNotificationCount();
        
        // Poll for new notifications every 60 seconds
        setInterval(updateNotificationCount, 60000);
    }
    
    // Update notification count
    function updateNotificationCount() {
        $.ajax({
            url: '../../common/notification_api.php?action=count',
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
});
