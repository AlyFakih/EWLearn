document.addEventListener("DOMContentLoaded", function () {
  // Get all elements with the data-setbg attribute
  var elements = document.querySelectorAll("[data-setbg]");

  // Loop through each element and set the background image
  elements.forEach(function (element) {
    var imageUrl = element.getAttribute("data-setbg");
    element.style.backgroundImage = "url(" + imageUrl + ")";
  });
});
