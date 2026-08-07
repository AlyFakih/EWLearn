// Initialize components when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar with event loading
    initCalendar();
    
    // Initialize notification handling
    initNotifications();
    
    // Initialize mini calendar in sidebar
    initMiniCalendar();
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

function showPopup() {
    document.getElementById('popup').style.display = 'block';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

function addCard() {
    var studentImageInput = document.getElementById('studentImage');
    var studentImageFile = studentImageInput.files[0]; // Get the selected file
    var studentID = document.getElementById('studentID').value;
    var studentName = document.getElementById('studentName').value;
    var studentMajor = document.getElementById('studentMajor').value;
    var studentYear = document.getElementById('studentYear').value;

    // Check if an image file is selected
    if (studentImageFile) {
        // Create FormData object and append form fields
        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('studentImage', studentImageFile);
        formData.append('studentID', studentID);
        formData.append('studentName', studentName);
        formData.append('studentMajor', studentMajor);
        formData.append('studentYear', studentYear);

        // Make an AJAX request to add the new card
        fetch('php/attendance_functions.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            // Check the response and update the page accordingly
            if (data.success) {
                // Create a new card element
                var newCard = document.createElement('div');
                newCard.className = 'eg';
                newCard.dataset.id = studentID;

                newCard.innerHTML = `
                    <div class='progress'>
                        <img src='${studentImageFile.name}' alt='Progress Image'>
                    </div>
                    <h4>${studentName}</h4>
                    <p>${studentMajor}</p>
                    <small class='text-muted'>${studentYear}</small>
                    <br>
                    <a href='#'><button>Profile</button></a>`;

                // Add click event listener to the new card for selecting
                newCard.addEventListener('click', function() {
                    selectCard(this);
                });

                // Append the new card to the subjects container
                document.getElementById('subjectContainer').appendChild(newCard);

                // Reinitialize the Swiper instance
                var swiper = new Swiper('.swiper-container', {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                });

                // Close the popup and reset the form fields
                closePopup();
                document.getElementById('cardForm').reset();
            } else {
                console.error('Error:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    } else {
        alert('Please select an image file.');
    }
}

function createCardElement(studentID, imageName, studentName, studentMajor, studentYear) {
    var newCard = document.createElement('div');
    newCard.className = 'eg';
    newCard.dataset.id = studentID; // Set the data-id attribute

    newCard.innerHTML = `
        <div class='progress'>
            <img src='${imageName}' alt='Progress Image'>
        </div>
        <h4>${studentName}</h4>
        <p>${studentMajor}</p>
        <small class='text-muted'>${studentYear}</small>
        <br>
        <a href='#'><button>Profile</button></a>`;

    return newCard;
}

function showUpdateForm() {
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        document.getElementById('updateStudentImage').value = ''; // Set to empty string
        document.getElementById('updateStudentName').value = selectedCard.querySelector('h4').textContent;
        document.getElementById('updateStudentMajor').value = selectedCard.querySelector('p').textContent;
        document.getElementById('updateStudentYear').value = selectedCard.querySelector('.text-muted').textContent;

        // Display the update form
        document.getElementById('updateForm').style.display = 'block';
    } else {
        alert('Please select a card to update.');
    }
}

function hideUpdateForm() {
    // Hide the update form
    document.getElementById('updateForm').style.display = 'none';
}

function updateCard() {
    var updateStudentImageInput = document.getElementById('updateStudentImage');
    var updateStudentImage = updateStudentImageInput.files[0]; // Get the selected file

    var updateStudentName = document.getElementById('updateStudentName').value;
    var updateStudentMajor = document.getElementById('updateStudentMajor').value;
    var updateStudentYear = document.getElementById('updateStudentYear').value;

    var cardToUpdate = document.querySelector('.eg.selected');

    if (cardToUpdate) {
        // Make an AJAX request to update the card in the database
        var formData = new FormData();
        formData.append('action', 'update');
        formData.append('studentID', cardToUpdate.dataset.id);
        formData.append('studentName', updateStudentName);
        formData.append('studentMajor', updateStudentMajor);
        formData.append('studentYear', updateStudentYear);

        if (updateStudentImage) {
            formData.append('studentImage', updateStudentImage);
        }

        fetch('php/attendance_functions.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);

            // Check the response and update the page accordingly
            if (data.success) {
                // Update the content of the selected card
                updateCardElement(cardToUpdate, updateStudentImage, updateStudentName, updateStudentMajor, updateStudentYear);
            } else {
                console.error('Error:', data.message);
            }

            // Hide the update form
            hideUpdateForm();
        })
        .catch(error => console.error('Error:', error));
    }
}

function updateCardElement(card, updateStudentImage, updateStudentName, updateStudentMajor, updateStudentYear) {
    card.querySelector('.progress img').src = updateStudentImage ? updateStudentImage.name : card.querySelector('.progress img').src;
    card.querySelector('h4').innerText = updateStudentName;
    card.querySelector('p').innerText = updateStudentMajor;
    card.querySelector('.text-muted').innerText = updateStudentYear;
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
    document.getElementById('updateStudentImage').value = ''; // Set to empty string
    document.getElementById('updateStudentName').value = card.querySelector('h4').innerText;
    document.getElementById('updateStudentMajor').value = card.querySelector('p').innerText;
    document.getElementById('updateStudentYear').value = card.querySelector('.text-muted').innerText;
}

function deleteCard(deleteOverlay) {
    // Find the parent card and remove it
    var cardToDelete = deleteOverlay.parentElement;
    cardToDelete.remove();

    // Make an AJAX request to delete the card
    fetch('php/attendance_functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&studentID=${cardToDelete.dataset.id}`,
    })
    .then(response => response.json())
    .then(data => console.log(data))
    .catch(error => console.error('Error:', error));
}

function deleteSelectedCard() {
    // Find the selected card and remove it
    var selectedCard = document.querySelector('.eg.selected');
    if (selectedCard) {
        selectedCard.remove();

        // Make an AJAX request to delete the selected card
        fetch('php/attendance_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&studentID=${selectedCard.dataset.id}`,
        })
        .then(response => response.json())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
    }
}

//script for the table

function showAddModal() {
    $('#addModal').fadeIn();
}

// Hide the add form when the "Cancel" button is clicked
$('#closeForm').on('click', function() {
    $('#addModal').fadeOut();
});

$(document).ready(function() {
    // Show the add form when the "Add" button is clicked
    $('#showForm').on('click', function(e) {
        e.preventDefault(); // Prevent the default form submission
        e.stopPropagation(); // Stop event propagation
        showAddModal();
    });

    // insertion function (Add button)
    $("#adddata").on("click", function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = $("#addForm").serialize();
        formData += "&action=add"; // Add action parameter

        $.ajax({
            url: "php/attendance_functions.php",
            type: "POST",
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success notification
                    showNotification('Success', response.message, 'success');
                    
                    // Append the new row to the existing table
                    $('#table1 tbody').append(response.html);
                    
                    // Clear the input fields
                    $("#addForm")[0].reset();
                    
                    // Hide the add form after successful addition
                    $('#addModal').fadeOut();
                } else {
                    // Show error notification
                    showNotification('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error', 'Failed to add attendance record: ' + error, 'error');
            },
            complete: function() {
                // Scroll to the bottom of the table to show the newly added row
                $('#table1').animate({
                    scrollTop: $('#table1 tbody').height()
                }, 1000);
                
                // Refresh calendars to show new attendance events
                refreshCalendars();
            }
        });
    });

    // update
    $(document).on('click', '#table1 .edit', function() {
        $('#table1').find('.save, .cancel').hide();
        $('#table1').find('.edit').show();
        $(this).hide().siblings('.save, .cancel').show();

        $(this).closest('tr').find('td[data-id]').each(function() {
            if (!$(this).is(':last-child')) { // Exclude the last child (buttons)
                var inp = $(this).find('input');
                if (inp.length) {
                    $(this).text(inp.val());
                } else {
                    $(this).attr('contenteditable', 'true');
                }
            }
        });
    });

    // cancel
    $(document).on('click', '#table1 .cancel', function() {
        $('#table1').find('.save, .cancel').hide();
        $(this).hide().siblings('.edit').show();
        $(this).closest('tr').find('td[data-id]').each(function() {
            $(this).attr('contenteditable', 'false');
        });
    });

    // insertion function (SAVE button)
    $(document).on('click', '#table1 .save', function() {
        var $btn = $(this);
        $('#table1').find('.save, .cancel').hide();
        $btn.hide().siblings('.edit').show();
        params = "";

        var id = $btn.data('id');

        $btn.closest('tr').find('td[data-id]').each(function() {
            $(this).attr('contenteditable', 'false');
            if (params != "") {
                params += "&";
            }
            params += $(this).data('id') + "=" + $(this).text();
        });

        params += "&id=" + id;

        if (params != "") {
            $.ajax({
                url: "php/attendance_functions.php",
                type: "POST",
                data: params,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Success', response.message, 'success');
                    } else {
                        showNotification('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error', 'Failed to update attendance record: ' + error, 'error');
                }
            });
        }
    });

    // delete
    $(document).on('click', '#table1 .del', function() {
        var ele = this;
        var deleteid = $(this).data('id');

        var confirmalert = confirm('Are you sure you want to delete it ?');

        if (confirmalert == true) {
            $.ajax({
                url: "php/attendance_functions.php",
                type: "POST",
                data: {
                    id: deleteid,
                    action: 'delete'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Success', response.message, 'success');
                        $(ele).closest('tr').css('background', '#ffcccb');
                        $(ele).closest('tr').fadeOut(800, function() {
                            $(this).remove();
                            // Refresh calendars to reflect deleted attendance
                            refreshCalendars();
                        });
                    } else {
                        showNotification('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error', 'Failed to delete attendance record: ' + error, 'error');
                }
            });
        }
    });

    // Hide the add form when the "Close" button is clicked
    $('#addModal .close').on('click', function() {
        $('#addModal').fadeOut();
    });

    // Hide the add form when clicking outside the form
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#addModal').length && !$(e.target).is('#showForm')) {
            $('#addModal').fadeOut();
        }
    });
});

// Refresh all calendars
function refreshCalendars() {
    if (window.calendar) {
        window.calendar.refetchEvents();
    }
    
    // Also refresh mini calendar if it exists
    if (window.miniCalendar) {
        window.miniCalendar.refetchEvents();
    }
}

// Show notification to the user
function showNotification(title, message, type) {
    // If you have a notification system
    if ($.notify) {
        $.notify({
            title: title,
            message: message
        }, {
            type: type,
            placement: {
                from: "top",
                align: "right"
            },
            z_index: 9999,
            delay: 5000
        });
    } else {
        // Fallback to alert
        if (type === 'error') {
            alert(title + ': ' + message);
        } else {
            // For success messages, show a temporary toast-like element
            var toast = $('<div class="toast-notification ' + type + '">' +
                '<strong>' + title + '</strong>: ' + message +
                '</div>');
            $('body').append(toast);
            toast.fadeIn();
            setTimeout(function() {
                toast.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
}