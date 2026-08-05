function showAddModal() {
  $('#addModal').fadeIn();
}

function showViewModal(submissionId) {
  $('#viewModal').fadeIn();
  $('#submissionDetails').html('<div class="loader">Loading...</div>');
  
  // Fetch submission details
  $.ajax({
    url: "php/get_submission.php",
    type: "GET",
    data: { id: submissionId },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        let html = '<div class="submission-details">';
        html += '<h3>' + response.data.assignment_title + '</h3>';
        html += '<div class="submission-meta">';
        html += '<p><strong>Student:</strong> ' + response.data.student_name + '</p>';
        html += '<p><strong>Course:</strong> ' + response.data.course_name + '</p>';
        html += '<p><strong>Submitted:</strong> ' + response.data.submitted_at + '</p>';
        html += '<p><strong>Status:</strong> <span class="status-badge status-' + response.data.status + '">' + response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1) + '</span></p>';
        html += '</div>';
        
        html += '<div class="submission-content">';
        html += '<h4>Submission Content:</h4>';
        html += '<div class="content-box">' + (response.data.content || 'No text content submitted') + '</div>';
        html += '</div>';
        
        if (response.data.file_path) {
          html += '<div class="submission-file">';
          html += '<h4>Attached File:</h4>';
          html += '<p><a href="' + response.data.file_path + '" target="_blank" class="file-download">Download File <i class="fas fa-download"></i></a></p>';
          html += '</div>';
        }
        
        if (response.data.grade) {
          html += '<div class="submission-grade">';
          html += '<h4>Grade:</h4>';
          html += '<p>' + response.data.grade_points + '/' + response.data.max_points + ' points</p>';
          html += '<p><strong>Feedback:</strong> ' + (response.data.feedback || 'No feedback provided') + '</p>';
          html += '</div>';
        }
        
        html += '</div>';
        
        $('#submissionDetails').html(html);
      } else {
        $('#submissionDetails').html('<div class="error">' + response.message + '</div>');
      }
    },
    error: function() {
      $('#submissionDetails').html('<div class="error">Failed to load submission details</div>');
    }
  });
}

function showGradeModal(submissionId) {
  $('#gradeModal').fadeIn();
  $('#submission_id').val(submissionId);
  
  // Get current grade if exists
  $.ajax({
    url: "php/get_submission.php",
    type: "GET",
    data: { id: submissionId },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        $('#max_points_display').text('(out of ' + response.data.max_points + ')');
        if (response.data.grade) {
          $('#grade_points').val(response.data.grade_points);
          $('#feedback').val(response.data.feedback);
        } else {
          $('#grade_points').val('');
          $('#feedback').val('');
        }
      }
    }
  });
}

// Hide modal when Cancel/Close buttons are clicked
$('#closeForm, #closeViewModal, #closeGradeModal').on('click', function() {
  $('.modal').fadeOut();
});

$(document).ready(function() {
  // Show the add form when the "Add" button is clicked
  $('#showForm').on('click', function(e) {
    e.preventDefault();  // Prevent the default form submission
    e.stopPropagation(); // Stop event propagation
    showAddModal();
  });

  // Handle assignment creation form submission
  $("#addAssignmentForm").on("submit", function(e) {
    e.preventDefault(); // Prevent the default form submission
    
    var formData = $(this).serialize();
    
    $.ajax({
      url: "php/create_assignment.php",
      type: "POST",
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Show success message
          alert('Assignment created successfully!');
          
          // Reload the page to show new assignment
          window.location.reload();
        } else {
          // Show error message
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('An error occurred while creating the assignment');
      },
      complete: function() {
        // Hide the modal
        $('#addModal').fadeOut();
      }
    });
  });

  // update
  $(document).on('click', '#table1 .edit', function () {
    $('#table1').find('.save, .cancel').hide();
    $('#table1').find('.edit').show();
    $(this).hide().siblings('.save, .cancel').show();

    $(this).closest('tr').find('td[data-id]').each(function () {
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
  $(document).on('click', '#table1 .cancel', function () {
    $('#table1').find('.save, .cancel').hide();
    $(this).hide().siblings('.edit').show();
    $(this).closest('tr').find('td[data-id]').each(function () {
      $(this).attr('contenteditable', 'false');
    });
  });

  // insertion function (SAVE button)
  $(document).on('click', '#table1 .save', function () {
    var $btn = $(this);
    $('#table1').find('.save, .cancel').hide();
    $btn.hide().siblings('.edit').show();
    params = "";

    var id = $btn.data('id');

    $btn.closest('tr').find('td[data-id]').each(function () {
      $(this).attr('contenteditable', 'false');
      if (params != "") {
        params += "&";
      }
      params += $(this).data('id') + "=" + $(this).text();
    });

    params += "&id=" + id;

    if (params != "") {
      $.ajax({
        url: "php/function.php",
        type: "POST",
        data: params,
        success: function (response) {
          $("#ajax-response").html(response);
        }
      });
    }
  });

  // delete
  $(document).on('click', '#table1 .del', function () {
    var ele = this;
    var deleteid = $(this).data('id');

    var confirmalert = confirm('Are you sure you want to delete this submission?');

    if (confirmalert == true) {
      $.ajax({
        url: "php/delete_submission.php",
        type: "POST",
        data: {
          id: deleteid
        },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            $(ele).closest('tr').css('background', 'tomato');
            $(ele).closest('tr').fadeOut(800, function () {
              $(this).remove();
            });
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function () {
          alert('An error occurred while deleting the submission');
        }
      });
    }
  });

  // View submission details
  $(document).on('click', '#table1 .view-btn', function () {
    var submissionId = $(this).data('id');
    showViewModal(submissionId);
  });

  // Open grade form
  $(document).on('click', '#table1 .grade-btn', function () {
    var submissionId = $(this).data('id');
    showGradeModal(submissionId);
  });

  // Handle grade form submission
  $("#gradeForm").on("submit", function (e) {
    e.preventDefault(); // Prevent the default form submission

    var formData = $(this).serialize();

    $.ajax({
      url: "php/grade_submission.php",
      type: "POST",
      data: formData,
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          // Show success message
          alert('Grade submitted successfully!');

          // Reload the page to show updated grade
          window.location.reload();
        } else {
          // Show error message
          alert('Error: ' + response.message);
        }
      },
      error: function () {
        alert('An error occurred while submitting the grade');
      },
      complete: function () {
        // Hide the modal
        $('#gradeModal').fadeOut();
      }
    });
  });

  // Hide the add form when the "Close" button is clicked
  $('#addModal .close').on('click', function () {
    $('#addModal').fadeOut();
  });

  // Hide modals when clicking outside the form
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.modal').length && 
        !$(e.target).is('#showForm') && 
        !$(e.target).closest('.view-btn').length && 
        !$(e.target).closest('.grade-btn').length) {
      $('.modal').fadeOut();
    }
  });

  // Initialize FullCalendar for mini-calendar if it exists
  const miniCalendarEl = document.getElementById('mini-calendar');
  if (miniCalendarEl) {
    const miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
      initialView: 'dayGridMonth',
      height: 300,
      headerToolbar: {
        left: 'prev,next',
        center: 'title',
        right: ''
      },
      events: '../../common/calendar_api.php?action=get_events',
      eventClick: function (info) {
        window.location.href = '#calendar';
      }
    });
    miniCalendar.render();
  }
});