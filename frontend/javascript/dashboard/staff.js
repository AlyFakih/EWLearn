// Function to add a new team member to the home page
// Function to add a new team member to the home page and staff page
function addTeamMember() {
  // Get input values
  var name = document.getElementById("name").value;
  var major = document.getElementById("major").value;
  var imageInput = document.getElementById("image");

  // Validate that all fields are filled
  if (name && major && imageInput.files.length > 0) {
    // Get the file data
    var imageFile = imageInput.files[0];

    // Create a new FileReader to read the file data
    var reader = new FileReader();

    reader.onload = function (e) {
      // Create a new team member card with the image URL
      var memberCard = document.createElement("div");
      memberCard.className =
        "col-lg-3 col-md-6 d-flex align-items-stretch team-member-card";
      memberCard.innerHTML = `
        <div class="member" data-aos="fade-up" data-aos-delay="100">
          <div class="member-img">
            <div class="image">
              <img src="${e.target.result}" alt="${name}" />
            </div>
            <div class="social">
              <a href="#"><i class="fa-brands fa-twitter"></i></a>
              <a href="#"><i class="fa-brands fa-facebook"></i></a>
              <a href="#"><i class="fa-brands fa-linkedin"></i></a>
              <a href="#"><i class="fa-brands fa-youtube"></i></a>
            </div>
          </div>
          <div class="member-info">
            <h4>${name}</h4>
            <span>${major}</span>
          </div>
        </div>
      `;
      // Create a new instance for the staff page container
      // var staffPageTeamMembersContainer = document.getElementById(
      //   "teamMembersContainerHome"
      // );
      // var memberCardForStaff = memberCard.cloneNode(true);
      // staffPageTeamMembersContainer.appendChild(memberCardForStaff);
      // Append the new team member card to the home page container
      var homePageTeamMembersContainer = document.getElementById(
        "teamMembersContainer"
      );
      homePageTeamMembersContainer.appendChild(memberCard);

      // Store the team member data in localStorage for both pages
      var teamMembers = JSON.parse(localStorage.getItem("teamMembers")) || [];
      var newMember = { name: name, major: major, image: e.target.result };
      teamMembers.push(newMember);
      localStorage.setItem("teamMembers", JSON.stringify(teamMembers));

      // Update the select input with the new team member name on the staff page
      var deleteMemberSelect = document.getElementById("deleteMemberSelect");
      var option = document.createElement("option");
      option.value = name;
      option.text = name;
      deleteMemberSelect.add(option);

      // Clear input fields
      document.getElementById("name").value = "";
      document.getElementById("major").value = "";
      document.getElementById("image").value = "";

      // Show success message
      var successMessage = document.getElementById("successMessage");
      successMessage.innerHTML = "Team member added successfully!";
    };
    // Set a timeout to clear the success message after 2 seconds
    setTimeout(function () {
      successMessage.innerHTML = ""; // Clear the success message after 2 seconds
    }, 2000); // 2000 milliseconds = 2 seconds
    // Read the file as a data URL
    reader.readAsDataURL(imageFile);
  } else {
    alert("Please fill in all fields.");
  }
}

// Function to remove a team member
function deleteTeamMember() {
  // Get the selected team member name
  var deleteMemberSelect = document.getElementById("deleteMemberSelect");
  var selectedName = deleteMemberSelect.value;

  // Ask for confirmation
  var isConfirmed = window.confirm(
    "Are you sure you want to delete this team member?"
  );

  if (isConfirmed && selectedName) {
    // Find and remove the team member card
    var teamMemberCard = Array.from(
      document.querySelectorAll(".team-member-card")
    ).find(
      (card) =>
        card
          .querySelector(".member-info h4")
          .textContent.trim()
          .toLowerCase() === selectedName.toLowerCase()
    );

    if (teamMemberCard) {
      teamMemberCard.remove();

      // Remove the team member from localStorage
      var teamMembers = JSON.parse(localStorage.getItem("teamMembers")) || [];
      var updatedTeamMembers = teamMembers.filter(
        (member) => member.name !== selectedName
      );
      localStorage.setItem("teamMembers", JSON.stringify(updatedTeamMembers));

      // Remove the option from the select input
      deleteMemberSelect.remove(deleteMemberSelect.selectedIndex);

      // Show success message
      var successMessage = document.getElementById("successMessage");
      successMessage.innerHTML = "Team member removed successfully!";
    }
  }
}

// Function to load team members from localStorage on page load
// Function to load team members from localStorage on page load
// function loadTeamMembers() {
//   var teamMembers = JSON.parse(localStorage.getItem("teamMembers")) || [];
//   var teamMembersContainer = document.getElementById("teamMembersContainer");

//   // Clear the container before adding new cards
//   teamMembersContainer.innerHTML = "";

//   teamMembers.forEach(function (member) {
//     var memberCard = document.createElement("div");
//     memberCard.className =
//       "col-lg-3 col-md-6 d-flex align-items-stretch team-member-card";
//     memberCard.innerHTML = `
//       <div class="member" data-aos="fade-up" data-aos-delay="100">
//         <div class="member-img">
//           <div class="image">
//             <img src="${member.image}" alt="${member.name}" />
//           </div>
//           <div class="social">
//             <a href="#"><i class="fa-brands fa-twitter"></i></a>
//             <a href="#"><i class="fa-brands fa-facebook"></i></a>
//             <a href="#"><i class="fa-brands fa-linkedin"></i></a>
//             <a href="#"><i class="fa-brands fa-youtube"></i></a>
//           </div>
//         </div>
//         <div class="member-info">
//           <h4>${member.name}</h4>
//           <span>${member.major}</span>
//         </div>
//       </div>
//     `;

//     teamMembersContainer.appendChild(memberCard);

//     // Update the select input with the team member names
//     var deleteMemberSelect = document.getElementById("deleteMemberSelect");
//     var option = document.createElement("option");
//     option.value = member.name;
//     option.text = member.name;
//     deleteMemberSelect.add(option);
//   });
// }
function loadTeamMembers() {
  var teamMembers = JSON.parse(localStorage.getItem("teamMembers")) || [];
  var teamMembersContainer = document.getElementById("teamMembersContainer");

  // Clear the container before adding new cards
  teamMembersContainer.innerHTML = "";

  teamMembers.forEach(function (member) {
    var memberCard = document.createElement("div");
    memberCard.className =
      "col-lg-3 col-md-6 d-flex align-items-stretch team-member-card";
    memberCard.innerHTML = `
      <div class="member" data-aos="fade-up" data-aos-delay="100">
        <div class="member-img">
          <div class="image">
            <img src="${member.image}" alt="${member.name}" />
          </div>
          <div class="social">
            <a href="#"><i class="fa-brands fa-twitter"></i></a>
            <a href="#"><i class="fa-brands fa-facebook"></i></a>
            <a href="#"><i class="fa-brands fa-linkedin"></i></a>
            <a href="#"><i class="fa-brands fa-youtube"></i></a>
          </div>
        </div>
        <div class="member-info">
          <h4>${member.name}</h4>
          <span>${member.major}</span>
        </div>
      </div>
    `;

    teamMembersContainer.appendChild(memberCard);
  });
}

// Load team members and select input on page load
document.addEventListener("DOMContentLoaded", function () {
  loadTeamMembers();
});
