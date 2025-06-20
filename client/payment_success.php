<?php
session_start();
include '../includes/db_connect.php';

// Get the payment intent ID and order ID from the URL
$paymentIntentId = isset($_GET['payment_intent_id']) ? $_GET['payment_intent_id'] : null;
$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if ($orderId) {
    // Update the order status
    $updateQuery = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - EZ-Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: rgba(0, 43, 92, 0.9);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .success-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #186479;
            margin-bottom: 20px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #186479;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .button:hover {
            background: #1e7b94;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully. Your order will be prepared shortly.</p>
        <a href="track_order.php" class="button">Track Your Order</a>
    </div>
</body>
</html> 