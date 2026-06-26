<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch this user's path to build the correct search prefix
$stmt_path = $pdo->prepare("SELECT path FROM users WHERE id = ?");
$stmt_path->execute([$user_id]);
$my_path = $stmt_path->fetchColumn();

// Define the exact match and the prefix match
if (empty($my_path)) {
    $exact_path = (string)$user_id;
    $search_prefix = $user_id . "/%";
} else {
    $exact_path = $my_path . "/" . $user_id;
    $search_prefix = $my_path . "/" . $user_id . "/%";
}

// 2. Fetch users under this user in the tree
$stmt = $pdo->prepare("SELECT id, username, email, path FROM users WHERE path = ? OR path LIKE ?");
$stmt->execute([$exact_path, $search_prefix]);
$referrals = $stmt->fetchAll();

// 3. Fetch MLM settings for Bonus Rates
$stmt_mlm = $pdo->query("SELECT level, commission_percent FROM mlm_settings ORDER BY level ASC");
$mlm_settings = $stmt_mlm->fetchAll(PDO::FETCH_KEY_PAIR);

// Initialize arrays for 10 levels
$level_counts = array_fill(1, 10, 0);
$level_roi = array_fill(1, 10, 0);
$level_residual = array_fill(1, 10, 0);
$level_faststart = array_fill(1, 10, 0);
$level_withdrawal = array_fill(1, 10, 0);
$level_volume = array_fill(1, 10, 0);

// Array to hold individual user statistics for the detailed data table
$user_details = [];

// =========================================================================
// 4. PREPARE SQL STATEMENTS (STRICT PRECEDENCE LOGIC)
// =========================================================================

// Team Volume (Only Individual)
$stmt_vol = $pdo->prepare("
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

// Personal Investment (Only Individual)
$stmt_personal = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
    AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
");

// Corporate Investment (Priority corporate check, ignores hash_ref)
$stmt_corporate = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
");

// Matching Bonus (Not corporate, IS admin/company assigned)
$stmt_matching = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.user_id = ? 
    AND i.status = 'active'
    AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
    AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')
");

// Total ROI
$stmt_roi = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi'");

// Total Faststart
$stmt_fast = $pdo->prepare("
    SELECT SUM(amount) 
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'referral_bonus' 
    AND description LIKE '%Referral Bonus%'
");

// Total Residual (Only Individual)
$stmt_res = $pdo->prepare("
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

// Total Withdrawal
$stmt_with = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal'");

// Calculate stats for each referral using relative path depth
$my_path_depth = empty($my_path) ? 0 : count(explode('/', $my_path));

foreach ($referrals as $ref) {
    $ref_depth = empty($ref['path']) ? 0 : count(explode('/', $ref['path']));
    $level = $ref_depth - $my_path_depth;
    
    if ($level >= 1 && $level <= 10) {
        $ref_user_id = $ref['id'];
        $ref_username = $ref['username'];
        $ref_email = $ref['email']; 
        $level_counts[$level]++;

        // 1. Team Volume (Recursive specifically for this downline user)
        $stmt_vol->execute([$ref_user_id, $ref_user_id, $ref_user_id]);
        $vol = (float)$stmt_vol->fetchColumn();
        $level_volume[$level] += $vol;

        // 2. Personal Investment
        $stmt_personal->execute([$ref_user_id]);
        $personal_inv = (float)$stmt_personal->fetchColumn();

        // 3. Corporate Investment
        $stmt_corporate->execute([$ref_user_id]);
        $corporate_inv = (float)$stmt_corporate->fetchColumn();

        // 4. Matching Bonus
        $stmt_matching->execute([$ref_user_id]);
        $matching_inv = (float)$stmt_matching->fetchColumn();

        // 5. Total ROI
        $stmt_roi->execute([$ref_user_id]);
        $roi = (float)$stmt_roi->fetchColumn();
        $level_roi[$level] += $roi;

        // 6. Total Faststart
        $stmt_fast->execute([$ref_user_id]);
        $fast = (float)$stmt_fast->fetchColumn();
        $level_faststart[$level] += $fast;

        // 7. Total Residual
        $stmt_res->execute([$ref_user_id]);
        $res = (float)$stmt_res->fetchColumn();
        $level_residual[$level] += $res;

        // 8. Total Withdrawal
        $stmt_with->execute([$ref_user_id]);
        $with = (float)$stmt_with->fetchColumn();
        $level_withdrawal[$level] += $with;

        // Push calculated stats into detailed array
        $user_details[] = [
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
}

// Sort the detailed array so Level 1 appears first, then by User ID
usort($user_details, function($a, $b) {
    if ($a['level'] == $b['level']) {
        return $a['id'] <=> $b['id'];
    }
    return $a['level'] <=> $b['level'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Reports - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-[90rem] mx-auto space-y-10">
            
            <header>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">Network Performance</h1>
                <p class="text-gray-500 text-sm">Real-time statistics of your 10-level organization.</p>
            </header>

            <section class="bg-white rounded-[2rem] border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Organization Distribution</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 whitespace-nowrap">
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Level</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Population</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total ROI</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Residual</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Faststart</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Withdrawal</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Volume</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Bonus Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php for($i=1; $i<=10; $i++): ?>
                            <tr class="hover:bg-gray-50/80 transition-colors whitespace-nowrap">
                                <td class="p-4 text-sm font-bold text-gray-600 italic">Level <?php echo $i; ?></td>
                                <td class="p-4">
                                    <span class="text-lg font-black <?php echo $level_counts[$i] > 0 ? 'text-[#00A6FB]' : 'text-gray-300'; ?>">
                                        <?php echo number_format($level_counts[$i]); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm font-bold text-green-600">
                                    $<?php echo number_format($level_roi[$i], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-orange-500">
                                    $<?php echo number_format($level_residual[$i], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-gray-900">
                                    $<?php echo number_format($level_faststart[$i], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-red-500">
                                    $<?php echo number_format($level_withdrawal[$i], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-blue-600">
                                    $<?php echo number_format($level_volume[$i], 2); ?>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="bg-blue-50 text-[#00A6FB] px-3 py-1 rounded-lg text-[10px] font-black">
                                        <?php echo number_format($mlm_settings[$i] ?? 0, 2); ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white rounded-[2rem] border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center flex-wrap gap-4">
                    <h2 class="text-lg font-bold text-gray-900">Detailed Network Statistics</h2>
                    
                    <div class="flex items-center gap-3">
                        <label for="levelFilter" class="text-sm font-bold text-gray-500">Show Level:</label>
                        <select id="levelFilter" onchange="filterDetailedTable()" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm font-bold rounded-xl focus:ring-[#00A6FB] focus:border-[#00A6FB] block p-2.5 outline-none cursor-pointer">
                            <option value="all">All Levels</option>
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>">Level <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="detailedNetworkTable">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 whitespace-nowrap">
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Name / UID</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Level</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Email</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Personal Inv.</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Corporate Inv.</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Matching Bonus</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total ROI</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Residual</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Faststart</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Withdrawal</th>
                                <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Team Volume</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($user_details as $u): ?>
                            <tr class="detailed-row hover:bg-gray-50/80 transition-colors whitespace-nowrap" data-level="<?php echo $u['level']; ?>">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900"><?php echo htmlspecialchars($u['username']); ?></div>
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-0.5">UID: <?php echo htmlspecialchars($u['id']); ?></div>
                                </td>
                                <td class="p-4 text-sm font-bold text-gray-600 italic">Level <?php echo $u['level']; ?></td>
                                <td class="p-4 text-sm text-gray-500 font-medium"><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                                <td class="p-4 text-sm font-bold text-indigo-600">
                                    $<?php echo number_format($u['personal_inv'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-purple-600">
                                    $<?php echo number_format($u['corporate_inv'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-pink-500">
                                    $<?php echo number_format($u['matching_bonus'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-green-600">
                                    $<?php echo number_format($u['roi'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-orange-500">
                                    $<?php echo number_format($u['residual'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-gray-900">
                                    $<?php echo number_format($u['faststart'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-red-500">
                                    $<?php echo number_format($u['withdrawal'], 2); ?>
                                </td>
                                <td class="p-4 text-sm font-bold text-blue-600">
                                    $<?php echo number_format($u['volume'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr id="emptyLevelMsg" style="display: none;">
                                <td colspan="11" class="p-8 text-center text-sm font-bold text-gray-400">No users found for this level.</td>
                            </tr>
                            
                            <?php if(empty($user_details)): ?>
                            <tr>
                                <td colspan="11" class="p-8 text-center text-sm font-bold text-gray-400">No network data found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
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
                if (visibleCount === 0 && <?php echo empty($user_details) ? 'false' : 'true'; ?>) {
                    emptyMsg.style.display = '';
                } else {
                    emptyMsg.style.display = 'none';
                }
            }
        }

        // Run this immediately on page load to default to showing Level 1
        document.addEventListener('DOMContentLoaded', () => {
            const levelSelect = document.getElementById('levelFilter');
            levelSelect.value = '1'; 
            filterDetailedTable(); 
        });
    </script>
</body>
</html>