<?php
// Public endpoint by design: this is the public "Contact Us" form, so
// unlike send_email.php (the admin-only bulk messaging tool) there is no
// auth gate here. The recipient is fixed to the site's own inbox below and
// is never taken from user input, so this can't be used to relay arbitrary
// mail to third parties.
header("Content-Type: application/json; charset=UTF-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php';
require_once __DIR__ . '/load_env.php';
$env = load_env(__DIR__ . '/.env');

$smtpUsername = $env['SMTP_USERNAME'] ?? null;
$smtpPassword = $env['SMTP_PASSWORD'] ?? null;

if (!$smtpUsername || !$smtpPassword) {
    echo json_encode(['success' => false, 'message' => 'Email is not configured on the server.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Name, email and message are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('no-reply@ewlearn.com', 'EWlearn Contact Form');
    $mail->addAddress($smtpUsername);
    // Lets the site owner hit "Reply" and land directly in the visitor's inbox.
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'New contact form message from ' . $name;
    $mail->Body =
        'Name: ' . htmlspecialchars($name) . '<br>' .
        'Email: ' . htmlspecialchars($email) . '<br>' .
        'Phone: ' . htmlspecialchars($phone) . '<br><br>' .
        'Message:<br>' . nl2br(htmlspecialchars($message));

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Thanks! Your message has been sent.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Could not send your message. Please try again later.']);
}
?>
