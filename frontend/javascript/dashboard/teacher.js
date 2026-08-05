// !  Get the modal ADD and update
var modal = document.getElementById("id01");
var modalupdate = document.getElementById("id02");

// When the user clicks anywhere outside of any modal, close it
window.onclick = function (event) {
  if (event.target == modal) {
    modal.style.display = "none";
  } else if (event.target == modalupdate) {
    modalupdate.style.display = "none";
  }
};

// ! search input ------------------------

const searchInput = document.getElementById("searchinstructor");
const rows = document.querySelectorAll("tbody tr");
const noResultsMessage = document.getElementById("no-cards-message"); // Assuming you have an element with the id "noResultsMessage"

searchInput.addEventListener("keyup", function (event) {
  const q = event.target.value.toLowerCase();
  let found = false;

  rows.forEach((row) => {
    const nameMatch = row
      .querySelector(".namein")
      .textContent.toLowerCase()
      .startsWith(q);
    const emailMatch = row
      .querySelector(".emailin")
      .textContent.toLowerCase()
      .startsWith(q);

    if (nameMatch || emailMatch) {
      row.style.display = "";
      found = true;
    } else {
      row.style.display = "none";
    }
  });

  if (found) {
    noResultsMessage.style.display = "none";
  } else {
    noResultsMessage.style.display = ""; // Show the message when no results are found
  }
});
// !-------------Inside teacher.js
$(document).ready(function () {
  // Fetch instructor data on page load
  fetchInstructorData();

  function fetchInstructorData() {
    $.ajax({
      url: "../../../backend/instructors.php", // Replace with the actual path to your PHP script
      method: "GET",
      dataType: "json",
      success: function (data) {
        // Check if the data is an array
        if (Array.isArray(data)) {
          // Call a function to populate the table with the retrieved data
          displayInstructors(data);
        } else {
          // Handle the error message if the data is not an array
          alert("Error: " + data.message);
        }
      },
      error: function (error) {
        // Handle the error if the AJAX request fails
        alert("Error communicating with the server. " + error.statusText);
      },
    });
  }

  function displayInstructors(instructors) {
    // Get the table body element
    var tableBody = $("#instructorTableBody");

    // Clear existing content in the table body
    tableBody.empty();

    // Loop through the instructors and append rows to the table
    instructors.forEach(function (instructor) {
      var row = `<tr>
              <td>${instructor.id}</td>
              <td class="namein">${instructor.fullName}</td>
              <td class="emailin">${instructor.email}</td>
              <td>${instructor.mobile}</td>
              <td><p>${instructor.country}</p></td>
              <td>
                  <button class="trash" onclick='confirmDelete("${instructor.email}")'>
                      <i class="fa-solid fa-trash fa-lg" style="color: #000000"></i>
                  </button>
                  <button class="log edit" onclick='editInstructor("${instructor.email}")'>
                      <i class="fa-solid fa-pen fa-lg" style="color: #000000"></i>
                  </button>
              </td>
          </tr>`;

      // Append the row to the table body
      tableBody.append(row);
    });
  }
});
function confirmDelete(email) {
  if (
    confirm(
      `Are you sure you want to delete the instructor with email "${email}"?`
    )
  ) {
    // Make an AJAX request to delete the instructor
    $.ajax({
      url: "../../../backend/deleteUser.php",
      method: "POST",
      data: { email: email },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          alert(`Instructor with email "${email}" deleted successfully!`);
          // Refresh the instructor data after deletion
          fetchInstructorData();
        } else {
          alert(`Error deleting instructor: ${response.message}`);
        }
      },
      error: function (error) {
        alert("Error communicating with the server. " + error.statusText);
      },
    });
  }
}
function editInstructor(email) {
  // Fetch user

  $.ajax({
    url: "../../../backend/getAllUser.php",
    method: "POST",
    data: { email: email },
    dataType: "json",
    success: function (response) {
      // Check if the form is already displayed, if not, display it
      if ($("#id02").css("display") === "none") {
        document.getElementById("id02").style.display = "block";
      }
      // Fill the update form with the retrieved user details
      $("#FullName").val(response.fullName);
      $("#BloodType").val(response.blood);
      $("#Email").val(response.email);
      $("#PhoneNumber").val(response.mobile);
      $("#Role").val(response.role);
      $("#Country").val(response.country);

      // Display the update message
      $("#updateUserMessage").text("User details fetched successfully.");
    },
    error: function (error) {
      alert("Error fetching user details. " + error.statusText);
    },
  });
}
// ! udate user
// Update user details using AJAX
function updateUserDetails(formData) {
  $.ajax({
    method: "POST",
    url: "../../../backend/updateUser.php",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Display the update message
      $("#updateUserMessage").text(response.message);

      // If the update was successful
      if (response.status === "success") {
        // // Close the modal after a short delay
        // setTimeout(function () {
        //   document.getElementById("updateUserForm").style.display = "none";
        // }, 2000);

        // Optionally, update the displayed user details on the page
        updateDisplayedUser(response.email, {
          fullName: response.fullName,
          blood: response.blood,
          email: response.email,
          mobile: response.mobile,
          role: response.role,
          country: response.country,
        });
      }
    },
    error: function (xhr, status, error) {
      // Display an error message
      $("#updateUserMessage").text("Error updating user: " + error);
    },
  });
}

// Attach the event handler to the form submit button
$("#updateUserForm").submit(function (event) {
  event.preventDefault();

  // Make an AJAX request to update the user
  const formData = $(this).serialize();
  updateUserDetails(formData);
});

// ! add Instructora -----
$(document).ready(function () {
  $("#addInstructorForm").submit(function (event) {
    event.preventDefault();

    // Serialize form data
    var formData = $(this).serialize();

    // Make Ajax request
    $.ajax({
      method: "POST",
      url: "../../../backend/addUser.php",
      data: formData,
      dataType: "json",
      success: function (response) {
        // Display the response message
        $("#messageResult").text(response.message);

        // You can perform additional actions after a successful insert
        if (response.status === "success") {
          fetchInstructorData();
          // For example, close the modal after a short delay
          setTimeout(function () {
            document.getElementById("id01").style.display = "none";
          }, 2000);
        }
      },
      error: function (xhr, status, error) {
        // Parse the responseText as JSON

        $("#messageResult").text(response.message);
      },
    });
  });
});
// ! Function to fetch and populate instructor names =====================================================
// Function to fetch and populate instructor names
function populateInstructors() {
  $.ajax({
    url: "../../../backend/getFullNameInstr.php",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var instructorSelect = $("#instructorName");
      instructorSelect.empty();

      // Add the "Choose Instructor" option as the default
      instructorSelect.append(
        $("<option>", {
          value: "",
          text: "Choose Instructor",
        })
      );

      if (data.length > 0) {
        $.each(data, function (index, instructor) {
          instructorSelect.append(
            $("<option>", {
              value: instructor.fullName,
              text: instructor.fullName,
            })
          );
        });
      } else {
        instructorSelect.append(
          $("<option>", {
            value: "",
            text: "No instructors available",
          })
        );
      }
    },
    error: function (error) {
      console.log("Error fetching instructor data:", error);
    },
  });
}

// Function to fetch and populate course names
function populateCourses() {
  $.ajax({
    url: "../../../backend/getCourses.php",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var courseSelect = $("#courseName");
      courseSelect.empty();

      // Add the "Choose Course" option as the default
      courseSelect.append(
        $("<option>", {
          value: "",
          text: "Choose Course",
        })
      );

      if (data.length > 0) {
        $.each(data, function (index, course) {
          courseSelect.append(
            $("<option>", {
              value: course.courseTitle,
              text: course.courseTitle,
            })
          );
        });
      } else {
        courseSelect.append(
          $("<option>", {
            value: "",
            text: "No courses available",
          })
        );
      }
    },
    error: function (error) {
      console.log("Error fetching course data:", error);
    },
  });
}
// Function to fetch and populate instructor courses
function populateInstructorCourses() {
  $.ajax({
    url: "../../../backend/getInstructorCourses.php", // Adjust the path to your PHP script
    type: "GET",
    dataType: "json",
    success: function (data) {
      var instructorCoursesSelect = $("#instructorCourses");
      instructorCoursesSelect.empty();
      instructorCoursesSelect.append(
        $("<option>", {
          value: "",
          text: "Choose One",
        })
      );
      if (data.length > 0) {
        $.each(data, function (index, course) {
          instructorCoursesSelect.append(
            $("<option>", {
              value: course.name,
              text: course.name,
            })
          );
        });
      } else {
        instructorCoursesSelect.append(
          $("<option>", {
            value: "",
            text: "No instructor courses available",
          })
        );
      }
    },
    error: function (error) {
      console.log("Error fetching instructor courses:", error);
    },
  });
}

//  Function to handle the "Choose" button click
function addCourseMember() {
  var instructorName = $("#instructorName").val();
  var courseName = $("#courseName").val();

  // Check if "Choose Instructor" or "Choose Course" is selected
  if (instructorName === "" || courseName === "") {
    alert("Please choose both an instructor and a course");
    return;
  }

  // Send the selected values to the server for insertion
  $.ajax({
    url: "../../../backend/ChooseINcourse.php",
    type: "POST",
    dataType: "json",
    data: {
      fullName: instructorName,
      courseTitle: courseName,
    },
    success: function (response) {
      $("#successMessage").text(response.message);

      // Display the message for 2 seconds
      setTimeout(function () {
        $("#successMessage").text(""); // Clear the message
      }, 2000);
    },
    error: function (error) {
      console.log("Error inserting data:", error);
    },
  });
}

// Call the functions to populate instructor and course dropdowns
$(document).ready(function () {
  populateInstructors();
  populateCourses();
  populateInstructorCourses();
});

// Function to handle the "Delete" button click
function deletedetials() {
  var instructorCoursesSelect = $("#instructorCourses");
  var selectedOption = instructorCoursesSelect.find(":selected");

  // Check if any option is selected
  if (selectedOption.length === 0) {
    alert("Please select an instructor course to delete");
    return;
  }

  // Display a confirmation dialog
  var isConfirmed = confirm(
    "Are you sure you want to delete this instructor course?"
  );
  if (!isConfirmed) {
    return; // User canceled the deletion
  }

  var instructorCourseName = selectedOption.val();

  // Send the selected instructor course name to the server for deletion
  $.ajax({
    url: "../../../backend/deleteInstructorCourse.php",
    type: "POST",
    dataType: "json",
    data: {
      instructorCourseName: instructorCourseName,
    },
    success: function (response) {
      // Remove the selected option from the select
      selectedOption.remove();

      // Display a success message
      $("#successMessage").text(response.message);

      // Clear the message after 2 seconds
      setTimeout(function () {
        $("#successMessage").text("");
      }, 2000);
    },
    error: function (error) {
      console.log("Error deleting instructor course:", error);
    },
  });
}
// Function to handle the "Update" button click
// Function to handle the "Update" button click
function updateDetails() {
  // Get the selected values from the dropdowns
  const instructor = document.getElementById("instructorName").value;
  const course = document.getElementById("courseName").value;
  const instructorCourses = document.getElementById("instructorCourses").value;

  // Make sure all values are selected
  if (!instructor || !course || !instructorCourses) {
    alert("Please select values for all dropdowns.");
    return;
  }

  // Prepare the data to send to the PHP script
  const formData = {
    instructor: instructor,
    course: course,
    instructorCourses: instructorCourses,
  };

  // Make an AJAX request to the PHP script
  $.ajax({
    type: "POST",
    url: "../../../backend/updateInstructorCourse.php", // Adjust the URL to your actual PHP script
    data: formData,
    dataType: "json",
    success: function (response) {
      // Handle the response from the server
      if (response.status === "success") {
        // Display a success message
        $("#successMessage").text(response.message).css("color", "green");
        // Clear the message after 2 seconds
        setTimeout(function () {
          $("#successMessage").text("");
        }, 2000);
        // Optionally, you can perform additional actions after a successful update
      } else {
        $("#successMessage").text(response.message).css("color", "red");
        // Clear the message after 2 seconds
        setTimeout(function () {
          $("#successMessage").text("");
        }, 2000);
      }
    },
    error: function (error) {
      // Handle the AJAX error
      alert("Error communicating with the server. " + error.statusText);
    },
  });
}
