<?php
session_start();
$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";
$paystack_sk = "sk_test_ed4c582d1f8a7417d7d20bb06c47b631f142f056";

$reference = $_GET['reference'] ?? null;
if (!$reference) {
    die("Invalid callback: no reference provided.");
}

// ✅ Verify payment with Paystack
$ch = curl_init("https://api.paystack.co/transaction/verify/" . $reference);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $paystack_sk"]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if (!$result || !$result['status'] || $result['data']['status'] !== 'success') {
    die("Payment not verified.");
}

// ✅ Load orders.json
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];
if (!isset($orders[$reference])) {
    die("Order not found.");
}
$order = $orders[$reference];

// ✅ Call Tiger-SMS getNumber
$service = $order['service'];
$country = $order['country'];

$url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getNumber&service=$service&country=$country";
$resp = @file_get_contents($url);

if (!$resp || !str_starts_with($resp, "ACCESS_NUMBER")) {
    // Handle NO_BALANCE or other error
    $orders[$reference]['status'] = "failed";
    $orders[$reference]['error'] = $resp;
    file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));

    ?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Order Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow rounded p-6 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4">⚠️ Order Failed</h2>
        <p class="mb-2">We could not buy your number.</p>
        <p class="text-red-600 font-mono"><?=htmlspecialchars($resp)?></p>
        <p class="mt-4 text-sm text-gray-600">Please contact support or try again.</p>
        <a href="dashboard.php" class="mt-6 inline-block bg-blue-600 text-white px-4 py-2 rounded">Go to Dashboard</a>
    </div>
</body>

</html>
<?php
    exit;
}

// ✅ Success
list(, $activation_id, $phone_number) = explode(":", $resp);

$orders[$reference]['status'] = "paid";
$orders[$reference]['activation_id'] = $activation_id;
$orders[$reference]['phone'] = $phone_number;

file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Order Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow rounded p-6 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4">✅ Payment Verified</h2>
        <p class="mb-2">Your virtual number:</p>
        <p class="text-xl font-mono mb-4"><?=$phone_number?></p>
        <a href="getcode.php?activation_id=<?=$activation_id?>" class="bg-blue-600 text-white px-4 py-2 rounded">Get
            Code</a>
        <br><br>
        <a href="dashboard.php" class="text-blue-500 underline">Go to Dashboard</a>
    </div>
</body>

</html>