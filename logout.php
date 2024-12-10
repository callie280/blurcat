<?php
session_start();

// Destroy the session to log out the user
session_unset(); // Removes all session variables
session_destroy(); // Destroys the session

// Delete the "Remember Me" cookie if it exists
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, '/'); // Set the expiration date to the past to delete the cookie
}

// Redirect to the login page after logging out
header("Location: index.php");
exit(); // Ensure no further code is executed after the redirect
?>
