<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'], $_POST['reason'])) {
    $comment_id = intval($_POST['comment_id']);
    $reason = trim($_POST['reason']);
    $user_id = $_SESSION['user_id']; // Assuming session contains `user_id`

    $insertQuery = "INSERT INTO reported_comments (comment_id, user_id, reason, report_date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $comment_id, $user_id, $reason);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Comment reported successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error reporting the comment.']);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
