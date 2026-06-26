<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-200 p-8">
        <div class="flex items-center justify-center gap-2 mb-8">
            <svg width="40" height="40" viewBox="0 0 48 48" fill="none">
                <rect x="13" y="24" width="6" height="16" rx="2" fill="#E63946"/>
                <rect x="21" y="28" width="6" height="12" rx="2" fill="#D62828"/>
            </svg>
            <div class="flex flex-col">
                <span class="text-xl font-bold tracking-wider text-gray-900">ADMIN</span>
                <span class="text-xs font-light text-gray-500 tracking-widest -mt-1">SECURE ACCESS</span>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2">Management Portal</h2>
        <p class="text-gray-500 text-center mb-8 text-sm">Please enter your credentials to manage the factory.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700 text-sm">
                    <?php 
                        if($_GET['error'] == 'invalid_credentials') echo "Invalid email or password.";
                        else echo "An error occurred. Please try again.";
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                    placeholder="admin@leveragefactory.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                    placeholder="••••••••">
            </div>

            <!--<div class="flex items-center justify-between">-->
            <!--    <div class="flex items-center">-->
            <!--        <input id="remember-me" type="checkbox" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">-->
            <!--        <label for="remember-me" class="ml-2 block text-sm text-gray-600">Remember me</label>-->
            <!--    </div>-->
            <!--    <a href="#" class="text-sm font-medium text-red-600 hover:text-red-500">Forgot Password?</a>-->
            <!--</div>-->

            <button type="submit" 
                class="w-full bg-[#E63946] hover:bg-[#D62828] text-white font-bold py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300 transform active:scale-95">
                Sign In to Dashboard
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">
                &copy; 2026 Leverage Factory Malta
            </p>
        </div>
    </div>

</body>
</html>