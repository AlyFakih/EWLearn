// function sendEmail() {
//   let name = document.getElementById("name").value;
//   let email = document.getElementById("email").value;
//   let subject = document.getElementById("subject").value;
//   let message = document.getElementById("message").value;
//   let selectedEmails = $("#selectedEmails").val();

//   let body =
//     "name:  " +
//     name +
//     "<br>email:" +
//     email +
//     "<br>Subject: " +
//     subject +
//     "<br>Message: " +
//     message;

//   Email.send({
//     Host: "smtp.gmail.com",
//     Username: "***REMOVED***",
//     Password: "***REMOVED***",
//     To: "abbassmallah58@gmail.com",
//     From: email,
//     Subject: subject,
//     Body: body,
//   }).then((message) => {
//     $.ajax({
//       type: "POST",
//       url: "../../../backend/send_email.php",
//       data: {
//         name: name,
//         email: email,
//         selectedEmails: selectedEmails,
//         subject: subject,
//         message: message,
//       },
//       dataType: "json",
//       success: function (response) {
//         // Display the message in #sendMessgeEmail
//         let messageElement = $("#sendMessgeEmail");
//         messageElement.text(response.message);

//         // Change text color based on success or error
//         if (response.success) {
//           messageElement.css("color", "green");
//         } else {
//           messageElement.css("color", "red");
//         }

//         // Clear form or perform other actions if needed
//         if (response.success) {
//           // Clear the form
//           document.getElementById("name").value = "";
//           document.getElementById("email").value = "";
//           document.getElementById("subject").value = "";
//           document.getElementById("message").value = "";
//           $("#selectedEmails").val([]).trigger("change");
//         }
//       },
//       error: function (error) {
//         console.error("Error sending email:", error);
//         alert("An error occurred while sending the email");
//       },
//     });
//   });
// }
$(document).ready(function () {
  // Assuming you have a button with the id "send" to trigger the action
  $("#send").on("click", function (e) {
    e.preventDefault();

    var messageElement = $("#sendMessgeEmail"); // Define the variable here

    // Perform AJAX request
    $.ajax({
      type: "POST",
      url: "../../../backend/send_email.php",
      data: $(".formMessage").serialize(), // Serialize form data
      dataType: "json",
      success: function (response) {
        if (response.success) {
          messageElement
            .text(response.message)
            .removeClass("error")
            .addClass("success");
        } else {
          messageElement
            .text(response.message)
            .removeClass("success")
            .addClass("error");
        }
        // Delay for 2 seconds and then remove the classes and clear the message
        setTimeout(function () {
          messageElement.text("").removeClass("success error");
        }, 2000);
      },
      error: function () {
        messageElement
          .text("An error occurred while sending the message.")
          .removeClass("success")
          .addClass("error");
        // Delay for 2 seconds and then remove the classes and clear the message
        setTimeout(function () {
          messageElement.text("").removeClass("success error");
        }, 2000);
      },
    });
  });
});

// Rest of your code...

// Assuming you have jQuery included in your project

// $(document).ready(function () {
//   // Perform AJAX request to fetch user emails
//   $.ajax({
//     type: "GET",
//     url: "../../../backend/get_user_emails.php", // Update with the correct path to your backend file
//     dataType: "json",
//     success: function (response) {
//       // Populate the select with options
//       var select = $("#selectedEmails");

//       // Add options dynamically
//       $.each(response, function (index, email) {
//         select.append(
//           $("<option>", {
//             value: email,
//             text: email,
//           })
//         );
//       });
//     },
//     error: function (error) {
//       console.error("Error fetching user emails:", error);
//     },
//   });
// });
// Assuming you have jQuery included in your project
$(document).ready(function () {
  // Array to store all email options
  var allEmails = [];

  // Initial update based on the default role
  updateEmailOptions($("#role").val());

  // Event listener for the role select to update emails on change
  $(".selectRole").on("change", function () {
    updateEmailOptions($(this).val());
  });

  // Event listener for the search input
  $("#search").on("input", function () {
    filterEmailOptions($(this).val());
  });

  // Rest of your existing code...

  function updateEmailOptions(role) {
    // Define the corresponding PHP file based on the selected role
    var phpFile = "";

    switch (role) {
      case "student":
        phpFile = "getEmailsStudents.php";
        break;
      case "instructor":
        phpFile = "getEmailsInstr.php";
        break;
      // Add more cases for other roles if needed
      default:
        phpFile = "get_user_emails.php";
        break;
    }

    // Perform AJAX request to fetch user emails based on the selected role
    $.ajax({
      type: "GET",
      url: "../../../backend/" + phpFile, // Update with the correct path to your backend files
      dataType: "json",
      success: function (response) {
        // Clear previous options
        $("#selectedEmails").empty();

        // Populate the select with options and store in allEmails array
        allEmails = [];
        $.each(response, function (index, email) {
          allEmails.push(email);
          $("#selectedEmails").append(
            $("<option>", {
              value: email,
              text: email,
            })
          );
        });
      },
      error: function (error) {
        console.error("Error fetching user emails:", error);
      },
    });
  }

  function filterEmailOptions(searchTerm) {
    // Clear the existing options
    $("#selectedEmails").empty();

    // If the search term is empty, show all options
    if (searchTerm.trim() === "") {
      // Re-append all options from the original array
      $.each(allEmails, function (index, email) {
        $("#selectedEmails").append(
          $("<option>", {
            value: email,
            text: email,
          })
        );
      });
    } else {
      // Filter emails based on the search term (match from the beginning)
      const filteredEmails = allEmails.filter(function (email) {
        return email.toLowerCase().startsWith(searchTerm.toLowerCase());
      });

      // Display the message based on the search results
      if (filteredEmails.length > 0) {
        // Add filtered options to the select
        $.each(filteredEmails, function (index, email) {
          $("#selectedEmails").append(
            $("<option>", {
              value: email,
              text: email,
            })
          );
        });
      } else {
        // Add a message option to the select
        $("#selectedEmails").append(
          $("<option>", {
            value: "noEmailFound",
            text: "No matching emails found.",
            disabled: true,
            selected: true,
          })
        );
      }
    }
  }

  // Rest of your existing code...
});
