// auth.js

// Function to save login state
function saveLoginState() {
  localStorage.setItem("isLoggedIn", true);
  // You can also store other user-related information if needed
  // localStorage.setItem("userRole", response.role);
}

// Function to remove login state
function removeLoginState() {
  localStorage.removeItem("isLoggedIn");
  window.location.href = "../pages/loginRegister.html";
  // Remove other user-related information if needed
  // localStorage.removeItem("userRole");
}

// Function to check login state on page load
function checkLoginState() {
  const isLoggedIn = localStorage.getItem("isLoggedIn");

  if (isLoggedIn) {
    // User is logged in, display the logout link and dashboard link
    document.getElementById("loginLink").innerHTML =
      '<a id="logoutButton" onClick="removeLoginState()" href="#">LOGOUT</a> | <a href="../pages/dashboardAdmin/AdminDash.html">DASHBOARD</a>';
  } else {
    // User is not logged in, display the login link
    document.getElementById("loginLink").innerHTML =
      '<a href="../pages/loginRegister.html">LOGIN</a>';
  }
}
