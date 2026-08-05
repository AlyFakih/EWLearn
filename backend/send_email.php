<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php';

// Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                         // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';   // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                // Enable SMTP authentication
    $mail->Username   = '***REMOVED***'; // SMTP username
    $mail->Password   = '***REMOVED***'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
    $mail->Port       = 465;                 // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    // Recipients
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    // $email = isset($_POST['email']) ? $_POST['email'] : '';
    $selectedEmails = isset($_POST['selectedEmails']) ? $_POST['selectedEmails'] : [];
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    // Extract the part before '@' from the email address
    // list($username) = explode('@', $email);

    $mail->setFrom('from@example.com', 'EWlearn'); // Set sender
    //! $mail->addAddress($email, $username); // Add recipient
      // Add additional recipients
      foreach ($selectedEmails as $selectedEmail) {
        list($selectedUsername) = explode('@', $selectedEmail);
        $mail->addAddress($selectedEmail, $selectedUsername);
}
    $mail->addReplyTo('***REMOVED***', 'EWlearn'); // Set reply-to address

    // Attachments
    $mail->addAttachment('./ph-student-fill.png', "Logo-For-EWlearn.png");
    
    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = 'Name: ' . $name . '<br>';
  
    $mail->Body .= 'Subject: ' . $subject . '<br>';
    $mail->Body .= 'Message: ' . $message;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Message has been sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>
