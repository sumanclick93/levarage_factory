<?php
require_once('includes/db_connect.php');

/**
 * UPLINE PERFORMANCE PROTOCOL - DAILY ROLLING CYCLE
 * Trigger: Runs daily to pay members on their 7th day of qualification.
 */

// 1. IDENTIFY ALL POTENTIALLY QUALIFIED DOWNLINES
// Criteria: Investment >= 10k AND 10 Direct Refs with >= 2.5k each.
$stmt = $pdo->query("
    SELECT id, referrer_id, created_at FROM users 
    WHERE is_locked = 0 
    AND referrer_id IS NOT NULL
    AND id IN (
        SELECT user_id FROM investments WHERE status = 'active' 
        GROUP BY user_id HAVING SUM(amount) >= 10000
    )
    AND (
        SELECT COUNT(DISTINCT u.id) 
        FROM users u
        JOIN investments i ON u.id = i.user_id
        WHERE u.referrer_id = users.id 
        AND i.status = 'active'
        GROUP BY u.id HAVING SUM(i.amount) >= 2500
    ) >= 10
");
$candidates = $stmt->fetchAll();

foreach ($candidates as $member) {
    $member_id = $member['id'];
    $upline_id = $member['referrer_id'];

    // 2. CHECK IF TODAY IS THE 7TH DAY (OR MULTIPLE OF 7) SINCE JOINING/QUALIFYING
    // Note: In a live system, you might track 'qualified_at' date; here we use 'created_at' for the cycle.
    $days_active = $pdo->query("SELECT DATEDIFF(NOW(), '{$member['created_at']}')")->fetchColumn();

    if ($days_active > 0 && $days_active % 7 == 0) {
        
        // 3. CALCULATE UPLINE'S TOTAL ROI EARNED IN THE LAST 7 DAYS
        $stmt = $pdo->prepare("
            SELECT SUM(amount) FROM transactions 
            WHERE user_id = ? AND type = 'roi' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$upline_id]);
        $upline_weekly_roi = (float)$stmt->fetchColumn();

        if ($upline_weekly_roi > 0) {
            
            // 4. FIND OTHER MEMBERS QUALIFYING FOR THIS SAME UPLINE TODAY
            // This ensures the 100% is shared if multiple people hit Day 7 today.
            $sharing_count = 0;
            foreach ($candidates as $other) {
                if ($other['referrer_id'] == $upline_id) {
                    $other_days = $pdo->query("SELECT DATEDIFF(NOW(), '{$other['created_at']}')")->fetchColumn();
                    if ($other_days > 0 && $other_days % 7 == 0) $sharing_count++;
                }
            }

            $payout_amount = $upline_weekly_roi / $sharing_count;

            $pdo->beginTransaction();
            try {
                // PAY THE QUALIFIED NODE
                $update = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $update->execute([$payout_amount, $member_id]);

                // LOG THE PERFORMANCE SETTLEMENT
                $log = $pdo->prepare("
                    INSERT INTO transactions (user_id, amount, type, description) 
                    VALUES (?, ?, 'performance_bonus', ?)
                ");
                $desc = "Rolling 7-Day Upline Performance Bonus from Upline #$upline_id (Split: $sharing_count)";
                $log->execute([$member_id, $payout_amount, $desc]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }
    }
}

echo "Rolling Performance Protocol Executed Successfully_";
?>