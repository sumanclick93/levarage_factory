<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if ($user_id) {

    $stmt = $pdo->prepare("
        SELECT w.*, u.username, u.email
        FROM withdrawal_requests w
        JOIN users u ON w.user_id = u.id
        WHERE w.status != 'pending' AND w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $withdrawals = $stmt->fetchAll();

    $totalStmt = $pdo->prepare("
        SELECT SUM(amount) as total
        FROM withdrawal_requests
        WHERE status='approved' AND user_id=?
    ");
    $totalStmt->execute([$user_id]);
    $totalWithdrawn = $totalStmt->fetch()['total'];

} else {

    $withdrawals = $pdo->query("
        SELECT w.*, u.username, u.email
        FROM withdrawal_requests w
        JOIN users u ON w.user_id = u.id
        WHERE w.status != 'pending'
        ORDER BY w.created_at DESC
    ")->fetchAll();

    $totalWithdrawn = $pdo->query("
        SELECT SUM(amount) as total
        FROM withdrawal_requests
        WHERE status='approved'
    ")->fetch()['total'];
}
?>
<!DOCTYPE html>
<html>
<head>

<title>Withdrawal History</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<link rel="stylesheet"
href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

</head>

<body class="bg-gray-100 text-gray-800 flex h-screen">

<?php include('includes/sidebar.php'); ?>

<main class="flex-1 overflow-y-auto p-8">

<header class="mb-8">

<h1 class="text-3xl font-black text-gray-900 uppercase italic">
Withdrawal <span class="text-[#00A6FB]">History</span>_
</h1>

<p class="text-gray-500 text-xs font-bold uppercase tracking-widest">
Processed Withdrawal Records
</p>

</header>


<!-- TOTAL CARD -->

<div class="mb-6 bg-white border rounded-xl p-6 shadow-sm">

<p class="text-xs uppercase text-gray-400 font-bold">
Total Approved Withdrawals
</p>

<p class="text-3xl font-black text-green-600">
$<?php echo number_format($totalWithdrawn,2); ?>
</p>

</div>



<!-- FILTER -->

<div class="mb-4">

<select id="statusFilter"
class="border px-4 py-2 rounded-lg text-sm">

<option value="">All Status</option>
<option value="approved">Approved</option>
<option value="rejected">Rejected</option>

</select>

</div>



<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

<table id="withdrawTable" class="w-full text-left">

<thead class="bg-gray-50 border-b border-gray-200 text-[10px] uppercase text-gray-500 font-black">

<tr>

<th>User</th>
<th>Email</th>
<th>Amount</th>
<th>Wallet Address</th>
<th>Network</th>
<th>Status</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php foreach($withdrawals as $w): ?>

<tr>

<td class="font-bold">
<?php echo htmlspecialchars($w['username']); ?>
</td>

<td class="text-xs">
<?php echo htmlspecialchars($w['email']); ?>
</td>

<td class="text-red-600 font-mono font-bold">
-$<?php echo number_format($w['amount'],2); ?>
</td>

<td class="text-[10px] font-mono max-w-[220px]">

<div class="flex items-center gap-2">

<span class="break-all">
<?php echo htmlspecialchars($w['wallet_address']); ?>
</span>

<button type="button"
onclick="copyAddress('<?php echo htmlspecialchars($w['wallet_address']); ?>', this)"
class="p-1 hover:bg-gray-100 rounded text-gray-400 hover:text-[#00A6FB]"
title="Copy Wallet">

<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3"
stroke-width="2"
stroke-linecap="round"
stroke-linejoin="round"/>
</svg>

</button>

</div>

</td>

<td class="text-[10px] font-mono">
<?php echo $w['network'] ?? '-'; ?>
</td>

<td>

<?php if($w['status']=='approved'): ?>

<span class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs font-bold">
APPROVED
</span>

<?php else: ?>

<span class="bg-red-100 text-red-700 px-3 py-1 rounded text-xs font-bold">
REJECTED
</span>

<?php endif; ?>

</td>

<td class="text-xs text-gray-500">
<?php echo date('d M Y H:i', strtotime($w['created_at'])); ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</main>



<script>

$(document).ready(function() {

var table = $('#withdrawTable').DataTable({

dom: 'Bfrtip',

buttons: [
'copy',
'csv',
'excel',
'print'
],

order: [[6,"desc"]]

});


// STATUS FILTER

$('#statusFilter').on('change', function() {

table.column(5).search(this.value).draw();

});

});

</script>
<script>
    function copyAddress(address, btn) {

    navigator.clipboard.writeText(address).then(() => {

        const original = btn.innerHTML;

        btn.innerHTML =
        '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"/></svg>';

        setTimeout(() => {
            btn.innerHTML = original;
        }, 1500);

    });

}
</script>


</body>
</html>