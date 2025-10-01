<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";

function tiger_api($action, $params = []) {
    global $tiger_key;
    $url = "https://api.tiger-sms.com/stubs/handler_api.php?action=$action&api_key=$tiger_key";

    foreach ($params as $k => $v) {
        $url .= "&$k=" . urlencode($v);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);

    if ($resp === false) {
        return null; // request failed
    }

    curl_close($ch);
    return json_decode($resp, true);
}

// ✅ Try to fetch from API
$countries = tiger_api("getCountries");
$services  = tiger_api("getServices");
$prices    = tiger_api("getPrices");

// ✅ Fallback if API fails
if (!$countries) {
    $countries = [
        "6" => "Nigeria",
        "7" => "USA",
        "0" => "Other"
    ];
}
if (!$services) {
    $services = [
        "wa" => "WhatsApp",
        "tg" => "Telegram",
        "go" => "Gmail",
        "fb" => "Facebook"
    ];
}
?>
<!doctype html>
<html lang="en" class="bg-gray-100">

<head>
    <meta charset="utf-8">
    <title>Create Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold mb-6">Choose Service & Country</h2>

        <form method="post" action="pay.php" class="space-y-6">
            <!-- Country -->
            <div>
                <h3 class="font-semibold mb-2">Select Country</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    <?php foreach ($countries as $id => $name): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="country" value="<?=$id?>" class="hidden peer" required>
                        <div
                            class="border rounded-lg p-3 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <span class="text-sm"><?=$name?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Service -->
            <div>
                <h3 class="font-semibold mb-2">Select Service</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    <?php foreach ($services as $code => $name): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="service" value="<?=$code?>" class="hidden peer" required>
                        <div
                            class="border rounded-lg p-3 text-center peer-checked:border-green-500 peer-checked:bg-green-50">
                            <span class="text-sm"><?=$name?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
            </div>

            <!-- Submit -->
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Proceed to Payment
            </button>
        </form>
    </div>
</body>

</html>