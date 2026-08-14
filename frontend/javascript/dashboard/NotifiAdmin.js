/* __IIFE_WRAPPED__ */
(function () {

function roleMessage(role) {
  if (role === 'student') return 'New student account registered.';
  if (role === 'instructor') return 'New instructor account registered.';
  if (role === 'admin') return 'New admin account registered.';
  return 'New account registered.';
}

$(document).ready(function () {
  let box = document.getElementById("box");
  let down = false;

  function toggleNotifi() {
    if (down) {
      box.style.height = "0px";
      box.style.display = "none";
      down = false;
    } else {
      box.style.height = "auto";
      box.style.display = "block";
      down = true;
    }
  }

  $(".icon").click(function () {
    toggleNotifi();
  });

  // Populate with the most recently added real accounts, replacing the
  // previous hardcoded demo content.
  $.ajax({
    url: "../../../backend/getRecentUsers.php",
    type: "GET",
    dataType: "json",
    success: function (users) {
      $("#notifiCount, #notifiCount2").text(users.length);
      const container = $("#notifiItems");
      container.empty();
      if (!users.length) {
        container.append('<p class="no-data">No recent activity.</p>');
        return;
      }
      users.forEach(function (u) {
        const imgSrc = u.image ? "../../images/" + u.image : "../../assets/images/aprox.png";
        container.append(
          '<div class="notifi-items">' +
            '<img src="' + imgSrc + '" alt="img" onerror="this.src=\'../../assets/images/aprox.png\'" />' +
            '<div class="textMessage">' +
              '<h4>' + u.fullName + '</h4>' +
              '<p>' + roleMessage(u.role) + '</p>' +
            '</div>' +
          '</div>'
        );
      });
    },
    error: function () {
      $("#notifiItems").html('<p class="error">Failed to load notifications.</p>');
    },
  });
});

})();
