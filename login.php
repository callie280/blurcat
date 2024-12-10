<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autoblur-database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: welcome.php");
        } else {
            header('Location: index.php?error=Invalid username or password');
        }
    } else {
        header('Location: index.php?error=Invalid username or password');
    }
}

$conn->close();
?>
