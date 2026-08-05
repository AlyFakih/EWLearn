// !   SEARCH
// Add this line at the beginning to select the message element
const noCardsMessage = $(".no-cards-message");

let dropdownBtnText = document.getElementById("drop-text");
let span = document.getElementById("span");
let icon = document.getElementById("icon");
let list = document.getElementById("list");
let input = document.getElementById("search-input");
let listItems = document.querySelectorAll(".dropdown-list-item");

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

for (item of listItems) {
  item.onclick = function (e) {
    span.innerText = e.target.innerText;
    if (e.target.innerText == "Everything") {
      input.placeholder = "Search Anything...";
    } else {
      input.placeholder = "Search in " + e.target.innerText + "...";
    }
  };
}
// Filter and Search Logic for Courses Page
$(document).ready(function () {
  $("#search-input").on("input", function () {
    updateCards();
  });

  $(".dropdown-list-item").on("click", function () {
    $(".dropdown-list-item").removeClass("active");
    $(this).addClass("active");
    updateCards();
  });

  function updateCards() {
    const searchTerm = $("#search-input").val().toLowerCase();
    const selectedCategory = $(".dropdown-list-item.active")
      .data("value")
      .toLowerCase();

    let anyMatches = false; // Flag to track if any cards match the criteria

    $(".course-item").each(function () {
      const courseTitle = $(this).find(".course-title a").text().toLowerCase();
      const courseCategory = $(this)
        .find(".course-category a")
        .text()
        .toLowerCase();

      const matchesSearch = courseTitle.includes(searchTerm);
      const matchesCategory =
        selectedCategory === "everything" ||
        courseCategory.includes(selectedCategory);

      const cardMatchesCriteria = matchesSearch && matchesCategory;

      $(this).toggle(cardMatchesCriteria);

      if (cardMatchesCriteria) {
        anyMatches = true; // At least one card matches the criteria
      }
    });

    // Display or hide the message based on whether any matches were found
    noCardsMessage.toggle(!anyMatches);
  }
});

// Dropdown for Price
$(document).ready(function () {
  $("#drop-text-price").on("click", function (e) {
    e.stopPropagation();
    $("#price-list").toggleClass("show");
    $("#icon-price").css(
      "transform",
      $("#price-list").hasClass("show") ? "rotate(-180deg)" : "rotate(0deg)"
    );
  });

  $(document).on("click", function (e) {
    if (
      !$(e.target).closest("#drop-text-price").length &&
      !$(e.target).is("#drop-text-price")
    ) {
      $("#price-list").removeClass("show");
      $("#icon-price").css("transform", "rotate(0deg)");
    }
  });

  $(".dropdown-list-price").on("click", function () {
    $(".dropdown-list-price").removeClass("active");
    $(this).addClass("active");
    updateCards(); // Call the function to update the displayed cards when the price range changes
  });
});

// Modify the updateCards function to consider the price filter
function updateCards() {
  const selectedPriceRange = $(".dropdown-list-price.active")
    .data("value")
    .toLowerCase();

  let anyMatches = false; // Flag to track if any cards match the criteria

  $(".course-item").each(function () {
    const coursePriceText = $(this)
      .find(".course-price")
      .text()
      .replace("$", "");
    const coursePrice = parseFloat(coursePriceText);
    let matchesPriceRange = true;

    if (selectedPriceRange !== "any price") {
      const priceRangeValues = selectedPriceRange.split("-");
      const minPrice = parseFloat(priceRangeValues[0]);
      const maxPrice = parseFloat(priceRangeValues[1]);

      if (selectedPriceRange === "120+") {
        // Handle the "$120+" case separately
        matchesPriceRange = coursePrice >= 120;
      } else {
        matchesPriceRange = coursePrice >= minPrice && coursePrice <= maxPrice;
      }
    }

    const cardMatchesCriteria = matchesPriceRange;

    $(this).toggle(cardMatchesCriteria);

    if (cardMatchesCriteria) {
      anyMatches = true; // At least one card matches the criteria
    }
  });

  // Display or hide the message based on whether any matches were found
  noCardsMessage.toggle(!anyMatches);
}
// ! finish
