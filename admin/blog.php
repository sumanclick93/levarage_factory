<?php
require_once('../includes/db_connect.php');
require_once('includes/admin_auth.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$blog = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    $blog = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $content = $_POST['content']; 
    $status = $_POST['status'];
    
    // --- Restored Image Upload Logic ---
    $thumbnail = $blog['thumbnail'] ?? ''; 
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_dir = "../uploads/blogs/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["thumbnail"]["name"], PATHINFO_EXTENSION));
        $thumbnail = "BLOG_" . time() . "." . $file_ext;
        move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_dir . $thumbnail);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE blogs SET title=?, slug=?, content=?, author=?, status=?, thumbnail=? WHERE id=?");
        $stmt->execute([$title, $slug, $content, $author, $status, $thumbnail, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO blogs (title, slug, content, author, status, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content, $author, $status, $thumbnail]);
    }
    header("Location: manage_blogs.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Intelligence Editor | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background: #f8fafc; }
        .tox-tinymce { border-radius: 1.5rem !important; border: 1px solid #e5e7eb !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>
    
    <main class="flex-1 p-8 overflow-y-auto">
        <header class="mb-8">
            <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Content_Node_V3</p>
            <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">Intelligence <span class="text-[#00A6FB]">Editor</span>_</h1>
        </header>
        
        <form method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-200 space-y-8">
            <div class="grid grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Post Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($blog['title'] ?? ''); ?>" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-[#00A6FB]">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Author</label>
                    <input type="text" name="author" value="<?php echo htmlspecialchars($blog['author'] ?? ''); ?>" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-[#00A6FB]">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Featured Image (Thumbnail)</label>
                <div class="flex items-center gap-6 p-4 bg-gray-50 rounded-2xl border border-gray-200">
                    <?php if(!empty($blog['thumbnail'])): ?>
                        <div class="relative">
                            <img src="../uploads/blogs/<?php echo $blog['thumbnail']; ?>" class="w-24 h-24 object-cover rounded-2xl shadow-md border-2 border-white">
                            <p class="text-[8px] text-center mt-1 text-gray-400 font-bold uppercase">Current_Node</p>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <input type="file" name="thumbnail" class="w-full text-xs text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-gray-900 file:text-white file:uppercase hover:file:bg-[#00A6FB] transition-all cursor-pointer">
                        <p class="text-[9px] text-gray-400 mt-2 italic font-medium">*Update existing or upload new asset (.jpg, .png)</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Enhanced_Content_Stream</label>
                <textarea name="content" id="full-editor"><?php echo $blog['content'] ?? ''; ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <select name="status" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold uppercase text-xs tracking-widest">
                    <option value="draft" <?php echo (isset($blog['status']) && $blog['status'] == 'draft') ? 'selected' : ''; ?>>Draft_Mode</option>
                    <option value="published" <?php echo (isset($blog['status']) && $blog['status'] == 'published') ? 'selected' : ''; ?>>Live_Protocol</option>
                </select>
                <button type="submit" class="w-full py-5 bg-black text-white rounded-2xl font-black uppercase tracking-widest hover:bg-[#00A6FB] transition-all active:scale-95 shadow-xl">
                    Save_Intelligence_
                </button>
            </div>
        </form>
    </main>

    <script>
        tinymce.init({
            selector: '#full-editor',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace verticalbreak visualblocks code fullscreen insertdatetime media table code help wordcount emoticons',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | emoticons table link image media | code fullscreen preview',
            height: 500,
            menubar: false,
            branding: false,
            promotion: false,
            skin: 'oxide',
            content_css: 'default',
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });
    </script>
</body>
</html>