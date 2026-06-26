<?php
// Get the current file name to highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="md:hidden bg-white border-b border-gray-200 p-4 flex justify-between items-center sticky top-0 z-[100]">
    <img src="https://leveragefactory.ai/logo-removebg-preview.png" class="h-8" alt="logo"/>
    <button onclick="toggleSidebar()" class="p-2 text-gray-600 focus:outline-none">
        <svg id="menuIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
        </svg>
    </button>
</div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-[110] w-64 bg-white border-r border-gray-200 transform -translate-x-full md:translate-x-0 md:static transition-transform duration-300 ease-in-out flex flex-col h-screen">
    <div class="px-4 py-7 flex items-center gap-2">
        <img src = "https://leveragefactory.ai/logo-removebg-preview.png" class="rounded-lg" alt="logo"/>
    </div>

    <nav class="flex-1 px-2 overflow-auto">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'dashboard.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20V16"/></svg>
                    <span class="ml-4 font-medium">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'profile.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="ml-4 font-medium">My Profile</span>
                </a>
            </li>
            <li>
                <a href="kyc_upload.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'kyc_upload.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="ml-4 font-medium">Identity (KYC)</span>
                    <?php if(isset($user['kyc_status']) && $user['kyc_status'] != 'approved'): ?>
                        <span class="ml-auto w-2 h-2 bg-yellow-400 rounded-full" title="Action Required"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="security.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'security.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span class="ml-4 font-medium">Security Center</span>
                </a>
            </li>
            <li>
                <a href="invest.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'invest.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="ml-4 font-medium">Investment Plans</span>
                </a>
            </li>
            <li>
                <a href="active_investments.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'active_investments.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="ml-4 font-medium">Active Investment Plans</span>
                </a>
            </li>
            <li>
                <a href="wallet.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'wallet.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    <span class="ml-4 font-medium">E-Wallet</span>
                </a>
            </li>
            <li>
                <a href="withdraw.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'withdraw.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                    </svg>
                    <span class="ml-4 font-medium">Payout Request</span>
                </a>
            </li>
            <li>
                <a href="ledger.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'ledger.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    <span class="ml-4 font-medium">Transaction Ledger</span>
                </a>
            </li>
            <li>
                <a href="network.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'network.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="ml-4 font-medium">My Network</span>
                </a>
            </li>
            <li>
                <a href="network_stats.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'network_stats.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span class="ml-4 font-medium">Network Stats</span>
                </a>
            </li>
            <li>
                <a href="network_tree.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'network_tree.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path d="M12 2v8M12 2l-3 3M12 2l3 3M4 14v6c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2v-6"></path>
                        <circle cx="12" cy="12" r="2"></circle>
                        <path d="M5 22v-4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"></path>
                    </svg>
                    <span class="ml-4 font-medium italic">Network Tree</span>
                </a>
            </li>
            <li>
                <a href="earnings_calculator.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'earnings_calculator.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M9 14h6"></path><path d="M9 18h6"></path><path d="M9 10h6"></path></svg>
                    <span class="ml-4 font-medium">Earnings Calculator</span>
                </a>
            </li>
            
            <li class="relative group">
                <div class="flex items-center p-3 my-1 rounded-xl transition-all duration-200 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M6 21V7"></path>
                        <path d="M10 21V7"></path>
                        <path d="M14 21V7"></path>
                        <path d="M18 21V7"></path>
                    </svg>
                    <span class="ml-4 font-medium">Buy Gold</span>
                    <span class="ml-auto bg-[#00A6FB]/10 text-[#00A6FB] text-[7px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter animate-pulse">Coming Soon</span>
                </div>
            </li>
            
            <li class="relative group">
                <div class="flex items-center p-3 my-1 rounded-xl transition-all duration-200 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M6 21V7"></path>
                        <path d="M10 21V7"></path>
                        <path d="M14 21V7"></path>
                        <path d="M18 21V7"></path>
                    </svg>
                    <span class="ml-4 font-medium">Buy Silver</span>
                    <span class="ml-auto bg-[#00A6FB]/10 text-[#00A6FB] text-[7px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter animate-pulse">Coming Soon</span>
                </div>
            </li>
            
            <li class="relative group">
                <div class="flex items-center p-3 my-1 rounded-xl transition-all duration-200 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M12 8V4H8"></path>
                        <rect x="4" y="8" width="16" height="12" rx="2"></rect>
                        <path d="M2 14h2"></path>
                        <path d="M20 14h2"></path>
                        <path d="M15 13v2"></path>
                        <path d="M9 13v2"></path>
                    </svg>
                    <span class="ml-4 font-medium">Leverage BOT</span>
                    <span class="ml-auto bg-purple-500/10 text-purple-400 text-[7px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter animate-pulse">Coming Soon</span>
                </div>
            </li>
        </ul>
    </nav>

    <div class="px-2 mb-4">
        <div class="border-t border-gray-200 my-4"></div>
        <a href="logout.php" class="flex items-center p-3 rounded-md text-gray-600 hover:bg-red-50 hover:text-red-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span class="ml-4 font-medium">Logout</span>
        </a>
    </div>
</aside>
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-[105] hidden md:hidden"></div>
<div class="lf-telegram-container">
    <span class="lf-tg-tooltip">Telegram Support</span>
    <a href="https://t.me/LeverageFactorySupport" target="_blank" class="lf-telegram-float" aria-label="Join our Telegram">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21.43 3.01L2.25 10.4c-1.31.52-1.3 1.26-.24 1.58l4.92 1.54 1.41 4.33c.17.47.09.65.58.65.38 0 .54-.17.76-.38l2.21-2.15 4.6 3.39c.85.47 1.45.23 1.66-.79l3.02-14.23c.31-1.24-.47-1.8-1.28-1.43z" fill="white"/>
        </svg>
        <span class="lf-tg-pulse"></span>
    </a>
</div>

<style>
    .lf-telegram-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        align-items: center;
        z-index: 9999;
    }

    .lf-telegram-float {
        width: 60px;
        height: 60px;
        background: linear-gradient(180deg, #00a6fb 0%, #22E0E8 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(34, 224, 232, 0.4);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* Tooltip Styling */
    .lf-tg-tooltip {
        position: absolute;
        right: 80px;
        background: #063142;
        color: #22E0E8;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transform: translateX(10px);
        transition: all 0.3s ease;
        border: 1px solid rgba(34, 224, 232, 0.2);
    }

    .lf-telegram-container:hover .lf-tg-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    .lf-telegram-float:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 30px rgba(34, 224, 232, 0.6);
    }

    /* Pulse animation logic */
    .lf-tg-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        background: #22E0E8;
        border-radius: 50%;
        z-index: -1;
        opacity: 0.7;
        animation: lf-tg-ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    @keyframes lf-tg-ping {
        75%, 100% {
            transform: scale(1.6);
            opacity: 0;
        }
    }

    @media (max-width: 768px) {
        .lf-telegram-container { bottom: 20px; right: 20px; }
        .lf-telegram-float { width: 50px; height: 50px; }
        .lf-tg-tooltip { display: none; } /* Hide tooltip on mobile to save space */
    }
</style>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isHidden = sidebar.classList.contains('-translate-x-full');

    if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}
</script>