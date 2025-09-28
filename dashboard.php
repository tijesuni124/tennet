<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";

// Fetch Countries + Services
$countries = json_decode(@file_get_contents("https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getCountries"), true) ?: [];
$services  = json_decode(@file_get_contents("https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getServices"), true) ?: [];

// Load orders
$orders_file = __DIR__ . "/orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];
$user_orders = array_filter($orders, fn($o) => $o['user_id'] == $_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <div class="bg-white shadow p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">📱 My Dashboard</h1>
        <div class="flex items-center gap-4">
            <span class="text-gray-600"> <?=htmlspecialchars($_SESSION['email'])?></span>
            <a href="order.php" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">New Order</a>
            <a href="logout.php" class="ml-3 text-red-600 hover:underline">Logout</a>
        </div>
    </div>

    <!-- Orders -->
    <div class="max-w-6xl mx-auto p-6">
        <h2 class="text-2xl font-semibold mb-4">My Orders</h2>
        <?php if (empty($user_orders)): ?>
        <p class="text-gray-600">No orders yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white shadow rounded">
                <thead class="bg-gray-200 text-left">
                    <tr>
                        <th class="py-2 px-4">Ref</th>
                        <th class="py-2 px-4">Service</th>
                        <th class="py-2 px-4">Country</th>
                        <th class="py-2 px-4">Phone</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_orders as $ref => $o): ?>
                    <tr class="border-t">
                        <td class="py-2 px-4 font-mono"><?=$ref?></td>
                        <td class="py-2 px-4"><?=$services[$o['service']] ?? $o['service']?></td>
                        <td class="py-2 px-4"><?=$countries[$o['country']] ?? $o['country']?></td>
                        <td class="py-2 px-4"><?=$o['phone'] ?? '-'?></td>
                        <td class="py-2 px-4">
                            <?php if ($o['status']==='paid'): ?>
                            <span class="text-green-600">Paid</span>
                            <?php elseif ($o['status']==='failed'): ?>
                            <span class="text-red-600">Failed</span>
                            <?php else: ?>
                            <span class="text-yellow-600"><?=ucfirst($o['status'])?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4">
                            <?php if (!empty($o['activation_id']) && $o['status']==='paid'): ?>
                            <a href="getcode.php?activation_id=<?=$o['activation_id']?>"
                                class="bg-blue-600 text-white px-3 py-1 rounded">Get Code</a>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>

</html>