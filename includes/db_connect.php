<?php

ob_start();


error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


function handleDbError($message) {
    error_log("Database Error: " . $message);
    if (headers_sent()) {
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
    }
    exit();
}


$conn = new mysqli("localhost", "root", "password", "kiosk_system");


if ($conn->connect_error) {
    handleDbError($conn->connect_error);
}
    
if (!$conn->set_charset("utf8mb4")) {
    handleDbError("Error setting charset: " . $conn->error);
}
?>
