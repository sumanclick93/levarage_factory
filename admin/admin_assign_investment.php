<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// PHPMailer Manual Loading
require_once(__DIR__ . '/PHPMailer-master/src/Exception.php');
require_once(__DIR__ . '/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer-master/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = "";
$error = "";

// Fetch Users and Schemes
$users = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();
$schemes = $pdo->query("SELECT id, name, min_amount FROM investment_schemes WHERE is_active = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_investment'])) {
    $target_user_id = $_POST['user_id'];
    $scheme_id = $_POST['scheme_id'];
    $amount = (float)$_POST['amount']; // Now pulled from the auto-filled/hidden field
    $description = trim($_POST['description']);

    $pdo->beginTransaction();
    try {
        // Insert record as 'active'
        $stmt = $pdo->prepare("INSERT INTO investments (user_id, scheme_id, amount, status, hash_ref, screenshot_url) VALUES (?, ?, ?, 'active', ?, '')");
        $stmt->execute([$target_user_id, $scheme_id, $amount, "ADMIN_ASSIGNED: " . $description]);
        
        $stmt_u = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt_u->execute([$target_user_id]);
        $user_data = $stmt_u->fetch();

        if ($user_data) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'localhost'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'admin@leveragefactory.ai'; 
                $mail->Password   = 'IR=hxVT!u]&3'; 
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
                $mail->setFrom('admin@leveragefactory.ai', 'Leverage Factory Finance');
                $mail->addReplyTo('admin@leveragefactory.ai', 'Leverage Factory Finance');
                $mail->Sender     = 'admin@leveragefactory.ai';
                
                $mail->addAddress($user_data['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Financial Node: Investment Assigned';
                $mail->Body    = "<div style='background-color: #0f172a; padding: 40px; color: #ffffff; text-align: center;'>
                                    <h2 style='color: #00A6FB;'>INVESTMENT ACTIVATED</h2>
                                    <p>Plan Amount: <b>$ " . number_format($amount, 2) . "</b></p>
                                  </div>";
                $mail->send();
            } catch (Exception $e) { }
        }
        $pdo->commit();
        $message = "Assignment Protocol Complete.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Investment - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Logic to automatically update the amount based on selected scheme
        function updateAmount() {
            const schemeSelect = document.getElementById('scheme_id');
            const amountInput = document.getElementById('amount_input');
            const selectedOption = schemeSelect.options[schemeSelect.selectedIndex];
            
            if (selectedOption.value !== "") {
                const minAmount = selectedOption.getAttribute('data-min');
                amountInput.value = minAmount;
            } else {
                amountInput.value = "";
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<style>
/* Make Select2 look like your Tailwind input */

.select2-container--default .select2-selection--single{
    height: 56px !important;
    padding: 12px !important;
    background:#f9fafb !important;
    border:1px solid #d1d5db !important;
    border-radius:12px !important;
    display:flex;
    align-items:center;
    font-weight:bold;
}

.select2-selection__rendered{
    line-height:30px !important;
}

.select2-selection__arrow{
    height:56px !important;
}

.select2-dropdown{
    border-radius:12px !important;
}
</style>
<body class="bg-gray-100 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-black uppercase italic italic text-gray-900">Assign <span class="text-[#00A6FB]">Investment</span>_</h1>
        </header>

        <div class="max-w-2xl bg-white p-10 rounded-[2.5rem] shadow-xl border border-gray-200">
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Target User</label>
                    <select name="user_id" id="user_select" required class="w-full p-4 bg-gray-50 border rounded-xl font-bold outline-none">
                        <option value="">-- Select Member --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['email']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Investment Scheme</label>
                        <select name="scheme_id" id="scheme_id" onchange="updateAmount()" required class="w-full p-4 bg-gray-50 border rounded-xl font-bold outline-none">
                            <option value="">-- Select Plan --</option>
                            <?php foreach($schemes as $s): ?>
                                <option value="<?php echo $s['id']; ?>" data-min="<?php echo $s['min_amount']; ?>">
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Calculated Amount ($)</label>
                        <input type="number" name="amount" id="amount_input"  class="w-full p-4 bg-gray-50 border rounded-xl font-bold outline-none">
                        <p class="text-[8px] text-gray-400 mt-1 uppercase font-bold">* Auto-populated by Scheme Protocol</p>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Reference Note</label>
                    <input type="text" name="description" class="w-full p-4 bg-gray-50 border rounded-xl outline-none">
                </div>

                <button type="submit" name="assign_investment" class="w-full py-5 bg-black text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] hover:bg-[#00A6FB] transition-all">Execute Assignment</button>
            </form>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#user_select').select2({
            placeholder: "Search or select user",
            allowClear: true,
            width: '100%'
        });
    });
    </script>
</body>
</html>