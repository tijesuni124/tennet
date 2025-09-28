<?php
session_start();
$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";
$paystack_pk = "pk_test_fb8fe2bbca8e9e76f05bcf2a73bb55c624dfb688";

// Read POST
$service = $_POST['service'] ?? null;
$country = $_POST['country'] ?? null;
$email   = $_POST['email'] ?? null;

if (!$service || !$country || !$email) {
    die("Invalid request.");
}

// Get Prices from Tiger-SMS
$prices_url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getPrices&service=$service&country=$country";
$prices = json_decode(@file_get_contents($prices_url), true);

if (!$prices || !isset($prices[$country][$service]['cost'])) {
    die("Could not fetch price from Tiger-SMS.");
}

$usd_price = floatval($prices[$country][$service]['cost']);
$rate = 1500; // 1 USD = 1500 NGN (example)
$price_naira = round($usd_price * $rate) + 1000; // add ₦1000 margin

// Create unique reference
$reference = uniqid("ORD");

// Save pending order to orders.json
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];
$orders[$reference] = [
    "user_id" => $_SESSION['user_id'] ?? null,
    "service" => $service,
    "country" => $country,
    "email" => $email,
    "amount" => $price_naira,
    "status" => "pending",
    "created_at" => date("Y-m-d H:i:s")
];
file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Redirecting to Paystack...</title>
    <script src="https://js.paystack.co/v1/inline.js"></script>
</head>

<body>
    <script>
    var handler = PaystackPop.setup({
        key: "<?=$paystack_pk?>",
        email: "<?=$email?>",
        amount: <?=$price_naira * 100?>, // Paystack expects kobo
        currency: "NGN",
        ref: "<?=$reference?>",
        callback: function(response) {
            window.location = "callback.php?reference=" + response.reference;
        },
        onClose: function() {
            alert("Transaction cancelled");
            window.location = "order.php";
        }
    });
    handler.openIframe();
    </script>
</body>

</html>