<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    echo json_encode(['success' => false]);
    exit();
}

$client_name = mysqli_real_escape_string($conn, $user['name']);
$result = mysqli_query($conn, "SELECT * FROM orders WHERE client_name = '$client_name' ORDER BY order_time DESC LIMIT 1");

if ($row = mysqli_fetch_assoc($result)) {
    $response = [
        'success' => true,
        'status' => $row['status'],
        'order_id' => $row['id'],
        'is_reservation' => $row['is_reservation'],
        'reservation_date' => $row['reservation_date'],
        'reservation_time' => $row['reservation_time']
    ];
    
    // For advance orders, check if it's time to prepare
    if ($row['is_reservation']) {
        $reservation_datetime = strtotime($row['reservation_date'] . ' ' . $row['reservation_time']);
        $current_datetime = time();
        $time_difference = $reservation_datetime - $current_datetime;
        
        // If it's within 15 minutes of reservation time and status is still Pending
        if ($time_difference <= 900 && $time_difference > 0 && $row['status'] === 'Pending') {
            $response['notification'] = 'Your advance order will be ready soon!';
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false]);
} 