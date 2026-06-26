<?php
require_once('db_connect.php');

$slug = $_GET['slug'] ?? '';

// Fetch the published post by slug
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

// Redirect if post is not found
if (!$post) {
    header("Location: blog.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/Favicon-removebg-preview.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> | Leverage Factory Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background-color: #020617; }
        .blog-content p { margin-bottom: 1.5rem; line-height: 1.8; color: #cbd5e1; }
        .blog-content h2 { font-size: 1.5rem; font-weight: 800; color: white; margin-top: 2rem; margin-bottom: 1rem; text-transform: uppercase; font-style: italic; }
    </style>
</head>
<body class="text-white selection:bg-cyan-500 selection:text-black">

    <canvas id="matrix-canvas" class="fixed top-0 left-0 w-full h-full opacity-[0.15] -z-20 pointer-events-none"></canvas>

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#00A6FB]/10 blur-[150px] rounded-full"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[50%] h-[50%] bg-purple-500/10 blur-[150px] rounded-full"></div>
    </div>

    <main class="max-w-4xl mx-auto px-6 py-20 animate__animated animate__fadeIn">
        
        <a href="index.php#about" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-cyan-400 hover:text-white transition mb-12 group">
            <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="3"/></svg>
            Terminal_Return
        </a>

        <header class="mb-16">
            <div class="flex items-center gap-3 mb-6">
                <span class="px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-[10px] font-black text-cyan-400 uppercase tracking-widest">
                    Intelligence_Report
                </span>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                </span>
            </div>
            
            <h1 class="text-4xl md:text-6xl font-black uppercase italic leading-tight tracking-tighter mb-8 animate__animated animate__slideInLeft">
                <?php echo htmlspecialchars($post['title']); ?>_
            </h1>

            <div class="flex items-center gap-4 py-6 border-y border-white/5">
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center font-black text-black text-xs">
                    <?php echo substr($post['author'], 0, 2); ?>
                </div>
                <div>
                    <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Authored By</p>
                    <p class="text-sm font-bold text-white uppercase italic"><?php echo htmlspecialchars($post['author']); ?></p>
                </div>
            </div>
        </header>

        <?php if($post['thumbnail']): ?>
        <div class="w-full h-[400px] rounded-[2.5rem] overflow-hidden mb-16 border border-white/10 shadow-2xl">
            <img src="uploads/blogs/<?php echo $post['thumbnail']; ?>" class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <article class="blog-content text-lg md:text-xl font-light tracking-wide animate__animated animate__fadeInUp animate__delay-1s">
            <?php 
                // Allow HTML content from the admin editor
                echo $post['content']; 
            ?>
        </article>

        <footer class="mt-20 pt-12 border-t border-white/5 text-center">
            <h4 class="text-xs font-black text-gray-500 uppercase tracking-[0.4em] mb-8">End of Intel Transmission</h4>
            <a href="register.php" class="inline-block bg-cyan-400 text-black font-black py-4 px-12 rounded-2xl hover:bg-white transition shadow-[0_0_30px_rgba(6,182,212,0.3)] transform hover:scale-105 uppercase tracking-widest text-xs">
                Launch Trading Terminal
            </a>
        </footer>

    </main>
    <script>
        const canvas = document.getElementById('matrix-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const katakana = '₿ ⟠ Ξ ✕ ₮ ₳ Ð D Ł Ƀ * ϑ ⨎ ε Ɓ ɱ ꜩ ξ ◈ ⓩ ⟁ Ӿ Ñ Ⱡ ȿ Ɍ Ꞥ ℕ Ᵽ Ψ';
        // --- const latin = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const latin = katakana;
        const nums = '0123456789';
        const alphabet = katakana + latin + nums;
        const fontSize = 16;
        const columns = canvas.width / fontSize;
        const rainDrops = Array.from({ length: columns }).map(() => 1);

        const drawMatrix = () => {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#0ff';
            ctx.font = fontSize + 'px monospace';
            for (let i = 0; i < rainDrops.length; i++) {
                const text = alphabet.charAt(Math.floor(Math.random() * alphabet.length));
                ctx.fillText(text, i * fontSize, rainDrops[i] * fontSize);
                if (rainDrops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    rainDrops[i] = 0;
                }
                rainDrops[i]++;
            }
        };
        setInterval(drawMatrix, 30);
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>