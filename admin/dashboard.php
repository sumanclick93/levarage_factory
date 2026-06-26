<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// 1. Quick Stats Queries
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pending_kyc = $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'pending'")->fetchColumn();
$pending_investments = $pdo->query("SELECT COUNT(*) FROM investments WHERE status = 'pending'")->fetchColumn();
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'")->fetchColumn();

// 2. Financial Overview (Strict Precedence Logic)

// Total Active Investment (All combined)
$stmt_total = $pdo->query("
    SELECT SUM(amount) 
    FROM investments 
    WHERE status = 'active'
");
$total_active_investment = $stmt_total->fetchColumn() ?: 0;

// Corporate Capital (Priority corporate check, ignores hash_ref)
$stmt_corp = $pdo->query("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.status = 'active'
    AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
");
$total_corporate = $stmt_corp->fetchColumn() ?: 0;

// Matching Bonus Capital (Not corporate, IS admin/company assigned)
$stmt_matching = $pdo->query("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')
");
$total_matching = $stmt_matching->fetchColumn() ?: 0;

// Individual Capital (Not corporate, not matching bonus)
$stmt_ind = $pdo->query("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");
$total_individual = $stmt_ind->fetchColumn() ?: 0;

// 3. Recent Activity (Latest 5 Users)
$recent_users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Platform Overview</h1>
            <p class="text-gray-500">Welcome back, Administrator. Here is what's happening today.</p>
        </header>

        <?php if ($pending_investments > 0 || $pending_withdrawals > 0 || $pending_kyc > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <?php if ($pending_investments > 0): ?>
                <a href="pending_investments.php" class="bg-red-50 border border-red-200 p-4 rounded-lg flex items-center justify-between hover:bg-red-100 transition">
                    <span class="text-red-700 font-bold"><?php echo $pending_investments; ?> Pending Deposits</span>
                    <span class="text-red-500 text-xs font-black">ACTION REQUIRED →</span>
                </a>
            <?php endif; ?>
            
            <?php if ($pending_withdrawals > 0): ?>
                <a href="withdrawals.php" class="bg-orange-50 border border-orange-200 p-4 rounded-lg flex items-center justify-between hover:bg-orange-100 transition">
                    <span class="text-orange-700 font-bold"><?php echo $pending_withdrawals; ?> Pending Payouts</span>
                    <span class="text-orange-500 text-xs font-black">REVIEW →</span>
                </a>
            <?php endif; ?>

            <?php if ($pending_kyc > 0): ?>
                <a href="kyc_approval.php" class="bg-blue-50 border border-blue-200 p-4 rounded-lg flex items-center justify-between hover:bg-blue-100 transition">
                    <span class="text-blue-700 font-bold"><?php echo $pending_kyc; ?> KYC Verifications</span>
                    <span class="text-blue-500 text-xs font-black">VIEW →</span>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Members</p>
                <p class="text-3xl font-black text-gray-900 mt-1"><?php echo number_format($total_users); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Investment</p>
                <p class="text-3xl font-black text-blue-600 mt-1">$<?php echo number_format($total_active_investment, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Individual Capital</p>
                <p class="text-3xl font-black text-green-600 mt-1">$<?php echo number_format($total_individual, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Corporate Capital</p>
                <p class="text-3xl font-black text-purple-600 mt-1">$<?php echo number_format($total_corporate, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Matching Bonus Capital</p>
                <p class="text-3xl font-black text-pink-500 mt-1">$<?php echo number_format($total_matching, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">Security Status</p>
                <p class="text-lg font-bold text-indigo-600 mt-1 uppercase">SSL Protected</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-bold text-gray-400 uppercase">System Time</p>
                <p class="text-lg font-mono font-bold text-gray-900 mt-1"><?php echo date('H:i T'); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-700 uppercase text-xs">Newest Members</h2>
                    <a href="manage_users.php" class="text-xs text-red-600 font-bold hover:underline">View All</a>
                </div>
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($recent_users as $user): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-6 text-sm font-medium text-gray-900"><a href="view_user.php?id=<?php echo $user['id']; ?>" class="hover:text-[#00A6FB] hover:underline transition-colors"><?php echo htmlspecialchars($user['username']); ?></a></td>
                            <td class="py-3 px-6 text-xs text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-3 px-6 text-xs text-gray-400 text-right"><?php echo date('M d', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <a href="notifications.php" class="bg-white border border-gray-200 p-6 rounded-xl flex flex-col items-center justify-center hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-red-500 transition">
                        <svg class="w-6 h-6 text-red-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </div>
                    <span class="font-bold text-gray-900">Send News</span>
                </a>
                <a href="referral_settings.php" class="bg-white border border-gray-200 p-6 rounded-xl flex flex-col items-center justify-center hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-gray-900 transition">
                        <svg class="w-6 h-6 text-gray-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <span class="font-bold text-gray-900">MLM Setup</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>