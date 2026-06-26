<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-white border-r border-gray-200 flex flex-col h-screen sticky top-0 overflow-auto">
    <div class="px-4 py-7 flex items-center gap-2">
       <img src = "../uploads/Logo/logo.jpg" class="rounded-lg" alt="logo"/>
    </div>

    <nav class="flex-1 px-2">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'dashboard.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <span class="ml-4 font-medium">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage_users.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'manage_users.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <span class="ml-4 font-medium">User Management</span>
                </a>
            </li>
            <li>
                <a href="kyc_approval.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'kyc_approval.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h6"/></svg>
                    <span class="ml-4 font-medium">KYC Approvals</span>
                </a>
            </li>
            <li>
                <a href="schemes.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'schemes.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 2v20M17 5l-5-3-5 3M17 19l-5 3-5-3"/><rect x="2" y="7" width="20" height="10" rx="2"/></svg>
                    <span class="ml-4 font-medium">Investment Plans</span>
                </a>
            </li>
            <li>
                <a href="pending_investments.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'pending_investments.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/><rect x="2" y="7" width="20" height="10" rx="2"/></svg>
                    <span class="ml-4 font-medium">Pending Approvals</span>
                </a>
            </li>
            <li>
                <a href="all_investments.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'all_investments.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <span class="ml-4 font-medium">All Investments</span>
                </a>
            </li>
            <li>
                <a href="currency_manager.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'currency_manager.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                    <span class="ml-4 font-medium">Currency Manager</span>
                </a>
            </li>
            <li>
                <a href="withdrawals.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'withdrawals.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="ml-4 font-medium">Withdrawal Manager</span>
                </a>
            </li>
            <li>
                <a href="view_all_withdrawls.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'view_all_withdrawls.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="ml-4 font-medium">Withdrawal History</span>
                </a>
            </li>
            <li>
                <a href="referral_settings.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'referral_settings.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8l1.41-1.41L19 5.17M21 12h-2M19 16l1.41 1.41-1.41 1.42"/></svg>
                    <span class="ml-4 font-medium">Referral Settings</span>
                </a>
            </li>
            <li>
                <a href="manage_ranks.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'manage_ranks.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                        <path d="M4 22h16"></path>
                        <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                        <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                    </svg>
                    <span class="ml-4 font-medium">Rank_Protocol</span>
                </a>
            </li>
            <li>
                <a href="manage_blogs.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'blog.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                    <span class="ml-4 font-medium">Insights</span>
                </a>
            </li>
            <li>
                <a href="admin_assign_investment.php" class="flex items-center p-3 rounded-md <?php echo ($current_page == 'admin_assign_investment.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span class="ml-4 font-medium">Assign Capital</span>
                </a>
            </li>
            <li>
                <a href="notifications.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'notifications.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><path d="m9 8 2 2 4-4"/></svg>
                    <span class="ml-4 font-medium">Global Notifications</span>
                </a>
            </li>
            <li>
                <a href="email_manager.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'email_manager.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span class="ml-4 font-medium">Email Manager</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'reports.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                    <span class="ml-4 font-medium">Platform Reports</span>
                </a>
            </li>
            <li>
                <a href="audit_logs.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'audit_logs.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span class="ml-4 font-medium">Audit Logs</span>
                </a>
            </li>
            <!-- <li>
                <a href="financial_settings.php" class="flex items-center p-3 my-1 rounded-xl transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'financial_settings.php') ? 'bg-black text-white shadow-lg shadow-[#00A6FB]/20' : 'text-gray-500 hover:bg-gray-100 hover:text-[#00A6FB]'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span class="ml-4 font-medium">Financial_Engine</span>
                </a>
            </li> -->
            <li>
                <a href="system_logs.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'system_logs.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                    <span class="ml-4 font-medium">System_Logs</span>
                </a>
            </li>
            <li>
                <a href="manage_subscribers.php" class="flex items-center p-3 my-1 rounded-md <?php echo ($current_page == 'manage_subscribers.php') ? 'bg-[#00A6FB] text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="ml-4 font-medium">Subscriber_Nodes</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="px-2 mb-4">
        <div class="border-t border-gray-200 my-4"></div>
        <a href="logout.php" class="flex items-center p-3 rounded-md text-gray-600 hover:bg-red-50 hover:text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span class="ml-4 font-medium">Logout</span>
        </a>
    </div>
</aside>