<?php
session_start();
require_once __DIR__ . "/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Save session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // ✅ Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin/admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!doctype html>
<html class="bg-gray-100">

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-3 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <input type="email" name="email" placeholder="Email" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
            <input type="password" name="password" placeholder="Password" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
        </form>
        <p class="mt-4 text-center text-gray-600">Don't have an account?
            <a href="signup.php" class="text-blue-600">Sign Up</a>
        </p>
    </div>
</body>

</html>