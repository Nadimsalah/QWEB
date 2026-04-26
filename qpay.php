<?php
require_once 'conn.php';

// Check Auth
$userId = $_COOKIE['qoon_user_id'] ?? null;
if (!$userId) {
    header("Location: index.php?auth_required=1");
    exit;
}

// Fetch User Data
$userData = [];
$res = $con->query("SELECT * FROM Users WHERE UserID = '$userId'");
if($res && $res->num_rows > 0) {
    $userData = $res->fetch_assoc();
} else {
    die("User not found.");
}

$uName = $userData['name'] ?: 'User';
$uPhoto = $userData['UserPhoto'] ?: '';
if(!$uPhoto || $uPhoto == 'NONE' || $uPhoto == '0') {
    $uPhoto = "https://ui-avatars.com/api/?name=".urlencode($uName)."&background=random&color=fff";
} else if (strpos($uPhoto, 'http') === false) {
    $uPhoto = "photo/" . ltrim($uPhoto, '/');
}
$uBalance = floatval($userData['Balance'] ?? 0);

// Unified History (Transactions + Orders)
$history = [];

// 1. Transactions
$res = $con->query("SELECT *, 'tx' as type FROM UserTransaction WHERE UserID = '$userId' ORDER BY UserTransactionID DESC LIMIT 50");
if($res) {
    while($row = $res->fetch_assoc()) {
        $row['sort_date'] = $row['CreatedAt'] ?? '2024-01-01';
        $history[] = $row;
    }
}

// 2. Orders (to show COD activities - Only Delivered/Done)
$res = $con->query("SELECT *, 'order' as type FROM Orders WHERE UserID = '$userId' AND OrderState IN ('Done', 'FINISH', 'Rated') ORDER BY OrderID DESC LIMIT 50");
if($res) {
    while($row = $res->fetch_assoc()) {
        $row['sort_date'] = $row['CreatedAtOrders'] ?? '2024-01-01';
        $history[] = $row;
    }
}

// Sort by date desc
usort($history, function($a, $b) {
    return strtotime($b['sort_date']) - strtotime($a['sort_date']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QOON Pay | Wallet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-glow: #2cb5e8;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        .aurora { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
        .blob { position: absolute; width: 60vw; height: 60vh; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); filter: blur(100px); opacity: 0.15; animation: move 15s infinite alternate; }
        @keyframes move { from { transform: translate(-10%, -10%); } to { transform: translate(10%, 10%); } }

        <?php if(isset($_GET['iframe'])): ?>
        header { display: none !important; }
        .back-nav { display: none !important; }
        .container { margin: 0 !important; padding: 16px !important; width: 100% !important; max-width: 100% !important; }
        body { background: transparent !important; min-height: auto !important; }
        .aurora { display: none !important; }
        <?php else: ?>
        header { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid var(--glass-border); background: rgba(5,5,5,0.7); }
        .container { max-width: 600px; margin: 20px auto; padding: 0 16px 100px; }
        .back-nav { display:flex; align-items:center; gap:16px; margin-bottom:30px; }
        <?php endif; ?>
        .logo { height: 24px; }
        .back-btn { width:44px; height:44px; border-radius:50%; background:var(--glass-bg); border:1px solid var(--glass-border); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; backdrop-filter:blur(10px); transition:all 0.3s; }
        .back-btn:hover { transform: translateX(-4px); background: rgba(255,255,255,0.1); }

        /* Modern Bank Card (Same as Profile) */
        .wallet-card {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .wallet-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 100% 0%, rgba(44, 181, 232, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 0% 100%, rgba(155, 45, 241, 0.1) 0%, transparent 50%);
            opacity: 0.5;
        }
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2; }
        .card-chip { 
            width: 50px; height: 38px; 
            background: linear-gradient(135deg, #d4af37 0%, #f9e29c 50%, #b8860b 100%); 
            border-radius: 8px; position: relative; overflow: hidden;
        }
        .card-chip::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(90deg, transparent, transparent 5px, rgba(0,0,0,0.1) 5px, rgba(0,0,0,0.1) 10px); }
        .contactless { font-size: 24px; color: rgba(255,255,255,0.3); transform: rotate(90deg); }
        .card-balance-section { position: relative; z-index: 2; margin-top: 20px; }
        .card-balance-section .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 2px; margin-bottom: 8px; display: block; }
        .card-balance-section .amount { font-size: 38px; font-weight: 800; color: #fff; display: flex; align-items: baseline; gap: 8px; }
        .card-balance-section .currency { font-size: 14px; font-weight: 500; color: var(--text-muted); }
        .card-bottom { display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 2; }
        .card-holder-name { font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.9); }

        /* Liquid Glass Action Buttons */
        .actions-group { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 35px; }
        .glass-action-main {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 20px 10px;
            border-radius: 20px;
            text-align: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        .glass-action-main:hover { background: rgba(255,255,255,0.1); transform: translateY(-4px); border-color: rgba(255,255,255,0.2); }
        .glass-action-main i { font-size: 20px; color: var(--accent-glow); margin-bottom: 10px; display: block; }
        .glass-action-main span { font-size: 12px; font-weight: 700; white-space: nowrap; }

        /* Transactions */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { font-size: 18px; font-weight: 800; }
        .section-header .see-all { font-size: 13px; color: var(--accent-glow); text-decoration: none; font-weight: 600; }

        .transaction-list { display: flex; flex-direction: column; gap: 10px; }
        .transaction-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }
        .transaction-card:hover { transform: translateX(4px); background: rgba(255,255,255,0.08); }
        .tx-icon {
            width: 44px; height: 44px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; font-size: 18px;
            background: rgba(255,255,255,0.05);
        }
        .tx-icon.income { color: #34c759; background: rgba(52, 199, 89, 0.1); }
        .tx-icon.expense { color: #ff3b30; background: rgba(255, 59, 48, 0.1); }
        
        .tx-info { flex: 1; }
        .tx-title { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
        .tx-date { font-size: 12px; color: var(--text-muted); }
        
        .tx-amount { font-size: 15px; font-weight: 800; }
        .tx-amount.income { color: #34c759; }
        .tx-amount.expense { color: #fff; }

        @media (max-width: 600px) {
            .card-balance-section .amount { font-size: 32px; }
            .glass-action-main { padding: 15px 5px; }
            .glass-action-main span { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="aurora"><div class="blob"></div></div>

    <header>
        <a href="index.php"><img src="logo_qoon_white.png" alt="QOON" class="logo"></a>
        <a href="user-profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
            <img src="<?= htmlspecialchars($uPhoto) ?>" style="width:32px; height:32px; border-radius:50%; border:1px solid #fff;">
        </a>
    </header>

    <div class="container">
        <div class="back-nav">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:48px; filter:brightness(0) invert(1);">
        </div>

        <!-- Hyper-Modern Wallet Card -->
        <div class="wallet-card">
            <div class="card-top">
                <div class="card-chip"></div>
                <div class="contactless"><i class="fa-solid fa-wifi"></i></div>
            </div>
            
            <div class="card-balance-section">
                <span class="label">Balance Available</span>
                <div class="amount">
                    <?= number_format($uBalance, 2) ?>
                    <span class="currency">MAD</span>
                </div>
            </div>

            <div class="card-bottom">
                <div class="card-holder-name"><?= htmlspecialchars($uName) ?></div>
                <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:22px; filter:brightness(0) invert(1); opacity:0.8;">
            </div>
        </div>

        <!-- Actions -->
        <div class="actions-group">
            <div class="glass-action-main" onclick="window.location.href='topup.php'">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Top up</span>
            </div>
            <div class="glass-action-main" onclick="window.location.href='send.php'">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Send</span>
            </div>
            <div class="glass-action-main" onclick="showMyQR()">
                <i class="fa-solid fa-qrcode"></i>
                <span>QR Code</span>
            </div>
        </div>

        <!-- Transactions -->
        <div class="section-header">
            <h2>Recent Transactions</h2>
            <a href="#" class="see-all">History</a>
        </div>

        <div class="transaction-list">
            <?php if(empty($history)): ?>
                <div style="text-align:center; padding:40px; color:var(--text-muted);">
                    <i class="fa-solid fa-receipt" style="font-size:32px; opacity:0.2; margin-bottom:10px; display:block;"></i>
                    <p>No activity yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($history as $item): 
                    $isOrder = ($item['type'] == 'order');
                    if($item['type'] == 'tx'):
                        $isIncome = (floatval($item['TransactionValue'] ?? 0) > 0);
                        $icon = $isIncome ? 'fa-arrow-down-long' : 'fa-arrow-up-long';
                        $class = $isIncome ? 'income' : 'expense';
                        $title = $item['DistnationName'] ?: ($isIncome ? 'Top-up' : 'Payment');
                        if($title == 'Jibler') $title = 'QOON';
                        $val = floatval($item['TransactionValue'] ?? 0);
                        $date = date('M j, H:i', strtotime($item['sort_date']));
                        $sub = "";
                        $link = "#";
                    else:
                        // Order type
                        $isIncome = false;
                        $icon = 'fa-shopping-bag';
                        $class = 'order-color'; 
                        $title = $item['DestinationName'] ?: 'Order #'.$item['OrderID'];
                        $val = floatval($item['OrderPriceFromShop'] ?? 0) + floatval($item['OrderPrice'] ?? 0);
                        $date = date('M j, H:i', strtotime($item['sort_date']));
                        $method = $item['Method'] ?: 'CASH';
                        $sub = ($method == 'CASH') ? '<span style="color:#ffcc00; font-size:10px; font-weight:700;">COD (Cash)</span>' : '<span style="color:var(--accent-glow); font-size:10px; font-weight:700;">Wallet</span>';
                        $link = "track_order.php?orderId=".$item['OrderID']."&tot=".$val;
                    endif;
                ?>
                    <a href="<?= $link ?>" class="transaction-card" style="text-decoration:none; color:inherit; cursor:<?= ($isOrder?'pointer':'default') ?>;">
                        <div class="tx-icon <?= ($item['type']=='tx')?$class:'order-icon' ?>"><i class="fa-solid <?= $icon ?>"></i></div>
                        <div class="tx-info">
                            <div class="tx-title"><?= htmlspecialchars($title) ?></div>
                            <div class="tx-date"><?= $date ?> • <?= $sub ?></div>
                        </div>
                        <div class="tx-amount <?= ($item['type']=='tx')?$class:'' ?>">
                            <?= ($isIncome ? '+' : '') . number_format($val, 2) ?>
                        </div>
                        <?php if($isOrder): ?>
                            <i class="fa-solid fa-chevron-right" style="font-size:12px; opacity:0.3; margin-left:10px;"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        // --- MY QR MODAL ---
        function showMyQR() {
            const userId = "<?= $userId ?>";
            const userName = "<?= addslashes($uName) ?>";
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(userId)}&bgcolor=ffffff&color=000&margin=10`;

            const modal = document.createElement('div');
            modal.id = 'qrModal';
            modal.className = 'qr-overlay';
            modal.innerHTML = `
                <div class="glass-modal qr-card">
                    <button class="close-qr" onclick="this.closest('.qr-overlay').remove()"><i class="fa-solid fa-xmark"></i></button>
                    <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:35px; filter:brightness(0) invert(1); margin-bottom:10px;">
                    <p>Share your code to receive money instantly</p>
                    
                    <div class="qr-frame">
                        <img src="${qrUrl}" alt="QR Code">
                        <div class="qr-corner top-left"></div>
                        <div class="qr-corner top-right"></div>
                        <div class="qr-corner bottom-left"></div>
                        <div class="qr-corner bottom-right"></div>
                    </div>

                    <div class="user-badge-qr">
                        <div class="u-name-qr">${userName}</div>
                        <div class="u-phone-qr">ID: ${userId}</div>
                    </div>

                    <button class="share-qr-btn" onclick="shareQR('${userName}', '${userId}')">
                        <i class="fa-solid fa-share-nodes"></i> Share My Code
                    </button>
                </div>
                <style>
                    .qr-overlay { position:fixed; inset:0; z-index:10001; background:rgba(0,0,0,0.8); backdrop-filter:blur(15px); display:flex; align-items:center; justify-content:center; animation: fadeIn 0.3s forwards; }
                    .qr-card { width:90%; max-width:380px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); border-radius:35px; padding:40px 30px; text-align:center; position:relative; box-shadow:0 40px 80px rgba(0,0,0,0.6); animation: slideUpModal 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
                    @keyframes slideUpModal { from { transform:translateY(50px); opacity:0; } to { transform:translateY(0); opacity:1; } }
                    .close-qr { position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.05); border:none; width:36px; height:36px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; }
                    .qr-card p { font-size:14px; color:rgba(255,255,255,0.6); margin-bottom:30px; }
                    .qr-frame { width:220px; height:220px; padding:15px; background:#fff; border-radius:24px; margin:0 auto 25px; position:relative; }
                    .qr-frame img { width:100%; height:100%; object-fit:contain; border-radius:10px; }
                    .qr-corner { position:absolute; width:40px; height:40px; border:4px solid var(--accent-glow); }
                    .qr-corner.top-left { top:-10px; left:-10px; border-right:none; border-bottom:none; border-radius:20px 0 0 0; }
                    .qr-corner.top-right { top:-10px; right:-10px; border-left:none; border-bottom:none; border-radius:0 20px 0 0; }
                    .qr-corner.bottom-left { bottom:-10px; left:-10px; border-right:none; border-top:none; border-radius:0 0 0 20px; }
                    .qr-corner.bottom-right { bottom:-10px; right:-10px; border-left:none; border-top:none; border-radius:0 0 20px 0 ; }
                    .user-badge-qr { margin-bottom:30px; }
                    .u-name-qr { font-size:18px; font-weight:700; }
                    .u-phone-qr { font-size:13px; color:rgba(255,255,255,0.5); font-family:monospace; }
                    .share-qr-btn { width:100%; background:linear-gradient(135deg, #2cb5e8 0%, #1e88e5 100%); color:#fff; border:none; padding:16px; border-radius:18px; font-size:15px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:10px; cursor:pointer; transition:all 0.3s; }
                    .share-qr-btn:hover { transform:scale(1.02); box-shadow:0 15px 30px rgba(44, 181, 232, 0.3); }
                    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
                </style>
            `;
            document.body.appendChild(modal);
        }

        window.shareQR = async (name, id) => {
            const btn = document.querySelector('.share-qr-btn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Generating image...';
            btn.disabled = true;

            try {
                const qrCard = document.querySelector('.qr-card');
                const closeBtn = qrCard.querySelector('.close-qr');
                const shareBtn = qrCard.querySelector('.share-qr-btn');
                closeBtn.style.visibility = 'hidden';
                shareBtn.style.visibility = 'hidden';

                const canvas = await html2canvas(qrCard, { backgroundColor: '#111', scale: 2, useCORS: true });
                closeBtn.style.visibility = 'visible';
                shareBtn.style.visibility = 'visible';

                canvas.toBlob(async (blob) => {
                    const file = new File([blob], `QOON_Pay_${id}.png`, { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({ files: [file], title: 'My QOON Pay Code' });
                    } else {
                        const link = document.createElement('a');
                        link.download = `QOON_Pay_${id}.png`;
                        link.href = URL.createObjectURL(blob);
                        link.click();
                    }
                    btn.innerHTML = originalHtml; btn.disabled = false;
                });
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalHtml; btn.disabled = false;
            }
        };

        // --- Auto-open view logic ---
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('view') === 'qr') {
                if (typeof showMyQR === 'function') showMyQR();
            }
        });
    </script>
</body>
</html>
