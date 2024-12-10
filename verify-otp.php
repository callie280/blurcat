<?php
header('Content-Type: application/json'); // Set the content type to JSON
ini_set('display_errors', 1); // Enable error reporting for debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Report all errors

include('db.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the data from the POST request
    $data = json_decode(file_get_contents('php://input'), true);
    $otp = isset($data['otp']) ? $data['otp'] : '';
    $email = isset($data['email']) ? $data['email'] : '';

    // Validate the input
    if (empty($otp) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'OTP and email are required.']);
        exit();
    }

    // Check the OTP in the database
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($dbOtp, $otpExpiry);
        $stmt->fetch();

        // Check if the OTP is valid and not expired
        if ($dbOtp === $otp && strtotime($otpExpiry) > time()) {
            echo json_encode(['success' => true, 'message' => 'OTP verified! You can now reset your password.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No user found with this email.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>