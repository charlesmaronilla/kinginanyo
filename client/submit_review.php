<?php
session_start();
include '../includes/db_connect.php';

// List of words to be censored
$censored_words = array(
    'bad', 'terrible', 'awful', 'horrible', 'worst', // Add more words as needed
    'stupid', 'dumb', 'idiot', 'fool',
    'hate', 'dislike', 'poor', 'cheap',
    'expensive', 'overpriced', 'waste',
    // Add more words that should be censored
);

// First, check if item_id column exists, if not, add it
$check_column = "SHOW COLUMNS FROM stall_reviews LIKE 'item_id'";
$column_exists = $conn->query($check_column);
if ($column_exists->num_rows == 0) {
    $add_column = "ALTER TABLE stall_reviews ADD COLUMN item_id INT AFTER stall_id";
    $conn->query($add_column);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stall_id = isset($_POST['stall_id']) ? intval($_POST['stall_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

    // Validate inputs
    if ($stall_id <= 0 || $item_id <= 0 || $rating < 1 || $rating > 5 || empty($feedback)) {
        $_SESSION['error'] = "Please provide valid rating, item selection, and feedback.";
        header("Location: menu.php?stall_id=" . $stall_id);
        exit();
    }

    // Verify that the item belongs to the stall
    $verify_query = "SELECT id FROM menu_items WHERE id = ? AND seller_id = ? AND available = 1 AND is_visible = 1";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, "ii", $item_id, $stall_id);
    mysqli_stmt_execute($verify_stmt);
    mysqli_stmt_store_result($verify_stmt);

    if (mysqli_stmt_num_rows($verify_stmt) === 0) {
        $_SESSION['error'] = "Invalid item selected.";
        header("Location: menu.php?stall_id=" . $stall_id);
        exit();
    }
    mysqli_stmt_close($verify_stmt);

    // Censor inappropriate words
    $feedback = str_ireplace($censored_words, '***', $feedback);

    // Sanitize feedback
    $feedback = mysqli_real_escape_string($conn, $feedback);

    // Insert review into database
    $query = "INSERT INTO stall_reviews (stall_id, item_id, rating, feedback) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiis", $stall_id, $item_id, $rating, $feedback);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Thank you for your review!";
    } else {
        $_SESSION['error'] = "Failed to submit review. Please try again.";
    }

    mysqli_stmt_close($stmt);
    header("Location: menu.php?stall_id=" . $stall_id);
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?> 