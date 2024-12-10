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
        <title>About Us - BlurCut</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="icon" href="resources/BlurCut.png" type="image/png">
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

            /* About Us Section Styles */
            header {
                background-color: #1e3a8a;
                color: #fff;
                padding: 20px 0;
                text-align: center;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            h1 {
                margin: 0;
            }

            .about-container {
                padding: 30px;
                margin: 20px auto;
                width: 80%;
                max-width: 1000px;
                background-color: #ffffff; 
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .about-container h2 {
                color: #1e3a8a; 
                font-size: 1.5rem;
                margin-bottom: 15px;
                transition: color 0.3s ease;
            }

            .about-container h2:hover {
                color: #2563eb; 
            }

            .about-container p {
                color: #555;
                line-height: 1.6;
            }

            .about-container .section-title {
                margin-top: 30px;
            }

            .about-container ul {
                padding-left: 20px;
            }

            .about-container li {
                color: #555;
                margin-bottom: 10px;
            }

            .about-container a {
                color: #1e3a8a;
                text-decoration: none;
                font-weight: bold;
                transition: color 0.3s ease;
            }

            .about-container a:hover {
                color: #2563eb; 
            }

            footer {
                text-align: center;
                padding: 15px;
                background-color: #1e3a8a; 
                color: white;
                position: fixed;
                width: 100%;
                bottom: 0;
            }

            .info-container {
                border: 2px solid #1e3a8a; 
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                background-color: #f9fafb; 
                transition: background-color 0.3s ease;
            }

            .info-container:hover {
                background-color: #e3eaf1; 
            }

            /* Animation for fading and sliding the sections into view */
            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .about-container section {
                opacity: 0;
                transform: translateY(30px);
                animation: fadeInUp 1s forwards;
            }

            .about-container section:nth-child(1) {
                animation-delay: 0.2s;
            }

            .about-container section:nth-child(2) {
                animation-delay: 0.4s;
            }

            .about-container section:nth-child(3) {
                animation-delay: 0.6s;
            }

            .about-container section:nth-child(4) {
                animation-delay: 0.8s;
            }

            html {
                scroll-behavior: smooth;
            }

            @media (max-width: 768px) {
                .navbar-left {
                    flex-direction: column;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .navbar-left a {
                    margin-right: 0;
                    margin-bottom: 10px;
                }

                .navbar-right {
                    flex-direction: column;
                    align-items: center;
                }
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

    <!-- About Us Section -->
    <div class="about-container">
        <section class="info-container">
            <h2>Our Mission</h2>
            <p>At BlurCut, we strive to empower vloggers by providing them with tools that ensure their content remains private while maintaining the focus on their message. With our advanced face detection and recognition algorithms, we aim to provide seamless automation for blurring sensitive information in videos.</p>
        </section>

        <section class="info-container">
            <h2 class="section-title">What We Offer</h2>
            <ul>
                <li><strong>Automated Face Detection and Blurring:</strong> Our system automatically detects faces in your videos and applies a blurring effect, enhancing privacy without compromising the quality of your content.</li>
                <li><strong>Customizable Settings:</strong> Users can adjust settings to customize the level of blur and focus, ensuring selective emphasis on vloggers or specific areas of the video.</li>
                <li><strong>User-Friendly Interface:</strong> Our intuitive interface simplifies the video editing process, allowing you to focus on creating great content while we handle the technical details.</li>
            </ul>
        </section>

        <section class="info-container">
            <h2 class="section-title">Why Choose BlurCut?</h2>
            <p>We understand the importance of privacy in the digital age. Our system is designed to protect the identities of individuals in videos while allowing content creators to share their stories without hesitation. Join us in creating a safer online environment for everyone.</p>
        </section>

        <section class="info-container">
            <h2 class="section-title">Contact Us</h2>
            <p>If you have any questions or would like to know more about our services, feel free to <a href="contact.php">contact us</a>.</p>
        </section>
    </div>

    </body>
    </html>
