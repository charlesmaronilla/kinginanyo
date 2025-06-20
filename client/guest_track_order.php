<?php
session_start();
include '../includes/db_connect.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';
$order = null;
$order_items = [];

if ($order_id > 0) {
    // Get order details
    $order_query = "SELECT o.*, s.stall_name FROM orders o LEFT JOIN seller s ON o.seller_id = s.id WHERE o.id = ?";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $order_result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($order_result);

    if ($order) {
        // Get order items
        $items_query = "SELECT oi.*, m.name, m.price, s.stall_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id JOIN seller s ON oi.seller_id = s.id WHERE oi.order_id = ? ORDER BY s.stall_name, m.name";
        $stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $items_result = mysqli_stmt_get_result($stmt);

        while ($item = mysqli_fetch_assoc($items_result)) {
            $order_items[] = $item;
        }
    } else {
        $error_message = 'Order not found. Please check your order ID.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Guest Order - EZ-Order</title>
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

        .content {
            padding: 30px;
        }

        .search-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .search-form h2 {
            color: #186479;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #186479;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #186479;
        }

        .search-btn {
            background: #186479;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .search-btn:hover {
            background: #134d5d;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .order-status {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            position: relative;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }

        .status-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            color: white;
        }

        .status-icon.active {
            background: #186479;
        }

        .status-icon.completed {
            background: #28a745;
        }

        .status-label {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .order-details h2 {
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
            
            .status-timeline {
                flex-direction: column;
                gap: 20px;
            }
            
            .status-timeline::before {
                display: none;
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
            <h1>Track Your Order</h1>
            <p>Enter your order ID to track your guest order status</p>
        </div>

        <div class="content">
            <div class="search-form">
                <h2>Search Order</h2>
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="order_id">Order ID</label>
                        <input type="number" id="order_id" name="id" value="<?php echo $order_id; ?>" placeholder="Enter your order ID" required>
                    </div>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Track Order
                    </button>
                </form>
            </div>

            <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if ($order): ?>
            <div class="order-status">
                <h2>Order Status</h2>
                <div class="status-timeline">
                    <div class="status-step">
                        <div class="status-icon <?php echo in_array($order['status'], ['Pending', 'Preparing', 'Ready', 'Claimed']) ? 'completed' : 'active'; ?>">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="status-label">Order Placed</div>
                    </div>
                    <div class="status-step">
                        <div class="status-icon <?php echo in_array($order['status'], ['Preparing', 'Ready', 'Claimed']) ? 'completed' : ($order['status'] == 'Pending' ? 'active' : ''); ?>">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="status-label">Preparing</div>
                    </div>
                    <div class="status-step">
                        <div class="status-icon <?php echo in_array($order['status'], ['Ready', 'Claimed']) ? 'completed' : ($order['status'] == 'Preparing' ? 'active' : ''); ?>">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="status-label">Ready</div>
                    </div>
                    <div class="status-step">
                        <div class="status-icon <?php echo $order['status'] == 'Claimed' ? 'completed' : ($order['status'] == 'Ready' ? 'active' : ''); ?>">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="status-label">Claimed</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
            </div>

            <div class="order-details">
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
                <div class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="login.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Create Account
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($order): ?>
    <script>
        // Real-time status checking for guest orders
        let currentStatus = '<?php echo $order['status']; ?>';
        let orderId = <?php echo $order_id; ?>;
        
        function checkOrderStatus() {
            fetch(`check_order_status.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status !== currentStatus) {
                        // Status has changed, reload the page to show updated status
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error checking order status:', error);
                });
        }
        
        // Check status every 5 seconds
        setInterval(checkOrderStatus, 5000);
        
        // Also check when the page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkOrderStatus();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html> 