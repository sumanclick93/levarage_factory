<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active investments joined with scheme details
$stmt = $pdo->prepare("
    SELECT i.*, s.name as scheme_name, s.duration_days, s.total_return_percent ,s.type
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$user_id]);
$investments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Plans & ROI - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">Active Plans</h1>
            <p class="text-gray-500 text-sm">Monitor your current working capital and live daily returns.</p>
        </header>

        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Plan Details</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Invested</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Income Generated</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timeline</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($investments)): ?>
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-400 italic">No active investments. <a href="invest.php" class="text-[#00A6FB] font-bold not-italic hover:underline">Start Trading</a></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($investments as $inv): 
                        // Date Calculations
                        $start_date = new DateTime($inv['payout_started_at']);
                        $today = new DateTime();
                        $duration = $inv['duration_days'];
                        $scheme_type = $inv['type'];
                        
                        // Calculate End Date
                        $end_date = clone $start_date;
                        $end_date->modify("+$duration days");
                        
                        // Calculate Elapsed Days (capped at duration)
                        $diff = $start_date->diff($today);
                        $days_elapsed = $diff->invert ? 0 : $diff->days;
                        if ($days_elapsed > $duration) $days_elapsed = $duration;

                        // Calculate Income Generated
                        $total_profit_percentage = $inv['total_return_percent'] / 100;
                        $daily_rate = $total_profit_percentage / $duration;
                        $income_so_far = $inv['amount'] * ($daily_rate * $days_elapsed);

                        // Calculate Remaining
                        $interval = $today->diff($end_date);
                        $days_remaining = $interval->invert ? 0 : $interval->days;
                    ?>
                    <tr class="hover:bg-blue-50/30 transition">
                        <td class="p-6">
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($inv['scheme_name']); ?> </p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">
                                ROI: <?php echo $inv['total_return_percent']; ?>% over <?php echo $duration; ?> Days
                            </p>
                        </td>
                        <td class="p-6">
                            <span class="font-black text-gray-900">$<?php echo number_format($inv['amount'], 2); ?></span>
                        </td>
                        <td class="p-6">
                            <div class="flex flex-col">
                                <span class="font-black text-green-600 text-lg">+$<?php echo number_format($income_so_far, 2); ?></span>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Income Generated</span>
                            </div>
                        </td>
                        <td class="p-6 text-sm text-gray-500">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] uppercase font-bold text-gray-400">Ends: <?php echo $end_date->format('M d, Y'); ?></span>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[#00A6FB] h-full" style="width: <?php echo ($days_elapsed / $duration) * 100; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="px-3 py-1 bg-blue-100 text-[#00A6FB] text-[10px] font-black rounded-lg uppercase">
                                <?php echo $days_remaining; ?> Days Left
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>