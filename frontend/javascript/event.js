var EVENTS_IMAGE_BASE = "/frontend/assets/images/events_uploads/";

function renderEventItem(event) {
  var bg = event.image ? EVENTS_IMAGE_BASE + event.image : "../assets/imageEvents/events1.png";
  // linkInfo holds the short summary shown on cards; description (if filled
  // in) is the longer body shown only on the detail page.
  var snippet = (event.linkInfo || "").slice(0, 90);
  if ((event.linkInfo || "").length > 90) snippet += "...";

  return (
    '<div class="col-md-4 mb-4">' +
      '<div class="blog-item set-bg" data-setbg="' + bg + '">' +
        '<div class="bi-tag bg-gradient">' + event.Date + "</div>" +
        '<div class="bi-text">' +
          "<h5>" +
            '<a href="./eventsDetails.html?id=' + event.id + '">' + event.nameType + "</a>" +
          "</h5>" +
          "<p>" + snippet + "</p>" +
        "</div>" +
      "</div>" +
    "</div>"
  );
}

function applyBackgrounds() {
  document.querySelectorAll("[data-setbg]").forEach(function (element) {
    element.style.backgroundImage = "url(" + element.getAttribute("data-setbg") + ")";
  });
}

$(document).ready(function () {
  var list = $("#eventsList");
  if (!list.length) {
    // Not on Events.html (e.g. this script isn't included elsewhere), nothing to do.
    return;
  }

  $.ajax({
    url: "/backend/getEvents.php",
    type: "GET",
    dataType: "json",
    success: function (events) {
      if (!events.length) {
        $(".no-events-message").show();
        return;
      }
      list.html(events.map(renderEventItem).join(""));
      applyBackgrounds();
    },
    error: function () {
      list.html('<p class="error text-center">Failed to load events.</p>');
    },
  });
});
