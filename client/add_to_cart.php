<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
  $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
  $seller_id = isset($_POST['seller_id']) ? (int)$_POST['seller_id'] : 0;

  if ($item_id <= 0 || $price <= 0 || $name === '' || $seller_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
    exit;
  }

  if (isset($_SESSION['cart'][$item_id])) {
    $_SESSION['cart'][$item_id]['quantity'] += $quantity;
  } else {
    $_SESSION['cart'][$item_id] = [
      'item_id' => $item_id,
      'name' => $name,
      'price' => $price,
      'quantity' => $quantity,
      'seller_id' => $seller_id
    ];
  }

  echo json_encode(['success' => true]);
  exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
