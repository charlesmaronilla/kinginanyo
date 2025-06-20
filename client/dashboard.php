<?php
session_start();
include '../includes/db_connect.php';

if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
  $item_id = (int)$_POST['item_id'];
  $name = trim($_POST['name']);
  $price = (float)$_POST['price'];
  $quantity = (int)$_POST['quantity'];
  $seller_id = (int)$_POST['seller_id'];

  if ($quantity > 0) {
    if (isset($_SESSION['cart'][$item_id])) {
      $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
      $_SESSION['cart'][$item_id] = [
        'name' => $name,
        'price' => $price,
        'quantity' => $quantity,
        'seller_id' => $seller_id
      ];
    }

    $_SESSION['add_to_cart_message'] = [
      'type' => 'success',
      'message' => 'Successfully added ' . $quantity . ' ' . $name . ' to cart!',
      'item_name' => $name,
      'quantity' => $quantity
    ];

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
  }
}

$cart_count = 0;
if (!empty($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $item) {
    if (is_array($item) && isset($item['quantity'])) {
      $cart_count += $item['quantity'];
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EZ-Order | <?php echo $is_logged_in ? 'Client Dashboard' : 'Guest Dashboard'; ?></title>
  <link rel="stylesheet" href="/Kiosk-System/client/assets/css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .guest-notice {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }

    .guest-notice i {
      margin-right: 8px;
    }

    .guest-notice a {
      color: #186479;
      font-weight: bold;
      text-decoration: none;
    }

    .guest-notice a:hover {
      text-decoration: underline;
    }

    .user-info {
      background: #e3f2fd;
      border: 1px solid #bbdefb;
      color: #1565c0;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }

    .user-info i {
      margin-right: 8px;
    }
  </style>
</head>

<body>
  <header class="admin-header">
    <div class="header-brand">
      <img src="../uploads/logo1.png" alt="EZ-Order" class="header-logo">
    </div>
  </header>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logoo-wrapper">
      <img src="../uploads/logo.png" alt="EZ-Order Logo" class="sidebar-logo">
      <div class="logoo">EZ-ORDER</div>
      <div class="divider"></div>
      <div class="tagline">"easy orders, zero hassle"</div>
    </div>

    <div class="menu-title"></div>
    <h2>🍽 Stalls</h2>
    <?php
    $stall_query = mysqli_query($conn, "SELECT id, stall_name FROM seller LIMIT 2");
    if ($stall_query && mysqli_num_rows($stall_query) > 0) {
      while ($stall = mysqli_fetch_assoc($stall_query)) {
        echo '<a href="menu.php?stall_id=' . (int)$stall['id'] . '" class="menu-item">';
        echo '    <i class="fas fa-utensils"></i>';
        echo '    <span>' . htmlspecialchars($stall['stall_name']) . '</span>';
        echo '</a>';
      }
    } else {
      echo '<div class="menu-item"><i class="fas fa-info-circle"></i> No stalls available</div>';
    }
    ?>
    
    <?php if ($is_logged_in): ?>
    <div class="menu-title"></div>
    <h2>📋 Orders</h2>
    <a href="order_history.php" class="menu-item">
      <i class="fas fa-history"></i>
      <span>Order History</span>
    </a>
    <?php endif; ?>
  </div>

  <div class="main-content">
    <div class="top-nav">
      <div class="user-section">
        <a href="cart.php" class="cart-button">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-count"><?= $cart_count ?></span>
        </a>
        <?php if ($is_logged_in): ?>
        <a href="logout.php" class="logout-button" style="margin-left: 15px; padding: 8px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <?php else: ?>
        <a href="login.php" class="login-button" style="margin-left: 15px; padding: 8px 15px; background: #186479; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;">
          <i class="fas fa-sign-in-alt"></i> Login
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="content-container">
      <?php if (!$is_logged_in): ?>
      <div class="guest-notice">
        <i class="fas fa-info-circle"></i>
        <strong>Guest Mode:</strong> You're browsing as a guest. You can order food but cannot make advance reservations. 
        <a href="login.php">Login</a> or <a href="register.php">create an account</a> to access all features!
      </div>
      <?php else: ?>
      <div class="user-info">
        <i class="fas fa-user"></i>
        <strong>Welcome back!</strong> You're logged in and can access all features including advance ordering.
      </div>
      <?php endif; ?>

      <section class="featured-section">
        <h2 class="section-title">Featured Items</h2>
        <div class="featured-card">
          <?php
          $menu_items_query = mysqli_query($conn, "
            SELECT m.id, m.name, m.description, m.image, m.price, m.category, s.stall_name, s.id AS stall_id 
            FROM menu_items m
            JOIN seller s ON m.seller_id = s.id
            WHERE m.available = 1 AND m.is_visible = 1 AND m.image IS NOT NULL
            ORDER BY RAND()
            LIMIT 10
        ");

          if ($menu_items_query && mysqli_num_rows($menu_items_query) > 0) {
          ?>
            <div class="slideshow-container">
              <?php
              $slide_dashboard = 0;
              while ($item = mysqli_fetch_assoc($menu_items_query)) {
                if (!isset($item['image']) || !isset($item['name']) || !isset($item['price']) || !isset($item['stall_name'])) {
                    continue; 
                }
                $image_path = $item['image'];
                if ($image_path && strpos($image_path, 'uploads/') === 0) {
                  $image_path = '../' . $image_path;
                }
                $slide_dashboard++;
              ?>
                <div class="slide fade" style="display: <?= $slide_dashboard === 1 ? 'block' : 'none' ?>;">
                  <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 100%; height: 400px; object-fit: cover;">
                  <div class="slide-caption">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p class="price">₱<?= number_format($item['price'], 2) ?></p>
                    <p class="stall">From: <?= htmlspecialchars($item['stall_name']) ?></p>
                  </div>
                </div>
              <?php } ?>

              <a class="prev" onclick="changeSlide(-1)">❮</a>
              <a class="next" onclick="changeSlide(1)">❯</a>
            </div>
            
            <style>
              .featured-section {
                padding: 20px;
                max-width: 1200px;
                margin: 0 auto;
              }

              .featured-card {
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                height: 400px;
              }

              .slideshow-container {
                position: relative;
                width: 100%;
                height: 400px;
                margin: auto;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
              }
              
              .slide {
                display: none;
                position: relative;
                width: 100%;
                height: 400px;
                animation: fade 1.5s ease-in-out;
              }

              .slide img {
                width: 100%;
                height: 400px;
                object-fit: cover;
              }

              .slide-caption {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 20px;
                text-align: center;
              }

              .slide-caption h3 {
                margin: 0 0 10px 0;
                font-size: 24px;
              }

              .slide-caption .price {
                font-size: 20px;
                font-weight: bold;
                margin: 5px 0;
                color: #4CAF50;
              }

              .slide-caption .stall {
                font-size: 14px;
                opacity: 0.8;
                margin: 5px 0;
              }

              .prev, .next {
                cursor: pointer;
                position: absolute;
                top: 50%;
                width: auto;
                padding: 16px;
                margin-top: -22px;
                color: white;
                font-weight: bold;
                font-size: 18px;
                transition: 0.6s ease;
                border-radius: 0 3px 3px 0;
                user-select: none;
                background: rgba(0, 0, 0, 0.5);
              }

              .next {
                right: 0;
                border-radius: 3px 0 0 3px;
              }

              .prev:hover, .next:hover {
                background-color: rgba(0, 0, 0, 0.8);
              }

              @keyframes fade {
                from {opacity: .4} 
                to {opacity: 1}
              }
            </style>

            <script>
              let slideIndex = 1;
              showSlides(slideIndex);

              function changeSlide(n) {
                showSlides(slideIndex += n);
              }

              function showSlides(n) {
                let i;
                let slides = document.getElementsByClassName("slide");
                if (n > slides.length) {slideIndex = 1}    
                if (n < 1) {slideIndex = slides.length}
                for (i = 0; i < slides.length; i++) {
                  slides[i].style.display = "none";  
                }
                slides[slideIndex-1].style.display = "block";  
              }

              // Auto-advance slides every 5 seconds
              setInterval(function() {
                changeSlide(1);
              }, 5000);
            </script>
          <?php } else { ?>
            <div style="text-align: center; padding: 40px; color: #666;">
              <i class="fas fa-utensils" style="font-size: 48px; margin-bottom: 20px;"></i>
              <h3>No featured items available</h3>
              <p>Check back later for new menu items!</p>
            </div>
          <?php } ?>
        </div>
      </section>
    </div>
  </div>
</body>
</html>