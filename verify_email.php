<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('db.php');

if (isset($_GET['email']) && isset($_GET['code'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $code = mysqli_real_escape_string($conn, $_GET['code']);

    // Check if the verification code is valid and not expired
    date_default_timezone_set('Asia/Manila');
    $query = "SELECT * FROM users WHERE email = '$email' AND verification_code = '$code' AND verification_expiry > NOW() LIMIT 1";
    error_log("Verification Query: $query");    
    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log("SQL Error: " . mysqli_error($conn));
        echo "<script>alert('Database error. Please try again later.'); window.history.back();</script>";
        exit();
    }

    $user = mysqli_fetch_assoc($result);

    if ($user) {
        // Update user to set is_verified to 1
        $update_query = "UPDATE users SET is_verified = 1, verification_code = NULL, verification_expiry = NULL WHERE email = '$email'";
        if (mysqli_query($conn, $update_query)) {
            echo "<script>alert('Email verified successfully! You can now log in.'); window.location.href = 'login_process.php';</script>";
        } else {
            error_log("SQL Error: " . mysqli_error($conn));
            echo "<script>alert('Failed to update verification status. Please try again.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Invalid or expired verification code. Please try again.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href = 'register_process.php';</script>";
}
?>