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

    $user_id = $_SESSION['user_id']; // User logged in

    // Fetch unread notification count
    if (isset($_POST['fetch_count'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        echo json_encode(['unread_count' => $row['unread_count']]);
        exit();
    }

    // Fetch notifications and mark them as read
    if (isset($_POST['fetch_notifications'])) {
        $stmt = $conn->prepare("SELECT id, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'message' => htmlspecialchars($row['message'], ENT_QUOTES),
                'created_at' => htmlspecialchars($row['created_at'], ENT_QUOTES)
            ];
        }

        // Mark notifications as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        echo json_encode(['notifications' => $notifications]);
        exit();
    }

    // Update notification functionality (e.g., after a comment is made)
    if (isset($_POST['update_notification'])) {
        $trigger_user_id = $_POST['trigger_user_id']; // ID of the user who triggered the notification
        $message = $_POST['message']; // Notification message
        $notification_id = $_POST['notification_id']; // Notification ID to update
        $created_at = date("Y-m-d H:i:s"); // Current timestamp
        $is_read = false; // Notification is initially not read

        // Update notification query
        $stmt = $conn->prepare("UPDATE `notifications` SET 
            `user_id` = ?, 
            `trigger_user_id` = ?, 
            `message` = ?, 
            `created_at` = ?, 
            `is_read` = ? 
        WHERE `id` = ?");

        // Bind parameters to the query (i = integer, s = string)
        $stmt->bind_param("iissii", $user_id, $trigger_user_id, $message, $created_at, $is_read, $notification_id);

        // Execute the query
        if ($stmt->execute()) {
            echo "Notification updated successfully!";
        } else {
            echo "Error updating notification: " . $stmt->error;
        }

        // Close prepared statement
        $stmt->close();
        exit();
    }

    // Securely fetch username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userName = '';
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userName = $row['username'];
    }

    $searchQuery = '';
if (isset($_POST['search'])) {
    $searchTerm = trim($_POST['search_term']);
    if (!empty($searchTerm) && is_numeric($searchTerm)) {
        $searchQuery = "WHERE u.id = ?";
    }
}

$sql = "SELECT vu.id, vu.video_title, vu.video_description, vu.video_path, vu.upload_date, vu.views, u.username, 
            COUNT(l.id) AS like_count
        FROM video_uploads vu
        JOIN users u ON vu.user_id = u.id
        LEFT JOIN video_likes l ON vu.id = l.video_id
        $searchQuery
        GROUP BY vu.id
        ORDER BY vu.upload_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($searchQuery)) {
    $stmt->bind_param("i", $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();


    // Increment view functionality
    if (isset($_POST['increment_view'])) {
        $video_id = $_POST['video_id'];

        // Increment the view count for the video
        $stmt = $conn->prepare("UPDATE video_uploads SET views = views + 1 WHERE id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();

        // Fetch the updated view count
        $stmt = $conn->prepare("SELECT views FROM video_uploads WHERE id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $updatedViews = $result->fetch_assoc()['views'];

        echo json_encode(['success' => true, 'updated_views' => $updatedViews]);
        exit();
    }

    ?>

        
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Explore Videos</title>
            <link rel="stylesheet" href="styles.css">
            <link rel="icon" href="resources/BlurCut.png" type="image/png">
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include SweetAlert CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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

                    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0, 0, 0);
        background-color: rgba(0, 0, 0, 0.4); /* Black with opacity */
        padding-top: 60px;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #notification-list {
        list-style-type: none;
        padding: 0;
    }

    #notification-list li {
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }



                /* Video Section */
                .video-container {
                    margin-top: 90px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    width: 100%;
                    max-width: 100%;
                }

                .video-box {
                    width: 100%;
                    max-width: 1000px;
                    margin-bottom: 30px;
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    transition: all 0.3s ease;
                    position: relative;
                }

                .video-box:hover {
                    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
                    transform: translateY(-5px);
                }

                .video-box iframe {
                    width: 100%;
                    height: 60vh;
                    border-radius: 8px;
                }

                .video-title {
                    font-size: 20px;
                    font-weight: bold;
                    color: #333;
                    margin-top: 15px;
                }

                .video-description {
                    font-size: 14px;
                    color: #555;
                    margin-top: 10px;
                }

                .video-upload-date {
                    font-size: 12px;
                    color: #888;
                    margin-top: 5px;
                }

                .video-user {
                    font-size: 14px;
                    color: #333;
                    margin-top: 5px;
                }

                .like-btn {
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 10px;
                }

                .like-btn:hover {
                    background-color: #45a049;
                }

                .comments-section {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f9f9f9;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                }

                .comment-box {
                    padding: 10px;
                    margin-bottom: 10px;
                    background-color: #fff;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                    border-radius: 5px;
                }

                .comment-box p {
                    margin: 5px 0;
                }

                .comment-box em {
                    font-size: 12px;
                    color: #888;
                }

                .comment-btn {
                    padding: 10px 15px;
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 10px;
                }

                .comment-btn:hover {
                    background-color: #45a049;
                }

                .report-comment-btn {
                    padding: 5px 10px;
                    background-color: #f39c12;
                    color: white;
                    border: none;
                    cursor: pointer;
                    margin-top: 10px;
                }
                    .modal {
                display: none;  /* Hidden by default */
                position: fixed;
                z-index: 1; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }

            .modal-content {
                background-color: white;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
            }
        /* Style for the modal */
        .modal {
                display: none;  /* Hidden by default */
                position: fixed;
                z-index: 1; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.6); /* Black w/ opacity */
                animation: fadeIn 0.5s ease; /* Modal fade-in animation */
            }
            .video-views {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }

            /* Modal content box */
            .modal-content {
                background-color: #fff;
                margin: 15% auto;
                padding: 30px;
                border-radius: 8px;
                width: 60%;
                max-width: 500px;
                box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
                animation: slideIn 0.5s ease-out; /* Modal slide-in animation */
            }

            /* Close button (X) */
            .close-btn {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close-btn:hover,
            .close-btn:focus {
                color: #333;
                text-decoration: none;
            }

            /* Popup message styling */
            #popup-message {
                font-size: 18px;
                color: #333;
                text-align: center;
            }

            /* Fade-in animation */
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            /* Slide-in animation */
            @keyframes slideIn {
                from {
                    transform: translateY(-100px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            /* Close the modal when clicked outside the content */
            .modal:hover {
                cursor: pointer;
            }

            /* Mobile responsiveness */
            @media (max-width: 768px) {
                .modal-content {
                    width: 80%;
                }
            }

                .report-comment-btn:hover {
                    background-color: #e67e22;
                }

                /* Mobile Optimization */
                @media (max-width: 768px) {
                    .navbar {
                        flex-direction: column;
                        align-items: center;
                        padding: 10px;
                    }

                    .video-box iframe {
                        height: 50vh;
                    }

                    .search-bar {
                        width: 220px;
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
                <div id="notification-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3>Notifications</h3>
            <ul id="notification-list"></ul>
        </div>
    </div>

                <div id="success-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <p id="popup-message">Your comment has been submitted successfully!</p>
            </div>
        </div>
                <div class="navbar-right">
                    <form method="POST">
                        <input type="text" name="search_term" class="search-bar" placeholder="Search videos..." value="<?php echo htmlspecialchars($_POST['search_term'] ?? '', ENT_QUOTES); ?>">
                        <button type="submit" name="search" class="search-btn">Search</button>
                        </form>
                        <!-- Notification Bell -->
        <div id="notification-bell">
            <img src="bell.png" alt="Notifications">
            <span id="notification-count">0</span>
        </div>

        <!-- Modal for displaying notifications -->
        <div id="notification-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Notifications</h2>
                <ul id="notification-list">
                    <!-- Notifications will be dynamically added here -->
                </ul>
                <!-- Optional: Mark as Read Button -->
                <button id="mark-as-read" style="display: none;">Mark all as read</button>
            </div>
        </div>

    <!-- Loading Spinner (for fetching notifications) -->
    <div id="loading-spinner" style="display: none;">
        <img src="loading.gif" alt="Loading..." />
    </div>





                    <a href="logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($userName, ENT_QUOTES); ?>)</a>
                </div>
            </div>
            
        
        </div>


            <!-- Video Content Section -->
        <div class="video-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="video-box" tabindex="0" data-video-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">';
                    $videoUrl = htmlspecialchars($row['video_path'], ENT_QUOTES);
                    if (strpos($videoUrl, 'autoplay=') === false) {
                        $videoUrl .= (strpos($videoUrl, '?') === false ? '?' : '&') . 'autoplay=0';
                    }
                    echo '<iframe src="' . $videoUrl . '" frameborder="0" allowfullscreen></iframe>';
                    echo '<div class="video-title">' . htmlspecialchars($row['video_title'], ENT_QUOTES) . '</div>';
                    echo '<div class="video-description">' . htmlspecialchars($row['video_description'], ENT_QUOTES) . '</div>';
                    echo '<div class="video-upload-date">Uploaded on: ' . htmlspecialchars($row['upload_date'], ENT_QUOTES) . '</div>';
                    echo '<div class="video-user">Uploaded by: ' . htmlspecialchars($row['username'], ENT_QUOTES) . '</div>';
                    echo '<div class="video-views">Views: ' . htmlspecialchars($row['views'], ENT_QUOTES) . '</div>';


                    https://drive.google.com/drive/folders/1DEN-MCERAgreUGQws8HpxTPGSe9HjU_S?usp=sharing
                    // Like button
                    echo '<form class="like-form" data-video-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">';
                    echo '<button type="submit" class="like-btn">Like <span class="like-count">' . htmlspecialchars($row['like_count'], ENT_QUOTES) . '</span></button>';
                    echo '</form>';

                    

                    // Comments Section
                    $video_id = $row['id'];
                    $commentsQuery = "SELECT c.id AS comment_id, c.comment_text, c.comment_date, u.username, c.user_id 
                                    FROM video_comments c 
                                    JOIN users u ON c.user_id = u.id
                                    WHERE c.video_id = ? 
                                    ORDER BY c.comment_date DESC";
                    $stmt = $conn->prepare($commentsQuery);
                    $stmt->bind_param("i", $video_id);
                    $stmt->execute();
                    $commentsResult = $stmt->get_result();
                    echo '<div class="comments-section">';
                    echo '<h3>Comments</h3>';
                    if ($commentsResult->num_rows > 0) {
                        while ($comment = $commentsResult->fetch_assoc()) {
                            $canDelete = ($comment['user_id'] == $user_id); // Check if the logged-in user posted the comment
                            echo '<div class="comment-box" data-comment-id="' . $comment['comment_id'] . '">';
                            echo '<p><strong>' . htmlspecialchars($comment['username'], ENT_QUOTES) . ':</strong> ' . htmlspecialchars($comment['comment_text'], ENT_QUOTES) . '</p>';
                            echo '<p><em>' . htmlspecialchars($comment['comment_date'], ENT_QUOTES) . '</em></p>';
                            if ($canDelete) {
                                echo '<button class="delete-comment-btn" data-comment-id="' . $comment['comment_id'] . '">Delete</button>';
                            }
                            // Report Button
                            echo '<button class="report-comment-btn" data-comment-id="' . $comment['comment_id'] . '">Report</button>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No comments yet.</p>';
                    }
                    echo '</div>';

                    // Comment Form
                    echo '<form class="comment-form" data-video-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">';
                    echo '<textarea name="comment_text" rows="3" placeholder="Add a comment"></textarea>';
                    echo '<button type="submit" class="comment-btn">Submit Comment</button>';
                    echo '</form>';

                    echo '</div>';
                }
            } else {
                echo '<div class="video-not-found">No videos found</div>';
            }
            ?>
        </div>

            <script>
        $(document).ready(function() {
        // Fetch notifications count periodically
        setInterval(function() {
            $.ajax({
                url: 'explore_videos.php',
                method: 'POST',
                data: { fetch_count: true },
                success: function(response) {
                    console.log(response);  // Log the response to check
                    var data = JSON.parse(response);
                    // Update notification count
                    if (data.unread_count > 0) {
                        $('#notification-bell').addClass('has-notifications');
                        $('#notification-count').text(data.unread_count);
                    } else {
                        $('#notification-bell').removeClass('has-notifications');
                        $('#notification-count').text('');
                    }
                },
                error: function() {
                    console.error('Error fetching notification count.');
                }
            });
        }, 10000); // Fetch every 10 seconds

        // Show notifications modal when bell is clicked
        $('#notification-bell').on('click', function () {
            $.ajax({
                url: 'explore_videos.php',
                method: 'POST',
                data: { fetch_notifications: true },
                success: function (response) {
                    console.log(response);  // Log the response to check
                    var data = JSON.parse(response);
                    var notifications = data.notifications;

                    // Populate the notification list
                    var notificationList = '';
                    notifications.forEach(function (notification) {
                        notificationList += '<li class="notification-item">' +
                            '<span class="notification-message">' + notification.message + '</span>' +
                            '<span class="notification-time">' + notification.created_at + '</span>' +
                            '</li>';
                    });
                    $('#notification-list').html(notificationList);

                    // Show the modal
                    $('#notification-modal').fadeIn();

                    // Optionally, mark notifications as read when viewed
                    $.ajax({
                        url: 'explore_videos.php',
                        method: 'POST',
                        data: { mark_as_read: true },
                        success: function(response) {
                            console.log(response);  // Log the response to check
                            // After marking notifications as read, reset count and update UI
                            $('#notification-count').text(0);
                        },
                        error: function () {
                            console.error('Error marking notifications as read.');
                        }
                    });
                },
                error: function () {
                    alert('Error fetching notifications. Please try again.');
                }
            });
        });

        // Close the modal when the close button is clicked
        $('.close-btn').on('click', function () {
            $('#notification-modal').fadeOut();
        });

        // Close the modal when clicking outside of it
        $(window).on('click', function (event) {
            if ($(event.target).is('#notification-modal')) {
                $('#notification-modal').fadeOut();
            }
        });

        // Optional: Handling the 'Mark as Read' action (if you want users to mark as read manually)
        $('#mark-as-read').on('click', function () {
            $.ajax({
                url: 'explore_videos.php',
                method: 'POST',
                data: { mark_as_read: true },
                success: function(response) {
                    console.log(response);  // Log the response to check
                    // After marking notifications as read, update the UI
                    $('#notification-count').text(0);
                    $('#notification-list').html('<li>No new notifications.</li>');
                    alert('All notifications marked as read!');
                },
                error: function () {
                    alert('Error marking notifications as read.');
                }
            });
        });
    });



            // Enforce autoplay=0 on all iframe URLs
            $('iframe').each(function() {
                    var iframe = $(this);
                    var src = iframe.attr('src');

                    // Remove autoplay=1 if present and replace with autoplay=0
                    if (src.includes('autoplay=1')) {
                        src = src.replace('autoplay=1', 'autoplay=0');
                    }

                    // If autoplay parameter is missing, add it as autoplay=0
                    if (!src.includes('autoplay=')) {
                        src += (src.includes('?') ? '&' : '?') + 'autoplay=0';
                    }

                    iframe.attr('src', src);
                });

                // Increment video views when the video is loaded
        $('.video-box').each(function() {
            var videoId = $(this).data('video-id');
            var iframe = $(this).find('iframe');

            // Trigger increment when iframe is loaded
            iframe.on('load', function() {
                $.ajax({
                    type: 'POST',
                    url: 'explore_videos.php',
                    data: { increment_view: true, video_id: videoId },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            // Update the view count in the DOM if needed
                            var viewElement = $(this).closest('.video-box').find('.video-views');
                            if (viewElement.length) {
                                viewElement.text('Views: ' + data.updated_views);
                            }
                        }
                    }
                });
            });
        });

                // Like functionality - Update the like count in real-time
                $('.like-form').on('submit', function(e) {
                    e.preventDefault();
                    var video_id = $(this).data('video-id');
                    var form = $(this);

                    $.ajax({
                        type: 'POST',
                        url: 'explore_videos.php',
                        data: { like_video: true, video_id: video_id },
                        success: function(response) {
                            var likeCount = response.like_count;
                            form.find('.like-count').text(likeCount);
                        }
                    });
                });

            // Function to display the success modal
        // Function to display the success modal
        function showSuccessPopup(message) {
            // Get the modal and the message elements
            var modal = document.getElementById('success-modal');
            var modalMessage = document.getElementById('popup-message');

            // Set the success message dynamically
            modalMessage.innerHTML = message || "Your comment has been submitted successfully!"; // Fallback message if undefined

            // Display the modal
            modal.style.display = "block";

            // Close the modal when the user clicks the close button (X)
            var closeBtn = document.getElementsByClassName("close-btn")[0];
            closeBtn.onclick = function() {
                modal.style.display = "none";  // Hide the modal
            }

            // Close the modal if the user clicks anywhere outside of it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";  // Hide the modal if clicked outside
                }
            }
        }

        $('.comment-form').on('submit', function(e) {
            e.preventDefault();
            var video_id = $(this).data('video-id');
            var commentText = $(this).find('textarea').val();
            var form = $(this);

            // Ensure the comment is not empty
            if (commentText.trim() === "") {
                alert("Comment cannot be empty.");
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'explore_videos.php', // Your PHP file to handle the submission
                data: { submit_comment: true, video_id: video_id, comment_text: commentText },
                success: function(response) {
                    // Log the response to check the data
                    console.log(response);

                    // Handle any error in the response
                    if (response.error) {
                        alert(response.error);  // Display the error message if there's any
                        return;
                    }

                    // Prepend the new comment
                    var newComment = response.new_comment;
                    form.find('textarea').val('');  // Clear the textarea after submission
                    form.closest('.video-box').find('.comments-section').prepend(newComment);  // Prepend the new comment

                    // Show success modal with the message from PHP
                    showSuccessPopup(response.message);  // Show the success popup
                },
                error: function() {
                    alert("Error submitting the comment. Please try again.");
                }
            });
        });

        // Function to display the success modal
        function showSuccessPopup(message) {
            // Get the modal and the message elements
            var modal = document.getElementById('success-modal');
            var modalMessage = document.getElementById('popup-message');

            // Set the success message dynamically
            modalMessage.innerHTML = message || "Your comment has been submitted successfully!"; // Fallback message if undefined

            // Display the modal
            modal.style.display = "block";

            // Close the modal when the user clicks the close button (X)
            var closeBtn = document.getElementsByClassName("close-btn")[0];
            closeBtn.onclick = function() {
                modal.style.display = "none";  // Hide the modal
            }

            // Close the modal if the user clicks anywhere outside of it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";  // Hide the modal if clicked outside
                }
            }
        }

    // Handle comment deletion
    $(document).on('click', '.delete-comment-btn', function() {
        var commentId = $(this).data('comment-id');
        var commentBox = $(this).closest('.comment-box'); // The comment box to be removed

        $.ajax({
            type: 'POST',
            url: 'explore_videos.php',
            data: { delete_comment: true, comment_id: commentId },
            success: function(response) {
                try {
                    // Parse the JSON response
                    var data = JSON.parse(response);

                    if (data.success) {
                        // Remove the comment box
                        commentBox.remove();

                        // Show success pop-up
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'The comment was deleted successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Could not delete the comment. Please try again.',
                            showConfirmButton: true
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Unexpected error. Please try again later.',
                        showConfirmButton: true
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request. Please try again.',
                    showConfirmButton: true
                });
            }
        });
    });


                // Handle comment reporting
                $(document).on('click', '.report-comment-btn', function() {
                    var commentId = $(this).data('comment-id');
                    var commentBox = $(this).closest('.comment-box');

                    var reason = prompt("Please provide a reason for reporting this comment (optional):");
                    if (!reason) {
                        reason = "No reason provided";
                    }

                    $.ajax({
                        type: 'POST',
                        url: 'explore_videos.php',
                        data: { report_comment: true, comment_id: commentId, reason: reason },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                commentBox.append('<p><strong>This comment has been reported.</strong></p>');
                            } else {
                                alert('Error reporting the comment. Please try again.');
                            }
                        },
                        error: function() {
                            alert('An error occurred while reporting the comment.');
                        }
                    });
                });

                // Real-time comment fetching - Simulate real-time updates every 10 seconds
                function refreshComments() {
                    $('.video-box').each(function() {
                        var video_id = $(this).data('video-id');
                        var commentsSection = $(this).find('.comments-section');

                        $.ajax({
                            type: 'POST',
                            url: 'explore_videos.php',
                            data: { get_comments: true, video_id: video_id },
                            success: function(response) {
                                commentsSection.html(response.updated_comments);
                            }
                        });
                    });
                }

                // Refresh comments every 10 seconds
                setInterval(refreshComments, 10000);
            </script>
        </body>
        </html>
        <?php
    if (isset($_POST['delete_comment'])) {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "autoblur-database";
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]);
            exit();
        }

        // Validate and sanitize the comment ID
        $comment_id = intval($_POST['comment_id']); // Cast to integer to prevent SQL injection

        // Check if the comment exists
        $checkStmt = $conn->prepare("SELECT id FROM video_comments WHERE id = ?");
        $checkStmt->bind_param("i", $comment_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => "Comment does not exist."]);
            $checkStmt->close();
            $conn->close();
            exit();
        }

        // Prepare the delete query
        $stmt = $conn->prepare("DELETE FROM video_comments WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $comment_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => "Failed to delete comment: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => "Failed to prepare the statement: " . $conn->error]);
        }

        // Close the database connection
        $conn->close();
        exit();
    }
        
        // Assuming you have a valid database connection in $conn

        if (isset($_POST['get_comments'])) {
            $video_id = $_POST['video_id'];

            // Fetch the latest comments for this video
            $stmt = $conn->prepare("SELECT c.comment_text, c.comment_date, u.username 
                                    FROM video_comments c 
                                    JOIN users u ON c.user_id = u.id 
                                    WHERE c.video_id = ? 
                                    ORDER BY c.comment_date DESC");
            $stmt->bind_param("i", $video_id);
            $stmt->execute();
            $commentsResult = $stmt->get_result();

            $updated_comments = '';
            if ($commentsResult->num_rows > 0) {
                while ($comment = $commentsResult->fetch_assoc()) {
                    $updated_comments .= '<div class="comment-box">
                                            <p><strong>' . htmlspecialchars($comment['username'], ENT_QUOTES) . ':</strong> ' . htmlspecialchars($comment['comment_text'], ENT_QUOTES) . '</p>
                                            <p><em>' . htmlspecialchars($comment['comment_date'], ENT_QUOTES) . '</em></p>
                                            </div>';
                }
            } else {
                $updated_comments = '<p>No comments yet.</p>';
            }

            echo json_encode(['updated_comments' => $updated_comments]);
            exit();
        }

        if (isset($_POST['like_video'])) {
            $video_id = $_POST['video_id'];

            // Check if the user has already liked the video
            $stmt = $conn->prepare("SELECT * FROM video_likes WHERE video_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $video_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Insert the like into the database
                $stmt = $conn->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $video_id, $user_id);
                $stmt->execute();

                // Fetch the owner of the video
                $stmt = $conn->prepare("SELECT user_id FROM video_uploads WHERE id = ?");
                $stmt->bind_param("i", $video_id);
                $stmt->execute();
                $ownerResult = $stmt->get_result();
                if ($ownerResult->num_rows > 0) {
                    $videoOwner = $ownerResult->fetch_assoc()['user_id'];

                    // Insert a notification for the video owner
                    $notificationMessage = "Your video received a like from {$_SESSION['username']}!";
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, trigger_user_id) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $videoOwner, $notificationMessage, $user_id);
                    $stmt->execute();
                }
            }

            // Return updated like count
            $stmt = $conn->prepare("SELECT COUNT(id) AS like_count FROM video_likes WHERE video_id = ?");
            $stmt->bind_param("i", $video_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $likeCount = $result->fetch_assoc()['like_count'];

            echo json_encode(['like_count' => $likeCount]);
            exit();
        }




        if (isset($_POST['submit_comment'])) {
            $video_id = $_POST['video_id'];
            $comment_text = trim($_POST['comment_text']);

            // Insert the comment into the database
            $stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, comment_text, comment_date) 
                                    VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $video_id, $user_id, $comment_text);
            $stmt->execute();

            // Fetch the owner of the video
            $stmt = $conn->prepare("SELECT user_id FROM video_uploads WHERE id = ?");
            $stmt->bind_param("i", $video_id);
            $stmt->execute();
            $ownerResult = $stmt->get_result();
            if ($ownerResult->num_rows > 0) {
                $videoOwner = $ownerResult->fetch_assoc()['user_id'];

                // Insert a notification for the video owner
                $notificationMessage = "{$_SESSION['username']} commented on your video!";
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, trigger_user_id) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $videoOwner, $notificationMessage, $user_id);
                $stmt->execute();
            }

            // Return the new comment
            $stmt = $conn->prepare("SELECT c.comment_text, c.comment_date, u.username, c.id AS comment_id 
                                    FROM video_comments c 
                                    JOIN users u ON c.user_id = u.id 
                                    WHERE c.video_id = ? 
                                    ORDER BY c.comment_date DESC LIMIT 1");
            $stmt->bind_param("i", $video_id);
            $stmt->execute();
            $newCommentResult = $stmt->get_result();
            $newComment = $newCommentResult->fetch_assoc();

            echo json_encode([ 
                'new_comment' => '<div class="comment-box" data-comment-id="' . htmlspecialchars($newComment['comment_id'], ENT_QUOTES) . '">
                                    <p><strong>' . htmlspecialchars($newComment['username'], ENT_QUOTES) . ':</strong> ' . htmlspecialchars($newComment['comment_text'], ENT_QUOTES) . '</p>
                                    <p><em>' . htmlspecialchars($newComment['comment_date'], ENT_QUOTES) . '</em></p>
                                </div>',
                'message' => 'Your comment has been submitted successfully!'
            ]);
            exit();
        }




        // Handle comment reporting
        if (isset($_POST['report_comment'])) {
            $comment_id = $_POST['comment_id'];
            $reason = $_POST['reason'] ?? 'No reason provided'; // Optional: user can provide a reason for the report

            // Insert the report into the database (You need to have a 'comment_reports' table)
            $stmt = $conn->prepare("INSERT INTO comment_reports (comment_id, user_id, reason, report_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $comment_id, $user_id, $reason);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Comment reported successfully.']);
            exit();
        }

        // Handle view count increment

        ?>
