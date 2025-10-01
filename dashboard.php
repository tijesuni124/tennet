<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';

// Load orders
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];

// Filter this user's orders
$user_orders = array_filter($orders, function ($o) use ($user_id) {
    return ($o['user_id'] ?? null) == $user_id;
});

// ✅ Fetch services & countries from Tiger-SMS
$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";

// Services
$services_url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getServices";
$services = json_decode(@file_get_contents($services_url), true);
if (!$services) $services = [];

// Countries
$countries_url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getCountries";
$countries = json_decode(@file_get_contents($countries_url), true);
if (!$countries) $countries = [];

// Helpers
function service_name($code, $services) {
    return $services[$code] ?? strtoupper($code);
}
function country_name($id, $countries) {
    return $countries[$id] ?? $id;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto py-10 px-4">
        <h1 class="text-2xl font-bold mb-6">👤 Welcome, <?= htmlspecialchars($user_email) ?></h1>

        <h2 class="text-xl font-semibold mb-4">📦 My Orders</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Reference</th>
                        <th class="px-4 py-2 text-left">Service</th>
                        <th class="px-4 py-2 text-left">Country</th>
                        <th class="px-4 py-2 text-left">Amount (₦)</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Phone / Code</th>
                        <th class="px-4 py-2 text-left">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($user_orders): ?>
                    <?php foreach ($user_orders as $ref => $o): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2"><?= htmlspecialchars($ref) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars(service_name($o['service'], $services)) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars(country_name($o['country'], $countries)) ?></td>
                        <td class="px-4 py-2">₦<?= number_format($o['amount']) ?></td>
                        <td class="px-4 py-2">
                            <?php if ($o['status'] === 'active'): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">Active</span>
                            <?php elseif ($o['status'] === 'pending'): ?>
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm">Pending</span>
                            <?php elseif ($o['status'] === 'used'): ?>
                            <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm">Used</span>
                            <?php else: ?>
                            <span
                                class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm"><?= htmlspecialchars($o['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2">
                            <?php if (!empty($o['phone'])): ?>
                            📱 <?= htmlspecialchars($o['phone']) ?><br>
                            <a href="get_code.php?activation_id=<?= urlencode($o['activation_id']) ?>"
                                class="text-blue-600 underline text-sm">Get Code</a>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2"><?= htmlspecialchars($o['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">No orders yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-between">
            <a href="order.php" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">➕ New
                Order</a>
            <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700">🚪 Logout</a>
        </div>
    </div>
</body>

</html>