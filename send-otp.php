<?php
session_start();
require_once 'db.php'; // Include your database connection file

header('Content-Type: application/json');

try {
    // Parse JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email is required.']);
        exit;
    }

    // Check email in the database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'No account found with that email address.']);
        exit;
    }

    // Generate and save OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;

    // Simulate sending OTP
    $subject = "Your OTP Code";
    $message = "Your OTP code is: $otp";
    $headers = "From: no-reply@yourdomain.com";

    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send OTP.']);
    }
} catch (Exception $e) {
    error_log("Error in send-otp.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred.']);
}
?>
