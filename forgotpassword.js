document.addEventListener("DOMContentLoaded", function () {
    const forgotPasswordForm = document.getElementById("forgotPasswordForm");
    const otpSection = document.getElementById("otpSection");
    const emailInput = document.getElementById("email");
    const verifyOtpButton = document.getElementById("verifyOtp");
    const resendOtpButton = document.getElementById("resendOtp");
    const otpInput = document.getElementById("otp");

    // Handle sending OTP
    forgotPasswordForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const email = emailInput.value;

        fetch("send-otp.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("OTP sent to your email!");
                forgotPasswordForm.style.display = "none";
                otpSection.style.display = "block";
            } else {
                alert(data.error || "Error sending OTP. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    });

    // Handle verifying OTP
    verifyOtpButton.addEventListener("click", function () {
        const otp = otpInput.value;
        const email = emailInput.value;

        fetch("verify-otp.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ otp, email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("OTP verified! You can now reset your password.");
                window.location.href = "reset-password.php?email=" + encodeURIComponent(email);
            } else {
                alert(data.error || "Invalid OTP. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    });

    // Handle resending OTP
    resendOtpButton.addEventListener("click", function () {
        const email = emailInput.value;

        if (!email) {
            alert("Please enter your email address first.");
            return;
        }

        fetch("send-otp.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("OTP resent to your email!");
            } else {
                alert(data.error || "Error resending OTP. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    });
});