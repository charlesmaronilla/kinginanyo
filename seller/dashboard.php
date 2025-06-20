<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="assets/logo.png" alt="Logo" class="logo">
            </div>
            <nav class="nav">
                <ul>
                    <li><a href="manage_menu.php"><i class="fas fa-utensils"></i> Menu</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
                    <li><a href="transactions.php"><i class="fas fa-history"></i> Transaction History</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <?php
                // Fetch seller information
                $seller_sql = "SELECT name, stall_name FROM seller WHERE id = ?";
                $seller_stmt = $conn->prepare($seller_sql);
                $seller_stmt->bind_param("i", $_SESSION['seller_id']);
                $seller_stmt->execute();
                $seller_result = $seller_stmt->get_result();
                $seller_info = $seller_result->fetch_assoc();
                ?>
                <div class="seller-info">
                    <h1>Welcome, <?php echo htmlspecialchars($seller_info['name']); ?></h1>
                    <h2><?php echo htmlspecialchars($seller_info['stall_name']); ?></h2>
                </div>
                <div class="date-display">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <?php
            
            $sql = "SELECT 
                        mi.name as item_name,
                        COUNT(oi.order_id) as total_orders,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.quantity * mi.price) as total_revenue
                    FROM menu_items mi
                    LEFT JOIN order_items oi ON mi.id = oi.menu_item_id
                    WHERE mi.seller_id = ? AND mi.is_deleted = 0
                    GROUP BY mi.id, mi.name
                    ORDER BY total_orders DESC, total_revenue DESC
                    LIMIT 5";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['seller_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>

            <div class="dashboard-grid">
                <div class="top-items-section">
                    <div class="section-header">
                        <h2><i class="fas fa-chart-line"></i> Top Ordered Items</h2>
                    </div>
                    <div class="table-container">
                        <table class="top-items-table">
                            <thead>
                                <tr>
                                    <th>Menu Item</th>
                                    <th>Total Orders</th>
                                    <th>Total Quantity</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><i class='fas fa-utensils'></i> " . htmlspecialchars($row['item_name']) . "</td>";
                                        echo "<td><i class='fas fa-shopping-bag'></i> " . number_format($row['total_orders']) . "</td>";
                                        echo "<td><i class='fas fa-box'></i> " . number_format($row['total_quantity']) . "</td>";
                                        echo "<td><i class='fas fa-peso-sign'></i> " . number_format($row['total_revenue'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='no-data'>No orders found for your items.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
        }

        .logo-container {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            max-width: 120px;
            height: auto;
        }

        .nav ul {
            list-style: none;
            padding: 20px 0;
        }

        .nav ul li {
            padding: 10px 20px;
            margin: 5px 0;
        }

        .nav ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav ul li a i {
            margin-right: 10px;
            width: 20px;
        }

        .nav ul li a:hover {
            color: #3498db;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .seller-info h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .seller-info h2 {
            color: #7f8c8d;
            font-size: 18px;
            font-weight: 500;
        }

        .date-display {
            color: #7f8c8d;
            font-size: 14px;
        }

        .date-display i {
            margin-right: 5px;
        }

        .dashboard-grid {
            display: grid;
            gap: 20px;
        }

        .top-items-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .section-header h2 i {
            margin-right: 10px;
            color: #3498db;
        }

        .table-container {
            padding: 20px;
            overflow-x: auto;
        }

        .top-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-items-table th,
        .top-items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .top-items-table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #2c3e50;
        }

        .top-items-table td {
            color: #34495e;
        }

        .top-items-table tr:hover {
            background-color: #f8f9fa;
        }

        .top-items-table td i {
            margin-right: 8px;
            color: #3498db;
        }

        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .main-content {
                margin-left: 70px;
            }

            .nav ul li a span {
                display: none;
            }

            .logo-container {
                padding: 10px;
            }

            .logo {
                max-width: 40px;
            }
        }
    </style>
</body>
</html>
