<?php
require_once __DIR__ . "/admin_header.php";

$orders_file = __DIR__ . "/../orders.json";
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];

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
function order_amount($o) {
    return $o['amount'] ?? ($o['naira_price'] ?? 0);
}

// Filters
$status_filter = $_GET['status'] ?? '';
$service_filter = $_GET['service'] ?? '';
$country_filter = $_GET['country'] ?? '';

$filtered_orders = array_filter($orders, function ($o) use ($status_filter, $service_filter, $country_filter) {
    if ($status_filter && ($o['status'] ?? '') !== $status_filter) return false;
    if ($service_filter && ($o['service'] ?? '') !== $service_filter) return false;
    if ($country_filter && ($o['country'] ?? '') !== $country_filter) return false;
    return true;
});

// Stats
$total_orders = count($orders);
$pending = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$active = count(array_filter($orders, fn($o) => $o['status'] === 'active'));
$failed = count(array_filter($orders, fn($o) => str_starts_with($o['status'], 'paid_but')));
$total_revenue = array_sum(array_map('order_amount', $orders));
?>

<h1 class="text-2xl font-bold mb-6">📊 Admin Dashboard</h1>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow">
        <p>Total Orders</p>
        <p class="text-xl font-bold"><?= $total_orders ?></p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p>Pending</p>
        <p class="text-xl font-bold text-yellow-600"><?= $pending ?></p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p>Active</p>
        <p class="text-xl font-bold text-green-600"><?= $active ?></p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p>Revenue</p>
        <p class="text-xl font-bold text-blue-600">₦<?= number_format($total_revenue) ?></p>
    </div>
</div>

<!-- Filters -->
<form method="get" class="flex flex-wrap gap-2 mb-6">
    <select name="status" class="px-3 py-2 border rounded">
        <option value="">All Status</option>
        <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
        <option value="active" <?= $status_filter==='active'?'selected':'' ?>>Active</option>
        <option value="used" <?= $status_filter==='used'?'selected':'' ?>>Used</option>
    </select>
    <input type="text" name="service" value="<?= htmlspecialchars($service_filter) ?>" placeholder="Service"
        class="px-3 py-2 border rounded">
    <input type="text" name="country" value="<?= htmlspecialchars($country_filter) ?>" placeholder="Country"
        class="px-3 py-2 border rounded">
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
    <a href="admin_dashboard.php" class="px-3 py-2 border rounded text-gray-600">Reset</a>
    <a href="admin_action.php?action=export_csv" class="px-3 py-2 bg-green-600 text-white rounded">Export CSV</a>
</form>

<!-- Orders Table -->
<div class="overflow-x-auto">
    <table class="min-w-full bg-white border border-gray-200 rounded shadow-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2">Ref</th>
                <th class="px-4 py-2">User</th>
                <th class="px-4 py-2">Service</th>
                <th class="px-4 py-2">Country</th>
                <th class="px-4 py-2">Amount</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Phone</th>
                <th class="px-4 py-2">Created</th>
                <th class="px-4 py-2">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($filtered_orders): ?>
            <?php foreach ($filtered_orders as $ref => $o): ?>
            <tr class="border-t">
                <td class="px-4 py-2"><?= htmlspecialchars($ref) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($o['email'] ?? '') ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars(service_name($o['service'], $services)) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars(country_name($o['country'], $countries)) ?></td>
                <td class="px-4 py-2">₦<?= number_format(order_amount($o)) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($o['status']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($o['phone'] ?? '-') ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($o['created_at']) ?></td>
                <td class="px-4 py-2">
                    <form method="post" action="admin_action.php" class="flex gap-2">
                        <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
                        <button name="action" value="mark_used"
                            class="bg-yellow-500 text-white px-2 py-1 rounded text-sm">Mark Used</button>
                        <button name="action" value="delete"
                            class="bg-red-600 text-white px-2 py-1 rounded text-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="9" class="px-4 py-6 text-center text-gray-500">No orders found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . "/admin_footer.php"; ?>