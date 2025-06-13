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


if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Please login first');
}

$user_id = $_SESSION['user_id'];

                            
$user_query = "SELECT name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($conn));
    sendJsonResponse(false, 'Database error');
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
if (!mysqli_stmt_execute($stmt)) {
    error_log("Execute failed: " . mysqli_stmt_error($stmt));
    sendJsonResponse(false, 'Database error');
}

$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    sendJsonResponse(false, 'User not found');
}

$client_name = $user['name'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$special_request = isset($_POST['special_request']) ? mysqli_real_escape_string($conn, $_POST['special_request']) : '';
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : 'online';


$is_reservation = isset($_SESSION['is_reservation']) ? 1 : 0;
$reservation_date = null;
$reservation_time = null;

if ($is_reservation) {
    $reservation_date = isset($_SESSION['reservation_date']) ? mysqli_real_escape_string($conn, $_SESSION['reservation_date']) : null;
    $reservation_time = isset($_SESSION['reservation_time']) ? mysqli_real_escape_string($conn, $_SESSION['reservation_time']) : null;
}


$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}


mysqli_begin_transaction($conn);

try {

    $payment_status = ($payment_type === 'online') ? 'pending' : 'paid';
    

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
        $client_name,
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
    

    $_SESSION['cart'] = [];
    unset($_SESSION['is_reservation']);
    unset($_SESSION['reservation_date']);
    unset($_SESSION['reservation_time']);
    

    sendJsonResponse(true, 'Order placed successfully', $order_id);
    
} catch (Exception $e) {

    mysqli_rollback($conn);
    error_log("Order submission error: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to place order: ' . $e->getMessage());
}
?> 