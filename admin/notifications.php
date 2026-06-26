<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // 1. Send New Notification
    if ($_POST['action'] == 'send_alert') {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type']; // info, warning, success

        $stmt = $pdo->prepare("INSERT INTO global_notifications (title, message, type, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $message, $type, $_SESSION['admin_id']]);
        
        logAdminAction($pdo, "Broadcasted Global Notification: $title");
        $success = "Announcement sent to all users!";
    }

    // 2. Delete/Archive Notification
    if ($_POST['action'] == 'delete') {
        $nid = $_POST['notif_id'];
        $pdo->prepare("DELETE FROM global_notifications WHERE id = ?")->execute([$nid]);
        logAdminAction($pdo, "Deleted notification #$nid");
    }

    header("Location: notifications.php?success=1");
    exit();
}

$notifications = $pdo->query("SELECT * FROM global_notifications ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Notifications - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Global Notifications</h1>
                <p class="text-gray-500">Broadcast news alerts or system messages to every member dashboard.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sticky top-8">
                    <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        Compose Broadcast
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="send_alert">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Alert Type</label>
                            <select name="type" class="w-full border rounded-lg p-2.5 mt-1 outline-none focus:ring-2 focus:ring-red-500">
                                <option value="info">Information (Blue)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Urgent/Danger (Red)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Headline</label>
                            <input type="text" name="title" required placeholder="e.g. New USDT-ERC20 Gateway" class="w-full border rounded-lg p-2.5 mt-1 outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Message Content</label>
                            <textarea name="message" rows="4" required placeholder="Type your message here..." class="w-full border rounded-lg p-2.5 mt-1 outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-[#E63946] text-white py-3 rounded-xl font-bold shadow-lg hover:bg-red-700 transition transform active:scale-95">
                            Broadcast Now
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-lg font-bold text-gray-700 px-2">Recent Broadcasts</h2>
                <?php if(empty($notifications)): ?>
                    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
                        No previous notifications found.
                    </div>
                <?php endif; ?>

                <?php foreach($notifications as $n): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm flex gap-4 hover:shadow-md transition">
                    <div class="shrink-0">
                        <?php 
                        $colors = [
                            'info' => 'bg-blue-100 text-blue-600',
                            'warning' => 'bg-yellow-100 text-yellow-600',
                            'success' => 'bg-green-100 text-green-600',
                            'danger' => 'bg-red-100 text-red-600'
                        ];
                        $colorClass = $colors[$n['type']] ?? $colors['info'];
                        ?>
                        <div class="w-12 h-12 rounded-full flex items-center justify-center <?php echo $colorClass; ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="font-bold text-gray-900"><?php echo $n['title']; ?></h3>
                            <span class="text-[10px] text-gray-400 font-mono"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed"><?php echo nl2br($n['message']); ?></p>
                        
                        <div class="mt-4 flex justify-end">
                            <form method="POST" onsubmit="return confirm('Remove this notification?')">
                                <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                                <button type="submit" name="action" value="delete" class="text-xs font-bold text-gray-400 hover:text-red-600 transition uppercase tracking-widest">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>