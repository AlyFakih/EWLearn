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
  // Reads the section straight out of the URL, e.g. /admin/students ->
  // "students". Bare /admin, or anything that doesn't match, falls back to
  // the default "dash" section.
  function sectionFromPath() {
    var match = window.location.pathname.match(/^\/admin\/([a-zA-Z0-9_-]+)\/?$/);
    return match ? match[1] : "dash";
  }

  // Same active-class logic the click handler below uses, factored out so
  // the initial load and popstate (browser back/forward) can reuse it -
  // neither of those has a click event to hang it off.
  function highlightSection(section) {
    $(".menu ul li a").removeClass("active");
    $(".menu ul li[data-section='" + section + "'] a").addClass("active");
  }

  // Initial section comes from the URL instead of always being "dash" - so
  // refreshing on /admin/students lands back on Students instead of
  // resetting to the default dashboard view.
  var initialSection = sectionFromPath();
  loadSection(initialSection);
  highlightSection(initialSection);

  // Menu item click event for dynamically added elements
  $(document).on("click", ".menu ul li", function (event) {
    event.preventDefault(); // Prevent the default behavior
    var section = $(this).data("section");
    // Check if the section is "Home" and redirect if needed
    if (section === "home") {
      window.location.href = "../Home.html"; // Adjust the path accordingly
    } else {
      loadSection(section);
      var url = section === "dash" ? "/admin" : "/admin/" + section;
      if (window.location.pathname !== url) {
        history.pushState({ section: section }, "", url);
      }
    }
  });

  // Back/forward buttons: reload whichever section the URL now points to.
  // No pushState here - the browser already changed the URL for us, pushing
  // again would break the back button it's supposed to support.
  window.addEventListener("popstate", function () {
    var section = sectionFromPath();
    loadSection(section);
    highlightSection(section);
  });

  // Highlight the current section in the sidebar. Previously "active" only
  // ever sat on the hardcoded initial "DashBoard" item in the markup and
  // never moved, so the sidebar gave no indication of which section you were
  // actually viewing after navigating.
  $(document).on("click", ".menu ul li", function () {
    $(".menu ul li a").removeClass("active");
    $(this).find("a").addClass("active");
  });

  // The page title in the topbar ("DashBoard") is markup that lives inside
  // NotifiAdmin.php, which each section loads asynchronously into #boxNotifi
  // right after its own content lands - so it isn't in the DOM yet at the
  // moment loadSection()'s own success callback runs. ajaxStop fires once
  // every in-flight request (the section AND its nested notification load)
  // has actually settled, which is the reliable point to update it.
  var SECTION_TITLES = {
    dash: "DashBoard",
    teacher: "Instructor",
    students: "Students",
    coursesAdmin: "Courses",
    eventsAdmin: "Events",
    staff: "Staff",
    messages: "Message",
  };
  $(document).ajaxStop(function () {
    var currentSection = $(".menu ul li a.active").closest("li").data("section");
    var title = SECTION_TITLES[currentSection];
    if (title) {
      $(".title-info p").first().text(title);
      document.title = title + " - Admin - EWLearn";
    }
  });

  function loadSection(section) {
    // Load content based on the selected section
    $.ajax({
      url: section + ".php",
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
// Function to remove login state.
// Clearing localStorage alone left the server-side PHP session alive, so
// "logging out" and then revisiting the dashboard URL still worked. Hand off to
// the server logout endpoint, which destroys the session and then redirects.
function removeLoginState() {
  localStorage.removeItem("isLoggedIn");
  window.location.href = "./logout.php";
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

