<?php
require_once('includes/db_connect.php');
date_default_timezone_set('Asia/Kolkata');

/* ===============================
   LOGGING SYSTEM
================================= */
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_file = $log_dir . '/roi_' . date('Y-m-d') . '.log';

function write_log($msg) {
    global $log_file;
    file_put_contents(
        $log_file,
        "[" . date('H:i:s') . "] " . $msg . PHP_EOL,
        FILE_APPEND
    );
}

write_log("STARTING DAILY DISTRIBUTION PROTOCOL_");

/* ===============================
   LEVEL QUALIFICATION SETTINGS
   ** FILL THESE IN FROM YOUR SCREENSHOT **
================================= */
$level_requirements = [
    1  => ['directs' => 1,  'personal' => 50,     'team_vol' => 0],
    2  => ['directs' => 2,  'personal' => 50,     'team_vol' => 0],
    3  => ['directs' => 3,  'personal' => 100,    'team_vol' => 1000],
    4  => ['directs' => 4,  'personal' => 250,    'team_vol' => 5000],
    5  => ['directs' => 5,  'personal' => 500,    'team_vol' => 10000],
    6  => ['directs' => 6,  'personal' => 1000,   'team_vol' => 25000],
    7  => ['directs' => 7,  'personal' => 2500,   'team_vol' => 50000],
    8  => ['directs' => 8,  'personal' => 2500,   'team_vol' => 75000],
    9  => ['directs' => 9,  'personal' => 5000,   'team_vol' => 100000],
    10 => ['directs' => 10, 'personal' => 5000,   'team_vol' => 200000],
    11 => ['directs' => 11, 'personal' => 10000,  'team_vol' => 300000],
    12 => ['directs' => 12, 'personal' => 25000,  'team_vol' => 500000],
    13 => ['directs' => 13, 'personal' => 50000,  'team_vol' => 1000000],
    14 => ['directs' => 14, 'personal' => 50000,  'team_vol' => 2500000],
    15 => ['directs' => 15, 'personal' => 100000, 'team_vol' => 5000000],
];

/* ===============================
   LOAD SETTINGS & CACHE
================================= */
$settings = $pdo->query("
    SELECT setting_key, setting_value 
    FROM system_settings
")->fetchAll(PDO::FETCH_KEY_PAIR);

$ranks = $pdo->query("
    SELECT * FROM rank_bonuses 
    ORDER BY team_volume DESC
")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'roi_total' => 0,
    'res_total' => 0,
    'promotions' => 0,
    'investments_processed' => 0
];

// Memory cache to prevent DB overload during loop
$user_qual_cache = [];

/* ===============================
   FETCH ACTIVE INVESTMENTS
================================= */
$active_investments = $pdo->query("
    SELECT 
        i.id,
        i.user_id,
        i.amount,
        i.hash_ref,
        i.created_at,
        DATEDIFF(CURDATE(), DATE(i.created_at)) AS days_passed,
        s.name AS scheme_name,
        s.type,
        s.total_return_percent,
        s.duration_days
    FROM investments i
    JOIN investment_schemes s ON i.scheme_id = s.id
    WHERE i.status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   PROCESS ROI & RESIDUALS
================================= */
foreach ($active_investments as $inv) {

    $investment_id = (int)$inv['id'];
    $uid = (int)$inv['user_id'];
    $amount = (float)$inv['amount'];
    $scheme_name = $inv['scheme_name'];
    $scheme_type = $inv['type'];
    $duration_days = (int)$inv['duration_days'];
    $days_passed = (int)$inv['days_passed'];

    $is_corporate = stripos($scheme_name, 'corporate') !== false || $scheme_type === 'corporate';
    $is_admin_assigned = stripos((string)$inv['hash_ref'], 'COMPANY') !== false || stripos((string)$inv['hash_ref'], 'ADMIN_ASSIGNED') !== false;
    $is_ripple = stripos($scheme_name, 'Ripple Effect') !== false;

    if ($is_ripple && $days_passed < 35) {
        continue; 
    }

    if ($is_ripple) {
        $roi_payout = round(($amount * (float)$inv['total_return_percent']) / 100, 8);
        $description = "Total ROI for Inv #{$investment_id} ({$inv['total_return_percent']}% Return) -> ";
    } else {
        $daily_percent = (float)$inv['total_return_percent'] / $duration_days;
        $roi_payout = round(($amount * $daily_percent) / 100, 8);
        $description = "Daily ROI for Inv #{$investment_id} ({$daily_percent}% daily) -> ";
    }

    if ($roi_payout <= 0) continue;

    $target_wallet = $is_corporate ? 'corporate_wallet' : 'wallet_balance';
    $wallet_label  = $is_corporate ? 'Corporate_Vault' : 'Standard_E-Wallet';
    $description .= $wallet_label;

    try {
        $pdo->beginTransaction();

        /* ===== Duplicate Protection ===== */
        $check = $pdo->prepare("
            SELECT id FROM transactions 
            WHERE user_id = ? AND type = 'roi' AND investment_id = ? AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $check->execute([$uid, $investment_id]);

        if ($check->fetch()) {
            $pdo->rollBack();
            continue;
        }

        /* ===== Credit Wallet & Insert Transaction ===== */
        $pdo->prepare("UPDATE users SET {$target_wallet} = {$target_wallet} + ? WHERE id = ?")->execute([$roi_payout, $uid]);
        
        $pdo->prepare("
            INSERT INTO transactions (user_id, investment_id, amount, type, description, created_at)
            VALUES (?, ?, ?, 'roi', ?, NOW())
        ")->execute([$uid, $investment_id, $roi_payout, $description]);

        /* ===============================
           RESIDUAL DISTRIBUTION (15 LEVELS)
        ================================= */
        if (!$is_corporate && !$is_admin_assigned) {

            $path_stmt = $pdo->prepare("SELECT path FROM users WHERE id = ?");
            $path_stmt->execute([$uid]);
            $path_str = $path_stmt->fetchColumn();

            if (!empty($path_str)) {
                $path_str = str_replace(',', '/', $path_str);
                $uplines = explode('/', $path_str);
                $uplines = array_filter($uplines);

                if (end($uplines) == $uid) array_pop($uplines);

                $uplines = array_reverse($uplines);
                $uplines = array_values($uplines);

                foreach ($uplines as $index => $upline_id) {
                    $upline_id = (int)$upline_id;
                    if ($upline_id <= 0) continue;

                    $level = $index + 1;

                    /* --- GATEKEEPER CACHE BUILDER --- */
                    if (!isset($user_qual_cache[$upline_id])) {
                        
                        // 1. Personal Individual Volume
                        $stmt_p = $pdo->prepare("
                            SELECT IFNULL(SUM(i.amount), 0) FROM investments i
                            JOIN investment_schemes s ON i.scheme_id = s.id
                            WHERE i.user_id = ? AND i.status = 'active' AND s.type = 'individual'
                            AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
                        ");
                        $stmt_p->execute([$upline_id]);
                        $p_vol = (float)$stmt_p->fetchColumn();

                        // 2. Direct Active Partners
                        $stmt_d = $pdo->prepare("
                            SELECT COUNT(DISTINCT u.id) FROM users u
                            JOIN investments i ON u.id = i.user_id
                            JOIN investment_schemes s ON i.scheme_id = s.id
                            WHERE u.referrer_id = ? AND i.status = 'active' AND s.type = 'individual'
                            AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
                        ");
                        $stmt_d->execute([$upline_id]);
                        $d_count = (int)$stmt_d->fetchColumn();

                        // 3. Leg Volumes for 40% Rule
                        $stmt_legs = $pdo->prepare("SELECT id FROM users WHERE referrer_id = ?");
                        $stmt_legs->execute([$upline_id]);
                        $legs = $stmt_legs->fetchAll(PDO::FETCH_COLUMN);
                        
                        $l_vols = [];
                        foreach($legs as $leg_id) {
                            $stmt_lv = $pdo->prepare("
                                SELECT IFNULL(SUM(i.amount), 0) FROM investments i
                                JOIN investment_schemes s ON i.scheme_id = s.id
                                JOIN users u ON u.id = i.user_id
                                WHERE i.status = 'active' AND s.type = 'individual'
                                AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
                                AND (u.id = ? OR FIND_IN_SET(?, REPLACE(u.path, '/', ',')) > 0)
                            ");
                            $stmt_lv->execute([$leg_id, $leg_id]);
                            $l_vols[] = (float)$stmt_lv->fetchColumn();
                        }

                        // Save to cache
                        $user_qual_cache[$upline_id] = [
                            'personal' => $p_vol,
                            'directs'  => $d_count,
                            'legs'     => $l_vols
                        ];
                    }

                    $qual = $user_qual_cache[$upline_id];

                    // Infinity Bonus Check (beyond Level 15)
                    $is_infinity_qualified = false;
                    if ($level > 15) {
                        $total_turnover = array_sum($qual['legs']);
                        if ($qual['personal'] >= 100000 && $qual['directs'] >= 50 && $total_turnover >= 10000000) {
                            $is_infinity_qualified = true;
                        } else {
                            break; // Stop paying commissions beyond level 15
                        }
                    }

                    if (!$is_infinity_qualified) {
                        $reqs = $level_requirements[$level] ?? ['directs' => 99, 'personal' => 999999, 'team_vol' => 9999999];

                        // Gate 1: Directs
                        if ($qual['directs'] < $reqs['directs']) {
                            continue; // Failed Directs requirement
                        }

                        // Gate 2: Personal Investment
                        if ($qual['personal'] < $reqs['personal']) {
                            continue; // Failed Personal Investment requirement
                        }

                        // Gate 3: Team Volume (40% Rule)
                        if ($reqs['team_vol'] > 0) {
                            $qualifying_tv = 0;
                            $max_cap = $reqs['team_vol'] * 0.40; // 40% max per leg
                            
                            foreach ($qual['legs'] as $lv) {
                                $qualifying_tv += min($lv, $max_cap);
                            }

                            if ($qualifying_tv < $reqs['team_vol']) {
                                continue; // Failed Team Volume requirement
                            }
                        }
                    }

                    // Passed All Gates - Pay Residual Bonus!
                    if ($is_infinity_qualified) {
                        $res_percent = (float)($settings["residual_level_$level"] ?? $settings["infinity_bonus_rate"] ?? $settings["residual_level_15"] ?? 0);
                    } else {
                        $res_percent = (float)($settings["residual_level_$level"] ?? 0);
                    }
                    if ($res_percent <= 0) continue;

                    $bonus = round($roi_payout * ($res_percent / 100), 8);
                    if ($bonus <= 0) continue;

                    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$bonus, $upline_id]);

                    $pdo->prepare("
                        INSERT INTO transactions (user_id, investment_id, amount, type, description, created_at)
                        VALUES (?, ?, ?, 'referral_bonus', ?, NOW())
                    ")->execute([$upline_id, $investment_id, $bonus, "Level {$level} Residual from Inv #{$investment_id}"]);

                    $stats['res_total'] += $bonus;
                }
            }
        }

        $pdo->commit();
        write_log("ROI_CREDIT: UID {$uid} | Inv {$investment_id} | Amount {$roi_payout}");
        $stats['roi_total'] += $roi_payout;
        $stats['investments_processed']++;

        if ($days_passed >= $duration_days) {
            $pdo->prepare("UPDATE investments SET status = 'completed' WHERE id = ?")->execute([$investment_id]);
            write_log("COMPLETED: Inv #{$investment_id} reached its duration of {$duration_days} days and has been closed.");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        write_log("CRITICAL_ERR: UID {$uid} | Inv {$investment_id} | ".$e->getMessage());
    }
}

/* ===============================
   RANK BONUS ENGINE
================================= */
foreach ($pdo->query("SELECT DISTINCT user_id FROM investments WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN) as $uid) {

    $pers_stmt = $pdo->prepare("
        SELECT IFNULL(SUM(i.amount),0) FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.user_id = ? AND i.status = 'active'
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
    ");
    $pers_stmt->execute([$uid]);
    $personal_vol = (float)$pers_stmt->fetchColumn();

    $team_stmt = $pdo->prepare("
        SELECT IFNULL(SUM(i.amount),0) FROM investments i
        JOIN users u ON i.user_id = u.id
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.user_id != ? 
        AND (u.referrer_id = ? OR FIND_IN_SET(?, REPLACE(u.path, '/', ',')) > 0)
        AND i.status = 'active'
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
    ");
    $team_stmt->execute([$uid, $uid, $uid]);
    $team_vol = (float)$team_stmt->fetchColumn();

    foreach ($ranks as $rk) {
        if ($personal_vol < $rk['personal_investment'] || $team_vol < $rk['team_volume']) continue;

        $paid_check = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND type = 'rank_reward' AND description = ? LIMIT 1");
        $paid_check->execute([$uid, "Rank Bonus: {$rk['rank_name']}"]);

        if ($paid_check->fetch()) continue;

        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$rk['bonus_amount'], $uid]);
        $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'rank_reward', ?, NOW())")
            ->execute([$uid, $rk['bonus_amount'], "Rank Bonus: {$rk['rank_name']}"]);

        write_log("PROMOTION: UID {$uid} achieved {$rk['rank_name']}");
    }
}

/* ===============================
   SUMMARY
================================= */
write_log("SUMMARY: Investments {$stats['investments_processed']} | ROI {$stats['roi_total']} | Residual {$stats['res_total']}");
write_log("PROTOCOL_FINISHED_");

echo "Cron Execution Complete.";