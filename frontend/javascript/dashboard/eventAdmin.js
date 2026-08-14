/* __IIFE_WRAPPED__ */
(function () {

var API_BASE = "/backend/";
var IMAGE_BASE = "/frontend/assets/images/events_uploads/";

var cachedEvents = [];

function escapeHtml(text) {
  return $("<div>").text(text).html();
}

function renderEventCard(container, event) {
  var card = document.createElement("div");
  card.className = "col-lg-3 col-md-6 d-flex align-items-stretch";
  var imgHtml = event.image
    ? '<img src="' + IMAGE_BASE + event.image + '" alt="' + escapeHtml(event.nameType) + '" style="width:100%;border-radius:6px" onerror="this.style.display=\'none\'" />'
    : "";
  card.innerHTML =
    '<div class="member" style="padding:12px">' +
      imgHtml +
      "<h4>" + event.nameType + "</h4>" +
      "<span>" + event.Date + "</span>" +
      "<p>" + (event.description || "") + "</p>" +
    "</div>";
  container.appendChild(card);
}

function loadEvents() {
  var container = document.getElementById("eventsContainer");
  var deleteEventSelect = document.getElementById("deleteEventSelect");

  fetch(API_BASE + "getEvents.php")
    .then(function (res) { return res.json(); })
    .then(function (events) {
      cachedEvents = events;

      if (container) {
        container.innerHTML = "";
        events.forEach(function (event) {
          renderEventCard(container, event);
        });
      }

      if (deleteEventSelect) {
        deleteEventSelect.innerHTML = '<option value="">-- Select an event --</option>';
        events.forEach(function (event) {
          var option = document.createElement("option");
          option.value = event.id;
          option.text = event.nameType + " (" + event.Date + ")";
          deleteEventSelect.add(option);
        });
      }
    })
    .catch(function () {
      if (container) {
        container.innerHTML = '<p class="error">Failed to load events.</p>';
      }
    });
}

function showMessage(text) {
  var successMessage = document.getElementById("eventSuccessMessage");
  if (!successMessage) return;
  successMessage.innerHTML = text;
  setTimeout(function () {
    successMessage.innerHTML = "";
  }, 2500);
}

function resetForm() {
  document.getElementById("editingEventId").value = "";
  document.getElementById("eventTitle").value = "";
  document.getElementById("eventDate").value = "";
  document.getElementById("eventDescription").value = "";
  document.getElementById("eventLink").value = "";
  document.getElementById("eventImage").value = "";
  document.getElementById("btnAddEvent").textContent = "Add Event";
}

function addEvent() {
  var editingId = document.getElementById("editingEventId").value;
  var nameType = document.getElementById("eventTitle").value.trim();
  var date = document.getElementById("eventDate").value.trim();
  var description = document.getElementById("eventDescription").value.trim();
  var linkInfo = document.getElementById("eventLink").value.trim();
  var imageInput = document.getElementById("eventImage");

  if (!nameType || !date) {
    alert("Please fill in the title and date.");
    return;
  }

  var formData = new FormData();
  formData.append("nameType", nameType);
  formData.append("Date", date);
  formData.append("description", description);
  formData.append("linkInfo", linkInfo);
  if (imageInput.files.length > 0) {
    formData.append("image", imageInput.files[0]);
  }

  var url;
  if (editingId) {
    formData.append("id", editingId);
    url = API_BASE + "updateEvent.php";
  } else {
    url = API_BASE + "addEvent.php";
  }

  fetch(url, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        showMessage(data.message);
        resetForm();
        loadEvents();
      } else {
        alert(data.message || "Something went wrong.");
      }
    })
    .catch(function () {
      alert("Request failed. Please try again.");
    });
}

function editEvent() {
  var deleteEventSelect = document.getElementById("deleteEventSelect");
  var selectedId = deleteEventSelect.value;
  if (!selectedId) {
    alert("Please select an event first.");
    return;
  }

  var event = cachedEvents.find(function (e) { return String(e.id) === String(selectedId); });
  if (!event) return;

  document.getElementById("editingEventId").value = event.id;
  document.getElementById("eventTitle").value = event.nameType;
  document.getElementById("eventDate").value = event.Date;
  document.getElementById("eventDescription").value = event.description || "";
  document.getElementById("eventLink").value = event.linkInfo || "";
  document.getElementById("eventImage").value = "";
  document.getElementById("btnAddEvent").textContent = "Update Event";
}

function cancelEditEvent() {
  resetForm();
}

function deleteEvent() {
  var deleteEventSelect = document.getElementById("deleteEventSelect");
  var selectedId = deleteEventSelect.value;

  if (!selectedId) {
    alert("Please select an event first.");
    return;
  }

  var isConfirmed = window.confirm("Are you sure you want to delete this event?");
  if (!isConfirmed) return;

  var formData = new FormData();
  formData.append("id", selectedId);

  fetch(API_BASE + "deleteEvent.php", { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        showMessage(data.message);
        resetForm();
        loadEvents();
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
// of never firing (same fix already applied in staff.js/NotifiAdmin.js).
$(document).ready(function () {
  loadEvents();
});

/* __WINDOW_EXPOSED__ */
window.addEvent = addEvent;
window.editEvent = editEvent;
window.cancelEditEvent = cancelEditEvent;
window.deleteEvent = deleteEvent;
})();
