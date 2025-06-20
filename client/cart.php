<?php
session_start();
include '../includes/db_connect.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $item_id => $quantity) {
            if (isset($_SESSION['cart'][$item_id])) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $_SESSION['cart'][$item_id]['quantity'] = $quantity;
                } else {
                    unset($_SESSION['cart'][$item_id]);
                }
            }
        }
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Store reservation data in session only when proceeding to checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_checkout'])) {
    $_SESSION['is_reservation'] = isset($_POST['is_reservation']) ? true : false;
    if ($_SESSION['is_reservation']) {
        $_SESSION['reservation_date'] = $_POST['reservation_date'];
        $_SESSION['reservation_time'] = $_POST['reservation_time'];
    } else {
        unset($_SESSION['is_reservation']);
        unset($_SESSION['reservation_date']);
        unset($_SESSION['reservation_time']);
    }
    error_log("Cart: Session after processing checkout: " . print_r($_SESSION, true)); // Debugging line
    header('Location: checkout.php');
    exit();
}

// Handle guest checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_checkout'])) {
    header('Location: guest_checkout.php');
    exit();
}

// Handle item removal
if (isset($_POST['remove'])) {
    $remove_id = $_POST['remove'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Calculate cart count
$cart_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $cart_count += $item['quantity'];
        }
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EZ-Order | Your Cart</title>
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

        .cart-section {
            background: rgb(207, 205, 205);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .cart-table th {
            background: #186479;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            width: 25px;
            height: 25px;
            border: none;
            background: #186479;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .qty-btn:hover {
            background: #1e7b94;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px;
        }

        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .remove-btn:hover {
            background: #cc0000;
        }

        .cart-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
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
            font-size: 18px;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #186479;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: #1e7b94;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
        }

        .empty-cart i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            color: #186479;
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 20px;
        }

        .continue-shopping {
            display: inline-block;
            padding: 12px 25px;
            background: #186479;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .continue-shopping:hover {
            background: #1e7b94;
        }

        .reservation-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .reservation-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #186479;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .reservation-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .input-group label {
            font-weight: 500;
            color: #186479;
        }

        .input-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .checkout-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .checkout-option {
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            background: white;
        }

        .checkout-option:hover {
            border-color: #186479;
            background: #f8f9fa;
        }

        .checkout-option i {
            font-size: 40px;
            color: #186479;
            margin-bottom: 15px;
        }

        .checkout-option h3 {
            color: #186479;
            margin-bottom: 10px;
        }

        .checkout-option p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .checkout-option .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
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
            .checkout-options {
                grid-template-columns: 1fr;
            }
            
            .reservation-inputs {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <div class="nav-left">
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Menu
                </a>
                <h1>Your Cart</h1>
            </div>
        </div>

        <div class="cart-section">
            <?php if (!empty($_SESSION['cart'])): ?>
                <form method="POST">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($_SESSION['cart'] as $item_id => $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn minus" onclick="updateQuantity(<?php echo $item_id; ?>, -1)">-</button>
                                        <input type="number" name="quantity[<?php echo $item_id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="qty-input" readonly>
                                        <button type="button" class="qty-btn plus" onclick="updateQuantity(<?php echo $item_id; ?>, 1)">+</button>
                                    </div>
                                </td>
                                <td>₱<?php echo number_format($subtotal, 2); ?></td>
                                <td>
                                    <button type="submit" name="remove" value="<?php echo $item_id; ?>" class="remove-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Total:</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <?php if ($is_logged_in): ?>
                        <div class="reservation-section">
                            <div class="reservation-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="isReservation" name="is_reservation" <?php echo isset($_SESSION['is_reservation']) && $_SESSION['is_reservation'] ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span>Advance Order</span>
                            </div>
                            
                            <div id="reservationDetails" style="display: <?php echo isset($_SESSION['is_reservation']) && $_SESSION['is_reservation'] ? 'block' : 'none'; ?>;">
                                <div class="reservation-inputs">
                                    <div class="input-group">
                                        <label for="reservation_date">Pick-up Date:</label>
                                        <input type="date" id="reservation_date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_SESSION['reservation_date']) ? $_SESSION['reservation_date'] : ''; ?>">
                                    </div>
                                    <div class="input-group">
                                        <label for="reservation_time">Pick-up Time:</label>
                                        <input type="time" id="reservation_time" name="reservation_time" value="<?php echo isset($_SESSION['reservation_time']) ? $_SESSION['reservation_time'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="proceed_to_checkout" class="checkout-btn">Proceed to Checkout</button>
                        <?php else: ?>
                        <div class="guest-notice">
                            <i class="fas fa-info-circle"></i>
                            <strong>Guest Checkout Available:</strong> You can order as a guest, but advance ordering requires an account.
                        </div>

                        <div class="checkout-options">
                            <div class="checkout-option">
                                <i class="fas fa-user-plus"></i>
                                <h3>Guest Checkout</h3>
                                <p>Order without creating an account. No advance ordering available.</p>
                                <button type="submit" name="guest_checkout" class="btn btn-primary">
                                    Order as Guest
                                </button>
                            </div>
                            <div class="checkout-option">
                                <i class="fas fa-user"></i>
                                <h3>Login to Order</h3>
                                <p>Create an account or login to access all features including advance ordering.</p>
                                <a href="login.php" class="btn btn-secondary">
                                    Login / Register
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="dashboard.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateQuantity(itemId, change) {
            const input = document.querySelector(`input[name="quantity[${itemId}]"]`);
            let newValue = parseInt(input.value) + change;
            if (newValue < 1) newValue = 1;
            input.value = newValue;
            
            // Submit the form to update cart
            input.form.submit();
        }

        // Reservation toggle functionality
        document.getElementById('isReservation')?.addEventListener('change', function() {
            const reservationDetails = document.getElementById('reservationDetails');
            const dateInput = document.getElementById('reservation_date');
            const timeInput = document.getElementById('reservation_time');

            reservationDetails.style.display = this.checked ? 'block' : 'none';
            
            // Set required attribute based on checkbox
            dateInput.required = this.checked;
            timeInput.required = this.checked;
        });
    </script>
</body>
</html>
