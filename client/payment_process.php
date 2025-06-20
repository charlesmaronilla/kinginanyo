<?php
session_start();
include '../includes/db_connect.php';

// Load configuration safely
$config = include('../includes/config.php');
define('PAYMONGO_SECRET_KEY', $config['paymongo_secret_key']);
define('PAYMONGO_PUBLIC_KEY', $config['paymongo_public_key']);
$domain = $config['domain'];

function createPaymentLink($amount, $description, $orderId = null) {
    error_log('=== createPaymentLink START ===');
    error_log("Input parameters - amount: $amount, description: $description, orderId: $orderId");

    $requestData = [
        'data' => [
            'attributes' => [
                'amount' => intval($amount * 100),
                'description' => $description,
                'remarks' => 'EZ-Order Payment',
                'metadata' => $orderId ? ['order_id' => $orderId] : []
            ]
        ]
    ];

    error_log('Request data prepared: ' . json_encode($requestData, JSON_PRETTY_PRINT));

    $ch = curl_init("https://api.paymongo.com/v1/links");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode(PAYMONGO_SECRET_KEY . ":")
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("CURL execution completed\nHTTP Code: $httpCode\nCURL Error: " . ($error ?: 'None') . "\nRaw Response: $response");

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $checkout = $result['data']['attributes']['checkout_url'] ?? false;
        if ($checkout) {
            error_log("Checkout URL found: $checkout\n=== createPaymentLink END (Success) ===");
            return $result;
        }
        error_log('No checkout_url found in response\n=== createPaymentLink END (No checkout_url) ===');
    } else {
        error_log("HTTP Error: $httpCode - Response: $response\n=== createPaymentLink END (HTTP Error) ===");
    }
    return false;
}

function createPaymentIntent($amount, $currency = 'PHP', $paymentMethod = null) {
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amount * 100,
                'payment_method_allowed' => ['card', 'gcash', 'grab_pay'],
                'currency' => $currency,
                'capture_type' => 'automatic'
            ]
        ]
    ];

    $ch = curl_init('https://api.paymongo.com/v1/payment_intents');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($paymentMethod && in_array($paymentMethod, ['gcash', 'grab_pay'])) {
            $source = createSource($result['data']['id'], $paymentMethod);
            $result['redirect_url'] = $source['data']['attributes']['redirect']['url'] ?? null;
        }
        return $result;
    }
    return false;
}

function createSource($paymentIntentId, $paymentMethod) {
    global $domain;

    $data = [
        'data' => [
            'attributes' => [
                'type' => $paymentMethod,
                'amount' => $_POST['amount'] * 100,
                'currency' => 'PHP',
                'redirect' => [
                    'success' => "$domain/client/payment_success.php",
                    'failed' => "$domain/client/payment_failed.php"
                ]
            ]
        ]
    ];

    $ch = curl_init('https://api.paymongo.com/v1/sources');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200 ? json_decode($response, true) : false;
}

function verifyPayment($paymentIntentId) {
    $url = "https://api.paymongo.com/v1/payment_intents/$paymentIntentId";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200 ? json_decode($response, true) : false;
}

// Process client payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $orderId = $_POST['order_id'] ?? null;

    error_log("=== PAYMENT DEBUG START ===\nReceived payment request - Amount: $amount, Order ID: $orderId");

    if ($amount < 1) {
        echo json_encode(['success' => false, 'message' => 'Minimum payment amount is ₱1.00']);
        exit;
    }

    if (empty(PAYMONGO_SECRET_KEY) || strlen(PAYMONGO_SECRET_KEY) < 20) {
        echo json_encode(['success' => false, 'message' => 'Configuration error. Please contact support.']);
        exit;
    }

    $paymentLink = createPaymentLink($amount, 'EZ-Order Payment', $orderId);

    if ($paymentLink && isset($paymentLink['data']['attributes']['checkout_url'])) {
        echo json_encode(['success' => true, 'payment_link' => $paymentLink['data']['attributes']['checkout_url']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create payment link.']);
    }
    exit;
}

// Handle webhook verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_PAYMONGO_SIGNATURE'])) {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'];
    $computedSignature = hash_hmac('sha256', $payload, PAYMONGO_SECRET_KEY);

    if (hash_equals($signature, $computedSignature)) {
        $event = json_decode($payload, true);

        if ($event['data']['attributes']['type'] === 'source.chargeable') {
            $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'];
            $paymentStatus = verifyPayment($paymentIntentId);

            if ($paymentStatus && $paymentStatus['data']['attributes']['status'] === 'succeeded') {
                $orderId = $event['data']['attributes']['data']['attributes']['metadata']['order_id'];
                $stmt = mysqli_prepare($conn, "UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $orderId);
                mysqli_stmt_execute($stmt);
            }
        }
    }

    http_response_code(200);
    exit;
}
?>
