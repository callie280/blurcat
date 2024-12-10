<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autoblur-database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();


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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="icon" href="resources/BlurCut.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <style>
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
        /* Main content styles */
        .welcome-container {
            text-align: center;
            margin: 50px auto;
            padding: 45px;
            background-color: #fff;
            max-width: 80%;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .welcome-container h1 {
            color: #0056b3;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .welcome-container p {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #0056b3;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #003d80;
        }

        /* Feature Section */
        .feature-container {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
            padding: 20px;
        }

        .feature-box {
            width: 30%;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-box h3 {
            color: #0056b3;
            margin-bottom: 10px;
        }

        .feature-box p {
            color: #555;
        }

        .feature-box:hover {
            box-shadow: 0 0 10px rgba(0, 0, 255, 0.7), 0 0 20px rgba(0, 0, 255, 0.6), 0 0 30px rgba(0, 0, 255, 0.5);
            border: 2px solid #0056b3;
        }

        .feature-box img {
            width: 100px;
            height: auto;
        }

        @media (max-width: 768px) {
            .feature-container {
                flex-direction: column;
                align-items: center;
            }

            .feature-box {
                width: 80%;
                margin-bottom: 20px;
            }

            .navbar-left a {
                margin-right: 10px;
            }
        }

        /* Modal Style */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            position: relative;
            box-sizing: border-box;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Slider and Button Styles */
        .slider-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin-bottom: 20px;
        }

        .slider-container label {
            font-size: 1em;
            color: #333;
            margin-bottom: 10px;
            width: 100%;
            text-align: left;
        }

        .slider-wrapper {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .slider-wrapper input[type="range"] {
            flex: 1;
            appearance: none;
            height: 8px;
            background: #ddd;
            border-radius: 5px;
            outline: none;
            transition: background 0.3s;
            margin-right: 10px;
        }

        .slider-wrapper input[type="range"]::-webkit-slider-thumb {
            appearance: none;
            width: 15px;
            height: 15px;
            background: #4379c0;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }

        .slider-wrapper input[type="range"]::-webkit-slider-thumb:hover {
            background: #3672f4;
        }

        .value-display {
            font-size: 1em;
            color: #555;
            text-align: right;
            width: 50px;
        }

        .save-btn {
            background-color: #4379c0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
            width: 100%;
            transition: background-color 0.3s ease;
            display: block;
            text-align: center;
        }

        .save-btn:hover {
            background-color: #3672f4;
        }

        /* Navbar extra styles */
        .navbar img {
            width: 50px;
            margin-right: 10px;
        }

        .navbar .logo-text {
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin-right: 20px;
        }
    </style>
</head>
<body>


    <!-- Navbar Section -->
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


<!-- Welcome Section -->
<div class="welcome-container">
    <h2>Hi, <?php echo htmlspecialchars($user['username']); ?>!</h2>
    <h1>Welcome to BlurCut Real-Time Recording!</h1>
    <p>Auto-blur unwanted faces while recording live.</p>
    <a href="video_recording.php" class="btn-primary">Start Recording</a>
 </div>

<!-- Feature Section -->
<div class="feature-container">
    <div class="feature-box">
        <img src="resources/camera.png" alt="Detect Faces">
        <h1>Detect Faces</h1>
        <h3>Our AI-powered tool detects faces in real-time during your live streams.</h3>
    </div>
    <div class="feature-box">
        <img src="resources/blurface.png" alt="Blur Faces">
        <h1>Blur Faces</h1>
        <h3>Automatically blur unwanted faces to protect privacy.</h3>
    </div>
    <div class="feature-box">
        <img src="resources/stream.png" alt="Live Stream">
        <h1>Live Stream</h1>
        <h3>Continue live streaming while protecting the privacy of others.</h3>
    </div>
</div>

<!-- Modal for Configuration -->
<div id="configModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Auto-Blurring Configuration</h2>
        
        <!-- Sliders for Configurations -->
        <div class="slider-container">
            <label for="bg-blur">Background Blur Amount</label>
            <div class="slider-wrapper">
                <input type="range" id="bg-blur" name="bg-blur" min="0" max="100" value="50">
                <span class="value-display" id="bg-blur-value">50</span>
            </div>
            <p>Adjust the intensity of the background blur.</p>
        </div>

        <div class="slider-container">
            <label for="face-blur">Face Blur Amount</label>
            <div class="slider-wrapper">
                <input type="range" id="face-blur" name="face-blur" min="0" max="100" value="20">
                <span class="value-display" id="face-blur-value">20</span>
            </div>
            <p>Adjust the intensity of the face blur.</p>
        </div>

        <!-- Save Button -->
        <button id="buttonsave" class="save-btn">Save Settings</button>
    </div>
</div>

</body>
</html>


    <script>
        var modal = document.getElementById("configModal");
        var btn = document.getElementById("openModalBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.classList.remove("fade-out");
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.classList.add("fade-out");
            setTimeout(() => {
                modal.style.display = "none";
                modal.classList.remove("fade-out");
            }, 300);
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.classList.add("fade-out");
                setTimeout(() => {
                    modal.style.display = "none";
                    modal.classList.remove("fade-out");
                }, 300);
            }
        }

        document.getElementById("bg-blur").oninput = function() {
            document.getElementById("bg-blur-value").textContent = this.value;
        }

        document.getElementById("face-blur").oninput = function() {
            document.getElementById("face-blur-value").textContent = this.value;
        }
        document.getElementById('navbarToggle').addEventListener('click', () => {
            document.querySelector('.navbar-right').classList.toggle('active');
        });

        document.getElementById("saveSettingsBtn").onclick = function() {
            var bgBlur = document.getElementById("bg-blur").value;
            var faceBlur = document.getElementById("face-blur").value;
            console.log("Saved settings:", bgBlur, faceBlur);
            modal.classList.add("fade-out");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
    </script>
</body>
</html>
