<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // 1. ADD NEW SCHEME
    if ($_POST['action'] == 'add_scheme') {
        $stmt = $pdo->prepare("INSERT INTO investment_schemes (name, min_amount, duration_days, total_return_percent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['min_amount'], $_POST['duration_days'], $_POST['total_return_percent']]);
        logAdminAction($pdo, "Created scheme: " . $_POST['name']);
    }

    // 2. EDIT SCHEME (New Feature)
    if ($_POST['action'] == 'edit_scheme') {
        echo $_POST['total_return_percent'];
        $stmt = $pdo->prepare("UPDATE investment_schemes SET name = ?, min_amount = ?, duration_days = ?, total_return_percent = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['min_amount'], $_POST['duration_days'], $_POST['total_return_percent'], $_POST['scheme_id']]);
        logAdminAction($pdo, "Edited scheme ID #" . $_POST['scheme_id']);
    }

    // 3. TOGGLE STATUS
    if ($_POST['action'] == 'toggle_status') {
        $pdo->prepare("UPDATE investment_schemes SET is_active = NOT is_active WHERE id = ?")->execute([$_POST['scheme_id']]);
    }

    // 4. DELETE
    if ($_POST['action'] == 'delete_scheme') {
        $pdo->prepare("DELETE FROM investment_schemes WHERE id = ?")->execute([$_POST['scheme_id']]);
    }

    header("Location: schemes.php?success=1");
    exit();
}

$schemes = $pdo->query("SELECT * FROM investment_schemes ORDER BY min_amount ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Investment Plans - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Investment Schemes</h1>
                <p class="text-gray-500 mt-1">Manage the plans available for customers to purchase.</p>
            </div>
            <button onclick="openAddModal()" class="bg-[#E63946] text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-red-700">
                + Create Plan
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($schemes as $s): ?>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold uppercase"><?php echo $s['name']; ?></h3>
                    <button onclick='openEditModal(<?php echo json_encode($s); ?>)' class="text-blue-500 hover:text-blue-700 text-sm font-bold">Edit</button>
                    <span class="px-2 py-1 rounded text-[10px] font-bold <?php echo $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo $s['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                    </span>
                </div>
                <div class="text-3xl font-black text-blue-600 mb-2">$<?php echo number_format($s['min_amount'], 0); ?></div>
                <div class="text-sm text-gray-500 mb-6"><?php echo $s['duration_days']; ?> Days | <?php echo $s['total_return_percent']; ?>% ROI</div>
                
                <div class="flex gap-2">
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="scheme_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="action" value="toggle_status" class="w-full text-xs font-bold py-2 border rounded hover:bg-gray-50">
                            <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete plan?')">
                        <input type="hidden" name="scheme_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="action" value="delete_scheme" class="p-2 text-red-500 hover:bg-red-50 rounded transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="schemeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8">
            <h2 id="modalTitle" class="text-2xl font-bold mb-6">Create New Plan</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="add_scheme">
                <input type="hidden" name="scheme_id" id="edit_scheme_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Scheme Name</label>
                    <input type="text" name="name" id="field_name" required class="w-full border rounded-lg p-2 mt-1" placeholder="e.g. Starter 90-Day Plan">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount ($)</label>
                        <input type="number" name="min_amount" id="field_amount" required class="w-full border rounded-lg p-2 mt-1" placeholder="1000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duration (Days)</label>
                        <input type="number" name="duration_days" id="field_duration" required class="w-full border rounded-lg p-2 mt-1" placeholder="90">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Return (%)</label>
                    <input type="number" name="total_return_percent" id="field_return" required class="w-full border rounded-lg p-2 mt-1" placeholder="300">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-2 border rounded-lg font-bold">Cancel</button>
                    <button type="submit" class="flex-1 py-2 bg-[#E63946] text-white rounded-lg font-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('schemeModal');
        
        function openAddModal() {
            document.getElementById('modalTitle').innerText = "Create New Plan";
            document.getElementById('formAction').value = "add_scheme";
            document.getElementById('edit_scheme_id').value = "";
            document.querySelector("form").reset();
            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = "Edit Plan: " + data.name;
            document.getElementById('formAction').value = "edit_scheme";
            document.getElementById('edit_scheme_id').value = data.id;
            document.getElementById('field_name').value = data.name;
            document.getElementById('field_amount').value = data.min_amount;
            document.getElementById('field_duration').value = data.duration_days;
            document.getElementById('field_return').value = data.total_return_percent;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>