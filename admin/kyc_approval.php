<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Handle KYC Decisions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kyc_action'])) {
    $uid = $_POST['user_id'];
    $status = ($_POST['kyc_action'] == 'approve') ? 'approved' : 'rejected';
    
    $stmt = $pdo->prepare("UPDATE users SET kyc_status = ? WHERE id = ?");
    $stmt->execute([$status, $uid]);
    
    logAdminAction($pdo, "KYC $status for User #$uid", $uid);
    header("Location: kyc_approval.php?success=1");
    exit();
}

// Fetch only users with pending KYC
$pending_kyc = $pdo->query("SELECT * FROM users WHERE kyc_status = 'pending' AND google_id IS NULL ORDER BY created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KYC Approvals - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">KYC Approval Queue</h1>
            <p class="text-gray-500">Review government-issued IDs for Malta regulatory compliance.</p>
        </header>

        <?php if (empty($pending_kyc)): ?>
            <div class="bg-blue-50 border border-blue-200 p-6 rounded-lg text-blue-700 text-center">
                No pending KYC requests at the moment.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($pending_kyc as $user): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg"><?php echo $user['username']; ?></h3>
                            <p class="text-sm text-gray-500"><?php echo $user['email']; ?></p>
                        </div>
                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Pending Review</span>
                    </div>
                    
                    <div class="bg-gray-100 rounded-lg h-48 mb-4 flex items-center justify-center border-2 border-dashed border-gray-300 overflow-hidden">
                        <p class="text-gray-400 text-sm italic">ID Document View</p>
                        </div>

                    <div class="flex gap-2 mt-4">
                        <form method="POST" class="flex-1">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="kyc_action" value="approve" class="w-full bg-green-600 text-white py-2 rounded-lg font-bold hover:bg-green-700 transition">Approve</button>
                        </form>
                        <form method="POST" class="flex-1">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="kyc_action" value="reject" class="w-full bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-700 transition">Reject</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>