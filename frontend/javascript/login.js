document.addEventListener("DOMContentLoaded", function () {
  const signUpForm = document.getElementById("sign-up-form");

  signUpForm.addEventListener("submit", function (event) {
    if (!validateForm()) {
      console.log("Validation failed");
      // Prevent the form submission if validation fails
      event.preventDefault();
    } else {
      console.log("Validation passed");
    }
  });
  // Your existing code for toggling sign-up and sign-in modes
  const sign_in_btn = document.querySelector("#sign-in-button");
  const sign_up_btn = document.querySelector("#sign-up-button");
  const container = document.querySelector(".container");
  const leftcontent = document.querySelector(".left-panel .content");
  const rightcontent = document.querySelector(".right-panel .content");

  sign_up_btn.addEventListener("click", () => {
    container.classList.add("sign-up-mode");
    leftcontent.style.opacity = "0";
    rightcontent.style.opacity = "1";
  });

  sign_in_btn.addEventListener("click", () => {
    container.classList.remove("sign-up-mode");
    leftcontent.style.opacity = "1";
    rightcontent.style.opacity = "0";
  });
  document
    .getElementById("toggleOldPassword")
    .addEventListener("click", function () {
      togglePasswordVisibility("oldPassword");
    });

  document
    .getElementById("togglePassword")
    .addEventListener("click", function () {
      togglePasswordVisibility("password");
    });

  document
    .getElementById("toggleConfirmPassword")
    .addEventListener("click", function () {
      togglePasswordVisibility("confirmPassword");
    });
  function togglePasswordVisibility(passwordFieldId) {
    var passwordInput = document.getElementById(passwordFieldId);
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
    } else {
      passwordInput.type = "password";
    }
  }
});

// ! password----------------
function validateForm() {
  let password = document.getElementById("passwordsignup").value;
  let confirmPassword = document.getElementById("confirmPasswordsignup").value;

  let inputs = document.querySelectorAll(
    ".sign-up-form input, .sign-up-form select"
  );

  for (let i = 0; i < inputs.length; i++) {
    if (!inputs[i].value) {
      alert("Please fill in all fields");
      return false; // Prevent form submission
    }
  }

  let email = document.getElementById("email").value;
  if (!isValidEmail(email)) {
    alert("Please enter a valid Gmail email address");
    return false; // Prevent form submission
  }
  if (password !== confirmPassword) {
    alert("Password and Confirm Password do not match");
    return false; // Prevent form submission
  }

  return true; // Allow form submission
}

function isValidEmail(email) {
  // Simple check for a Gmail address
  let gmailRegex = /^[a-zA-Z0-9._-]+@gmail\.com$/;
  return gmailRegex.test(email);
}

document.getElementById("loginForm").addEventListener("submit", function (event) {
  event.preventDefault(); // Prevent the default form submission

  // Reset error messages
  document.getElementById("emailErrorMessage").innerText = "";
  document.getElementById("passwordErrorMessage").innerText = "";

  // Get login form data
  var email = document.getElementById("loginEmail").value;
  var password = document.getElementById("loginPassword").value;

  // Basic validation
  let hasErrors = false;

  if (email.trim() === "") {
    document.getElementById("emailErrorMessage").innerText = "Email is required.";
    hasErrors = true;
  }

  if (password.trim() === "") {
    document.getElementById("passwordErrorMessage").innerText = "Password is required.";
    hasErrors = true;
  }

  // If form has validation errors, don't submit
  if (hasErrors) {
    return false;
  }

  // Submit the form using AJAX if validation passes
  $.ajax({
    type: "POST",
    url: "../../backend/login.php",
    data: {
      loginEmail: email,
      loginPassword: password
    },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        // Store user role in local storage for later use
        localStorage.setItem("userRole", response.role);

        // Redirect to the appropriate dashboard based on role
        window.location.href = response.redirect;
      } else {
        // Show error message
        alert(response.message || "An unexpected error occurred");
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
      alert("An unexpected error occurred. Please try again later.");
    }
  });
});

// !  Get the modal ADD and update
let modal = document.getElementById("id01");
let modalupdate = document.getElementById("id02");

// When the user clicks anywhere outside of any modal, close it
window.onclick = function (event) {
  if (event.target == modal) {
    modal.style.display = "none";
  } else if (event.target == modalupdate) {
    modalupdate.style.display = "none";
  }
};

// This is the old login form handler - now replaced with the new implementation above
//         // Handle the response
//         if (response.status === "success") {
//           // Check the user's role and redirect accordingly
//           if (response.role === "admin") {
//             window.location.href = "../pages/dashboardAdmin/AdminDash.html";
//             document.getElementById("loginLink").innerHTML =
//               '<a href="#">LOGOUT</a>';
//           } else {
//             // Redirect to the home page or another appropriate page
//             window.location.href = "../pages/Home.html";
//             document.getElementById("loginLink").innerHTML =
//               '<a href="#">LOGOUT</a>';
//           }
//         } else {
//           // Display the error message
//           $("#loginErrorMessage").text(response.message);
//         }
//       },
//       error: function () {
//         // Handle the error
//         $("#loginErrorMessage").text("An unexpected error occurred.");
//       },
//     });
//   });
// });
// login.js

document.addEventListener("DOMContentLoaded", function () {
  // Check login state on page load
  // checkLoginState();

  const loginForm = document.querySelector(".sign-in-form");

  loginForm.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent the default form submission

    // Fetch the email and password from the form
    const email = document.querySelector("[name=loginEmail]").value;
    const password = document.querySelector("[name=loginPassword]").value;

    // Make an AJAX request to the login.php file
    $.ajax({
      type: "POST",
      url: "../../backend/login.php",
      data: {
        loginEmail: email,
        loginPassword: password,
      },
      dataType: "json", // Expect JSON response
      success: function (response) {
        // Handle the response
        if (response.status === "success") {
          // Save login state

          saveLoginState();

          // Check the user's role and redirect accordingly
          window.location.href = response.redirect;
        } else {
          // Display the error message
          $("#loginErrorMessage").text(response.message);
        }
      },
      error: function () {
        // Handle the error
        $("#loginErrorMessage").text("An unexpected error occurred.");
      },
    });
  });
});
document.addEventListener("DOMContentLoaded", function () {
  const signUpForm = document.getElementById("sign-up-form");

  signUpForm.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent the default form submission

    if (!validateForm()) {
      console.log("Validation failed");
      // Prevent the form submission if validation fails
      return;
    }

    // Fetch the form data
    var formData = $(this).serialize();

    // Make an AJAX request to the register.php file
    $.ajax({
      method: "POST",
      url: "../../backend/regitser.php",
      data: formData,
      dataType: "json",
      success: function (response) {
        // Display the message under the button
        $("#MessageReg").text(response.message).css("color", "white");
        setTimeout(function () {
          $("#MessageReg").text("");
        }, 2000);
        // Optionally, you can reset the form or perform other actions
        if (response.status === "success") {
          signUpForm.reset(); // Reset the form
          // You can redirect or perform other actions here
          window.location.href = "../pages/loginRegister.html";
        }
      },
      error: function (xhr, status, error) {
        // Display an error message
      },
    });
  });
});
