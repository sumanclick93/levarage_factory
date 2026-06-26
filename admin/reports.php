<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// 1. Total Deposits (Only Active/Approved)
$total_deposits = $pdo->query("SELECT SUM(amount) FROM investments WHERE status = 'active'")->fetchColumn() ?: 0;

// 2. Total Payouts (Approved Withdrawals)
$total_payouts = $pdo->query("SELECT SUM(amount) FROM withdrawal_requests WHERE status = 'approved'")->fetchColumn() ?: 0;

// 3. Pending Withdrawals (Liability)
$pending_withdrawals = $pdo->query("SELECT SUM(amount) FROM withdrawal_requests WHERE status = 'pending'")->fetchColumn() ?: 0;

// 4. User Wallet Balances (Total system liability)
$system_liability = $pdo->query("SELECT SUM(wallet_balance) FROM users")->fetchColumn() ?: 0;

$net_profit = $total_deposits - $total_payouts;

// Fetch popularity stats by scheme
$scheme_stats = $pdo->query("
    SELECT s.name,s.min_amount,COUNT(i.id) as total_subscriptions,SUM(i.amount) as total_volume FROM investment_schemes s LEFT JOIN investments i ON s.id = i.scheme_id AND i.status = 'active' GROUP BY s.id ORDER BY total_subscriptions DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Financial Overview</h1>
            <p class="text-gray-500">Real-time stats of deposits, payouts, and platform health.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Deposits</p>
                <p class="text-2xl font-black text-green-600 mt-2">$<?php echo number_format($total_deposits, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Payouts</p>
                <p class="text-2xl font-black text-red-500 mt-2">$<?php echo number_format($total_payouts, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Net Profit (Cash on Hand)</p>
                <p class="text-2xl font-black text-blue-600 mt-2">$<?php echo number_format($net_profit, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">System Liability</p>
                <p class="text-2xl font-black text-orange-500 mt-2">$<?php echo number_format($system_liability, 2); ?></p>
            </div>
        </div>
        
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Scheme Popularity Report</h2>
            
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                        <tr>
                            <th class="py-4 px-6">Scheme Name</th>
                            <th class="py-4 px-6">Entry Price</th>
                            <th class="py-4 px-6 text-center">Active Subscriptions</th>
                            <th class="py-4 px-6 text-right">Total Volume</th>
                            <th class="py-4 px-6 text-center">Market Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($scheme_stats as $stat): 
                            // Calculate percentage of total volume for market share
                            $share = ($total_deposits > 0) ? ($stat['total_volume'] / $total_deposits) * 100 : 0;
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-4 px-6 font-bold text-gray-900"><?php echo $stat['name']; ?></td>
                            <td class="py-4 px-6 text-gray-600 font-mono">$<?php echo number_format($stat['min_amount'], 0); ?></td>
                            <td class="py-4 px-6 text-center">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">
                                    <?php echo $stat['total_subscriptions']; ?> Users
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-bold text-gray-900">
                                $<?php echo number_format($stat['total_volume'], 2); ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="w-full bg-gray-100 rounded-full h-2 max-w-[100px] mx-auto">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $share; ?>%"></div>
                                </div>
                                <p class="text-[10px] text-center mt-1 font-bold text-gray-400"><?php echo round($share, 1); ?>%</p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </br>
        <div class="bg-blue-50 border border-blue-200 p-6 rounded-xl">
            <h3 class="font-bold text-blue-800 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Understanding Your Data
            </h3>
            <p class="text-sm text-blue-700 mt-2 leading-relaxed">
                <strong>Net Profit</strong> represents the actual funds remaining in your master wallet after all processed payouts. 
                <strong>System Liability</strong> is the sum of all users' current balances; this is money that users may request to withdraw in the future.
            </p>
        </div>
    </main>
</body>
</html>