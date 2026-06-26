<?php
require_once('includes/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$message="";

if(isset($_POST['update_wallet'])){

$wallet_network = trim($_POST['wallet_network']);
$wallet_address = trim($_POST['wallet_address']);

$update = $pdo->prepare("
UPDATE users 
SET wallet_network=?, wallet_address=? 
WHERE id=?
");

$update->execute([$wallet_network,$wallet_address,$user_id]);

$message="Wallet updated successfully.";

$stmt->execute([$user_id]);
$user = $stmt->fetch();
}


$networks = $pdo->query("
SELECT symbol 
FROM currencies 
WHERE is_active = 1
ORDER BY symbol
")->fetchAll();


if(isset($_POST['change_password'])){

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

if(!password_verify($current_password,$user['password_hash'])){
    $message="Current password is incorrect.";
}
elseif($new_password != $confirm_password){
    $message="New passwords do not match.";
}
else{

$new_hash = password_hash($new_password,PASSWORD_DEFAULT);

$pdo->prepare("
UPDATE users 
SET password_hash=? 
WHERE id=?
")->execute([$new_hash,$user_id]);

$message="Password changed successfully.";

}
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen"> <?php include('includes/sidebar.php'); ?> <main class="flex-1 overflow-y-auto p-8">
      <header class="mb-8">
        <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic"> My Profile </h1>
        <p class="text-gray-500 text-sm"> Manage your account and withdrawal wallet. </p>
      </header> <?php if($message): ?> <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-xl text-sm font-bold"> <?php echo $message ?> </div> <?php endif; ?> <div class="bg-white rounded-[2rem] shadow-sm border border-gray-200 p-8">
        <!-- USER INFO -->
        <div class="grid md:grid-cols-2 gap-8 mb-10">
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"> Username </p>
            <p class="font-black text-lg text-gray-900"> <?php echo htmlspecialchars($user['username']); ?> </p>
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"> Email </p>
            <p class="font-black text-lg text-gray-900"> <?php echo htmlspecialchars($user['email']); ?> </p>
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"> Member Since </p>
            <p class="font-bold text-gray-700"> <?php echo date('M d, Y', strtotime($user['created_at'])); ?> </p>
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"> KYC Status </p> <?php if($user['kyc_status']=='approved'): ?> <span class="px-3 py-1 bg-green-100 text-green-600 text-[10px] font-black rounded-lg uppercase"> Approved </span> <?php else: ?> <span class="px-3 py-1 bg-yellow-100 text-yellow-600 text-[10px] font-black rounded-lg uppercase"> Pending </span> <?php endif; ?>
          </div>
        </div>
        <!-- WALLET BALANCES -->
        <div class="grid md:grid-cols-3 gap-6 mb-10">
          <div class="p-6 bg-gray-50 rounded-xl text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase">Main Wallet</p>
            <p class="text-xl font-black text-gray-900"> $ <?php echo number_format($user['wallet_balance'],2); ?> </p>
          </div>
          <div class="p-6 bg-gray-50 rounded-xl text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase">Corporate Wallet</p>
            <p class="text-xl font-black text-gray-900"> $ <?php echo number_format($user['corporate_wallet'],2); ?> </p>
          </div>
          <div class="p-6 bg-gray-50 rounded-xl text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase">Gold Wallet</p>
            <p class="text-xl font-black text-gray-900"> <?php echo number_format($user['gold_wallet'],2); ?>gm </p>
          </div>
        </div>
        <!-- WALLET UPDATE -->
        <form method="POST">
          <h2 class="font-black text-gray-900 uppercase text-sm mb-6"> Withdrawal Wallet </h2>
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="text-[10px] font-black text-gray-400 uppercase"> Network </label>
              <select name="wallet_network" class="w-full mt-2 p-4 bg-gray-50 border rounded-xl font-bold outline-none">
                <option value="">Select Network</option> <?php foreach($networks as $net): ?> <option value="
											
											<?php echo $net['symbol']; ?>" <?php if($user['wallet_network']==$net['symbol']) echo 'selected'; ?>> <?php echo $net['symbol']; ?> </option> <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="text-[10px] font-black text-gray-400 uppercase"> Wallet Address </label>
              <input type="text" name="wallet_address" value="
										
										<?php echo htmlspecialchars($user['wallet_address']); ?>" class="w-full mt-2 p-4 bg-gray-50 border rounded-xl font-bold outline-none">
            </div>
          </div>
          <button type="submit" name="update_wallet" class="mt-8 w-full py-5 bg-black text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] hover:bg-[#00A6FB] transition-all"> Update Wallet </button>
        </form>
        <div class="mt-12 border-t pt-10">
          <h2 class="font-black text-gray-900 uppercase text-sm mb-6"> Change Password </h2>
          <form method="POST">
            <div class="grid md:grid-cols-3 gap-6">
              <div>
                <label class="text-[10px] font-black text-gray-400 uppercase"> Current Password </label>
                <input type="password" name="current_password" required class="w-full mt-2 p-4 bg-gray-50 border rounded-xl font-bold outline-none">
              </div>
              <div>
                <label class="text-[10px] font-black text-gray-400 uppercase"> New Password </label>
                <input type="password" name="new_password" required class="w-full mt-2 p-4 bg-gray-50 border rounded-xl font-bold outline-none">
              </div>
              <div>
                <label class="text-[10px] font-black text-gray-400 uppercase"> Confirm Password </label>
                <input type="password" name="confirm_password" required class="w-full mt-2 p-4 bg-gray-50 border rounded-xl font-bold outline-none">
              </div>
            </div>
            <button type="submit" name="change_password" class="mt-8 w-full py-5 bg-black text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] hover:bg-[#00A6FB] transition-all"> Change Password </button>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>