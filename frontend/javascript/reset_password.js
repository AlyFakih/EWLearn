const resetForm = document.getElementById("resetFrom");
const resetMessage = document.getElementById("Resetmessage");

resetForm.addEventListener("submit", function (event) {
  event.preventDefault(); // Prevent the default form submission

  // Fetch the email, old password, and new password from the form
  const email = $("#EmailR").val();
  const oldPassword = $("#oldPassword").val();
  const newPassword = $("#password").val();

  // Make an AJAX request to the resetPassword.php file
  $.ajax({
    type: "POST",
    url: "../../backend/resetPassword.php",
    data: $("form").serialize(),

    dataType: "json", // Expect JSON response
    success: function (response) {
      // Handle the response
      if (response.status === "success") {
        resetMessage.style.color = "green";
      } else {
        resetMessage.style.color = "red";
      }
      resetMessage.innerText = response.message;
    },
    error: function () {
      resetMessage.style.color = "red";
      resetMessage.innerText = "An unexpected error occurred.";
    },
  });
});
