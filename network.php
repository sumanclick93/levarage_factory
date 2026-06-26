<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User and Sponsor details
$stmt = $pdo->prepare("
    SELECT u.username, u.referral_code, u.referrer_id, u.path, s.username as sponsor_name 
    FROM users u 
    LEFT JOIN users s ON u.referrer_id = s.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$user_referral_code = $user['referral_code'];
$current_user_path = $user['path'];

// 2. Generate Referral Link
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$ref_link = $protocol . $_SERVER['HTTP_HOST'] . "/register.php?ref_code=" . $user_referral_code;

// 3. FIXED: Count Network size using Path Anchoring
// We create a prefix: (upline path) + (my ID) + /%
// This ensures we ONLY count branches growing directly out of this user node.
$network_prefix = ($current_user_path ? $current_user_path . "/" : "") . $user_id . "/%";

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE path LIKE ?");
$stmt_count->execute([$network_prefix]);
$total_network = $stmt_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Network - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">My <span class="text-[#00A6FB]">Network</span>_</h1>
            <p class="text-gray-500 text-sm">Track your sponsor and grow your 10-level earning tree.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Your Sponsor</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-[#00A6FB] rounded-full flex items-center justify-center font-bold">
                            <?php echo $user['sponsor_name'] ? substr($user['sponsor_name'], 0, 1) : '?'; ?>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">
                            <?php echo $user['sponsor_name'] ? htmlspecialchars($user['sponsor_name']) : "System Root"; ?>
                        </h2>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Your Referral Code</p>
                        <p class="text-2xl font-black text-[#00A6FB] font-mono"><?php echo $user['referral_code']; ?></p>
                    </div>
                </div>

                <div class="bg-gray-900 p-8 rounded-3xl shadow-xl text-white">
                    <p class="text-xs font-bold text-blue-400 uppercase mb-2">Network Growth</p>
                    <h3 class="text-3xl font-black"><?php echo number_format($total_network); ?> Members</h3>
                    <p class="text-gray-400 text-xs mt-2">Verified nodes in your organization downline.</p>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200 h-full">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Share Your Link</h2>
                    <p class="text-gray-500 mb-8 leading-relaxed">
                        Copy your unique link below and share it with your community. Every person who joins using this link becomes your Level 1 referral, unlocking commissions up to 10 levels deep.
                    </p>

                    <div class="space-y-4">
                        <div class="relative group">
                            <input type="text" readonly value="<?php echo $ref_link; ?>" id="refLink" 
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 text-sm font-mono text-gray-600 outline-none focus:border-[#00A6FB] transition">
                            <button onclick="copyRef()" class="absolute right-2 top-2 bottom-2 bg-[#00A6FB] hover:bg-blue-600 text-white px-6 rounded-xl font-bold text-xs uppercase tracking-widest transition">
                                Copy Link
                            </button>
                        </div>
                        <p id="copyStatus" class="text-center text-[10px] font-bold text-green-500 uppercase tracking-widest opacity-0 transition-opacity">Protocol: Link Copied</p>
                    </div>

                    <div class="mt-12 grid grid-cols-2 gap-4">
                        <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 text-center">
                            <p class="text-xs text-blue-700 font-bold uppercase">Level 1 Bonus</p>
                            <p class="text-lg font-black text-[#00A6FB]">Active</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                            <p class="text-xs text-gray-500 font-bold uppercase">Weekly Payouts</p>
                            <p class="text-lg font-black text-gray-900">Enabled</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function copyRef() {
            const copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(copyText.value);
            
            const status = document.getElementById("copyStatus");
            status.style.opacity = "1";
            setTimeout(() => { status.style.opacity = "0"; }, 2000);
        }
    </script>
</body>
</html>