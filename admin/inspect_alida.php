<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

$username = 'alida';

// 1. Fetch User details
$stmt = $pdo->prepare("SELECT id, username, email, wallet_balance, corporate_wallet, referrer_id, path, created_at, is_locked FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user) {
    echo "<h1>User '$username' not found in the database.</h1>";
    exit();
}

$uid = $user['id'];

// 2. Fetch Active Investments
$stmt = $pdo->prepare("
    SELECT i.id, i.amount, i.hash_ref, i.created_at, s.name as scheme_name, s.type as scheme_type, s.total_return_percent, s.duration_days 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? AND i.status = 'active'
");
$stmt->execute([$uid]);
$investments = $stmt->fetchAll();

// 3. Fetch recent Daily ROI transactions (last 7 days)
$stmt = $pdo->prepare("
    SELECT id, amount, description, created_at 
    FROM transactions 
    WHERE user_id = ? AND type = 'roi' 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$uid]);
$roi_txs = $stmt->fetchAll();

// 4. Fetch recent Residual transactions (last 7 days)
$stmt = $pdo->prepare("
    SELECT id, amount, description, created_at 
    FROM transactions 
    WHERE user_id = ? AND type = 'referral_bonus' AND description LIKE '%Residual%'
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$uid]);
$residual_txs = $stmt->fetchAll();

// 5. Calculate daily stats (last 24 hours)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi' AND created_at >= NOW() - INTERVAL 1 DAY");
$stmt->execute([$uid]);
$last_24h_roi = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_bonus' AND description LIKE '%Residual%' AND created_at >= NOW() - INTERVAL 1 DAY");
$stmt->execute([$uid]);
$last_24h_residuals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_bonus' AND description LIKE '%Referral Bonus from%' AND created_at >= NOW() - INTERVAL 1 DAY");
$stmt->execute([$uid]);
$last_24h_faststart = $stmt->fetchColumn() ?: 0;

// 6. Check count of downlines active in MLM tree
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE path LIKE ? OR referrer_id = ?");
$stmt->execute(["%/" . $uid . "%", $uid]);
$downlines_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic: User 'alida' | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background: #F8FAFC; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-6xl mx-auto space-y-8">
        <header class="flex justify-between items-center pb-6 border-b border-gray-200">
            <div>
                <p class="text-xs font-bold text-[#00A6FB] uppercase tracking-widest">Diagnostic_Protocol</p>
                <h1 class="text-3xl font-black uppercase text-gray-900">User node: <?php echo htmlspecialchars($user['username']); ?> (UID: <?php echo $user['id']; ?>)</h1>
            </div>
            <span class="px-4 py-2 bg-emerald-100 text-emerald-800 font-bold uppercase tracking-wider text-xs rounded-full">
                <?php echo $user['is_locked'] ? 'Account Locked' : 'Active Account'; ?>
            </span>
        </header>

        <!-- Earnings Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Wallet Balance</p>
                <p class="text-2xl font-black text-gray-900 mt-2">$<?php echo number_format($user['wallet_balance'], 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last 24h Daily ROI</p>
                <p class="text-2xl font-black text-indigo-600 mt-2">$<?php echo number_format($last_24h_roi, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last 24h Residuals</p>
                <p class="text-2xl font-black text-emerald-600 mt-2">$<?php echo number_format($last_24h_residuals, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Downline Members</p>
                <p class="text-2xl font-black text-purple-600 mt-2"><?php echo $downlines_count; ?> Nodes</p>
            </div>
        </div>

        <!-- Section 1: Active Investments -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
            <h3 class="text-lg font-black uppercase text-gray-900 mb-4 border-b border-gray-50 pb-2">Active Investments & Expected ROI Payouts</h3>
            <?php if (empty($investments)): ?>
                <p class="text-sm font-medium text-gray-400 uppercase tracking-wider py-4">No active investments found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-400 uppercase tracking-wider text-xs border-b border-gray-100">
                                <th class="pb-3">Inv ID</th>
                                <th class="pb-3">Scheme Name</th>
                                <th class="pb-3">Capital Amount</th>
                                <th class="pb-3">Duration (Days)</th>
                                <th class="pb-3">Total Return %</th>
                                <th class="pb-3">Daily ROI %</th>
                                <th class="pb-3">Calculated Daily Payout</th>
                                <th class="pb-3">Date Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($investments as $inv): 
                                $is_ripple = stripos($inv['scheme_name'], 'Ripple Effect') !== false;
                                if ($is_ripple) {
                                    $daily_percent = 'N/A (Delayed)';
                                    $daily_payout = $inv['amount'] * ((float)$inv['total_return_percent'] / 100);
                                    $daily_payout_lbl = '$' . number_format($daily_payout, 2) . ' (One-Time)';
                                } else {
                                    $daily_percent = round((float)$inv['total_return_percent'] / (int)$inv['duration_days'], 4) . '%';
                                    $daily_payout = $inv['amount'] * (((float)$inv['total_return_percent'] / (int)$inv['duration_days']) / 100);
                                    $daily_payout_lbl = '$' . number_format($daily_payout, 2) . ' / day';
                                }
                            ?>
                            <tr class="text-gray-700">
                                <td class="py-3 font-mono font-bold"><?php echo $inv['id']; ?></td>
                                <td class="py-3 font-bold"><?php echo htmlspecialchars($inv['scheme_name']); ?> (<?php echo htmlspecialchars($inv['scheme_type']); ?>)</td>
                                <td class="py-3 font-bold text-gray-900">$<?php echo number_format($inv['amount'], 2); ?></td>
                                <td class="py-3"><?php echo $inv['duration_days']; ?> days</td>
                                <td class="py-3 font-bold text-indigo-600"><?php echo $inv['total_return_percent']; ?>%</td>
                                <td class="py-3 font-mono"><?php echo $daily_percent; ?></td>
                                <td class="py-3 font-black text-gray-900"><?php echo $daily_payout_lbl; ?></td>
                                <td class="py-3 text-gray-400"><?php echo $inv['created_at']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Recent Transactions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- ROI Log -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-lg font-black uppercase text-gray-900 mb-4 border-b border-gray-50 pb-2">Recent Daily ROI Transactions</h3>
                <?php if (empty($roi_txs)): ?>
                    <p class="text-sm font-medium text-gray-400 uppercase tracking-wider py-4">No recent ROI payouts found.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-50">
                        <?php foreach ($roi_txs as $tx): ?>
                        <li class="py-3 flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($tx['description']); ?></p>
                                <span class="text-[10px] text-gray-400 font-mono"><?php echo $tx['created_at']; ?></span>
                            </div>
                            <span class="text-sm font-black text-indigo-600">+$<?php echo number_format($tx['amount'], 2); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Residuals Log -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-lg font-black uppercase text-gray-900 mb-4 border-b border-gray-50 pb-2">Recent Residual Commissions</h3>
                <?php if (empty($residual_txs)): ?>
                    <p class="text-sm font-medium text-gray-400 uppercase tracking-wider py-4">No recent residual payouts found.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-50">
                        <?php foreach ($residual_txs as $tx): ?>
                        <li class="py-3 flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($tx['description']); ?></p>
                                <span class="text-[10px] text-gray-400 font-mono"><?php echo $tx['created_at']; ?></span>
                            </div>
                            <span class="text-sm font-black text-emerald-600">+$<?php echo number_format($tx['amount'], 2); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
