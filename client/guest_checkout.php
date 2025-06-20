<?php
session_start();
include '../includes/db_connect.php';

define('PAYMONGO_PUBLIC_KEY', 'pk_test_7HMZjMgQ1Ct9dkNkQ9AugX99');

// Redirect if user is logged in (they should use regular checkout)
if (isset($_SESSION['user_id'])) {
    header('Location: checkout.php');
    exit();
}

// Check if cart has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Guest orders cannot be reservations
$is_reservation = false;
$reservation_date = '';
$reservation_time = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EZ-Order | Guest Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: rgba(0, 43, 92, 0.9);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-button {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: #186479;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .back-button:hover {
            background: #1e7b94;
        }

        .back-button i {
            margin-right: 8px;
        }

        h1 {
            color: #186479;
            font-size: 24px;
            margin: 0;
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

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #186479;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #186479;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #186479;
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-summary h2 {
            color: #186479;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: #186479;
            margin-bottom: 5px;
        }

        .item-price {
            color: #666;
            font-size: 14px;
        }

        .item-quantity {
            color: #186479;
            font-weight: 500;
            margin-left: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #186479;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #1e7b94;
        }

        .error-message {
            color: #ff4444;
            font-size: 14px;
            margin-top: 5px;
        }

        .success-message {
            color: #186479;
            font-size: 14px;
            margin-top: 5px;
        }

        .payment-section {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .payment-options {
            margin-top: 15px;
        }

        .payment-option {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #186479;
            background: #f8f9fa;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin: 0;
            color: #186479;
        }

        .payment-option i {
            font-size: 24px;
            margin-right: 15px;
            color: #186479;
        }

        .payment-description {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            margin-left: 39px;
        }

        .payment-option input[type="radio"]:checked + label {
            font-weight: bold;
        }

        .payment-option input[type="radio"]:checked + label i {
            color: #186479;
        }

        .login-prompt {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            color: #186479;
        }

        .login-prompt a {
            color: #186479;
            font-weight: bold;
            text-decoration: none;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <div class="nav-left">
                <a href="cart.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Cart
                </a>
                <h1>Guest Checkout</h1>
            </div>
        </div>

        <div class="guest-notice">
            <i class="fas fa-info-circle"></i>
            <strong>Guest Order Notice:</strong> You're ordering as a guest. You can place immediate orders but cannot make advance reservations. 
            <a href="login.php" style="color: #186479; font-weight: bold;">Login</a> to access all features including advance ordering.
        </div>

        <div class="checkout-grid">
            <div class="checkout-form">
                <form id="guest-checkout-form" method="POST" action="submit_guest_order.php">
                    <div class="form-section">
                        <h2>Guest Information</h2>
                        <div class="form-group">
                            <label for="guest_name">Full Name *</label>
                            <input type="text" id="guest_name" name="guest_name" required>
                        </div>
                        <div class="form-group">
                            <label for="special_request">Special Requests (Optional)</label>
                            <textarea id="special_request" name="special_request" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="payment-section">
                        <h2>Payment Method</h2>
                        <div class="payment-options">
                            <div class="payment-option">
                                <input type="radio" id="pay_counter" name="payment_type" value="counter" checked>
                                <label for="pay_counter">
                                    <i class="fas fa-store"></i>
                                    Pay at Counter
                                    <span class="payment-description">Pay when you receive your order</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="submit-button">Place Guest Order</button>
                </form>

                <div class="login-prompt">
                    <i class="fas fa-user-plus"></i>
                    Want to save your information and access advance ordering? 
                    <a href="register.php">Create an account</a> or <a href="login.php">login</a> now!
                </div>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const submitButton = document.getElementById('submit-button');
        document.getElementById('guest-checkout-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            try {
                const formData = new FormData(e.target);
                formData.set('payment_type', 'counter');
                
                const response = await fetch('submit_guest_order.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to place order');
                }
                
                window.location.href = 'guest_order_confirmation.php?id=' + result.order_id;
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while processing your order. Please try again or contact support.');
                submitButton.disabled = false;
                submitButton.textContent = 'Place Guest Order';
            }
        });
    </script>
</body>
</html> 