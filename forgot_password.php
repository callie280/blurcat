<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" href="resources/BlurCut.png" type="image/png">
    <link rel="stylesheet" href="forgotpassword.css">
</head>
<body>
    <div class="navbar">
        <img src="resources/BlurCut.png" alt="Logo">
        <span class="logo-text">BlurCut</span>
        <a href="videoContentArea.php">Home</a>
        <a href="videoContentArea.php">About Us</a>
        <a href="register.php">Sign Up</a>
        <a href="#">More</a>
    </div>

    <div class="container">
        <div class="forgot-password-box">
            <h2>Forgot Password?</h2>
            <p>Enter your email address below and we will send you an OTP to your email.</p>
            <form id="forgotPasswordForm" action="#" method="post">
                <input type="email" id="email" placeholder="Email Address" required>
                <button type="submit">Send Reset Link</button>
            </form>
            <div id="otpSection" style="display:none;">
                <p>Enter the OTP sent to your email:</p>
                <input type="text" id="otp" placeholder="Enter OTP" required>
                <div class="button-group">
                    <button id="verifyOtp">Verify OTP</button>
                    <button id="resendOtp">Resend OTP</button>
                </div>
            </div>
            <a href="index.php" class="back-link">Back to Login</a>
        </div>
    </div>
    <script src="forgotpassword.js"></script>
</body>
</html>
