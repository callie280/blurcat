<?php
include('db.php'); // Include your database connection

// Initialize variables
$email = '';
$message = '';
$success = false;

// Check if the email parameter is provided in the URL
if (isset($_GET['email'])) {
    $email = $_GET['email'];
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword === $confirmPassword) {
        // Hash the new password before storing it
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        if ($stmt->execute()) {
            $success = true;
            $message = "Password reset successfully!";
        } else {
            $message = "Error resetting password. Please try again.";
        }
    } else {
        $message = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="reset-password.css">
    <title>Reset Password</title>
    <link rel="stylesheet" href="reset-password.css">
</head>
<body>
    <div class=" container">
        <div class="reset-password-box">
            <h2>Reset Password</h2>
            <?php if ($success): ?>
                <p><?php echo $message; ?></p>
                <a href="index.php" class="back-link">Back to Login</a>
            <?php else: ?>
                <form id="resetPasswordForm" action="" method="post">
                    <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit">Reset Password</button>
                </form>
                <?php if ($message): ?>
                    <p><?php echo $message; ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>