<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    die('Invalid order ID.');
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header('Location: login.php');
    exit();
}

$order_query = "SELECT o.*, s.stall_name FROM orders o LEFT JOIN seller s ON o.seller_id = s.id WHERE o.id = ? AND o.client_name = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "is", $order_id, $user['name']);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die('Order not found or you do not have permission to view this order.');
}

$items_query = "SELECT oi.*, m.name, m.price, s.stall_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id JOIN seller s ON oi.seller_id = s.id WHERE oi.order_id = ? ORDER BY s.stall_name, m.name";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}

$total = 0;
foreach ($order_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - EZ-Order</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 32px 40px;
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .confirmation-header h1 {
            color: #186479;
            margin-bottom: 10px;
        }
        .order-summary {
            margin-top: 30px;
        }
        .order-summary h2 {
            color: #186479;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .order-items {
            margin-bottom: 20px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #186479;
            text-decoration: none;
            font-weight: 500;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .order-info p {
            margin: 4px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            background: #e0f7fa;
            color: #186479;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-brand">
            <img src="../uploads/logo1.png" alt="EZ-Order" class="header-logo">
        </div>
    </header>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #2ecc71;"></i>
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been placed successfully.</p>
        </div>
        <div class="order-info">
            <p><strong>Order Number:</strong> #<?php echo $order['id']; ?></p>
            <p><strong>Status:</strong> <span class="status-badge"><?php echo htmlspecialchars($order['status']); ?></span></p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['order_time'])); ?></p>
            <?php if (!empty($order['special_request'])): ?>
                <p><strong>Special Request:</strong> <?php echo htmlspecialchars($order['special_request']); ?></p>
            <?php endif; ?>
            <?php if ($order['is_reservation']): ?>
                <p><strong>Order Type:</strong> Advance Order</p>
                <p><strong>Pick-up Date:</strong> <?php echo date('F j, Y', strtotime($order['reservation_date'])); ?></p>
                <p><strong>Pick-up Time:</strong> <?php echo date('g:i A', strtotime($order['reservation_time'])); ?></p>
            <?php endif; ?>
            <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
        </div>
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="order-items">
                <?php if (count($order_items) > 0): ?>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                            <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="order-item">No items found for this order.</div>
                <?php endif; ?>
            </div>
            <div class="summary-row">
                <span>Total</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 