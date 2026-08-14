/* __IIFE_WRAPPED__ */
(function () {

// This script is shared between two pages at different directory depths
// (Home.html and the admin staff.php dashboard section, injected at runtime
// by dash.js while the browser is still on AdminDash.php's URL). A relative
// URL here would resolve differently depending on which page loaded it, so
// every backend/image URL below is root-relative instead.
var API_BASE = "/backend/";
var IMAGE_BASE = "/frontend/assets/images/team/";

var cachedMembers = [];

function renderMemberCard(container, member) {
  var card = document.createElement("div");
  card.className =
    "col-lg-3 col-md-6 d-flex align-items-stretch team-member-card";
  card.setAttribute("data-member-id", member.id);
  card.innerHTML =
    '<div class="member" data-aos="fade-up" data-aos-delay="100">' +
      '<div class="member-img">' +
        '<div class="image">' +
          '<img src="' + IMAGE_BASE + member.image + '" alt="' + member.name + '" onerror="this.src=\'/frontend/assets/images/aprox.png\'" />' +
        "</div>" +
        '<div class="social">' +
          '<a href="#"><i class="fa-brands fa-twitter"></i></a>' +
          '<a href="#"><i class="fa-brands fa-facebook"></i></a>' +
          '<a href="#"><i class="fa-brands fa-linkedin"></i></a>' +
          '<a href="#"><i class="fa-brands fa-youtube"></i></a>' +
        "</div>" +
      "</div>" +
      '<div class="member-info">' +
        "<h4>" + member.name + "</h4>" +
        "<span>" + member.major + "</span>" +
      "</div>" +
    "</div>";
  container.appendChild(card);
}

function loadTeamMembers() {
  var container = document.getElementById("teamMembersContainer");
  var deleteMemberSelect = document.getElementById("deleteMemberSelect");

  fetch(API_BASE + "getTeamMembers.php")
    .then(function (res) { return res.json(); })
    .then(function (members) {
      cachedMembers = members;

      if (container) {
        container.innerHTML = "";
        members.forEach(function (member) {
          renderMemberCard(container, member);
        });
      }

      if (deleteMemberSelect) {
        deleteMemberSelect.innerHTML = '<option value="">-- Select a team member --</option>';
        members.forEach(function (member) {
          var option = document.createElement("option");
          option.value = member.id;
          option.text = member.name;
          deleteMemberSelect.add(option);
        });
      }
    })
    .catch(function () {
      if (container) {
        container.innerHTML = '<p class="error">Failed to load team members.</p>';
      }
    });
}

function showMessage(text) {
  var successMessage = document.getElementById("successMessage");
  if (!successMessage) return;
  successMessage.innerHTML = text;
  setTimeout(function () {
    successMessage.innerHTML = "";
  }, 2500);
}

function resetForm() {
  document.getElementById("editingMemberId").value = "";
  document.getElementById("name").value = "";
  document.getElementById("major").value = "";
  document.getElementById("image").value = "";
  document.getElementById("btnAddTeamMember").textContent = "Add Team Member";
}

function addTeamMember() {
  var editingId = document.getElementById("editingMemberId").value;
  var name = document.getElementById("name").value.trim();
  var major = document.getElementById("major").value.trim();
  var imageInput = document.getElementById("image");

  if (!name || !major) {
    alert("Please fill in all fields.");
    return;
  }

  var formData = new FormData();
  formData.append("name", name);
  formData.append("major", major);
  if (imageInput.files.length > 0) {
    formData.append("image", imageInput.files[0]);
  }

  var url;
  if (editingId) {
    formData.append("id", editingId);
    url = API_BASE + "updateTeamMember.php";
  } else {
    if (imageInput.files.length === 0) {
      alert("Please choose an image.");
      return;
    }
    url = API_BASE + "addTeamMember.php";
  }

  fetch(url, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        showMessage(data.message);
        resetForm();
        loadTeamMembers();
      } else {
        alert(data.message || "Something went wrong.");
      }
    })
    .catch(function () {
      alert("Request failed. Please try again.");
    });
}

function editTeamMember() {
  var deleteMemberSelect = document.getElementById("deleteMemberSelect");
  var selectedId = deleteMemberSelect.value;
  if (!selectedId) {
    alert("Please select a team member first.");
    return;
  }

  var member = cachedMembers.find(function (m) { return String(m.id) === String(selectedId); });
  if (!member) return;

  document.getElementById("editingMemberId").value = member.id;
  document.getElementById("name").value = member.name;
  document.getElementById("major").value = member.major;
  document.getElementById("image").value = "";
  document.getElementById("btnAddTeamMember").textContent = "Update Team Member";
}

function cancelEditTeamMember() {
  resetForm();
}

function deleteTeamMember() {
  var deleteMemberSelect = document.getElementById("deleteMemberSelect");
  var selectedId = deleteMemberSelect.value;

  if (!selectedId) {
    alert("Please select a team member first.");
    return;
  }

  var isConfirmed = window.confirm(
    "Are you sure you want to delete this team member?"
  );
  if (!isConfirmed) return;

  var formData = new FormData();
  formData.append("id", selectedId);

  fetch(API_BASE + "deleteTeamMember.php", { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        showMessage(data.message);
        resetForm();
        loadTeamMembers();
      } else {
        alert(data.message || "Something went wrong.");
      }
    })
    .catch(function () {
      alert("Request failed. Please try again.");
    });
}

// $(document).ready (not a raw DOMContentLoaded listener) because on the
// admin dashboard this script is injected long after DOMContentLoaded has
// already fired once; jQuery's ready() runs immediately in that case instead
// of never firing.
$(document).ready(function () {
  loadTeamMembers();
});

/* __WINDOW_EXPOSED__ */
window.addTeamMember = addTeamMember;
window.editTeamMember = editTeamMember;
window.cancelEditTeamMember = cancelEditTeamMember;
window.deleteTeamMember = deleteTeamMember;
})();
