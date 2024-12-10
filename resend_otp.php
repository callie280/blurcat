<?php
session_start();
require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include('db.php'); // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email from the POST request
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Generate a new OTP
    $verification_code = random_int(100000, 999999);
    $verification_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // OTP valid for 1 hour

    // Update the OTP in the database for the user
    $update_query = "UPDATE users SET verification_code = '$verification_code', verification_expiry = '$verification_expiry' WHERE email = '$email'";
    if (mysqli_query($conn, $update_query)) {
        // Send the OTP email
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'lesterlopez511@gmail.com'; // Your email
            $mail->Password = 'pres fxeh etsk qutg'; // Your email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email content
            $mail->setFrom('lesterlopez511@gmail.com', 'Mailer');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your New OTP Code';
            $mail->Body    = "Your new OTP code is <b>$verification_code</b>. It is valid for 1 hour.";
            $mail->AltBody = "Your new OTP code is $verification_code. It is valid for 1 hour.";
            $mail->send();

            echo "success"; // Send a success response
        } catch (Exception $e) {
            echo "Error sending email: {$mail->ErrorInfo}"; // Handle email sending error
        }
    } else {
        echo "Error updating OTP in database: " . mysqli_error($conn); // Handle database error
    }
} else {
    echo "Invalid request method."; // Handle invalid request
}
?>