<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="icon" href="resources/BlurCut.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <style>
                body {
  background-image: url('resources/nightcity.jpg'); 
  background-size: cover; 
  background-position: center; 
  background-repeat: no-repeat; 
  height: 100vh; 
  margin: 0;
  overflow:hidden;
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
            margin-right:20px;
        }
        .center-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 60px);
            padding: 20px;
            background-color: #f1f1f1;
        }
        .register-container {
            width: 80%;
            max-width: 500px;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .register-container h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        .register-container input {
            width: 92%;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .register-container button {
            width: 98%;
            padding: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .register-container button:hover {
            background-color: #0056b3;
        }
        .form-switch {
            text-align: center;
            margin-top: 15px;
        }
        .form-switch a {
            color: #007bff;
            text-decoration: none;
        }
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 16px;
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 16px;
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

    </style>
</head>
<body>
          <div class="navbar">
          <img src="resources/BlurCut.png" alt="Logo">
        <span class="logo-text">BlurCut</span>
      <a href="videoContentArea.php">Home</a>
        <a href="videoContentArea.php">About Us</a>
        <a href="index.php">Login</a>
        <a href="#">More</a>
    </div>

    <div class="center-wrapper">
        <div class="register-container">
            <h2>Register</h2>
            <form id="registerForm">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
            <div class="form-switch">
                <p>Already have an account? <a href="index.php">Login</a></p>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('registerForm');
        const registerContainer = document.querySelector('.register-container');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            fetch('register-db.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Remove any existing messages
                const existingMessage = document.querySelector('.register-container p.message');
                if (existingMessage) {
                    existingMessage.remove();
                }

                // Create and style the success or error message
                const message = document.createElement('p');
                message.textContent = data.message;
                message.className = data.success ? 'success-message' : 'error-message';
                message.classList.add('message');
                registerContainer.appendChild(message);

                if (data.success) {
                    form.reset(); // Clear the form on success
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
