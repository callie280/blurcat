<?php
$servername = "127.0.0.1:3306"; 
$username = "u391052341_blur"; 
$password = ""; 
$dbname = "u391052341_auto_blur"; 

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
