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
        $("#dashboard-content").html(data); // Update the content of #dashboard-content
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
