/* __CHART_GUARD_APPLIED__ */
/* __IIFE_WRAPPED__ */
(function () {

function monthLabel(ym) {
  // ym is "YYYY-MM" from the server
  const [, m] = ym.split('-');
  const names = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return names[parseInt(m, 10) - 1] || ym;
}

$.ajax({
  url: "../../../backend/getDashboardStats.php",
  type: "GET",
  dataType: "json",
  success: function (stats) {
    $("#statStudents").text(stats.students);
    $("#statTeachers").text(stats.teachers);
    $("#statStaff").text(stats.staff);
    $("#statTotalUsers").text(stats.totalUsers);

    // ! chart 1 : real enrollment activity, past 12 months
    var ctx = document.getElementById("lineChart");
    (function(){var __c=Chart.getChart(ctx);if(__c)__c.destroy();})();
    new Chart(ctx, {
      type: "line",
      data: {
        labels: stats.enrollmentMonths.map(monthLabel),
        datasets: [
          {
            label: "New Enrollments",
            data: stats.enrollmentCounts,
            borderWidth: 2,
            backgroundColor: ["rgba(255, 255, 255, 1)"],
            borderColor: ["rgba(255, 0, 0, 1)"],
          },
        ],
      },
      options: {
        responsive: true,
      },
    });

    // ! chart 2 : real role breakdown
    var ctxd = document.getElementById("doughnut");
    (function(){var __c=Chart.getChart(ctxd);if(__c)__c.destroy();})();
    new Chart(ctxd, {
      type: "doughnut",
      data: {
        labels: ["Administration", "Instructors", "Students", "Others"],
        datasets: [
          {
            label: "Users by Role",
            data: [
              stats.roleBreakdown.administration,
              stats.roleBreakdown.instructors,
              stats.roleBreakdown.students,
              stats.roleBreakdown.others,
            ],
            borderWidth: 2,
            backgroundColor: [
              "rgba(0, 0, 0, 1)", // Black
              "rgba(255, 0, 0, 0.5)", // Red with 70% opacity
              "rgba(255, 0, 0, 1)", // Full opacity red
              "rgba(25,20, 0, 1)",
            ],
            borderColor: ["rgba(255, 255, 255, 1)"],
          },
        ],
      },
      options: {
        responsive: true,
      },
    });
  },
  error: function () {
    $("#statStudents, #statTeachers, #statStaff, #statTotalUsers").text("N/A");
  },
});

})();
