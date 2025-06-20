<?php
require 'vendor/autoload.php';

use WebSocket\Client;

function sendOrderNotification($orderId, $status) {
    try {
        $client = new Client("ws://localhost:8080");
        $data = [
            'type' => 'order_status',
            'orderId' => $orderId,
            'status' => $status,
            'timestamp' => time()
        ];
        $client->send(json_encode($data));
        $client->close();
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
    }
} 