<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

// Handle Deletion Protocol
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM newsletter_subs WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_subscribers.php?status=deleted");
    exit();
}

// Fetch total subscriber count for stats
$count = $pdo->query("SELECT COUNT(*) FROM newsletter_subs")->fetchColumn();

// Fetch all entries
$subscribers = $pdo->query("SELECT * FROM newsletter_subs ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribers | Admin Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-[#F8FAFC] text-gray-900 flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-y-auto animate__animated animate__fadeIn">
        <header class="flex justify-between items-end mb-10">
            <div>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Network Expansion</p>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Subscriber <span class="text-[#00A6FB]">Nodes</span></h1>
            </div>
            <div class="flex gap-4">
                <button onclick="exportCSV()" class="bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-gray-50 transition shadow-sm">
                    Export_Dataset.csv
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Audience</p>
                <p class="text-3xl font-black text-gray-900"><?php echo number_format($count); ?> <span class="text-xs text-[#00A6FB]">Active</span></p>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Email_Address</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Subscription_Date</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($subscribers)): ?>
                        <tr><td colspan="3" class="p-20 text-center text-gray-300 font-bold uppercase italic text-xs">No network nodes found_</td></tr>
                    <?php endif; ?>

                    <?php foreach($subscribers as $s): ?>
                    <tr class="hover:bg-gray-50/50 transition group">
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-[#00A6FB]">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2.5"/></svg>
                                </div>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($s['email']); ?></span>
                            </div>
                        </td>
                        <td class="p-6 text-xs text-gray-400 font-mono">
                            <?php echo date('Y-m-d H:i', strtotime($s['created_at'])); ?>
                        </td>
                        <td class="p-6 text-right">
                            <a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Purge this subscriber from the database?')" class="opacity-0 group-hover:opacity-100 bg-red-50 text-red-500 p-3 rounded-xl hover:bg-red-500 hover:text-white transition-all inline-block">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function exportCSV() {
            let csv = 'Email,Subscription Date\n';
            <?php foreach($subscribers as $s): ?>
                csv += "<?php echo $s['email']; ?>,<?php echo $s['created_at']; ?>\n";
            <?php endforeach; ?>
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'Leverage_Subscribers_<?php echo date('Ymd'); ?>.csv');
            a.click();
        }
    </script>
</body>
</html>