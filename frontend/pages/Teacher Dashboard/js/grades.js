function showAddModal() {
  $('#addModal').fadeIn();
}

$('#closeForm').on('click', function () {
  $('#addModal').fadeOut();
});

$(document).ready(function() {
    // Show the add form when the "Add" button is clicked
    $('#showForm').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      showAddModal();
    });

    // Search functionality
    $('#searchinstructor').on('input', function() {
      var searchText = $(this).val().toLowerCase();
      if (searchText === '') {
        $('#table1 tbody tr').show();
        return;
      }
      $('#table1 tbody tr').each(function() {
        var rowData = $(this).text().toLowerCase();
        if (rowData.indexOf(searchText) === -1) {
          $(this).hide();
        } else {
          $(this).show();
        }
      });
    });

    // insertion function (Add button)
    $("#adddata").on("click", function(e) {
      e.preventDefault();
      var formData = $("#addForm").serialize();
      $.ajax({
        url: "php/gradesFunctions.php",
        type: "POST",
        data: formData,
        success: function(response) {
          $('#table1 tbody').append(response);
          $("#addForm")[0].reset();
          $('#addModal').fadeOut();
        },
        complete: function() {
          $('#table1').animate({
            scrollTop: $('#table1 tbody').height()
          }, 1000);
        }
      });
    });

    // update
    $(document).on('click', '#table1 .edit', function() {
      $('#table1').find('.save, .cancel').hide();
      $('#table1').find('.edit').show();
      $(this).hide().siblings('.save, .cancel').show();

      $(this).closest('tr').find('td[data-id]').each(function() {
        if (!$(this).is(':last-child')) {
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
          url: "php/gradesFunctions.php",
          type: "POST",
          data: params,
          success: function(response) {
            $("#ajax-response").html(response);
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
          url: "php/gradesFunctions.php",
          type: "POST",
          data: {
            id: deleteid,
            action: 'del'
          },
          success: function(response) {
            if (response == 1) {
              $(ele).closest('tr').css('background', 'tomato');
              $(ele).closest('tr').fadeOut(800, function() {
                $(this).remove();
              });
            } else {
              alert('Deleting row Failed');
            }
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
