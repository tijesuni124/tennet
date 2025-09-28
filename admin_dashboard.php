<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$orders_file = __DIR__ . '/orders.json';
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];

// DB orders
$db_orders = [];
try {
    $stmt = $pdo->query("SELECT o.*, u.email as user_email 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id=u.id 
                         ORDER BY o.created_at DESC");
    $db_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // table may not exist yet
}

// Merge sources
$all_orders = [];
foreach ($orders as $ref => $o) {
    $o['source'] = 'json';
    $o['reference'] = $ref;
    $all_orders[] = $o;
}
foreach ($db_orders as $o) {
    $o['source'] = 'db';
    $all_orders[] = $o;
}

// Filters
$q_email   = strtolower(trim($_GET['email'] ?? ''));
$q_service = strtolower(trim($_GET['service'] ?? ''));
$q_status  = strtolower(trim($_GET['status'] ?? ''));
$q_from    = $_GET['from'] ?? '';
$q_to      = $_GET['to'] ?? '';

$filtered_orders = array_filter($all_orders, function ($o) use ($q_email, $q_service, $q_status, $q_from, $q_to) {
    if ($q_email && stripos($o['email'] ?? ($o['user_email'] ?? ''), $q_email) === false) return false;
    if ($q_service && strtolower($o['service'] ?? '') !== $q_service) return false;
    if ($q_status && strtolower($o['status'] ?? '') !== $q_status) return false;

    if ($q_from && strtotime($o['created_at'] ?? 'now') < strtotime($q_from)) return false;
    if ($q_to && strtotime($o['created_at'] ?? 'now') > strtotime($q_to . " 23:59:59")) return false;

    return true;
});

// Stats
$total_orders = count($all_orders);
$paid_orders = count(array_filter($all_orders, fn($o) => $o['status']==='paid'));
$pending_orders = count(array_filter($all_orders, fn($o) => $o['status']==='pending'));
$failed_orders = count(array_filter($all_orders, fn($o) => $o['status']==='failed'));
$used_orders = count(array_filter($all_orders, fn($o) => $o['status']==='used'));
$revenue = array_sum(array_map(fn($o) => ($o['status']==='paid') ? ($o['amount'] ?? 0) : 0, $all_orders));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <div class="bg-white shadow p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">🛠 Admin Dashboard</h1>
        <div>
            <a href="admin_action.php?action=export_csv" class="bg-green-600 text-white px-3 py-1 rounded">Export
                CSV</a>
            <a href="logout.php" class="ml-4 text-red-600">Logout</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-5 gap-4 max-w-6xl mx-auto mt-6">
        <div class="bg-white shadow p-4 rounded text-center">
            <h2 class="text-2xl font-bold"><?=$total_orders?></h2>
            <p>Total</p>
        </div>
        <div class="bg-yellow-100 shadow p-4 rounded text-center">
            <h2 class="text-2xl font-bold"><?=$pending_orders?></h2>
            <p>Pending</p>
        </div>
        <div class="bg-green-100 shadow p-4 rounded text-center">
            <h2 class="text-2xl font-bold"><?=$paid_orders?></h2>
            <p>Paid</p>
        </div>
        <div class="bg-red-100 shadow p-4 rounded text-center">
            <h2 class="text-2xl font-bold"><?=$failed_orders?></h2>
            <p>Failed</p>
        </div>
        <div class="bg-gray-200 shadow p-4 rounded text-center">
            <h2 class="text-2xl font-bold"><?=$used_orders?></h2>
            <p>Used</p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto mt-4 bg-white shadow rounded p-4 text-center">
        <h2 class="text-xl font-bold">💰 Revenue: ₦<?=number_format($revenue)?></h2>
    </div>

    <!-- Filters -->
    <div class="max-w-6xl mx-auto mt-6 bg-white shadow rounded p-4">
        <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm">Email</label>
                <input type="text" name="email" value="<?=htmlspecialchars($q_email)?>"
                    class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm">Service</label>
                <input type="text" name="service" value="<?=htmlspecialchars($q_service)?>"
                    class="w-full border rounded p-2" placeholder="wa, tg, gm">
            </div>
            <div>
                <label class="block text-sm">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">All</option>
                    <option value="pending" <?=$q_status==='pending'?'selected':''?>>Pending</option>
                    <option value="paid" <?=$q_status==='paid'?'selected':''?>>Paid</option>
                    <option value="failed" <?=$q_status==='failed'?'selected':''?>>Failed</option>
                    <option value="used" <?=$q_status==='used'?'selected':''?>>Used</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">From</label>
                <input type="date" name="from" value="<?=htmlspecialchars($q_from)?>" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm">To</label>
                <input type="date" name="to" value="<?=htmlspecialchars($q_to)?>" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-5 flex gap-3 mt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
                <a href="admin_dashboard.php" class="bg-gray-300 px-4 py-2 rounded">Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="max-w-6xl mx-auto mt-6 bg-white shadow rounded p-4 overflow-x-auto">
        <h2 class="text-xl font-bold mb-4">Orders</h2>
        <?php if (empty($filtered_orders)): ?>
        <p class="text-gray-600">No matching orders found.</p>
        <?php else: ?>
        <table class="min-w-full">
            <thead class="bg-gray-200 text-left">
                <tr>
                    <th class="py-2 px-4">Ref</th>
                    <th class="py-2 px-4">Source</th>
                    <th class="py-2 px-4">Email</th>
                    <th class="py-2 px-4">Service</th>
                    <th class="py-2 px-4">Country</th>
                    <th class="py-2 px-4">Phone</th>
                    <th class="py-2 px-4">Amount</th>
                    <th class="py-2 px-4">Status</th>
                    <th class="py-2 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_orders as $o): ?>
                <tr class="border-t">
                    <td class="py-2 px-4 font-mono"><?=$o['reference'] ?? ''?></td>
                    <td class="py-2 px-4"><?=$o['source']?></td>
                    <td class="py-2 px-4"><?=$o['email'] ?? ($o['user_email'] ?? '-')?></td>
                    <td class="py-2 px-4"><?=$o['service'] ?? '-'?></td>
                    <td class="py-2 px-4"><?=$o['country'] ?? '-'?></td>
                    <td class="py-2 px-4"><?=$o['phone'] ?? ($o['phone_number'] ?? '-')?></td>
                    <td class="py-2 px-4">₦<?=number_format($o['amount'] ?? $o['naira_price'] ?? 0)?></td>
                    <td class="py-2 px-4">
                        <?php if (($o['status'] ?? '')==='paid'): ?>
                        <span class="text-green-600">Paid</span>
                        <?php elseif (($o['status'] ?? '')==='failed'): ?>
                        <span class="text-red-600">Failed</span>
                        <?php elseif (($o['status'] ?? '')==='used'): ?>
                        <span class="text-gray-600">Used</span>
                        <?php else: ?>
                        <span class="text-yellow-600">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-4">
                        <form method="post" action="admin_action.php" class="inline">
                            <input type="hidden" name="ref" value="<?=$o['reference']?>">
                            <input type="hidden" name="source" value="<?=$o['source']?>">
                            <button type="submit" name="action" value="mark_used"
                                class="text-blue-600 hover:underline">Mark Used</button>
                        </form>
                        <form method="post" action="admin_action.php" class="inline ml-2">
                            <input type="hidden" name="ref" value="<?=$o['reference']?>">
                            <input type="hidden" name="source" value="<?=$o['source']?>">
                            <button type="submit" name="action" value="delete"
                                class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>

</html>