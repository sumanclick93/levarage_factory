<?php
require_once('includes/db_connect.php');
session_start();

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- 1. DEFINE VARIABLES FIRST ---
$filter = $_GET['filter'] ?? 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;

// --- 2. FETCH USER BALANCES ---
$stmt = $pdo->prepare("SELECT wallet_balance, corporate_wallet FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- 3. FETCH TRANSACTIONS ---
$sql = "SELECT * FROM transactions WHERE user_id = :uid";
if ($filter !== 'all') {
    $sql .= " AND type = :type";
}
$sql .= " ORDER BY created_at DESC LIMIT :limit";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
if ($filter !== 'all') {
    $stmt->bindValue(':type', $filter, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Wallet - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="space-y-10 max-w-8xl mx-auto">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-black text-white p-8 rounded-3xl shadow-lg border border-gray-800">
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Total Balance</p>
                    <h2 class="text-3xl font-bold italic mb-6">$<?php echo number_format($user['wallet_balance'], 2); ?></h2>
                    
                    <div class="flex gap-4"> 
                        <a href="invest.php" class="flex-1 bg-white text-blue-900 px-4 py-2 rounded-md font-semibold text-center hover:bg-blue-50 transition">Deposit / Invest</a> 
                        <a href="withdraw.php?individual" class="flex-1 bg-white/20 text-white px-4 py-2 rounded-md font-semibold text-center hover:bg-white/30 transition">Withdraw</a> 
                    </div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Corporate Wallet</p>
                    <h2 class="text-3xl font-bold italic text-gray-900">$<?php echo number_format($user['corporate_wallet'], 2); ?></h2>
                    <div class="flex gap-4" style="margin-top: 25px;"> 
                        <a href="withdraw.php?corporate" class="flex-1 bg-black text-white px-4 py-2 rounded-md font-semibold text-center hover:bg-black transition">Withdraw</a> 
                    </div>
                </div>
            </div>

            
        </div>
    </main>
</body>
</html>