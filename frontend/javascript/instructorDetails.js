/* __IIFE_WRAPPED__ */
(function () {

var IMAGE_BASE = "/frontend/images/";
var FALLBACK_IMAGE = "/frontend/assets/images/aprox.png";

function skillRow(skill) {
  var pct = Math.max(0, Math.min(100, parseInt(skill.progressPercentage, 10) || 0));
  return (
    "<p>" + skill.subjectName + (skill.experience ? " <small>(" + skill.experience + ")</small>" : "") + "</p>" +
    '<div class="progress">' +
      '<div class="progress-bar bg-danger progress-bar-striped progress-bar-animated bg-teal" role="progressbar" style="width: ' + pct + '%" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100">' +
        pct + "%" +
      "</div>" +
    "</div>"
  );
}

function courseCard(course) {
  return (
    '<div class="col-md-4">' +
      '<div class="instructor-card">' +
        "<p><b>" + course.courseTitle + "</b></p>" +
        "<p>" + (course.category || "") + "</p>" +
        "<p>" + (course.description || "") + "</p>" +
        '<a href="./courseDetails.php?id=' + course.id + '" class="btn">View Course</a>' +
      "</div>" +
    "</div>"
  );
}

function render(data) {
  var instructor = data.instructor;
  var imgSrc = instructor.image ? IMAGE_BASE + instructor.image : FALLBACK_IMAGE;

  var skillsHtml = data.skills.length
    ? data.skills.map(skillRow).join("")
    : "<p>No skills listed yet.</p>";

  var coursesHtml = data.courses.length
    ? data.courses.map(courseCard).join("")
    : "<p>No courses assigned yet.</p>";

  var html =
    '<div class="row">' +
      '<div class="col-md-4 leftbox">' +
        '<div class="card text-grey">' +
          '<div class="position-relative">' +
            '<img src="' + imgSrc + '" class="img-fluid" style="width: 100%" alt="Avatar" onerror="this.src=\'' + FALLBACK_IMAGE + '\'" />' +
            '<div class="position-absolute bottom-0 start-0 container text-black">' +
              "<h2>" + instructor.fullName + "</h2>" +
            "</div>" +
          "</div>" +
          '<div class="container info">' +
            "<p><i class=\"fa fa-briefcase fa-fw me-2 large text-teal\"></i>Instructor</p>" +
            "<p><i class=\"fa fa-home fa-fw me-2 large text-teal\"></i>" + (instructor.country || "") + "</p>" +
            "<hr />" +
            '<p class="large"><b><i class="fa fa-asterisk fa-fw me-2 text-teal"></i>Skills</b></p>' +
            skillsHtml +
            "<br />" +
          "</div>" +
        "</div>" +
        "<br />" +
      "</div>" +
      '<div class="col-md-8 rightbox">' +
        '<div class="card">' +
          '<h2 class="text-grey padding-16"><i class="fa fa-certificate me-2 xxlarge text-teal"></i>Courses</h2>' +
          '<div class="row">' + coursesHtml + "</div>" +
        "</div>" +
      "</div>" +
    "</div>";

  $("#instructorDetailContainer").html(html);
}

$(document).ready(function () {
  var id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    $("#instructorDetailContainer").html('<p class="text-center" style="padding: 60px 0">No instructor selected. <a href="./Instructor.html">Back to Instructors</a></p>');
    return;
  }

  $.ajax({
    url: "/backend/getInstructorDetail.php",
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (data) {
      render(data);
    },
    error: function (xhr) {
      // 400/404 land here (jQuery treats any non-2xx as an error, not success)
      var message = (xhr.responseJSON && xhr.responseJSON.message) || "Failed to load instructor.";
      $("#instructorDetailContainer").html('<p class="text-center" style="padding: 60px 0">' + message + ' <a href="./Instructor.html">Back to Instructors</a></p>');
    },
  });
});

})();
