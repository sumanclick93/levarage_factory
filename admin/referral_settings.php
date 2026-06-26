<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

$success = "";

// --- 1. HANDLE UPDATES ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // A. Update Direct Commissions (mlm_settings table)
    if (isset($_POST['update_direct'])) {
        foreach ($_POST['direct'] as $level => $percent) {
            $stmt = $pdo->prepare("UPDATE mlm_settings SET commission_percent = ? WHERE level = ?");
            $stmt->execute([$percent, $level]);
        }
        $success = "Direct Commission Protocol Updated Successfully_";
    }

    // B. Update Residual Bonuses (system_settings table)
    if (isset($_POST['update_residual'])) {
        foreach ($_POST['residual'] as $level => $percent) {
            $key = "residual_level_" . $level;
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$percent, $key]);
        }
        $success = "Residual Bonus Protocol Updated Successfully_";
    }
}

// --- 2. FETCH DATA ---
// Fetch Direct Commissions
$direct_commissions = $pdo->query("SELECT level, commission_percent FROM mlm_settings ORDER BY level ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Residual Bonuses
$residual_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'residual_level_%'");
$residual_raw = $residual_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Referral Settings | Admin Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-y-auto animate__animated animate__fadeIn">
        <header class="mb-10">
            <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Network Control</p>
            <h1 class="text-4xl font-black uppercase italic tracking-tighter">Referral <span class="text-[#00A6FB]">Settings</span>_</h1>
        </header>

        <?php if($success): ?>
            <div class="mb-8 p-4 bg-green-50 border border-green-100 text-green-600 rounded-2xl text-[10px] font-black uppercase tracking-widest animate__animated animate__shakeX">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="space-y-12">
            
            <section class="animate__animated animate__fadeInUp">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-8 bg-[#00A6FB] rounded-full"></div>
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter">Direct <span class="text-[#00A6FB]">Commissions</span></h2>
                </div>

                <form method="POST" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                        <?php for($i=1; $i<=10; $i++): ?>
                        <div class="group">
                            <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 group-hover:text-[#00A6FB]">Level <?php echo $i; ?></label>
                            <div class="relative">
                                <input type="number" step="0.01" name="direct[<?php echo $i; ?>]" value="<?php echo $direct_commissions[$i] ?? 0; ?>" 
                                       class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 font-black text-xs">%</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-50 flex justify-end">
                        <button type="submit" name="update_direct" class="bg-black text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-lg hover:bg-[#00A6FB] transition-all">
                            Update Direct Protocol
                        </button>
                    </div>
                </form>
            </section>

            <section class="animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-8 bg-purple-500 rounded-full"></div>
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter">Residual <span class="text-purple-500">Bonuses</span></h2>
                </div>

                <form method="POST" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                        <?php for($i=1; $i<=10; $i++): 
                            $key = "residual_level_" . $i;
                            $val = $residual_raw[$key] ?? 0;
                        ?>
                        <div class="group">
                            <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 group-hover:text-purple-500">Level <?php echo $i; ?></label>
                            <div class="relative">
                                <input type="number" step="0.01" name="residual[<?php echo $i; ?>]" value="<?php echo $val; ?>" 
                                       class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-purple-500 font-bold">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 font-black text-xs">%</span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-50 flex justify-end">
                        <button type="submit" name="update_residual" class="bg-black text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-lg hover:bg-purple-600 transition-all">
                            Update Residual Protocol
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </main>
</body>
</html>