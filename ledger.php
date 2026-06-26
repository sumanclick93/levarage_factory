<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;

// Sub-filters inherited from admin logic
$roi_type = $_GET['roi_type'] ?? 'all';
$inv_type = $_GET['inv_type'] ?? 'all';

try {
    /* ===============================
       TRANSACTION + INVESTMENT LEDGER (ADVANCED LOGIC)
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

        $tx_query .= " ORDER BY i.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($tx_query);
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $all_records = $stmt->fetchAll();

    } else {
        // Added '0 as wallet_deduction' to ensure the array keys match and prevent PHP warnings
        $tx_query = "SELECT id, amount, type, description, created_at, 'completed' as status, '' as hash_ref, '' as scheme_type, 0 as wallet_deduction FROM transactions WHERE user_id = :uid";
        $tx_params = ['uid' => $user_id];

        if ($filter === 'fastart') {
            $tx_query .= " 
                AND type = 'referral_bonus' 
                AND description LIKE '%Referral Bonus from%'";
        }
        elseif ($filter === 'residual') {
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
        elseif ($filter === 'performance') {
            $tx_query .= " AND type = 'performance_bonus'";
        }
        elseif ($filter === 'withdrawal') {
            $tx_query .= " AND type = 'withdrawal'";
        }
        elseif ($filter !== 'all') {
            $tx_query .= " AND type = :filter_type";
            $tx_params['filter_type'] = $filter;
        }

        $tx_query .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($tx_query);
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        if(isset($tx_params['filter_type'])){
            $stmt->bindValue(':filter_type', $tx_params['filter_type'], PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $all_records = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    die("Database Protocol Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction Ledger - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC] text-gray-800 flex h-screen overflow-hidden">

<?php include('includes/sidebar.php'); ?>

<main class="flex-1 overflow-y-auto p-8">

<header class="mb-10 flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6">
    <div>
        <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Audit_Node</p>
        <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">
            Transaction <span class="text-[#00A6FB]">Ledger</span>
        </h1>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3">

        <div class="flex flex-col gap-1">
            <span class="text-[8px] font-black text-gray-400 uppercase ml-2">Protocol_Type</span>
            <select name="filter" onchange="this.form.submit()"
                class="bg-white border border-gray-100 px-6 py-3 rounded-2xl text-[10px] font-black uppercase outline-none focus:ring-2 focus:ring-[#00A6FB] shadow-sm cursor-pointer">
                <option value="all" <?= ($filter=='all'?'selected':'') ?>>All_Activity</option>
                <option value="roi" <?= ($filter=='roi'?'selected':'') ?>>Daily_ROI</option>
                <option value="investment" <?= ($filter=='investment'?'selected':'') ?>>Investments</option>
                <option value="fastart" <?= ($filter=='fastart'?'selected':'') ?>>Fast Start Bonus</option>
                <option value="residual" <?= ($filter=='residual'?'selected':'') ?>>Residual Bonus</option>
                <option value="performance" <?= ($filter=='performance'?'selected':'') ?>>Performance</option>
                <option value="withdrawal" <?= ($filter=='withdrawal'?'selected':'') ?>>Withdrawal</option>
            </select>
        </div>

        <?php if ($filter === 'roi'): ?>
        <div class="flex flex-col gap-1">
            <span class="text-[8px] font-black text-gray-400 uppercase ml-2">Wallet_Target</span>
            <select name="roi_type" onchange="this.form.submit()" 
                class="bg-white border border-gray-100 px-6 py-3 rounded-2xl text-[10px] font-black uppercase outline-none focus:ring-2 focus:ring-[#00A6FB] shadow-sm cursor-pointer">
                <option value="all" <?php if($roi_type=='all') echo 'selected'; ?>>All Wallets</option>
                <option value="e_wallet" <?php if($roi_type=='e_wallet') echo 'selected'; ?>>E-Wallet</option>
                <option value="corporate_wallet" <?php if($roi_type=='corporate_wallet') echo 'selected'; ?>>Corporate Wallet</option>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($filter === 'investment'): ?>
        <div class="flex flex-col gap-1">
            <span class="text-[8px] font-black text-gray-400 uppercase ml-2">Scheme_Type</span>
            <select name="inv_type" onchange="this.form.submit()" 
                class="bg-white border border-gray-100 px-6 py-3 rounded-2xl text-[10px] font-black uppercase outline-none focus:ring-2 focus:ring-[#00A6FB] shadow-sm cursor-pointer">
                <option value="all" <?php if($inv_type=='all') echo 'selected'; ?>>All Types</option>
                <option value="individual" <?php if($inv_type=='individual') echo 'selected'; ?>>Individual</option>
                <option value="corporate" <?php if($inv_type=='corporate') echo 'selected'; ?>>Corporate</option>
                <option value="admin" <?php if($inv_type=='admin') echo 'selected'; ?>>Matching Bonus</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="flex flex-col gap-1">
            <span class="text-[8px] font-black text-gray-400 uppercase ml-2">Display_Limit</span>
            <select name="limit" onchange="this.form.submit()"
                class="bg-white border border-gray-100 px-4 py-3 rounded-2xl text-[10px] font-black uppercase outline-none focus:ring-2 focus:ring-[#00A6FB] shadow-sm cursor-pointer">
                <option value="10" <?= ($limit==10?'selected':'') ?>>Latest 10</option>
                <option value="25" <?= ($limit==25?'selected':'') ?>>Latest 25</option>
                <option value="50" <?= ($limit==50?'selected':'') ?>>Latest 50</option>
                <option value="100" <?= ($limit==100?'selected':'') ?>>Latest 100</option>
                <option value="500" <?= ($limit==500?'selected':'') ?>>Latest 500</option>
            </select>
        </div>

    </form>
</header>

<div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">

<div class="overflow-x-auto">
    <table class="w-full text-left">
    <thead class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
    <tr>
        <th class="p-6">Timestamp</th>
        <th class="p-6">Type</th>
        <th class="p-6">Audit_Description</th>
        <th class="p-6 text-right">Settlement</th>
    </tr>
    </thead>

    <tbody class="divide-y divide-gray-50">

    <?php foreach($all_records as $tx):

    $is_credit = in_array($tx['type'], 
        ['roi','referral_bonus','performance_bonus','rank_reward','admin_recharge','reverse_back','gold_bonus']
    );

    // --- SMART TYPE DETECTION ---
    $display_type = $tx['type'];

    if ($tx['type'] === 'referral_bonus') {
        if (strpos($tx['description'], 'Referral Bonus from') !== false) {
            $display_type = 'faststart bonus';
        }
        elseif (strpos($tx['description'], 'Residual') !== false) {
            $display_type = 'residual bonus';
        }
    }
    ?>

    <tr class="hover:bg-gray-50/50 transition">

    <td class="p-6">
        <p class="text-xs font-bold text-gray-900">
            <?= date('M d, Y', strtotime($tx['created_at'])) ?>
        </p>
        <span class="text-[9px] text-gray-300 font-mono">
            <?= date('H:i:s', strtotime($tx['created_at'])) ?>
        </span>
    </td>

    <td class="p-6">
        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase border 
            <?= ($display_type=='investment') 
                ? 'bg-blue-50 text-[#00A6FB] border-blue-100' 
                : 'bg-gray-100 text-gray-600 border-gray-200'; ?>">
            <?= str_replace('_',' ',$display_type); ?>
        </span>
    </td>

    <td class="p-6 text-sm italic text-gray-700">
        <?php
        $description = $tx['description'] ?? 'Protocol_Verified';

        // Apply advanced tagging logic mirroring the admin view
        if ($tx['type'] === 'investment') {
            $status_label = strtoupper($tx['status'] ?? 'Unknown');
            $scheme_name = $tx['description'];
            
            // Rule 1: Priority Corporate Check
            $is_corporate = ($tx['scheme_type'] === 'corporate' || stripos($scheme_name, 'corporate') !== false);
            
            if ($is_corporate) {
                $description = htmlspecialchars($scheme_name);
            } else {
                // Rule 2: If not Corporate, check Matching Bonus (Admin/Company)
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
        <span class="text-lg font-black tracking-tighter <?= $is_credit ? 'text-green-600':'text-red-600'; ?>">
            <?= $is_credit?'+':'-'; ?> $<?= number_format($tx['amount'],2); ?>
        </span>
        
        <?php if ($tx['type'] === 'investment' && !empty($tx['wallet_deduction']) && $tx['wallet_deduction'] > 0): ?>
            <span class="block text-[10px] font-bold text-gray-400 tracking-widest uppercase mt-1">
                Wallet Deducted: -$<?= number_format($tx['wallet_deduction'], 2); ?>
            </span>
        <?php endif; ?>
    </td>

    </tr>

    <?php endforeach; ?>

    <?php if(empty($all_records)): ?>
    <tr>
    <td colspan="4" class="p-12 text-center text-sm text-gray-400 italic font-bold">
        No transaction history found in this node.
    </td>
    </tr>
    <?php endif; ?>

    </tbody>
    </table>
</div>
</div>

</main>
</body>
</html>