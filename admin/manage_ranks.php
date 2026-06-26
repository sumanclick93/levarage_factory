<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

$success = "";

// --- HANDLE CRUD OPERATIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['save_rank'])) {
        $id = $_POST['rank_id'] ?? null;
        $name = $_POST['rank_name'];
        $personal = $_POST['personal_investment'];
        $team = $_POST['team_volume'];
        $bonus = $_POST['bonus_amount'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE rank_bonuses SET rank_name=?, personal_investment=?, team_volume=?, bonus_amount=? WHERE id=?");
            $stmt->execute([$name, $personal, $team, $bonus, $id]);
            $success = "Rank Protocol Updated_";
        } else {
            $stmt = $pdo->prepare("INSERT INTO rank_bonuses (rank_name, personal_investment, team_volume, bonus_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $personal, $team, $bonus]);
            $success = "New Rank Initialized_";
        }
    }

    if (isset($_POST['delete_rank'])) {
        $stmt = $pdo->prepare("DELETE FROM rank_bonuses WHERE id = ?");
        $stmt->execute([$_POST['rank_id']]);
        $success = "Rank Node Purged_";
    }
}

$ranks = $pdo->query("SELECT * FROM rank_bonuses ORDER BY personal_investment ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rank Control | Admin Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-y-auto animate__animated animate__fadeIn">
        <header class="flex justify-between items-end mb-10">
            <div>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Affiliate Program</p>
                <h1 class="text-4xl font-black uppercase italic tracking-tighter">Rank <span class="text-[#00A6FB]">Bonuses</span>_</h1>
            </div>
            <button onclick="openModal()" class="bg-black text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl hover:bg-[#00A6FB] transition-all">Initialize New Rank</button>
        </header>

        <?php if($success): ?>
            <div class="mb-8 p-4 bg-blue-50 border border-blue-100 text-[#00A6FB] rounded-2xl text-[10px] font-black uppercase tracking-widest italic animate__animated animate__headShake">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Rank_Node</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Personal_Inv</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Team_Volume</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Rank_Bonus</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($ranks as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition group">
                        <td class="p-6 font-bold text-gray-900 italic"><?php echo $r['rank_name']; ?></td>
                        <td class="p-6 font-mono text-sm font-bold">$<?php echo number_format($r['personal_investment'], 2); ?></td>
                        <td class="p-6 font-mono text-sm font-bold">$<?php echo number_format($r['team_volume'], 2); ?></td>
                        <td class="p-6 font-mono text-sm font-bold text-[#00A6FB]">$<?php echo number_format($r['bonus_amount'], 2); ?></td>
                        <td class="p-6 text-right flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-all">
                            <button onclick='editRank(<?php echo json_encode($r); ?>)' class="p-3 bg-gray-100 rounded-xl hover:bg-black hover:text-white transition">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg>
                            </button>
                            <form method="POST" onsubmit="return confirm('Purge this rank protocol?')">
                                <input type="hidden" name="rank_id" value="<?php echo $r['id']; ?>">
                                <button type="submit" name="delete_rank" class="p-3 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="rankModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center">
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl animate__animated animate__zoomIn">
            <h2 id="modalTitle" class="text-2xl font-black uppercase italic tracking-tighter mb-8">Initialize <span class="text-[#00A6FB]">Rank Node</span></h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="rank_id" id="rank_id">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Rank_Title</label>
                    <input type="text" name="rank_name" id="rank_name" required class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Personal_Inv</label>
                        <input type="number" step="0.01" name="personal_investment" id="personal_investment" required class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Team_Volume</label>
                        <input type="number" step="0.01" name="team_volume" id="team_volume" required class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Bonus_Allocation</label>
                    <input type="number" step="0.01" name="bonus_amount" id="bonus_amount" required class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-[#00A6FB] font-bold">
                </div>
                <div class="flex gap-4 pt-6">
                    <button type="button" onclick="closeModal()" class="flex-1 py-4 font-black text-[10px] uppercase tracking-widest text-gray-400">Cancel</button>
                    <button type="submit" name="save_rank" class="flex-1 bg-black text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-[#00A6FB] transition-all">Execute Protocol</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('rankModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerHTML = "Initialize <span class='text-[#00A6FB]'>Rank Node</span>";
            document.getElementById('rank_id').value = "";
            document.querySelector('form').reset();
        }
        function closeModal() { document.getElementById('rankModal').classList.add('hidden'); }
        function editRank(data) {
            openModal();
            document.getElementById('modalTitle').innerHTML = "Update <span class='text-[#00A6FB]'>Rank Node</span>";
            document.getElementById('rank_id').value = data.id;
            document.getElementById('rank_name').value = data.rank_name;
            document.getElementById('personal_investment').value = data.personal_investment;
            document.getElementById('team_volume').value = data.team_volume;
            document.getElementById('bonus_amount').value = data.bonus_amount;
        }
    </script>
</body>
</html>