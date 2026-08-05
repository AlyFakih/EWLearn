// ! chart 1 :

const ctx = document.getElementById("lineChart");

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
const ctxd = document.getElementById("doughnut");

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
