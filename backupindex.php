<?php
include('db_connect.php');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="images/Favicon-removebg-preview.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Leverage Factory AI Trading</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --glow-color: hsl(180, 100%, 50%);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes heroZoom {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }
        @keyframes glitch {
          0% { transform: translate(0); }
          20% { transform: translate(-2px, 2px); }
          40% { transform: translate(-2px, -2px); }
          60% { transform: translate(2px, 2px); }
          80% { transform: translate(2px, -2px); }
          100% { transform: translate(0); }
        }

        .animated { opacity: 0; }
        .is-visible { animation: fadeInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }

        .animate-delay-200 { animation-delay: 200ms; }
        .animate-delay-400 { animation-delay: 400ms; }
        .animate-delay-600 { animation-delay: 600ms; }

        .hero-bg-image { animation: heroZoom 40s ease-in-out infinite alternate; }

        .card-hover-effect {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .card-hover-effect:hover {
            transform: translateY(-6px);
            box-shadow: 0 0 15px var(--glow-color), 0 0 30px var(--glow-color);
            border-color: var(--glow-color);
        }

        /* Glitch Effect */
        .glitch-hover { position: relative; }
        .glitch-hover:hover::before, .glitch-hover:hover::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;tra
            background: black;
            overflow: hidden;
        }
        .glitch-hover:hover::before {
            left: 2px;
            text-shadow: -2px 0 #ff00c1;
            animation: glitch 250ms infinite;
        }
        .glitch-hover:hover::after {
            left: -2px;
            text-shadow: -2px 0 var(--glow-color), 2px 2px #ff00c1;
            animation: glitch 200ms infinite reverse;
        }
        
        /* Mobile Nav */
        #mobile-nav {
            transform: translateY(-100%);
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        #mobile-nav.open {
            transform: translateY(0);
        }

        /* Custom Slider Styles */
        input[type=range] {
          -webkit-appearance: none;
          appearance: none;
          background: transparent;
          cursor: pointer;
          width: 100%;
        }
        input[type=range]:focus {
          outline: none;
        }
        input[type=range]::-webkit-slider-runnable-track {
            background: #2dd4bf;
            height: 4px;
            border-radius: 2px;
        }
        input[type=range]::-moz-range-track {
            background: #2dd4bf;
            height: 4px;
            border-radius: 2px;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            margin-top: -6px;
            background: #0ff;
            height: 1rem;
            width: 1rem;
            border-radius: 9999px;
            border: 2px solid black;
            box-shadow: 0 0 5px var(--glow-color);
        }
        input[type=range]::-moz-range-thumb {
            background: #0ff;
            height: 1rem;
            width: 1rem;
            border-radius: 9999px;
            border: 2px solid black;
            box-shadow: 0 0 5px var(--glow-color);
            border: none;
        }
    
        .logo-slider {
              display: grid;
              grid-template-columns: auto auto auto auto auto auto
        }
    
        .logo-slider:hover {
            animation-play-state: paused; /* User interaction pause */
        }
    
        .slide {
            width: 170px; /* Fixed width for calculation accuracy */
            display: flex;
            justify-content: center;
            align-items: center;
          
            padding: 0 15px;
            margin-bottom: 40px;
        }
    
        /* Standardize image alignment */
        .exchange-logo {
            max-height: 50px; /* Forces all logos to share a horizon line */
            width: auto;
            object-fit: contain;
            /*filter: grayscale(100%) opacity(0.6);*/
            transition: all 0.4s ease;
        }
    
        .slide:hover .exchange-logo {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.15);
        }
    </style>
    <style>
    :root {
        --glow-color: hsl(180, 100%, 50%);
        /* Header height offset (adjust 80px based on your actual header size) */
        scroll-padding-top: 80px; 
    }
    
    html {
        scroll-behavior: smooth;
    }
    
    /* Ensure sections have clear boundaries for the observer */
    section {
        position: relative;
    }
</style>
  <link rel="stylesheet" href="/index.css">
</head>
  <body class="bg-black text-white font-['Circularxx',sans-serif]">
    <canvas id="matrix-canvas" class="fixed top-[68px] left-0 w-full h-screen opacity-30 z-20 pointer-events-none"></canvas> 
    <div id="root" class="relative z-10">
        <header class="fixed top-0 left-0 right-0 z-20 py-4 bg-black">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <img src="logo-removebg-preview.png" style="height: 40px;">
                        <!-- <span class="text-xl font-bold tracking-wider">LEVERAGE <span class="font-light">FACTORY</span></span> -->
                    </div>
                    <nav class="hidden md:flex items-center space-x-8">
                        <!--<a href="#home" class="text-gray-300 hover:text-cyan-300 transition">Home</a>-->
                        <a href="#about" class="text-gray-300 hover:text-cyan-300 transition">News</a>
                        <a href="#features" class="text-gray-300 hover:text-cyan-300 transition">Portfolio</a>
                        <!--<a href="#pricing" class="text-gray-300 hover:text-cyan-300 transition">Pricing</a>-->
                        <a href="#pages" class="text-gray-300 hover:text-cyan-300 transition">Pages</a>
                    </nav>
                    
                    <a href="login.php" class="hidden md:flex bg-cyan-400 text-black font-bold py-2 px-3 rounded-md hover:bg-cyan-300 transition shadow-lg shadow-cyan-500/50 hover:shadow-cyan-400/70 transform hover:scale-105">
                       <img src="images/icons8-login-64.png" alt="login" class="h-6 w-6" /> <span class="ml-2">Login</span>
                    </a>
                    <button id="menu-btn" class="md:hidden text-white z-30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile Menu -->
        <div id="mobile-nav" class="fixed top-0 left-0 w-full h-screen bg-black/95 backdrop-blur-sm z-20 flex items-center justify-center">
            <nav class="flex flex-col items-center space-y-8 text-2xl">
                <!--<a href="#home" class="text-gray-300 hover:text-cyan-300 transition">Home</a>-->
                <a href="#about" class="text-gray-300 hover:text-cyan-300 transition">News</a>
                <a href="#features" class="text-gray-300 hover:text-cyan-300 transition">Portfolio</a>
                <!--<a href="#pricing" class="text-gray-300 hover:text-cyan-300 transition">Pricing</a>-->
                <a href="#pages" class="text-gray-300 hover:text-cyan-300 transition">Pages</a>
                <a href="login.php" class="text-gray-300 hover:text-cyan-300 transition">Login</a>
            </nav>
        </div>

        <main>
            <!-- Hero Section -->
            <section id="home" class="relative pt-24 md:pt-0 md:h-[46rem] flex items-center justify-center text-left overflow-hidden ">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-0 md:mt-[10rem] flex flex-col-reverse md:flex-row justify-center gap-8 items-center">
                    <div class="animated w-full  md:w-[58%]">
                        <h1 class="text-4xl md:text-7xl font-extrabold text-white tracking-tight leading-tight">
                            Building an open economy for everyone
                        </h1>
                        <p class="mt-6 text-lg text-gray-300 max-w-xl">
                            Leverage Factory is building the foundation of a more open, global economy through digital assets, payment applications, and programmable blockchain infrastructure.
                        </p>
                        <div class="mt-8 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                            <a href="register.php" class="inline-block text-center bg-white text-black font-semibold py-3 px-8 rounded-lg hover:bg-gray-200 transition shadow-lg">
                                GET STARTED
                            </a>
                            <!--<a href="#" class="inline-block text-center border border-gray-400 text-white font-semibold py-3 px-8 rounded-lg hover:bg-white hover:text-black transition">-->
                            <!--    LEARN ABOUT USDC-->
                            <!--</a>-->
                        </div>
                    </div>
                    <div class="block animated animate-delay-200">
                        <!-- Please replace 'images/floating-coins.png' with the actual path to your image -->
                        <img src="icon-removebg-preview.png" alt="Floating crypto coins graphic" class="w-[506px] h-[509px]">
                    </div>
                </div>
            </section>

            <div class="space-y-24 md:space-y-32 py-12 md:py-8">
                <!-- About Us Section -->
                <?php
                    // Fetch only the latest 4 published blogs for the cinematic slider
                    $stmt = $pdo->query("SELECT title, slug, content, thumbnail FROM blogs WHERE status = 'published' ORDER BY created_at DESC LIMIT 4");
                    $featured_blogs = $stmt->fetchAll();
                ?>
                <section id="about" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
                    <div class="bg-gray-900/50 border border-cyan-500/30 p-8 md:p-12 rounded-2xl shadow-lg backdrop-blur-sm">
                        <div class="grid md:grid-cols-2 gap-12 items-center">
                            
                            <div class="flex items-center justify-center p-8 bg-gray-800/50 rounded-xl h-full min-h-[300px]">
                                <div class="relative w-full h-full min-h-[300px] overflow-hidden rounded-lg">
                                    <?php if(empty($featured_blogs)): ?>
                                        <img src="images/arc-logo.png" class="slide-image absolute inset-0 w-full h-full object-contain">
                                    <?php else: ?>
                                        <?php foreach($featured_blogs as $index => $fb): ?>
                                            <img src="uploads/blogs/<?php echo $fb['thumbnail']; ?>" 
                                                 alt="<?php echo htmlspecialchars($fb['title']); ?>" 
                                                 class="slide-image absolute inset-0 w-full h-full object-cover transition-opacity duration-700 ease-in-out <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                
                            <div class="text-white flex flex-col justify-between min-h-[400px] py-12">
                                <div class="relative overflow-hidden h-64">
                                    <?php if(empty($featured_blogs)): ?>
                                        <div class="slide-text absolute w-full">
                                            <p class="text-sm font-bold tracking-widest text-cyan-400">NO DATA</p>
                                            <h3 class="text-4xl font-bold mt-2 text-gray-500 italic">No published intel found_</h3>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach($featured_blogs as $index => $fb): ?>
                                            <div class="slide-text absolute w-full transition-opacity duration-700 ease-in-out <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>">
                                                <p class="text-sm font-bold tracking-widest text-cyan-400">LATEST INSIGHT_</p>
                                                <h3 class="text-4xl font-bold mt-2 uppercase italic tracking-tighter">
                                                    <?php echo htmlspecialchars($fb['title']); ?>
                                                </h3>
                                                <p class="mt-4 text-gray-400 leading-relaxed text-sm">
                                                    <?php 
                                                        $excerpt = strip_tags($fb['content']);
                                                        echo (strlen($excerpt) > 160) ? substr($excerpt, 0, 160) . '...' : $excerpt; 
                                                    ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                
                                <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                                    <div class="flex space-x-4">
                                        <a href="read.php?slug=<?php echo $featured_blogs[0]['slug'] ?? '#'; ?>" id="read-more-btn" class="inline-block text-center bg-cyan-400 text-black font-black py-3 px-8 rounded-lg hover:bg-white transition shadow-sm text-xs tracking-widest">
                                            READ MORE
                                        </a>
                                    </div>
                
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center space-x-2">
                                            <button id="prev-slide" class="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center text-gray-300 hover:bg-cyan-400 hover:text-black transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                            </button>
                                            <button id="next-slide" class="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center text-gray-300 hover:bg-cyan-400 hover:text-black transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            </button>
                                        </div>
                                        <div class="hidden sm:flex items-center space-x-2">
                                            <?php foreach($featured_blogs as $index => $fb): ?>
                                                <div class="slide-indicator h-1 w-6 rounded-full <?php echo $index === 0 ? 'bg-cyan-400' : 'bg-gray-700'; ?>"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Why Leverage Section -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold animated glitch-hover" data-text="Why We Use LEVERAGE Trading for Copy Trading?">
                        Why We Use <span class="text-cyan-400">LEVERAGE</span> Trading for Copy Trading?
                    </h2>
                    <p class="mt-4 max-w-2xl mx-auto text-gray-400 animated animate-delay-200">
                        We've seen just how life-changing the opportunity. Our experience and strategies bring the discipline. Copy trading connects both to our members.
                    </p>
                    <!--<div class="mt-8 animated animate-delay-400">-->
                    <!--    <a href="#" class="inline-block bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-bold py-3 px-8 rounded-full hover:opacity-90 transition shadow-lg shadow-cyan-500/50 hover:shadow-cyan-400/70 transform hover:scale-105">-->
                    <!--        View Details-->
                    <!--    </a>-->
                    <!--</div>-->
                    <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-8 text-left">
                        <div class="bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 card-hover-effect animated">
                            <h4 class="text-lg font-semibold text-cyan-400">High Reliability &amp; High Opportunity</h4>
                            <p class="mt-2 text-gray-400 text-sm">Find a balance between risk and reward by using multiple strategies simultaneously.</p>
                        </div>
                        <div class="bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 card-hover-effect animated animate-delay-200">
                            <h4 class="text-lg font-semibold text-cyan-400">Community &amp; Information Driven</h4>
                            <p class="mt-2 text-gray-400 text-sm">Rely on community and our professional news team for opportunities.</p>
                        </div>
                        <div class="bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 card-hover-effect animated animate-delay-400">
                            <h4 class="text-lg font-semibold text-cyan-400">Short-Term Trading Friendly</h4>
                            <p class="mt-2 text-gray-400 text-sm">Intended to capitalize on short to quick turnarounds and price swings.</p>
                        </div>
                        <div class="bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 card-hover-effect animated animate-delay-600">
                            <h4 class="text-lg font-semibold text-cyan-400">Strong Credibility &amp; Market Authority</h4>
                            <p class="mt-2 text-gray-400 text-sm">Informed trading ensures a greater trading confidence, backed by proven results.</p>
                        </div>
                    </div>
                </section>

                <!-- How to Copy Section -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-center">
                        <div class="animated">
                            <h2 class="text-3xl md:text-4xl font-bold glitch-hover w-[80%]" data-text="HOW CAN USERS COPY      OUR TRADES?">
                                HOW CAN USERS COPY <br> <span class="text-cyan-400">OUR TRADES?</span>
                            </h2>
                            <!--<p class="mt-4 text-gray-400">You decide:</p>-->
                            <!--<div class="mt-8 space-y-4">-->
                            <!--    <div class="flex items-center space-x-3 bg-gray-900/50 border-2 border-cyan-500/30 py-3 px-6 rounded-full text-lg">-->
                            <!--        <div class="w-3 h-3 rounded-full bg-cyan-400"></div>-->
                            <!--        <span>which strategy to follow</span>-->
                            <!--    </div>-->
                            <!--    <div class="flex items-center space-x-3 bg-gray-900/50 border-2 border-cyan-500/30 py-3 px-6 rounded-full text-lg">-->
                            <!--        <div class="w-3 h-3 rounded-full bg-cyan-400"></div>-->
                            <!--        <span>how much you want to allocate</span>-->
                            <!--    </div>-->
                            <!--    <div class="flex items-center space-x-3 bg-gray-900/50 border-2 border-cyan-500/30 py-3 px-6 rounded-full text-lg">-->
                            <!--        <div class="w-3 h-3 rounded-full bg-cyan-400"></div>-->
                            <!--        <span>and the system does the rest</span>-->
                            <!--    </div>-->
                            <!--</div>-->
                            <p class="mt-8 text-gray-400">
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing. In this scenario, users are completely independent and our fee is the best in the segment and 100% transparent.
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing. In this scenario, users are completely independent and our fee is the best in the segment and 100% transparent.
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing. In this scenario, users are completely independent and our fee is the best in the segment and 100% transparent.
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing. In this scenario, users are completely independent and our fee is the best in the segment and 100% transparent.
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing. In this scenario, users are completely independent and our fee is the best in the segment and 100% transparent.
                                As we explain, users will have the choice to trade themselves, or in other words, execute a trading strategy of their own choosing.
                            </p>
                            <!--<div class="mt-8">-->
                            <!--    <a href="#" class="inline-block bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-bold py-3 px-8 rounded-full hover:opacity-90 transition shadow-lg shadow-cyan-500/50 hover:shadow-cyan-400/70 transform hover:scale-105">-->
                            <!--        Contact Us-->
                            <!--    </a>-->
                            <!--</div>-->
                        </div>
                        <div class="relative animated animate-delay-200">
                            <img src="images/8.png" alt="Trader at a desk" class="rounded-lg shadow-2xl shadow-cyan-500/20 transition-transform duration-500 hover:scale-105">
                            <div class="absolute -bottom-8 right-0 md:-right-8 bg-cyan-500 text-black p-6 rounded-lg max-w-xs shadow-lg">
                                <p class="font-bold">Open Your Trades From Anywhere in the World</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Thank You Banner -->
            <section class="relative py-12 md:py-16 overflow-hidden group border-y border-white/5 mt-16" style="background: #0a0a0a url('images/thankyou.png') no-repeat center center; background-size: cover;">
    
                <div class="absolute inset-0 bg-black/20 z-0"></div>
            
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        
                        <div class="animated">
                            <h2 class="text-4xl md:text-6xl font-black uppercase italic tracking-tighter text-white drop-shadow-[0_2px_10px_rgba(0,0,0,0.8)] glitch-hover" 
                                data-text="Thank You">
                                Thank You
                            </h2>
                            <p class="text-[9px] font-black text-cyan-400 uppercase tracking-[0.4em] mt-1 drop-shadow-md">
                                Leverage_Your_Potential_
                            </p>
                        </div>
            
                        <div class="animated animate-delay-200">
                            <a href="register.php" class="inline-flex items-center px-10 py-4 bg-gradient-to-r from-[#00E5FF] to-[#00B8D4] text-black text-xs font-black uppercase tracking-[0.2em] rounded-full shadow-[0_10px_30px_rgba(0,229,255,0.45)] hover:shadow-[0_0_50px_rgba(0,229,255,0.8)] transition-all transform hover:scale-105 active:scale-95">

                                Start Trading
                                <svg class="ml-3 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                          d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>

                        </div>
            
                    </div>
                </div>
            </section>

            <div class="space-y-24 md:space-y-12 py-24 md:py-32">
                <!-- Strategies Section -->
                <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center mb-12">
                        <!--<h2 class="text-3xl md:text-4xl font-bold animated glitch-hover" data-text="We Offer Four Proven Strategies You Can Copy">We Offer Four Proven <br> Strategies You Can Copy</h2>-->
                        <h2 class="text-3xl md:text-4xl font-bold animated glitch-hover mx-auto" data-text="We Offer Four Proven Strategies You Can Copy">  We Offer Four Proven <br> Strategies You Can Copy </h2>
                        <div class="hidden md:flex space-x-2 animated animate-delay-200">
                            <button class="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center hover:bg-gray-800 transition transform hover:scale-110 hover:border-cyan-400 hover:text-cyan-400">&lt;</button>
                            <button class="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center hover:bg-gray-800 transition transform hover:scale-110 hover:border-cyan-400 hover:text-cyan-400">&gt;</button>
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-[6rem]">
                        <div class="selectable-card relative group p-6 rounded-lg bg-cyan-500 text-black card-hover-effect animated">
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max max-w-xs p-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <!--A balanced strategy focusing on steady market trends for consistent growth with moderate risk.-->
                            </div>
                            <div class="flex justify-center items-start">
                                <div class="flex flex-col items-center ">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-white/30">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-cyan-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-xl font-bold text-white">VANGUARD</h3>
                                </div>
                              <!--  <p class="text-4xl font-bold text-white animate-percentage" data-target="800">0%</p> -->
                            </div>
                            <p class="mt-4 text-sm text-white/90">Our vanguard includes a strategy that is always trading in the current market. The strategy offers growth potential, and the company's objective is to see large capital gains for investors.</p>
                        </div>
                        <div class="selectable-card relative group p-6 rounded-lg bg-gray-900/50 border border-cyan-500/30 card-hover-effect animated animate-delay-200">
                             <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max max-w-xs p-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <!--Rule-based forex strategy using momentum to capitalize on market shifts for higher growth potential.-->
                            </div>
                            <div class="flex justify-center items-start">
                                <div class="flex flex-col items-center ">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-cyan-500/20">
                                        <span class="font-bold text-cyan-400">F</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-bold text-cyan-400">FIDELITY</h3>
                                </div>
                                <!-- <p class="text-4xl font-bold text-white animate-percentage" data-target="800">0%</p> -->
                            </div>
                            <p class="mt-4 text-sm text-gray-400">A new dynamic, rule-based forex strategy incorporating strategic momentum concepts to create long and short positions to generate higher growth.</p>
                        </div>
                        <div class="selectable-card relative group p-6 rounded-lg bg-gray-900/50 border border-cyan-500/30 card-hover-effect animated animate-delay-400">
                             <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max max-w-xs p-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <!--Multi-asset strategy adapting to global economic cycles for robust, diversified performance.-->
                            </div>
                            <div class="flex justify-center items-start">
                                <div class="flex flex-col items-center ">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-cyan-500/20">
                                        <span class="font-bold text-cyan-400">BR</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-bold text-cyan-400">BLACK ROCK</h3>
                                </div>
                                <!-- <p class="text-4xl font-bold text-white animate-percentage" data-target="1200">0%</p> -->
                            </div>
                            <p class="mt-4 text-sm text-gray-400">A sophisticated, multi-market strategy trading across multiple asset classes, engineered to capitalize on market opportunities aligned to global economic cycles for extra strength.</p>
                        </div>
                        <div class="selectable-card relative group p-6 rounded-lg bg-gray-900/50 border border-cyan-500/30 card-hover-effect animated animate-delay-600">
                             <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max max-w-xs p-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <!--Aggressive, high-performance strategy using AI and live news for maximum profit potential.-->
                            </div>
                            <div class="flex justify-center items-start">
                                <div class="flex flex-col items-center ">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-cyan-500/20">
                                        <span class="font-bold text-cyan-400">VIP</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-bold text-cyan-400">VIP INDEX</h3>
                                </div>
                              <!--   <p class="text-4xl font-bold text-white animate-percentage" data-target="2000">0%</p>  -->
                            </div>
                            <p class="mt-4 text-sm text-gray-400">An active, high-performance strategy combining multiple analytical inputs and live news, powered by artificial intelligence, gives you a strong position to grow your profits.</p>
                        </div>
                    </div>
                </section>

                <!-- Product Grid Section -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 animated hidden">
                    <div id="product-filters" class="flex flex-wrap justify-center gap-2 mb-12">
                        <button data-filter="all" class="active bg-cyan-400 text-black px-4 py-2 rounded-md text-sm font-semibold transition">ALL</button>
                        <button data-filter="stablecoins" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">STABLECOINS</button>
                        <button data-filter="tokenized-funds" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">TOKENIZED FUNDS</button>
                        <button data-filter="liquidity-services" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">LIQUIDITY SERVICES</button>
                        <button data-filter="payments" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">PAYMENTS</button>
                        <button data-filter="blockchains" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">BLOCKCHAINS</button>
                        <button data-filter="developer-services" class="bg-gray-800 text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition hover:bg-gray-700">DEVELOPER SERVICES</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <!-- Card 1 -->
                        <div class="product-card bg-cyan-400 text-black p-6 rounded-lg card-hover-effect" data-category="stablecoins">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C12.5523 2 13 2.44772 13 3V4.27668C15.9337 4.98243 18.0176 7.0663 18.7233 10H20C20.5523 10 21 10.4477 21 11C21 11.5523 20.5523 12 20 12H18.7233C18.0176 14.9337 15.9337 17.0176 13 17.7233V19C13 20.1046 12.1046 21 11 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H11C11.5523 3 12 2.55231 12 2Z" fill="currentColor"></path><path d="M11 5H7V19H11V5Z" fill="currentColor" fill-opacity="0.5"></path></svg>
                            <h3 class="text-xl font-bold mt-4">USDC</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1">STABLECOINS</p>
                            <p class="mt-2 text-sm text-black/90">The world's largest regulated digital dollar. Fully backed, fast, designed for stability, and built for global business.</p>
                        </div>
                        <!-- Card 2 -->
                        <div class="product-card bg-cyan-400 text-black p-6 rounded-lg card-hover-effect" data-category="stablecoins">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C12.5523 2 13 2.44772 13 3V4.2934C15.9019 4.96695 18.033 7.09808 18.7066 10H20C20.5523 10 21 10.4477 21 11C21 11.5523 20.5523 12 20 12H18.7066C18.033 14.9019 15.9019 17.033 13 17.7066V19H14C14.5523 19 15 19.4477 15 20C15 20.5523 14.5523 21 14 21H10C9.44772 21 9 20.5523 9 20C9 19.4477 9.44772 19 10 19H11V14H8V12H11V9H8V7H11V4.2934C10.1554 4.41163 9.35109 4.64696 8.59998 4.98715C8.09998 5.18715 7.59998 4.88715 7.39998 4.38715C7.19998 3.88715 7.49998 3.38715 7.99998 3.18715C9.09998 2.72715 10.2999 2.5 11.5 2.5C11.6667 2.5 11.8333 2.5 12 2Z" fill="currentColor"></path></svg>
                            <h3 class="text-xl font-bold mt-4">EURC</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1">STABLECOINS</p>
                            <p class="mt-2 text-sm text-black/90">The world's largest regulated digital euro. Fully backed, fast, designed for stability, and built for global business.</p>
                        </div>
                        <!-- Card 3 -->
                        <div class="product-card bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg card-hover-effect" data-category="tokenized-funds">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"></path><path d="M10.125 10.125L12 12L13.875 13.875" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M13.875 10.125L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M15.75 8.25V6.75H17.25" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M8.25 15.75V17.25H6.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">USYC™</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">TOKENIZED FUNDS</p>
                            <p class="mt-2 text-sm text-gray-300">An institutional-grade tokenized money market fund with near-instant redeemability to USDC.</p>
                        </div>
                        <!-- Card 4 -->
                        <div class="product-card bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg card-hover-effect" data-category="payments">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"></path><path d="M15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12Z" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Leverage Factory Payments Network</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">PAYMENTS</p>
                            <p class="mt-2 text-sm text-gray-300">One integration for global stablecoin-powered payments.</p>
                        </div>
                        <!-- Card 5 -->
                        <div class="product-card bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg card-hover-effect" data-category="liquidity-services">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 4.9V19.1C16 20.1 15.1 21 14.1 21H5.9C4.9 21 4 20.1 4 19.1V4.9C4 3.9 4.9 3 5.9 3H14.1C15.1 3 16 3.9 16 4.9Z" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"></path><path d="M20 4.9V19.1C20 18.2 19.6 17.3 19 16.7L16 14.2V8.8L19 6.3C19.6 5.7 20 4.9 20 4.9Z" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Mint™</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">LIQUIDITY SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Mint, redeem, and manage USDC directly from Leverage Factory at global scale.</p>
                        </div>
                        <!-- Card 6 -->
                        <div class="product-card bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg card-hover-effect" data-category="liquidity-services">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L12 12L12 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M17 7L7 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7 7L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">StableFX</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">LIQUIDITY SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Institutional-grade onchain FX, powered by stablecoins.</p>
                        </div>
                        <!-- Card 7 -->
                        <div class="product-card bg-gray-900/50 border border-cyan-500/30 p-6 rounded-lg card-hover-effect" data-category="blockchains">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L3 7L12 12L21 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 17L12 22L21 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 12L12 17L21 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Arc™</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">BLOCKCHAINS</p>
                            <p class="mt-2 text-sm text-gray-300">An open Layer-1 blockchain built to meet the demands of the global internet economy.</p>
                        </div>
                        <!-- Card 8 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 7V17C20 18.1046 19.1046 19 18 19H6C4.89543 19 4 18.1046 4 17V7C4 5.89543 4.89543 5 6 5H18C19.1046 5 20 5.89543 20 7Z" stroke="currentColor" stroke-width="2"></path><path d="M16 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Wallets™</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Easily integrate digital asset storage, payments, and transactions into your apps.</p>
                        </div>
                        <!-- Card 9 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Contracts™</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">A curated library of customizable smart contract templates for tokenization and workflow automation.</p>
                        </div>
                        <!-- Card 10 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 14.5C2 13.0315 2.84315 11.75 4 11.125M22 9.5C22 10.9685 21.1569 12.25 20 12.875M20 11.125C21.1569 11.75 22 13.0315 22 14.5M4 12.875C2.84315 12.25 2 10.9685 2 9.5M16 4L12 8L8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 20L12 16L8 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">CCTP</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Transfer USDC natively between supported chains in seconds, more securely than traditional bridging.</p>
                        </div>
                        <!-- Card 11 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21L15 18L12 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 3L9 6L12 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M21 12L18 9L15 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 12L6 15L9 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9 6L12 3L15 6L12 9L9 6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path><path d="M6 9L9 12L6 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18 9L15 12L18 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9 18L12 15L15 18L12 21L9 18Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">xReserve</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Launch a USDC-backed stablecoin on your blockchain.</p>
                        </div>
                        <!-- Card 12 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 10L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M10 14L3 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M21 15V3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 9V21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Gateway</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Enable a unified USDC balance for instant crosschain liquidity in &lt;500 ms.</p>
                        </div>
                        <!-- Card 13 -->
                        <div class="product-card bg-gray-900/30 border border-cyan-500/10 p-6 rounded-lg card-hover-effect" data-category="developer-services">
                           <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 22V18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M19 12V2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 22V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 9V2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M5 22V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M5 11V2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 11H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M17 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M10 9H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            <h3 class="text-xl font-bold mt-4 text-cyan-400">Paymaster</h3>
                            <p class="text-xs uppercase tracking-wider font-semibold mt-1 text-gray-400">DEVELOPER SERVICES</p>
                            <p class="mt-2 text-sm text-gray-300">Enable seamless transaction experiences by allowing users to pay gas fees in USDC.</p>
                        </div>
                    </div>
                </section>
                <Section class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <!-- Dark Overlay -->
                      <!--<div class="absolute inset-0 bg-black/50 -z-5"></div>-->
                      <div class="grid w-[200%] h-full grid-cols-2 gap-0 transition-transform duration-500 text-center">
                        <div class="relative size-full px-4 text-white sm:pt-33.5 md:pb-35!">
                    
                          <h2 class="mb-6 text-3xl font-semibold lg:text-5xl">
                            Digital-First Spending, Made Simple
                          </h2>
                    
                          <p class="text-base text-balance sm:text-xl">
                            Experience effortless, secure payments for subscriptions, travel, and
                            everyday purchases.
                          </p>
                        </div>
                      </div>
                </Section>
                
                 <section
                  class="relative mx-auto overflow-hidden h-[70vh] sm:min-h-[70vh] bg-black text-start sm:w-9/10 sm:text-center md:rounded-[60px] lg:rounded-[60px] bg-black/50"
                >
                  <!-- Background YouTube Video -->
                    <div class="absolute inset-0 z-10 overflow-hidden rounded-[60px]">
                      <iframe
                        class="absolute top-1/2 left-1/2 min-w-[61%] min-h-[100%] w-auto h-auto -translate-x-1/2 -translate-y-1/2 pointer-events-none rounded-[60px]"
                        src="https://www.youtube.com/embed/dESmYe7F1LQ?autoplay=1&mute=1&controls=0&loop=1&playlist=dESmYe7F1LQ&modestbranding=1&showinfo=0"
                        title="YouTube video player"
                        frameborder="0"
                        allow="autoplay; encrypted-media"
                        allowfullscreen>
                      </iframe>
                    </div>

                      <div class="absolute bottom-16 z-5 flex w-full items-center justify-center gap-4">
                        <button class="relative cursor-pointer overflow-hidden rounded-full border px-6 py-3 text-base leading-[13px] transition-colors duration-200 sm:py-4 sm:text-lg border-foreground bg-white/80 text-black">
                          Virtual Card
                          <div class="absolute inset-y-0 left-0 -z-1 bg-white/50"></div>
                        </button>
                    
                        <button class="relative cursor-pointer overflow-hidden rounded-full border px-6 py-3 text-base leading-[13px] transition-colors duration-200 sm:py-4 sm:text-lg border-white bg-transparent text-white hover:bg-white/10">
                          Physical Card
                        </button>
                      </div>
                
                </section>


                
                <section class="relative py-12 md:py-2 overflow-hidden group border-y border-white/5 " style="background: #06b6c97d no-repeat center center; background-size: cover;">
                    <div class="absolute inset-0 bg-black/20 z-0"></div>
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div id="crypto-row" class="flex flex-col md:flex-row w-full gap-4" data-lazy="" tracking-section-name="crypto">
                                <div class="w-1/2">
                                    <div class="image-box">
                                        <img src="/images/crypto2x.png" alt="crypto" class="max-w-[400px] rounded-xl">
                                    </div>
                                </div>
                                <div class="w-1/2">
                                    <div class="h-full mt-8">
                                        <div>
                                            <h2 class="text-[40px] mb-0 md:mb-4">Crypto trading at its best</h2>
                                                <p class="text-[20px] leading-normal">Trade and manage 70+ cryptoassets on a trusted global platform that offers top-tier security, powerful tools,
                                                  user-friendly features, and fixed transparent fees. Eligible eToro Club members can also
                                                    <a href="/crypto/trade-with-crypto/" class="underline">sell their crypto for GBP or EUR</a>,
                                                  unlocking even more flexibility to trade, invest, or explore new opportunities.
                                                </p>
                                        </div>
                                        <div class="mt-6">
                                            <p class="text-16px leading-normal">Cryptoasset investing is highly volatile and unregulated in some EU countries. No consumer protection. Tax on profits may apply.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </section>
                
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[450px] mb-10">
                    <div class="flex flex-col md:flex-row w-full">
                        <div class="w-1/2 flex flex-col items-start justify-center">
                            <div class="">
                                <h2 class="text-[40px] leading-normal">Diversify your portfolio</h2>
                                <p class="text-[20px] leading-normal">Invest in a variety of asset classes — including 20 global stock exchanges and 100 cryptocurrencies — while managing all of your holdings in one place</p>
                            </div>
                        </div>
                        <div class="w-1/2 -mt-[34px]">
                            <img class="" loading="lazy" width="100%" height="100%" src="/images/dyversify.png" alt="dyversify">
                        </div>
    
                    </div>
                </section>
                
                <!-- Platforms Section -->
                <section id="pages" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold animated glitch-hover" data-text="Our Trading Platforms">Our Trading Platforms</h2>
                    <p class="mt-4 max-w-3xl mx-auto text-gray-400 animated animate-delay-200">
                        We trade members on leading high-liquidity cryptocurrency exchanges such as BYBIT, OKX for secure, fast execution, reliable price feeds, and secure trading environments. Our trading activity focuses on reputable centralized exchanges and selected decentralized exchanges.
                    </p>
                    <!--<div class="mt-8 animated animate-delay-400">-->
                    <!--    <a href="#" class="inline-block bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-bold py-3 px-8 rounded-full hover:opacity-90 transition shadow-lg shadow-cyan-500/50 hover:shadow-cyan-400/70 transform hover:scale-105">-->
                    <!--        Join Now-->
                    <!--    </a>-->
                    <!--</div>-->
                    <div class="mt-12 overflow-hidden relative">
                        <p class="text-gray-400 text-[10px] font-black tracking-[0.5em] uppercase text-center mb-10 italic">
                            Integrated with Major Protocols_
                        </p>
                        
                        <div class="logo-slider">
                            <div class="slide"><img src="images/logo/1.png" class="exchange-logo h-[5rem]"></div>
                            <div class="slide"><img src="images/logo/2.png" class="exchange-logo h-[5rem]"></div>
                            <div class="slide"><img src="images/logo/3.png" class="exchange-logo h-[8rem] y"></div>
                            <div class="slide"><img src="images/logo/4.png" class="exchange-logo h-[9rem] x"></div>
                            <div class="slide"><img src="images/logo/5.png" class="exchange-logo h-[6rem] a"></div>
                            <div class="slide"><img src="images/logo/6.png" class="exchange-logo h-[4rem] b"></div>
                            <div class="slide"><img src="images/logo/7.png" class="exchange-logo h-[8rem] c"></div>
                            <div class="slide"><img src="images/logo/8.png" class="exchange-logo h-[5rem] d"></div>
                            <div class="slide"><img src="images/logo/9.png" class="exchange-logo h-[8rem] e"></div>
                            <div class="slide"><img src="images/logo/10.png" class="exchange-logo h-[8rem] e"></div>
                            <div class="slide"><img src="images/logo/11.png" class="exchange-logo h-[8rem] e"></div>
                            <div class="slide"><img src="images/logo/12.png" class="exchange-logo h-[8rem] e"></div>
                        </div>
                  
                    </div>
                    <div class="animated p-8 bg-gray-900/50 border border-cyan-500/30 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 space-y-4 mt-[5rem] hidden">
                        <h4 class="text-2xl font-bold text-center">Estimate Your Earnings</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Left Column: Inputs -->
                            <div id="level-inputs-container" class="space-y-2">
                                <!-- Populated by JS -->
                            </div>
                            <!-- Right Column: Breakdown -->
                            <div class="flex flex-col">
                                <h5 class="text-lg font-bold text-cyan-400 mb-2 text-center">Earnings Breakdown</h5>
                                <div id="earnings-breakdown" class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm flex-grow">
                                    <!-- Populated by JS -->
                                </div>
                                <div class="mt-4 border-t border-cyan-500/30 pt-3 text-center">
                                    <p class="text-gray-400 text-sm">Total Potential Earnings:</p>
                                    <p id="total-earnings" class="text-2xl font-bold text-cyan-400 mt-1">$0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-16 animated hidden">
                        <img src="4.png" alt="Trading Platform UI" class="rounded-lg shadow-2xl shadow-cyan-500/20 mx-auto transition-transform duration-500 hover:scale-105">
                    </div>
                </section>

                <!-- Affiliate Program Section -->
                <section id="pricing" class=" max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20 hidden">
                    <div class="grid md:grid-cols-1 gap-16 items-center">
                         <div class="animated">
                            <h3 class="text-3xl font-bold">Affiliate Program</h3>
                            <p class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-teal-400">
                                10 Levels Of Deposit Bonus
                            </p>
                            <p class="mt-2 text-cyan-400">*Earn Direct Referral And Collect One-Level Of Income</p>
                            <div class="mt-8 grid md:grid-cols-2 gap-4">
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated"><span class="font-semibold text-cyan-400">Level 1</span><span class="font-bold">10%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-200"><span class="font-semibold text-cyan-400">Level 6</span><span class="font-bold">2.5%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-200"><span class="font-semibold text-cyan-400">Level 2</span><span class="font-bold">5%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-400"><span class="font-semibold text-cyan-400">Level 7</span><span class="font-bold">1%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-400"><span class="font-semibold text-cyan-400">Level 3</span><span class="font-bold">2%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-600"><span class="font-semibold text-cyan-400">Level 8</span><span class="font-bold">1%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-600"><span class="font-semibold text-cyan-400">Level 4</span><span class="font-bold">1%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-200"><span class="font-semibold text-cyan-400">Level 9</span><span class="font-bold">1%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-200"><span class="font-semibold text-cyan-400">Level 5</span><span class="font-bold">0.5%</span></div>
                                <div class="flex justify-between items-center bg-gray-900/50 border border-cyan-500/30 py-3 px-6 rounded-lg card-hover-effect animated animate-delay-400"><span class="font-semibold text-cyan-400">Level 10</span><span class="font-bold">1%</span></div>
                            </div>
                        </div>

                        <div class="animated p-8 bg-gray-900/50 border border-cyan-500/30 rounded-lg backdrop-blur-sm shadow-lg shadow-cyan-900/20 space-y-6">
                            <h4 class="text-2xl font-bold text-center">Estimate Your Earnings</h4>
                            <div>
                                <label for="deposit-amount" class="block text-sm font-medium text-cyan-400">Average Deposit per Referral ($)</label>
                                <input type="number" id="deposit-amount" value="1000" class="mt-1 block w-full bg-gray-800 border-gray-700 text-white rounded-md p-2 focus:ring-cyan-500 focus:border-cyan-500">
                            </div>
                             <div>
                                <label for="referrals-slider" class="block text-sm font-medium text-cyan-400">Number of Referrals (<span id="referrals-value">10</span>)</label>
                                <input type="range" id="referrals-slider" min="1" max="100" value="10" class="mt-1 block w-full">
                            </div>
                            <div class="text-center pt-4">
                                <p class="text-gray-400">Potential Level 1 Earnings:</p>
                                <p id="potential-earnings" class="text-4xl font-bold text-cyan-400 mt-2">$1,000.00</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <footer class="bg-slate-900 text-gray-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <!-- Top Section: Links & Newsletter -->
                <div class="flex flex-col md:flex-row justify-between mb-12">
                    <div class="flex flex-col md:flex-row w-full gap-8 md:gap-28">
                    <!-- Platform Links -->
                    <div>
                        <h4 class="font-bold uppercase tracking-wider text-white mb-4">Platform</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">USDC</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">EURC</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">USYC</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Leverage Factory Payments Network</a></li>
                        </ul>
                    </div>
                    <!-- Use Cases Links -->
                    <div>
                        <h4 class="font-bold uppercase tracking-wider text-white mb-4">Use Cases</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Payments</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Dollar Access</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Trading Services</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Case Studies</a></li>
                        </ul>
                    </div>
                    </div>
                    <!-- Developer Links -->
                  <!--  <div>
                        <h4 class="font-bold uppercase tracking-wider text-white mb-4">Developer</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Developer Hub</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Documentation</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">API Reference</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Sample Projects</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Testnet Faucet</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Developer Blog</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Leverage Factory Research</a></li>
                            <li><a href="#" class="hover:text-cyan-400 transition text-sm">Uptime Status</a></li>
                        </ul>
                    </div> -->
                    <!-- Newsletter -->
                    <div class="mt-10 md:mt-0">
                        <h4 class="font-bold uppercase tracking-wider text-white mb-4">Subscribe to the Leverage Factory Newsletter</h4>
                        <form id="newsletter-form" class="flex items-center">
                            <input type="email" id="news-email" placeholder="Enter your email address" required class="w-full bg-gray-200 text-black px-4 py-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <button type="submit" class="bg-cyan-400 text-white font-bold px-6 py-2 rounded-r-md hover:bg-cyan-500 transition">Subscribe</button>
                        </form>
                        <div id="subs-msg" class="mt-2 text-[10px] font-bold uppercase tracking-widest hidden"></div>
                        <p class="mt-4 text-xs text-gray-400">
                            By submitting this form, you agree to receive marketing and other communications from Leverage Factory about Leverage Factory Products and other company updates. You can unsubscribe from these communications at any time. For more information on our privacy practices, please review our <a href="privacy-policy.php" class="underline hover:text-purple-400">Privacy Policy</a>.
                        </p>
                    </div>
                </div>
                
                <!-- Divider -->
                <div class="border-t border-gray-700 my-8"></div>

                <!-- Middle Section: Logo, Lang, Socials -->
                <div class="flex flex-col md:flex-row justify-between items-center space-y-6 md:space-y-0">
                     <!-- Left Side -->
                    <div class="flex items-center space-x-6">
                        <!-- Leverage Factory Logo SVG -->
                        <img src="logo-removebg-preview.png" style="height: 40px;">
                        <!-- Language Selector -->
                        <!--<div class="flex items-center space-x-2">-->
                        <!--    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m0 0a9 9 0 019-9m-9 9a9 9 0 009 9"></path></svg>-->
                        <!--    <span>EN</span>-->
                        <!--</div>-->
                    </div>
                    <!-- Right Side -->
                    <div class="flex items-center space-x-4">
                         <a href="#" class="hover:text-cyan-400 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
                         </a>
                         <a href="#" class="hover:text-cyan-400 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.316 1.363.364 2.427.048 1.067.06 1.407.06 3.808s-.012 2.741-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.316-2.427.364-1.067.048-1.407.06-3.808.06s-2.741-.012-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.316-1.363-.364-2.427C2.013 14.741 2 14.4 2 12s.013-2.741.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.316 2.427-.364C8.933 2.013 9.273 2 11.715 2h.6zM12 6.848c-2.84 0-5.152 2.312-5.152 5.152s2.312 5.152 5.152 5.152 5.152-2.312 5.152-5.152S14.84 6.848 12 6.848zM12 15.354c-1.844 0-3.354-1.51-3.354-3.354s1.51-3.354 3.354-3.354 3.354 1.51 3.354 3.354-1.51 3.354-3.354 3.354zM16.965 6.586a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z" clip-rule="evenodd"></path></svg>
                         </a>
                         <a href="#" class="hover:text-cyan-400 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"></path></svg>
                         </a>
                         <a href="#" class="hover:text-cyan-400 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M20.283 4.403s-.182-.04-.333-.004c-1.921.43-3.602 1.34-4.996 2.65-.296.28-.56.582-.787.907-.027.04-.05.08-.073.12l-1.463-2.403c-.382-.627-.85-1.2-1.38-1.713-.578-.565-1.255-1.02-2.004-1.345-.78-.34-1.635-.51-2.51-.49-.977.02-1.924.23-2.79.6-1.03.44-1.914 1.1-2.58 1.9-1.012 1.21-1.55 2.75-1.515 4.33.023.95.27 1.88.72 2.7.47.85.912 1.48 1.31 1.86.38.36.73.62 1.05.78.22.1.43.15.65.15h.05c.23 0 .46-.06.68-.18.23-.13.44-.3.62-.5.16-.18.28-.38.38-.6s.17-.4.2-.64l-.56-2.06c-.05-.18-.04-.36.03-.53.07-.17.19-.3.35-.38.16-.08.35-.1.52-.05.17.04.3.15.38.3l1.15 2.2c.16.3.36.57.6.8.24.24.5.43.78.58.28.15.58.25.88.3.3.04.6.04.9-.02a5.52 5.52 0 002.7-.95c.8-.5 1.48-1.2 2-2.06.5-.86.82-1.8.95-2.77.13-1 .04-2.02-.28-2.98-.3-.95-.82-1.8-1.5-2.5s-1.47-1.25-2.33-1.64c-.23-.1-.47-.16-.7-.2-.24-.04-.48-.04-.72-.02-.23.02-.46.07-.68.16-.22.09-.43.2-.6.37l-.2.2c-.32.33-.52.76-.58 1.22l-.17 1.45c-.02.15.01.3.08.43s.18.24.3.3c.12.07.26.08.4.04.14-.04.27-.13.36-.25l2.08-3.3c.35-.57.8-.95 1.3-1.15.5-.2 1.05-.2 1.55 0 .58.22 1.08.64 1.42 1.2.34.55.5 1.2.45 1.85-.04.65-.27 1.3-.65 1.85-.38.56-.9 1-1.5 1.28-.6.28-1.28.36-1.92.23-.65-.12-1.24-.45-1.7-1-.4-.5-.65-1.1-.73-1.75l.18-1.55c.02-.2.02-.4 0-.6-.02-.2-.07-.4-.15-.58-.08-.18-.2-.34-.33-.48-.13-.14-.28-.25-.45-.33-.17-.08-.35-.13-.53-.14-.18-.01-.37 0-.54.05-.17.05-.34.13-.5.23-.15.1-.3.23-.42.38-.12.15-.22.3-.3.47l-1.14 2.1c-.08.14-.2.25-.34.33-.14.08-.3.1-.46.08l-1.3-.38c.1-.5.3-.98.58-1.4.28-.42.63-.8.92-1.12.06-.06.1-.13.14-.2.4-.4.8-.8 1.2-1.1s.8-.6 1.2-.8c.4-.2.8-.3 1.2-.3s.8.1 1.1.2c.3.1.6.3.8.5.2.2.4.4.5.7.1.3.2.6.2.9z"></path></svg>
                         </a>
                         <a href="#" class="hover:text-cyan-400 transition">
                             <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zM9 16V8l7 4-7 4z"></path></svg>
                         </a>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-8"></div>
                
                <!-- Bottom Section: Legal -->
                <div class="text-xs text-white-500 space-y-4">
                    <p>
                        Past performance does not guarantee or predict future results. Trading and investing in cryptocurrencies, forex, and commodities involves a high level of risk and may not be suitable for all investors. You should carefully consider your investment objectives, level of experience, and risk tolerance before participating in any trading activities.
                    </p>
                
                    <p>
                        We strongly recommend that you seek independent financial advice from a suitably licensed professional to ensure you fully understand the risks involved. Under no circumstances shall Leverage Factory be liable for any loss or damage of any kind, including direct, indirect, special, consequential, or incidental losses arising from trading activities or reliance on platform information.
                    </p>
                
                    <p>
                        Crypto investments are highly speculative, can fluctuate significantly in value, and may not be protected by regulatory investor compensation schemes or dispute resolution protections such as MiFID frameworks, the Cyprus Investor Compensation Fund (ICF), the Financial Services Compensation Scheme (FSCS), or the Financial Ombudsman Service.
                    </p>
                
                    <p>
                        Copy trading carries additional risks. By copying or following other traders, you acknowledge that you may be replicating strategies of traders who may not be licensed professionals and whose trading objectives, financial situation, and risk profile may differ from yours. The historical performance of any Leverage Factory community member does not constitute a reliable indicator of future results. All content shared within the Leverage Factory ecosystem is provided by community members and does not constitute financial advice or recommendations from Leverage Factory.
                    </p>
                
                    <p>
                        © 2026 Leverage Factory — Your Social Investment Network. All rights reserved.
                    </p>
                </div>

            </div>
        </footer>
    </div>
    
    <script>
        const cards = document.querySelectorAll('.selectable-card');
        
        cards.forEach(card => {
            card.addEventListener('click', () => {
        
                // RESET ALL CARDS
                cards.forEach(c => {
                    c.classList.remove('bg-cyan-500','text-black');
                    c.classList.add('bg-gray-900/50');
        
                    // reset p color
                    c.querySelectorAll('p').forEach(p=>{
                        p.classList.remove('text-white');
                        p.classList.add('text-gray-400');
                    });
        
                    // reset icon circle text
                    const iconDiv = c.querySelector('.w-12.h-12');
                    if(iconDiv){
                        iconDiv.classList.remove('text-white');
                        iconDiv.classList.add('text-red-500');
                    }
                });
        
                // ACTIVATE CLICKED CARD
                card.classList.remove('bg-gray-900/50');
                card.classList.add('bg-cyan-500','text-black');
        
                // p → white
                card.querySelectorAll('p').forEach(p=>{
                    p.classList.remove('text-gray-400');
                    p.classList.add('text-white');
                });
        
                // icon text → white
                const activeIcon = card.querySelector('.w-12.h-12');
                if(activeIcon){
                    activeIcon.classList.remove('text-cyan-400');
                    activeIcon.classList.add('text-red-500');
                }
        
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // --- ANIMATIONS ---
            function animateCount(el) {
                const target = parseInt(el.dataset.target, 10);
                if (isNaN(target)) return;
                let current = 0;
                const duration = 1500;
                const increment = Math.ceil(target / (duration / 16));

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    el.textContent = `${current.toLocaleString()}%`;
                }, 16);
            }

            const animatedElements = document.querySelectorAll('.animated');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        
                        entry.target.querySelectorAll('.animate-percentage').forEach(el => {
                            if (!el.dataset.animated) {
                                animateCount(el);
                                el.dataset.animated = 'true';
                            }
                        });

                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            animatedElements.forEach(el => observer.observe(el));

            // --- MOBILE MENU ---
            const menuBtn = document.getElementById('menu-btn');
            const mobileNav = document.getElementById('mobile-nav');
            menuBtn.addEventListener('click', () => {
                mobileNav.classList.toggle('open');
            });
            mobileNav.addEventListener('click', (e) => {
                if(e.target.tagName === 'A') {
                    mobileNav.classList.remove('open');
                }
            });

            
            // --- SCROLL SPY ---
            // --- REFINED SCROLL SPY PROTOCOL ---
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('header nav a, #mobile-nav nav a');

    const scrollOptions = {
        root: null,
        // The negative top margin "pre-detects" the section before it hits the absolute top
        rootMargin: '-10% 0px -80% 0px', 
        threshold: 0
    };

    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                
                navLinks.forEach(link => {
                    // Reset all links
                    link.classList.remove('text-cyan-400', 'font-bold');
                    link.classList.add('text-gray-300');
                    
                    // Activate current link
                    if (link.getAttribute('href') === `#${id}`) {
                        link.classList.add('text-cyan-400', 'font-bold');
                        link.classList.remove('text-gray-300');
                    }
                });
            }
        });
    }, scrollOptions);

    sections.forEach(section => scrollObserver.observe(section));
});
            
            // --- AFFILIATE CALCULATOR ---
            const levelInputsContainer = document.getElementById('level-inputs-container');
            const earningsBreakdownContainer = document.getElementById('earnings-breakdown');
            const totalEarningsEl = document.getElementById('total-earnings');
            
            const commissionRates = [0.10, 0.05, 0.02, 0.01, 0.005, 0.025, 0.01, 0.01, 0.01, 0.01];

            function setupCalculator() {
                if (!levelInputsContainer) return;
                
                let inputsHTML = '';
                for (let i = 1; i <= 10; i++) {
                    inputsHTML += `
                        <div>
                            <label for="level-${i}-deposit" class="block text-xs font-medium text-cyan-400/80">Level ${i} Deposit ($)</label>
                            <input type="number" id="level-${i}-deposit" placeholder="0" class="level-deposit-input mt-1 block w-full bg-gray-800 border-gray-700 text-white rounded-md p-1.5 text-sm focus:ring-cyan-500 focus:border-cyan-500">
                        </div>
                    `;
                }
                levelInputsContainer.innerHTML = inputsHTML;
                
                // Add event listeners after creating the inputs
                document.querySelectorAll('.level-deposit-input').forEach(input => {
                    input.addEventListener('input', updateCalculator);
                });

                updateCalculator(); // Initial calculation
            }

            function updateCalculator() {
                if (!earningsBreakdownContainer || !totalEarningsEl) return;

                let totalEarnings = 0;
                let breakdownHTML = '';

                for (let i = 0; i < commissionRates.length; i++) {
                    const level = i + 1;
                    const rate = commissionRates[i];
                    
                    const inputEl = document.getElementById(`level-${level}-deposit`);
                    const deposit = inputEl ? (parseFloat(inputEl.value) || 0) : 0;
                    
                    const earnings = deposit * rate;
                    totalEarnings += earnings;
                    
                    const formattedEarnings = earnings.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                    const ratePercentage = (rate * 100).toFixed(1).replace(/\.0$/, '');

                    breakdownHTML += `
                        <div class="text-gray-300">Level ${level} (${ratePercentage}%)</div>
                        <div class="font-semibold text-white text-right">${formattedEarnings}</div>
                    `;
                }

                earningsBreakdownContainer.innerHTML = breakdownHTML;
                totalEarningsEl.textContent = totalEarnings.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
            }

            setupCalculator();


            // --- DYNAMIC ABOUT SLIDER ENGINE ---
            const slideTextElements = document.querySelectorAll('.slide-text');
            const slideImageElements = document.querySelectorAll('.slide-image');
            const slideIndicators = document.querySelectorAll('.slide-indicator');
            const readMoreBtn = document.getElementById('read-more-btn');
            
            // Capture slugs from PHP into a JS array
            const blogSlugs = [<?php foreach($featured_blogs as $fb) echo '"' . $fb['slug'] . '",'; ?>];
            
            if (slideTextElements.length > 0) {
                let currentSlide = 0;
            
                function showSlide(index) {
                    slideTextElements.forEach((s, i) => {
                        s.classList.toggle('opacity-100', i === index);
                        s.classList.toggle('opacity-0', i !== index);
                    });
            
                    slideImageElements.forEach((img, i) => {
                        img.classList.toggle('opacity-100', i === index);
                        img.classList.toggle('opacity-0', i !== index);
                    });
            
                    slideIndicators.forEach((ind, i) => {
                        ind.classList.toggle('bg-cyan-400', i === index);
                        ind.classList.toggle('bg-gray-700', i !== index);
                    });
                    
                    // Update the link to the correct blog slug
                    if(readMoreBtn && blogSlugs[index]) {
                        readMoreBtn.href = 'read.php?slug=' + blogSlugs[index];
                    }
            
                    currentSlide = index;
                }
            
                document.getElementById('prev-slide').addEventListener('click', () => {
                    const newIndex = (currentSlide - 1 + slideTextElements.length) % slideTextElements.length;
                    showSlide(newIndex);
                });
            
                document.getElementById('next-slide').addEventListener('click', () => {
                    const newIndex = (currentSlide + 1) % slideTextElements.length;
                    showSlide(newIndex);
                });
            
                // Auto-advance every 10 seconds for user engagement
                setInterval(() => {
                    const next = (currentSlide + 1) % slideTextElements.length;
                    showSlide(next);
                }, 10000);
            }
            
            // --- MATRIX EFFECT ---
            const canvas = document.getElementById('matrix-canvass');
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

            // --- PRODUCT FILTER ---
            const filterContainer = document.getElementById('product-filters');
            const productCards = document.querySelectorAll('.product-card');

            if (filterContainer && productCards.length > 0) {
                filterContainer.addEventListener('click', (e) => {
                    const button = e.target.closest('button');
                    if (!button) return;
                    
                    const filter = button.dataset.filter;
                    
                    // Update active button state
                    const currentActive = filterContainer.querySelector('button.active');
                    if (currentActive) {
                        currentActive.classList.remove('active', 'bg-cyan-400', 'text-black');
                        currentActive.classList.add('bg-gray-800', 'text-gray-300');
                    }
                    button.classList.add('active', 'bg-cyan-400', 'text-black');
                    button.classList.remove('bg-gray-800', 'text-gray-300');

                    // Filter cards
                    productCards.forEach(card => {
                        if (filter === 'all' || card.dataset.category === filter) {
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                });
            }
        });
    </script>
    <script>
        document.getElementById('newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = document.getElementById('news-email');
            const msgDiv = document.getElementById('subs-msg');
            const email = emailInput.value;
        
            fetch('subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                msgDiv.textContent = data.message;
                msgDiv.classList.remove('hidden', 'text-red-400', 'text-green-400');
                msgDiv.classList.add(data.status === 'success' ? 'text-green-400' : 'text-red-400');
                if(data.status === 'success') emailInput.value = '';
            });
        });
    </script>
  <script type="module" src="/index.tsx"></script>
</body>
</html>
