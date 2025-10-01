<?php
session_start();
require_once __DIR__ . "/config.php";  // for keys & margin
require_once __DIR__ . "/helpers.php"; // for conversion

$tiger_key   = TIGER_KEY;
$paystack_pk = PAYSTACK_PK;

// Read POST
$service = $_POST['service'] ?? null;
$country = $_POST['country'] ?? null;
$email   = $_POST['email'] ?? null;

if (!$service || !$country || !$email) {
    die("⚠️ Invalid request.");
}

// Get Prices from Tiger-SMS (in RUB)
$prices_url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getPrices&service=$service&country=$country";
$prices = json_decode(@file_get_contents($prices_url), true);

if (!$prices || !isset($prices[$country][$service]['cost'])) {
    die("⚠️ Could not fetch price from Tiger-SMS.");
}

$rub_price = floatval($prices[$country][$service]['cost']);
$ngn_price = convertRubToNgn($rub_price) + PROFIT_MARGIN_NGN; // conversion + margin

// Create unique reference
$reference = uniqid("ORD");

// Save pending order to orders.json
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];
$orders[$reference] = [
    "user_id"       => $_SESSION['user_id'] ?? null,
    "service"       => $service,
    "country"       => $country,
    "email"         => $email,
    "rub_price"     => $rub_price,
    "ngn_price"     => $ngn_price,
    "status"        => "pending",
    "created_at"    => date("Y-m-d H:i:s")
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
        amount: <?=$ngn_price * 100?>, // Paystack expects kobo
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