<?php
require_once 'db.php'; // Include your database connection file

// Start session to access user_id
session_start();

if (isset($_POST['submit_comment'])) {
    $video_id = intval($_POST['video_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'] ?? null; // Ensure user is logged in

    // Check if user is logged in
    if (!$user_id) {
        echo json_encode(['error' => 'You must be logged in to comment.']);
        exit;
    }

    // Validate comment text
    if (empty($comment_text)) {
        echo json_encode(['error' => 'Comment text cannot be empty.']);
        exit;
    }

    // Insert the new comment into the database
    $stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, comment_text, comment_date) 
                            VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("iis", $video_id, $user_id, $comment_text);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Error adding comment.']);
        exit;
    }

    // Fetch the newly added comment
    $new_comment_id = $stmt->insert_id;
    $stmt = $conn->prepare("SELECT c.comment_text, c.comment_date, u.username, c.id AS comment_id 
                            FROM video_comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = ? 
                            LIMIT 1");
    $stmt->bind_param("i", $new_comment_id);
    $stmt->execute();
    $newCommentResult = $stmt->get_result();
    $newComment = $newCommentResult->fetch_assoc();

    if ($newComment) {
        // Return the new comment and success message as JSON
        echo json_encode([
            'new_comment' => '<div class="comment-box" data-comment-id="' . htmlspecialchars($newComment['comment_id'], ENT_QUOTES) . '">
                                <p><strong>' . htmlspecialchars($newComment['username'], ENT_QUOTES) . ':</strong> ' . htmlspecialchars($newComment['comment_text'], ENT_QUOTES) . '</p>
                                <p><em>' . htmlspecialchars($newComment['comment_date'], ENT_QUOTES) . '</em></p>
                                <button class="delete-comment-btn" data-comment-id="' . htmlspecialchars($newComment['comment_id'], ENT_QUOTES) . '">Delete</button>
                                <button class="report-comment-btn" data-comment-id="' . htmlspecialchars($newComment['comment_id'], ENT_QUOTES) . '">Report</button>
                              </div>',
            'message' => 'Your comment has been submitted successfully!'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to fetch the new comment.']);
    }
    exit();
}

echo json_encode(['error' => 'Invalid request.']);
