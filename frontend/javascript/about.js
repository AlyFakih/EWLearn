/*------------------------
		Testimonial Slider
    ----------------------- */
// $(".testimonial-slider").owlCarousel({
//     items: 2,
//     dots: false,
//     autoplay: false,
//     loop: true,
//     smartSpeed: 1200,
//     nav: true,
//     navText: ["<span class='fa fa-angle-left'></span>", "<span class='fa fa-angle-right'></span>"],
//     responsive: {
//         320: {
//             items: 1,
//         },
//         768: {
//             items: 2
//         }
//     }
// });

// $(document).ready(function () {
//   $(".testimonial-slider").owlCarousel({
//     items: 2,
//     loop: true,
//     nav: false, // Hide navigation buttons since there are only two items at a time
//     dots: false, // Hide navigation dots
//     autoplay: true,
//     autoplayTimeout: 5000, // Set the delay time in milliseconds (e.g., 5000ms for 5 seconds)
//     responsive: {
//       0: {
//         items: 1,
//       },
//       768: {
//         items: 2,
//       },
//     },
//     onChanged: function (event) {
//       // Hide the third testimonial after the first slide
//       if (event.item.index === 0) {
//         $(".testimonial-item.hidden").show();
//       } else {
//         $(".testimonial-item.hidden").hide();
//       }
//     },
//   });
// });



// Get all the testimonials-item cards. querySelectorAll() returns a NodeList,
// which has no .slice() - convert to a real Array so the .slice(3) below
// (and the rest of this file) works.
const testimonialsItems = Array.from(document.querySelectorAll(".testimonial-item"));

// Create a new Swiper instance
const swiper = new Swiper(".testimonial-slider", {
  slidesPerView: 1,
  spaceBetween: 10,
  loop: true,
  autoplay: {
    delay: 5000,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  // The fixed slidesPerView: 3 (no breakpoints) forced 3 columns at any
  // viewport width, including mobile, causing horizontal page overflow -
  // the old OwlCarousel config this replaced had responsive breakpoints,
  // but they were dropped in the migration to Swiper.
  breakpoints: {
    768: { slidesPerView: 2 },
    992: { slidesPerView: 3 },
  },
});

// Hide the remaining cards on initial load
const hiddenTestimonialsItems = testimonialsItems.slice(3);
hiddenTestimonialsItems.forEach((testimonialsItem) => {
  testimonialsItem.style.display = "none";
});

// Hide and show the remaining cards as the carousel swipes
swiper.on("slideChange", () => {
  const currentSlideIndex = swiper.activeIndex;
  const nextSlideIndex = (currentSlideIndex + 1) % swiper.slides.length;
  const previousSlideIndex =
    currentSlideIndex - 1 < 0
      ? swiper.slides.length - 1
      : currentSlideIndex - 1;

  // Hide the previous card
  hiddenTestimonialsItems.includes(testimonialsItems[previousSlideIndex]) &&
    (testimonialsItems[previousSlideIndex].style.display = "none");

  // Show the next card
  hiddenTestimonialsItems.includes(testimonialsItems[nextSlideIndex]) &&
    (testimonialsItems[nextSlideIndex].style.display = "block");
});
