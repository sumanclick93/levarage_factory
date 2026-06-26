<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Initialize filter variables
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Base Query (Updated to fetch network name)
$query = "SELECT i.*, u.username, u.email, s.name as scheme_name, c.symbol, c.name as network_name 
          FROM investments i 
          JOIN users u ON i.user_id = u.id 
          JOIN investment_schemes s ON i.scheme_id = s.id 
          LEFT JOIN currencies c ON i.currency_id = c.id WHERE 1=1";

$params = [];

// Apply Filters dynamically
if ($username != '') {
    $query .= " AND u.username LIKE ?";
    $params[] = "%$username%";
}
if ($status != '') {
    $query .= " AND i.status = ?";
    $params[] = $status;
}
if ($start_date != '') {
    $query .= " AND i.created_at >= ?";
    $params[] = $start_date . " 00:00:00";
}
if ($end_date != '') {
    $query .= " AND i.created_at <= ?";
    $params[] = $end_date . " 23:59:59";
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$investments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Investments - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Copy to clipboard functionality with visual feedback
        function copyHash(text, buttonElement) {
            navigator.clipboard.writeText(text).then(() => {
                const originalSvg = buttonElement.innerHTML;
                buttonElement.innerHTML = `<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
                setTimeout(() => {
                    buttonElement.innerHTML = originalSvg;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Investment History</h1>
                <p class="text-gray-500">Comprehensive list of all platform investments.</p>
            </div>
            <div class="bg-white px-6 py-2 rounded-lg shadow-sm border border-gray-200">
                <span class="text-xs font-bold text-gray-400 uppercase block">Total Records</span>
                <span class="text-xl font-bold text-[#00A6FB]"><?php echo count($investments); ?></span>
            </div>
        </header>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Search user..." class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-sm">
                </div>
        
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-sm">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
        
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-sm">
                </div>
        
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-sm">
                </div>
        
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-black text-white px-4 py-2 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-[#00A6FB] transition">Filter</button>
                    <a href="all_investments.php" class="flex-1 bg-gray-100 text-gray-500 px-4 py-2 rounded-xl font-bold text-xs uppercase tracking-widest text-center hover:bg-gray-200 transition">Reset</a>
                </div>
            </form>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden overflow-x-auto">
            <table class="w-full text-left min-w-[1000px]">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold whitespace-nowrap">
                    <tr>
                        <th class="py-4 px-6">Investment Date</th>
                        <th class="py-4 px-6">Approval Date</th>
                        <th class="py-4 px-6">User / Plan</th>
                        <th class="py-4 px-6">Amount</th>
                        <th class="py-4 px-6">Network / Wallet</th>
                        <th class="py-4 px-6">Hash ID</th>
                        <th class="py-4 px-6">Proof</th>
                        <th class="py-4 px-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($investments as $inv): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="py-4 px-6 text-sm text-gray-500 whitespace-nowrap">
                            <?php echo date('M d, Y', strtotime($inv['created_at'])); ?>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-500 whitespace-nowrap">
                            <?php echo date('M d, Y', strtotime($inv['payout_started_at'])); ?>
                        </td>
                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($inv['username']); ?></div>
                            <div class="text-[10px] text-[#00A6FB] font-bold uppercase"><?php echo htmlspecialchars($inv['scheme_name']); ?></div>
                        </td>
                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="font-bold text-gray-900">$<?php echo number_format($inv['amount'], 2); ?></div>
                            <div class="text-[10px] text-gray-400 uppercase font-bold"><?php echo htmlspecialchars($inv['symbol'] ?? 'N/A'); ?></div>
                        </td>

                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="text-xs font-bold text-gray-800 uppercase">
                                <?php echo htmlspecialchars($inv['network_name'] ?? 'System Wallet'); ?>
                            </div>
                            <?php if(isset($inv['wallet_deduction']) && $inv['wallet_deduction'] > 0): ?>
                                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">
                                    Wallet Ded: $<?php echo number_format($inv['wallet_deduction'], 2); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="py-4 px-6">
                            <?php if(!empty($inv['hash_ref'])): ?>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[11px] text-gray-500 truncate w-32" title="<?php echo htmlspecialchars($inv['hash_ref']); ?>">
                                        <?php echo htmlspecialchars($inv['hash_ref']); ?>
                                    </span>
                                    <button type="button" onclick="copyHash('<?php echo htmlspecialchars($inv['hash_ref']); ?>', this)" class="text-gray-400 hover:text-[#00A6FB] focus:outline-none transition-colors" title="Copy Full Hash">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-[10px] text-gray-400 font-bold uppercase">N/A</span>
                            <?php endif; ?>
                        </td>

                        <td class="py-4 px-6">
                            <?php if(!empty($inv['screenshot_url'])): ?>
                                <a href="../uploads/payments/<?php echo $inv['screenshot_url']; ?>" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition flex items-center gap-1 w-max">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] text-gray-400 font-bold uppercase">No Proof</span>
                            <?php endif; ?>
                        </td>

                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <?php 
                                $statusStyles = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'active' => 'bg-green-100 text-green-700',
                                    'completed' => 'bg-blue-100 text-blue-700',
                                    'rejected' => 'bg-red-100 text-red-700'
                                ];
                                $currentStyle = $statusStyles[$inv['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo $currentStyle; ?>">
                                <?php echo htmlspecialchars($inv['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>