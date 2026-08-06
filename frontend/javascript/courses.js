// !   SEARCH
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

// Single unified filter function - combines search, category, and price
function updateCards() {
  const searchTerm = $("#search-input").val().toLowerCase();

  const selectedCategoryEl = $(".dropdown-list-item.active");
  const selectedCategory = selectedCategoryEl.length
    ? selectedCategoryEl.data("value").toLowerCase()
    : "everything";

  const selectedPriceEl = $(".dropdown-list-price.active");
  const selectedPriceRange = selectedPriceEl.length
    ? selectedPriceEl.data("value").toLowerCase()
    : "any price";

  let anyMatches = false;

  $(".course-item").each(function () {
    const courseTitle = $(this).find(".course-title a").text().toLowerCase();
    const courseCategory = $(this)
      .find(".course-category a")
      .text()
      .toLowerCase();
    const coursePriceText = $(this)
      .find(".course-price")
      .text()
      .replace("$", "");
    const coursePrice = parseFloat(coursePriceText);

    const matchesSearch = courseTitle.includes(searchTerm);
    const matchesCategory =
      selectedCategory === "everything" ||
      courseCategory.includes(selectedCategory);

    let matchesPriceRange = true;
    if (selectedPriceRange !== "any price") {
      if (selectedPriceRange === "120+") {
        matchesPriceRange = coursePrice >= 120;
      } else {
        const priceRangeValues = selectedPriceRange.split("-");
        const minPrice = parseFloat(priceRangeValues[0]);
        const maxPrice = parseFloat(priceRangeValues[1]);
        matchesPriceRange = coursePrice >= minPrice && coursePrice <= maxPrice;
      }
    }

    const cardMatchesCriteria =
      matchesSearch && matchesCategory && matchesPriceRange;

    $(this).toggle(cardMatchesCriteria);

    if (cardMatchesCriteria) {
      anyMatches = true;
    }
  });

  noCardsMessage.toggle(!anyMatches);
}

$(document).ready(function () {
  $("#search-input").on("input", function () {
    updateCards();
  });

  $(".dropdown-list-item").on("click", function () {
    $(".dropdown-list-item").removeClass("active");
    $(this).addClass("active");
    updateCards();
  });

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
    updateCards();
  });
});
// ! finish
