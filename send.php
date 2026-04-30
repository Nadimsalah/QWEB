<?php
require_once 'conn.php';

$userId = $_COOKIE['qoon_user_id'] ?? null;
if (!$userId) {
    header("Location: index.php?auth_required=1");
    exit;
}

$userData = [];
$res = $con->query("SELECT * FROM Users WHERE UserID = '$userId'");
if($res) $userData = $res->fetch_assoc();
$uBalance = floatval($userData['Balance'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money | QOON Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- ⚡ Apply theme BEFORE paint to prevent flash -->
    <script>
        (function() {
            var t = localStorage.getItem('qoon_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.add('light-mode');
        })();
    </script>
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
        body { background-color: var(--bg-color); color: var(--text-main); min-height: 100vh; }

        /* Light Mode Overrides */
        html.light-mode { --bg-color: #f8f9fa; --text-main: #0f1115; --text-muted: rgba(0, 0, 0, 0.5); --glass-bg: #ffffff; --glass-border: rgba(0, 0, 0, 0.08); }
        html.light-mode body { background-color: #f8f9fa !important; color: #0f1115 !important; }
        html.light-mode header { background: rgba(255,255,255,0.7) !important; border-bottom-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .back-btn { background: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; color: #0f1115 !important; }
        html.light-mode h1 { color: #0f1115 !important; }
        html.light-mode .type-selector { background: rgba(0,0,0,0.03) !important; }
        html.light-mode .glass-card { background: #ffffff !important; border-color: rgba(0,0,0,0.08) !important; box-shadow: 0 10px 40px rgba(0,0,0,0.04) !important; }
        html.light-mode .main-input-group input { background: rgba(0,0,0,0.03) !important; color: #0f1115 !important; border-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .user-result { background: rgba(0,0,0,0.02) !important; }
        html.light-mode .u-name { color: #0f1115 !important; }
        html.light-mode .confirm-name, html.light-mode .balance-info b { color: #0f1115 !important; }
        html.light-mode header img { filter: none !important; }


        .aurora { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
        .blob { position: absolute; width: 60vw; height: 60vh; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); filter: blur(100px); opacity: 0.15; animation: move 15s infinite alternate; }
        @keyframes move { from { transform: translate(-10%, -10%); } to { transform: translate(10%, 10%); } }

        header { padding: 15px 20px; display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); background: rgba(5,5,5,0.7); border-bottom: 1px solid var(--glass-border); }
        .back-btn { width:40px; height:40px; border-radius:50%; background:var(--glass-bg); border:1px solid var(--glass-border); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; }

        <?php if(isset($_GET['iframe'])): ?>
        /* header { display: none !important; } */
        .container { margin: 0 !important; padding: 20px !important; width: 100% !important; max-width: 100% !important; }
        body { background: transparent !important; }
        .aurora { display: none !important; }
        <?php else: ?>
        .container { max-width: 500px; margin: 30px auto; padding: 0 20px 100px; }
        <?php endif; ?>
        
        .step { display: none; animation: slideUp 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        .step.active { display: block; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        h1 { font-size: 26px; font-weight: 800; margin-bottom: 8px; }
        p.subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 25px; }

        /* Multi-type Selector */
        .type-selector { display:flex; background:rgba(255,255,255,0.03); padding:6px; border-radius:16px; border:1px solid var(--glass-border); margin-bottom:25px; }
        .type-btn { flex:1; padding:12px; border-radius:12px; border:none; background:transparent; color:var(--text-muted); font-weight:700; font-size:14px; cursor:pointer; transition:0.3s; display:flex; align-items:center; justify-content:center; gap:8px; }
        .type-btn.active { background:var(--accent-glow); color:#000; box-shadow:0 8px 15px rgba(44, 181, 232, 0.2); }

        /* Input Cards */
        .glass-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 24px; padding: 25px; margin-bottom: 20px; }
        .input-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .main-input-group { position: relative; }
        .main-input-group input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 16px 20px; border-radius: 16px; color: #fff; font-size: 16px; font-weight: 600; outline: none; transition: 0.3s; }
        .main-input-group input:focus { border-color: var(--accent-glow); background: rgba(255,255,255,0.05); }

        .amount-input { font-size: 32px !important; text-align: center; color: var(--accent-glow) !important; }

        /* Recipient Search Results */
        .search-results { margin-top: 15px; display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto; }
        .user-result { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 14px; background: rgba(255,255,255,0.02); cursor: pointer; transition: 0.2s; }
        .user-result:hover { background: rgba(255,255,255,0.08); }
        .user-result img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .user-result .u-name { font-size: 14px; font-weight: 600; }
        .user-result .u-phone { font-size: 12px; color: var(--text-muted); }

        /* QR Scanner Placeholder */
        .qr-placeholder { aspect-ratio: 1; background: rgba(0,0,0,0.4); border: 2px dashed var(--glass-border); border-radius: 30px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 15px; margin-bottom: 20px; position:relative; overflow:hidden;}
        .qr-line { position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: var(--accent-glow); box-shadow: 0 0 15px var(--accent-glow); animation: scan 3s infinite linear; }
        @keyframes scan { 0% { top: 0%; } 50% { top: 100%; } 100% { top: 0%; } }

        /* Buttons */
        .btn-primary { width: 100%; background: linear-gradient(135deg, #2cb5e8 0%, #1e88e5 100%); color: #fff; padding: 18px; border-radius: 18px; font-size: 16px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 10px 20px rgba(44, 181, 232, 0.2); transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(44, 181, 232, 0.4); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

        .balance-info { text-align: center; margin-bottom: 20px; font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .balance-info b { color: #fff; }

        /* Confirmation Card */
        .confirm-card { text-align: center; }
        .confirm-avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent-glow); margin: 0 auto 15px; display: block; }
        .confirm-name { font-size: 20px; font-weight: 800; margin-bottom: 5px; }
        .confirm-phone { font-size: 14px; color: var(--text-muted); margin-bottom: 25px; }
    </style>
</head>
<body>
    <div class="aurora"><div class="blob"></div></div>

    <header>
        <a href="javascript:void(0)" onclick="window.location.href='qpay.php' + window.location.search" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <img src="qoon_pay_logo.png" style="height:28px; filter:brightness(0) invert(1);">
    </header>

    <div class="container">
        <!-- Step 1: Destination -->
        <div class="step active" id="step1">
            <h1>Send Money</h1>
            <p class="subtitle">Quickly transfer funds to any QOON wallet via phone or QR.</p>

            <div class="type-selector">
                <button class="type-btn active" id="type-phone" onclick="setType('phone')">
                    <i class="fa-solid fa-phone"></i> Phone
                </button>
                <button class="type-btn" id="type-qr" onclick="setType('qr')">
                    <i class="fa-solid fa-qrcode"></i> Scan QR
                </button>
            </div>

            <!-- Phone Section -->
            <div id="section-phone">
                <div class="glass-card">
                    <label class="input-label">Recipient's Details</label>
                    <div class="main-input-group">
                        <input type="text" id="phoneInput" placeholder="Enter name or phone number..." onkeyup="searchUsers(this.value)">
                    </div>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>

            <!-- QR Section -->
            <div id="section-qr" style="display:none;">
                <div class="qr-container">
                    <div id="reader" style="width: 100%; border-radius: 24px; overflow: hidden; background: #000; border: 1px solid var(--glass-border);"></div>
                    <div class="qr-line" id="scanLine"></div>
                    <p style="text-align:center; font-size:12px; color:var(--text-muted); margin-top:15px;">Scanning for recipient's QOON code...</p>
                </div>
            </div>

            <!-- Selected Recipient (Small Badge) -->
            <div id="selectedRecipient" class="glass-card" style="display:none; padding:15px; flex-direction:row; align-items:center; gap:12px;">
                <img src="" id="selAvatar" style="width:40px; height:40px; border-radius:50%;">
                <div style="flex:1;">
                    <div id="selName" style="font-weight:700; font-size:14px;"></div>
                    <div id="selPhone" style="font-size:11px; color:var(--text-muted);"></div>
                </div>
                <button onclick="clearRecipient()" style="background:transparent; border:none; color:#ff3b30; cursor:pointer;"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>

            <!-- Amount Section -->
            <div class="glass-card">
                <label class="input-label">Amount (MAD)</label>
                <div class="main-input-group">
                    <input type="number" id="sendAmount" class="amount-input" placeholder="0.00">
                </div>
            </div>

            <div class="balance-info">Available Balance: <b><?= number_format($uBalance, 2) ?> MAD</b></div>

            <button class="btn-primary" id="btnNext" onclick="goToStep(2)">
                Continue <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- Step 2: Confirmation -->
        <div class="step" id="step2">
            <h1>Confirm Transfer</h1>
            <p class="subtitle">Please review the details below before confirming.</p>

            <div class="glass-card confirm-card">
                <img src="" id="confAvatar" class="confirm-avatar">
                <div class="confirm-name" id="confName">Nadim Tester</div>
                <div class="confirm-phone" id="confPhone">+212 600 000 000</div>

                <div style="font-size:12px; text-transform:uppercase; color:var(--text-muted); font-weight:700; letter-spacing:1px; margin-bottom:5px;">Transfer Amount</div>
                <div id="confAmount" style="font-size:36px; font-weight:800; color:var(--accent-glow); margin-bottom:20px;">100.00 MAD</div>

                <div style="display:flex; justify-content:space-between; padding-top:15px; border-top:1px solid var(--glass-border); font-size:13px;">
                    <span style="color:var(--text-muted);">Platform Fee</span>
                    <span style="font-weight:600;">0.00 MAD</span>
                </div>
            </div>

            <button class="btn-primary" id="btnConfirm" onclick="finishTransfer()">
                Send Now <i class="fa-solid fa-paper-plane"></i>
            </button>
            <button style="width:100%; padding:15px; background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-weight:600;" onclick="goToStep(1)">Edit details</button>
        </div>
    </div>

    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="assets/js/firebase-auth.js"></script>
    <script>
        let selectedUser = null;
        let html5QrCode = null;
        const currentBalance = <?= $uBalance ?>;
        const currentUserID = "<?= $userId ?>";

        function showError(msg) {
            const toast = document.createElement('div');
            toast.className = 'error-toast';
            toast.innerHTML = `
                <i class="fa-solid fa-circle-exclamation"></i>
                <div class="err-msg">
                    <b>No Balance</b>
                    <p>${msg}</p>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('active'), 10);
            setTimeout(() => {
                toast.classList.remove('active');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        async function setType(type) {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('type-' + type).classList.add('active');
            
            if(type === 'phone') {
                document.getElementById('section-phone').style.display = 'block';
                document.getElementById('section-qr').style.display = 'none';
                stopScanner();
            } else {
                document.getElementById('section-phone').style.display = 'none';
                document.getElementById('section-qr').style.display = 'block';
                startScanner();
            }
        }

        function startScanner() {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess);
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Assume decodedText is phone number or specific URL
            console.log("Scanned:", decodedText);
            stopScanner();
            searchUsers(decodedText);
        }

        async function searchUsers(q) {
            if(q.length < 5) { 
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            const formData = new FormData();
            formData.append('PhoneNumber', q);

            const res = await fetch('GetUsersByWritePhone.php', { method: 'POST', body: formData });
            const json = await res.json();

            let html = '';
            if(json.success && json.data) {
                const filtered = json.data.filter(u => u.UserID !== currentUserID);
                
                if(filtered.length === 0) {
                    html = '<p style="text-align:center; font-size:12px; color:var(--text-muted); padding:10px;">No other users found</p>';
                } else {
                    // Auto-select if exact match found from QR
                    if(filtered.length === 1 && (filtered[0].PhoneNumber === q || filtered[0].name === q)) {
                        let u = filtered[0];
                        let photo = u.UserPhoto;
                        if(!photo || photo == 'NONE' || photo == '0') photo = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.name);
                        else if(!photo.startsWith('http')) photo = 'photo/' + photo;
                        selectUser({...u, photo});
                        return;
                    }

                    filtered.forEach(u => {
                        let photo = u.UserPhoto;
                        if(!photo || photo == 'NONE' || photo == '0') photo = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.name);
                        else if(!photo.startsWith('http')) photo = 'photo/' + photo;

                        html += `
                            <div class="user-result" onclick='selectUser(${JSON.stringify({...u, photo})})'>
                                <img src="${photo}">
                                <div>
                                    <div class="u-name">${u.name}</div>
                                    <div class="u-phone">${u.PhoneNumber}</div>
                                </div>
                            </div>
                        `;
                    });
                }
            } else {
                html = '<p style="text-align:center; font-size:12px; color:var(--text-muted); padding:10px;">No users found</p>';
            }
            document.getElementById('searchResults').innerHTML = html;
        }

        function selectUser(user) {
            selectedUser = user;
            document.getElementById('searchResults').innerHTML = '';
            document.getElementById('section-phone').style.display = 'none';
            document.getElementById('selectedRecipient').style.display = 'flex';
            document.getElementById('selAvatar').src = user.photo;
            document.getElementById('selName').innerText = user.name;
            document.getElementById('selPhone').innerText = user.PhoneNumber;
            document.getElementById('phoneInput').value = '';
        }

        function clearRecipient() {
            selectedUser = null;
            document.getElementById('selectedRecipient').style.display = 'none';
            document.getElementById('section-phone').style.display = 'block';
        }

        function mockScan() {
            selectUser({
                UserID: '9999',
                name: 'System Tester',
                PhoneNumber: '+212 600 000 999',
                photo: 'https://ui-avatars.com/api/?name=ST'
            });
        }

        function goToStep(s) {
            if(s === 2) {
                if(currentBalance <= 0) {
                    showError("Your QOON Pay balance is zero. Please top up your wallet first.");
                    return;
                }
                if(!selectedUser) {
                    showError("Please search and select a recipient first.");
                    return;
                }
                const am = document.getElementById('sendAmount').value;
                if(!am || am <= 0) {
                    showError("Please enter a valid amount to send.");
                    return;
                }
                if(parseFloat(am) > currentBalance) {
                    showError("Insufficient balance. You only have " + currentBalance + " MAD.");
                    return;
                }

                document.getElementById('confAvatar').src = selectedUser.photo;
                document.getElementById('confName').innerText = selectedUser.name;
                document.getElementById('confPhone').innerText = selectedUser.PhoneNumber;
                document.getElementById('confAmount').innerText = parseFloat(am).toFixed(2) + ' MAD';
            }

            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + s).classList.add('active');
        }

        async function finishTransfer() {
            const btn = document.getElementById('btnConfirm');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
            btn.disabled = true;

            const am = document.getElementById('sendAmount').value;

            const formData = new FormData();
            formData.append('UserID', currentUserID);
            formData.append('ReceiverID', selectedUser.UserID);
            formData.append('Money', am);

            try {
                const res = await fetch('AddChargeToUser.php', {
                    method: 'POST',
                    headers: { 'token': '<?= $userData['UserToken'] ?? "" ?>' },
                    body: formData
                });
                const json = await res.json();

                if(json.success || json.status_code === 200) {
                    showSuccessAnimation(am, selectedUser.name);
                } else {
                    showError(json.message || "Transfer failed. Please try again.");
                    btn.innerHTML = 'Send Now <i class="fa-solid fa-paper-plane"></i>';
                    btn.disabled = false;
                }
            } catch(e) {
                showError("Network error. Please try again.");
                btn.innerHTML = 'Send Now <i class="fa-solid fa-paper-plane"></i>';
                btn.disabled = false;
            }
        }

        function showSuccessAnimation(amount, name) {
            
            // Push to Firebase Chat Room
            try {
                if (typeof firebase !== 'undefined') {
                    const currentUserID = '<?= $userId ?>';
                    const currentUserName = '<?= addslashes($userData['name']) ?>';
                    const chatRoomId = [currentUserID, selectedUser.UserID].sort().join('_');
                    firebase.database().ref('FriendChats/' + chatRoomId).push({
                        timestamp: Date.now(),
                        type: 'Transfer',
                        message: amount,
                        senderId: currentUserID,
                        senderName: currentUserName
                    });
                }
            } catch(e) { console.error("Firebase push failed", e); }

            const overlay = document.createElement('div');
            overlay.className = 'success-overlay';
            overlay.innerHTML = `
                <div class="success-card">
                    <img src="qoon_pay_logo.png" alt="QOON PAY" class="s-logo">
                    <div class="check-circle">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2>Transfer Successful!</h2>
                    <div class="s-amount">${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} MAD</div>
                    <p class="s-desc">Sent to <b>${name}</b></p>
                    <div class="s-loader"></div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            // Trigger reflow
            void overlay.offsetWidth;
            overlay.classList.add('active');

            setTimeout(() => {
                window.location.href = 'qpay.php' + window.location.search;
            }, 3500);
        }
    </script>
    <style>
        .success-overlay {
            position: fixed; inset: 0; z-index: 99999;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex; justify-content: center; align-items: center;
            opacity: 0; pointer-events: none;
            transition: opacity 0.4s ease;
        }
        .success-overlay.active { opacity: 1; pointer-events: auto; }
        
        .success-card {
            background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
            border: 1px solid rgba(44, 181, 232, 0.3);
            border-radius: 30px;
            padding: 40px 30px;
            text-align: center;
            width: 90%; max-width: 380px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 40px rgba(44, 181, 232, 0.15);
            transform: translateY(40px) scale(0.95);
            transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .success-overlay.active .success-card { transform: translateY(0) scale(1); }

        .s-logo { height: 32px; filter: brightness(0) invert(1); margin-bottom: 30px; }
        
        .check-circle {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, #34c759 0%, #28a745 100%);
            display: flex; justify-content: center; align-items: center;
            margin: 0 auto 25px;
            box-shadow: 0 15px 30px rgba(52, 199, 89, 0.3);
            position: relative;
        }
        .check-circle::after {
            content: ''; position: absolute; inset: -10px;
            border: 2px solid rgba(52, 199, 89, 0.3);
            border-radius: 50%;
            animation: pulse 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }
        @keyframes pulse { 0% { transform: scale(0.8); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }
        
        .check-circle i { font-size: 36px; color: #fff; animation: popIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s backwards; }
        @keyframes popIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

        .success-card h2 { font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #fff; }
        .s-amount { font-size: 38px; font-weight: 800; color: var(--accent-glow); margin-bottom: 10px; letter-spacing: -1px; }
        .s-desc { font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 30px; }
        .s-desc b { color: #fff; }

        .s-loader {
            width: 50px; height: 4px; background: rgba(255,255,255,0.1);
            border-radius: 4px; margin: 0 auto; position: relative; overflow: hidden;
        }
        .s-loader::after {
            content: ''; position: absolute; top: 0; left: 0; height: 100%; width: 0%;
            background: var(--accent-glow);
            animation: fillLoader 3.5s linear forwards;
        }
        @keyframes fillLoader { to { width: 100%; } }

        .error-toast {
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 400px;
            background: rgba(255, 59, 48, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 59, 48, 0.2);
            border-radius: 20px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10000;
            transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .error-toast.active { top: 20px; }
        .error-toast i { font-size: 24px; color: #ff3b30; }
        .err-msg b { display: block; font-size: 14px; font-weight: 800; color: #ff3b30; }
        .err-msg p { font-size: 13px; color: rgba(255,255,255,0.7); }
    </style>
</body>
</html>
