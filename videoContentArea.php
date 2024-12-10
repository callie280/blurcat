<?php
session_start();
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

// Fetch the logged-in user's details
$userQuery = "SELECT username FROM users WHERE id = '$user_id'";
$userResult = $conn->query($userQuery);
$userName = '';
if ($userResult->num_rows > 0) {
    $userRow = $userResult->fetch_assoc();
    $userName = $userRow['username'];
}

// Handle like action
if (isset($_POST['like_video'])) {
    $video_id = $_POST['video_id'];

    // Check if user already liked the video
    $checkLikeQuery = "SELECT * FROM video_likes WHERE video_id = '$video_id' AND user_id = '$user_id'";
    $result = $conn->query($checkLikeQuery);

    if ($result->num_rows == 0) {
        // Insert like into database
        $insertLikeQuery = "INSERT INTO video_likes (video_id, user_id) VALUES ('$video_id', '$user_id')";
        $conn->query($insertLikeQuery);
    }
}

// Handle comment submission
if (isset($_POST['submit_comment'])) {
    $video_id = $_POST['video_id'];
    $comment_text = $_POST['comment_text'];

    // Insert comment into video_comments table
    $insertCommentQuery = "INSERT INTO video_comments (video_id, user_id, comment_text) VALUES ('$video_id', '$user_id', '$comment_text')";
    $conn->query($insertCommentQuery);
}

// Search functionality
$searchQuery = '';
if (isset($_POST['search'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_POST['search_term']);
    $searchQuery = "WHERE video_title LIKE '%$searchTerm%' OR video_description LIKE '%$searchTerm%'";
}

// Fetch all videos from the database, including user details and like counts
$sql = "SELECT vu.id, vu.video_title, vu.video_description, vu.video_path, vu.upload_date, u.username, 
            COUNT(l.id) AS like_count
        FROM video_uploads vu
        JOIN users u ON vu.user_id = u.id
        LEFT JOIN video_likes l ON vu.id = l.video_id
        $searchQuery
        GROUP BY vu.id
        ORDER BY vu.upload_date DESC";

$result = $conn->query($sql);

// Check if the video was uploaded successfully
$videoUploaded = isset($_GET['upload']) && $_GET['upload'] == 'success';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Content Area</title>
    <link rel="icon" href="resources/BlurCut.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap');
        
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #eaf2f8;
    overflow-x: hidden;
}

/* Navbar Styles */
.navbar {
    background-color: #080808;
    color: #fff;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.navbar-left {
    display: flex;
    align-items: center;
}

.navbar-left img {
    width: 40px;
    margin-right: 15px;
}

.navbar-left a {
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    font-size: 16px;
    margin-right: 10px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.navbar-left a:hover {
    background-color: #3498db;
    transform: scale(1.05);
}

.navbar-right {
    display: flex;
    align-items: center;
}

.search-bar {
    padding: 12px;
    width: 250px;
    border-radius: 5px;
    border: 1px solid #ddd;
    margin-right: 20px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.search-bar:focus {
    box-shadow: 0 0 10px 2px rgba(52, 152, 219, 0.8);
    border-color: #3498db;
}

.search-btn {
    padding: 12px 20px;
    background-color: #e74c3c;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.search-btn:hover {
    background-color: #c0392b;
    transform: scale(1.05);
}

.logout-btn {
    padding: 12px 20px;
    background-color: #f39c12;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-left: 15px;
    transition: background-color 0.3s ease;
}

.logout-btn:hover {
    background-color: #e67e22;
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .navbar {
        padding: 15px 20px; /* Adjust padding for medium screens */
    }

    .navbar-left a {
        font-size: 14px; /* Reduce font size for links */
        margin-right: 15px; /* Decrease margin */
    }

    .search-bar {
        width: 200px; /* Narrow the search bar */
    }

    .search-btn, .logout-btn {
        font-size: 14px; /* Decrease font size for buttons */
        padding: 10px 18px; /* Adjust button padding */
    }
}

@media (max-width: 768px) {
    .navbar {
        padding: 10px 15px; /* Reduce padding for small screens */
        flex-direction: column; /* Stack items vertically */
        align-items: flex-start; /* Align items to the left */
    }

    .navbar-left {
        margin-bottom: 10px; /* Add space below the logo and links */
    }

    .navbar-left a {
        font-size: 14px; /* Smaller font size */
        margin-right: 15px; /* Decrease margin */
    }

    .navbar-right {
        width: 100%; /* Take full width */
        justify-content: space-between; /* Spread buttons apart */
        margin-top: 10px; /* Add some spacing */
    }

    .search-bar {
        width: 180px; /* Reduce the width of search bar */
    }

    .search-btn, .logout-btn {
        font-size: 14px; /* Smaller font size */
        padding: 10px 18px; /* Adjust button padding */
    }
}

@media (max-width: 480px) {
    .navbar {
        padding: 10px 10px; /* Further reduce padding on extra small screens */
    }

    .navbar-left a {
        font-size: 12px; /* Further reduce font size */
        margin-right: 10px; /* Reduce space between links */
    }

    .search-bar {
        width: 150px; /* Narrow search bar on small devices */
    }

    .search-btn, .logout-btn {
        font-size: 12px; /* Decrease font size */
        padding: 8px 16px; /* Adjust button padding */
    }

    .logout-btn {
        margin-left: 10px; /* Decrease space between logout and search button */
    }
}

        /* Video Upload Form Styles */
        .upload-container {
            width: 90%;
            max-width: 800px;
            margin: 0 auto;
            margin-top: 80px;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .upload-container h2 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
        }

        .upload-container form {
            display: flex;
            flex-direction: column;
        }

        .upload-container label {
            font-size: 1rem;
            margin-bottom: 5px;
            color: #333;
        }

        /* Input fields and text area styling */
        .upload-container input[type="text"],
        .upload-container textarea,
        .upload-container input[type="file"],
        .upload-container button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        /* Text area */
        .upload-container textarea {
            resize: vertical;
        }

        /* Button styling */
        .upload-container button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        .upload-container button:hover {
            background-color: #0056b3;
        }

        /* Radio button and label styles */
        .radio-container {
            margin-bottom: 20px;
        }

        .radio-container input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }

        .radio-container label {
            font-size: 16px;
            color: #333;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .radio-container input[type="radio"]:checked + label {
            color: #007bff;
        }

        .radio-container input[type="radio"]:focus + label {
            font-weight: bold;
        }

        /* Custom styling for the checkbox */
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
        }

        label {
            font-size: 16px;
            color: #333;
        }

        .upload-container input[type="file"] {
            font-size: 16px;
            padding: 10px;
            border: 2px solid #007bff;
            background-color: #f0f8ff;
            color: #007bff;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media screen and (max-width: 600px) {
            .upload-container {
                padding: 10px;
            }

            .upload-container h2 {
                font-size: 1.2rem;
            }

            .upload-container button {
                font-size: 0.9rem;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-left">
            <img src="resources/BlurCut.png" alt="Logo">
            <a href="welcome.php">Home</a>
            <a href="videoContentArea.php">Video Content Area</a>
            <a href="aboutUs.php">About Us</a>
            <a href="explore_videos.php">Explore</a>
        </div>
        <div class="navbar-right">
            <form method="POST" style="display: inline-block;">
                <input type="text" name="search_term" class="search-bar" placeholder="Search videos..." value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>">
                <button type="submit" name="search" class="search-btn">Search</button>
            </form>
            <a href="logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($userName); ?>)</a>
        </div>
    </div>

    <!-- Video Upload Form -->
<div class="upload-container">
    <h2>Upload Video</h2>
    <form action="upload_video.php" method="POST" enctype="multipart/form-data">
        <label for="videoTitle">Video Title:</label>
        <input type="text" name="videoTitle" id="videoTitle" required><br><br>

        <label for="videoDescription">Video Description:</label>
        <textarea name="videoDescription" id="videoDescription" rows="4" required></textarea><br><br>

        <label for="videoFile">Choose Video File:</label>
        <input type="file" name="videoFile" id="videoFile" accept="video/*" required><br><br>

        <!-- Radio buttons for blur options -->
        <div class="radio-container">
            <input type="radio" name="blurOption" id="firstPersonBlur" value="first" required>
            <label for="firstPersonBlur">First Person Blur</label>
        </div>
          <!-- Radio buttons for blur options -->
          <div class="radio-container">
            <input type="radio" name="blurOption" id="NoBlur" value="none" required>
            <label for="Noblur">Noblur</label>
        </div>

        <div class="radio-container">
            <input type="radio" name="blurOption" id="BackgroundPersonOnlyBlur" value="BackgroundPersonOnlyBlur">
            <label for="BackgroundPersonOnlyBlur">Background Person Only Blur</label>
        </div>

        <div class="radio-container">
            <input type="radio" name="blurOption" id="secondPersonBlur" value="second">
            <label for="secondPersonBlur">Second Person Blur</label>
        </div>

        <div class="radio-container">
            <input type="radio" name="blurOption" id="thirdormorePersonBlur" value="3 or more person is blur but first or second are unblurr">
            <label for="thirdormorePersonBlur">3 or More People Blur (But First or Second Unblurred)</label>
        </div>

        <button type="submit" name="submit">Upload Video</button>
    </form>
    </div>

</body>
</html>


   
    <!-- Popup Script -->
    <script>
        <?php if ($videoUploaded): ?>
            // Show popup
            const popup = document.getElementById('successPopup');
            popup.classList.add('show');

            // Hide after 3 seconds
            setTimeout(() => {
                popup.classList.remove('show');
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
