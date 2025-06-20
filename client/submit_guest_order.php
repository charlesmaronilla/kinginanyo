<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');

function sendJsonResponse($success, $message, $orderId = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($orderId !== null) {
        $response['order_id'] = $orderId;
    }
    echo json_encode($response);
    exit();
}

// Check if cart has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    sendJsonResponse(false, 'Cart is empty');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Validate guest information
$guest_name = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';

if (empty($guest_name)) {
    sendJsonResponse(false, 'Please enter your name');
}

$special_request = isset($_POST['special_request']) ? mysqli_real_escape_string($conn, $_POST['special_request']) : '';
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : 'online';

// Guest orders cannot be reservations
$is_reservation = 0;
$reservation_date = null;
$reservation_time = null;

// Calculate total
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

mysqli_begin_transaction($conn);

try {
    $payment_status = ($payment_type === 'online') ? 'pending' : 'paid';
    
    // Insert guest order
    $sql_order = "INSERT INTO orders (
        client_name, 
        status, 
        order_time, 
        special_request, 
        is_reservation, 
        reservation_date, 
        reservation_time,
        payment_status,
        total_amount
    ) VALUES (
        ?, 'Pending', NOW(), ?, ?, ?, ?, ?, ?
    )";
    
    $stmt = mysqli_prepare($conn, $sql_order);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssisssd", 
        $guest_name,
        $special_request,
        $is_reservation,
        $reservation_date,
        $reservation_time,
        $payment_status,
        $total
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create order: " . mysqli_stmt_error($stmt));
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Insert order items
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item_id => $item) {
            $menu_item_id = mysqli_real_escape_string($conn, $item_id);
            $quantity = mysqli_real_escape_string($conn, $item['quantity']);
            $seller_id = mysqli_real_escape_string($conn, $item['seller_id']);
            
            if (!$seller_id) {
                $menu_query = mysqli_query($conn, "SELECT seller_id FROM menu_items WHERE id = '$menu_item_id'");
                if ($menu_item = mysqli_fetch_assoc($menu_query)) {
                    $seller_id = mysqli_real_escape_string($conn, $menu_item['seller_id']);
                } else {
                    throw new Exception("Failed to get seller information");
                }
            }
            
            $sql_item = "INSERT INTO order_items (order_id, menu_item_id, quantity, seller_id) 
                        VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_item);
            if (!$stmt) {
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "iiis", $order_id, $menu_item_id, $quantity, $seller_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to add order items: " . mysqli_stmt_error($stmt));
            }
        }
    }
    
    mysqli_commit($conn);
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    sendJsonResponse(true, 'Guest order placed successfully', $order_id);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Guest order submission error: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to place order: ' . $e->getMessage());
}
?> 