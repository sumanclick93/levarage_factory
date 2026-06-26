<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch investment schemes for the Personal ROI section
$stmt = $pdo->query("SELECT * FROM investment_schemes WHERE is_active = 1 ORDER BY total_return_percent DESC");
$schemes = $stmt->fetchAll();

// 15-Level Configuration dynamically fetched from database
$mlm_settings = $pdo->query("SELECT level, commission_percent FROM mlm_settings WHERE level <= 15 ORDER BY level ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$level_percents = [];
for ($i = 1; $i <= 15; $i++) {
    $level_percents[$i] = (float)($mlm_settings[$i] ?? 0);
}

$roi_result = null;
$total_potential = 0;
$level_breakdown = array_fill(1, 10, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Personal ROI Logic (Screenshot 2)
    if (isset($_POST['calc_roi'])) {
        $scheme_id = (int)$_POST['scheme_id'];
        $amount = (float)$_POST['amount'];
        $stmt = $pdo->prepare("SELECT * FROM investment_schemes WHERE id = ?");
        $stmt->execute([$scheme_id]);
        $s = $stmt->fetch();
        if ($s) {
            $payout = ($amount * ($s['total_return_percent'] / 100));
            $roi_result = ['payout' => $payout, 'name' => $s['name'], 'days' => $s['duration_days']];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Forecasting | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        
        /* Matrix Theme Styling for Referral Calc (Screenshot 1) */
        .matrix-bg { 
            background: linear-gradient(rgba(0,0,0,0.9), rgba(0,0,0,0.9)), url('matrix_bg.jpg'); 
            background-size: cover;
            border: 1px solid rgba(0, 166, 251, 0.2);
        }
        .matrix-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 10px;
            border-radius: 8px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
        <header class="mb-6">
            <h2 class="text-xs font-black text-[#00A6FB] uppercase tracking-widest mb-4 italic">Personal ROI Protocol_</h2>
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex-1">
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="text-[9px] font-black text-gray-400 uppercase mb-2 block">Strategy Selection</label>
                            <select name="scheme_id" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold outline-none">
                                <?php foreach($schemes as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?> (<?php echo $s['total_return_percent']; ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-black text-gray-400 uppercase mb-2 block">Investment Amount ($)</label>
                            <input type="number" name="amount" placeholder="e.g. 1000" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold outline-none">
                        </div>
                        <button type="submit" name="calc_roi" class="w-full py-4 bg-[#00A6FB] text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:bg-blue-600 transition">Calculate Direct Yield</button>
                    </form>
                </div>

                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex-[2] flex flex-col justify-center">
                    <p class="text-[9px] font-black text-gray-400 uppercase mb-2">Maturity Settlement Projection</p>
                    <p class="text-6xl font-black text-green-600 tracking-tighter">
                        $<?php echo $roi_result ? number_format($roi_result['payout'], 2) : '0.00'; ?>
                    </p>
                    <p class="text-xs text-gray-400 italic mt-2 font-bold">
                        Strategy: <?php echo $roi_result ? $roi_result['name'].' ('.$roi_result['days'].' Days)' : 'None Selected'; ?>
                    </p>
                </div>
            </div>
        </header>

        <div class="matrix-bg p-10 rounded-[2.5rem] mt-12 text-white shadow-2xl">
            <h2 class="text-center text-2xl font-black uppercase tracking-[0.2em] mb-12">Estimate Your Earnings</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <div class="space-y-4">
                    <?php for($i=1; $i<=15; $i++): ?>
                    <div class="relative">
                        <label class="text-[9px] font-bold text-[#00A6FB] uppercase absolute -top-2 left-3 bg-[#0a0a0a] px-2">Level <?php echo $i; ?> Deposit ($)</label>
                        <input type="number" id="dep_<?php echo $i; ?>" oninput="calculateMatrix()" placeholder="0" class="matrix-input pt-4 font-mono">
                    </div>
                    <?php endfor; ?>
                </div>

                <div>
                    <h3 class="text-[#00A6FB] font-black uppercase tracking-widest text-sm mb-8">Earnings Breakdown_</h3>
                    <div class="space-y-5 border-b border-white/10 pb-8 mb-8">
                        <?php foreach($level_percents as $lvl => $percent): ?>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-400 font-bold uppercase">Level <?php echo $lvl; ?> (<?php echo $percent; ?>%)</span>
                            <span class="font-mono font-bold" id="breakdown_<?php echo $lvl; ?>">$0.00</span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center">
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Total Potential Earnings</p>
                        <p class="text-5xl font-black text-[#00A6FB] tracking-tighter" id="total_pot">$0.00</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const percs = <?php echo json_encode(array_values($level_percents)); ?>;

        function calculateMatrix() {
            let total = 0;
            for(let i=1; i<=15; i++) {
                let val = parseFloat(document.getElementById('dep_'+i).value) || 0;
                let earned = val * ((percs[i-1] || 0) / 100);
                document.getElementById('breakdown_'+i).innerText = '$' + earned.toLocaleString(undefined, {minimumFractionDigits: 2});
                total += earned;
            }
            document.getElementById('total_pot').innerText = '$' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    </script>
</body>
</html>