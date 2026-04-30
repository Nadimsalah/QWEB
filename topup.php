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
$uName = $userData['name'] ?: 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Up | QOON Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        html.light-mode .amount-card { background: #ffffff !important; border-color: rgba(0,0,0,0.08) !important; box-shadow: 0 10px 40px rgba(0,0,0,0.04) !important; }
        html.light-mode .amount-input-group input { color: #0f1115 !important; }
        html.light-mode .q-amount { background: rgba(0,0,0,0.03) !important; color: #0f1115 !important; }
        html.light-mode .method-item { background: #ffffff !important; border-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .method-name { color: #0f1115 !important; }
        html.light-mode .method-icon { background: rgba(0,0,0,0.03) !important; color: #0f1115 !important; }
        html.light-mode .summary-card { background: rgba(0,0,0,0.02) !important; }
        html.light-mode .summary-total { border-top-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .summary-total span { color: #0f1115 !important; }
        html.light-mode header img { filter: none !important; }


        .aurora { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
        .blob { position: absolute; width: 60vw; height: 60vh; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); filter: blur(100px); opacity: 0.1; animation: move 15s infinite alternate; }
        @keyframes move { from { transform: translate(-10%, -10%); } to { transform: translate(10%, 10%); } }

        header { padding: 15px 20px; display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); background: rgba(5,5,5,0.7); border-bottom: 1px solid var(--glass-border); }
        .back-btn { width:40px; height:40px; border-radius:50%; background:var(--glass-bg); border:1px solid var(--glass-border); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; }

        <?php if(isset($_GET['iframe'])): ?>
        /* header { display: none !important; } */
        .container { margin: 0 !important; padding: 20px !important; width: 100% !important; max-width: 100% !important; }
        body { background: transparent !important; }
        .aurora { display: none !important; }
        <?php else: ?>
        .container { max-width: 500px; margin: 40px auto; padding: 0 20px; }
        <?php endif; ?>
        
        .step { display: none; animation: slideUp 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        .step.active { display: block; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        h1 { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
        p.subtitle { color: var(--text-muted); font-size: 15px; margin-bottom: 30px; }

        /* Amount Input Card */
        .amount-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .amount-input-group { position: relative; margin: 20px 0; }
        .amount-input-group input {
            width: 100%;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 48px;
            font-weight: 800;
            text-align: center;
            padding: 10px;
            outline: none;
        }
        .amount-input-group input::placeholder { color: rgba(255,255,255,0.1); }
        .currency-tag { font-size: 18px; font-weight: 700; color: var(--accent-glow); margin-top: -10px; display: block; }

        .quick-amounts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px; }
        .q-amount { background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .q-amount:hover { background: rgba(255,255,255,0.1); border-color: var(--accent-glow); }

        /* Payment Methods */
        .methods-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px; }
        .method-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .method-item:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .method-item.selected { border-color: var(--accent-glow); background: rgba(44, 181, 232, 0.05); }
        .method-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        
        .method-info { flex: 1; }
        .method-name { font-size: 16px; font-weight: 700; }
        .method-desc { font-size: 12px; color: var(--text-muted); }

        .brand-icons { display: flex; gap: 8px; margin-top: 4px; }
        .brand-icons i { font-size: 22px; opacity: 0.8; }
        .fa-cc-visa { color: #fff; }
        .fa-cc-mastercard { color: #eb001b; }
        .fa-paypal { color: #003087; }

        /* Buttons */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #2cb5e8 0%, #1e88e5 100%);
            color: #fff;
            padding: 18px;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(44, 181, 232, 0.2);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(44, 181, 232, 0.4); }
        .btn-primary:active { transform: translateY(0); }

        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: var(--text-muted); }
        .summary-total { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--glass-border); font-size: 18px; font-weight: 800; }
    </style>
</head>
<body>
    <div class="aurora"><div class="blob"></div></div>

    <header>
        <a href="javascript:void(0)" onclick="window.location.href='qpay.php' + window.location.search" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <img src="qoon_pay_logo.png" style="height:28px; filter:brightness(0) invert(1);">
    </header>

    <div class="container">
        <!-- Step 1: Amount -->
        <div class="step active" id="step1">
            <h1>Top Up Wallet</h1>
            <p class="subtitle">Enter the amount you would like to add to your QOON Pay balance.</p>
            
            <div class="amount-card">
                <div class="amount-input-group">
                    <input type="number" id="topupAmount" placeholder="0" autofocus>
                </div>
                <span class="currency-tag">MAD (Moroccan Dirham)</span>
                
                <div class="quick-amounts">
                    <div class="q-amount" onclick="setAmount(50)">50</div>
                    <div class="q-amount" onclick="setAmount(100)">100</div>
                    <div class="q-amount" onclick="setAmount(500)">500</div>
                </div>
            </div>

            <button class="btn-primary" onclick="goToStep(2)">
                Next Step <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- Step 2: Payment Method -->
        <div class="step" id="step2">
            <h1>Payment Method</h1>
            <p class="subtitle">Select your preferred way to pay.</p>

            <div class="methods-list" id="methodsList">
                <div class="method-item" onclick="selectMethod('card', this)">
                    <div class="method-icon"><i class="fa-solid fa-credit-card"></i></div>
                    <div class="method-info">
                        <div class="method-name">Credit / Debit Card</div>
                        <div class="brand-icons">
                            <i class="fa-brands fa-cc-visa"></i>
                            <i class="fa-brands fa-cc-mastercard"></i>
                        </div>
                    </div>
                    <div class="check-mark"><i class="fa-solid fa-circle-check" style="display:none; color:var(--accent-glow);"></i></div>
                </div>

                <div class="method-item" onclick="selectMethod('transfer', this)">
                    <div class="method-icon"><i class="fa-solid fa-building-columns"></i></div>
                    <div class="method-info">
                        <div class="method-name">Bank Transfer</div>
                        <div class="method-desc">Domestic wire transfer</div>
                    </div>
                    <div class="check-mark"><i class="fa-solid fa-circle-check" style="display:none; color:var(--accent-glow);"></i></div>
                </div>

                <div class="method-item" onclick="selectMethod('paypal', this)">
                    <div class="method-icon" style="color:#0079c1;"><i class="fa-brands fa-paypal"></i></div>
                    <div class="method-info">
                        <div class="method-name">PayPal</div>
                        <div class="method-desc">Fast and secure checkout</div>
                    </div>
                    <div class="check-mark"><i class="fa-solid fa-circle-check" style="display:none; color:var(--accent-glow);"></i></div>
                </div>
            </div>

            <div class="summary-card" style="background:rgba(255,255,255,0.02); padding:20px; border-radius:20px; margin-bottom:24px;">
                <div class="summary-row"><span>Amount</span> <span id="sumAmount">0.00 MAD</span></div>
                <div class="summary-row"><span>Fees</span> <span>0.00 MAD</span></div>
                <div class="summary-total summary-row"><span>Total</span> <span id="sumTotal" style="color:#fff;">0.00 MAD</span></div>
            </div>

            <button class="btn-primary" onclick="confirmTopup()">
                Confirm & Pay <i class="fa-solid fa-lock"></i>
            </button>
            <button style="width:100%; padding:15px; background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-weight:600;" onclick="goToStep(1)">Back to amount</button>
        </div>
    </div>

    <script>
        let selectedMethod = null;
        let amount = 0;

        function setAmount(val) {
            document.getElementById('topupAmount').value = val;
        }

        function goToStep(s) {
            amount = document.getElementById('topupAmount').value;
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }
            
            document.getElementById('sumAmount').innerText = parseFloat(amount).toFixed(2) + ' MAD';
            document.getElementById('sumTotal').innerText = parseFloat(amount).toFixed(2) + ' MAD';

            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + s).classList.add('active');
        }

        function selectMethod(m, el) {
            selectedMethod = m;
            document.querySelectorAll('.method-item').forEach(i => {
                i.classList.remove('selected');
                i.querySelector('.fa-circle-check').style.display = 'none';
            });
            el.classList.add('selected');
            el.querySelector('.fa-circle-check').style.display = 'block';
        }

        function confirmTopup() {
            if (!selectedMethod) {
                alert('Please select a payment method');
                return;
            }

            const btn = document.querySelector('#step2 .btn-primary');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
            btn.disabled = true;

            setTimeout(() => {
                window.location.href = 'qpay.php' + window.location.search;
            }, 3000);
        }
    </script>
</body>
</html>
