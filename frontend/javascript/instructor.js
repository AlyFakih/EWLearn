const noCardsMessage = $(".no-cards-message");
const IMAGE_BASE = "/frontend/images/";
const FALLBACK_IMAGE = "/frontend/assets/images/aprox.png";

function renderInstructorCard(instructor) {
  var imgSrc = instructor.image ? IMAGE_BASE + instructor.image : FALLBACK_IMAGE;
  var categories = instructor.categories || "";
  var courseLabel = instructor.courseCount === 1 ? "Course" : "Courses";

  return (
    '<div class="col">' +
      '<div class="card">' +
        '<img src="' + imgSrc + '" alt="Instructor profile image" onerror="this.src=\'' + FALLBACK_IMAGE + '\'" />' +
        "<h1 class=\"namein\">" + instructor.fullName + "</h1>" +
        '<p class="inf">' + (categories || "Instructor") + "</p>" +
        '<p class="infocourse">' +
          '<i class="fa-solid fa-chalkboard-user fa-2xl"></i> ' + instructor.courseCount + " " + courseLabel +
        "</p>" +
        "<p>" +
          '<a href="./instructorDetails.html?id=' + instructor.id + '"><button>View Profile</button></a>' +
        "</p>" +
      "</div>" +
    "</div>"
  );
}

function loadInstructors() {
  $.ajax({
    url: "/backend/getInstructorsList.php",
    type: "GET",
    dataType: "json",
    success: function (instructors) {
      var cardsContainer = $("#instructorCards");
      cardsContainer.empty();

      if (!instructors.length) {
        noCardsMessage.text("No instructors found.").show();
        return;
      }

      instructors.forEach(function (instructor) {
        cardsContainer.append(renderInstructorCard(instructor));
      });

      // Build the category filter from the real categories instructors
      // actually teach, instead of a fixed list that didn't match real data.
      var categorySet = {};
      instructors.forEach(function (instructor) {
        (instructor.categories || "").split(",").forEach(function (c) {
          c = c.trim();
          if (c) categorySet[c] = true;
        });
      });

      var list = $("#list");
      Object.keys(categorySet).sort().forEach(function (category) {
        list.append(
          $("<li>", { class: "dropdown-list-item", text: category }).attr("data-value", category)
        );
      });

      updateCards();
    },
    error: function () {
      $("#instructorCards").html('<p class="error">Failed to load instructors.</p>');
    },
  });
}

function updateCards() {
  const searchTerm = $("#search-input").val().toLowerCase();
  const activeItem = $(".dropdown-list-item.active");
  const selectedMajor = activeItem.length
    ? activeItem.data("value").toString().toLowerCase()
    : "everything";

  let anyMatches = false;

  $("#instructorCards .card").each(function () {
    const instructorName = $(this).find(".namein").text().toLowerCase();
    const instructorMajor = $(this).find(".inf").text().toLowerCase();

    const matchesSearch = instructorName.includes(searchTerm);
    const matchesMajor =
      selectedMajor === "everything" || instructorMajor.includes(selectedMajor);

    const cardMatchesCriteria = matchesSearch && matchesMajor;

    $(this).closest(".col").toggle(cardMatchesCriteria);

    if (cardMatchesCriteria) {
      anyMatches = true;
    }
  });

  noCardsMessage.toggle(!anyMatches);
}

$(document).ready(function () {
  loadInstructors();

  $("#search-input").on("input", function () {
    updateCards();
  });

  // Event delegation (not a one-time querySelectorAll) because the dropdown
  // items are added dynamically after the real category list loads.
  $(document).on("click", ".dropdown-list-item", function () {
    $(".dropdown-list-item").removeClass("active");
    $(this).addClass("active");

    const text = $(this).text();
    $("#span").text(text);
    $("#search-input").attr(
      "placeholder",
      text === "Everything" ? "Search Anything..." : "Search in " + text + "..."
    );

    updateCards();
  });
});

// Dropdown open/close logic.
let dropdownBtnText = document.getElementById("drop-text");
let icon = document.getElementById("icon");
let list = document.getElementById("list");

dropdownBtnText.onclick = function () {
  list.classList.toggle("show");
  icon.style.rotate = "-180deg";
};

window.onclick = function (e) {
  if (
    e.target.id !== "drop-text" &&
    e.target.id !== "icon" &&
    e.target.id !== "span"
  ) {
    list.classList.remove("show");
    icon.style.rotate = "0deg";
  }
};
