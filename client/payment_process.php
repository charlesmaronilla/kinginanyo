<?php
session_start();
include '../includes/db_connect.php';


define('PAYMONGO_SECRET_KEY', 'sk_test_sFRj5bx6fN2nXxEcmhXUywSd');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_TB51ed9BJHRv3RFfYvh9eeKU');

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $protocol . $_SERVER['HTTP_HOST'];

function createPaymentLink($amount, $description) {
    $url = 'https://api.paymongo.com/v1/links';
    
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amount * 100, 
                'description' => $description,
                'remarks' => 'EZ-Order Payment',
                'line_items' => [
                    [
                        'currency' => 'PHP',
                        'amount' => $amount * 100,
                        'name' => 'Order Payment',
                        'quantity' => 1
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
    }
    
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['data']['attributes']['checkout_url'])) {
            return $result;
        }
    }
    
    error_log('Payment link creation failed. Response: ' . $response);
    return false;
}

function createPaymentIntent($amount, $currency = 'PHP', $paymentMethod = null) {
    $url = 'https://api.paymongo.com/v1/payment_intents';
    
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amount * 100, 
                'payment_method_allowed' => [
                    'card',
                    'gcash',
                    'grab_pay'
                ],
                'currency' => $currency,
                'capture_type' => 'automatic'
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
       
        if ($paymentMethod && in_array($paymentMethod, ['gcash', 'grab_pay'])) {
            $source = createSource($result['data']['id'], $paymentMethod);
            if ($source) {
                $result['redirect_url'] = $source['data']['attributes']['redirect']['url'];
            }
        }
        
        return $result;
    }
    
    return false;
}

function createSource($paymentIntentId, $paymentMethod) {
    global $domain;
    
    $url = 'https://api.paymongo.com/v1/sources';
    
    $data = [
        'data' => [
            'attributes' => [
                'type' => $paymentMethod,
                'amount' => $_POST['amount'] * 100,
                'currency' => 'PHP',
                'redirect' => [
                    'success' => $domain . '/client/payment_success.php',
                    'failed' => $domain . '/client/payment_failed.php'
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

function verifyPayment($paymentIntentId) {
    $url = 'https://api.paymongo.com/v1/payment_intents/' . $paymentIntentId;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    
   
    $paymentLink = createPaymentLink($amount, 'EZ-Order Payment');
    
    if ($paymentLink && isset($paymentLink['data']['attributes']['checkout_url'])) {
        echo json_encode([
            'success' => true,
            'payment_link' => $paymentLink['data']['attributes']['checkout_url']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create payment link'
        ]);
    }
    exit();
}

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
                $updateQuery = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, "i", $orderId);
                mysqli_stmt_execute($stmt);
            }
        }
    }
    
    http_response_code(200);
    exit();
}
?> 