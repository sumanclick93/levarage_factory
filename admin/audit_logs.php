<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Fetch Logs with Admin Names
// Fetch Logs with Admin Names - Updated to use 'email' or 'full_name'
$query = "SELECT l.*, a.email as admin_name 
          FROM admin_activity_logs l 
          JOIN admins a ON l.admin_id = a.id 
          ORDER BY l.created_at DESC 
          LIMIT 500";
$logs = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">System Audit Logs</h1>
            <p class="text-gray-500">A permanent record of all administrative actions for regulatory compliance.</p>
        </header>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                    <tr>
                        <th class="py-4 px-6">Timestamp</th>
                        <th class="py-4 px-6">Administrator</th>
                        <th class="py-4 px-6">Action Performed</th>
                        <th class="py-4 px-6">Target User ID</th>
                        <th class="py-4 px-6 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="py-4 px-6 text-xs font-mono text-gray-500">
                            <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-bold text-gray-900"><?php echo $log['admin_name']; ?></span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-sm text-gray-700 font-medium"><?php echo $log['action']; ?></div>
                        </td>
                        <td class="py-4 px-6">
                            <?php if($log['target_user_id']): ?>
                                <a href="view_user.php?id=<?php echo $log['target_user_id']; ?>" class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-[10px] font-bold hover:bg-blue-100 hover:text-blue-700 transition">USER #<?php echo $log['target_user_id']; ?></a>
                            <?php else: ?>
                                <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right text-xs font-mono text-gray-400">
                            <?php echo $log['ip_address']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex items-center justify-between text-xs text-gray-400">
            <p>Showing last 500 administrative actions.</p>
            <p>Log data is read-only and cannot be modified by administrators.</p>
        </div>
    </main>
</body>
</html>