<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autoblur-database";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch the logged-in user's username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userName);
$stmt->fetch();
$stmt->close();
// Execute Python script for video processing with the selected blur option
$pythonCommand = "python3 start _video.py";
$output = shell_exec($pythonCommand);



// Handle like action
if (isset($_POST['like_video'])) {
    $video_id = $_POST['video_id'];

    // Check if user already liked the video
    $stmt = $conn->prepare("SELECT * FROM video_likes WHERE video_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert like into the database
        $stmt = $conn->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $video_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle comment submission
if (isset($_POST['submit_comment'])) {
    $video_id = $_POST['video_id'];
    $comment_text = $_POST['comment_text'];

    // Insert comment into video_comments table
    $stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $video_id, $user_id, $comment_text);
    $stmt->execute();
    $stmt->close();
}

// Search functionality
$searchQuery = '';
if (isset($_POST['search'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_POST['search_term']);
    $searchQuery = "WHERE video_title LIKE ? OR video_description LIKE ?";
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

$stmt = $conn->prepare($sql);
if ($searchQuery) {
    $searchTerm = "%$searchTerm%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Recorder with Face Blurring</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
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
    margin-right: 20px;
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

      /* Video Recorder Styles */
.video-recorder-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 90vh;
    text-align: center;
}

.container {
    background: #000000;
    border-radius: 20px;
    padding: 20px;
    max-width: 700px;
    width: 90%;
    color: white;
    position: relative; /* Important for positioning exit button */
}

video {
    width: 100%;
    max-width: 600px;
    height: auto;
    border-radius: 12px;
    margin-bottom: 20px;
}

.controls {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

button {
    background: #ffa500;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Button hover effect */
button:hover {
    background-color: #ff8c00; /* Darker orange on hover */
}

button:disabled {
    background-color: #ff8c00; /* Light gray for disabled buttons */
    cursor: not-allowed;
}

.timer {
    margin-top: 15px;
    font-size: 16px;
}

/* Blur Slider Styles */
.blur-control {
    margin-top: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.blur-control input {
    width: 100%;
    max-width: 300px;
}

/* Exit Button Styles */
.exit-btn {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    position: absolute;
    top: 15px;  /* Position near the top of the container */
    right: 15px; /* Position near the right of the container */
    transition: transform 0.2s;
}

.exit-btn:hover {
    transform: scale(1.1);
}

/* Responsive Design */
@media (max-width: 600px) {
    .container {
        width: 95%;
    }

    video {
        max-width: 100%;
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
                <input type="text" name="search_term" class="search-bar" placeholder="Search videos...">
                <button type="submit" name="search" class="search-btn">Search</button>
            </form>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Video Recorder -->
    <div class="video-recorder-wrapper">
        <div class="container">
            <h1>Video Recorder</h1>
            <video id="videoElement" autoplay></video>
            <canvas id="overlayCanvas"></canvas> <!-- Canvas for face blurring -->
            <div class="controls">
                <button id="startButton"  class="stop-button" value="recording" >Start Recording</button>
                <button id="pauseButton" disabled>Pause Recording</button>
                <button id="resumeButton" disabled>Resume Recording</button>
                <button id="stopButton"  class="stop-button" disabled>Stop Recording</button>
                <button id="switchToBackCamera">Switch to Back Camera</button>
                <button id="enableBlurButton" disabled>Enable Blur</button>
                <button id="disableBlurButton" disabled>Disable Blur</button>

                <a id="downloadButton" href="#" download="recorded-video.webm" disabled>
                    <button>Download Video</button>
                </a>
            </div>
            <div class="timer">
                <span id="timerDisplay">00:00:00</span>
            </div>
            <div class="blur-control">
                <label for="blurRange" style="color: white; margin-right: 10px;">Adjust Blur:</label>
                <input type="range" id="blurRange" min="0" max="30" value="0">
            </div>


            <!-- Exit Button -->
            <button class="exit-btn" onclick="exitRecorder()">
                <i class="fas fa-times"></i> 
            </button>
        </div>
    </div>

    <script>
   // DOM Elements
const startButton = document.getElementById('startButton');
const pauseButton = document.getElementById('pauseButton');
const resumeButton = document.getElementById('resumeButton');
const stopButton = document.getElementById('stopButton');
const downloadButton = document.getElementById('downloadButton');
const enableBlurButton = document.getElementById('enableBlurButton');
const disableBlurButton = document.getElementById('disableBlurButton');
const backCamButton = document.getElementById('switchToBackCamera');
const exitButton = document.getElementById('exitButton'); // New exit button
const videoElement = document.getElementById('videoElement');
const overlayCanvas = document.getElementById('overlayCanvas');
const ctx = overlayCanvas.getContext('2d');
const blurRange = document.getElementById('blurRange');

// State Variables
let isRecording = false;
let isPaused = false;
let mediaRecorder;
let recordedChunks = [];
let mediaStream;
let mediaConstraints = { video: { width: 640, height: 480, frameRate: 30 }, audio: true };
let timer;
let seconds = 0, minutes = 0, hours = 0;

// Timer Functions
function resetTimer() {
    clearInterval(timer);
    seconds = minutes = hours = 0;
    updateTimerDisplay();
}

function startTimer() {
    timer = setInterval(() => {
        seconds++;
        if (seconds >= 60) { seconds = 0; minutes++; }
        if (minutes >= 60) { minutes = 0; hours++; }
        updateTimerDisplay();
    }, 1000);
}

function updateTimerDisplay() {
    document.getElementById('timerDisplay').textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Start Recording
async function startRecording() {
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia(mediaConstraints);
        videoElement.srcObject = mediaStream;
        overlayCanvas.width = videoElement.videoWidth;
        overlayCanvas.height = videoElement.videoHeight;

        recordedChunks = [];
        mediaRecorder = new MediaRecorder(mediaStream, { mimeType: 'video/webm;codecs=vp8,opus' });
        mediaRecorder.ondataavailable = event => { 
            if (event.data.size > 0) recordedChunks.push(event.data); 
        };
        mediaRecorder.onstop = () => {
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            const videoURL = URL.createObjectURL(blob);
            downloadButton.href = videoURL;
            downloadButton.disabled = false;  // Enable download button after stop
            resumeButton.disabled = true; // Disable resume button after stopping
        };

        mediaRecorder.start();
        toggleRecordingState(true);
        resetTimer();
        startTimer();

        // Disable download button while recording
        downloadButton.disabled = true; // Make sure it stays disabled while recording
        resumeButton.disabled = true; // Initially disable resume button

    } catch (err) {
        alert(`Error: ${err.message}`);
    }
}

// Stop Recording
function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    clearInterval(timer);
    toggleRecordingState(false);
    resetTimer();

    // After recording stops, display a notification and enable the download button
    alert('Your previous video is available for download.');

    // Here, we enable the download button only when recording stops
    downloadButton.disabled = false;  // Enable the download button only after stopping
    resumeButton.disabled = true; // Ensure resume is still disabled after stopping
}




// Pause Recording
function pauseRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause();
        clearInterval(timer); // Stop timer
        isPaused = true;
        toggleRecordingState(true);
        resumeButton.disabled = false; // Enable resume button when paused
    }
}

// Resume Recording
function resumeRecording() {
    if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume();
        startTimer(); // Restart timer
        isPaused = false;
        toggleRecordingState(true);
    }
}



// Exit Recording and Stop Timer
function exitRecorder() {
    // Stop the media stream if it's active
    if (videoElement.srcObject) {
        const stream = videoElement.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
        videoElement.srcObject = null;
    }

    // Stop the timer if it's running
    clearInterval(timer);
    resetTimer(); // Reset timer to 00:00:00

    // Reset UI state to reflect that recording has stopped
    toggleRecordingState(false);
}

// Toggle UI State (Start, Pause, Resume, Stop)
function toggleRecordingState(isRecordingState) {
    isRecording = isRecordingState;
    startButton.disabled = isRecordingState;
    pauseButton.disabled = !isRecordingState || isPaused;
    resumeButton.disabled = !isPaused;
    stopButton.disabled = !isRecordingState;
    enableBlurButton.disabled = !isRecordingState;
    disableBlurButton.disabled = !isRecordingState;
}

// Enable Auto Blur
function enableAutoBlur() {
    videoElement.style.filter = `blur(${blurRange.value}px)`;
    enableBlurButton.disabled = true;
    disableBlurButton.disabled = false;
}

// Disable Auto Blur
function disableAutoBlur() {
    videoElement.style.filter = 'none';
    enableBlurButton.disabled = false;
    disableBlurButton.disabled = true;
}

// Update the blur dynamically based on the slider value
blurRange.addEventListener('input', function () {
    if (enableBlurButton.disabled) {
        videoElement.style.filter = `blur(${blurRange.value}px)`;
    }
});

// Switch to Back Camera
async function switchToBackCamera() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');

        if (videoDevices.length === 0) {
            alert('No video input devices found!');
            return;
        }

        // Look for a camera with "back" or "rear" in its label
        const backCamera = videoDevices.find(device =>
            device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('rear')
        );

        if (!backCamera) {
            alert('Back camera not found!');
            return;
        }

        // Use the back camera's deviceId to access it
        const mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: backCamera.deviceId } },
            audio: false // Optional, include audio if needed
        });

        // Assign the stream to the video element
        if (videoElement.srcObject) {
            videoElement.srcObject.getTracks().forEach(track => track.stop()); // Stop previous streams
        }
        videoElement.srcObject = mediaStream;

        alert(`Switched to: ${backCamera.label}`);
    } catch (err) {
        console.error('Error switching to back camera:', err);
        alert(`Error accessing the back camera: ${err.message}`);
    }
}

// Add event listeners for buttons
startButton.addEventListener('click', startRecording);
pauseButton.addEventListener('click', pauseRecording);
resumeButton.addEventListener('click', resumeRecording);
stopButton.addEventListener('click', stopRecording);
enableBlurButton.addEventListener('click', enableAutoBlur);
disableBlurButton.addEventListener('click', disableAutoBlur);
backCamButton.addEventListener('click', switchToBackCamera);
exitButton.addEventListener('click', exitRecorder); // Event listener for the exit button

</script>
