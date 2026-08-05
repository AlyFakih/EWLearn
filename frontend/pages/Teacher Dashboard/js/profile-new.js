$(document).ready(function() {
    setupModalHandlers();
    setupCardSelection();
    updateNotificationCount();
    
    // Poll for notifications every 60 seconds
    setInterval(updateNotificationCount, 60000);
});

// Setup modal handlers
function setupModalHandlers() {
    // Show Add Skill Modal
    $('#showForm').click(function() {
        $('#addModal').modal('show');
    });
    
    // Update skill button
    $('#updateButton').click(function() {
        const selectedCard = $('.skill-card.selected');
        
        if (selectedCard.length === 0) {
            showNotification('Error', 'Please select a skill to update', 'danger');
            return;
        }
        
        const skillId = selectedCard.data('id');
        
        // Find the skill details from the card
        const iconClass = selectedCard.find('.left i').attr('class');
        const title = selectedCard.find('.left h5').text();
        const progress = selectedCard.find('.number h3').text().replace('%', '');
        const experience = selectedCard.find('.exp').text();
        
        // Set icon value based on class
        let iconValue = 'code';
        if (iconClass.includes('fa-database')) iconValue = 'database';
        else if (iconClass.includes('fa-laptop-code')) iconValue = 'laptop';
        else if (iconClass.includes('fa-desktop')) iconValue = 'computer';
        else if (iconClass.includes('fa-network-wired')) iconValue = 'network';
        else if (iconClass.includes('fa-pen')) iconValue = 'drawing';
        else if (iconClass.includes('fa-shield-alt')) iconValue = 'security';
        
        // Fill the update form with current values
        $('#update_skill_id').val(skillId);
        $('#updatedIcon').val(iconValue);
        $('#updatedTitle').val(title);
        $('#updatedProgress').val(progress);
        $('#updatedDuration').val(experience);
        
        // Show the modal
        $('#updateModal').modal('show');
    });
    
    // Delete skill button
    $('#deleteButton').click(function() {
        const selectedCard = $('.skill-card.selected');
        
        if (selectedCard.length === 0) {
            showNotification('Error', 'Please select a skill to delete', 'danger');
            return;
        }
        
        const skillId = selectedCard.data('id');
        
        if (confirm('Are you sure you want to delete this skill? This action cannot be undone.')) {
            deleteSelectedCard(skillId);
        }
    });
}

// Setup card selection
function setupCardSelection() {
    $(document).on('click', '.skill-card', function() {
        $('.skill-card').removeClass('selected');
        $(this).addClass('selected');
    });
}

// Add new skill card
function addCard() {
    // Get form values
    const subjectIcon = $('#subjectIcon').val();
    const subjectName = $('#subjectName').val();
    const progressPercentage = $('#progressPercentage').val();
    const experience = $('#experience').val();
    
    // Validate inputs
    if (!subjectIcon || !subjectName || !progressPercentage || !experience) {
        showNotification('Error', 'All fields are required', 'danger');
        return;
    }
    
    if (progressPercentage < 0 || progressPercentage > 100) {
        showNotification('Error', 'Percentage must be between 0 and 100', 'danger');
        return;
    }
    
    // Send AJAX request to add card
    $.ajax({
        url: 'php/profile_functions.php',
        type: 'POST',
        data: {
            action: 'add',
            subjectIcon: subjectIcon,
            subjectName: subjectName,
            progressPercentage: progressPercentage,
            experience: experience
        },
        success: function(response) {
            try {
                // Check if response is JSON (error)
                const result = JSON.parse(response);
                if (!result.success) {
                    showNotification('Error', result.message, 'danger');
                }
            } catch (e) {
                // Response is HTML (success)
                if ($('#subjectContainer .col-12').length) {
                    $('#subjectContainer').empty(); // Remove "no skills found" message
                }
                
                // Add the new card in a column wrapper
                $('#subjectContainer').append(`
                    <div class="col-md-6 col-lg-4 mb-4">
                        ${response}
                    </div>
                `);
                
                // Hide the modal and reset form
                $('#addModal').modal('hide');
                $('#cardForm')[0].reset();
                
                showNotification('Success', 'Skill added successfully!', 'success');
                
                // Update the progress bar animation
                setProgressAnimation();
            }
        },
        error: function() {
            showNotification('Error', 'Failed to add skill. Please try again.', 'danger');
        }
    });
}

// Update skill card
function updateCard() {
    // Get form values
    const skillId = $('#update_skill_id').val();
    const subjectIcon = $('#updatedIcon').val();
    const subjectName = $('#updatedTitle').val();
    const progressPercentage = $('#updatedProgress').val();
    const experience = $('#updatedDuration').val();
    
    // Validate inputs
    if (!subjectIcon || !subjectName || !progressPercentage || !experience) {
        showNotification('Error', 'All fields are required', 'danger');
        return;
    }
    
    if (progressPercentage < 0 || progressPercentage > 100) {
        showNotification('Error', 'Percentage must be between 0 and 100', 'danger');
        return;
    }
    
    // Send AJAX request to update card
    $.ajax({
        url: 'php/profile_functions.php',
        type: 'POST',
        data: {
            action: 'update',
            id: skillId,
            subjectIcon: subjectIcon,
            subjectName: subjectName,
            progressPercentage: progressPercentage,
            experience: experience
        },
        success: function(response) {
            try {
                // Check if response is JSON (error)
                const result = JSON.parse(response);
                if (!result.success) {
                    showNotification('Error', result.message, 'danger');
                }
            } catch (e) {
                // Response is HTML (success)
                const cardContainer = $(`.skill-card[data-id="${skillId}"]`).closest('.col-md-6');
                cardContainer.html(response);
                
                // Hide the modal
                $('#updateModal').modal('hide');
                
                showNotification('Success', 'Skill updated successfully!', 'success');
                
                // Update the progress bar animation
                setProgressAnimation();
            }
        },
        error: function() {
            showNotification('Error', 'Failed to update skill. Please try again.', 'danger');
        }
    });
}

// Delete skill card
function deleteSelectedCard(skillId) {
    $.ajax({
        url: 'php/profile_functions.php',
        type: 'POST',
        data: {
            action: 'delete',
            id: skillId
        },
        success: function(response) {
            if (response === '1') {
                // Remove the card from the UI
                $(`.skill-card[data-id="${skillId}"]`).closest('.col-md-6').fadeOut(400, function() {
                    $(this).remove();
                    
                    // Show "no skills" message if no cards left
                    if ($('#subjectContainer .skill-card').length === 0) {
                        $('#subjectContainer').html("<div class='col-12'><p>No skills found. Add your first skill!</p></div>");
                    }
                });
                
                showNotification('Success', 'Skill deleted successfully!', 'success');
            } else {
                try {
                    const result = JSON.parse(response);
                    showNotification('Error', result.message, 'danger');
                } catch (e) {
                    showNotification('Error', 'Failed to delete skill. Please try again.', 'danger');
                }
            }
        },
        error: function() {
            showNotification('Error', 'Failed to delete skill. Please try again.', 'danger');
        }
    });
}

// Set progress bar animation for all skill cards
function setProgressAnimation() {
    $('.skill-card').each(function() {
        const id = $(this).data('id');
        const percent = parseFloat($(this).find('.number h3').text());
        
        // Calculate the progress circle properties
        const radius = 52;
        const circumference = 2 * Math.PI * radius;
        const dashoffset = circumference - (percent / 100) * circumference;
        
        // Apply the animation
        $(`.meter-${id}`).css({
            'stroke-dasharray': circumference,
            'stroke-dashoffset': dashoffset
        });
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
