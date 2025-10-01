<?php
session_start();
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";

$tiger_key   = TIGER_KEY;
$paystack_sk = "sk_test_ed4c582d1f8a7417d7d20bb06c47b631f142f056"; // secret key

// Validate reference
$reference = $_GET['reference'] ?? null;
if (!$reference) {
    die("⚠️ Invalid callback: no reference provided.");
}

// Verify Paystack transaction
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . $reference);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $paystack_sk"
]);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    die("⚠️ Paystack verification failed.");
}

$result = json_decode($response, true);
if (!isset($result['data']['status']) || $result['data']['status'] !== 'success') {
    die("⚠️ Payment not successful.");
}

// Load order
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];

if (!isset($orders[$reference])) {
    die("⚠️ Order not found.");
}

$order = $orders[$reference];

// Safety: Recompute NGN amount from RUB
$rub_price = $order['rub_price'];
$expected_ngn = convertRubToNgn($rub_price) + PROFIT_MARGIN_NGN;

// Compare with Paystack amount
$paid_amount = intval($result['data']['amount'] / 100); // kobo → naira
if ($paid_amount < $expected_ngn) {
    die("⚠️ Paid amount does not match expected.");
}

// Request number from Tiger-SMS
$service = $order['service'];
$country = $order['country'];
$tiger_url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getNumber&service=$service&country=$country";

$tiger_response = @file_get_contents($tiger_url);
if (!$tiger_response || strpos($tiger_response, "ACCESS_NUMBER") === false) {
    $orders[$reference]['status'] = "paid_but_failed_tiger";
    file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
    die("⚠️ Tiger-SMS failed: " . htmlspecialchars($tiger_response));
}

// Parse Tiger response: e.g. ACCESS_NUMBER:12345:9876543210
list(, $activation_id, $phone) = explode(":", $tiger_response);

$orders[$reference]['status']        = "active";
$orders[$reference]['activation_id'] = $activation_id;
$orders[$reference]['phone']         = $phone;
$orders[$reference]['confirmed_at']  = date("Y-m-d H:i:s");

// Save back
file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));

// Redirect to dashboard or order details
header("Location: dashboard.php?success=1&ref=" . $reference);
exit;