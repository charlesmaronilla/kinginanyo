<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple PayMongo Test</h2>";

// Test 1: Your exact working example
echo "<h3>Test 1: Your Working Example</h3>";

$ch = curl_init('https://api.paymongo.com/v1/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"data":{"attributes":{"amount":10000,"description":"EzOrder","remarks":"None"}}}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept' => 'application/json',
    'authorization' => 'Basic c2tfdGVzdF9zRlJqNWJ4NmZOMm5YeEVjbWhYVXl3U2Q6',
    'content-type' => 'application/json',
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? $error : 'None') . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 2: With the corrected base64
echo "<h3>Test 2: With Corrected Base64</h3>";

$ch = curl_init('https://api.paymongo.com/v1/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"data":{"attributes":{"amount":10000,"description":"EzOrder","remarks":"None"}}}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept' => 'application/json',
    'authorization' => 'Basic c2tfdGVzdF81RlJqNWJ4NmZOMm5YeEVjbWhYVXl3U2Q6',
    'content-type' => 'application/json',
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? $error : 'None') . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 3: Dynamic generation
echo "<h3>Test 3: Dynamic Generation</h3>";

$apiKey = 'sk_test_5FRj5bx6fN2nXxEcmhXUywSd';
$auth = 'Basic ' . base64_encode($apiKey . ':');

echo "<p><strong>API Key:</strong> " . $apiKey . "</p>";
echo "<p><strong>Generated Auth:</strong> " . $auth . "</p>";

$ch = curl_init('https://api.paymongo.com/v1/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"data":{"attributes":{"amount":10000,"description":"EzOrder","remarks":"None"}}}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept' => 'application/json',
    'authorization' => $auth,
    'content-type' => 'application/json',
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? $error : 'None') . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
?> 