<?php
$tiger_key = "dxUecc9xfg1CyKWlPDJRM2C21ywm5Aii";

$activation_id = $_GET['activation_id'] ?? null;
if (!$activation_id) {
    die("No activation ID provided.");
}

$url = "https://api.tiger-sms.com/stubs/handler_api.php?api_key=$tiger_key&action=getStatus&id=$activation_id";
$resp = @file_get_contents($url);

$status_msg = htmlspecialchars($resp);
$code = null;

if (str_starts_with($resp, "STATUS_OK")) {
    // STATUS_OK:1234
    list(, $code) = explode(":", $resp);
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Check Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow rounded p-6 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4">📩 SMS Status</h2>
        <?php if ($code): ?>
        <p class="mb-2">Your verification code:</p>
        <p class="text-2xl font-mono"><?=$code?></p>
        <?php else: ?>
        <p class="mb-2">Status:</p>
        <p class="text-gray-700"><?=$status_msg?></p>
        <a href="getcode.php?activation_id=<?=$activation_id?>"
            class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded">Refresh</a>
        <?php endif; ?>
    </div>
</body>

</html>