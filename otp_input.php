<?php
session_start();
date_default_timezone_set(timezoneId: 'Asia/Manila');
if (!isset($_SESSION['email'])) {
    header("Location: register-dp.php"); // Redirect to registration if email is not set
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verification</title>
    <link rel="stylesheet" href="otp_input.css">
</head>
<body>
    <div class="container">
        <h2>Verification</h2>
        <p>Enter the OTP sent to your email address below to verify your account</p>
        <form action="verify_email.php" method="GET">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
            <input type="text" id="otp" name="code" placeholder="Enter OTP" required />
            <button type="submit" class="verify-btn">Verify OTP</button>
            <button type="button" class="resend-btn" id="resendButton" onclick="resendOTP()">Resend OTP</button>
        </form>
        <a href="index.php">Back to Login</a>
    </div>

    <script>
        let countdownTime = 30; // seconds
        const resendButton = document.getElementById("resendButton");

        function startCountdown() {
            resendButton.disabled = true;
            resendButton.textContent = `Resend OTP (${countdownTime}s)`;
            const countdownInterval = setInterval(() => {
                countdownTime--;
                resendButton.textContent = `Resend OTP (${countdownTime}s)`;
                if (countdownTime <= 0) {
                    clearInterval(countdownInterval);
                    resendButton.disabled = false;
                    resendButton.textContent = "Resend OTP";
                    countdownTime = 30; // Reset countdown time for next use
                }
            }, 1000);
        }

        function resendOTP() {
            // Start countdown timer
            startCountdown();

            // Send AJAX request to resend OTP
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "resend_otp.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert("A new OTP has been sent to your email.");
                }
            };
            xhr.send("email=<?php echo htmlspecialchars($_SESSION['email']); ?>");
        }
    </script>
</body>
</html>