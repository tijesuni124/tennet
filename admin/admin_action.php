<?php
// admin_action.php
session_start();
require_once __DIR__ . '/../db.php'; // ✅ fixed path

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$action = $_POST['action'] ?? null;
$ref = $_POST['ref'] ?? null;
$source = $_POST['source'] ?? null; // optional: 'json' or 'db'

$orders_file = __DIR__ . '/../orders.json'; // ✅ fixed path
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];

if ($action === 'mark_used' && $ref) {
    // Try JSON first
    if (isset($orders[$ref])) {
        $orders[$ref]['status'] = 'used';
        file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit;
    }

    // Try DB
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'used' WHERE reference = ?");
        $stmt->execute([$ref]);
    } catch (Exception $e) {
        // ignore
    }
    header("Location: admin_dashboard.php");
    exit;
}

if ($action === 'delete' && $ref) {
    // If source specified and is json, remove from JSON only
    if ($source === 'json' && isset($orders[$ref])) {
        unset($orders[$ref]);
        file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit;
    }

    // Try delete from JSON (if exists)
    if (isset($orders[$ref])) {
        unset($orders[$ref]);
        file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
    }

    // Try delete from DB
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE reference = ?");
        $stmt->execute([$ref]);
    } catch (Exception $e) {
        // ignore
    }

    header("Location: admin_dashboard.php");
    exit;
}

if ($action === 'export_csv') {
    // Prepare CSV from both sources
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export_'.date('Ymd_His').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['source','ref','user_id','user_email','service','country','phone','activation_id','amount','status','created_at']);

    // JSON orders
    foreach ($orders as $refKey => $o) {
        fputcsv($out, [
            'json',
            $refKey,
            $o['user_id'] ?? '',
            $o['email'] ?? '',
            $o['service'] ?? '',
            $o['country'] ?? '',
            $o['phone'] ?? ($o['phone_number'] ?? ''),
            $o['activation_id'] ?? '',
            $o['amount'] ?? $o['naira_price'] ?? '',
            $o['status'] ?? '',
            $o['created_at'] ?? ''
        ]);
    }

    // DB orders
    try {
        $stmt = $pdo->query("SELECT o.*, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id=u.id");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                'db',
                $r['reference'] ?? '',
                $r['user_id'] ?? '',
                $r['user_email'] ?? '',
                $r['service'] ?? '',
                $r['country'] ?? '',
                $r['phone'] ?? ($r['phone_number'] ?? ''),
                $r['activation_id'] ?? '',
                $r['amount'] ?? $r['naira_price'] ?? '',
                $r['status'] ?? '',
                $r['created_at'] ?? ''
            ]);
        }
    } catch (Exception $e) {
        // skip if table doesn't exist
    }

    fclose($out);
    exit;
}

header("Location: admin_dashboard.php");
exit;