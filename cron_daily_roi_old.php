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
   LOAD SETTINGS
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
   PROCESS ROI
================================= */
/* ===============================
   PROCESS ROI
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
    
    // Fixed NULL hash bug while reading
    $is_admin_assigned = stripos((string)$inv['hash_ref'], 'COMPANY') !== false || stripos((string)$inv['hash_ref'], 'ADMIN_ASSIGNED') !== false;

    // --- NEW LOGIC: Ripple Effect & Completion ---
    $is_ripple = stripos($scheme_name, 'Ripple Effect') !== false;

    // 1. Ripple Effect Rule: Hold all payouts until 35 days have passed
    if ($is_ripple && $days_passed < 35) {
        continue; // Silently skip ROI generation for this investment today
    }

    // 2. Calculate ROI Amount
    if ($is_ripple) {
        // Ripple Effect: Pay the FULL accumulated ROI at the end of the 35 days
        $roi_payout = round(($amount * (float)$inv['total_return_percent']) / 100, 8);
        $description = "Total ROI for Inv #{$investment_id} ({$inv['total_return_percent']}% Return) -> ";
    } else {
        // Normal Plans: Pay Daily ROI
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
            SELECT id 
            FROM transactions 
            WHERE user_id = ?
            AND type = 'roi'
            AND investment_id = ?
            AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $check->execute([$uid, $investment_id]);

        if ($check->fetch()) {
            $pdo->rollBack();
            continue;
        }

        /* ===== Credit Wallet ===== */
        $update_sql = "
            UPDATE users 
            SET {$target_wallet} = {$target_wallet} + ?
            WHERE id = ?
        ";
        $stmt_update = $pdo->prepare($update_sql);
        $stmt_update->execute([$roi_payout, $uid]);

        /* ===== Insert ROI Transaction ===== */
        $description = "Daily ROI for Inv #{$investment_id} ({$daily_percent}% daily) -> {$wallet_label}";

        $stmt_tx = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, investment_id, amount, type, description, created_at)
            VALUES (?, ?, ?, 'roi', ?, NOW())
        ");
        $stmt_tx->execute([$uid, $investment_id, $roi_payout, $description]);

        /* ===============================
           RESIDUAL DISTRIBUTION
        ================================= */

        if (!$is_corporate && !$is_admin_assigned) {

            $path_stmt = $pdo->prepare("SELECT path FROM users WHERE id = ?");
            $path_stmt->execute([$uid]);
            $path_str = $path_stmt->fetchColumn();

            if (!empty($path_str)) {

                $path_str = str_replace(',', '/', $path_str);
                $uplines = explode('/', $path_str);
                $uplines = array_filter($uplines);

                if (end($uplines) == $uid) {
                    array_pop($uplines);
                }

                $uplines = array_reverse($uplines);
                $uplines = array_values($uplines);

                foreach ($uplines as $index => $upline_id) {

                    $upline_id = (int)$upline_id;
                    if ($upline_id <= 0) continue;

                    $level = $index + 1;
                    if ($level > 10) break;

                    $res_percent = (float)($settings["residual_level_$level"] ?? 0);
                    if ($res_percent <= 0) continue;

                    $bonus = round($roi_payout * ($res_percent / 100), 8);
                    if ($bonus <= 0) continue;

                    $pdo->prepare("
                        UPDATE users 
                        SET wallet_balance = wallet_balance + ?
                        WHERE id = ?
                    ")->execute([$bonus, $upline_id]);

                    $pdo->prepare("
                        INSERT INTO transactions
                        (user_id, investment_id, amount, type, description, created_at)
                        VALUES (?, ?, ?, 'referral_bonus', ?, NOW())
                    ")->execute([
                        $upline_id,
                        $investment_id,
                        $bonus,
                        "Level {$level} Residual from Inv #{$investment_id}"
                    ]);

                    $stats['res_total'] += $bonus;
                }
            }

        } else {

            write_log("RESIDUAL_SKIPPED: Inv {$investment_id} Corporate/Admin");
        }

        $pdo->commit();

        write_log("ROI_CREDIT: UID {$uid} | Inv {$investment_id} | Amount {$roi_payout}");

        $stats['roi_total'] += $roi_payout;
        $stats['investments_processed']++;

        // ==========================================
        // NEW LOGIC: Mark Investment as Completed
        // ==========================================
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

foreach ($pdo->query("
    SELECT DISTINCT user_id 
    FROM investments 
    WHERE status = 'active'
")->fetchAll(PDO::FETCH_COLUMN) as $uid) {

    // Personal Volume (only valid individual packages)
    $pers_stmt = $pdo->prepare("
        SELECT SUM(i.amount)
        FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.user_id = ?
        AND i.status = 'active'
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
        AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
    ");
    $pers_stmt->execute([$uid]);
    $personal_vol = (float)$pers_stmt->fetchColumn();

    // Team Volume (only valid individual packages from downline)
    $team_stmt = $pdo->prepare("
        SELECT SUM(i.amount)
        FROM investments i
        JOIN users u ON i.user_id = u.id
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.user_id != ? 
        AND (u.referrer_id = ? OR FIND_IN_SET(?, REPLACE(u.path, '/', ',')) > 0)
        AND i.status = 'active'
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%'
        AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
    ");
    $team_stmt->execute([$uid, $uid, $uid]);
    $team_vol = (float)$team_stmt->fetchColumn();

    foreach ($ranks as $rk) {

        if ($personal_vol < $rk['personal_investment'] || $team_vol < $rk['team_volume']) {
            continue;
        }

        $paid_check = $pdo->prepare("
            SELECT id FROM transactions
            WHERE user_id = ?
            AND type = 'rank_reward'
            AND description = ?
            LIMIT 1
        ");
        $paid_check->execute([$uid, "Rank Bonus: {$rk['rank_name']}"]);

        if ($paid_check->fetch()) continue;

        $pdo->prepare("
            UPDATE users SET wallet_balance = wallet_balance + ?
            WHERE id = ?
        ")->execute([$rk['bonus_amount'], $uid]);

        $pdo->prepare("
            INSERT INTO transactions 
            (user_id, amount, type, description, created_at)
            VALUES (?, ?, 'rank_reward', ?, NOW())
        ")->execute([$uid, $rk['bonus_amount'], "Rank Bonus: {$rk['rank_name']}"]);

        write_log("PROMOTION: UID {$uid} achieved {$rk['rank_name']}");
    }
}


/* ===============================
   SUMMARY
================================= */

write_log("SUMMARY: Investments {$stats['investments_processed']} | ROI {$stats['roi_total']} | Residual {$stats['res_total']}");
write_log("PROTOCOL_FINISHED_");

echo "Cron Execution Complete.";