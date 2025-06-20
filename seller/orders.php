<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connect.php';
$seller_id = $_SESSION['seller_id'];

$stall_query = $conn->prepare("SELECT stall_name FROM seller WHERE id = ?");
$stall_query->bind_param("i", $seller_id);
$stall_query->execute();
$stall_result = $stall_query->get_result();
$stall_data = $stall_result->fetch_assoc();
$stall_name = $stall_data['stall_name'] ?? 'Unknown Stall';

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id_to_update = (int) $_POST['order_id'];
    $status_to_set = $_POST['status'];

    $allowed_statuses = ['Pending', 'Preparing', 'Ready', 'Claimed'];
    if (in_array($status_to_set, $allowed_statuses)) {
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND seller_id = ?");
        $check_stmt->bind_param("ii", $order_id_to_update, $seller_id);
        $check_stmt->execute();
        $item_count = 0;
        $check_stmt->bind_result($item_count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($item_count > 0) {
            mysqli_begin_transaction($conn);
            
            try {
                // Update order items status for this seller
                $update_stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND seller_id = ?");
                $update_stmt->bind_param("sii", $status_to_set, $order_id_to_update, $seller_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating order items status: " . $update_stmt->error);
                }
                
                // Check overall order status based on all items
                $check_overall_status = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN status = 'Claimed' THEN 1 ELSE 0 END) as claimed_items,
                        SUM(CASE WHEN status = 'Ready' THEN 1 ELSE 0 END) as ready_items,
                        SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) as preparing_items,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_items
                    FROM order_items 
                    WHERE order_id = ?
                ");
                $check_overall_status->bind_param("i", $order_id_to_update);
                $check_overall_status->execute();
                $result = $check_overall_status->get_result();
                $status_data = $result->fetch_assoc();
                
                // Determine the overall order status
                $overall_status = 'Pending';
                
                if ($status_data['total_items'] > 0) {
                    if ($status_data['total_items'] == $status_data['claimed_items']) {
                        $overall_status = 'Claimed';
                    } elseif ($status_data['total_items'] == $status_data['ready_items']) {
                        $overall_status = 'Ready';
                    } elseif ($status_data['preparing_items'] > 0) {
                        $overall_status = 'Preparing';
                    } elseif ($status_data['pending_items'] == $status_data['total_items']) {
                        $overall_status = 'Pending';
                    } else {
                        // Mixed statuses - if any are ready, mark as ready
                        if ($status_data['ready_items'] > 0) {
                            $overall_status = 'Ready';
                        } elseif ($status_data['preparing_items'] > 0) {
                            $overall_status = 'Preparing';
                        }
                    }
                }
                
                // Update the main order status
                $update_main = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $update_main->bind_param("si", $overall_status, $order_id_to_update);
                if (!$update_main->execute()) {
                    throw new Exception("Error updating main order status: " . $update_main->error);
                }
                
                mysqli_commit($conn);
                $feedback = "<p style='color:green;'>Order #$order_id_to_update status updated to $status_to_set. Overall order status: $overall_status</p>";
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $feedback = "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            $update_stmt->close();
        } else {
            $feedback = "<p style='color:red;'>You do not have items in this order to update.</p>";
        }
    } else {
        $feedback = "<p style='color:red;'>Invalid status selected.</p>";
    }
}

$sql = "SELECT
            o.id AS order_id,
            o.client_name,
            o.student_number,
            o.status AS order_status,
            o.order_time,
            o.is_reservation,
            o.reservation_date,
            o.reservation_time,
            o.payment_status,
            o.total_amount,
            oi.id AS order_item_id,
            oi.menu_item_id,
            oi.quantity,
            oi.seller_id,
            oi.status AS item_status,
            mi.name AS item_name
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.seller_id = ? AND o.status != 'Claimed'
        ORDER BY o.order_time DESC, o.id, oi.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['order_id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'id' => $row['order_id'],
                'client_name' => $row['client_name'],
                'student_number' => $row['student_number'],
                'status' => $row['order_status'],
                'order_time' => $row['order_time'],
                'is_reservation' => $row['is_reservation'],
                'reservation_date' => $row['reservation_date'],
                'reservation_time' => $row['reservation_time'],
                'payment_status' => $row['payment_status'],
                'total_amount' => $row['total_amount'],
                'items' => []
            ];
        }
        $orders[$order_id]['items'][] = [
            'order_item_id' => $row['order_item_id'],
            'menu_item_id' => $row['menu_item_id'],
            'item_name' => $row['item_name'],
            'quantity' => $row['quantity'],
            'item_status' => $row['item_status']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Seller Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background-color: var(--background-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 600;
            text-align: center;
            position: relative;
            padding-bottom: 0.5rem;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            background-color: var(--primary-light);
            border: 1px solid var(--primary-color);
        }

        .back-link:hover {
            color: white;
            background-color: var(--primary-color);
            transform: translateX(-3px);
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info {
            flex: 1;
        }

        .order-id {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .customer-info {
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .order-time {
            font-size: 0.875rem;
            color: #666;
        }

        .order-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
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

        .order-items {
            margin-top: 1rem;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: var(--text-color);
        }

        .item-quantity {
            font-size: 0.875rem;
            color: #666;
        }

        .item-status {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .status-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .status-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .status-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .reservation-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .payment-info {
            background: #f3e5f5;
            border: 1px solid #e1bee7;
            color: #7b1fa2;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-orders i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-status {
                align-items: flex-start;
            }

            .status-controls {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>Orders - <?php echo htmlspecialchars($stall_name); ?></h2>
        
        <?php if ($feedback): ?>
            <div class="feedback"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-inbox"></i>
                <h3>No Active Orders</h3>
                <p>You don't have any pending orders at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="customer-info">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($order['client_name']); ?>
                                <?php if ($order['student_number']): ?>
                                    (<?php echo htmlspecialchars($order['student_number']); ?>)
                                <?php endif; ?>
                            </div>
                            <div class="order-time">
                                <?php echo date('F j, Y g:i A', strtotime($order['order_time'])); ?>
                            </div>
                            
                            <?php if ($order['is_reservation']): ?>
                                <div class="reservation-info">
                                    <i class="fas fa-calendar"></i>
                                    <strong>Advance Order:</strong> 
                                    Pick-up on <?php echo date('F j, Y', strtotime($order['reservation_date'])); ?> 
                                    at <?php echo date('g:i A', strtotime($order['reservation_time'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="payment-info">
                                <i class="fas fa-credit-card"></i>
                                <strong>Payment:</strong> <?php echo ucfirst($order['payment_status']); ?> | 
                                <strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="order-status">
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="item">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="item-status">
                                    Status: <span class="status-badge status-<?php echo strtolower($item['item_status']); ?>">
                                        <?php echo $item['item_status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <div class="status-controls">
                            <button type="submit" name="status" value="Pending" class="status-btn <?php echo $order['status'] === 'Pending' ? 'active' : ''; ?>">
                                Pending
                            </button>
                            <button type="submit" name="status" value="Preparing" class="status-btn <?php echo $order['status'] === 'Preparing' ? 'active' : ''; ?>">
                                Preparing
                            </button>
                            <button type="submit" name="status" value="Ready" class="status-btn <?php echo $order['status'] === 'Ready' ? 'active' : ''; ?>">
                                Ready
                            </button>
                            <button type="submit" name="status" value="Claimed" class="status-btn <?php echo $order['status'] === 'Claimed' ? 'active' : ''; ?>">
                                Claimed
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
