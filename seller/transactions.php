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

// Get the selected date from the form, default to today if not set
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$sql = "SELECT
            o.id AS order_id,
            o.client_name,
            o.order_time,
            CASE 
                WHEN mi.is_deleted = 1 THEN CONCAT(mi.name, ' (Deleted)') 
                ELSE mi.name 
            END AS menu_item_name,
            oi.quantity,
            mi.price,
            (mi.price * oi.quantity) AS item_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.seller_id = ? 
        AND o.status = 'Claimed'
        AND DATE(o.order_time) = ?
        ORDER BY o.order_time DESC, o.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $seller_id, $selected_date);

// Handle file downloads
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $filename = "transactions_" . date('Y-m-d') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, ['Order ID', 'Client Name', 'Order Time', 'Menu Item', 'Quantity', 'Price', 'Item Total']);
    
    // Get data
    $stmt->execute();
    $result = $stmt->get_result();
    
    $current_order_id = null;
    $order_total = 0;
    $grand_total = 0;
    
    while ($row = $result->fetch_assoc()) {
        if ($current_order_id !== $row['order_id']) {
            if ($current_order_id !== null) {
                // Add order total
                fputcsv($output, ['Order Total', '', '', '', '', '', '₱' . number_format($order_total, 2)]);
                $grand_total += $order_total;
            }
            $current_order_id = $row['order_id'];
            $order_total = 0;
        }
        
        // Add transaction row
        fputcsv($output, [
            $row['order_id'],
            $row['client_name'],
            $row['order_time'],
            $row['menu_item_name'],
            $row['quantity'],
            '₱' . number_format($row['price'], 2),
            '₱' . number_format($row['item_total'], 2)
        ]);
        
        $order_total += $row['item_total'];
    }
    
    // Add final order total and grand total
    if ($current_order_id !== null) {
        fputcsv($output, ['Order Total', '', '', '', '', '', '₱' . number_format($order_total, 2)]);
        $grand_total += $order_total;
        fputcsv($output, ['Grand Total', '', '', '', '', '', '', '₱' . number_format($grand_total, 2)]);
    }
    
    fclose($output);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Claimed Orders (Transactions)</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #186479;
            --primary-hover: #134d5d;
            --primary-light: rgba(24, 100, 121, 0.1);
            --text-color: #2d3748;
            --border-color: #e2e8f0;
            --background-light: #f5f7fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--background-light);
            padding: 2rem;
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            text-align: left;
        }

        td {
            padding: 1rem;
            text-align: left;
            border: none;
        }

        tr:nth-child(even) {
            background-color: var(--background-light);
        }

        tr:hover {
            background-color: var(--primary-light);
        }

        .order-group {
            background-color: var(--primary-light) !important;
            font-weight: 500;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .back-link:hover {
            background-color: var(--primary-hover);
            transform: translateX(-3px);
            box-shadow: 0 4px 6px rgba(24, 100, 121, 0.2);
        }

        .grand-total {
            margin-top: 2rem;
            padding: 1.2rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px;
            text-align: right;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px rgba(24, 100, 121, 0.2);
        }

        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(24, 100, 121, 0.2);
            border: none;
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            background-color: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(24, 100, 121, 0.3);
        }

        .error-message, .no-orders {
            background-color: #FED7D7;
            color: #C53030;
            padding: 1.2rem;
            border-radius: 12px;
            margin: 1rem 0;
            border: 1px solid #FC8181;
        }

        .price {
            font-family: 'Courier New', monospace;
            font-weight: 500;
            color: var(--primary-color);
        }

        .date-filter {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: var(--background-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .date-filter label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .date-filter input[type="date"] {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .filter-btn:hover {
            background-color: var(--primary-hover);
        }

        .download-buttons {
            display: flex;
            gap: 1rem;
            margin-left: auto;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            background-color: #217346;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            background-color: #1a5c38;
        }

        .download-btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 1rem;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                padding: 0.75rem;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .date-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .download-buttons {
                margin-left: 0;
                margin-top: 1rem;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>Claimed Orders (Transaction View)</h2>

        <form method="GET" class="date-filter">
            <label for="date">Select Date:</label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            <button type="submit" class="filter-btn">Filter</button>
            <div class="download-buttons">
                <a href="?date=<?php echo htmlspecialchars($selected_date); ?>&download=csv" class="download-btn excel">
                    <i class="fas fa-file-excel"></i> Download CSV
                </a>
            </div>
        </form>

        <?php
        if (!$result) {
            echo "<div class='error-message'>Error fetching claimed orders: " . htmlspecialchars($conn->error) . "</div>";
        } elseif ($result->num_rows === 0) {
            echo "<div class='no-orders'>No claimed orders found with your items.</div>";
        } else {
            $current_order_id = null;
            $grand_total = 0;
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Order Time</th>
                        <th>Menu Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Item Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $order_total = 0;
                    while ($row = $result->fetch_assoc()) { 
                        if ($current_order_id !== $row['order_id']) {
                            if ($current_order_id !== null) {
                                echo "<tr class='order-group'><td colspan='6' style='text-align: right;'><strong>Order Total:</strong></td><td class='price'><strong>₱" . number_format($order_total, 2) . "</strong></td></tr>";
                                $grand_total += $order_total;
                            }
                            $current_order_id = $row['order_id'];
                            $order_total = 0;
                        }
                        $order_total += $row['item_total'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['order_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['menu_item_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td class="price">₱<?php echo htmlspecialchars(number_format($row['price'], 2)); ?></td>
                            <td class="price">₱<?php echo htmlspecialchars(number_format($row['item_total'], 2)); ?></td>
                        </tr>
                    <?php } 
                    if ($current_order_id !== null) {
                        echo "<tr class='order-group'><td colspan='6' style='text-align: right;'><strong>Order Total:</strong></td><td class='price'><strong>₱" . number_format($order_total, 2) . "</strong></td></tr>";
                        $grand_total += $order_total;
                    }
                    ?>
                </tbody>
            </table>
            <div class="grand-total">
                <strong>Grand Total: ₱<?php echo number_format($grand_total, 2); ?></strong>
            </div>
            <?php
        }
        
        if ($stmt) {
            $stmt->close();
        }
        ?>
    </div>

    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script>
        const scrollTop = document.getElementById('scrollTop');

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                scrollTop.classList.add('visible');
            } else {
                scrollTop.classList.remove('visible');
            }
        });

        scrollTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html> 