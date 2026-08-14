var EVENTS_IMAGE_BASE = "/frontend/assets/images/events_uploads/";

$(document).ready(function () {
  var id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    $("#eventTitle").text("No event selected.");
    return;
  }

  $.ajax({
    url: "/backend/getEventDetail.php",
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (event) {
      $("#eventTitle").text(event.nameType);
      $("#eventDate").html('<i class="fa-solid fa-clock fa-lg" style="color: #ff0000;"></i> ' + event.Date);
      $("#eventSummary").text(event.linkInfo || "");

      if (event.image) {
        var hero = document.getElementById("eventHero");
        hero.setAttribute("data-setbg", EVENTS_IMAGE_BASE + event.image);
        hero.style.backgroundImage = "url(" + EVENTS_IMAGE_BASE + event.image + ")";
      }

      if (event.description) {
        $("#eventDescription").text(event.description);
        $("#eventDescriptionWrap").show();
      }
    },
    error: function (xhr) {
      var message = (xhr.responseJSON && xhr.responseJSON.message) || "Failed to load event.";
      $("#eventTitle").text(message);
    },
  });
});
