<?php
require_once('includes/db_connect.php');
session_start();

// Security: Redirect to login if session is missing
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Fetch User Data for Status and Sidebar
$stmt = $pdo->prepare("SELECT username, kyc_status, kyc_document_url FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Handle File Upload Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['kyc_doc'])) {
    if ($user['kyc_status'] == 'approved') {
        $error = "Your identity is already verified.";
    } else {
        $target_dir = "uploads/kyc/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES["kyc_doc"]["name"], PATHINFO_EXTENSION));
        // Rename for security: KYC_USERID_TIMESTAMP.ext
        $new_filename = "KYC_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        // Validations
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file_ext, $allowed)) {
            $error = "Invalid format. Use JPG, PNG, or PDF.";
        } elseif ($_FILES["kyc_doc"]["size"] > 5242880) { // 5MB
            $error = "File is too large (Limit 5MB).";
        } else {
            if (move_uploaded_file($_FILES["kyc_doc"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET kyc_status = 'pending', kyc_document_url = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
                $success = "Document submitted! Admin review in progress.";
                $user['kyc_status'] = 'pending'; // Refresh local state
            } else {
                $error = "Upload failed. Check folder permissions.";
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
    <title>KYC Verification - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">

    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-8xl mx-auto">
            
            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">Identity Verification</h1>
                    <p class="text-gray-500 text-sm mt-1">Malta Compliance: Please provide a valid Government ID or Passport.</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Account ID</span>
                    <p class="font-mono font-bold text-[#00A6FB]">#<?php echo str_pad($user_id, 5, '0', STR_PAD_LEFT); ?></p>
                </div>
            </header>

            <div class="mb-8 p-6 rounded-2xl flex items-center justify-between border-2 <?php 
                echo $user['kyc_status'] == 'approved' ? 'bg-green-50 border-green-200' : 
                    ($user['kyc_status'] == 'pending' ? 'bg-blue-50 border-blue-200' : 'bg-orange-50 border-orange-200'); ?>">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center <?php 
                        echo $user['kyc_status'] == 'approved' ? 'bg-green-500 text-white' : 'bg-white text-gray-400'; ?>">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Verification Status: <?php echo strtoupper($user['kyc_status']); ?></h3>
                        <p class="text-xs text-gray-500">
                            <?php 
                                if($user['kyc_status'] == 'approved') echo "Your account is fully compliant. You can now withdraw funds.";
                                elseif($user['kyc_status'] == 'pending') echo "Our compliance team is currently reviewing your documents.";
                                else echo "Identity proof is required to unlock withdrawal features.";
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if($success): ?>
                <div class="mb-6 p-4 bg-green-500 text-white rounded-xl text-sm font-bold shadow-lg text-center uppercase"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="mb-6 p-4 bg-red-500 text-white rounded-xl text-sm font-bold shadow-lg text-center uppercase"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8">
                    <?php if($user['kyc_status'] != 'approved'): ?>
                        <form action="kyc_upload.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-3xl p-12 text-center hover:border-[#00A6FB] transition group relative">
                                <input type="file" name="kyc_doc" id="kyc_doc" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="updateUI(this)">
                                <div id="upload_placeholder">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4 group-hover:text-[#00A6FB] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-black text-gray-700 uppercase tracking-widest">Select Identification File</p>
                                    <p class="text-[10px] text-gray-400 mt-2">PASSPORT / NATIONAL ID / DRIVING LICENSE</p>
                                </div>
                                <div id="file_info" class="hidden">
                                    <p class="text-[#00A6FB] font-bold" id="filename_text"></p>
                                    <p class="text-xs text-gray-400 mt-1">Ready for submission</p>
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-xl flex gap-4">
                                <svg class="w-5 h-5 text-[#00A6FB] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-[11px] text-blue-700 leading-tight">By submitting, you confirm that the document is valid, not expired, and clearly shows your full name and date of birth.</p>
                            </div>

                            <button type="submit" class="w-full py-4 bg-[#00A6FB] hover:bg-blue-600 text-white rounded-xl font-black uppercase tracking-widest shadow-xl shadow-blue-100 transition transform active:scale-95">
                                Start Verification Process
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Identity Secured</h3>
                            <p class="text-gray-500 text-sm mt-1">Your account is fully verified. No further action is required.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function updateUI(input) {
            if (input.files && input.files[0]) {
                document.getElementById('upload_placeholder').classList.add('hidden');
                document.getElementById('file_info').classList.remove('hidden');
                document.getElementById('filename_text').innerText = "Selected: " + input.files[0].name;
            }
        }
    </script>
</body>
</html>