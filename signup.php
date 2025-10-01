<?php
require_once __DIR__ . "/db.php";
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Email already registered.";
    } else {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Always create with role = user
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'user')");
        $stmt->execute([$email, $hashedPassword]);

        // Auto-login after signup
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = 'user';
        $_SESSION['email'] = $email;

        header("Location: dashboard.php");
        exit;
    }
}
?>
<!doctype html>
<html class="bg-gray-100">

<head>
    <meta charset="utf-8">
    <title>Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Create Account</h2>

        <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-3 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="email" name="email" placeholder="Email" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
            <input type="password" name="password" placeholder="Password" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Sign Up</button>
        </form>

        <p class="mt-4 text-center text-gray-600">Already have an account?
            <a href="login.php" class="text-blue-600">Login</a>
        </p>
    </div>
</body>

</html>