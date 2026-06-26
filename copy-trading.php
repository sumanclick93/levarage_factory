<?php
require_once('includes/db_connect.php');
session_start();

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active schemes from database
$stmt = $pdo->query("SELECT * FROM investment_schemes WHERE is_active = 1 ORDER BY total_return_percent DESC");
$schemes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copy Trading - Leverage Factory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Including your provided custom CSS */
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; color: #111827; }
        .container { max-width: 1300px; margin: auto; padding: 24px; }
        h1 { font-size: 28px; margin-bottom: 4px; }
        .subtitle { color: #6b7280; margin-bottom: 24px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .tabs { display: flex; gap: 24px; border-bottom: 1px solid #e5e7eb; }
        .tabs button { background: none; border: none; padding: 10px 0; font-size: 14px; color: #6b7280; cursor: pointer; position: relative; }
        .tabs button.active { color: #111827; font-weight: 600; }
        .tabs button.active::after { content: ""; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: #00A6FB; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); border-color: #00A6FB; }
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        .profile { display: flex; gap: 12px; align-items: center; }
        .profile img { width: 42px; height: 42px; border-radius: 50%; background: #eee; }
        .name { font-weight: 600; font-size: 15px; }
        .followers { font-size: 12px; color: #6b7280; }
        .copy-btn { background: linear-gradient(to right, #00A6FB, #0068A3); border: none; color: #fff; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .card-body { display: grid; grid-template-columns: 1.2fr 2fr; gap: 16px; margin-top: 14px; align-items: center; }
        .label { font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: bold; }
        .roi { font-size: 22px; font-weight: bold; color: #16a34a; margin: 2px 0; }
        .value { font-weight: 600; font-size: 14px; }
        .sharpe { text-align: right; font-weight: 600; color: #16a34a; font-size: 14px; }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 flex flex-col md:flex-row min-h-screen">
    <?php include('includes/sidebar.php'); ?>

    <main class="flex-1 overflow-y-auto">
        <div class="container">
            <h1>Copy Trading Marketplace</h1>
            <p class="subtitle">Select a high-performance strategy to copy automatically.</p>

            <div class="top-bar">
                <div class="tabs">
                    <button class="active">All Strategies</button>
                    <button>Spot</button>
                    <button>Futures</button>
                </div>
                <div class="flex gap-4 items-center text-sm">
                    <strong>Sort by:</strong>
                    <select class="bg-transparent border-none font-bold text-[#00A6FB] outline-none">
                        <option>Highest ROI</option>
                        <option>Popularity</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <?php foreach($schemes as $s): ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="profile">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($s['name']); ?>&background=00A6FB&color=fff">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($s['name']); ?></div>
                                <div class="followers"><?php echo rand(50, 200); ?> / 500 Copiers</div>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="checkout.php?scheme_id=<?php echo $s['id']; ?>">
                                <button class="copy-btn">Copy</button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div>
                            <div class="label">Est. ROI (<?php echo $s['duration_days']; ?>D)</div>
                            <div class="roi">+<?php echo number_format($s['total_return_percent'], 2); ?>%</div>

                            <div class="label" style="margin-top:10px;">Min. Investment</div>
                            <div class="value">$<?php echo number_format($s['min_amount'], 0); ?></div>
                        </div>

                        <div>
                            <div class="chart-box" style="height: 60px;">
                                <svg viewBox="0 0 300 100" width="100%" height="100%" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="grad<?php echo $s['id']; ?>" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#22c55e" stop-opacity="0.3"/>
                                            <stop offset="100%" stop-color="#22c55e" stop-opacity="0.01"/>
                                        </linearGradient>
                                    </defs>
                                    <polyline fill="none" stroke="#22c55e" stroke-width="3" points="0,80 50,70 100,75 150,50 200,55 250,20 300,30" />
                                    <polygon fill="url(#grad<?php echo $s['id']; ?>)" points="0,100 0,80 50,70 100,75 150,50 200,55 250,20 300,30 300,100" />
                                </svg>
                            </div>
                            <div class="label" style="text-align:right; margin-top:5px;">Risk Index</div>
                            <div class="sharpe">Low Risk</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>