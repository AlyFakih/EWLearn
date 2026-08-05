// Add this line at the beginning to select the message element
const noCardsMessage = $(".no-cards-message");

// Filter and Search Logic
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
    const selectedMajor = $(".dropdown-list-item.active")
      .data("value")
      .toLowerCase();

    let anyMatches = false; // Flag to track if any cards match the criteria

    $(".card").each(function () {
      const instructorName = $(this).find(".namein").text().toLowerCase();
      const instructorMajor = $(this).find(".inf").text().toLowerCase();

      const matchesSearch = instructorName.includes(searchTerm);
      const matchesMajor =
        selectedMajor === "everything" ||
        instructorMajor.includes(selectedMajor);

      const cardMatchesCriteria = matchesSearch && matchesMajor;

      $(this).toggle(cardMatchesCriteria);

      if (cardMatchesCriteria) {
        anyMatches = true; // At least one card matches the criteria
      }
    });

    // Display or hide the message based on whether any matches were found
    noCardsMessage.toggle(!anyMatches);
  }
});

// Dropdown Logic
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
