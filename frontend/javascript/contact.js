$(document).ready(function () {
  $("#contactForm").on("submit", function (e) {
    e.preventDefault();

    var resultMessage = $("#contactResultMessage");
    var submitButton = $(this).find("button[type=submit]");

    var payload = {
      name: $("#name").val().trim(),
      email: $("#email").val().trim(),
      phone: $("#phone").val().trim(),
      message: $("#message").val().trim(),
    };

    submitButton.prop("disabled", true);
    resultMessage.text("").removeClass("success error");

    $.ajax({
      type: "POST",
      url: "/backend/sendContactMessage.php",
      data: payload,
      dataType: "json",
      success: function (response) {
        resultMessage
          .text(response.message)
          .removeClass(response.success ? "error" : "success")
          .addClass(response.success ? "success" : "error");
        if (response.success) {
          $("#contactForm")[0].reset();
        }
      },
      error: function () {
        resultMessage
          .text("Something went wrong. Please try again later.")
          .removeClass("success")
          .addClass("error");
      },
      complete: function () {
        submitButton.prop("disabled", false);
      },
    });
  });
});
