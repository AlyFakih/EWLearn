<?php

include "../../backend/config.php";


if (!isset($_GET['id'])) {
    die("Course ID is missing");
}


$courseID = intval($_GET['id']);


// Get course

$stmt = $conn->prepare(
    "SELECT * FROM courses WHERE id=?"
);

$stmt->bind_param(
    "i",
    $courseID
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {
    die("Course not found");
}


$course = $result->fetch_assoc();

$courseTitle = $course['courseTitle'];

$stmt->close();



// Get course details

$courseDetail = null;

$stmt = $conn->prepare(
    "SELECT * FROM coursedetails WHERE courseID=?"
);

$stmt->bind_param(
    "s",
    $courseTitle
);

$stmt->execute();


$result = $stmt->get_result();


if ($result->num_rows > 0) {

    $courseDetail = $result->fetch_assoc();

}

$stmt->close();




// Get chapters

$chapters = [];


$stmt = $conn->prepare(
    "SELECT * FROM chapterdetails WHERE courseName=? ORDER BY chapter"
);


$stmt->bind_param(
    "s",
    $courseTitle
);


$stmt->execute();


$result = $stmt->get_result();


while($row = $result->fetch_assoc()) {

    $chapters[] = $row;

}


$stmt->close();




// Get instructors

$instructors = [];


$stmt = $conn->prepare(
    "SELECT * FROM instructorcourse WHERE courseID=?"
);


$stmt->bind_param(
    "s",
    $courseTitle
);


$stmt->execute();


$result = $stmt->get_result();



while($row = $result->fetch_assoc()) {

    $instructors[] = $row;

}


$stmt->close();


?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EWlearn</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
      integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../styles/bstyle.css" />
    <link rel="stylesheet" href="../styles/courses/courseDetails.css" />
  </head>
  <body>
    <!-- Include the navigation bar -->
    <div id="navbar-container"></div>
    <script>
      $("#navbar-container").load("./navbar.html");
    </script>

    <!-- Course Details Section -->
    <div class="course-details">
      <div class="container">
        <div class="row">
          <!-- Course Information -->
          <div class="col-md-8">
            <h2>
              <i
                class="fa-solid fa-bookmark fa-lg"
                style="color: #ff0000; margin-right: 10px"
              ></i
              ><?php echo htmlspecialchars($course['courseTitle']); ?>
            </h2>
<?php if(!empty($course['image'])): ?>

<div class="course-image mb-4">

<img 
src="<?php echo htmlspecialchars($course['image']); ?>"
class="img-fluid rounded shadow"
alt="<?php echo htmlspecialchars($course['courseTitle']); ?>">

</div>

<?php endif; ?>

            <h3 style="color: black; margin: 20px 0">&rarr; Description:</h3>
            <p style="font-weight: 600">
              <?php echo nl2br(htmlspecialchars($course['description'])); ?>
            </p>

            <h3 style="color: black; margin: 20px 0">&rarr; Course Details:</h3>
            <div>
              <table>
                <thead>
                  <tr>
                    <th>Chapter</th>
                    <th>Name of Chapter</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($chapters) > 0): ?>
                    <?php foreach ($chapters as $ch): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($ch['chapter']); ?></td>
                        <td><?php echo htmlspecialchars($ch['chapterName']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="2">No chapters listed yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <ul>
              <li>
                Credits:
                <?php echo $courseDetail ? htmlspecialchars($courseDetail['credit']) : 'N/A'; ?>
              </li>
              <li>
                Category:
                <?php echo htmlspecialchars($course['category']); ?>
              </li>
              <li>
                Duration:
                <?php echo htmlspecialchars($course['duration']); ?>
              </li>
              <li>
                Seats:
                <?php echo htmlspecialchars($course['courseSeats']); ?>
              </li>
              <li>
                Price: $<?php echo htmlspecialchars($course['price']); ?>
              </li>
            </ul>

            <!-- Instructor Selection -->
            <h3 style="color: black; margin: 20px 0">
              &rarr; Select Instructor:
            </h3>

            <div class="row">
              <?php if (count($instructors) > 0): ?>
                <?php foreach ($instructors as $inst): ?>
                  <div class="col-md-3">
                    <div class="instructor-card">
                      <div class="instructor-image">
                        <img
                          src="../assets/images/member1.jpg"
                          alt="<?php echo htmlspecialchars($inst['name']); ?>"
                        />
                      </div>
                      <p><?php echo htmlspecialchars($inst['name']); ?></p>
                      <div class="bttdetials">
                        <button class="btn">Select</button>
                        <button class="btn">View</button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p>No instructors assigned to this course yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Section -->
    <div class="payment-section">
      <div class="container">
        <h2>Enroll in the Course</h2>
        <p>Complete your payment to enroll in the course.</p>

        <form id="payment-form" method="POST">
          <main class="container2">
            <section class="ui">
              <div class="container-left">
                <div id="credit-card">
                  <div class="number-container">
                    <label>Card Number</label>
                    <input
                      type="text"
                      name="card-number"
                      id="card-number"
                      maxlength="19"
                      placeholder=""
                      required
                      onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                    />
                  </div>
                  <div class="name-container">
                    <label>Holder</label>
                    <input
                      type="text"
                      name="name-text"
                      id="name-text"
                      maxlength="30"
                      placeholder=""
                      required
                      onkeypress="return (event.charCode > 64 && event.charCode < 91) || (event.charCode > 96 && event.charCode < 123) || event.key == ' '"
                    />
                  </div>
                  <div class="infos-container">
                    <div class="expiration-container">
                      <label>Valid-thru</label>
                      <input
                        type="text"
                        name="valid-thru-text"
                        id="valid-thru-text"
                        maxlength="5"
                        placeholder=""
                        required
                        onkeypress="return event.charCode >=48 && event.charCode <= 57"
                      />
                    </div>
                    <div class="cvv-container">
                      <label>CVV</label>
                      <input
                        type="text"
                        name="cvv-text"
                        id="cvv-text"
                        maxlength="4"
                        placeholder=""
                        required
                        onkeypress="return event.charCode >=48 && event.charCode <= 57"
                      />
                    </div>
                  </div>
                  <input type="submit" value="ADD" id="add" />
                </div>
              </div>
              <div class="container-right">
                <div class="card">
                  <div class="intern">
                    <img
                      class="approximation"
                      src="../assets/images/aprox.png"
                      alt="aproximation"
                    />
                    <div class="card-number">
                      <div class="number-vl"></div>
                    </div>
                    <div class="info-holder">
                      <div class="card-holder">
                        <label>Holder</label>
                        <div class="name-vl"></div>
                      </div>
                      <div class="card-infos">
                        <div class="exp">
                          <label>valid-thru</label>
                          <div class="expiration-vl"></div>
                        </div>
                        <div class="cvv">
                          <label>CVV</label>
                          <div class="cvv-vl">123</div>
                        </div>
                      </div>
                    </div>
                    <img
                      class="chip"
                      src="../assets/images/chip.png"
                      alt="chip"
                    />
                  </div>
                </div>
              </div>
            </section>
          </main>
        </form>
      </div>
    </div>

    <!-- Include the footer -->
    <div id="footer-container"></div>
    <script>
      $("#footer-container").load("Footer.html");
    </script>

    <!-- ! js Code -->
    <script src="../javascript/coursedetails.js"></script>
  </body>
</html>
