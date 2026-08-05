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

// ! search course name--------------------------------
const searchInput = document.getElementById("searchcourse");
const cards = document.querySelectorAll(".card");
const noResultsMessage = document.getElementById("no-cards-message");

searchInput.addEventListener("keyup", function (event) {
  const q = event.target.value.toLowerCase();
  let found = false;

  cards.forEach((card) => {
    const courseName = card
      .querySelector(".coursename")
      .textContent.toLowerCase();

    if (courseName.includes(q)) {
      card.style.display = "";
      found = true;
    } else {
      card.style.display = "none";
    }
  });

  if (found) {
    noResultsMessage.style.display = "none";
  } else {
    noResultsMessage.style.display = "";
  }
});

// ! detials course=------------------
// Create table of inputs
let description = document.getElementById("description");
let subNb = document.getElementById("subjectsNum");

let subjectsArray = [],
  gradesArray = [];

// Create table of inputs
document.getElementById("enterDetails").addEventListener("click", () => {
  if (description.value != "" && subNb.value != 0) {
    let data = `<table id="datadetails"><tr><th>Chapters</th><th>Names</th></tr>`;
    for (let i = 0; i < subNb.value; i++) {
      data += `<tr>
                  <td><input type="text" class="form-control" name="chapter${
                    i + 1
                  }" id="subjectid${i + 1}" placeholder="Chapter ${
        i + 1
      } "></td>
                  <td><input type="text" class="form-control" name="chName${
                    i + 1
                  }" id="gradeid${i + 1}" placeholder="Chapter ${
        i + 1
      } Name"></td>
              </tr>`;
    }
    // document.getElementById("enterDetails").disabled = true;

    // description.disabled = true;
    // subNb.disabled = true;
    document.getElementById("details").innerHTML = `${data}</table><hr>`;

    document.getElementById("message").innerHTML = "";
  } else {
    alert("Please enter the data before clicking the button");
  }
});

// ! Get  courses00000000000000000000000

$(document).ready(function () {
  // Fetch courses on page load using jQuery Ajax

  $.ajax({
    url: "../../../backend/getCourses.php",
    method: "GET",
    dataType: "json",
    success: function (data) {
      console.log(data);

      if (Array.isArray(data)) {
        // Array indicates success, handle the courses
        displayCourses(data);
      } else {
        // Non-array indicates an error, handle the error message
        alert("Error: " + data.message);
      }
    },
    error: function (error) {
      alert("Error communicating with the server. " + error.statusText);
    },
  });

  function displayCourses(courses) {
    // Append the card HTML to the container
    const container = $(".container-fluid");
    container.html(""); // Clear existing content

    courses.forEach((course) => {
      let cardHtml = `
        <div class="card">
          <div class="divImage">
            <img src="${course.image}" alt="Course Image" />
          </div>
          <div class="info">
            <h2 class="coursename">${course.courseTitle}</h2>
            <p>
              <i class="fa-solid fa-person-chalkboard" style="color: #fafafa"></i>2 Instructors
            </p>
            <div class="card-buttons">
              <button onclick="updateCourse('${course.courseTitle}')">Update</button>
              <button onclick="confirmDelete('${course.courseTitle}')">Delete</button>
            </div>
          </div>
        </div>
      `;
      container.append(cardHtml);
    });
  }
});

function confirmDelete(courseTitle) {
  // Display a confirmation dialog
  if (confirm(`Are you sure you want to delete the course "${courseTitle}"?`)) {
    // Perform the delete operation
    $.ajax({
      url: "../../../backend/deleteCourse.php", // Replace with the actual URL of your delete script
      method: "POST",
      data: { courseTitle: courseTitle },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          alert(`Course "${courseTitle}" deleted successfully!`);
          // If you want to update the UI after deletion, you may need to refetch and display the courses.
          // You can call the fetchCourses function here or trigger a page reload.
        } else {
          alert(`Error deleting course: ${response.message}`);
        }
      },
      error: function (error) {
        alert("Error communicating with the server. " + error.statusText);
      },
    });
  }
}
// ! get courses detials
function updateCourse(courseTitle) {
  // Fetch course details using an AJAX request
  $.ajax({
    url: "../../../backend/getAllCourses.php", // Replace with the actual URL of your script to fetch course details
    method: "POST",
    data: { title: courseTitle },
    dataType: "json",
    success: function (response) {
      // Check if the form is already displayed, if not, display it
      if ($("#id02").css("display") === "none") {
        document.getElementById("id02").style.display = "block";
      }
      // Fill the update form with the retrieved course details
      $("#updateCourseTitle").val(response.courseTitle);
      $("#updatePathImage").val(response.image);
      $("#updateCategory").val(response.category);
      $("#updatePrice").val(response.price);
      $("#updateCalendar").val(response.calendar);
      $("#updateDescription").val(response.description);
      $("#updateSeats").val(response.courseSeats);
      // Display the update message
      $("#updateMessage").text("Course details fetched successfully.");
    },
    error: function (error) {
      alert("Error fetching course details. " + error.statusText);
    },
  });
}

// !Update course details using AJAX
function updateCourseDetails(formData) {
  $.ajax({
    method: "POST",
    url: "../../../backend/updateCourse.php",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Display the update message
      $("#updateMessage").text(response.message);

      // If the update was successful
      if (response.status === "success") {
        // Close the modal after a short delay
        setTimeout(function () {
          document.getElementById("id02").style.display = "none";
        }, 2000);

        // Optionally, update the displayed course details on the page
        updateDisplayedCourse(response.courseTitle, {
          courseTitle: response.courseTitle,
          image: response.image,
          category: response.category,
          price: response.price,
          calendar: response.calendar,
          description: response.description,
          courseSeats: response.courseSeats,
        });
      }
    },
    error: function (xhr, status, error) {
      // Display an error message
      $("#updateMessage").text("Error updating course: " + error);
    },
  });
}

// Attach the event handler to the form submit button
$("#updateCourseForm").submit(function (event) {
  event.preventDefault();

  // Make an AJAX request to update the course
  const formData = $(this).serialize();
  updateCourseDetails(formData);
});

// ! to add course ------
// ... (your existing code)

// AJAX request to add a new course
$("#addCourseForm").submit(function (event) {
  event.preventDefault();

  // Make sure to serialize the form data
  const formData = $(this).serialize();

  // Make an AJAX request to add the course
  $.ajax({
    method: "POST",
    url: "../../../backend/addCourse.php",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Display the result message
      $("#messageResult").text(response.message);

      // If the addition was successful, you can perform additional actions here

      // Optionally, you can clear the form fields or perform other UI updates
      if (response.status === "success") {
        $("#addCourseForm")[0].reset(); // Reset the form
      }
    },
    error: function (xhr, status, error) {
      // Display an error message
      $("#messageResult").text("Error adding course: " + error);
    },
  });
});
// Add this function to your existing JavaScript code

// AJAX request to add course details
$("#addCourseDetails").submit(function (event) {
  event.preventDefault();

  // Create an array to store chapter details
  let chapterDetails = [];

  // Loop through input fields to collect chapter details
  for (let i = 1; i <= subNb.value; i++) {
    let chapter = $("#subjectid" + i).val();
    let chapterName = $("#gradeid" + i).val();
    chapterDetails.push({ chapter: chapter, chapterName: chapterName });
  }

  // Serialize form data along with chapter details
  const formData =
    $(this).serialize() + "&chapterDetails=" + JSON.stringify(chapterDetails);

  // Make an AJAX request to add course details
  $.ajax({
    method: "POST",
    url: "../../../backend/addCourseDetails.php",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Display the result message for course details
      $("#MessageCoursesD").text(response.message);

      // If the addition was successful, you can perform additional actions here

      // Optionally, you can clear the form fields or perform other UI updates
      if (response.status === "success") {
        $("#addCourseDetails")[0].reset(); // Reset the form
      }
    },
    error: function (xhr, status, error) {
      // Display an error message for course details
      $("#MessageCoursesD").text("Error adding course details: " + error);
    },
  });
});
