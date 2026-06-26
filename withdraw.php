<?php
require_once('includes/db_connect.php');

// Manual Loading Protocol for PHPMailer
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
$error = "";
$success = "";

// ============================================================================
// 10X VOLUME PROTOCOL CALCULATOR
// ============================================================================

/**
 * Calculates non-corporate branch volume recursively with loop protection.
 */
function getNonCorporateBranchVolume($pdo, $node_id, &$visited = []) {
    if (isset($visited[$node_id])) return 0;
    $visited[$node_id] = true;
    
    $total = 0;
    // Add Personal Active NON-CORPORATE Investment
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(i.amount), 0) 
        FROM investments i 
        JOIN investment_schemes s ON i.scheme_id = s.id 
        WHERE i.user_id = ? AND i.status = 'active' AND s.name NOT LIKE '%Corporate%'
    ");
    $stmt->execute([$node_id]);
    $total += (float)$stmt->fetchColumn();

    // Recurse through their downline
    $stmt_refs = $pdo->prepare("SELECT id FROM users WHERE referrer_id = ?");
    $stmt_refs->execute([$node_id]);
    foreach ($stmt_refs->fetchAll() as $ref) {
        $total += getNonCorporateBranchVolume($pdo, $ref['id'], $visited);
    }
    return $total;
}

// A. Calculate Active Corporate Target
$stmt_corp = $pdo->prepare("
    SELECT IFNULL(SUM(i.amount), 0) 
    FROM investments i 
    JOIN investment_schemes s ON i.scheme_id = s.id 
    WHERE i.user_id = ? AND i.status = 'active' AND s.name LIKE '%Corporate%'
");
$stmt_corp->execute([$user_id]);
$total_corporate_investment = (float)$stmt_corp->fetchColumn();

// B. Define 10X Targets and 40% Leg Caps
$target_10x_volume = $total_corporate_investment * 10;
$max_per_leg = $target_10x_volume * 0.40;

// C. Calculate Qualified Team Volume (Direct Legs)
$stmt_directs = $pdo->prepare("SELECT id FROM users WHERE referrer_id = ?");
$stmt_directs->execute([$user_id]);
$directs = $stmt_directs->fetchAll();

$qualified_10x_volume = 0;
foreach ($directs as $direct) {
    $visited_nodes = [];
    $leg_vol = getNonCorporateBranchVolume($pdo, $direct['id'], $visited_nodes);
    
    if ($leg_vol > 0 && $target_10x_volume > 0) {
        // Cap the leg volume at 40% of the target
        $qualified_10x_volume += min($leg_vol, $max_per_leg);
    } elseif ($leg_vol > 0 && $target_10x_volume == 0) {
        $qualified_10x_volume += $leg_vol; 
    }
}

// D. Final Evaluation Flag
$is_10x_met = ($total_corporate_investment > 0 && $qualified_10x_volume >= $target_10x_volume);


// ============================================================================
// STANDARD SYSTEM FETCHES
// ============================================================================

// 1. Fetch User Balances (Main & Corporate), Lock Status, Saved Wallet Data AND User Details for Email
$stmt = $pdo->prepare("SELECT username, email, wallet_balance, corporate_wallet, is_locked, wallet_address, wallet_network FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. CHECK FOR DIRECT PERSONAL INVESTMENT
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM investments WHERE user_id = ? AND status = 'active'");
$stmt_check->execute([$user_id]);
$has_active_investment = (int)$stmt_check->fetchColumn();

// 3. Fetch Minimum Withdrawal Setting
$stmt_s = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_withdrawal'");
$min_withdrawal = (float)$stmt_s->fetchColumn();

// 4. CHECK FOR PENDING WITHDRAWALS
$stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM withdrawal_requests WHERE user_id = ? AND status = 'pending'");
$stmt_pending->execute([$user_id]);
$has_pending_withdrawal = ((int)$stmt_pending->fetchColumn()) > 0;

// Handle Withdrawal Post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_payout'])) {
    $amount = (float)$_POST['amount'];
    
    // Determine which wallet destination was selected
    $wallet_choice = $_POST['wallet_choice'] ?? 'other';
    if ($wallet_choice === 'saved') {
        $network = $user['wallet_network'];
        $wallet_address = $user['wallet_address'];
    } else {
        $network = trim($_POST['network'] ?? '');
        $wallet_address = trim($_POST['wallet_address'] ?? '');
    }
    
    // Securely identify the target wallet column
    $source_wallet = ($_POST['source_wallet'] === 'corporate_wallet') ? 'corporate_wallet' : 'wallet_balance';
    $wallet_label = ($source_wallet === 'corporate_wallet') ? 'Corporate Vault' : 'Main E-Wallet';
    $available_balance = (float)$user[$source_wallet];
    
    // SECURITY BLOCK: Enforcement of Investment Rule & 10X Protocol
    if ($has_active_investment <= 0) {
        $error = "Security Protocol: You must have at least one active personal investment to request a payout.";
    } elseif ($has_pending_withdrawal) { 
        $error = "Protocol Error: You already have a pending withdrawal request in the queue.";
    } elseif ($user['is_locked'] == 1) {
        $error = "Your wallet is currently locked. Please contact support.";
    } elseif ($source_wallet === 'corporate_wallet' && $total_corporate_investment <= 0) {
        $error = "10X Protocol Error: No active corporate assignment found to base your 10X target on.";
    } elseif ($source_wallet === 'corporate_wallet' && !$is_10x_met) {
        $error = "10X Protocol Validation Failed: You must achieve 10X non-corporate team volume ($" . number_format($target_10x_volume, 2) . ") to withdraw from the Corporate Vault.";
    } elseif ($amount < $min_withdrawal) {
        $error = "Minimum withdrawal amount is $" . number_format($min_withdrawal, 2);
    } elseif ($amount > $available_balance) {
        $error = "Insufficient balance in your $wallet_label.";
    } elseif (empty($network) || empty($wallet_address)) {
        $error = "Please provide both network and wallet address.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, network, wallet_address, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $amount, $network, $wallet_address]);

            // Dynamically update the selected wallet column
            $stmt = $pdo->prepare("UPDATE users SET $source_wallet = $source_wallet - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'withdrawal', ?)");
            $desc = "Withdrawal request: $" . $amount . " via " . $network . " from " . $wallet_label;
            $stmt->execute([$user_id, $amount, $desc]);

            $pdo->commit();
            $success = "Payout request submitted successfully from your $wallet_label!";
            
            $user[$source_wallet] -= $amount; 
            
            // DISPATCH WITHDRAWAL REQUEST EMAIL
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
                
                $mail->addAddress($user['email']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Withdrawal Request Received';
                
                // Formatted Email Variables
                $email_username = $user['username'];
                $display_amount = '$' . number_format($amount, 2);
                $display_currency = 'USD';
                $display_time = date('Y-m-d H:i:s T');

                $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;padding:0;background:#f3f5f9;font-family:Arial, Helvetica, sans-serif;}
.wrapper{width:100%;padding:30px 10px;}
.container{max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.08);}
.header{background:#0f172a;padding:35px;text-align:center;}
.logo{max-width:180px;}
.content{padding:35px;color:#333;line-height:1.6;}
.title{font-size:24px;font-weight:bold;margin-bottom:10px;}
.subtitle{font-size:14px;color:#666;margin-bottom:20px;}
.card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-top:20px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;}
.row:last-child{border-bottom:none;}
.label{color:#777;font-size:14px;}
.value{font-weight:bold;font-size:14px;word-break:break-all;}
.status{color:#f59e0b;font-weight:bold;}
.button{display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:6px;font-weight:bold;margin-top:25px;}
.footer{background:#f1f5f9;text-align:center;padding:25px;font-size:12px;color:#777;}
.website{margin-top:10px;font-weight:bold;}
</style>
</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<img src="https://leveragefactory.ai/wp-content/uploads/2026/02/logo.png" class="logo">
</div>
<div class="content">
<div class="title">Withdrawal Request Received</div>
<div class="subtitle">Hello <strong>$email_username</strong>, your withdrawal request has been received and is currently being processed.</div>
<div class="card">
<div class="row"><div class="label">Amount</div><div class="value"> $display_amount</div></div>
<div class="row"><div class="label">Currency</div><div class="value"> $display_currency</div></div>
<div class="row"><div class="label">Wallet Address</div><div class="value"> $wallet_address</div></div>
<div class="row"><div class="label">Network</div><div class="value"> $network</div></div>
<div class="row"><div class="label">Request Time</div><div class="value"> $display_time</div></div>
<div class="row"><div class="label">Status</div><div class="value status">Pending Review</div></div>
</div>
<center><a href="https://leveragefactory.ai/dashboard" class="button">View Withdrawal</a></center>
<p style="margin-top:25px;font-size:13px;color:#666;">If you did not authorize this withdrawal request, please contact support immediately.</p>
</div>
<div class="footer">
Security Reminder: Always keep your account secure.
<div class="website">https://leveragefactory.ai</div>
</div>
</div>
</div>
</body>
</html>
HTML;
                $mail->send();
            } catch (Exception $e) {
                // Fail silently to prevent interrupting the user flow
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen font-sans">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8 relative">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-[#00A6FB]/10 blur-[150px] -z-10"></div>
        
        <div class="max-w-6xl mx-auto space-y-8 animate__animated animate__fadeIn">
            <header>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Financial Terminal</p>
                <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">Weekly <span class="text-[#00A6FB]">Payout</span></h1>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    <div class="bg-black p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden group space-y-8">
                        <div class="relative z-10 border-b border-gray-800 pb-8">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Main E-Wallet</p>
                            <h2 class="text-4xl font-black text-white">$<?php echo number_format($user['wallet_balance'], 2); ?></h2>
                        </div>
                        
                        <div class="relative z-10">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Corporate Vault</p>
                            <h2 class="text-3xl font-black text-[#00A6FB]">$<?php echo number_format($user['corporate_wallet'], 2); ?></h2>
                        </div>

                        <div class="relative z-10 mt-6 flex items-center gap-2 pt-4 border-t border-gray-800">
                            <span class="w-2 h-2 rounded-full <?php echo ($has_active_investment > 0) ? 'bg-green-500' : 'bg-red-500'; ?> animate-pulse"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">
                                <?php echo ($has_active_investment > 0) ? 'Node: Active Funding' : 'Node: Inactive Funding'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white p-10 rounded-[2.5rem] border border-gray-100 shadow-sm">
                        <?php if($success): ?>
                            <div class="mb-8 p-4 bg-green-50 text-green-600 border border-green-200 rounded-2xl text-xs font-black uppercase text-center tracking-widest"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if($error): ?>
                            <div class="mb-8 p-4 bg-red-50 text-red-600 border border-red-200 rounded-2xl text-xs font-black uppercase text-center tracking-widest"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if($has_active_investment <= 0): ?>
                            <div class="p-12 bg-gray-50 border-2 border-dashed border-gray-200 rounded-[3rem] text-center">
                                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-400 font-black text-2xl">!</div>
                                <h3 class="text-gray-900 font-black uppercase tracking-widest italic">Withdrawal Locked</h3>
                                <p class="text-[11px] text-gray-500 mt-2 uppercase font-bold leading-relaxed">Your account protocol requires an active investment node to proceed.<br>Please select an investment plan from the portal.</p>
                                <a href="invest.php" class="mt-8 inline-block bg-[#00A6FB] text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest">Browse Plans_</a>
                            </div>
                        <?php elseif($user['is_locked']): ?>
                            <div class="p-10 bg-orange-50 border border-orange-200 rounded-[2rem] text-center">
                                <h3 class="text-orange-600 font-black uppercase tracking-widest italic">Security Node Restricted</h3>
                                <p class="text-[11px] text-orange-500/80 mt-2 uppercase font-bold">Contact Administrator for account verification.</p>
                            </div>
                        <?php elseif($has_pending_withdrawal): ?> 
                            <div class="p-12 bg-blue-50 border-2 border-dashed border-blue-200 rounded-[3rem] text-center">
                                <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center mx-auto mb-6 text-blue-500 font-black text-2xl">⏳</div>
                                <h3 class="text-gray-900 font-black uppercase tracking-widest italic">Request Pending</h3>
                                <p class="text-[11px] text-gray-500 mt-2 uppercase font-bold leading-relaxed">You already have a withdrawal request processing in the protocol queue.<br>Please wait for it to be completed or rejected before initiating a new one.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="space-y-6">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mb-3 italic">01. Source Wallet_</label>
                                        <select id="wallet_selector" name="source_wallet" required class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-gray-900 font-bold text-sm transition">
                                            <option value="wallet_balance">Main E-Wallet ($<?php echo number_format($user['wallet_balance'], 2); ?>)</option>
                                            <option value="corporate_wallet">Corporate Vault ($<?php echo number_format($user['corporate_wallet'], 2); ?>)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mb-3 italic">02. Amount (USD)_</label>
                                        <input type="number" step="0.01" name="amount" required placeholder="0.00" 
                                               class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-gray-900 font-black text-xl">
                                    </div>
                                </div>

                                <div id="corp-warning" class="hidden bg-gray-900 p-6 rounded-2xl shadow-inner text-white">
                                    <p class="text-[10px] text-[#00A6FB] font-black uppercase tracking-widest mb-3 italic">10X Corporate Security Protocol_</p>
                                    
                                    <div class="flex justify-between text-xs font-black mb-1">
                                        <span>Qualified Team Volume</span>
                                        <span class="<?php echo $is_10x_met ? 'text-green-400' : 'text-orange-400'; ?>">$<?php echo number_format($qualified_10x_volume, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-[10px] text-gray-400 font-bold mb-3">
                                        <span>Target Required</span>
                                        <span>$<?php echo number_format($target_10x_volume, 2); ?></span>
                                    </div>

                                    <div class="w-full bg-gray-800 rounded-full h-1.5 mb-2">
                                        <?php $pct = min(100, ($qualified_10x_volume / max(1, $target_10x_volume)) * 100); ?>
                                        <div class="bg-[#00A6FB] h-1.5 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                                    </div>
                                    
                                    <p class="text-[9px] text-gray-500 italic mb-3">* Max 40% ($<?php echo number_format($max_per_leg, 2); ?>) allowed per team leg.</p>
                                    
                                    <?php if(!$is_10x_met): ?>
                                    <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-center">
                                        <p class="text-[10px] text-red-500 font-black uppercase tracking-tighter">! Target not met. Corporate payouts disabled.</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="p-3 bg-green-500/10 border border-green-500/20 rounded-xl text-center">
                                        <p class="text-[10px] text-green-400 font-black uppercase tracking-tighter">Protocol Achieved. You may proceed.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.3em] mb-3 italic">03. Select Network & Address_</label>
                                    <select id="wallet_choice" name="wallet_choice" class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-gray-900 font-bold text-sm">
                                        <?php if(!empty($user['wallet_address']) && !empty($user['wallet_network'])): ?>
                                            <option value="saved">Network: <?php echo htmlspecialchars($user['wallet_network']); ?> | Address: <?php echo htmlspecialchars($user['wallet_address']); ?></option>
                                            <option value="other">Others (Enter New Network & Address)</option>
                                        <?php else: ?>
                                            <option value="other" selected>Others (Enter New Network & Address)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div id="other_wallet_fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 <?php echo (!empty($user['wallet_address']) && !empty($user['wallet_network'])) ? 'hidden' : ''; ?>">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-3 italic">Select Protocol_</label>
                                        <select name="network" id="network_input" class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-gray-900 font-bold text-sm">
                                            <option value="" disabled selected>-- Choose Protocol --</option>
                                            <option value="ERC20">ERC20 (Ethereum)</option>
                                            <option value="TRC20">TRC20 (Tron)</option>
                                            <option value="BEP20">BEP20 (Binance Smart Chain)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-3 italic">Destination Address_</label>
                                        <input type="text" name="wallet_address" id="wallet_input" placeholder="Paste Wallet Address" 
                                               class="w-full p-5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] text-gray-900 font-mono text-xs">
                                    </div>
                                </div>

                                <div class="bg-white/5 border border-gray-100 p-5 rounded-2xl mt-4">
                                    <p class="text-[10px] text-[#00A6FB] font-black uppercase tracking-widest mb-1 italic">Protocol Notice_</p>
                                    <p class="text-[11px] text-gray-400 leading-relaxed font-medium italic">Ensure your wallet address matches the selected network. Assets sent to the wrong network protocol cannot be recovered.</p>
                                </div>

                                <button type="submit" id="submit_btn" name="request_payout" class="w-full py-6 bg-[#00A6FB] hover:bg-black text-white rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl transition-all transform active:scale-95 text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                    Initiate Payout Protocol
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Corporate warning logic
            const walletSelector = document.getElementById('wallet_selector');
            const corpWarningBox = document.getElementById('corp-warning');
            const submitBtn = document.getElementById('submit_btn');
            const is10xMet = <?php echo $is_10x_met ? 'true' : 'false'; ?>;

            if (walletSelector) {
                walletSelector.addEventListener('change', function(e) {
                    if (e.target.value === 'corporate_wallet') {
                        corpWarningBox.classList.remove('hidden');
                        if (!is10xMet) {
                            submitBtn.disabled = true;
                            submitBtn.classList.replace('bg-[#00A6FB]', 'bg-gray-400');
                            submitBtn.classList.replace('hover:bg-black', 'hover:bg-gray-400');
                        }
                    } else {
                        corpWarningBox.classList.add('hidden');
                        submitBtn.disabled = false;
                        submitBtn.classList.replace('bg-gray-400', 'bg-[#00A6FB]');
                        submitBtn.classList.replace('hover:bg-gray-400', 'hover:bg-black');
                    }
                });
            }

            // Saved vs Other wallet logic
            const walletChoice = document.getElementById('wallet_choice');
            const otherWalletFields = document.getElementById('other_wallet_fields');
            const networkInput = document.getElementById('network_input');
            const walletInput = document.getElementById('wallet_input');

            function toggleWalletFields() {
                if (!walletChoice || !otherWalletFields) return;
                
                if (walletChoice.value === 'other') {
                    otherWalletFields.classList.remove('hidden');
                    networkInput.required = true;
                    walletInput.required = true;
                } else {
                    otherWalletFields.classList.add('hidden');
                    networkInput.required = false;
                    walletInput.required = false;
                }
            }

            if (walletChoice) {
                walletChoice.addEventListener('change', toggleWalletFields);
                toggleWalletFields(); // Initialize state on load
            }
        });
    </script>
</body>
</html>