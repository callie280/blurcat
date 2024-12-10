<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autoblur-database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if (isset($_POST['submit'])) {
    // Increase execution time for video processing
    ini_set('max_execution_time', 1200); // 20 minutes

    // Collect and sanitize form inputs
    $videoTitle = mysqli_real_escape_string($conn, trim($_POST['videoTitle']));
    $videoDescription = mysqli_real_escape_string($conn, trim($_POST['videoDescription']));
    $blurOption = isset($_POST['blurOption']) ? $_POST['blurOption'] : 'none'; // Default to 'none' if not provided

    // Validate inputs
    if (empty($videoTitle) || empty($videoDescription)) {
        header("Location: error.php?error=Missing required fields");
        exit();
    }

    if (strlen($videoTitle) < 3 || strlen($videoDescription) < 5) {
        header("Location: error.php?error=Invalid title or description length");
        exit();
    }

    // Define upload directory and process the file
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true); // Create the directory if it doesn't exist
    }   

    $uniqueFilename = uniqid('', true) . "_" . basename($_FILES["videoFile"]["name"]);
    $videoFile = $targetDir . $uniqueFilename;
    $videoFileType = strtolower(pathinfo($videoFile, PATHINFO_EXTENSION));
    $allowedExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mpeg', 'webm'];

    // Validate file type and size
    if (!in_array($videoFileType, $allowedExtensions)) {
        header("Location: error.php?error=Invalid file type");
        exit();
    }

    $mimeType = mime_content_type($_FILES["videoFile"]["tmp_name"]);
    $allowedMimeTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/x-ms-wmv', 'video/x-flv', 'video/mpeg', 'video/webm'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        header("Location: error.php?error=Invalid MIME type");
        exit();
    }

    $maxFileSize = 100 * 1024 * 1024; // 100 MB
    if ($_FILES["videoFile"]["size"] > $maxFileSize) {
        header("Location: error.php?error=File size exceeds limit");
        exit();
    }

    // Move uploaded file
    if (move_uploaded_file($_FILES["videoFile"]["tmp_name"], $videoFile)) {
        $processedFile = $targetDir . 'processed_' . uniqid() . '.webm';

        // Escape shell arguments
        $safeVideoFile = escapeshellarg($videoFile);
        $safeProcessedFile = escapeshellarg($processedFile);
        $safeBlurOption = escapeshellarg($blurOption);

        // Retry mechanism for video processing
        $retryLimit = 3;
        $success = false;
        for ($attempt = 1; $attempt <= $retryLimit; $attempt++) {
            $pythonCommand = "python3 process_video.py $safeVideoFile $safeProcessedFile $safeBlurOption";
            $output = shell_exec($pythonCommand);

            if (file_exists($processedFile)) {
                $success = true;
                break;
            }

            error_log("Attempt $attempt: Video processing failed. Command: $pythonCommand Output: $output");
        }

        if (!$success) {
            header("Location: error.php?error=Video processing failed after $retryLimit attempts");
            exit();
        }

        // Insert video data into the database
        $uploadDate = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO video_uploads (user_id, video_title, video_description, video_path, upload_date) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $videoTitle, $videoDescription, $processedFile, $uploadDate);

        if ($stmt->execute()) {
            $_SESSION['video_uploaded'] = true;
            header("Location: videoContentArea.php");
            exit();
        } else {
            error_log("Database error: " . $stmt->error);
            header("Location: error.php?error=Database error");
            exit();
        }

        $stmt->close();
    } else {
        error_log("File upload error: " . $_FILES["videoFile"]["error"]);
        header("Location: error.php?error=File upload failed");
        exit();
    }
}

$conn->close();
?>






