// !  Get the modal ADD and update

let modal = document.getElementById("id01");
let modalupdate = document.getElementById("id02");

// When the user clicks anywhere outside of any modal, close it
window.onclick = function (event) {
  if (event.target == modal) {
    modal.style.display = "none";
  } else if (event.target == modalupdate) {
    modalupdate.style.display = "none";
  }
};

function confirmDelete() {
  let result = confirm("Are you sure you want to delete?");
  if (result) {
    // If the user clicks "OK," proceed with the deletion
    alert("Deleted!"); // You can replace this alert with your actual delete logic
  } else {
    // If the user clicks "Cancel" or closes the dialog, do nothing
    alert("Deletion canceled.");
  }
}
// ! search and message ==================================
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
// ! add studnts-----
$(document).ready(function () {
  $("#addstudentsForm").submit(function (event) {
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
        $("#messageR").text(response.message);

        // You can perform additional actions after a successful insert
        if (response.status === "success") {
          // For example, close the modal after a short delay
          setTimeout(function () {
            document.getElementById("id01").style.display = "none";
          }, 2000);
        }
      },
      error: function (xhr, status, error) {
        // Parse the responseText as JSON

        $("#messageR").text(response.message);
      },
    });
  });
});
// !-------------get role students

$(document).ready(function () {
  // Fetch instructor data on page load
  fetchstudentData();
  function fetchstudentData() {
    $.ajax({
      type: "GET",
      url: "../../../backend/dashStudent/getStudent.php", // Replace with the actual path to your PHP script
      dataType: "json",
      async: true,
      success: function (data) {
        // Check if the data is an array
        if (Array.isArray(data)) {
          // Call a function to populate the table with the retrieved data
          displayStudents(data);
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

  function displayStudents(students) {
    // Get the table body element
    var tableBody = $("#studentTableBody");

    // Clear existing content in the table body
    tableBody.empty();
    // Check if there are no students
    if (students.length === 0) {
      // Display a message or take appropriate action
      $("#no-cards-message").text("No students found.").show();
      return;
    }
    // Loop through the instructors and append rows to the table
    students.forEach(function (student) {
      var row = `<tr>
                  <td>${student.id}</td>
                  <td class="namein">${student.fullName}</td>
                  <td class="emailin">${student.email}</td>
                  <td>${student.mobile}</td>
                  <td><p>${student.country}</p></td>
                  <td>
                      <button class="trash" onclick='confirmDelete("${student.email}")'>
                          <i class="fa-solid fa-trash fa-lg" style="color: #000000"></i>
                      </button>
                      <button class="log edit" onclick='editInstructor("${student.email}")'>
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
      $("#namest").val(response.fullName);
      $("#bloodst").val(response.blood);
      $("#emailst").val(response.email);
      $("#phonest").val(response.mobile);
      $("#rolest").val(response.role);
      $("#countryst").val(response.country);

      // Display the update message
      $("#updateSTMessage").text("User details fetched successfully.");
    },
    error: function (error) {
      alert("Error fetching user details. " + error.statusText);
    },
  });
}
// ! update user
// Update user details using AJAX
function updateUserDetails(formData) {
  $.ajax({
    method: "POST",
    url: "../../../backend/updateUser.php",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Display the update message
      $("#updateSTMessage").text(response.message);

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
      $("#updateSTMessage").text("Error updating user: " + error);
    },
  });
}

// Attach the event handler to the form submit button
$("#updateSTForm").submit(function (event) {
  event.preventDefault();

  // Make an AJAX request to update the user
  const formData = $(this).serialize();
  updateUserDetails(formData);
});

// ! Function to fetch and populate instructor names =====================================================
// Function to fetch and populate instructor names
function populateInstructors() {
  $.ajax({
    url: "../../../backend/getFullNameInstr.php",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var instructorSelect = $("#getInstName");
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
function populateStudents() {
  $.ajax({
    url: "../../../backend/dashStudent/getNameStudents.php",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var studentSelect = $("#studentName");
      studentSelect.empty();

      // Add the "Choose student" option as the default
      studentSelect.append(
        $("<option>", {
          value: "",
          text: "Choose student",
        })
      );

      if (data.length > 0) {
        $.each(data, function (index, student) {
          studentSelect.append(
            $("<option>", {
              value: student.fullName,
              text: student.fullName,
            })
          );
        });
      } else {
        studentSelect.append(
          $("<option>", {
            value: "",
            text: "No students available",
          })
        );
      }
    },
    error: function (error) {
      console.log("Error fetching student data:", error);
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
      var courseSelect = $("#courseNamestudent");
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
// Call the functions to populate instructor and course dropdowns
$(document).ready(function () {
  populateInstructors();
  populateCourses();
  populateStudents();
});
function addStudentCourse() {
  // Get selected data from the form
  var studentName = $("#studentName").val();
  var instructorName = $("#getInstName").val();
  var courseTitle = $("#courseNamestudent").val();

  // Perform AJAX request
  $.ajax({
    type: "POST",
    url: "../../../backend/dashStudent/addStudentCourse.php", // Replace with the actual path to your PHP script
    data: {
      studentName: studentName,
      instructorName: instructorName,
      courseTitle: courseTitle,
    },
    dataType: "json",
    success: function (response) {
      $("#successMessage")
        .text(response.message)
        .css("color", response.status === "success" ? "green" : "red");
      // Hide the message after 2000 milliseconds (2 seconds)
      setTimeout(function () {
        $("#successMessage").text("").css("color", "");
      }, 2000);
    },
    error: function (error) {
      // Display error message
      $("#successMessage")
        .text("Error: " + error.responseText)
        .css("color", "red");
    },
  });
}
//!-------------- Get studentcourse table field

$(document).ready(function () {
  $.ajax({
    type: "GET",
    url: "../../../backend/dashStudent/getStudentCourses.php",
    dataType: "json",
    success: function (response) {
      // Populate the select with options
      var select = $("#studentCourseSelect");
      select.empty(); // Clear existing options
      // Add the "Choose Course" option as the default
      select.append(
        $("<option>", {
          value: "",
          text: "Choose One",
        })
      );

      // Iterate through the response data and add options to the select
      $.each(response, function (index, course) {
        // Assuming you have a function to format the option text
        var optionText = formatOptionText(course);

        // Create the option element
        var option = $("<option>").val(index).text(optionText);

        // Set data attributes
        option.data("userStudentID", course.userStudentID);
        option.data("userInstructorID", course.userInstructorID);
        option.data("courseID", course.courseID);

        // Append the option to the select
        select.append(option);
      });
    },
    error: function (error) {
      console.error("Error fetching studentcourse data:", error);
    },
  });

  // Function to format option text (replace with your logic)
  function formatOptionText(course) {
    // Assuming you have functions to fetch names based on IDs
    var studentName = getUserNameById(course.userStudentID);
    var instructorName = getUserNameById(course.userInstructorID);
    var courseTitle = getCourseTitleById(course.courseID);

    // Format the option text
    return (
      "Student: " +
      studentName +
      " - " +
      "Instructor: " +
      instructorName +
      " - " +
      courseTitle
    );
  }

  // Replace these functions with your actual logic to fetch names
  function getUserNameById(userId) {
    // Implement logic to fetch user name based on ID
    return userId;
  }

  function getCourseTitleById(courseId) {
    // Implement logic to fetch course title based on ID
    return "Course: " + courseId;
  }
});

// Function to delete student course
function deleteStudentCourse() {
  // Get the selected option
  var selectedOption = $("#studentCourseSelect option:selected");

  // Check if an option is selected
  if (selectedOption.val() === "") {
    alert("Please select a course to delete.");
    return;
  }

  // Confirm deletion
  var confirmDelete = confirm("Are you sure you want to delete this course?");
  if (!confirmDelete) {
    return;
  }

  // Get the userStudentID, userInstructorID, and courseID from the data attributes
  var userStudentID = selectedOption.data("userStudentID");
  var userInstructorID = selectedOption.data("userInstructorID");
  var courseID = selectedOption.data("courseID");

  // Perform AJAX request to delete the course
  $.ajax({
    type: "POST",
    url: "../../../backend/dashStudent/deleteStudentCourse.php",
    data: {
      userStudentID: userStudentID,
      userInstructorID: userInstructorID,
      courseID: courseID,
    },
    dataType: "json",
    success: function (response) {
      // Display the result
      $("#successMessage").text(response.message).css("color", "green");
      // Hide the message after 2000 milliseconds (2 seconds)
      setTimeout(function () {
        $("#successMessage").text("").css("color", "");
      }, 2000);

      // getStudentCourses();
    },
    error: function (error) {
      // Display error message
      $("#successMessage")
        .text("Error: " + error.responseText)
        .css("color", "red");
    },
  });
}
// ! --------------------------------Function to update a student course
// Function to handle updating student course
function updateStudentCourse() {
  // Get the selected option
  var selectedOption = $("#studentCourseSelect option:selected");

  // Check if an option is selected
  if (selectedOption.val() === "") {
    alert("Please select a course to update.");
    return;
  }

  // Fetch the data from the selected option
  var userStudentID = selectedOption.data("userStudentID");
  var userInstructorID = selectedOption.data("userInstructorID");
  var courseID = selectedOption.data("courseID");

  // Get the updated values from the form
  var updatedStudentName = $("#studentName").val();
  var updatedInstructorName = $("#getInstName").val();
  var updatedCourseTitle = $("#courseNamestudent").val();

  // Perform AJAX request to update the course
  $.ajax({
    type: "POST",
    url: "../../../backend/dashStudent/updateStudentCourse.php",
    data: {
      userStudentID: userStudentID,
      userInstructorID: userInstructorID,
      courseID: courseID,
      updatedStudentName: updatedStudentName,
      updatedInstructorName: updatedInstructorName,
      updatedCourseTitle: updatedCourseTitle,
    },
    dataType: "json",
    success: function (response) {
      // Display the result
      $("#successMessage")
        .text(response.message)
        .css("color", response.status === "success" ? "green" : "red");
      // Hide the message after 2000 milliseconds (2 seconds)
      setTimeout(function () {
        $("#successMessage").text("").css("color", "");
      }, 2000);
      getStudentCourses(); // Optional: Refresh the list of student courses
    },
    error: function (error) {
      // Display error message
      $("#successMessage")
        .text("Error: " + error.responseText)
        .css("color", "red");
    },
  });
}
