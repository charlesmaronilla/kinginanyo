<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// First get the user's information
$user_query = "SELECT id, name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
if (!$stmt) {
    error_log("Failed to prepare user query: " . mysqli_error($conn));
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
if (!mysqli_stmt_execute($stmt)) {
    error_log("Failed to execute user query: " . mysqli_stmt_error($stmt));
    exit();
}
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header('Location: login.php');
    exit();
}

// Get date filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch user's order history (temporarily without date filter for debugging)
$orders_query = "SELECT o.*, s.stall_name, o.is_reservation, o.reservation_date, o.reservation_time 
                FROM orders o 
                LEFT JOIN seller s ON o.seller_id = s.id 
                WHERE o.client_name = ? 
                ORDER BY o.order_time DESC";
$stmt = mysqli_prepare($conn, $orders_query);
if (!$stmt) {
    error_log("Failed to prepare orders query: " . mysqli_error($conn));
    $orders_result = false;
} else {
    mysqli_stmt_bind_param($stmt, "s", $user['name']);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Failed to execute orders query: " . mysqli_stmt_error($stmt));
        $orders_result = false;
    } else {
        $orders_result = mysqli_stmt_get_result($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - EZ-Order</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php // Debugging: Display client name at the very top of the body - REMOVED ?>
    <?php // echo "<div class='debug-message'>"; ?>
    <?php // echo "DEBUG: User Name: " . htmlspecialchars($user['name']) . "</div>"; ?>

    <header class="admin-header">
        <div class="header-brand">
            <img src="../uploads/logo1.png" alt="EZ-Order" class="header-logo">
        </div>
    </header>

    <div class="sidebar">
        <div class="logoo-wrapper">
            <img src="../uploads/logo.png" alt="EZ-Order Logo" class="sidebar-logo">
            <div class="logoo">EZ-ORDER</div>
            <div class="divider"></div>
            <div class="tagline">"easy orders, zero hassle"</div>
        </div>
    </div>

    <div class="main-content">
        <div class="orders-container">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Order History</h1>

            <div class="date-filter">
                <form method="GET" action="">
                    <div>
                        <label for="start_date">From:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div>
                        <label for="end_date">To:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <button type="submit">Filter</button>
                </form>
            </div>
            
            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                    // Check if this is a current order (not claimed)
                    $is_current_order = $order['status'] !== 'Claimed';
                ?>
                    <div class="order-card <?php echo $is_current_order ? 'current-order' : ''; ?>">
                        <div class="order-header">
                            <div>
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <p>From: <?php echo htmlspecialchars($order['stall_name'] ?? 'N/A'); ?></p>
                                <p class="order-time">Date: <?php echo date('F j, Y g:i A', strtotime($order['order_time'])); ?></p>
                                <?php if (!empty($order['special_request'])): ?>
                                    <p><strong>Special Request:</strong> <?php echo htmlspecialchars($order['special_request']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($order['is_reservation']): // Check if it's an advance order ?>
                                <p><strong>Order Type:</strong> Advance Order</p>
                                <p><strong>Pick-up Date:</strong> <?php echo date('F j, Y', strtotime($order['reservation_date'])); ?></p>
                                <p><strong>Pick-up Time:</strong> <?php echo date('g:i A', strtotime($order['reservation_time'])); ?></p>
                                <?php endif; ?>
                                <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status'] ?? ''); ?></p>
                                <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
                            </div>
                            <div>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <?php
                            // Fetch order items
                            $items_query = "SELECT oi.*, m.name, m.price 
                                          FROM order_items oi 
                                          JOIN menu_items m ON oi.menu_item_id = m.id 
                                          WHERE oi.order_id = ?";
                            $stmt = mysqli_prepare($conn, $items_query);
                            if (!$stmt) {
                                error_log("Failed to prepare order items query: " . mysqli_error($conn));
                            } else {
                                mysqli_stmt_bind_param($stmt, "i", $order['id']);
                                if (!mysqli_stmt_execute($stmt)) {
                                    error_log("Failed to execute order items query: " . mysqli_stmt_error($stmt));
                                } else {
                                    $items_result = mysqli_stmt_get_result($stmt);

                                    $total = 0;
                                    if ($items_result && mysqli_num_rows($items_result) === 0) {
                                        echo "<p>No items found for this order.</p>";
                                    }
                                    while ($item = mysqli_fetch_assoc($items_result)):
                                        $item_total = $item['price'] * $item['quantity'];
                                        $total += $item_total;
                                    ?>
                                        <div class="order-item">
                                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                            <span>₱<?php echo number_format($item_total, 2); ?></span>
                                        </div>
                                    <?php endwhile;
                                }
                            }
                            ?>
                            
                            <div class="order-item total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="order-card">
                    <p>No orders found in your history for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 