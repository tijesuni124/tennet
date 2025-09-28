<?php
session_start();
require_once "db.php"; // your DB connection (PDO or mysqli)

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
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
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow rounded p-8 w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">🔐 Login</h2>

        <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <?=htmlspecialchars($error)?>
        </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block mb-1 text-sm">Email</label>
                <input type="email" name="email" required
                    class="w-full border rounded p-2 focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-1 text-sm">Password</label>
                <input type="password" name="password" required
                    class="w-full border rounded p-2 focus:ring focus:ring-blue-300">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Login
            </button>
        </form>

        <p class="text-sm text-center mt-4">
            Don’t have an account? <a href="signup.php" class="text-blue-600 hover:underline">Sign Up</a>
        </p>
    </div>
</body>

</html>