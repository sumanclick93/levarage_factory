<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

$log_dir = '../logs/';
$success = "";

// --- HANDLE LOG PURGE (Security Maintenance) ---
if (isset($_POST['purge_logs'])) {
    $files = glob($log_dir . 'roi_*.log');
    $now = time();
    foreach ($files as $file) {
        // Delete logs older than 30 days
        if ($now - filemtime($file) >= 60 * 60 * 24 * 30) {
            unlink($file);
        }
    }
    $success = "Legacy Log Nodes Purged Successfully_";
}

$selected_log = $_GET['log'] ?? (date('Y-m-d') . '.log');
$log_path = $log_dir . 'roi_' . $selected_log;

$log_files = glob($log_dir . 'roi_*.log');
rsort($log_files);

$log_content = "No log data found for node: " . htmlspecialchars($selected_log);
if (file_exists($log_path)) {
    $log_content = file_get_contents($log_path);
}

// --- ERROR HIGHLIGHTING LOGIC ---
// We wrap critical keywords in high-contrast spans
$highlighted_content = str_replace(
    ['CRITICAL ERROR', 'FAILED', 'SUMMARY:', 'PROMOTION:'],
    [
        '<span class="text-red-500 font-black animate-pulse">CRITICAL ERROR</span>',
        '<span class="text-red-400 font-bold">FAILED</span>',
        '<span class="text-[#00A6FB] font-black italic">SUMMARY:</span>',
        '<span class="text-yellow-400 font-bold italic">PROMOTION:</span>'
    ],
    htmlspecialchars($log_content)
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Terminal | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background-color: #F8FAFC; }
        .terminal-screen { background: #0a0a0a; border: 1px solid #1a1a1a; color: #d1d1d1; font-family: 'Courier New', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-y-auto animate__animated animate__fadeIn">
        <header class="flex justify-between items-end mb-10">
            <div>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Protocol Audit</p>
                <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">Security <span class="text-[#00A6FB]">& Logs</span>_</h1>
            </div>
            
            <div class="flex gap-4">
                <form method="POST" onsubmit="return confirm('Purge all logs older than 30 days?')">
                    <button type="submit" name="purge_logs" class="bg-red-50 text-red-500 px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest border border-red-100 hover:bg-red-500 hover:text-white transition-all">
                        Purge Legacy Nodes
                    </button>
                </form>
                <select onchange="window.location.href='?log='+this.value" class="bg-white border border-gray-200 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest outline-none shadow-sm">
                    <?php foreach($log_files as $file): 
                        $val = str_replace(['../logs/roi_', '.log'], '', $file); ?>
                        <option value="<?php echo $val; ?>.log" <?php echo ($selected_log == $val.'.log') ? 'selected' : ''; ?>>
                            Node: <?php echo $val; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>

        <?php if($success): ?>
            <div class="mb-8 p-4 bg-green-50 border border-green-100 text-green-600 rounded-2xl text-[10px] font-black uppercase tracking-widest italic animate__animated animate__headShake">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="terminal-screen rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden">
            <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 italic">Active_Audit_Feed: roi_<?php echo $selected_log; ?></p>
                </div>
                <p class="text-[9px] font-mono text-gray-600 uppercase">System Status: Encrypted</p>
            </div>

            <pre class="whitespace-pre-wrap text-[13px] leading-relaxed custom-scrollbar max-h-[600px] overflow-y-auto">
<?php echo $highlighted_content; ?>
            </pre>
        </div>
    </main>
</body>
</html>