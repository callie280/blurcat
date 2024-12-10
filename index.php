<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link rel="icon" href="resources/BlurCut.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-image: url('resources/pixel.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding-left: 40px;
        }
        .logincontainer {
            width: 100%;
            max-width: 600px;
            padding: 30px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .logincontainer h2 {
            text-align: center;
            font-size: 30px;
            margin-bottom: 25px;
            color: #333;
        }
        .logincontainer input[type="text"],
        .logincontainer input[type="password"],
        .logincontainer button {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            font-size: 18px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .logincontainer input[type="text"],
        .logincontainer input[type="password"] {
            background-color: #fff;
            color: #333;
        }
        .logincontainer button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin-top: 15px;
        }
        .logincontainer button:hover {
            background-color: #0056b3;
        }
        .logincontainer .form-switch {
            text-align: center;
            margin-top: 15px;
        }
        .logincontainer .form-switch a {
            color: #007bff;
            text-decoration: none;
            font-size: 18px;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            display: none;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .navbar {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background-color: #333;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 18px;
        }
        .navbar img {
            height: 40px;
            margin-right: 10px;
        }
        .navbar .logo-text {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-right: 20px;
        }
        .logincontainer label {
            margin-bottom: 20px;
        }
    </style>
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

    <div class="login-wrapper">
        <div class="logincontainer">
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" required><br>
                <input type="password" id="password" name="password" placeholder="Password" required><br>
            
                <!-- Remember Me checkbox -->
                <label for="rememberMe">
                    <input type="checkbox" name="remember_me" id="rememberMe"> Remember Me
                </label><br>

                <div id="errorMessage" class="error-message"></div>
                <button type="submit">Login</button>
            </form>
            
            <div class="form-switch">
                <p>Don't have an account? <a href="register.php">Register</a> or  <a href="forgot_password.php">Forgot Password</a></p>
 
            </div>
                
        </div>
    </div>

    <script>
        const showPasswordCheckbox = document.getElementById('showPassword');
        const passwordInput = document.getElementById('password');

        showPasswordCheckbox.addEventListener('change', function () {
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const errorMessage = urlParams.get('error');

        if (errorMessage) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = errorMessage;
            errorDiv.style.display = 'block';
        }
    </script>
</body>
</html>
