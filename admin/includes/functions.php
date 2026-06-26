<?php
function distributeMLMCommissions($pdo, $investment_id) {

    /* ===============================
       LEVEL QUALIFICATION SETTINGS
       (Ensure these match your cron job settings)
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
       FETCH INVESTMENT + USER DATA
    ================================= */
    $stmt = $pdo->prepare("
        SELECT 
            i.amount,
            i.hash_ref,
            s.name AS scheme_name,
            s.type AS scheme_type,
            u.path,
            u.username
        FROM investments i
        JOIN users u ON i.user_id = u.id
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$investment_id]);
    $inv = $stmt->fetch();

    if (!$inv || empty($inv['path'])) return;

    /* ===============================
       DETECT CORPORATE / ADMIN
    ================================= */
    $is_corporate = stripos($inv['scheme_name'], 'corporate') !== false || $inv['scheme_type'] === 'corporate';
    $is_admin_assigned = stripos($inv['hash_ref'], 'COMPANY') !== false || stripos($inv['hash_ref'], 'ADMIN_ASSIGNED') !== false;

    /* Skip commissions if invalid */
    if ($is_corporate || $is_admin_assigned) {
        return;
    }

    /* ===============================
       FETCH MLM SETTINGS
    ================================= */
    $settings_stmt = $pdo->query("
        SELECT level, commission_percent 
        FROM mlm_settings 
        ORDER BY level ASC
    ");
    $mlm_rates = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    /* ===============================
       PREPARE UPLINE LIST
    ================================= */
    $path = str_replace(',', '/', $inv['path']);
    $upline_ids = array_reverse(explode('/', $path));

    // Cache to optimize database queries during the loop
    $user_qual_cache = [];

    foreach ($upline_ids as $index => $upline_id) {

        $upline_id = (int)$upline_id;
        if ($upline_id <= 0) continue;

        $level = $index + 1;

        /* ===============================
           GATEKEEPER QUALIFICATION CHECK
        ================================= */
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
                continue; // Skipped: Failed Directs requirement
            }

            // Gate 2: Personal Investment
            if ($qual['personal'] < $reqs['personal']) {
                continue; // Skipped: Failed Personal Investment requirement
            }

            // Gate 3: Team Volume (40% Rule)
            if ($reqs['team_vol'] > 0) {
                $qualifying_tv = 0;
                $max_cap = $reqs['team_vol'] * 0.40; // 40% max per leg
                
                foreach ($qual['legs'] as $lv) {
                    $qualifying_tv += min($lv, $max_cap);
                }

                if ($qualifying_tv < $reqs['team_vol']) {
                    continue; // Skipped: Failed Team Volume requirement
                }
            }
        }

        /* ===============================
           PAYOUT CALCULATION
        ================================= */
        if ($is_infinity_qualified) {
            $percentage = $mlm_rates[$level] ?? $mlm_rates['infinity'] ?? $mlm_rates[15] ?? 0;
        } else {
            if (!isset($mlm_rates[$level])) continue;
            $percentage = $mlm_rates[$level];
        }
        $bonus_amount = round(($inv['amount'] * $percentage) / 100, 8);

        if ($bonus_amount <= 0) continue;

        /* ===============================
           DUPLICATE PROTECTION
        ================================= */
        $check = $pdo->prepare("
            SELECT id 
            FROM transactions
            WHERE user_id = ?
            AND investment_id = ?
            AND type = 'referral_bonus'
            AND description LIKE ?
            LIMIT 1
        ");
        $check->execute([
            $upline_id,
            $investment_id,
            "%Level $level%"
        ]);

        if ($check->fetch()) {
            continue;
        }

        /* ===============================
           CREDIT WALLET
        ================================= */
        $pdo->prepare("
            UPDATE users
            SET wallet_balance = wallet_balance + ?
            WHERE id = ?
        ")->execute([$bonus_amount, $upline_id]);

        /* ===============================
           TRANSACTION RECORD
        ================================= */
        $desc = "Level $level Referral Bonus from {$inv['username']} (Inv #{$investment_id})";

        $pdo->prepare("
            INSERT INTO transactions
            (user_id, investment_id, amount, type, description, created_at)
            VALUES (?, ?, ?, 'referral_bonus', ?, NOW())
        ")->execute([
            $upline_id,
            $investment_id,
            $bonus_amount,
            $desc
        ]);
    }
}
?>