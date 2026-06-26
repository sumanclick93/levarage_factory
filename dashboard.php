<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User Profile & Balance
$stmt = $pdo->prepare("SELECT username, wallet_balance, referral_code, corporate_wallet, gold_wallet, created_at, referrer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Also assign to $user_gold to keep your existing modal logic fully intact
$user_gold = $user;

// =========================================================
// STRICT PRECEDENCE LOGIC: CAPITAL & VOLUME
// =========================================================

// RULE 1: Corporate Capital (Highest priority, ignores hash_ref checks)
$stmt = $pdo->prepare("
    SELECT SUM(i.amount) 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? 
      AND i.status = 'active' 
      AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
");
$stmt->execute([$user_id]);
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
$stmt->execute([$user_id]);
$active_capital_matching = $stmt->fetchColumn() ?: 0;

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
$stmt->execute([$user_id]);
$active_capital_individual = $stmt->fetchColumn() ?: 0;

// (Legacy helper variable for rank engine)
$active_capital = $active_capital_individual + $active_capital_corporate + $active_capital_matching;

// Total Team Volume (Only 'individual' type active investments from all downlines)
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
$stmt->execute([$user_id, $user_id, $user_id]);
$total_team_volume = $stmt->fetchColumn() ?? 0;

// =========================================================
// TRANSACTIONS & BONUSES
// =========================================================

// Total ROI Earned to Date (All active investments)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi'");
$stmt->execute([$user_id]);
$total_roi = $stmt->fetchColumn() ?? 0;

// Total Fast Start Bonus (Referral Bonus)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_bonus' AND description LIKE '%Referral Bonus%'");
$stmt->execute([$user_id]);
$total_Faststart_bonus = $stmt->fetchColumn() ?? 0;

// Total Residual Commission (Daily ROI Residuals from Individual only)
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
$stmt_resid->execute([$user_id]);
$total_residal = (float)$stmt_resid->fetchColumn() ?? 0;

// Total Withdrawals
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal'");
$stmt->execute([$user_id]);
$total_withdrawal = $stmt->fetchColumn() ?? 0;

// Total Rank Bonuses
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'rank_reward'");
$stmt->execute([$user_id]);
$total_rank_bonuses = $stmt->fetchColumn() ?? 0;

// Count Direct Referrals (Level 1)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id = ?");
$stmt->execute([$user_id]);
$referral_count = $stmt->fetchColumn() ?? 0;

// Fetch latest notifications
$news_stmt = $pdo->query("SELECT * FROM global_notifications ORDER BY created_at DESC LIMIT 2");
$latest_news = $news_stmt->fetchAll();

// =========================================================
// EXISTING PERFORMANCE BONUS & RANK ENGINE LOGIC
// =========================================================

$stmt = $pdo->prepare("SELECT 
    (SELECT IFNULL(SUM(amount), 0) 
     FROM investments 
     WHERE user_id = ? AND status = 'active') as total_invested,
    
    (SELECT COUNT(DISTINCT u.id) 
     FROM users u
     JOIN investments i ON u.id = i.user_id
     WHERE u.referrer_id = ? 
     AND i.status = 'active'
     AND (SELECT SUM(amount) FROM investments WHERE user_id = u.id AND status = 'active') >= 2500
    ) as qualified_refs
");
$stmt->execute([$user_id, $user_id]);
$qual = $stmt->fetch();

$is_bonus_qualified = ($qual['total_invested'] >= 10000 && ($qual['qualified_refs'] ?? 0) >= 10);

$join_date = $user['created_at'];
$days_since_join = floor((time() - strtotime($join_date)) / (60 * 60 * 24));
$days_to_next_milestone = 7 - ($days_since_join % 7);
$next_payout_timestamp = strtotime("+$days_to_next_milestone days", strtotime(date('Y-m-d 00:00:00')));

$projected_amount = 0.00;
if ($is_bonus_qualified && $user['referrer_id']) {
    $upline_id = $user['referrer_id'];
    $days_into_cycle = $days_since_join % 7;
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'roi' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    $stmt->execute([$upline_id, $days_into_cycle]);
    $total_upline_roi = (float)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id = ? AND id IN (SELECT user_id FROM investments WHERE status = 'active' GROUP BY user_id HAVING SUM(amount) >= 10000)");
    $stmt->execute([$upline_id]);
    $sharers = (int)$stmt->fetchColumn();
    $projected_amount = $total_upline_roi / (($sharers > 0) ? $sharers : 1);
}

$stmt = $pdo->prepare("SELECT amount, created_at FROM transactions WHERE user_id = ? AND type = 'performance_bonus' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_bonus = $stmt->fetch();

$next_rank = null;
$qualifying_volume = 0;
$personal_inv = $active_capital; 

$ranks_stmt = $pdo->query("SELECT * FROM rank_bonuses ORDER BY team_volume ASC");
$all_ranks = $ranks_stmt->fetchAll();

$directs = $pdo->prepare("SELECT id FROM users WHERE referrer_id = ?");
$directs->execute([$user_id]);
$leg_roots = $directs->fetchAll(PDO::FETCH_COLUMN);

if (count($leg_roots) >= 3) {
    $leg_vols = [];
    foreach ($leg_roots as $leg_id) {
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM investments WHERE status = 'active' AND (user_id IN (SELECT id FROM users WHERE FIND_IN_SET(?, path)) OR user_id = ?)");
        $stmt->execute([$leg_id, $leg_id]);
        $leg_vols[] = (float)$stmt->fetchColumn();
    }
    rsort($leg_vols);

    foreach ($all_ranks as $rk) {
        $target = (float)$rk['team_volume'];
        
        $check = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = 'rank_reward' AND description LIKE ?");
        $check->execute([$user_id, "Rank Bonus: {$rk['rank_name']}%"]);
        
        if ($check->fetchColumn() == 0) {
            $next_rank = $rk;
            $max_leg_cap = $target * 0.40;
            $qualifying_volume = min($leg_vols[0], $max_leg_cap);
            for ($i = 1; $i < count($leg_vols); $i++) { $qualifying_volume += $leg_vols[$i]; }
            break; 
        }
    }
}

$current_rank_name = "No Rank";
$reversed_ranks = array_reverse($all_ranks);
foreach ($reversed_ranks as $rk) {
    if ($qualifying_volume >= $rk['team_volume'] && $personal_inv >= $rk['personal_investment']) {
        $current_rank_name = $rk['rank_name'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-[87rem] mx-auto space-y-8">
            <header class="flex flex-col md:flex-row justify-between items-flex-start md:items-center gap-2">
                <div>
                    <h1 class="text-[19px] md:text-3xl font-black text-gray-900 tracking-tight uppercase italic">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p class="text-gray-500 text-sm">Here is what's happening with your capital today.</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-200 h-fit">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Current Status</p>
                        <p class="text-sm font-bold text-[#00A6FB]"><?php echo $current_rank_name; ?></p>
                    </div>
                    <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-200 h-fit">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Referral Code</p>
                        <p class="text-sm font-bold text-[#00A6FB]"><?php echo $user['referral_code']; ?></p>
                    </div>
                </div>
            </header>

            <div class="w-full mb-8 animate__animated animate__fadeIn">
                <div class="relative overflow-hidden rounded-[2.5rem] p-8 md:p-12 min-h-[220px] flex items-center shadow-2xl border border-white/10" 
                     style="background-image: url('countdown.jpg'); background-size: cover; background-position: center;">
                    
                    <div class="absolute inset-0 bg-gradient-to-r from-[#1a0b2e]/90 via-[#311352]/70 to-[#1a0b2e]/90 z-0"></div>
                    <div class="absolute top-[-40%] left-[-10%] w-[50%] h-[180%] bg-[#00A6FB]/20 blur-[120px] rounded-full z-1"></div>
                    <div class="absolute bottom-[-40%] right-[-5%] w-[40%] h-[150%] bg-purple-500/20 blur-[100px] rounded-full animate-pulse z-1"></div>
            
                    <div class="relative z-10 w-full flex flex-col md:flex-row justify-between items-center gap-10">
                        <div class="text-center md:text-left">
                            <h3 class="text-white text-2xl md:text-4xl font-light tracking-[0.25em] uppercase leading-none">
                                Leverage<span class="font-black italic text-[#00A6FB]">Factory</span> 
                            </h3>
                            <p class="text-blue-400 text-[10px] font-bold uppercase tracking-[0.5em] mt-4 opacity-80">Next-Gen Trading Protocol</p>
                        </div>
            
                        <div class="bg-white/5 backdrop-blur-2xl border border-white/10 p-2 md:p-8 md:px-12 rounded-[1.5rem] md:rounded-[2.5rem] shadow-2xl flex items-center gap-3 md:gap-10 relative">
                            <div class="text-center">
                                <span id="days" class="text-5xl font-black text-white tracking-tighter tabular-nums">00</span>
                                <p class="text-[8px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mt-2">Days</p>
                            </div>
                            <span class="text-3xl font-light text-white/20">:</span>
                            <div class="text-center">
                                <span id="hours" class="text-5xl font-black text-white tracking-tighter tabular-nums">00</span>
                                <p class="text-[8px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mt-2">Hours</p>
                            </div>
                            <span class="text-3xl font-light text-white/20">:</span>
                            <div class="text-center">
                                <span id="mins" class="text-5xl font-black text-white tracking-tighter tabular-nums">00</span>
                                <p class="text-[8px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mt-2">Mins</p>
                            </div>
                            <span class="text-3xl font-light text-white/20">:</span>
                            <div class="text-center">
                                <span id="secs" class="text-5xl font-black text-[#00A6FB] tracking-tighter tabular-nums">00</span>
                                <p class="text-[8px] font-black text-white/40 uppercase tracking-[0.3em] mt-2">Secs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">E-Wallet Balance</p>
                    <p class="text-2xl font-black text-gray-900">$<?php echo number_format($user['wallet_balance'], 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Active Capital</p>
                    <p class="text-2xl font-black text-[#00A6FB]">$<?php echo number_format($active_capital_individual, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                   <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Corporate Wallet</p>
                   <p class="text-2xl font-black text-indigo-600">$<?php echo number_format($user['corporate_wallet'], 2); ?></p> 
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Corporate Capital</p>
                    <p class="text-2xl font-black text-[#00A6FB]">$<?php echo number_format($active_capital_corporate, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Matching Bonus</p>
                    <p class="text-2xl font-black text-pink-500">$<?php echo number_format($active_capital_matching, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Daily Roi</p>
                    <p class="text-2xl font-black text-green-600">$<?php echo number_format($total_roi, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Fast Start Bonus</p>
                    <p class="text-2xl font-black text-gray-900">$<?php echo number_format($total_Faststart_bonus, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Residual Bonus</p>
                    <p class="text-2xl font-black text-orange-500">$<?php echo number_format($total_residal, 2); ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Team Volume</p>
                    <p class="text-2xl font-black text-blue-600">$<?php echo number_format($total_team_volume, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Direct Partners</p>
                    <p class="text-2xl font-black text-purple-600"><?php echo $referral_count; ?> Users</p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Withdrawal</p>
                    <p class="text-2xl font-black text-red-500">$<?php echo number_format($total_withdrawal, 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Rank Bonus</p>
                    <p class="text-2xl font-black text-indigo-600">$<?php echo number_format($total_rank_bonuses, 2); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 ">
              <div class="">
                    <div onclick="openGoldModal()" class="rounded-t-[2rem] cursor-pointer bg-gradient-to-br from-[#FFD700] via-[#DAA520] to-[#B8860B] p-6 shadow-[0_10px_30px_rgba(218,165,32,0.3)] border border-white/20 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="relative z-10">
                            <p class="text-[10px] font-black text-white/70 uppercase tracking-widest mb-1">Vault Gold Balance</p>
                            <p class="text-3xl font-black text-white italic tracking-tighter"><?php echo number_format($user_gold['gold_wallet'], 2); ?> <span class="text-xs font-normal not-italic">gm</span></p>
                            <p class="text-[9px] font-bold text-white/50 uppercase tracking-widest mt-2 group-hover:text-white transition-colors">Click to verify protocol</p>
                        </div>
                            <div class="absolute right-0 -bottom-12">
                            <img src="images/gold.png" alt="gold biscuit" class="h-[221px]" />
                        </div>
                    </div>
                 <div id="goldModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate__animated animate__fadeIn">
                <div class="bg-white rounded-[3rem] p-10 max-w-md w-full relative overflow-hidden shadow-2xl border border-gray-100 animate__animated animate__zoomIn">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#FFD700]/10 blur-[50px] rounded-full"></div>
                    
                    <div class="relative z-10 text-center">
                        <div class="w-20 h-20 bg-gradient-to-tr from-[#FFD700] to-[#B8860B] rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-xl rotate-12">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="white"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 mb-2">Welcome to the <br> <span class="text-[#DAA520]">Levarage Factory Vault</span></h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-8">
                            To celebrate the Leverage Factory pre-launch, we have credited your secure vault with <span class="font-black text-gray-900">0.5gm of pure gold</span>. This asset is fully liquid and held under your unique node ID.
                        </p>
                        <p class="text-sm text-gray-500 leading-relaxed mb-8">
                            Every new user who signs up with Levarage Factory will receive <span class="font-black text-gray-900">0.5gm of gold</span>upon registration.
                        </p>
                        
                        <button onclick="closeGoldModal()" class="w-full py-5 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-[#DAA520] transition-all shadow-lg active:scale-95">
                            Accept The Gold
                        </button>
                    </div>
                </div>
            </div>
                 <div class="rounded-b-[2rem] bg-white p-8 border border-gray-200 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Market Live Feed</p>
                            <h3 class="text-xl font-black text-gray-900 uppercase italic tracking-tighter">Gold <span class="text-[#DAA520]">XAU/USD</span></h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-ping"></span>
                            <span class="text-[9px] font-black text-green-500 uppercase tracking-widest">Live NY Market</span>
                        </div>
                    </div>
            
                    <div class="py-2 h-[108px]">
                        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-single-quote.js" async>
                        {
                        "symbol": "TVC:GOLD",
                        "width": "100%",
                        "colorTheme": "light",
                        "isTransparent": true,
                        "locale": "en",
                        "largeChartUrl": ""
                        }
                        </script>
                    </div>
                    
                    <p class="text-[9px] text-gray-400 italic border-t border-gray-50 uppercase font-bold tracking-widest">
                        Data sourced from global commodities exchanges
                    </p>
                </div>
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-[#DAA520]/5 blur-[40px] rounded-full"></div>
            </div>
                </div>
                  <div class="">
                    <div  class="rounded-t-[2rem] cursor-pointer bg-gradient-to-br from-[#b6b7b7] via-[#b6b7b7] to-[#5d5858] p-6 shadow-[0_10px_30px_rgba(218,165,32,0.3)] border border-white/20 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="relative z-10">
                            <p class="text-[10px] font-black text-white/70 uppercase tracking-widest mb-1">Vault Silver Balance</p>
                            <p class="text-3xl font-black text-white italic tracking-tighter">0.00 <span class="text-xs font-normal not-italic">gm</span></p>
                            <p class="text-[9px] font-bold text-white/50 uppercase tracking-widest mt-2 group-hover:text-white transition-colors">Coming Soon</p>
                        </div>
                            <div class="absolute right-0 -bottom-12">
                            <img src="images/silver.png" alt="silver biscuit" class="h-[221px]" />
                        </div>
                    </div>
                 <div id="goldModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate__animated animate__fadeIn">
                <div class="bg-white rounded-[3rem] p-10 max-w-md w-full relative overflow-hidden shadow-2xl border border-gray-100 animate__animated animate__zoomIn">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#FFD700]/10 blur-[50px] rounded-full"></div>
                    
                    <div class="relative z-10 text-center">
                        <div class="w-20 h-20 bg-gradient-to-tr from-[#FFD700] to-[#B8860B] rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-xl rotate-12">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="white"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 mb-2">Welcome to the <br> <span class="text-[#DAA520]">Vault Protocol</span></h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-8">
                            To celebrate your initiation into the **Leverage Factory Elite**, we have credited your secure vault with <span class="font-black text-gray-900">0.5gm of pure gold</span>. This asset is fully liquid and held under your unique node ID.
                        </p>
                        
                        <button onclick="closeGoldModal()" class="w-full py-5 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-[#DAA520] transition-all shadow-lg active:scale-95">
                            Protocol Accepted
                        </button>
                    </div>
                </div>
            </div>
                 <div class="rounded-b-[2rem] bg-white p-8 border border-gray-200 shadow-sm relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Market Live Feed</p>
                                    <h3 class="text-xl font-black text-gray-900 uppercase italic tracking-tighter">Silver <span class="text-[#DAA520]">XAU/USD</span></h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-500 rounded-full animate-ping"></span>
                                    <span class="text-[9px] font-black text-green-500 uppercase tracking-widest">Live NY Market</span>
                                </div>
                            </div>
                    
                            <div class="py-2 h-[108px]">
                                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-single-quote.js" async>
                                {
                                "symbol": "TVC:SILVER",
                                "width": "100%",
                                "colorTheme": "light",
                                "isTransparent": true,
                                "locale": "en",
                                "largeChartUrl": ""
                                }
                                </script>
                            </div>
                            
                            <p class="text-[9px] text-gray-400 italic border-t border-gray-50 uppercase font-bold tracking-widest">
                                Data sourced from global commodities exchanges
                            </p>
                        </div>
                        <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-[#DAA520]/5 blur-[40px] rounded-full"></div>
                    </div>
                </div>
                 <div class="">
                    <div  class="h-[128px] rounded-t-[2rem] cursor-pointer border border-white/20 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                         <div  class="rounded-t-[2rem] cursor-pointer bg-gradient-to-br from-[#00a6fb] via-[#00a6fb] to-[#0f557a] p-6 shadow-[0_10px_30px_rgba(218,165,32,0.3)] border border-white/20 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                        <div class="relative z-10">
                            <p class="text-[10px] font-black text-white/70 uppercase tracking-widest mb-1">Card Balance</p>
                            <p class="text-3xl font-black text-white italic tracking-tighter">0.00</p>
                            <p class="text-[9px] font-bold text-white/50 uppercase tracking-widest mt-2 group-hover:text-white transition-colors">Coming Soon</p>
                        </div>
                            <div class="absolute right-0 -bottom-8">
                            <img src="images/redot.png" alt="redot card" class="h-[171px]" />
                        </div>
                    </div>
                    </div>
                 <div id="goldModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate__animated animate__fadeIn">
                <div class="bg-white rounded-[3rem] p-10 max-w-md w-full relative overflow-hidden shadow-2xl border border-gray-100 animate__animated animate__zoomIn">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#FFD700]/10 blur-[50px] rounded-full"></div>
                    
                    <div class="relative z-10 text-center">
                        <div class="w-20 h-20 bg-gradient-to-tr from-[#FFD700] to-[#B8860B] rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-xl rotate-12">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="white"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 mb-2">Welcome to the <br> <span class="text-[#DAA520]">Vault Protocol</span></h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-8">
                            To celebrate your initiation into the **Leverage Factory Elite**, we have credited your secure vault with <span class="font-black text-gray-900">0.5gm of pure gold</span>. This asset is fully liquid and held under your unique node ID.
                        </p>
                        
                        <button onclick="closeGoldModal()" class="w-full py-5 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-[#DAA520] transition-all shadow-lg active:scale-95">
                            Protocol Accepted
                        </button>
                    </div>
                </div>
            </div>
                 <div class="rounded-b-[2rem] bg-white p-0 border border-gray-200 shadow-sm relative overflow-hidden group h-[250px]">
                        <div class="relative z-10">
                            <video
                              class="pointer-events-none h-[200px] w-full"
                              autoplay
                              muted
                              loop
                              playsinline
                              preload="auto"
                            >
                              <source src="https://leveragefactory.ai/wp-content/uploads/2026/02/cardfinal3.webm" type="video/webm">
                            </video>
                            <div class="flex justify-center gap-8 px-4 py-1">
                                <button class="bg-[#00a6fb] hover:[#0588cc] text-white font-bold py-1 px-4 rounded-full text-[13px]">Add Funds</button>
                                <button class="bg-[#00a6fb] hover:[#0588cc] text-white font-bold py-1 px-4 rounded-full text-[13px]">Apply</button>
                            </div>
                        </div>
                        <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-[#DAA520]/5 blur-[40px] rounded-full"></div>
                    </div>
                </div>
                
                <div class="hidden relative w-full h-[370px] overflow-hidden">
                    <div
                    id="videoCarousel"
                    class="flex h-full transition-transform duration-700 ease-in-out"
                    >
                      <div class="min-w-full h-full px-2">
                        <div class="h-full rounded-[2rem] bg-black">
                            <a href="https://www.redotpay.com/" target="_blank">
                          <video class="h-full w-full object-contain" muted>
                            <source src="https://leveragefactory.ai/wp-content/uploads/2026/02/cardfinal3.webm" >
                          </video>
                          </a>
                        </div>
                      </div>
                </div>
                
                </div>


            </div>
              
            <?php foreach($latest_news as $news): 
                $bg = ['info'=>'bg-blue-50', 'success'=>'bg-green-50', 'warning'=>'bg-orange-50', 'danger'=>'bg-red-50'];
                $text = ['info'=>'text-blue-700', 'success'=>'text-green-700', 'warning'=>'text-orange-700', 'danger'=>'text-red-700'];
                $border = ['info'=>'border-blue-100', 'success'=>'border-green-100', 'warning'=>'border-orange-100', 'danger'=>'border-red-100'];
            ?>
            <div class="mb-6 p-5 rounded-3xl border <?php echo $bg[$news['type']].' '.$border[$news['type']]; ?> relative overflow-hidden">
                <div class="relative z-10 flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase <?php echo $text[$news['type']].' '.str_replace('bg-','bg-opacity-20 bg-',$bg[$news['type']]); ?>">
                                <?php echo $news['type']; ?>
                            </span>
                            <span class="text-[10px] text-gray-400 font-bold"><?php echo date('M d, H:i', strtotime($news['created_at'])); ?></span>
                        </div>
                        <h3 class="font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($news['message']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white/5 backdrop-blur-xl p-8 rounded-[2.5rem] border border-white/10 shadow-2xl relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mb-1">Performance Protocol</p>
                                <h3 class="text-xl font-black uppercase italic italic tracking-tighter">Upline <span class="text-[#00A6FB]">Bonus</span></h3>
                            </div>
                            <?php if($is_bonus_qualified): ?>
                                <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-[9px] font-black uppercase tracking-widest animate-pulse">Qualified</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full bg-red-500/20 text-red-400 text-[9px] font-black uppercase tracking-widest">Ineligible</span>
                            <?php endif; ?>
                        </div>
                
                        <?php if($is_bonus_qualified): ?>
                            <div class="grid grid-cols-2 gap-4 items-center">
                                <div id="bonus-countdown" class="flex gap-4" data-target="<?php echo $next_payout_timestamp; ?>">
                                    <div class="text-center"><p class="text-2xl font-black text-gray-900" id="b_days">00</p><p class="text-[8px] text-gray-500 uppercase font-bold">Days</p></div>
                                    <div class="text-2xl font-black text-gray-700">:</div>
                                    <div class="text-center"><p class="text-2xl font-black text-gray-900" id="b_hours">00</p><p class="text-[8px] text-gray-500 uppercase font-bold">Hrs</p></div>
                                    <div class="text-2xl font-black text-gray-700">:</div>
                                    <div class="text-center"><p class="text-2xl font-black text-gray-900" id="b_mins">00</p><p class="text-[8px] text-gray-500 uppercase font-bold">Min</p></div>
                                </div>
                                <div class="border-l border-gray-200 pl-6">
                                    <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Projected Yield</p>
                                    <p class="text-3xl font-black text-gray-900 italic tracking-tighter">$<?php echo number_format($projected_amount, 2); ?></p>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 italic mt-4">Next 100% ROI share distribution node pending</p>
                        <?php else: ?>
                            <div class="py-4">
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden mb-2">
                                    <div class="bg-[#00A6FB] h-full" style="width: <?php echo min(($qual['total_invested']/10000)*100, 100); ?>%"></div>
                                </div>
                                <p class="text-[9px] text-gray-400 uppercase font-bold tracking-widest">Requirements: $10k Invested & 10 Qualified Directs</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-[#00A6FB]/5 blur-[60px] rounded-full"></div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-200 shadow-sm relative overflow-hidden group">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Historical Settlement</p>
                    <h3 class="text-xl font-black text-gray-900 uppercase italic tracking-tighter mb-4">Last <span class="text-[#00A6FB]">Bonus</span> Earned</h3>
                    <?php if($last_bonus): ?>
                        <p class="text-4xl font-black text-gray-900 tracking-tighter mb-2">$<?php echo number_format($last_bonus['amount'], 2); ?></p>
                        <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Released: <?php echo date('M d, Y', strtotime($last_bonus['created_at'])); ?></p>
                    <?php else: ?>
                        <div class="py-6 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                            <p class="text-[10px] font-black text-gray-300 uppercase italic">No history found in node</p>
                        </div>
                    <?php endif; ?>
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-[#00A6FB]/5 blur-[40px] rounded-full group-hover:bg-[#00A6FB]/10 transition-all"></div>
                </div>
            </div>
            

            <div class="bg-gray-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.4em] mb-1 italic">Achievement Protocol</p>
                            <h3 class="text-xl font-black uppercase italic tracking-tighter">Rank <span class="text-cyan-400">Evolution</span></h3>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Current Status</p>
                            <p class="text-xs font-bold text-white italic"><?php echo $next_rank ? 'Awaiting Payout' : 'Max Rank Reached'; ?></p>
                        </div>
                    </div>
            
                    <?php if($next_rank): 
                        $percent = min(($qualifying_volume / $next_rank['team_volume']) * 100, 100);
                        $shortfall = max($next_rank['team_volume'] - $qualifying_volume, 0);
                    ?>
                        <div class="space-y-6">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Target Rank</p>
                                    <p class="text-2xl font-black text-white italic tracking-tighter"><?php echo $next_rank['rank_name']; ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Reward Allocation</p>
                                    <p class="text-2xl font-black text-green-400 italic tracking-tighter">$<?php echo number_format($next_rank['bonus_amount'], 0); ?></p>
                                </div>
                            </div>
            
                            <div class="relative">
                                <div class="w-full bg-white/5 h-3 rounded-full overflow-hidden border border-white/5">
                                    <div class="bg-gradient-to-r from-cyan-600 to-cyan-400 h-full shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <div class="flex justify-between mt-3">
                                    <p class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">$<?php echo number_format($qualifying_volume, 0); ?></p>
                                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Goal: $<?php echo number_format($next_rank['team_volume'], 0); ?></p>
                                </div>
                            </div>
            
                            <div class="pt-4 border-t border-white/5">
                                <p class="text-[9px] text-gray-400 italic leading-relaxed">
                                    To unlock <span class="text-white font-bold"><?php echo $next_rank['rank_name']; ?></span>, increase qualified leg volume by <span class="text-cyan-400 font-bold">$<?php echo number_format($shortfall, 0); ?></span>. 
                                    <?php if($personal_inv < $next_rank['personal_investment']): ?>
                                        <br><span class="text-red-400 font-bold">Note:</span> Personal investment must be $<?php echo number_format($next_rank['personal_investment'], 0); ?>.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="py-10 text-center">
                            <p class="text-cyan-400 font-black text-xs uppercase tracking-widest">All Achievement Nodes Synchronized</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-cyan-400/5 blur-[60px] rounded-full group-hover:bg-cyan-400/10 transition-all duration-700"></div>
            </div>

            <div class="bg-gray-900 p-8 rounded-[2.5rem] text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <h2 class="text-2xl font-black uppercase italic tracking-tight">Build Your Network</h2>
                        <p class="text-blue-300 text-sm mt-1">Earn commissions up to 10 levels deep.</p>
                    </div>
                    <div class="flex bg-white/10 p-2 rounded-2xl border border-white/20 w-full md:w-auto">
                        <input type="text" readonly id="refLink" value="https://leveragefactory.ai/register.php?ref_code=<?php echo $user['referral_code']; ?>" 
                            class="bg-transparent border-none text-sm px-4 outline-none w-full md:w-80">
                        <button onclick="copyRef()" class="bg-[#00A6FB] text-white px-6 py-3 rounded-xl font-bold text-xs uppercase transition hover:bg-blue-600">Copy Link</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="invest.php" class="flex-1 bg-white border border-gray-200 p-6 rounded-[2rem] text-center hover:bg-gray-50 transition">
                    <p class="font-black text-gray-900 uppercase italic">Browse Plans</p>
                </a>
                <a href="ledger.php" class="flex-1 bg-white border border-gray-200 p-6 rounded-[2rem] text-center hover:bg-gray-50 transition">
                    <p class="font-black text-gray-900 uppercase italic">View Ledger</p>
                </a>
            </div>

           

            <div class="w-full mb-8 animate__animated animate__fadeInUp">
                <div class="relative overflow-hidden rounded-[2.5rem] p-8 md:p-14 min-h-[240px] flex items-center shadow-2xl border border-white/10 group" 
                     style="background-image: url('whitepaper.jpg'); background-size: cover; background-position: center;">
                    <div class="absolute inset-0 bg-gradient-to-r from-[#1a0b2e]/90 via-[#1a0b2e]/40 to-transparent z-0"></div>
                    <div class="absolute top-0 right-0 w-1/2 h-full bg-blue-500/10 blur-[120px] rounded-full group-hover:bg-blue-500/20 transition-all duration-700"></div>
            
                    <div class="relative z-10 w-full flex flex-col md:flex-row justify-between items-center gap-8">
                        <div class="flex items-center gap-6">
                            <div class="hidden md:flex w-20 h-20 items-center justify-center bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 shadow-inner">
                                <svg viewBox="0 0 24 24" fill="none" class="w-12 h-12 text-white/90 drop-shadow-[0_0_15px_rgba(0,166,251,0.8)]"><path d="M15 6C13.3431 6 12 7.34315 12 9C12 10.6569 13.3431 12 15 12C16.6569 12 18 10.6569 18 9C18 7.34315 16.6569 6 15 6Z" stroke="currentColor" stroke-width="1.2"/><path d="M12 9H6V12H8V15H11V12H12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h3 class="text-white text-4xl font-light tracking-[0.25em] uppercase leading-none">Leverage <span class="font-black italic text-[#00A6FB]">Factory</span></h3>
                        </div>
                        <!--<a href="Leverage Factory Pitch Deck Version.pdf" target="_blank" class="relative inline-flex items-center justify-center px-12 py-5 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md text-white font-black uppercase tracking-[0.2em] text-xs transition-all hover:bg-[#00A6FB] hover:border-[#00A6FB] hover:shadow-[0_0_30px_rgba(0,166,251,0.5)] active:scale-95 overflow-hidden group/btn"><span class="relative z-10">Download Presentation</span></a>-->
                        <a href="#"  class="relative inline-flex items-center justify-center px-12 py-5 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md text-white font-black uppercase tracking-[0.2em] text-xs transition-all hover:bg-[#00A6FB] hover:border-[#00A6FB] hover:shadow-[0_0_30px_rgba(0,166,251,0.5)] active:scale-95 overflow-hidden group/btn"><span class="relative z-10">Download Presentation</span></a>
                    </div>
                    <div class="absolute bottom-6 right-10"><span class="text-[9px] font-mono text-white/40 tracking-[0.3em] uppercase">V2.0.4 STABLE NODE</span></div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
  const carousel = document.getElementById("videoCarousel");
  const slides = carousel.children;
  const totalSlides = slides.length;

  let index = 0;
  const slideInterval = 4000; // 4 seconds

  function showSlide(i) {
    carousel.style.transform = `translateX(-${i * 100}%)`;

    // Pause all videos
    Array.from(slides).forEach(slide => {
      const video = slide.querySelector("video");
      video.pause();
      video.currentTime = 0;
    });

    // Play active video
    const activeVideo = slides[i].querySelector("video");
    activeVideo.play();
  }

  function startCarousel() {
    showSlide(index);
    setInterval(() => {
      index = (index + 1) % totalSlides;
      showSlide(index);
    }, slideInterval);
  }

  startCarousel();
</script>


    <script>
        function copyRef() {
            var copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Referral link copied to clipboard!");
        }

        document.addEventListener('DOMContentLoaded', function() {
            const targetDate = new Date("Mar 12, 2026 00:00:00").getTime();
            function updateTimer() {
                const now = new Date().getTime();
                const diff = targetDate - now;
                if (diff <= 0) { document.querySelectorAll('#days, #hours, #mins, #secs').forEach(el => el.innerHTML = "00"); return; }
                const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);
                document.getElementById("days").innerText = d.toString().padStart(2, '0');
                document.getElementById("hours").innerText = h.toString().padStart(2, '0');
                document.getElementById("mins").innerText = m.toString().padStart(2, '0');
                document.getElementById("secs").innerText = s.toString().padStart(2, '0');
            }
            setInterval(updateTimer, 1000);
            updateTimer();

            function updateBonusCountdown() {
                const timer = document.getElementById('bonus-countdown');
                if (!timer) return;
                const target = parseInt(timer.dataset.target) * 1000;
                const now = new Date().getTime();
                const diff = target - now;
                if (diff <= 0) { location.reload(); return; }
                document.getElementById('b_days').textContent = Math.floor(diff / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                document.getElementById('b_hours').textContent = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                document.getElementById('b_mins').textContent = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            }
            setInterval(updateBonusCountdown, 60000);
            updateBonusCountdown();
        });
    </script>
    <script>
        function openGoldModal() {
            document.getElementById('goldModal').classList.remove('hidden');
        }
        function closeGoldModal() {
            document.getElementById('goldModal').classList.add('hidden');
        }
    </script>
</body>
</html>