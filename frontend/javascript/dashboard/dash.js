// ! dashload :

// $(document).ready(function () {
//   // Default section is 'dash'
//   loadSection("dash");

//   // Menu item click event
//   $(".menu ul li").click(function (event) {
//     event.preventDefault(); // Prevent the default behavior
//     var section = $(this).data("section");
//     loadSection(section);
//   });

//   function loadSection(section) {
//     // Load content based on the selected section
//     $.ajax({
//       url: section + ".html",
//       method: "GET",
//       success: function (data) {
//         $(".bodydash").html(data); // Update the content of .bodydash
//       },
//       error: function () {
//         console.log("Error loading content for section: " + section);
//       },
//     });
//   }
// });

$(document).ready(function () {
  // Default section is 'dash'
  loadSection("dash");

  // Menu item click event for dynamically added elements
  $(document).on("click", ".menu ul li", function (event) {
    event.preventDefault(); // Prevent the default behavior
    var section = $(this).data("section");
    // Check if the section is "Home" and redirect if needed
    if (section === "home") {
      window.location.href = "../Home.html"; // Adjust the path accordingly
    } else {
      loadSection(section);
    }
  });

  function loadSection(section) {
    // Load content based on the selected section
    $.ajax({
      url: section + ".html",
      method: "GET",
      success: function (data) {
        $("#dashboard-content").html(data);

        const scripts = {
          teacher: "../../javascript/dashboard/teacher.js",
          students: "../../javascript/dashboard/student.js",
          coursesAdmin: "../../javascript/dashboard/coursesadmin.js",
          messages: "../../javascript/dashboard/messages.js",
          staff: "../../javascript/dashboard/staff.js",
          eventsAdmin: "../../javascript/dashboard/eventAdmin.js",
          NotifiAdmin: "../../javascript/dashboard/NotifiAdmin.js",
          dash: "../../javascript/dashboard/adminDash.js"
        };

        if (scripts[section]) {
          $.getScript(scripts[section])
            .done(function () {
              console.log(section + " JavaScript loaded.");
            })
            .fail(function () {
              console.error("Failed to load " + scripts[section]);
            });
        }
      },

      error: function () {
        console.log("Error loading content for section: " + section);
      },
    });
  }
});
// Function to remove login state
function removeLoginState() {
  localStorage.removeItem("isLoggedIn");
  window.location.href = "../../pages/loginRegister.html";
  // Remove other user-related information if needed
  // localStorage.removeItem("userRole");
}


// ===============================
// ADMIN DASHBOARD GLOBAL REFRESH
// ===============================

window.fetchInstructorData = function () {

    $.ajax({
        url: "../../../backend/instructors.php",
        method: "GET",
        dataType: "json",
        success: function(data){

            if(Array.isArray(data)){

                let table = $("#instructorTableBody");

                if(table.length){
                    table.empty();

                    data.forEach(function(instructor){

                        table.append(`
                        <tr>
                            <td>${instructor.id}</td>
                            <td class="namein">${instructor.fullName}</td>
                            <td class="emailin">${instructor.email}</td>
                            <td>${instructor.mobile}</td>
                            <td>${instructor.country}</td>
                            <td>
                                <button onclick='editInstructor("${instructor.email}")'>
                                Edit
                                </button>
                            </td>
                        </tr>
                        `);

                    });
                }

            }

        }
    });

};



window.fetchstudentData = function(){

    $.ajax({

        url:"../../../backend/dashStudent/getStudent.php",
        method:"GET",
        dataType:"json",

        success:function(data){

            if(Array.isArray(data)){

                let table=$("#studentTableBody");

                if(table.length){

                    table.empty();

                    data.forEach(function(student){

                        table.append(`
                        <tr>
                            <td>${student.id}</td>
                            <td class="namein">${student.fullName}</td>
                            <td class="emailin">${student.email}</td>
                            <td>${student.mobile}</td>
                            <td>${student.country}</td>
                        </tr>
                        `);

                    });

                }

            }

        }

    });

};

