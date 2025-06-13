<?php

session_start();
include '../includes/db_connect.php';

$stall_id = isset($_GET['stall_id']) ? intval($_GET['stall_id']) : 0;
if ($stall_id <= 0) {
  die("Invalid stall selected.");
}

// Fetch stall name
$stall_query = mysqli_query($conn, "SELECT stall_name FROM seller WHERE id = $stall_id");
$stall = mysqli_fetch_assoc($stall_query);
if (!$stall) {
  die("Stall not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($stall['stall_name']); ?> Menu</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="/Kiosk-System/client/assets/js/quantity.js" defer></script>
</head>
<body>
  <div class="top-nav">
    <div class="nav-left">
      <a href="dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </a>
      <h1><?php echo htmlspecialchars($stall['stall_name']); ?></h1>
    </div>
    <div class="cart-section">
      <a href="cart.php" class="cart-button">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
      </a>
    </div>
  </div>

  <div class="menu-section">
    <div class="category-buttons">
      <button class="category-btn active" data-category="all">All</button>
      <button class="category-btn" data-category="meal">Meals</button>
      <button class="category-btn" data-category="drinks">Drinks</button>
    </div>

    <div class="menu-grid">
      <?php
      $category = isset($_GET['category']) ? $_GET['category'] : 'all';
      $category_condition = $category !== 'all' ? "AND category = '$category'" : "";
      $items = mysqli_query($conn, "SELECT * FROM menu_items WHERE seller_id = $stall_id AND available = 1 AND is_visible = 1 $category_condition");
      while ($item = mysqli_fetch_assoc($items)) {
        $category_class = strtolower($item['category']);
        echo '<div class="menu-card">
                <div class="menu-card-image">
                  <img src="../' . $item['image'] . '" alt="' . htmlspecialchars($item['name']) . '">
                  <span class="category-tag ' . $category_class . '">' . htmlspecialchars($item['category']) . '</span>
                </div>
                <div class="menu-card-content">
                  <h3>' . htmlspecialchars($item['name']) . '</h3>
                  <p class="description">' . htmlspecialchars($item['description']) . '</p>
                  <div class="price">₱' . number_format($item['price'], 2) . '</div>
                  <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                    <div class="quantity-control">
                      <button type="button" class="qty-btn minus">-</button>
                      <input type="number" name="quantity" value="1" min="1" class="qty-input">
                      <button type="button" class="qty-btn plus">+</button>
                    </div>
                    <input type="hidden" name="item_id" value="' . $item['id'] . '">
                    <input type="hidden" name="name" value="' . htmlspecialchars($item['name']) . '">
                    <input type="hidden" name="price" value="' . $item['price'] . '">
                    <input type="hidden" name="seller_id" value="' . $stall_id . '">
                    <button type="submit" class="add-cart-btn">
                      <i class="fas fa-shopping-cart"></i>
                    </button>
                  </form>
                </div>
              </div>';
      }
      ?>
    </div>
  </div>

  <!-- Reviews Section -->
  <div class="reviews-section">
    <h2>Customer Reviews</h2>
    <div class="reviews-container">
      <?php
      $reviews_query = mysqli_query($conn, "SELECT * FROM stall_reviews WHERE stall_id = $stall_id ORDER BY created_at DESC");
      if (mysqli_num_rows($reviews_query) > 0) {
        while ($review = mysqli_fetch_assoc($reviews_query)) {
          echo '<div class="review-card">
                  <div class="review-rating">';
          for ($i = 1; $i <= 5; $i++) {
            echo '<span class="star ' . ($i <= $review['rating'] ? 'filled' : '') . '">★</span>';
          }
          echo '</div>
                <p class="review-feedback">' . htmlspecialchars($review['feedback']) . '</p>
                <p class="review-date">' . date('F j, Y', strtotime($review['created_at'])) . '</p>
              </div>';
        }
      } else {
        echo '<p class="no-reviews">No reviews yet. Be the first to review!</p>';
      }
      ?>
    </div>

    <!-- Review Form -->
    <div class="review-form">
      <h3>Leave a Review</h3>
      <form id="reviewForm" method="POST" action="submit_review.php">
        <input type="hidden" name="stall_id" value="<?php echo $stall_id; ?>">
        
        <div class="rating-container">
          <label>Rating:</label>
          <div class="star-rating">
            <?php for($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
              <label for="star<?php echo $i; ?>">★</label>
            <?php endfor; ?>
          </div>
        </div>

        <div class="feedback-container">
          <label for="feedback">Your Feedback:</label>
          <textarea name="feedback" id="feedback" rows="4" required placeholder="Share your experience..."></textarea>
        </div>

        <button type="submit" class="submit-review-btn">Submit Review</button>
      </form>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    // Category button functionality
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(button => {
      button.addEventListener('click', function() {
        const category = this.dataset.category;
        window.location.href = `menu.php?stall_id=<?php echo $stall_id; ?>&category=${category}`;
      });
    });

    // Set active button based on current category
    const currentCategory = '<?php echo $category; ?>';
    categoryButtons.forEach(button => {
      if (button.dataset.category === currentCategory) {
        button.classList.add('active');
      } else {
        button.classList.remove('active');
      }
    });

    // Handle quantity buttons
    document.querySelectorAll('.qty-btn').forEach(button => {
      button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('.qty-input');
        const currentValue = parseInt(input.value);
        if (this.classList.contains('minus')) {
          if (currentValue > 1) input.value = currentValue - 1;
        } else {
          input.value = currentValue + 1;
        }
      });
    });

    // Handle form submission
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        
        fetch('add_to_cart.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Show success message
            alert('Item added to cart successfully!');
            // Reload page to update cart count
            window.location.reload();
          } else {
            // Show error message
            alert(data.message || 'Failed to add item to cart.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while adding to cart.');
        });
      });
    });
  });
  </script>
</body>
</html>
