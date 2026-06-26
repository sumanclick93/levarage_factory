<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $currency_id = $_POST['currency_id'] ?? null;

    // 1. ADD / EDIT Logic
    if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
        $name = $_POST['name'];
        $symbol = $_POST['symbol'];
        $address = $_POST['wallet_address'];
        $qr_path = $_POST['existing_qr'] ?? '';

        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
            $target_dir = "../uploads/qr/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_ext = pathinfo($_FILES["qr_code"]["name"], PATHINFO_EXTENSION);
            $file_name = strtolower($symbol) . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_dir . $file_name)) {
                $qr_path = "qr/" . $file_name;
            }
        }

        if ($_POST['action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO currencies (name, symbol, wallet_address, qr_code_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $symbol, $address, $qr_path]);
            logAdminAction($pdo, "Added currency: $name");
        } else {
            $stmt = $pdo->prepare("UPDATE currencies SET name = ?, symbol = ?, wallet_address = ?, qr_code_url = ? WHERE id = ?");
            $stmt->execute([$name, $symbol, $address, $qr_path, $currency_id]);
            logAdminAction($pdo, "Updated currency: $name");
        }
    }

    // 2. TOGGLE STATUS Logic
    if ($_POST['action'] == 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE currencies SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$currency_id]);
        logAdminAction($pdo, "Toggled status for currency ID #$currency_id");
    }

    // 3. DELETE Logic
    if ($_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM currencies WHERE id = ?");
        $stmt->execute([$currency_id]);
        logAdminAction($pdo, "Deleted currency ID #$currency_id");
    }

    header("Location: currency_manager.php?success=1");
    exit();
}

$currencies = $pdo->query("SELECT * FROM currencies ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Currency Manager - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Currency Manager</h1>
                <p class="text-gray-500 mt-1">Manage active payment gateways and wallet addresses.</p>
            </div>
            <button onclick="openModal('add')" class="bg-[#E63946] text-white px-6 py-2 rounded-lg font-bold shadow-md hover:bg-red-700 transition">
                + Add Currency
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($currencies as $c): ?>
            <div class="bg-white border <?php echo $c['is_active'] ? 'border-gray-200' : 'border-red-200 opacity-75'; ?> rounded-xl shadow-sm p-6 flex flex-col hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-gray-600">
                            <?php echo substr($c['symbol'], 0, 1); ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900"><?php echo $c['name']; ?></h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded <?php echo $c['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo $c['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                            </span>
                        </div>
                    </div>
                    <button onclick='openModal("edit", <?php echo json_encode($c); ?>)' class="text-blue-600 hover:text-blue-800 text-xs font-bold uppercase">Edit</button>
                </div>

                <div class="mb-6">
                    <label class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Wallet Address</label>
                    <code class="text-xs bg-gray-50 p-2 rounded block break-all border border-gray-100"><?php echo $c['wallet_address']; ?></code>
                </div>

                <div class="mt-auto pt-4 border-t border-gray-100 flex gap-2">
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="currency_id" value="<?php echo $c['id']; ?>">
                        <button type="submit" name="action" value="toggle_status" class="w-full py-2 text-xs font-bold border rounded hover:bg-gray-50 transition">
                            <?php echo $c['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete this currency?')">
                        <input type="hidden" name="currency_id" value="<?php echo $c['id']; ?>">
                        <button type="submit" name="action" value="delete" class="px-3 py-2 text-red-500 hover:bg-red-50 rounded border border-transparent hover:border-red-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="currencyModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8 overflow-hidden">
        <h2 id="modalTitle" class="text-2xl font-bold text-gray-900 mb-6">Add Currency</h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="currency_id" id="currencyId">
            <input type="hidden" name="existing_qr" id="existingQr">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Name</label>
                    <input type="text" name="name" id="field_name" required 
                        class="w-full border border-gray-300 rounded-lg p-2.5 mt-1 focus:ring-2 focus:ring-red-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Symbol</label>
                    <input type="text" name="symbol" id="field_symbol" required 
                        class="w-full border border-gray-300 rounded-lg p-2.5 mt-1 focus:ring-2 focus:ring-red-500 outline-none" placeholder="USDT">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Wallet Address</label>
                <input type="text" name="wallet_address" id="field_address" required 
                    class="w-full border border-gray-300 rounded-lg p-2.5 mt-1 focus:ring-2 focus:ring-red-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">QR Code Image</label>
                <input type="file" name="qr_code" 
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 mt-2">
                <p class="text-[10px] text-gray-400 mt-1 italic">Leave empty to keep existing QR code when editing.</p>
            </div>

            <div class="flex gap-3 pt-6">
                <button type="button" onclick="closeModal()" 
                    class="flex-1 py-3 border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" 
                    class="flex-1 py-3 bg-[#E63946] text-white rounded-xl font-bold shadow-lg hover:bg-red-700 transition">
                    Save Currency
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('currencyModal');
            document.getElementById('formAction').value = mode;
            if(mode === 'edit') {
                document.getElementById('modalTitle').innerText = "Edit Currency";
                document.getElementById('currencyId').value = data.id;
                document.getElementById('existingQr').value = data.qr_code_url;
                document.getElementById('field_name').value = data.name;
                document.getElementById('field_symbol').value = data.symbol;
                document.getElementById('field_address').value = data.wallet_address;
            } else {
                document.getElementById('modalTitle').innerText = "Add New Currency";
                document.querySelector('form').reset();
            }
            modal.classList.remove('hidden');
        }
        function closeModal() { document.getElementById('currencyModal').classList.add('hidden'); }
    </script>
</body>
</html>