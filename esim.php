<?php
define('FROM_UI', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>QOON eSIM - Global Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #0a0a1f;
            --purple-glow: #6a11cb;
            --pink-glow: #ff0080;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass: rgba(20, 20, 40, 0.4);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        /* Responsive Layout */
        body { background-color: var(--bg-deep); color: var(--text-main); font-family: 'Outfit', sans-serif; overflow-x: hidden; }
        .app-wrapper {
            width: 100%;
            min-height: 100vh;
            background-color: var(--bg-deep);
            position: relative;
            overflow-x: hidden;
        }

        .container { max-width: 1200px; margin: 0 auto; width: 100%; }

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--pink-glow); }

        /* Top Nav */
        .top-nav {
            position: sticky; top: 0; z-index: 100;
            padding: 20px; padding-top: max(20px, env(safe-area-inset-top));
            display: flex; justify-content: space-between; align-items: center;
            background: linear-gradient(to bottom, rgba(10,10,31,0.9), transparent);
            backdrop-filter: blur(10px);
        }
        .btn-circle {
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all 0.3s; font-size: 18px; cursor: pointer;
        }
        .btn-circle:hover { background: var(--pink-glow); border-color: transparent; transform: scale(1.1); box-shadow: 0 0 20px var(--pink-glow); }

        /* Hero */
        .kenz-header {
            position: relative;
            height: 35vh; min-height: 250px;
            width: 100%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            margin-top: -85px; /* Pull up under nav */
            padding-top: 85px;
        }
        .kenz-header::before {
            content: ''; position: absolute; inset: 0;
            background: url('kenz_bg.png') center/cover no-repeat;
            filter: brightness(0.6) saturate(1.2); z-index: 0;
            animation: slowZoom 60s infinite alternate linear;
        }
        @keyframes slowZoom { from { transform: scale(1); } to { transform: scale(1.15); } }
        .kenz-header::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to bottom, transparent 40%, var(--bg-deep)); z-index: 1;
        }
        .header-content { position: relative; z-index: 10; text-align: center; padding: 0 20px; }
        .header-content h1 {
            font-size: clamp(36px, 6vw, 60px); font-weight: 800; letter-spacing: -2px; line-height: 1; margin-bottom: 12px;
            background: linear-gradient(to right, #fff, #ff75a0, #6a11cb); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.5));
        }
        .header-content p { font-size: clamp(15px, 2vw, 18px); color: rgba(255,255,255,0.8); max-width: 400px; margin: 0 auto; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }

        /* Search */
        .search-container { max-width: 800px; margin: 0 auto 30px; padding: 0 20px; position: sticky; top: calc(75px + env(safe-area-inset-top)); z-index: 90; }
        .search-bar {
            background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            border-radius: 20px; padding: 4px; display: flex; align-items: center;
            backdrop-filter: blur(12px); box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        .search-bar:focus-within { border-color: var(--pink-glow); box-shadow: 0 0 20px rgba(255,0,128,0.3); }
        .search-icon { padding: 0 16px; color: var(--text-muted); font-size: 18px; }
        .search-input {
            flex: 1; background: transparent; border: none; outline: none; color: #fff;
            font-family: 'Outfit', sans-serif; font-size: 16px; padding: 14px 0;
        }
        .search-input::placeholder { color: rgba(255,255,255,0.4); }

        /* Grid */
        .page-content { max-width: 1200px; margin: 0 auto; padding: 0 20px 40px; position: relative; z-index: 20; }
        .sec-title { font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .sec-title::before { content: ''; width: 4px; height: 20px; background: var(--pink-glow); border-radius: 99px; box-shadow: 0 0 15px var(--pink-glow); }
        
        .country-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 16px; }
        @media (min-width: 768px) {
            .country-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 24px; }
            .cat-card { padding: 24px; }
            .flag-wrapper { width: 80px; height: 80px; margin-bottom: 16px; }
            .country-name { font-size: 18px; }
        }
        
        .cat-card {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid var(--glass-border);
            border-radius: 24px; padding: 16px; display: flex; flex-direction: column; align-items: center;
            cursor: pointer; position: relative; overflow: hidden; text-align: center;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            background-image: radial-gradient(at 0% 0%, rgba(44, 181, 232, 0.1) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(155, 45, 241, 0.1) 0px, transparent 50%);
        }
        .cat-card:hover { transform: translateY(-6px); background-color: rgba(255, 255, 255, 0.05); border-color: var(--pink-glow); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4); }
        
        .flag-wrapper { width: 60px; height: 60px; border-radius: 50%; padding: 3px; background: linear-gradient(135deg, var(--glass-border), rgba(255,255,255,0.1)); margin-bottom: 12px; overflow: hidden; }
        .country-flag { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .country-name { font-size: 15px; font-weight: 600; color: var(--text-main); line-height: 1.2; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Shimmer Loading */
        .shimmer-card {
            height: 130px; border-radius: 24px;
            background: linear-gradient(90deg, rgba(255,255,255,0.02) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.02) 75%);
            background-size: 200% 100%; animation: shimmer 2s infinite linear; border: 1px solid var(--glass-border);
        }
        @media (min-width: 768px) { .shimmer-card { height: 180px; } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }

        /* Drawers (Responsive Modals) */
        .drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 200; opacity: 0; pointer-events: none; transition: opacity 0.4s; }
        .drawer-overlay.open { opacity: 1; pointer-events: auto; }
        
        .drawer {
            position: fixed; bottom: 0; left: 0; right: 0; background: #0f0f1f;
            border-top-left-radius: 40px; border-top-right-radius: 40px; z-index: 210;
            transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            max-height: 85vh; display: flex; flex-direction: column;
            border-top: 1px solid var(--glass-border); box-shadow: 0 -20px 60px rgba(0,0,0,0.8);
        }
        .drawer.open { transform: translateY(0); }
        
        @media (min-width: 768px) {
            .drawer {
                bottom: 50%; left: 50%; right: auto; transform: translate(-50%, 100%);
                width: 100%; max-width: 500px; max-height: 80vh;
                border-radius: 32px; border: 1px solid var(--glass-border);
                opacity: 0; pointer-events: none; transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            }
            .drawer.open { transform: translate(-50%, 50%); opacity: 1; pointer-events: auto; }
            .drawer-handle { display: none; }
        }
        .drawer-handle { width: 40px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 4px; margin: 16px auto; }
        .drawer-header { padding: 10px 24px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--glass-border); }
        .drawer-title { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .drawer-content { overflow-y: auto; padding: 24px; flex: 1; }

        /* Plan Cards */
        .plan-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 24px; padding: 20px; margin-bottom: 16px;
            display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.3s;
        }
        .plan-card:hover { border-color: var(--pink-glow); background: rgba(255,255,255,0.06); transform: scale(1.02); }
        .plan-data { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; background: linear-gradient(to right, #fff, #ff75a0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .plan-validity { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-top: 4px; display: flex; align-items: center; gap: 6px; }
        .plan-price-btn { background: #fff; color: #000; padding: 10px 20px; border-radius: 100px; font-weight: 700; font-size: 16px; transition: all 0.3s; }
        .plan-card:hover .plan-price-btn { background: var(--pink-glow); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(255,0,128,0.3); }

        /* Actions */
        .action-btn {
            width: 100%; padding: 18px; border-radius: 99px; background: linear-gradient(135deg, var(--purple-glow), var(--pink-glow));
            color: #fff; font-size: 18px; font-weight: 700; border: none; cursor: pointer;
            box-shadow: 0 10px 30px rgba(255,0,128,0.4); transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .action-btn:hover { transform: scale(1.03); box-shadow: 0 15px 40px rgba(255,0,128,0.6); }
        .action-btn:active { transform: scale(0.97); }
        .action-btn:disabled { background: var(--glass); color: var(--text-muted); box-shadow: none; cursor: not-allowed; }

        /* Checkouts */
        .checkout-summary { background: rgba(0,0,0,0.4); border-radius: 32px; padding: 24px; text-align: center; margin-bottom: 24px; border: 1px solid var(--glass-border); }
        .checkout-data { font-size: 48px; font-weight: 800; margin-bottom: 8px; color: #fff; line-height: 1; }
        .checkout-price { font-size: 24px; color: var(--pink-glow); font-weight: 700; }
    </style>
</head>
<body>
    <div class="app-wrapper">

        <nav class="top-nav">
            <a href="index.php" class="btn-circle"><i class="fa-solid fa-arrow-left"></i></a>
            <img src="logo_qoon_white.png" alt="QOON" onerror="this.style.display='none'" style="height:28px; object-fit:contain; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));">
            <a href="javascript:void(0)" class="btn-circle"><i class="fa-solid fa-clock-rotate-left"></i></a>
        </nav>

        <header class="kenz-header">
            <div class="header-content">
                <h1>Global eSIM</h1>
                <p>Instant connectivity across 190+ destinations. No physical SIM needed.</p>
            </div>
        </header>

        <div class="search-container">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="countryInput" class="search-input" placeholder="Search destination..." oninput="filterCountries()">
            </div>
        </div>

        <div class="page-content">
            <h2 class="sec-title">Destinations</h2>
            <div class="country-grid" id="country-container">
                <!-- Shimmer Loading -->
            </div>
        </div>

        <!-- PLANS DRAWER -->
        <div class="drawer-overlay" id="plans-overlay" onclick="closeDrawer('plans')"></div>
        <div class="drawer" id="plans-drawer">
            <div class="drawer-handle"></div>
            <div class="drawer-header">
                <div class="drawer-title" id="plans-title">
                    <img id="plans-flag" src="" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    <span id="plans-country-name">Country</span>
                </div>
                <button class="btn-circle" onclick="closeDrawer('plans')" style="width: 36px; height: 36px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="drawer-content" id="plans-content">
                <!-- Plans loaded here -->
            </div>
        </div>

        <!-- CHECKOUT DRAWER -->
        <div class="drawer-overlay" id="checkout-overlay" style="z-index: 220;" onclick="closeDrawer('checkout')"></div>
        <div class="drawer" id="checkout-drawer" style="z-index: 230;">
            <div class="drawer-handle"></div>
            <div class="drawer-header">
                <div class="drawer-title">Review Plan</div>
                <button class="btn-circle" onclick="closeDrawer('checkout')" style="width: 36px; height: 36px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="drawer-content">
                <div class="checkout-summary">
                    <div style="color: var(--pink-glow); font-weight: 700; margin-bottom: 12px; letter-spacing: 1px; text-transform: uppercase;" id="checkout-country">Country</div>
                    <div class="checkout-data" id="checkout-data">10 GB</div>
                    <div style="color: #fff; margin-bottom: 24px; font-weight: 500;" id="checkout-validity">30 Days Validity</div>
                    <div class="checkout-price" id="checkout-price">$24.00</div>
                </div>
                <button class="action-btn" id="confirm-buy-btn">
                    <span>Pay securely</span> <i class="fa-solid fa-shield-halved"></i>
                </button>
            </div>
        </div>

        <!-- SUCCESS DRAWER -->
        <div class="drawer-overlay" id="success-overlay" style="z-index: 240;" onclick="closeDrawer('success')"></div>
        <div class="drawer" id="success-drawer" style="z-index: 250;">
            <div class="drawer-handle"></div>
            <div class="drawer-header" style="border-bottom: none;">
                <div class="drawer-title" style="color: #10b981;">eSIM Ready</div>
                <button class="btn-circle" onclick="closeDrawer('success')" style="width: 36px; height: 36px;"><i class="fa-solid fa-check"></i></button>
            </div>
            <div class="drawer-content" style="text-align: center; padding-top: 0;">
                <p style="color: var(--text-muted); margin-top: 0; margin-bottom: 30px; font-size: 16px;">Scan this QR code from your device settings to activate your eSIM.</p>
                <div style="background: #fff; padding: 20px; border-radius: 24px; display: inline-block; margin-bottom: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                    <img id="qr-image" src="" alt="eSIM QR Code" style="width: 220px; height: 220px; border-radius: 8px;">
                </div>
                <div style="background: rgba(124, 58, 237, 0.1); border: 1px dashed var(--purple-glow); padding: 12px 20px; border-radius: 16px; font-family: monospace; font-size: 16px; color: #d8b4fe; letter-spacing: 1px;" id="iccid-text">ICCID: ...</div>
                <button class="action-btn" style="margin-top: 30px; background: #fff; color: #000; box-shadow: none;" onclick="closeDrawer('success')">Done</button>
            </div>
        </div>

    </div>

    <script>
        let allCountries = [];
        
        // Render initial shimmer
        const container = document.getElementById('country-container');
        let shimmerHTML = '';
        for(let i=0; i<12; i++) {
            shimmerHTML += '<div class="shimmer-card"></div>';
        }
        container.innerHTML = shimmerHTML;

        // Fetch dynamic list of countries from eSIM Access API Backend
        fetch('esimaccess_countries.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.countries && data.countries.length > 0) {
                    allCountries = data.countries;
                    
                    // Sorting logic: Morocco first!
                    allCountries.sort((a, b) => {
                        const isMoroccoA = a.code.toLowerCase() === 'ma';
                        const isMoroccoB = b.code.toLowerCase() === 'ma';
                        
                        if (isMoroccoA && !isMoroccoB) return -1;
                        if (!isMoroccoA && isMoroccoB) return 1;
                        
                        // Otherwise alphabetical
                        return a.name.localeCompare(b.name);
                    });

                    renderCountries(allCountries);
                } else if (!data.success) {
                    container.innerHTML = `<div style="grid-column: 1/-1; padding: 20px; text-align: center; color: #ff0080;">${data.message || 'Error loading countries'}</div>`;
                }
            })
            .catch(err => {
                console.error('Failed to load countries:', err);
                container.innerHTML = '<div style="grid-column: 1/-1; padding: 20px; text-align: center; color: #ff0080;">Failed to load destinations.</div>';
            });

        function renderCountries(countries) {
            container.innerHTML = '';
            if (countries.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; padding: 20px; text-align: center; color: rgba(255,255,255,0.5);">No destinations found.</div>';
                return;
            }
            countries.forEach((country) => {
                container.innerHTML += `
                    <div class="cat-card" onclick="openPlansDrawer('${country.code}', '${country.name.replace(/'/g, "\\'")}', '${country.flag}')">
                        <div class="flag-wrapper">
                            <img src="${country.flag}" class="country-flag" alt="${country.name}" onerror="this.src='https://ui-avatars.com/api/?name=${country.code}&background=6a11cb&color=fff'">
                        </div>
                        <div class="country-name">${country.name}</div>
                    </div>
                `;
            });
        }

        function filterCountries() {
            const query = document.getElementById('countryInput').value.toLowerCase().trim();
            if (query === '') {
                renderCountries(allCountries);
                return;
            }
            const filtered = allCountries.filter(c => c.name.toLowerCase().includes(query));
            renderCountries(filtered);
        }

        // Drawer Management
        function openDrawer(id) {
            document.getElementById(id + '-overlay').classList.add('open');
            document.getElementById(id + '-drawer').classList.add('open');
        }

        function closeDrawer(id) {
            document.getElementById(id + '-overlay').classList.remove('open');
            document.getElementById(id + '-drawer').classList.remove('open');
        }

        function openPlansDrawer(countryCode, countryName, flagUrl) {
            document.getElementById('plans-country-name').innerText = countryName;
            document.getElementById('plans-flag').src = flagUrl;
            
            const content = document.getElementById('plans-content');
            content.innerHTML = `
                <div class="shimmer-card" style="height:100px; margin-bottom:16px;"></div>
                <div class="shimmer-card" style="height:100px; margin-bottom:16px;"></div>
                <div class="shimmer-card" style="height:100px; margin-bottom:16px;"></div>
            `;
            
            openDrawer('plans');

            fetch(`search_esim.php?countryCode=${encodeURIComponent(countryCode)}&country=${encodeURIComponent(countryName)}`)
                .then(res => res.json())
                .then(data => {
                    content.innerHTML = '';
                    if(data.success && data.plans.length > 0) {
                        data.plans.forEach((plan) => {
                            content.innerHTML += `
                                <div class="plan-card" onclick="openCheckoutDrawer('${plan.id}', '${plan.data}', '${plan.validity}', '${plan.price}', '${countryName.replace(/'/g, "\\'")}')">
                                    <div>
                                        <div class="plan-data">${plan.data}</div>
                                        <div class="plan-validity"><i class="fa-solid fa-bolt" style="color: #fbbf24;"></i> ${plan.validity}</div>
                                        <div style="font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 5px;">
                                            <i class="fa-solid fa-tower-cell"></i> ${plan.provider || 'Premium Network'}
                                        </div>
                                    </div>
                                    <div class="plan-price-btn">${plan.price}</div>
                                </div>
                            `;
                        });
                    } else {
                        content.innerHTML = `
                            <div style="text-align:center; padding: 60px 20px; color: rgba(255,255,255,0.5);">
                                <i class="fa-solid fa-sim-card fa-3x" style="opacity: 0.2; margin-bottom: 20px;"></i>
                                <h3 style="margin: 0 0 8px; color: #fff;">No Plans Available</h3>
                                <p style="margin: 0;">We currently don't have eSIM plans for this destination.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    content.innerHTML = `
                        <div style="text-align:center; padding: 60px 20px; color: #ff0080;">
                            <i class="fa-solid fa-triangle-exclamation fa-3x" style="opacity: 0.8; margin-bottom: 20px;"></i>
                            <h3 style="margin: 0 0 8px; color: #fff;">Connection Error</h3>
                            <p style="margin: 0;">Failed to retrieve plans. Please check your network and try again.</p>
                        </div>
                    `;
                });
        }

        function openCheckoutDrawer(id, data, validity, price, countryName) {
            document.getElementById('checkout-country').innerText = countryName + " eSIM";
            document.getElementById('checkout-data').innerText = data;
            document.getElementById('checkout-validity').innerText = validity;
            document.getElementById('checkout-price').innerText = price;
            
            const btn = document.getElementById('confirm-buy-btn');
            btn.onclick = function() { processPurchase(id); };
            resetCheckoutBtn();
            
            openDrawer('checkout');
        }

        function processPurchase(offerId) {
            const btn = document.getElementById('confirm-buy-btn');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Activating eSIM...</span>';
            btn.disabled = true;

            fetch('buy_esim.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ offerId: offerId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.transactionId) {
                    pollEsimStatus(data.transactionId);
                } else {
                    alert("Transaction Failed: " + (data.message || "Unknown error"));
                    resetCheckoutBtn();
                }
            })
            .catch(err => {
                alert("Network connection error.");
                resetCheckoutBtn();
            });
        }

        function pollEsimStatus(transactionId) {
            let attempts = 0;
            const pollInterval = setInterval(() => {
                attempts++;
                if (attempts > 20) {
                    clearInterval(pollInterval);
                    alert("Activation is taking longer than expected. Please check your email.");
                    resetCheckoutBtn();
                    return;
                }
                fetch(`check_esim.php?transactionId=${transactionId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'DONE') {
                        clearInterval(pollInterval);
                        closeDrawer('checkout');
                        showSuccess(data.qrUrl, data.iccid);
                        resetCheckoutBtn();
                    } else if (data.status === 'FAILED') {
                        clearInterval(pollInterval);
                        alert("Activation failed. Your payment method has not been charged.");
                        resetCheckoutBtn();
                    }
                });
            }, 3000);
        }

        function resetCheckoutBtn() {
            const btn = document.getElementById('confirm-buy-btn');
            btn.innerHTML = '<span>Pay securely</span> <i class="fa-solid fa-shield-halved"></i>';
            btn.disabled = false;
        }

        function showSuccess(qrUrl, iccid) {
            document.getElementById('qr-image').src = qrUrl;
            document.getElementById('iccid-text').innerText = "ICCID: " + iccid;
            openDrawer('success');
        }
    </script>
</body>
</html>
