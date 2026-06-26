<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

// Handle Deletion logic
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // First, fetch the thumbnail to delete the file from the server
    $stmt = $pdo->prepare("SELECT thumbnail FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    
    if($img && file_exists("../uploads/blogs/" . $img)) {
        unlink("../uploads/blogs/" . $img);
    }

    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_blogs.php?success=deleted");
    exit();
}

// Fetch all blogs for the management table
$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Intelligence Manager | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-[#F8FAFC] text-gray-900 flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 p-8 overflow-y-auto animate__animated animate__fadeIn">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Intelligence <span class="text-[#00A6FB]">Manager</span></h1>
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mt-1">Manage global insights and protocol updates_</p>
            </div>
            <a href="blog.php" class="bg-black text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl hover:bg-[#00A6FB] transition-all transform active:scale-95">
                + Create New Intel
            </a>
        </header>

        <?php if(isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-600 rounded-2xl text-[10px] font-black uppercase tracking-widest animate__animated animate__headShake">
                Protocol Updated: Post successfully removed from node.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Identity_</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Author_</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status_</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Release_Date</th>
                        <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Terminal_Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($blogs)): ?>
                        <tr><td colspan="5" class="p-20 text-center text-gray-300 font-bold italic uppercase text-xs">No active intel found in database_</td></tr>
                    <?php endif; ?>

                    <?php foreach($blogs as $b): ?>
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex-shrink-0">
                                    <?php if($b['thumbnail']): ?>
                                        <img src="../uploads/blogs/<?php echo $b['thumbnail']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-[#00A6FB]">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-black text-gray-900 text-sm uppercase italic"><?php echo htmlspecialchars($b['title']); ?></p>
                                    <p class="text-[9px] text-gray-400 font-mono mt-1">slug: /<?php echo $b['slug']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-tighter"><?php echo htmlspecialchars($b['author']); ?></span>
                        </td>
                        <td class="p-6 text-center">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $b['status'] == 'published' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'; ?>">
                                <?php echo $b['status']; ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <p class="text-[10px] font-mono text-gray-400 uppercase"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></p>
                        </td>
                        <td class="p-6 text-right">
                            <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="blog.php?id=<?php echo $b['id']; ?>" class="bg-gray-100 hover:bg-[#00A6FB] hover:text-white p-2 rounded-lg transition-colors">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg>
                                </a>
                                <a href="?delete=<?php echo $b['id']; ?>" onclick="return confirm('WARNING: Are you sure you want to purge this intelligence post?')" class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white p-2 rounded-lg transition-colors">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>