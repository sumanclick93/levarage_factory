<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

$uid = $_GET['id'] ?? null;
if (!$uid) { header("Location: manage_users.php"); exit(); }

// --- FETCH CORE USER DATA ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user_data = $stmt->fetch();

$filter = $_GET['tx_filter'] ?? 'all';
$limit  = $_GET['tx_limit'] ?? 10;
$net_limit = $_GET['net_limit'] ?? 10;

$roi_type = $_GET['roi_type'] ?? 'all';
$inv_type = $_GET['inv_type'] ?? 'all';

$allowed_limits = [10, 100, 1000];
if(!in_array($limit, $allowed_limits)){
    $limit = 10;
}
if(!in_array($net_limit, $allowed_limits)){
    $net_limit = 10;
}

/* ===============================
   TRANSACTION + INVESTMENT LEDGER
================================*/

if($filter === 'investment'){

    $tx_query = "
        SELECT i.id, i.amount, 'investment' as type, s.name as description, i.created_at, i.status, i.hash_ref, s.type as scheme_type, i.wallet_deduction
        FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.user_id = :uid
    ";

    // Strict Precedence Sub-filters for Investment
    if ($inv_type === 'individual') {
        $tx_query .= " AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%') AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'";
    } elseif ($inv_type === 'corporate') {
        $tx_query .= " AND (s.type = 'corporate' OR s.name LIKE '%corporate%')";
    } elseif ($inv_type === 'admin') {
        // Admin Assigned = Matching Bonus
        $tx_query .= " AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%') AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')";
    }

    $tx_query .= " ORDER BY i.created_at DESC LIMIT " . $limit;

    $tx_stmt = $pdo->prepare($tx_query);
    $tx_stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $tx_stmt->execute();
    $all_transactions = $tx_stmt->fetchAll();

}else{

    $tx_query = "SELECT * FROM transactions WHERE user_id = :uid";
    $tx_params = ['uid' => $uid];

    if ($filter === 'fastart_bonus') {
        $tx_query .= " 
            AND type = 'referral_bonus' 
            AND description LIKE '%Referral Bonus from%'";
    }
    elseif ($filter === 'residual_bonus') {
        $tx_query .= " 
            AND type = 'referral_bonus' 
            AND description LIKE '%Residual%'";
    }
    elseif ($filter === 'roi') {
        $tx_query .= " AND type = 'roi'";
        if ($roi_type === 'e_wallet') {
            $tx_query .= " AND description LIKE '%E-Wallet%'";
        } elseif ($roi_type === 'corporate_wallet') {
            $tx_query .= " AND description LIKE '%Corporate_Vault%'";
        }
    }
    elseif ($filter !== 'all') {
        $tx_query .= " AND type = :filter_type";
        $tx_params['filter_type'] = $filter;
    }

    $tx_query .= " ORDER BY created_at DESC LIMIT " . $limit;

    $tx_stmt = $pdo->prepare($tx_query);
    $tx_stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

    if(isset($tx_params['filter_type'])){
        $tx_stmt->bindValue(':filter_type', $tx_params['filter_type'], PDO::PARAM_STR);
    }

    $tx_stmt->execute();
    $all_transactions = $tx_stmt->fetchAll();
}


// --- FETCH CORE USER DATA WITH UPLINE PROTOCOL ---
$stmt = $pdo->prepare("SELECT u.*, s.username as sponsor_name, s.id as sponsor_id FROM users u LEFT JOIN users s ON u.referrer_id = s.id WHERE u.id = ?");
$stmt->execute([$uid]);
$user_data = $stmt->fetch();

if (!$user_data) {
    die("User not found.");
}

// 1. Fetch User Profile
$stmt = $pdo->prepare("SELECT username, wallet_balance, referral_code, created_at, referrer_id FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// 2. Calculate Total ROI Earned to Date
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi'");
$stmt->execute([$uid]);
$total_roi = $stmt->fetchColumn() ?? 0;

// 3. Count Direct Referrals (Level 1)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id = ?");
$stmt->execute([$uid]);
$referral_count = $stmt->fetchColumn() ?? 0;

// 4. Calculate Total Withdrawals
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal'");
$stmt->execute([$uid]);
$total_withdrawal = $stmt->fetchColumn() ?? 0;

// 5. Calculate Total Team Volume (Active individual investments from downlines)
$stmt = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i 
    JOIN users u ON i.user_id = u.id 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE 
        i.user_id != ? 
        AND (u.referrer_id = ? OR FIND_IN_SET(?, REPLACE(u.path, '/', ',')) > 0)
        AND i.status = 'active' 
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' 
        AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");
$stmt->execute([$uid, $uid, $uid]);
$total_team_volume = $stmt->fetchColumn() ?? 0;

// 6. Calculate Total Direct Commission (Referral Bonus)
$stmt_direct = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_bonus' AND description LIKE '%Referral Bonus%'");
$stmt_direct->execute([$uid]);
$total_refferal_commission = (float)$stmt_direct->fetchColumn() ?? 0;


// 7. Calculate Total Residual Commission (Daily ROI Residuals from Individual only)
$stmt_resid = $pdo->prepare("
    SELECT SUM(t.amount)
    FROM transactions t
    JOIN investments i ON t.investment_id = i.id
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE t.user_id = ?
    AND t.type = 'referral_bonus'
    AND t.description LIKE '%Residual%'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");
$stmt_resid->execute([$uid]);
$total_residual_commission = (float)$stmt_resid->fetchColumn() ?? 0;

// 8. Calculate Total Rank Bonuses
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'rank_reward'");
$stmt->execute([$uid]);
$total_rank_bonuses = $stmt->fetchColumn() ?? 0;


// RULE 3: Individual Capital (Must not be corporate, must not be matching bonus)
$stmt = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");
$stmt->execute([$uid]);
$active_capital_individual = $stmt->fetchColumn() ?: 0;

// RULE 1: Corporate Capital (Highest priority, ignores hash_ref checks)
$stmt = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? 
      AND i.status = 'active' 
      AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
");
$stmt->execute([$uid]);
$active_capital_corporate = $stmt->fetchColumn() ?: 0;

// RULE 2: Matching Bonus Capital (Not corporate, but IS admin/company assigned)
$stmt = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? 
      AND i.status = 'active' 
      AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
      AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')
");
$stmt->execute([$uid]);
$active_capital_matching = $stmt->fetchColumn() ?: 0;


// =========================================================================
// NEW BLOCK: DETAILED NETWORK STATISTICS FOR THIS USER
// =========================================================================
$stmt_path_net = $pdo->prepare("SELECT path FROM users WHERE id = ?");
$stmt_path_net->execute([$uid]);
$my_path_net = $stmt_path_net->fetchColumn();

if (empty($my_path_net)) {
    $exact_path_net = (string)$uid;
    $search_prefix_net = $uid . "/%";
} else {
    $exact_path_net = $my_path_net . "/" . $uid;
    $search_prefix_net = $my_path_net . "/" . $uid . "/%";
}

$stmt_net_users = $pdo->prepare("SELECT id, username, email, path FROM users WHERE path = ? OR path LIKE ?");
$stmt_net_users->execute([$exact_path_net, $search_prefix_net]);
$referrals_net = $stmt_net_users->fetchAll();

$net_user_details = [];

// 1. Team Volume (Only Individual)
$stmt_vol_net = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN users u ON i.user_id = u.id
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE 
        i.user_id != ? 
        AND (u.referrer_id = ? OR FIND_IN_SET(?, REPLACE(u.path, '/', ',')) > 0)
        AND i.status = 'active' 
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' 
        AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");

// 2. Personal Investment (Only Individual)
$stmt_personal_net = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");

// 3. Corporate Investment (Priority Corporate)
$stmt_corporate_net = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
");

// 4. Matching Bonus (Not corporate, IS admin assigned)
$stmt_matching_net = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')
");

// 5. Total ROI
$stmt_roi_net = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi'");

// 6. Total Faststart
$stmt_fast_net = $pdo->prepare("
    SELECT SUM(amount) 
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'referral_bonus' 
    AND description LIKE '%Referral Bonus%'
");

// 7. Total Residual (Only Individual)
$stmt_res_net = $pdo->prepare("
    SELECT SUM(t.amount)
    FROM transactions t
    JOIN investments i ON t.investment_id = i.id
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE t.user_id = ?
    AND t.type = 'referral_bonus'
    AND t.description LIKE '%Residual%'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");

// 8. Total Withdrawal
$stmt_with_net = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal'");

$my_path_depth_net = empty($my_path_net) ? 0 : count(explode('/', $my_path_net));

$filtered_referrals = [];
foreach ($referrals_net as $ref) {
    $ref_depth = empty($ref['path']) ? 0 : count(explode('/', $ref['path']));
    $level = $ref_depth - $my_path_depth_net;
    
    if ($level >= 1 && $level <= 10) {
        $ref['level'] = $level;
        $filtered_referrals[] = $ref;
    }
}

usort($filtered_referrals, function($a, $b) {
    if ($a['level'] == $b['level']) {
        return $a['id'] <=> $b['id'];
    }
    return $a['level'] <=> $b['level'];
});

$net_user_details_sliced = [];
$sliced_referrals = array_slice($filtered_referrals, 0, $net_limit);

foreach ($sliced_referrals as $ref) {
    $ref_user_id = $ref['id'];
    $ref_username = $ref['username'];
    $ref_email = $ref['email'];
    $level = $ref['level'];

    $stmt_vol_net->execute([$ref_user_id, $ref_user_id, $ref_user_id]);
    $vol = (float)$stmt_vol_net->fetchColumn();

    $stmt_personal_net->execute([$ref_user_id]);
    $personal_inv = (float)$stmt_personal_net->fetchColumn();

    $stmt_corporate_net->execute([$ref_user_id]);
    $corporate_inv = (float)$stmt_corporate_net->fetchColumn();

    $stmt_matching_net->execute([$ref_user_id]);
    $matching_inv = (float)$stmt_matching_net->fetchColumn();

    $stmt_roi_net->execute([$ref_user_id]);
    $roi = (float)$stmt_roi_net->fetchColumn();

    $stmt_fast_net->execute([$ref_user_id]);
    $fast = (float)$stmt_fast_net->fetchColumn();

    $stmt_res_net->execute([$ref_user_id]);
    $res = (float)$stmt_res_net->fetchColumn();

    $stmt_with_net->execute([$ref_user_id]);
    $with = (float)$stmt_with_net->fetchColumn();

    $net_user_details_sliced[] = [
        'id' => $ref_user_id,
        'username' => $ref_username,
        'email' => $ref_email,
        'level' => $level,
        'personal_inv' => $personal_inv,
        'corporate_inv' => $corporate_inv,
        'matching_bonus' => $matching_inv,
        'roi' => $roi,
        'residual' => $res,
        'faststart' => $fast,
        'withdrawal' => $with,
        'volume' => $vol
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Audit Terminal | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background: #F8FAFC; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8 animate__animated animate__fadeIn custom-scrollbar">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Member_Audit_Protocol</p>
                <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">
                    <?php echo htmlspecialchars($user_data['username']); ?> <span class="text-[#00A6FB]">Node</span>_
                </h1>
                
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Connected_Upline:</span>
                    <?php if($user_data['sponsor_name']): ?>
                        <a href="view_user.php?id=<?php echo $user_data['sponsor_id']; ?>" class="text-[11px] font-black text-gray-900 hover:text-[#00A6FB] uppercase transition-colors">
                            <?php echo htmlspecialchars($user_data['sponsor_name']); ?> 
                        </a>
                    <?php else: ?>
                        <span class="text-[11px] font-black text-gray-400 uppercase italic">MASTER_ROOT_NODE</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm text-right">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Available_Balance</p>
                    <p class="text-xl font-black text-gray-900">$<?php echo number_format($user_data['wallet_balance'], 2); ?></p>
                </div>
                <div class="bg-black p-4 rounded-2xl shadow-lg text-right">
                    <p class="text-[9px] font-black text-[#00A6FB] uppercase tracking-widest">Total_Settled</p>
                    <p class="text-xl font-black text-white">$<?php echo number_format($total_career_earnings ?? 0, 2); ?></p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-green-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">E-Wallet Balance</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($user['wallet_balance'], 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-green-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Corporate Balance</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($user_data['corporate_wallet'] ?? 0, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-purple-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Active Capital</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($active_capital_individual, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-purple-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Corporate Capital</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($active_capital_corporate, 2); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-pink-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Matching Bonus</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($active_capital_matching, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-indigo-600 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Daily Roi</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_roi, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-indigo-600 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Faststart Bonus</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_refferal_commission, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-cyan-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Residual Bonus</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_residual_commission, 2); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-cyan-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Rank Rewards</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_rank_bonuses, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-green-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Withdrawal</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_withdrawal, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-purple-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Team Volume</p>
                <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_team_volume, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border-l-4 border-cyan-500 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Direct Partners</p>
                <p class="text-2xl font-black text-gray-900"><?php echo $referral_count; ?> Users</p>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-6">

                <h3 class="text-xl font-black uppercase italic tracking-tighter">
                Transaction <span class="text-[#00A6FB]">Full Ledger</span>_
                </h3>
                
                <form method="GET" class="flex gap-2">
                
                <input type="hidden" name="id" value="<?php echo $uid; ?>">
                <input type="hidden" name="net_limit" value="<?php echo htmlspecialchars($net_limit); ?>">
                
                <select name="tx_filter" onchange="this.form.submit()"
                class="bg-gray-50 border border-gray-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                
                <option value="all" <?php if($filter=='all') echo 'selected'; ?>>All_Protocols</option>
                <option value="roi" <?php if($filter=='roi') echo 'selected'; ?>>Daily_ROI</option>
                <option value="investment" <?php if($filter=='investment') echo 'selected'; ?>>Investments</option>
                <option value="fastart_bonus" <?php if($filter=='fastart_bonus') echo 'selected'; ?>>Fast Start Bonus</option>
                <option value="residual_bonus" <?php if($filter=='residual_bonus') echo 'selected'; ?>>Residuals</option>
                <option value="performance_bonus" <?php if($filter=='performance_bonus') echo 'selected'; ?>>Performance</option>
                <option value="rank_reward" <?php if($filter=='rank_reward') echo 'selected'; ?>>Rank_Rewards</option>
                <option value="withdrawal" <?php if($filter=='withdrawal') echo 'selected'; ?>>Withdrawals</option>
                
                </select>

                <?php if ($filter === 'roi'): ?>
                <select name="roi_type" onchange="this.form.submit()" class="bg-gray-50 border border-gray-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                    <option value="all" <?php if($roi_type=='all') echo 'selected'; ?>>All Wallets</option>
                    <option value="e_wallet" <?php if($roi_type=='e_wallet') echo 'selected'; ?>>E-Wallet</option>
                    <option value="corporate_wallet" <?php if($roi_type=='corporate_wallet') echo 'selected'; ?>>Corporate Wallet</option>
                </select>
                <?php endif; ?>

                <?php if ($filter === 'investment'): ?>
                <select name="inv_type" onchange="this.form.submit()" class="bg-gray-50 border border-gray-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                    <option value="all" <?php if($inv_type=='all') echo 'selected'; ?>>All Types</option>
                    <option value="individual" <?php if($inv_type=='individual') echo 'selected'; ?>>Individual</option>
                    <option value="corporate" <?php if($inv_type=='corporate') echo 'selected'; ?>>Corporate</option>
                    <option value="admin" <?php if($inv_type=='admin') echo 'selected'; ?>>Matching Bonus</option>
                </select>
                <?php endif; ?>
                
                <select name="tx_limit" onchange="this.form.submit()"
                class="bg-gray-50 border border-gray-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                
                <option value="10" <?php if($limit==10) echo 'selected'; ?>>10</option>
                <option value="100" <?php if($limit==100) echo 'selected'; ?>>100</option>
                <option value="1000" <?php if($limit==1000) echo 'selected'; ?>>1000</option>
                
                </select>
                
                </form>
            </div>

            <div class="overflow-x-auto">

            <table class="w-full text-left">
            
            <thead class="bg-gray-50/50">
            <tr>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Protocol</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Audit_Description</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Settlement</th>
            </tr>
            </thead>
            
            <tbody class="divide-y divide-gray-50">
            
            <?php foreach($all_transactions as $tx): 
            
            $is_credit = in_array($tx['type'],['roi','referral_bonus','performance_bonus','rank_reward','admin_recharge','reverse_back','gold_bonus']);
            
            ?>
            
            <tr class="hover:bg-gray-50/50 transition">
            
            <td class="p-6">
            <p class="text-xs font-bold text-gray-900"><?php echo date('M d, Y',strtotime($tx['created_at'])); ?></p>
            <span class="text-[10px] text-gray-300 font-mono"><?php echo date('H:i:s',strtotime($tx['created_at'])); ?></span>
            </td>
            
            <td class="p-6">
            <span class="px-3 py-1 text-[9px] font-black rounded-lg uppercase border bg-gray-50 text-gray-500 border-gray-100">
            
            <?php
            $type=$tx['type'];
            
            if($tx['type']=='referral_bonus'){
            
            if(strpos($tx['description'],'Referral Bonus from')!==false){
            $type='faststart_bonus';
            }
            elseif(strpos($tx['description'],'Residual')!==false){
            $type='residual_bonus';
            }
            
            }
            
            echo str_replace('_',' ',$type);
            ?>
            
            </span>
            </td>
            
            <td class="p-6 text-sm text-gray-600 italic">
            <?php
            $description = $tx['description'] ?? 'Protocol_Verified';

            if ($tx['type'] === 'investment') {
                $status_label = strtoupper($tx['status'] ?? 'Unknown');
                $scheme_name = $tx['description'];
                
                // Rule 1: Priority Corporate Check
                $is_corporate = ($tx['scheme_type'] === 'corporate' || stripos($scheme_name, 'corporate') !== false);
                
                if ($is_corporate) {
                    $description = htmlspecialchars($scheme_name);
                } else {
                    // Rule 2: If not Corporate, check Admin Assigned/Company (Matching Bonus)
                    $is_admin = (strpos($tx['hash_ref'], 'ADMIN_ASSIGNED') !== false || strpos($tx['hash_ref'], 'COMPANY') !== false);
                    if ($is_admin) {
                        $description = htmlspecialchars($scheme_name) . " (Matching Bonus)";
                    } else {
                        // Rule 3: Individual
                        $description = htmlspecialchars($scheme_name);
                    }
                }
                
                // Coloring the Status
                $status_color = in_array(strtolower($status_label), ['approved', 'active']) ? 'text-green-500' : (strtolower($status_label) === 'rejected' ? 'text-red-500' : 'text-orange-500');
                
                $description .= " <span class='text-[10px] font-black uppercase tracking-widest ml-2 {$status_color}'>[{$status_label}]</span>";

            } elseif ($tx['type'] === 'referral_bonus') {
                if (strpos($description, 'Referral Bonus from') !== false) {
                    $description = str_replace('Referral Bonus', 'Fast Sart Bonus', $description);
                }
            }
            echo $description;
            ?>
            </td>
            
            <td class="p-6 text-right">

            <span class="text-lg font-black tracking-tighter <?php echo $is_credit?'text-green-600':'text-red-600'; ?>">
                <?php echo $is_credit?'+':'-'; ?> $<?php echo number_format($tx['amount'],2); ?>
            </span>
            
            <?php if ($tx['type'] === 'investment' && !empty($tx['wallet_deduction']) && $tx['wallet_deduction'] > 0): ?>
                <span class="block text-[10px] font-bold text-gray-400 tracking-widest uppercase mt-1">
                    Wallet Deducted: -$<?php echo number_format($tx['wallet_deduction'], 2); ?>
                </span>
            <?php endif; ?>
            
            </td>
            
            </tr>
            
            <?php endforeach; ?>
            
            </tbody>
            
            </table>
            
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-6">
                <h3 class="text-xl font-black uppercase italic tracking-tighter">
                    Detailed <span class="text-[#00A6FB]">Network Stats</span>_
                </h3>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label for="levelFilter" class="text-sm font-bold text-gray-500">Show Level:</label>
                        <select id="levelFilter" onchange="filterDetailedTable()" class="bg-gray-50 border border-gray-100 text-gray-900 text-[10px] font-black uppercase tracking-widest rounded-xl focus:ring-[#00A6FB] focus:border-[#00A6FB] block p-2.5 outline-none cursor-pointer">
                            <option value="all">All_Levels</option>
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>">Level <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="id" value="<?php echo $uid; ?>">
                        <input type="hidden" name="tx_filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="hidden" name="tx_limit" value="<?php echo htmlspecialchars($limit); ?>">
                        <?php if($filter === 'roi'): ?><input type="hidden" name="roi_type" value="<?php echo htmlspecialchars($roi_type); ?>"><?php endif; ?>
                        <?php if($filter === 'investment'): ?><input type="hidden" name="inv_type" value="<?php echo htmlspecialchars($inv_type); ?>"><?php endif; ?>

                        <label for="net_limit" class="text-sm font-bold text-gray-500">Rows:</label>
                        <select name="net_limit" onchange="this.form.submit()" class="bg-gray-50 border border-gray-100 text-gray-900 text-[10px] font-black uppercase tracking-widest rounded-xl focus:ring-[#00A6FB] focus:border-[#00A6FB] block p-2.5 outline-none cursor-pointer">
                            <option value="10" <?php if($net_limit==10) echo 'selected'; ?>>10</option>
                            <option value="100" <?php if($net_limit==100) echo 'selected'; ?>>100</option>
                            <option value="1000" <?php if($net_limit==1000) echo 'selected'; ?>>1000</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="detailedNetworkTable">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Name / UID</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Level</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Email</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Personal Inv.</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Corporate Inv.</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Matching Bonus</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total ROI</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Residual</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Faststart</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Withdrawal</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Volume</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($net_user_details_sliced as $u): ?>
                        <tr class="detailed-row hover:bg-gray-50/50 transition-colors whitespace-nowrap" data-level="<?php echo $u['level']; ?>">
                            <td class="p-6">
                                <div class="font-bold text-gray-900"><a href="view_user.php?id=<?php echo $u['id']; ?>" class="hover:text-[#00A6FB] hover:underline transition-colors"><?php echo htmlspecialchars($u['username']); ?></a></div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-0.5">UID: <?php echo htmlspecialchars($u['id']); ?></div>
                            </td>
                            <td class="p-6 text-sm font-bold text-gray-600 italic">Level <?php echo $u['level']; ?></td>
                            <td class="p-6 text-sm text-gray-500 font-medium"><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                            <td class="p-6 text-sm font-bold text-indigo-600">
                                $<?php echo number_format($u['personal_inv'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-purple-600">
                                $<?php echo number_format($u['corporate_inv'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-pink-500">
                                $<?php echo number_format($u['matching_bonus'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-green-600">
                                $<?php echo number_format($u['roi'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-orange-500">
                                $<?php echo number_format($u['residual'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-gray-900">
                                $<?php echo number_format($u['faststart'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-red-500">
                                $<?php echo number_format($u['withdrawal'], 2); ?>
                            </td>
                            <td class="p-6 text-sm font-bold text-blue-600">
                                $<?php echo number_format($u['volume'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr id="emptyLevelMsg" style="display: none;">
                            <td colspan="11" class="p-8 text-center text-sm font-bold text-gray-400 uppercase tracking-widest">No users found for this level.</td>
                        </tr>
                        
                        <?php if(empty($net_user_details_sliced)): ?>
                        <tr>
                            <td colspan="11" class="p-8 text-center text-sm font-bold text-gray-400 uppercase tracking-widest">No network data found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function filterDetailedTable() {
            const selectedLevel = document.getElementById('levelFilter').value;
            const rows = document.querySelectorAll('.detailed-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const rowLevel = row.getAttribute('data-level');
                if (selectedLevel === 'all' || rowLevel === selectedLevel) {
                    row.style.display = ''; 
                    visibleCount++;
                } else {
                    row.style.display = 'none'; 
                }
            });

            const emptyMsg = document.getElementById('emptyLevelMsg');
            if (emptyMsg) {
                if (visibleCount === 0 && <?php echo empty($net_user_details_sliced) ? 'false' : 'true'; ?>) {
                    emptyMsg.style.display = '';
                } else {
                    emptyMsg.style.display = 'none';
                }
            }
        }

        // Run immediately on load to default to Level 1
        document.addEventListener('DOMContentLoaded', () => {
            const levelSelect = document.getElementById('levelFilter');
            levelSelect.value = '1'; 
            filterDetailedTable(); 
        });
    </script>
</body>
</html>