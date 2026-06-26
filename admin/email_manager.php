<?php
require_once(__DIR__ . '/includes/db_connect.php');
require_once(__DIR__ . '/includes/admin_auth.php');

// Pre-fill check if user_id is passed
$prefill_recipient = '';
if (isset($_GET['user_id'])) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_GET['user_id']]);
    $prefill_recipient = $stmt->fetchColumn() ?: '';
}

// Fetch all users list for the dropdown selection
$users_list = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Communications Protocol | Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap');
        body { font-family: 'Space+Grotesk', sans-serif; background: #F8FAFC; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        .console-scrollbar::-webkit-scrollbar { width: 4px; }
        .console-scrollbar::-webkit-scrollbar-thumb { background: #00A6FB; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto p-8 animate__animated animate__fadeIn custom-scrollbar">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-[0.5em] mb-2">Platform_Communications</p>
                <h1 class="text-4xl font-black uppercase italic tracking-tighter text-gray-900">
                    Email <span class="text-[#00A6FB]">Manager</span>_
                </h1>
                <p class="text-sm text-gray-500 mt-2">Send announcements, support updates, and custom alerts to member nodes.</p>
            </div>
        </header>

        <!-- Tabs Navigation -->
        <div class="flex border-b border-gray-200 mb-8">
            <button onclick="switchTab('single')" id="tab-single-btn" class="py-4 px-6 font-bold text-sm uppercase tracking-widest border-b-2 border-[#00A6FB] text-[#00A6FB] outline-none transition duration-150">
                Single Node Email
            </button>
            <button onclick="switchTab('bulk')" id="tab-bulk-btn" class="py-4 px-6 font-bold text-sm uppercase tracking-widest border-b-2 border-transparent text-gray-400 hover:text-gray-600 outline-none transition duration-150">
                Global Broadcast Protocol
            </button>
        </div>

        <!-- TAB 1: Single Node Email -->
        <section id="tab-single" class="tab-content animate__animated animate__fadeIn">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Column -->
                <div class="lg:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                    <form id="single-email-form" onsubmit="sendSingleEmail(event)" class="space-y-6">
                        <input type="hidden" name="action" value="send_single">
                        
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Recipient User (Name & Email)</label>
                            <select name="recipient" required
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-medium cursor-pointer">
                                <option value="" disabled <?php echo empty($prefill_recipient) ? 'selected' : ''; ?>>Select user node...</option>
                                <?php foreach ($users_list as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['email']); ?>" <?php echo ($prefill_recipient === $u['email']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['email']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Email Subject</label>
                            <input type="text" name="subject" required placeholder="Enter communication subject..."
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-medium">
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Message Body (Supports HTML)</label>
                            <textarea name="message" required rows="10" placeholder="Type your message here..."
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-medium custom-scrollbar"></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="wrap_template" value="1" checked id="single-wrap"
                                class="w-4 h-4 text-[#00A6FB] border-gray-300 rounded focus:ring-[#00A6FB]">
                            <label for="single-wrap" class="text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer">Wrap in Leverage Factory Premium Email Template</label>
                        </div>

                        <button type="submit" id="single-submit-btn" class="w-full bg-black hover:bg-[#00A6FB] text-white font-black py-4 rounded-xl uppercase tracking-widest transition shadow-lg flex items-center justify-center gap-2">
                            <span>Execute Dispatch</span>
                        </button>
                    </form>
                </div>

                <!-- Info Sidebar -->
                <div class="space-y-6">
                    <div class="bg-gray-900 text-white rounded-3xl p-6 shadow-md border border-gray-800">
                        <h4 class="text-xs font-black text-[#00A6FB] uppercase tracking-widest mb-4">Transmission Info</h4>
                        <ul class="space-y-4 text-xs font-medium text-gray-300">
                            <li class="flex justify-between border-b border-gray-800 pb-2">
                                <span>Sender Node:</span>
                                <span class="font-bold text-white">support@leveragefactory.ai</span>
                            </li>
                            <li class="flex justify-between border-b border-gray-800 pb-2">
                                <span>Protocol type:</span>
                                <span class="font-bold text-white">SMTP Secured (SSL)</span>
                            </li>
                            <li class="flex justify-between">
                                <span>Port:</span>
                                <span class="font-bold text-white">465</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Styling Guidelines</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            When wrapping your message in the brand template, it will include the company logo in the header and styling in the footer. You can use standard HTML tags inside the body textarea like <code>&lt;strong&gt;</code>, <code>&lt;p&gt;</code>, or <code>&lt;a href="..."&gt;</code> for links.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- TAB 2: Bulk Broadcast Protocol -->
        <section id="tab-bulk" class="tab-content hidden animate__animated animate__fadeIn">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Column -->
                <div id="bulk-form-container" class="lg:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                    <form id="bulk-email-form" onsubmit="startBulkCampaign(event)" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Target Segment</label>
                                <select name="segment" id="bulk-segment" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-bold uppercase tracking-wider cursor-pointer">
                                    <option value="all">All Registered Users</option>
                                    <option value="active_investors">Active Investors Only</option>
                                    <option value="non_locked">Non-Locked Members</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Batch Sending Size</label>
                                <select name="limit" id="bulk-limit" disabled class="w-full bg-gray-100 border border-gray-100 rounded-xl px-4 py-3 text-sm outline-none font-bold uppercase tracking-wider cursor-not-allowed opacity-75">
                                    <option value="10" selected>10 emails / batch</option>
                                    <option value="20">20 emails / batch</option>
                                    <option value="50">50 emails / batch</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Broadcast Subject</label>
                            <input type="text" name="subject" required id="bulk-subject" placeholder="Enter broadcast announcement subject..."
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-medium">
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">Broadcast Message (HTML Supported)</label>
                            <textarea name="message" required id="bulk-message" rows="10" placeholder="Type your broadcast communication here..."
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-[#00A6FB] focus:ring-1 focus:ring-[#00A6FB] outline-none font-medium custom-scrollbar"></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="wrap_template" value="1" checked id="bulk-wrap"
                                class="w-4 h-4 text-[#00A6FB] border-gray-300 rounded focus:ring-[#00A6FB]">
                            <label for="bulk-wrap" class="text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer">Wrap in Leverage Factory Premium Email Template</label>
                        </div>

                        <button type="submit" class="w-full bg-black hover:bg-[#00A6FB] text-white font-black py-4 rounded-xl uppercase tracking-widest transition shadow-lg flex items-center justify-center gap-2">
                            <span>Initialize Broadcast Campaign</span>
                        </button>
                    </form>
                </div>

                <!-- Live Progress Dashboard (Hidden initially) -->
                <div id="bulk-progress-container" class="lg:col-span-2 hidden bg-white rounded-3xl p-8 border border-gray-100 shadow-sm space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <div>
                            <p class="text-[10px] font-black text-[#00A6FB] uppercase tracking-widest">Active_Broadcast_Status</p>
                            <h3 class="text-xl font-black uppercase italic tracking-tighter" id="progress-heading">Campaign Initializing_</h3>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="pauseCampaign()" id="btn-pause" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition">Pause</button>
                            <button onclick="resumeCampaign()" id="btn-resume" class="hidden bg-green-500 hover:bg-green-600 text-white font-bold px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition">Resume</button>
                            <button onclick="abortCampaign()" id="btn-abort" class="bg-red-500 hover:bg-red-600 text-white font-bold px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition">Abort</button>
                        </div>
                    </div>

                    <!-- Progress Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 text-center">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Recipients</p>
                            <p class="text-2xl font-black text-gray-900 mt-1" id="stat-total">0</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-2xl border border-green-100 text-center">
                            <p class="text-[9px] font-black text-green-600 uppercase tracking-widest">Success Deliveries</p>
                            <p class="text-2xl font-black text-green-700 mt-1" id="stat-success">0</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-2xl border border-red-100 text-center">
                            <p class="text-[9px] font-black text-red-600 uppercase tracking-widest">Failure Rate</p>
                            <p class="text-2xl font-black text-red-700 mt-1" id="stat-failure">0</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <span>Progress</span>
                            <span id="progress-percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-4 rounded-full overflow-hidden">
                            <div id="progress-bar-fill" class="bg-[#00A6FB] h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Live Debug Console -->
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Live_Transmission_Console</p>
                        <div id="live-console" class="w-full h-64 bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-2xl overflow-y-auto console-scrollbar space-y-1">
                            <div>[SYSTEM] Console initialized. Ready for campaign broadcast protocol...</div>
                        </div>
                    </div>

                    <button onclick="resetCampaignUI()" id="btn-campaign-reset" class="hidden w-full bg-black hover:bg-[#00A6FB] text-white font-black py-4 rounded-xl uppercase tracking-widest transition shadow-lg">
                        Return to Campaign setup
                    </button>
                </div>

                <!-- Info Sidebar -->
                <div class="space-y-6">
                    <div class="bg-gray-900 text-white rounded-3xl p-6 shadow-md border border-gray-800">
                        <h4 class="text-xs font-black text-[#00A6FB] uppercase tracking-widest mb-4">Transmission Protocol</h4>
                        <p class="text-xs text-gray-400 leading-relaxed mb-4">
                            Bulk operations partition sending loads sequentially to satisfy hosting server SMTP traffic constraints.
                        </p>
                        <div class="border-t border-gray-800 pt-4 text-xs space-y-2 text-gray-300">
                            <div class="flex justify-between">
                                <span>Batch Throttle Sleep:</span>
                                <span class="font-bold text-[#00A6FB]">2 seconds</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function switchTab(tab) {
            const tabs = ['single', 'bulk'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}-btn`);
                const content = document.getElementById(`tab-${t}`);
                if (t === tab) {
                    btn.classList.add('border-[#00A6FB]', 'text-[#00A6FB]');
                    btn.classList.remove('border-transparent', 'text-gray-400');
                    content.classList.remove('hidden');
                } else {
                    btn.classList.remove('border-[#00A6FB]', 'text-[#00A6FB]');
                    btn.classList.add('border-transparent', 'text-gray-400');
                    content.classList.add('hidden');
                }
            });
        }

        /* SINGLE EMAIL SENDING */
        function sendSingleEmail(event) {
            event.preventDefault();
            const form = document.getElementById('single-email-form');
            const submitBtn = document.getElementById('single-submit-btn');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span>Transmitting Node Message...</span>`;
            submitBtn.classList.add('opacity-50');

            const formData = new FormData(form);

            fetch('send_email_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    form.reset();
                } else {
                    alert("Dispatch Error: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Critical network error during email dispatch.");
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('opacity-50');
            });
        }

        /* BULK CAMPAIGN STATES */
        let campaignActive = false;
        let campaignPaused = false;
        let totalCount = 0;
        let offset = 0;
        let limit = 20;
        let successCount = 0;
        let failureCount = 0;
        let segment = '';
        let subject = '';
        let message = '';
        let wrapTemplate = '0';

        function resetCampaignUI() {
            document.getElementById('bulk-progress-container').classList.add('hidden');
            document.getElementById('bulk-form-container').classList.remove('hidden');
            document.getElementById('btn-campaign-reset').classList.add('hidden');
            document.getElementById('btn-pause').classList.remove('hidden');
            document.getElementById('btn-abort').classList.remove('hidden');
            
            // Reset state
            campaignActive = false;
            campaignPaused = false;
            totalCount = 0;
            offset = 0;
            successCount = 0;
            failureCount = 0;
        }

        function startBulkCampaign(event) {
            event.preventDefault();
            
            segment = document.getElementById('bulk-segment').value;
            limit = parseInt(document.getElementById('bulk-limit').value);
            subject = document.getElementById('bulk-subject').value;
            message = document.getElementById('bulk-message').value;
            wrapTemplate = document.getElementById('bulk-wrap').checked ? '1' : '0';

            // Swap forms with dashboard
            document.getElementById('bulk-form-container').classList.add('hidden');
            document.getElementById('bulk-progress-container').classList.remove('hidden');

            const consoleElement = document.getElementById('live-console');
            consoleElement.innerHTML = `<div>[SYSTEM] Handshaking database to count active targets...</div>`;

            // 1. Fetch audience count
            const formData = new FormData();
            formData.append('action', 'get_bulk_count');
            formData.append('segment', segment);

            fetch('send_email_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    totalCount = data.total_count;
                    document.getElementById('stat-total').innerText = totalCount;
                    document.getElementById('stat-success').innerText = '0';
                    document.getElementById('stat-failure').innerText = '0';
                    document.getElementById('progress-bar-fill').style.width = '0%';
                    document.getElementById('progress-percent').innerText = '0%';
                    document.getElementById('progress-heading').innerText = 'Broadcast Active_';

                    logConsole(`Audience count fetched successfully. Total targets: ${totalCount}`);
                    
                    if (totalCount === 0) {
                        logConsole(`[SYSTEM] Target audience list is empty. Aborting campaign.`, 'red');
                        document.getElementById('progress-heading').innerText = 'Empty Target Group';
                        document.getElementById('btn-campaign-reset').classList.remove('hidden');
                        document.getElementById('btn-pause').classList.add('hidden');
                        document.getElementById('btn-abort').classList.add('hidden');
                        return;
                    }

                    campaignActive = true;
                    campaignPaused = false;
                    offset = 0;
                    successCount = 0;
                    failureCount = 0;

                    // Trigger first batch processing
                    processBatch();
                } else {
                    logConsole(`[ERROR] Audience counting handshake failed: ${data.message}`, 'red');
                }
            })
            .catch(err => {
                logConsole(`[CRITICAL] Audience counting connection error.`, 'red');
            });
        }

        function processBatch() {
            if (!campaignActive) return;
            if (campaignPaused) {
                logConsole(`[SYSTEM] Campaign paused. Awaiting protocol resume...`);
                return;
            }

            logConsole(`Dispatching batch at offset ${offset} (Limit: ${limit})...`);

            const formData = new FormData();
            formData.append('action', 'send_bulk_batch');
            formData.append('segment', segment);
            formData.append('offset', offset);
            formData.append('limit', limit);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('wrap_template', wrapTemplate);

            fetch('send_email_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.completed || data.batch_results.length === 0) {
                        logConsole(`[SYSTEM] Broadcast Campaign complete. Total successful dispatches: ${successCount}`, 'yellow');
                        document.getElementById('progress-heading').innerText = 'Broadcast Complete_';
                        document.getElementById('progress-bar-fill').style.width = '100%';
                        document.getElementById('progress-percent').innerText = '100%';
                        
                        document.getElementById('btn-campaign-reset').classList.remove('hidden');
                        document.getElementById('btn-pause').classList.add('hidden');
                        document.getElementById('btn-abort').classList.add('hidden');
                        campaignActive = false;
                        return;
                    }

                    // Process results of this batch
                    data.batch_results.forEach(res => {
                        if (res.status === 'success') {
                            successCount++;
                            logConsole(`SUCCESS → Delivery confirmed for ${res.email}`);
                        } else {
                            failureCount++;
                            logConsole(`FAILED → Delivery failed for ${res.email}: ${res.error}`, 'red');
                        }
                    });

                    // Update stats & progress bar
                    document.getElementById('stat-success').innerText = successCount;
                    document.getElementById('stat-failure').innerText = failureCount;
                    
                    offset = data.next_offset;
                    const percent = Math.min(Math.round((offset / totalCount) * 100), 99);
                    document.getElementById('progress-bar-fill').style.width = `${percent}%`;
                    document.getElementById('progress-percent').innerText = `${percent}%`;

                    // Respect SMTP limits with a small sleep delay
                    logConsole(`Cooling down connection node. Sleeping for 2 seconds...`);
                    setTimeout(() => {
                        processBatch();
                    }, 2000);
                } else {
                    logConsole(`[ERROR] Batch processing failure: ${data.message}. Stopping campaign.`, 'red');
                    campaignActive = false;
                }
            })
            .catch(err => {
                logConsole(`[CRITICAL] Batch request error. Connection reset.`, 'red');
                campaignActive = false;
            });
        }

        function pauseCampaign() {
            if (!campaignActive) return;
            campaignPaused = true;
            document.getElementById('btn-pause').classList.add('hidden');
            document.getElementById('btn-resume').classList.remove('hidden');
            document.getElementById('progress-heading').innerText = 'Broadcast Paused_';
            logConsole(`[SYSTEM] Campaign paused.`, 'yellow');
        }

        function resumeCampaign() {
            if (!campaignActive) return;
            campaignPaused = false;
            document.getElementById('btn-resume').classList.add('hidden');
            document.getElementById('btn-pause').classList.remove('hidden');
            document.getElementById('progress-heading').innerText = 'Broadcast Active_';
            logConsole(`[SYSTEM] Campaign resumed.`, 'yellow');
            processBatch();
        }

        function abortCampaign() {
            if (!confirm("Are you sure you want to stop this campaign immediately? Sent emails cannot be recalled.")) return;
            campaignActive = false;
            logConsole(`[SYSTEM] Campaign aborted by administrator.`, 'red');
            document.getElementById('progress-heading').innerText = 'Broadcast Aborted_';
            document.getElementById('btn-campaign-reset').classList.remove('hidden');
            document.getElementById('btn-pause').classList.add('hidden');
            document.getElementById('btn-abort').classList.add('hidden');
            document.getElementById('btn-resume').classList.add('hidden');
        }

        function logConsole(text, color = 'green') {
            const consoleElement = document.getElementById('live-console');
            const time = new Date().toLocaleTimeString();
            let colorClass = 'text-emerald-400';
            if (color === 'red') colorClass = 'text-red-500';
            if (color === 'yellow') colorClass = 'text-amber-400';
            
            consoleElement.innerHTML += `<div class="${colorClass}">[${time}] ${text}</div>`;
            consoleElement.scrollTop = consoleElement.scrollHeight;
        }
    </script>
</body>
</html>
