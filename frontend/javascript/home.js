// js code for navbar

const togglebtn = document.querySelector(".toggle_btn");
const togglebtnIcon = document.querySelector(".toggle_btn i");
const dropdownMenu = document.querySelector(".dropdown_menu");

togglebtn.onclick = function () {
  dropdownMenu.classList.toggle("open");
  const isOpen = dropdownMenu.classList.contains("open");
};

// swiper cards

let swiperCards = new Swiper(".card_contant", {
  loop: true,
  spaceBetween: 32,
  grabCursor: true,

  pagination: {
    el: ".swiper-pagination",
    clickable: true,
    dynamicBullets: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  breakpoints: {
    600: {
      slidesPerView: 2,
    },
    968: {
      slidesPerView: 3,
    },
  },
});

// ! ----------Get the button
let mybutton = document.getElementById("myBtn");

// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function () {
  scrollFunction();
};

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    mybutton.style.display = "block";
  } else {
    mybutton.style.display = "none";
  }
}

// When the user clicks on the button, scroll to the top of the document
function topFunction() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}

// Navbar.js
document.addEventListener("DOMContentLoaded", function () {
  // Check login state on page load
  checkLoginState();

  // Other navbar-related JavaScript code
});
// Function to check login state on page load
function checkLogin() {
  const isLoggedIn = localStorage.getItem("isLoggedIn");

  if (isLoggedIn) {
    window.location.href = "../pages/courses.html";
  } else {
    // User is not logged in, display the login link
    window.location.href = "../pages/loginRegister.html";
  }
}
