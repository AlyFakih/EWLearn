<?php
session_start();
include "../../backend/config.php";

$courses = [];

$result = $conn->query("SELECT * FROM courses ORDER BY id");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EWlearn</title>
    <link
      rel="stylesheet"
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
      integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="../styles/instructors/welcomeImage.css" />
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="../styles/welcomeImage.css" />
    <link rel="stylesheet" href="../styles/courses/courses.css" />
  </head>
  <body>
    <div id="navbar-container"></div>
    <script>
      $("#navbar-container").load("./navbar.html");
    </script>
    <div class="WelcomeImage">
      <div class="container-fluid bg-primary py-5 hero-header-about mb-3">
        <div class="row py-5">
          <div class="col-12 text-center">
            <h1 class="display-3 text-white animated aboutus">Welcome</h1>
            <a href="../pages/Home.html" class="h4 text-white word"> Home </a>
            <i class="far fa-circle text-white px-2"></i>
            <a href="../pages/courses.html" class="h4 text-white word">
              Courses
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="intro">
      <h2>
        <span>Courses Cards</span>
      </h2>
      <h4>
        Explore a variety of professional courses designed to help students gain knowledge, improve their skills, and prepare for successful careers.
      </h4>
    </div>
    <div class="selectdrop">
      <div class="dropdown price-dropdown">
        <div id="drop-text-price" class="dropdown-text">
          <span id="span-price">Any Price</span>
          <i id="icon-price" class="fa-solid fa-chevron-down"></i>
        </div>
        <ul id="price-list" class="dropdown-list">
          <li class="dropdown-list-price" data-value="Any Price">Any Price</li>
          <li class="dropdown-list-price" data-value="10-30">$10 - $30</li>
          <li class="dropdown-list-price" data-value="30-60">$30 - $60</li>
          <li class="dropdown-list-price" data-value="60-90">$60 - $90</li>
          <li class="dropdown-list-price" data-value="90-120">$90 - $120</li>
          <li class="dropdown-list-price" data-value="120+">$120+</li>
        </ul>
      </div>

      <div class="search-bar">
        <div class="dropdown catdropdown">
          <div id="drop-text" class="dropdown-text">
            <span id="span">Everything</span>
            <i id="icon" class="fa-solid fa-chevron-down"></i>
          </div>
          <ul id="list" class="dropdown-list">
            <li class="dropdown-list-item" data-value="Everything">
              Everything
            </li>
            <li class="dropdown-list-item" data-value="Science">Sciences</li>
            <li class="dropdown-list-item" data-value="Engineering">
              Engineering
            </li>
            <li class="dropdown-list-item" data-value="Computer Science">
              Computer Science
            </li>
            <li class="dropdown-list-item" data-value="Mathematics">
              Mathematics
            </li>
            <li class="dropdown-list-item" data-value="Business">Business</li>
          </ul>
        </div>

        <div class="search-box">
          <input
            type="text"
            id="search-input"
            placeholder="Search anything..."
          />
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
      </div>
    </div>
    <!-- Courses Start -->
    <div id="rs-courses-3" class="rs-courses-3 sec-spacer">
      <div class="container">
        <div class="row grid">
          <div class="no-cards-message" style="display: none">
            No matching cards found.
          </div>

          <?php foreach ($courses as $course): ?>
            <?php $filterClass = 'filter-' . strtolower(str_replace(' ', '-', $course['category'])); ?>
            <div class="col-lg-4 col-md-6 grid-item <?php echo htmlspecialchars($filterClass); ?>">
              <div class="course-item">
                <div class="course-img">
                  <img
                    src="<?php echo htmlspecialchars($course['image']); ?>"
                    alt="<?php echo htmlspecialchars($course['courseTitle']); ?>"
                  />
                  <span class="course-price">$<?php echo htmlspecialchars($course['price']); ?></span>
                  <div class="course-toolbar">
                    <h4 class="course-category">
                      <a href="#"><?php echo htmlspecialchars($course['category']); ?></a>
                    </h4>
                    <div class="course-date">
                      <i class="fa fa-calendar"></i>
                      <?php echo htmlspecialchars($course['calendar']); ?>
                    </div>
                    <div class="course-duration">
                      <i class="fa fa-clock-o"></i>
                      <?php echo htmlspecialchars($course['duration']); ?>
                    </div>
                  </div>
                </div>
                <div class="course-body">
                  <div class="course-desc">
                    <h4 class="course-title">
                      <a href="courseDetails.php?id=<?php echo $course['id']; ?>">
                        <?php echo htmlspecialchars($course['courseTitle']); ?>
                      </a>
                    </h4>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                  </div>
                </div>
                <div class="course-footer">
                  <div class="course-seats">
                    <i class="fa fa-users"></i>
                    <?php echo htmlspecialchars($course['courseSeats']); ?> SEATS
                  </div>
                  <div class="course-button">
                    <a href="courseDetails.php?id=<?php echo $course['id']; ?>">APPLY NOW</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>

    <!-- Include the footer -->
    <div id="footer-container"></div>
    <script>
      $("#footer-container").load("Footer.html");
    </script>
    <script src="../javascript/courses.js"></script>
  </body>
</html>
