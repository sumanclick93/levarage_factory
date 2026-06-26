<?php
require_once('includes/db_connect.php');

// Manual Loading Protocol for PHPMailer-master
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$scheme_id = isset($_GET['scheme_id']) ? (int)$_GET['scheme_id'] : 0;

// 1. Fetch User Profile and Current Wallet Balance (Added username and email for the mailer)
$stmt_u = $pdo->prepare("SELECT username, email, wallet_balance FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch();
$current_wallet = (float)$user_data['wallet_balance'];
$username = $user_data['username'];
$email = $user_data['email'];

// 2. Fetch the selected investment plan
$stmt_s = $pdo->prepare("SELECT * FROM investment_schemes WHERE id = ? AND is_active = 1");
$stmt_s->execute([$scheme_id]);
$scheme = $stmt_s->fetch();

if (!$scheme) {
    die("Error: Investment plan not found.");
}

$minimum_required = (float)$scheme['min_amount'];
$wallets = $pdo->query("SELECT * FROM currencies WHERE is_active = 1")->fetchAll();
$message = "";
$error_msg = "";


// 3. CHECK FOR DIRECT PERSONAL INVESTMENT
// We count active investments specifically for this user node.
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM investments WHERE user_id = ? AND status = 'active'");
$stmt_check->execute([$user_id]);
$has_active_investment = (int)$stmt_check->fetchColumn();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_payment'])) {
    $invest_amount = (float)$_POST['invest_amount'];
    $wallet_usage = isset($_POST['use_wallet']) ? (float)$_POST['wallet_amount_to_use'] : 0;
    $ref_no = trim($_POST['ref_no']);
    
    // NEW: Capture the selected currency ID
    $currency_id = !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null;
    
    // VALIDATION PROTOCOL
    if ($invest_amount < $minimum_required) {
        $error_msg = "Minimum investment for this plan is $" . number_format($minimum_required, 2);
    } elseif ($wallet_usage > $current_wallet) {
        $error_msg = "Critical: Wallet deduction exceeds available balance.";
    } elseif ($wallet_usage > $invest_amount) {
        $error_msg = "Wallet deduction cannot exceed the total investment amount.";
    } else {
        $remaining_to_pay = $invest_amount - $wallet_usage;
        $filename = ""; // FIXED: Initialize as empty string instead of null

        // 4. MANDATORY EXTERNAL PAYMENT VALIDATION
        if ($remaining_to_pay > 0) {
            // NEW: Ensure a currency is selected if external payment is required
            if (empty($currency_id)) {
                $error_msg = "Please select a deposit asset for the external balance of $" . number_format($remaining_to_pay, 2);
            } elseif (empty($ref_no)) {
                $error_msg = "Transaction Hash (TXID) is mandatory for the external balance of $" . number_format($remaining_to_pay, 2);
            } elseif (empty($_FILES["screenshot"]["name"])) {
                $error_msg = "Proof of payment (screenshot) is mandatory for the external balance of $" . number_format($remaining_to_pay, 2);
            } else {
                $target_dir = "uploads/payments/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES["screenshot"]["name"], PATHINFO_EXTENSION));
                $filename = "PAY_SPLIT_" . $user_id . "_" . time() . "." . $file_ext;
                move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_dir . $filename);
            }
        }

        if (!$error_msg) {
            $pdo->beginTransaction();
            try {
                // 5. INSERT INVESTMENT RECORD (UPDATED to include currency_id)
                $stmt = $pdo->prepare("INSERT INTO investments (user_id, scheme_id, amount, wallet_deduction, currency_id, hash_ref, screenshot_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $scheme['id'], $invest_amount, $wallet_usage, $currency_id, $ref_no, $filename]);

                // 6. DEDUCT FROM WALLET (If applicable)
                if ($wallet_usage > 0) {
                    $upd = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $upd->execute([$wallet_usage, $user_id]);
                    
                    // Log the deduction
                    $log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'investment_deduction', ?)");
                    $log->execute([$user_id, $wallet_usage, "Wallet deduction for Plan: " . $scheme['name']]);
                }

                $pdo->commit();
                $message = "success";

                // 7. DISPATCH PENDING DEPOSIT NOTIFICATION EMAIL
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'localhost'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'support@leveragefactory.ai'; 
                    $mail->Password   = 'ShohanPassword22!'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465; 
                    $mail->CharSet    = 'UTF-8';
                    $mail->Timeout    = 10;

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    /* IMPORTANT — required by Namecheap */
                    $mail->setFrom('support@leveragefactory.ai', 'Leverage Factory');
                    $mail->addReplyTo('support@leveragefactory.ai', 'Leverage Factory');
                    $mail->Sender     = 'support@leveragefactory.ai';
                    
                    $mail->addAddress($email);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Deposit Pending Approval';
                    
                    $display_txid = !empty($ref_no) ? $ref_no : 'INTERNAL-WALLET';
                    $display_amount = '$' . number_format($invest_amount, 2);

                    $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;padding:0;background:#f2f4f8;font-family:Arial, Helvetica, sans-serif;}
.wrapper{width:100%;padding:30px 10px;}
.container{max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.08);}
.header{background:#0f172a;padding:35px;text-align:center;}
.logo{max-width:180px;}
.content{padding:35px;color:#333;line-height:1.6;}
.title{font-size:24px;font-weight:bold;margin-bottom:15px;color:#111;}
.subtitle{font-size:14px;color:#666;margin-bottom:25px;}
.card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;}
.row:last-child{border-bottom:none;}
.label{color:#777;font-size:14px;}
.value{font-weight:bold;font-size:14px;}
.status-pending{color:#ea580c;font-weight:bold;}
.button{display:inline-block;margin-top:25px;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 30px;border-radius:6px;font-weight:bold;font-size:14px;}
.footer{background:#f1f5f9;text-align:center;padding:25px;font-size:12px;color:#777;}
.website{margin-top:10px;font-weight:bold;color:#111;}
.social{margin-top:15px;}
.social a{margin:0 6px;text-decoration:none;font-size:12px;color:#2563eb;}
@media only screen and (max-width:600px){.content{padding:25px;}.row{flex-direction:column;}.value{margin-top:4px;}}
</style>
</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>
<div class="content">
<div class="title">Deposit Pending Approval</div>
<div class="subtitle">Hello <strong> $username</strong>, your deposit request has been received and is currently under review by our team.</div>
<div class="card">
<div class="row"><div class="label">Transaction ID</div><div class="value"> $display_txid</div></div>
<div class="row"><div class="label">Amount</div><div class="value"> $display_amount</div></div>
<div class="row"><div class="label">Currency</div><div class="value"> USD</div></div>
<div class="row"><div class="label">Status</div><div class="value status-pending">Pending Review</div></div>
</div>
<p style="margin-top:25px;font-size:14px;">We will notify you via email as soon as your deposit has been approved and credited to your wallet.</p>
<center><a href="https://leveragefactory.ai/dashboard" class="button">View Dashboard</a></center>
<p style="margin-top:25px;font-size:13px;color:#666;">If you did not authorize this transaction, please contact support immediately.</p>
</div>
<div class="footer">
Security Reminder: Never share your login credentials or verification codes.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                    $mail->send();
                } catch (Exception $e) {
                    // Fail silently so the UI still displays success
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Protocol Error: Database update failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleWalletInput() {
            const container = document.getElementById('wallet_input_container');
            const checkbox = document.getElementById('use_wallet');
            container.classList.toggle('hidden', !checkbox.checked);
            if(!checkbox.checked) {
                document.getElementById('wallet_amount_input').value = 0;
                document.getElementById('wallet_amount_slider').value = 0;
            }
            updatePaymentDisplay();
        }

        function syncWalletInput(source) {
            const inputVal = document.getElementById('wallet_amount_input');
            const sliderVal = document.getElementById('wallet_amount_slider');
            
            if (source === 'slider') {
                inputVal.value = sliderVal.value;
            } else if (source === 'text') {
                sliderVal.value = inputVal.value;
            }
            
            updatePaymentDisplay();
        }

        function updatePaymentDisplay() {
            const totalInput = document.getElementById('invest_amount');
            const walletInput = document.getElementById('wallet_amount_input');
            const walletSlider = document.getElementById('wallet_amount_slider');
            const total = parseFloat(totalInput.value) || 0;
            
            const maxAllowed = Math.min(total, <?php echo $current_wallet; ?>);
            walletSlider.max = maxAllowed;
            
            let currentWalletVal = parseFloat(walletInput.value) || 0;
            if(currentWalletVal > maxAllowed) {
                currentWalletVal = maxAllowed;
                walletInput.value = maxAllowed.toFixed(2);
                walletSlider.value = maxAllowed;
            } else if (currentWalletVal < 0) {
                currentWalletVal = 0;
                walletInput.value = 0;
                walletSlider.value = 0;
            }

            const wallet = document.getElementById('use_wallet').checked ? currentWalletVal : 0;
            const remaining = Math.max(0, total - wallet);
            
            document.getElementById('remaining_display').innerText = remaining.toFixed(2);
            document.getElementById('external_payment_protocol').classList.toggle('hidden', remaining <= 0);
        }

        function updateWalletInfo() {
            const select = document.getElementById('wallet_select');
            const selectedOption = select.options[select.selectedIndex];
            if(selectedOption.value === "") {
                document.getElementById('wallet_info_box').classList.add('hidden');
                return;
            }
            document.getElementById('display_address').innerText = selectedOption.getAttribute('data-address');
            document.getElementById('display_qr').src = 'uploads/' + selectedOption.getAttribute('data-qr'); 
            document.getElementById('wallet_info_box').classList.remove('hidden');
        }

        function copyToClipboard() {
            const addressText = document.getElementById('display_address').innerText;
            navigator.clipboard.writeText(addressText).then(() => {
                const btn = document.getElementById('copy_btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Copied!';
                btn.classList.add('bg-green-500');
                setTimeout(() => { btn.innerHTML = originalText; btn.classList.remove('bg-green-500'); }, 2000);
            });
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 relative">
        <div class="max-w-5xl mx-auto">
            <header class="mb-10">
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Secure Gateway</p>
                <h1 class="text-4xl font-black uppercase italic">Investment <span class="text-[#00A6FB]">Portal</span></h1>
            </header>

            <?php if(!empty($error_msg)): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-black uppercase text-center border border-red-100 mb-6 shadow-sm">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if($message == "success"): ?>
                <div class="bg-white p-12 rounded-[3rem] text-center shadow-xl border border-gray-200">
                    <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"/></svg>
                    </div>
                    <h2 class="text-2xl font-black uppercase mb-4">Deposit Registered</h2>
                    <p class="text-gray-500 mb-8">Admin node will verify the split-funding protocol within 24 hours.</p>
                    <a href="dashboard.php" class="bg-black text-white px-10 py-4 rounded-xl font-bold uppercase text-xs">Return to Dashboard</a>
                </div>
            <?php else: ?>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-200">
                        <p class="text-[10px] font-black text-[#00A6FB] uppercase mb-4 italic">Investment Plan: <?php echo htmlspecialchars($scheme['name']); ?></p>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Total Capital to Invest ($)</label>
                        <input type="number" name="invest_amount" id="invest_amount" step="0.01" min="<?php echo $minimum_required; ?>" 
                               value="<?php echo $minimum_required; ?>" oninput="updatePaymentDisplay()" required 
                               class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl text-2xl font-black outline-none focus:ring-2 focus:ring-[#00A6FB]">
                    </div>
                    
                    <?php if ($has_active_investment): ?>
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <label class="text-[10px] font-black text-[#00A6FB] uppercase italic">Internal Funding_</label>
                            <span class="text-[10px] font-bold text-gray-400">Available: $<?php echo number_format($current_wallet, 2); ?></span>
                        </div>
                        
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-6">
                            <input type="checkbox" name="use_wallet" id="use_wallet" onchange="toggleWalletInput()" class="w-6 h-6 accent-[#00A6FB]">
                            <span class="text-xs font-bold uppercase tracking-widest">Deduct from E-Wallet</span>
                        </div>

                        <div id="wallet_input_container" class="hidden space-y-4">
                            <div class="flex justify-between items-end mb-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Deduction Amount</p>
                                <div class="flex items-center gap-1">
                                    <span class="text-2xl font-black text-[#00A6FB]">$</span>
                                    <input type="number" name="wallet_amount_to_use" id="wallet_amount_input" min="0" step="0.01" value="0"
                                           oninput="syncWalletInput('text')" 
                                           class="w-32 bg-transparent text-2xl font-black text-[#00A6FB] outline-none border-b-2 border-dashed border-[#00A6FB] text-right focus:border-solid">
                                </div>
                            </div>
                            <input type="range" id="wallet_amount_slider" min="0" step="0.01" value="0"
                                   oninput="syncWalletInput('slider')" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#00A6FB]">
                            <div class="flex justify-between text-[8px] font-bold text-gray-400 uppercase tracking-tighter">
                                <span>$0.00</span>
                                <span>Max allowed deduction</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bg-black text-white p-8 rounded-[2.5rem] shadow-xl">
                        <p class="text-[10px] font-black text-gray-500 uppercase italic mb-2">Required External Deposit_</p>
                        <p class="text-4xl font-black italic tracking-tighter text-[#00A6FB]">$<span id="remaining_display"><?php echo number_format($minimum_required, 2); ?></span></p>
                    </div>
                </div>

                <div id="payment_details_column" class="space-y-6">
                    <div id="external_payment_protocol" class="space-y-6">
                        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-200">
                            <label class="block text-[10px] font-black text-[#00A6FB] uppercase mb-4 italic">Select Deposit Asset_</label>
                            <select id="wallet_select" name="currency_id" onchange="updateWalletInfo()" class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl font-bold outline-none">
                                <option value="">-- Choose Currency --</option>
                                <?php foreach($wallets as $w): ?>
                                    <option value="<?php echo $w['id']; ?>" data-address="<?php echo $w['wallet_address']; ?>" data-qr="<?php echo $w['qr_code_url']; ?>">
                                        <?php echo $w['name']; ?> (<?php echo $w['symbol']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="wallet_info_box" class="hidden bg-white p-8 rounded-[2.5rem] border border-gray-200 text-center">
                            <img id="display_qr" src="" class="w-44 h-44 mx-auto rounded-3xl bg-gray-50 p-3 mb-6">
                            <div class="relative group mb-6">
                                <p id="display_address" class="text-[10px] font-mono break-all bg-gray-50 p-5 pr-16 rounded-xl border border-gray-200 text-[#00A6FB] text-left"></p>
                                <button id="copy_btn" onclick="copyToClipboard()" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 bg-[#00A6FB] text-white px-3 py-2 rounded-lg text-[9px] font-black uppercase">Copy</button>
                            </div>
                            <div class="text-left space-y-4">
                                <input type="text" name="ref_no" placeholder="TXID / Hash" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl font-mono text-xs">
                                <input type="file" name="screenshot" accept="image/*" class="w-full text-xs text-gray-400 file:bg-gray-100 file:border-0 file:rounded-lg file:px-4 file:py-2 file:mr-4 file:font-bold">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="submit_payment" class="w-full py-6 bg-[#00A6FB] text-white rounded-[2rem] font-black uppercase tracking-widest shadow-lg hover:bg-black transition active:scale-95 text-xs">Confirm Investment Protocol</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>