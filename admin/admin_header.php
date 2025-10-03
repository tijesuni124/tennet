<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-800 text-white px-6 py-3 flex justify-between items-center">
        <div class="text-lg font-bold">⚙️ Admin Panel</div>
        <div class="space-x-4">
            <a href="admin_dashboard.php" class="hover:underline">Dashboard</a>
            <a href="admin_action.php?action=export_csv" class="hover:underline">Export CSV</a>
            <a href="../logout.php" class="hover:underline text-red-400">Logout</a>
        </div>
    </nav>
    <div class="p-6">