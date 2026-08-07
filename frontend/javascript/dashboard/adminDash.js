/* __CHART_GUARD_APPLIED__ */
/* __IIFE_WRAPPED__ */
(function () {
// ! chart 1 :

var ctx = document.getElementById("lineChart");

(function(){var __c=Chart.getChart(ctx);if(__c)__c.destroy();})();
new Chart(ctx, {
  type: "line",
  data: {
    labels: [
      "Jav",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ],
    datasets: [
      {
        label: "Earnings in $",
        data: [
          2000, 4000, 6000, 9000, 12000, 15000, 10000, 11000, 9000, 7000, 10000,
        ],
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

// ! chart 2 :
var ctxd = document.getElementById("doughnut");

(function(){var __c=Chart.getChart(ctxd);if(__c)__c.destroy();})();
new Chart(ctxd, {
  type: "doughnut",
  data: {
    labels: ["Administration", "Instructors", "Students", "Others"],
    datasets: [
      {
        label: "Employees/Users",
        data: [10, 20, 50, 12],
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

})();
