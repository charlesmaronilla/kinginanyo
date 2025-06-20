<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/db_connect.php';
$seller_id = $_SESSION['seller_id'];

// Get average rating
$avg_rating_query = mysqli_query($conn, "SELECT AVG(rating) as avg_rating FROM stall_reviews WHERE stall_id = $seller_id");
$avg_rating = mysqli_fetch_assoc($avg_rating_query)['avg_rating'] ?? 0;

// Get total number of reviews
$total_reviews_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM stall_reviews WHERE stall_id = $seller_id");
$total_reviews = mysqli_fetch_assoc($total_reviews_query)['total'];

// Get all reviews
$reviews_query = mysqli_query($conn, "SELECT * FROM stall_reviews WHERE stall_id = $seller_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reviews - <?php echo $_SESSION['stall_name']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #186479;
            --primary-hover: #134d5d;
            --primary-light: rgba(24, 100, 121, 0.1);
            --text-color: #2d3748;
            --border-color: #e2e8f0;
            --background-light: #f5f7fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
            line-height: 1.6;
        }

        .reviews-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
        }

        .reviews-container h2 {
            color: var(--primary-color);
            font-size: 2.2em;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .reviews-container a {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 12px 24px;
            border-radius: 12px;
            background-color: white;
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(24, 100, 121, 0.1);
        }

        .reviews-container a:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 100, 121, 0.2);
        }

        .rating-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .rating-summary:hover {
            transform: translateY(-5px);
        }

        .rating-summary h3 {
            color: var(--primary-color);
            font-size: 1.5em;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .average-rating {
            font-size: 3em;
            color: var(--primary-color);
            margin: 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-card {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .review-rating {
            margin-bottom: 15px;
        }

        .star {
            color: var(--border-color);
            font-size: 1.4em;
            margin-right: 2px;
            transition: color 0.3s ease;
        }

        .star.filled {
            color: var(--primary-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-feedback {
            margin: 15px 0;
            line-height: 1.6;
            color: var(--text-color);
            font-size: 1.1em;
        }

        .review-date {
            color: #636e72;
            font-size: 0.9em;
            margin-top: 15px;
            font-weight: 300;
        }

        .no-reviews {
            text-align: center;
            color: #636e72;
            font-style: italic;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .rating-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .rating-stat {
            background: var(--background-light);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .rating-stat span {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="reviews-container">
        <h2>Customer Reviews</h2>
        <a href="dashboard.php">← Back to Dashboard</a>

        <div class="rating-summary">
            <h3>Overall Rating</h3>
            <div class="average-rating">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    echo '<span class="star ' . ($i <= round($avg_rating) ? 'filled' : '') . '">★</span>';
                }
                ?>
            </div>
            <div class="rating-stats">
                <div class="rating-stat">Average Rating: <span><?php echo number_format($avg_rating, 1); ?></span></div>
                <div class="rating-stat">Total Reviews: <span><?php echo $total_reviews; ?></span></div>
            </div>
        </div>

        <div class="reviews-list">
            <?php
            if (mysqli_num_rows($reviews_query) > 0) {
                while ($review = mysqli_fetch_assoc($reviews_query)) {
                    echo '<div class="review-card">
                            <div class="review-rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<span class="star ' . ($i <= $review['rating'] ? 'filled' : '') . '">★</span>';
                    }
                    echo '</div>
                            <p class="review-feedback">' . htmlspecialchars($review['feedback']) . '</p>
                            <p class="review-date">' . date('F j, Y g:i A', strtotime($review['created_at'])) . '</p>
                          </div>';
                }
            } else {
                echo '<p class="no-reviews">No reviews yet.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html> 