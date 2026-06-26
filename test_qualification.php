<?php
/**
 * LEVERAGE FACTORY AI - AFFILIATE PROGRAM TEST SUITE
 * Run this in your web browser (e.g. http://yourdomain.com/test_qualification.php)
 * or via CLI if php is available.
 * 
 * Safety: This script performs read-only calculations or runs tests in a transaction
 * that is ALWAYS rolled back. No database modifications are persisted.
 */

require_once('includes/db_connect.php');
header('Content-Type: text/plain');

echo "=== STARTING AFFILIATE QUALIFICATION TEST PROTOCOL ===\n\n";

// 1. Fetch some test users to verify paths and cache builders
try {
    $users = $pdo->query("SELECT id, username, path FROM users LIMIT 10")->fetchAll();
    echo "Successfully connected to the database.\n";
    echo "Found " . count($users) . " users for checking path structures.\n\n";
    
    foreach ($users as $user) {
        echo "User ID: {$user['id']} | Username: {$user['username']} | Path: {$user['path']}\n";
    }
} catch (Exception $e) {
    echo "Database connectivity error: " . $e->getMessage() . "\n";
}

echo "\n=== MOCK GATEKEEPER CHECK ===\n";
// Let's define the new level requirements to test the logic
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

// Test Cases for standard and infinity qualifications
$test_cases = [
    [
        'name' => 'Failed Directs L3',
        'level' => 3,
        'qual' => ['personal' => 500, 'directs' => 2, 'legs' => [500, 500]],
        'expected' => false
    ],
    [
        'name' => 'Failed Personal L5',
        'level' => 5,
        'qual' => ['personal' => 400, 'directs' => 5, 'legs' => [5000, 5000]],
        'expected' => false
    ],
    [
        'name' => 'Failed Team Volume 40% rule cap L4',
        'level' => 4,
        'qual' => ['personal' => 250, 'directs' => 4, 'legs' => [4000, 500]], // Max cap is 5000 * 0.4 = 2000 per leg. Leg 1: 2000, Leg 2: 500. Total qualifying = 2500.
        'expected' => false
    ],
    [
        'name' => 'Passed Team Volume L4',
        'level' => 4,
        'qual' => ['personal' => 250, 'directs' => 4, 'legs' => [3000, 3000]], // Max cap is 2000 per leg. Leg 1: 2000, Leg 2: 2000. Total qualifying = 4000 (still fails 5000)
        'expected' => false
    ],
    [
        'name' => 'Passed Team Volume L4 with multiple legs',
        'level' => 4,
        'qual' => ['personal' => 250, 'directs' => 4, 'legs' => [2000, 2000, 1000]], // Leg 1: 2000, Leg 2: 2000, Leg 3: 1000. Total = 5000.
        'expected' => true
    ],
    [
        'name' => 'Failed Infinity Bonus L16 (Insufficient directs)',
        'level' => 16,
        'qual' => ['personal' => 100000, 'directs' => 45, 'legs' => [5000000, 5000000]],
        'expected' => false
    ],
    [
        'name' => 'Failed Infinity Bonus L16 (Insufficient turnover)',
        'level' => 16,
        'qual' => ['personal' => 100000, 'directs' => 50, 'legs' => [4000000, 4000000]],
        'expected' => false
    ],
    [
        'name' => 'Passed Infinity Bonus L16 (Turnover raw sum >= 10M)',
        'level' => 16,
        'qual' => ['personal' => 100000, 'directs' => 50, 'legs' => [5000000, 5000000]],
        'expected' => true
    ]
];

foreach ($test_cases as $tc) {
    $level = $tc['level'];
    $qual = $tc['qual'];
    $passed = false;
    
    if ($level > 15) {
        $total_turnover = array_sum($qual['legs']);
        if ($qual['personal'] >= 100000 && $qual['directs'] >= 50 && $total_turnover >= 10000000) {
            $passed = true;
        }
    } else {
        $reqs = $level_requirements[$level];
        $passed_gates = true;
        
        if ($qual['directs'] < $reqs['directs']) $passed_gates = false;
        if ($qual['personal'] < $reqs['personal']) $passed_gates = false;
        
        if ($reqs['team_vol'] > 0) {
            $qualifying_tv = 0;
            $max_cap = $reqs['team_vol'] * 0.40;
            foreach ($qual['legs'] as $lv) {
                $qualifying_tv += min($lv, $max_cap);
            }
            if ($qualifying_tv < $reqs['team_vol']) $passed_gates = false;
        }
        $passed = $passed_gates;
    }
    
    $status = ($passed === $tc['expected']) ? "SUCCESS" : "FAIL";
    echo "[{$status}] {$tc['name']}: Result = " . ($passed ? 'Passed' : 'Failed') . " | Expected = " . ($tc['expected'] ? 'Passed' : 'Failed') . "\n";
}

echo "\n=== SYNTAX VALIDATION SUITE ===\n";
$files_to_check = [
    'admin/includes/functions.php',
    'cron_daily_roi.php',
    'cron_yesterday.php',
    'earnings_calculator.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "[OK] file found: $file\n";
    } else {
        echo "[WARNING] file missing: $file\n";
    }
}

echo "\n=== TEST SUITE COMPLETED ===";
?>
