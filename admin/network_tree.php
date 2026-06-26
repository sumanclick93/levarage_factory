<?php
require_once('includes/db_connect.php');
session_start();

$user_id = $_GET['id'] ?? null;

if (!$user_id) { header("Location: manage_users.php"); exit(); }

// Fetch the target user's data for the Root Node
$stmt_root = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt_root->execute([$user_id]);
$logged_user = $stmt_root->fetch();

if (!$logged_user) { header("Location: manage_users.php"); exit(); }

// =========================================================================
// O(N) IN-MEMORY ENGINE: Fetch everything ONCE to prevent N+1 query crashes
// =========================================================================

// 1. Fetch all users
$stmt_users = $pdo->query("SELECT id, username, email, referrer_id FROM users");
$all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch all active investments obeying strict precedence logic
$personal_inv_map = [];
$corporate_inv_map = [];
$matching_inv_map = [];

try {
    // A. Personal Investment (Individual, Not Corporate, Not Matching)
    $stmt_ind = $pdo->query("
        SELECT i.user_id, SUM(i.amount) 
        FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.status = 'active' 
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND IFNULL(i.hash_ref, '') NOT LIKE '%COMPANY%' 
        AND IFNULL(i.hash_ref, '') NOT LIKE '%ADMIN_ASSIGNED%'
        GROUP BY i.user_id
    ");
    if ($stmt_ind) $personal_inv_map = $stmt_ind->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // B. Corporate Investment (Priority Corporate Check)
    $stmt_corp = $pdo->query("
        SELECT i.user_id, SUM(i.amount) 
        FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.status = 'active' 
        AND (s.type = 'corporate' OR s.name LIKE '%corporate%')
        GROUP BY i.user_id
    ");
    if ($stmt_corp) $corporate_inv_map = $stmt_corp->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // C. Matching Bonus (Not Corporate, IS Admin/Company Assigned)
    $stmt_match = $pdo->query("
        SELECT i.user_id, SUM(i.amount) 
        FROM investments i
        JOIN investment_schemes s ON i.scheme_id = s.id
        WHERE i.status = 'active' 
        AND (s.type != 'corporate' AND s.name NOT LIKE '%corporate%')
        AND (IFNULL(i.hash_ref, '') LIKE '%COMPANY%' OR IFNULL(i.hash_ref, '') LIKE '%ADMIN_ASSIGNED%')
        GROUP BY i.user_id
    ");
    if ($stmt_match) $matching_inv_map = $stmt_match->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

} catch (Exception $e) {
    // Failsafe in case investments table is empty/missing
}

// 3. Build Adjacency List (The strict hierarchy)
$tree = [];
$user_data = [];
foreach ($all_users as $u) {
    $ref = $u['referrer_id'] ?: 0;
    $tree[$ref][] = $u['id'];
    $user_data[$u['id']] = $u;
}

// 4. Calculate accurate Team Volumes bottom-up
$team_vol_map = [];
function calculateTeamVols($uid, &$tree, &$personal_inv_map, &$team_vol_map) {
    $vol = 0;
    if (isset($tree[$uid])) {
        foreach ($tree[$uid] as $child_id) {
            $vol += ($personal_inv_map[$child_id] ?? 0); // Add direct child's personal volume
            $vol += calculateTeamVols($child_id, $tree, $personal_inv_map, $team_vol_map); // Add deep volume
        }
    }
    $team_vol_map[$uid] = $vol;
    return $vol;
}

// Populate volume maps starting from the requested root node downward
calculateTeamVols($user_id, $tree, $personal_inv_map, $team_vol_map);

/**
 * RECURSIVE RENDERER: Renders the tree straight from memory (Lightning Fast)
 */
function renderDownlineTreeMemory($parent_id, $level = 1) {
    global $tree, $user_data, $personal_inv_map, $corporate_inv_map, $matching_inv_map, $team_vol_map;

    if (!isset($tree[$parent_id])) return;

    // Branches deeper than level 1 are hidden by default to prevent UI lag on massive trees
    $hiddenClass = $level > 1 ? 'hidden' : '';
    
    echo '<ul id="branch-'.$parent_id.'" class="'.$hiddenClass.' ml-10 mt-6 space-y-8 border-l-2 border-dashed border-blue-200 pl-10 relative transition-all duration-300">';
    
    foreach ($tree[$parent_id] as $mid) {
        $m = $user_data[$mid];
        
        $personal_inv = $personal_inv_map[$mid] ?? 0;
        $corporate_inv = $corporate_inv_map[$mid] ?? 0;
        $matching_inv = $matching_inv_map[$mid] ?? 0;
        $team_vol = $team_vol_map[$mid] ?? 0;
        
        $downline_count = isset($tree[$mid]) ? count($tree[$mid]) : 0;

        echo '<li class="relative">';
        ?>
        <div class="tree-node bg-white p-6 rounded-[2.5rem] border border-gray-100 shadow-xl inline-block min-w-[500px] relative hover:border-[#00A6FB] transition-all duration-300 group" 
             data-uid="<?php echo $mid; ?>"
             data-username="<?php echo strtolower(htmlspecialchars($m['username'])); ?>"
             data-email="<?php echo strtolower(htmlspecialchars($m['email'])); ?>">
            
            <div class="absolute -left-[42px] top-1/2 -translate-y-1/2 w-10 h-[2px] bg-blue-200"></div>
            <div class="absolute -left-[46px] top-1/2 -translate-y-1/2 w-3 h-3 bg-[#00A6FB] rounded-full ring-4 ring-white shadow-sm"></div>

            <?php if($downline_count > 0): ?>
            <button onclick="toggleBranch(<?php echo $mid; ?>, this)" class="absolute -right-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-black text-white rounded-full flex items-center justify-center border-4 border-white shadow-lg hover:bg-[#00A6FB] z-20">
                <span class="text-lg font-bold leading-none mb-1">+</span>
            </button>
            <?php endif; ?>

            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex flex-col items-center justify-center border border-gray-100">
                    <span class="text-[7px] font-black text-gray-400 uppercase italic">Lvl</span>
                    <span class="text-xs font-black text-[#00A6FB]"><?php echo $level; ?></span>
                </div>
                <div>
                    <p class="font-black uppercase italic text-gray-900 tracking-tighter"><?php echo htmlspecialchars($m['username']); ?></p>
                    <p class="text-[9px] text-gray-400 font-bold">UID: <?php echo $mid; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2 border-t border-gray-50 pt-4 bg-gray-50/50 -mx-6 -mb-6 px-6 pb-6 rounded-b-[2.5rem]">
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Personal_Inv</p>
                    <p class="text-sm font-black text-gray-900">$<?php echo number_format($personal_inv, 2); ?></p>
                </div>
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Corp_Inv</p>
                    <p class="text-sm font-black text-purple-600">$<?php echo number_format($corporate_inv, 2); ?></p>
                </div>
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Match_Bonus</p>
                    <p class="text-sm font-black text-pink-500">$<?php echo number_format($matching_inv, 2); ?></p>
                </div>
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Team_Volume</p>
                    <p class="text-sm font-black text-[#00A6FB]">$<?php echo number_format($team_vol, 2); ?></p>
                </div>
            </div>
        </div>
        <?php
        renderDownlineTreeMemory($mid, $level + 1);
        echo '</li>';
    }
    echo '</ul>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Tree | Leverage Factory Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleBranch(userId, btn) {
            const branch = document.getElementById('branch-' + userId);
            const icon = btn.querySelector('span');
            if (branch.classList.contains('hidden')) {
                branch.classList.remove('hidden');
                icon.innerText = '−';
                btn.classList.replace('bg-black', 'bg-[#00A6FB]');
            } else {
                branch.classList.add('hidden');
                icon.innerText = '+';
                btn.classList.replace('bg-[#00A6FB]', 'bg-black');
            }
        }

        function searchTree() {
            const input = document.getElementById('treeSearch').value.trim().toLowerCase();
            const nodes = document.querySelectorAll('.tree-node');
            const counter = document.getElementById('matchCounter');
            let matchCount = 0;
            
            nodes.forEach(node => {
                node.classList.remove('ring-4', 'ring-[#00A6FB]', 'scale-105', 'z-50', 'opacity-40');
                node.classList.add('opacity-100');
            });

            if (input === "") {
                counter.classList.add('hidden');
                return;
            }

            nodes.forEach(node => {
                const uid = node.getAttribute('data-uid');
                const username = node.getAttribute('data-username');
                const email = node.getAttribute('data-email');

                if (uid === input || username.includes(input) || email.includes(input)) {
                    matchCount++;
                    let parent = node.closest('ul');
                    while (parent) {
                        if (parent.classList.contains('hidden')) {
                            parent.classList.remove('hidden');
                            const parentBtn = parent.previousElementSibling?.querySelector('button');
                            if (parentBtn) {
                                parentBtn.querySelector('span').innerText = '−';
                                parentBtn.classList.replace('bg-black', 'bg-[#00A6FB]');
                            }
                        }
                        parent = parent.parentElement.closest('ul');
                    }
                    node.classList.add('ring-4', 'ring-[#00A6FB]', 'scale-105', 'z-50');
                    if (matchCount === 1) node.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    node.classList.add('opacity-40');
                }
            });

            counter.innerText = matchCount + (matchCount === 1 ? ' Match Found' : ' Matches Found');
            counter.classList.toggle('hidden', matchCount === 0 && input !== "");
        }
    </script>
</head>
<body class="bg-[#F8FAFC] text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-x-auto relative">
        <header class="mb-12 sticky left-0 z-40 bg-[#F8FAFC]/80 backdrop-blur-md py-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 max-w-7xl">
                <h1 class="text-4xl font-black uppercase italic tracking-tighter">Network <span class="text-[#00A6FB]">Tree</span>_</h1>
                <div class="flex flex-col items-end gap-2">
                    <input type="text" id="treeSearch" placeholder="UID, Username, or Email..." onkeyup="searchTree()"
                           class="bg-white border-2 border-gray-100 rounded-2xl px-6 py-4 w-full md:w-96 outline-none focus:border-[#00A6FB] font-bold text-sm shadow-sm">
                    <div id="matchCounter" class="hidden px-4 py-1.5 bg-[#00A6FB] text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg transition-all">
                        0 Matches Found
                    </div>
                </div>
            </div>
        </header>

        <div id="network-root" class="inline-block bg-black p-8 rounded-[3rem] shadow-2xl border border-white/10 mb-12">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-[#00A6FB] rounded-[1.5rem] flex items-center justify-center text-white font-black text-xl shadow-[0_0_30px_rgba(0,166,251,0.3)]">LF</div>
                <p class="text-white text-2xl font-black uppercase italic tracking-tighter">
                    <?php echo htmlspecialchars($logged_user['username']); ?> Node_
                </p>
            </div>
        </div>

        <div class="pb-40">
            <?php renderDownlineTreeMemory($user_id); ?>
        </div>
    </main>
</body>
</html>