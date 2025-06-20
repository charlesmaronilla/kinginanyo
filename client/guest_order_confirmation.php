<?php
session_start();
include '../includes/db_connect.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    die('Invalid order ID.');
}

// Get order details
$order_query = "SELECT o.*, s.stall_name FROM orders o LEFT JOIN seller s ON o.seller_id = s.id WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die('Order not found.');
}

// Get order items
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #186479 0%, #134d5d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .success-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .content {
            padding: 30px;
        }

        .order-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .order-info h2 {
            color: #186479;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            color: #186479;
            font-size: 16px;
        }

        .order-items {
            margin-bottom: 30px;
        }

        .order-items h2 {
            color: #186479;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            color: #186479;
            margin-bottom: 5px;
        }

        .item-stall {
            font-size: 12px;
            color: #666;
        }

        .item-price {
            color: #186479;
            font-weight: bold;
        }

        .total-section {
            background: #186479;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .total-section h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .total-amount {
            font-size: 32px;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #186479;
            color: white;
        }

        .btn-primary:hover {
            background: #134d5d;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-outline {
            background: transparent;
            color: #186479;
            border: 2px solid #186479;
        }

        .btn-outline:hover {
            background: #186479;
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-preparing {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-ready {
            background: #d4edda;
            color: #155724;
        }

        .status-claimed {
            background: #cce5ff;
            color: #004085;
        }

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

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .content {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We'll start preparing it right away.</p>
        </div>

        <div class="content">
            <div class="guest-notice">
                <i class="fas fa-info-circle"></i>
                <strong>Guest Order:</strong> You ordered as a guest. To track your order status, please save your order ID: <strong><?php echo $order_id; ?></strong>
            </div>

            <div class="order-info">
                <h2>Order Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Order ID</span>
                        <span class="info-value">#<?php echo $order_id; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Customer Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['client_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Date</span>
                        <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['order_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Status</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_status']); ?></span>
                    </div>
                    <?php if (!empty($order['special_request'])): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Special Request</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['special_request']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-items">
                <h2>Order Items</h2>
                <?php foreach ($order_items as $item): ?>
                <div class="item">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-stall">From: <?php echo htmlspecialchars($item['stall_name']); ?></div>
                    </div>
                    <div class="item-price">
                        ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        <span style="color: #666; font-size: 14px;">(x<?php echo $item['quantity']; ?>)</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="total-section">
                <h3>Total Amount</h3>
                <div class="total-amount">₱<?php echo number_format($total, 2); ?></div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="guest_track_order.php?id=<?php echo $order_id; ?>" class="btn btn-outline">
                    <i class="fas fa-search"></i> Track Order
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Create Account
                </a>
            </div>
        </div>
    </div>
</body>
</html> 