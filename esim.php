<?php
define('FROM_UI', true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Global eSIM - QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #050505;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 16px;
            background: rgba(5, 5, 5, 0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            justify-content: space-between;
        }

        .back-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            padding: 16px;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-box {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 4px;
            display: flex;
            align-items: center;
        }

        .search-box i {
            padding: 0 16px;
            color: rgba(255, 255, 255, 0.4);
        }

        .search-box input {
            background: transparent;
            border: none;
            color: #fff;
            font-family: inherit;
            font-size: 16px;
            width: 100%;
            padding: 16px 0;
            outline: none;
        }

        .country-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .country-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .country-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(147, 51, 234, 0.5);
            transform: translateY(-2px);
        }

        .country-flag {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .country-name {
            font-weight: 600;
            font-size: 16px;
        }

        .plan-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .plan-card:hover {
            transform: translateY(-2px);
            border-color: rgba(147, 51, 234, 0.5);
            box-shadow: 0 10px 30px rgba(147, 51, 234, 0.15);
        }

        .data-amount {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
        }

        .validity {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 4px;
            font-weight: 500;
        }

        .price-badge {
            background: #9333ea;
            color: #fff;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
        }

        .hero-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -1px;
            margin: 0 0 8px 0;
            text-align: center;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
            margin: 0 0 24px 0;
            text-align: center;
        }

        /* Modal styling */
        #plans-modal {
            position: fixed;
            inset: 0;
            background: #050505;
            z-index: 200;
            display: none;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        #plans-modal.open {
            transform: translateX(0);
        }
    </style>
</head>

<body>
    <div class="header">
        <button class="back-btn" onclick="window.location.href='index.php'"><i
                class="fa-solid fa-arrow-left"></i></button>
        <div style="font-weight: 700;">Global eSIM</div>
        <div style="width: 40px;"></div>
    </div>

    <div class="content">
        <div style="text-align: center; margin-top: 10px;">
            <div
                style="width: 64px; height: 64px; background: linear-gradient(135deg, #9333ea, #c084fc); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; box-shadow: 0 10px 20px rgba(147,51,234,0.3);">
                <i class="fa-solid fa-sim-card" style="font-size: 28px; color: #fff;"></i>
            </div>
            <h1 class="hero-title">Stay Connected.</h1>
            <p class="hero-subtitle">Search instant data plans in 190+ countries.</p>
        </div>

        <div class="search-box">
            <i class="fa-solid fa-globe"></i>
            <input type="text" id="countryInput" placeholder="Where are you traveling to?" oninput="filterCountries()">
        </div>

        <h3 style="margin-top: 20px; margin-bottom: 0; font-size: 18px;">Popular Destinations</h3>
        <div class="country-grid" id="country-container">
            <!-- Countries injected via JS -->
        </div>
    </div>

    <!-- Plans Modal -->
    <div id="plans-modal">
        <div class="header">
            <button class="back-btn" onclick="closePlans()"><i class="fa-solid fa-arrow-left"></i></button>
            <div style="font-weight: 700;" id="modal-title">Morocco eSIM</div>
            <div style="width: 40px;"></div>
        </div>
        <div class="content" style="overflow-y: auto;">
            <div id="plans-container"></div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkout-modal"
        style="position:fixed; inset:0; background:#050505; z-index:300; display:none; flex-direction:column; transform:translateX(100%); transition:transform 0.3s;">
        <div class="header">
            <button class="back-btn" onclick="closeCheckout()"><i class="fa-solid fa-arrow-left"></i></button>
            <div style="font-weight: 700;">Confirm Purchase</div>
            <div style="width: 40px;"></div>
        </div>
        <div class="content" style="text-align: center; padding-top: 40px;">
            <i class="fa-solid fa-cart-shopping fa-3x" style="color: #9333ea; margin-bottom: 20px;"></i>
            <h2 id="checkout-plan-name" style="margin-bottom: 8px;">Plan Name</h2>
            <div id="checkout-price" style="font-size: 32px; font-weight: 800; margin-bottom: 40px;">$0.00</div>
            <button id="confirm-buy-btn"
                style="width: 100%; padding: 16px; border-radius: 16px; background: #9333ea; color: #fff; font-size: 18px; font-weight: 700; border: none; cursor: pointer;">Pay
                Now</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal"
        style="position:fixed; inset:0; background:#050505; z-index:400; display:none; flex-direction:column; transform:translateX(100%); transition:transform 0.3s;">
        <div class="header">
            <button class="back-btn" onclick="closeSuccess()"><i class="fa-solid fa-xmark"></i></button>
            <div style="font-weight: 700;">eSIM Ready</div>
            <div style="width: 40px;"></div>
        </div>
        <div class="content" style="text-align: center; padding-top: 40px;">
            <i class="fa-solid fa-circle-check fa-4x" style="color: #10b981; margin-bottom: 20px;"></i>
            <h2 style="margin-bottom: 8px;">Purchase Complete</h2>
            <p style="color: rgba(255,255,255,0.6); margin-bottom: 30px;">Scan this QR code to install your eSIM.</p>
            <div
                style="background: #fff; padding: 16px; border-radius: 20px; display: inline-block; margin-bottom: 20px;">
                <img id="qr-image" src="" style="width: 200px; height: 200px;" alt="eSIM QR">
            </div>
            <div id="iccid-text" style="font-family: monospace; font-size: 16px; color: #9333ea; letter-spacing: 2px;">
            </div>
        </div>
    </div>

    <script>
        const popularCountries = [
            { name: 'Morocco', code: 'ma', flag: 'https://flagcdn.com/w80/ma.png' },
            { name: 'United States', code: 'us', flag: 'https://flagcdn.com/w80/us.png' },
            { name: 'United Kingdom', code: 'gb', flag: 'https://flagcdn.com/w80/gb.png' },
            { name: 'France', code: 'fr', flag: 'https://flagcdn.com/w80/fr.png' },
            { name: 'Turkey', code: 'tr', flag: 'https://flagcdn.com/w80/tr.png' },
            { name: 'United Arab Emirates', code: 'ae', flag: 'https://flagcdn.com/w80/ae.png' },
            { name: 'Spain', code: 'es', flag: 'https://flagcdn.com/w80/es.png' },
            { name: 'Italy', code: 'it', flag: 'https://flagcdn.com/w80/it.png' }
        ];

        function renderCountries(countries) {
            const container = document.getElementById('country-container');
            container.innerHTML = '';
            countries.forEach(country => {
                container.innerHTML += `
                    <div class="country-card" onclick="openPlans('${country.code}', '${country.name}')">
                        <img src="${country.flag}" class="country-flag" alt="${country.name}">
                        <div class="country-name">${country.name}</div>
                    </div>
                `;
            });
        }

        function filterCountries() {
            const query = document.getElementById('countryInput').value.toLowerCase();
            const filtered = popularCountries.filter(c => c.name.toLowerCase().includes(query));
            renderCountries(filtered);
        }

        function openPlans(countryCode, countryName) {
            const modal = document.getElementById('plans-modal');
            const container = document.getElementById('plans-container');
            document.getElementById('modal-title').innerText = countryName + " eSIM";

            modal.style.display = 'flex';
            // Trigger reflow for animation
            void modal.offsetWidth;
            modal.classList.add('open');

            container.innerHTML = '<div style="text-align:center; padding:40px; color:rgba(255,255,255,0.4);"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i></div>';

            fetch(`search_esim.php?countryCode=${encodeURIComponent(countryCode)}&country=${encodeURIComponent(countryName)}`)
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = '';
                    if (data.success && data.plans.length > 0) {
                        data.plans.forEach(plan => {
                            container.innerHTML += `
                                <div class="plan-card" onclick="buyPlan('${plan.id}', '${plan.data}', '${plan.validity}', '${plan.price}')">
                                    <div>
                                        <div class="data-amount">${plan.data}</div>
                                        <div class="validity"><i class="fa-solid fa-calendar-days" style="margin-right: 6px;"></i>${plan.validity}</div>
                                    </div>
                                    <div class="price-badge">${plan.price}</div>
                                </div>
                            `;
                        });
                    } else {
                        container.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">No plans found.</div>';
                    }
                })
                .catch(err => {
                    container.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">Error connecting to API.</div>';
                });
        }

        function closePlans() {
            const modal = document.getElementById('plans-modal');
            modal.classList.remove('open');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function buyPlan(id, data, validity, price) {
            document.getElementById('checkout-plan-name').innerText = data + " - " + validity;
            document.getElementById('checkout-price').innerText = price;
            document.getElementById('confirm-buy-btn').onclick = function () { processPurchase(id); };

            const checkoutModal = document.getElementById('checkout-modal');
            checkoutModal.style.display = 'flex';
            void checkoutModal.offsetWidth;
            checkoutModal.classList.add('open');
        }

        function closeCheckout() {
            const checkoutModal = document.getElementById('checkout-modal');
            checkoutModal.classList.remove('open');
            setTimeout(() => { checkoutModal.style.display = 'none'; }, 300);
        }

        function processPurchase(offerId) {
            const btn = document.getElementById('confirm-buy-btn');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Provisioning...';
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
                        alert("Error: " + (data.message || "Purchase failed"));
                        resetCheckoutBtn();
                    }
                })
                .catch(err => {
                    alert("Network Error");
                    resetCheckoutBtn();
                });
        }

        function pollEsimStatus(transactionId) {
            const pollInterval = setInterval(() => {
                fetch(`check_esim.php?transactionId=${transactionId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'DONE') {
                            clearInterval(pollInterval);
                            closeCheckout();
                            showSuccess(data.qrUrl, data.iccid);
                            resetCheckoutBtn();
                        } else if (data.status === 'FAILED') {
                            clearInterval(pollInterval);
                            alert("Provisioning failed. Your wallet was refunded.");
                            resetCheckoutBtn();
                        }
                    });
            }, 3000);
        }

        function resetCheckoutBtn() {
            const btn = document.getElementById('confirm-buy-btn');
            btn.innerHTML = 'Pay Now';
            btn.disabled = false;
        }

        function showSuccess(qrUrl, iccid) {
            document.getElementById('qr-image').src = qrUrl;
            document.getElementById('iccid-text').innerText = "ICCID: " + iccid;

            const successModal = document.getElementById('success-modal');
            successModal.style.display = 'flex';
            void successModal.offsetWidth;
            successModal.classList.add('open');
        }

        function closeSuccess() {
            const successModal = document.getElementById('success-modal');
            successModal.classList.remove('open');
            setTimeout(() => { successModal.style.display = 'none'; }, 300);
        }

        // Initial load
        renderCountries(popularCountries);
    </script>
</body>

</html>tml>