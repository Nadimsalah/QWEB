<?php
define('FROM_UI', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Book Flight - QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr for Modern Calendar Popup -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <style>
        :root {
            --kiwi-teal: #00a991;
            --kiwi-dark-bg: #0a0b0d;
            --kiwi-card-bg: #1a1d23;
            --kiwi-border: rgba(255,255,255,0.08);
            --kiwi-text-dim: rgba(255,255,255,0.5);
            --kiwi-accent: #00d7bb;
        }
        
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: 'Outfit', 'Inter', sans-serif; 
            background: var(--kiwi-dark-bg); 
            color: #fff;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .header { 
            background: rgba(16, 18, 21, 0.8); 
            padding: 16px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border-bottom: 1px solid var(--kiwi-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(20px);
        }
        
        .content { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 40px 24px; 
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 40px;
        }

        .back-btn { 
            background: rgba(255,255,255,0.05); 
            border: 1px solid var(--kiwi-border); 
            color: #fff; 
            width: 44px; 
            height: 44px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        .back-btn:hover { background: rgba(255,255,255,0.1); transform: translateX(-2px); }

        /* Kiwi Sidebar */
        .filter-sidebar {
            background: #14161a;
            border: 1px solid var(--kiwi-border);
            border-radius: 28px;
            padding: 32px;
            height: fit-content;
            position: sticky;
            top: 100px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .filter-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .filter-section {
            margin-bottom: 40px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--kiwi-border);
        }
        .filter-section:last-child { border: none; margin-bottom: 0; padding-bottom: 0; }
        .filter-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--kiwi-text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 20px;
            display: block;
        }

        /* Kiwi Dark Flight Card */
        .flight-card {
            background: var(--kiwi-card-bg);
            border: 1px solid var(--kiwi-border);
            border-radius: 28px;
            margin-bottom: 24px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            position: relative;
        }
        .flight-card:hover {
            border-color: rgba(0,169,145,0.5);
            transform: translateY(-6px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }
        
        .card-main {
            padding: 32px;
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 40px;
            align-items: center;
        }
        
        .timeline {
            display: flex;
            align-items: center;
            gap: 32px;
            flex: 1;
        }
        .time-block { min-width: 100px; }
        .time-val { font-size: 26px; font-weight: 900; color: #fff; letter-spacing: -0.5px; }
        .city-code { font-size: 14px; font-weight: 700; color: var(--kiwi-text-dim); margin-top: 4px; }
        
        .path-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .duration { font-size: 12px; font-weight: 800; color: var(--kiwi-text-dim); margin-bottom: 12px; }
        .path-visual {
            width: 100%;
            height: 2px;
            background: rgba(255,255,255,0.08);
            position: relative;
            border-radius: 1px;
        }
        .path-visual::before, .path-visual::after {
            content: '';
            position: absolute;
            top: -4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2a2d35;
            border: 2px solid var(--kiwi-border);
        }
        .path-visual::before { left: 0; }
        .path-visual::after { right: 0; }
        .path-icon {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%) rotate(90deg);
            color: var(--kiwi-teal);
            font-size: 18px;
            filter: drop-shadow(0 0 8px rgba(0,169,145,0.4));
        }

        .price-zone {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            border-left: 1px solid var(--kiwi-border);
            padding-left: 40px;
        }
        .price-tag { font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .price-desc { font-size: 12px; font-weight: 600; color: var(--kiwi-text-dim); margin-bottom: 20px; }
        
        .kiwi-book-btn {
            background: var(--kiwi-teal);
            color: #fff;
            border: none;
            border-radius: 16px;
            height: 56px;
            width: 100%;
            font-weight: 900;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(0,169,145,0.2);
        }
        .kiwi-book-btn:hover { background: var(--kiwi-accent); transform: translateY(-2px); box-shadow: 0 15px 35px rgba(0,169,145,0.4); }

        /* Modern Checkbox */
        .kiwi-checkbox {
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            padding: 10px 0;
            user-select: none;
        }
        .kiwi-checkbox input { display: none; }
        .box {
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.03);
            border: 2px solid var(--kiwi-border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .kiwi-checkbox input:checked + .box {
            background: var(--kiwi-teal);
            border-color: var(--kiwi-teal);
        }
        .kiwi-checkbox input:checked + .box::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 12px;
            color: #fff;
        }
        .check-label { font-size: 15px; font-weight: 600; color: rgba(255,255,255,0.8); }

        /* Expansion Styling */
        .expansion-header {
            background: #15171c;
            border-top: 1px solid var(--kiwi-border);
            display: flex;
            height: 56px;
        }
        .ex-tab {
            background: transparent;
            border: none;
            color: var(--kiwi-text-dim);
            padding: 0 32px;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .ex-tab.active {
            color: var(--kiwi-teal);
            border-bottom-color: var(--kiwi-teal);
        }
        .expansion-body {
            background: #0d0f12;
            padding: 40px;
            border-top: 1px solid var(--kiwi-border);
        }

        /* Modern Range Slider */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.08);
            border-radius: 3px;
            outline: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            background: #fff;
            border-radius: 50%;
            cursor: pointer;
            border: 6px solid var(--kiwi-teal);
            box-shadow: 0 0 15px rgba(0,169,145,0.5);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        /* ── Drawer ── */
        .drawer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1999; backdrop-filter:blur(6px); }
        .drawer-overlay.show { display:block; }
        #checkout-drawer { position:fixed; right:-520px; top:0; width:500px; max-width:100vw; height:100vh; background:#111318; border-left:1px solid var(--kiwi-border); z-index:2000; display:flex; flex-direction:column; transition:right 0.4s cubic-bezier(0.16,1,0.3,1); overflow:hidden; }
        #checkout-drawer.open { right:0; }
        .drawer-header { padding:24px 28px; border-bottom:1px solid var(--kiwi-border); display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
        .drawer-title { font-size:20px; font-weight:800; color:#fff; }
        .drawer-close { background:rgba(255,255,255,0.06); border:1px solid var(--kiwi-border); color:#fff; width:36px; height:36px; border-radius:10px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; }
        .drawer-body { flex:1; overflow-y:auto; padding:24px 28px; display:flex; flex-direction:column; gap:20px; }
        .drawer-footer { padding:20px 28px; border-top:1px solid var(--kiwi-border); flex-shrink:0; }
        .checkout-section { display:flex; flex-direction:column; gap:12px; }
        .section-label { font-size:11px; font-weight:800; color:var(--kiwi-text-dim); text-transform:uppercase; letter-spacing:1.5px; }
        .drawer-input { background:rgba(255,255,255,0.05); border:1px solid var(--kiwi-border); color:#fff; padding:12px 16px; border-radius:12px; font-size:14px; font-family:inherit; width:100%; outline:none; transition:border-color 0.2s; }
        .drawer-input:focus { border-color:var(--kiwi-teal); }
        .drawer-input::placeholder { color:rgba(255,255,255,0.3); }
        .drawer-input option { background:#1a1d23; color:#fff; }
        .flight-summary-mini { background:rgba(255,255,255,0.04); border:1px solid var(--kiwi-border); border-radius:16px; padding:20px; }
        .mini-route { display:flex; align-items:center; gap:16px; font-size:24px; font-weight:900; margin-bottom:12px; }
        .mini-airport { color:#fff; }
        .mini-airline { display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,0.7); font-weight:600; }
        .traveler-info-card { background:rgba(255,255,255,0.03); border:1px solid var(--kiwi-border); border-radius:16px; padding:20px; display:flex; flex-direction:column; gap:12px; }
        .price-breakdown { display:flex; flex-direction:column; gap:8px; }
        .price-row { display:flex; justify-content:space-between; font-size:14px; color:rgba(255,255,255,0.7); }
        .price-total { font-weight:800; color:#fff; font-size:16px; padding-top:8px; border-top:1px solid var(--kiwi-border); margin-top:4px; }
        .confirm-btn { width:100%; height:56px; background:var(--kiwi-teal); color:#fff; font-size:16px; font-weight:900; border:none; border-radius:16px; cursor:pointer; transition:all 0.3s; box-shadow:0 10px 25px rgba(0,169,145,0.25); }
        .confirm-btn:hover { background:var(--kiwi-accent); transform:translateY(-2px); }
    </style>
</head>
<body>
    <!-- Fullscreen Loader -->
    <div id="fullscreen-loader" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #000; z-index: 9999; flex-direction: column; align-items: center; justify-content: center;">
        <img src="logo_qoon_white.png" alt="QOON" style="height: 40px; margin-bottom: 40px; animation: pulse 2s infinite;">
        <div style="display: flex; align-items: center; gap: 24px; font-size: 24px; font-weight: 700; color: #fff;">
            <span id="loader-origin">RBA</span>
            <i class="fa-solid fa-plane" style="color: #ff9f0a; animation: flyRight 2s infinite linear;"></i>
            <span id="loader-dest">IST</span>
        </div>
        <div id="loader-date" style="color: rgba(255,255,255,0.6); margin-top: 16px; font-size: 16px;">13 May 2026</div>
        <div id="loader-pax" style="color: rgba(255,255,255,0.4); margin-top: 8px; font-size: 14px;">1 Passengers</div>
    </div>
    
    <style>
        @keyframes pulse {
            0% { opacity: 0.5; transform: scale(0.95); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 0.5; transform: scale(0.95); }
        }
        @keyframes flyRight {
            0% { transform: translateX(-20px) translateY(5px); opacity: 0; }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { transform: translateX(20px) translateY(-5px); opacity: 0; }
        }
    </style>
    <div class="header">
        <div style="display: flex; align-items: center; gap: 16px;">
            <button class="back-btn" onclick="window.location.href='index.php'"><i class="fa-solid fa-arrow-left"></i></button>
            <img src="logo_qoon_white.png" alt="QOON" style="height: 28px; width: auto; object-fit: contain;">
        </div>
        <div>
            <button class="back-btn" style="width: auto; padding: 0 20px; font-weight: 600; font-size: 14px;" onclick="document.getElementById('manage-booking-modal').style.display='flex'">My Trips</button>
        </div>
    </div>

    <!-- Manage Booking Modal -->
    <div id="manage-booking-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
        <div style="background: #111; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 32px; width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 20px;">Manage Booking</h2>
                <button onclick="document.getElementById('manage-booking-modal').style.display='none'" style="background: transparent; border: none; color: rgba(255,255,255,0.6); font-size: 20px; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin: 0;">Enter your PNR to view your E-Ticket, void, or request a refund.</p>
            <input type="text" id="manage-pnr" class="drawer-input" placeholder="PNR (e.g. TRW12345)" style="text-transform: uppercase;">
            <button onclick="fetchTripDetails()" style="background: #fff; color: #000; font-weight: 700; border: none; border-radius: 12px; padding: 14px; cursor: pointer;">Retrieve Booking</button>
            
            <div id="manage-results" style="display: none; flex-direction: column; gap: 12px; margin-top: 16px;">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
    
    <div class="content" id="results-layout" style="display: none;">
        <div class="filter-sidebar">
            <div class="filter-title">
                <i class="fa-solid fa-sliders" style="color: var(--kiwi-teal);"></i>
                Filters
            </div>
            
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span class="filter-label">Max Price</span>
                    <span id="price-display" style="font-size: 14px; font-weight: 800; color: var(--kiwi-teal);">$1000</span>
                </div>
                <input type="range" id="price-filter" min="0" max="1000" value="1000" oninput="document.getElementById('price-display').innerText = '$' + this.value; applyFilters();">
            </div>

            <div class="filter-section">
                <span class="filter-label">Stops</span>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label class="kiwi-checkbox">
                        <input type="checkbox" value="0" onchange="applyFilters()">
                        <div class="box"></div>
                        <span class="check-label">Direct</span>
                    </label>
                    <label class="kiwi-checkbox">
                        <input type="checkbox" value="1" onchange="applyFilters()">
                        <div class="box"></div>
                        <span class="check-label">1 Stop</span>
                    </label>
                </div>
            </div>

            <div class="filter-section">
                <span class="filter-label">Airlines</span>
                <div id="airline-filters" style="display: flex; flex-direction: column; gap: 8px;"></div>
            </div>

            <button style="background: transparent; border: 1px solid var(--kiwi-border); color: #fff; width: 100%; height: 48px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 13px;" onclick="location.reload()">Reset All</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 900; margin: 0 0 4px 0;">Available Flights</h2>
                    <div id="results-count" style="color: var(--kiwi-text-dim); font-size: 14px; font-weight: 600;">0 Flights found</div>
                </div>
            </div>
            <div id="results"></div>
        </div>
    </div>

    <!-- Checkout Drawer -->
    <div id="drawer-overlay" class="drawer-overlay" onclick="closeCheckout()"></div>
    <div id="checkout-drawer">
        <div class="drawer-header">
            <div class="drawer-title">Flight Checkout</div>
            <button class="drawer-close" onclick="closeCheckout()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="drawer-body">
            <div class="checkout-section">
                <span class="section-label">Itinerary</span>
                <div class="flight-summary-mini">
                    <div class="mini-route">
                        <div class="mini-airport" id="chk-origin">RBA</div>
                        <i class="fa-solid fa-arrow-right" style="color: rgba(255,255,255,0.2);"></i>
                        <div class="mini-airport" id="chk-dest">MRS</div>
                    </div>
                    <div class="mini-airline" id="chk-airline-row">
                        <img id="chk-logo" src="" style="width: 20px; height: 20px; border-radius: 50%; background: #fff;">
                        <span id="chk-airline-name">Ryanair</span>
                        <span style="opacity: 0.3;">•</span>
                        <span id="chk-date">Jun 25</span>
                    </div>
                </div>
            </div>

            <div class="checkout-section">
                <span class="section-label">Booking Add-ons</span>
                <div style="display: flex; gap: 12px; margin-top: 12px;">
                    <button class="drawer-input" style="flex:1; background:rgba(255,255,255,0.05); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;" onclick="viewFareRules()">
                        <i class="fa-solid fa-file-contract"></i> View Fare Rules
                    </button>
                    <button class="drawer-input" style="flex:1; background:rgba(255,255,255,0.05); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;" onclick="viewExtraServices()">
                        <i class="fa-solid fa-suitcase-rolling"></i> Add Baggage
                    </button>
                </div>
            </div>

            <div class="checkout-section">
                <span class="section-label">Travelers (Pre-Booking)</span>
                <div id="chk-passengers-container">
                    <!-- Dynamic passenger forms injected here -->
                </div>
                
                <div class="traveler-info-card passenger-form" style="margin-top: 16px;">
                    <span class="section-label">Contact & Billing Address</span>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px;">
                        <input type="email" id="chk-email" class="drawer-input" placeholder="Email Address">
                        <input type="text" id="chk-mobile" class="drawer-input" placeholder="Mobile Number">
                    </div>
                    <input type="text" id="chk-address" class="drawer-input" placeholder="Address">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <input type="text" id="chk-city" class="drawer-input" placeholder="City">
                        <input type="text" id="chk-state" class="drawer-input" placeholder="State">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <input type="text" id="chk-country" class="drawer-input" placeholder="Country">
                        <input type="text" id="chk-zip" class="drawer-input" placeholder="Zip Code">
                    </div>
                </div>
            </div>

            <div class="checkout-section">
                <span class="section-label">Price Summary</span>
                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Adult x<span id="chk-passengers">1</span></span>
                        <span id="chk-base-price">$0</span>
                    </div>
                    <div class="price-row">
                        <span>Taxes & Fees</span>
                        <span>Included</span>
                    </div>
                    <div class="price-row price-total">
                        <span>Total Price</span>
                        <span id="chk-total-price">$0</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="drawer-footer">
            <button class="confirm-btn" id="chk-confirm-btn">Book Flight</button>
        </div>
    </div>

    <!-- Modern Alert -->
    <div id="modern-alert" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
<div style="background: #111; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 32px; width: 100%; max-width: 350px; text-align: center; display: flex; flex-direction: column; gap: 16px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 40px; color: #ff9f0a;"></i>
            <div id="alert-text" style="color: #fff; font-size: 15px; font-weight: 500;"></div>
            <button onclick="document.getElementById('modern-alert').style.display='none'" style="background: #fff; color: #000; font-weight: 700; border: none; border-radius: 12px; padding: 12px; cursor: pointer; margin-top: 8px;">Got it</button>
        </div>
    </div>
    <script>
        const AIRLINE_NAMES = {
            'FR': 'Ryanair', 'AT': 'Royal Air Maroc', 'RAM': 'Royal Air Maroc', 'LH': 'Lufthansa',
            'AF': 'Air France', 'EK': 'Emirates', 'QR': 'Qatar Airways', 'TK': 'Turkish Airlines',
            'BA': 'British Airways', 'VY': 'Vueling', 'U2': 'EasyJet', 'EZY': 'EasyJet',
            'HV': 'Transavia', 'TO': 'Transavia France', 'IB': 'Iberia', 'TP': 'TAP Air Portugal',
            'LX': 'Swiss', 'OS': 'Austrian Airlines', 'SN': 'Brussels Airlines', 'KL': 'KLM',
            'AZ': 'ITA Airways', 'DL': 'Delta Air Lines', 'UA': 'United Airlines', 'AA': 'American Airlines',
            'B6': 'JetBlue', 'WS': 'WestJet', 'AC': 'Air Canada', 'MS': 'EgyptAir', 'TU': 'Tunisair',
            'AH': 'Air Algérie', 'ET': 'Ethiopian Airlines', 'KQ': 'Kenya Airways', 'SA': 'South African Airways',
            'EY': 'Etihad Airways', 'SV': 'Saudia', 'KU': 'Kuwait Airways', 'RJ': 'Royal Jordanian',
            'ME': 'MEA', 'GF': 'Gulf Air', 'WY': 'Oman Air', 'PC': 'Pegasus Airlines', 'FZ': 'flydubai',
            'G9': 'Air Arabia', '3O': 'Air Arabia Maroc'
        };

        let currentFlights = [];
        let paxCounts = { adt: 1, chd: 0, inf: 0 };
        let currentExtraTab = {};
        let activeFlight = null;

        async function fetchFlights() {
            const p = new URLSearchParams(window.location.search);
            const origin = p.get('origin'), dest = p.get('dest'), date = p.get('date');
            const adt = parseInt(p.get('adt')) || 1;
            const chd = parseInt(p.get('chd')) || 0;
            const inf = parseInt(p.get('inf')) || 0;
            paxCounts = { adt, chd, inf };

            const loader = document.getElementById('fullscreen-loader');
            if (loader) {
                loader.style.display = 'flex';
                document.getElementById('loader-origin').innerText = origin || '---';
                document.getElementById('loader-dest').innerText = dest || '---';
                document.getElementById('loader-date').innerText = date || '';
                document.getElementById('loader-pax').innerText = `${adt + chd + inf} Passenger${(adt+chd+inf)>1?'s':''}`;
            }

            try {
                const url = `search_flights.php?origin=${origin}&destination=${dest}&depart_date=${date}&return_date=&adults=${adt}&children=${chd}&infants=${inf}&trip_class=0`;
                const res = await fetch(url);
                const data = await res.json();

                // search_flights.php returns: { success: true, data: { "CMN": [...flights] } }
                if (data.success && data.data && data.data[dest] && data.data[dest].length > 0) {
                    currentFlights = data.data[dest];
                    document.getElementById('results-layout').style.display = 'grid';
                    initFilters();

                } else {
                    showModernAlert(data.message || 'No flights found for this route.');
                }
            } catch (e) {
                console.error(e);
                showModernAlert('Network error. Please try again.');
            } finally {
                if (loader) loader.style.display = 'none';
            }
        }


        function initFilters() {
            const prices = currentFlights.map(f => parseFloat(f.price));
            const maxPrice = Math.ceil(Math.max(...prices));
            const priceFilter = document.getElementById('price-filter');
            priceFilter.max = maxPrice;
            priceFilter.value = maxPrice;
            document.getElementById('price-display').innerText = '$' + maxPrice;

            // Build airline checkboxes from flat f.airline field
            const container = document.getElementById('airline-filters');
            const airlines = {};
            currentFlights.forEach(f => {
                airlines[f.airline] = AIRLINE_NAMES[f.airline] || f.airline;
            });
            container.innerHTML = '';
            Object.entries(airlines).forEach(([code, name]) => {
                const lbl = document.createElement('label');
                lbl.className = 'kiwi-checkbox';
                lbl.innerHTML = `<input type="checkbox" value="${code}" class="airline-check" onchange="applyFilters()"><div class="box"></div><span class="check-label">${name}</span>`;
                container.appendChild(lbl);
            });

            // Render all flights initially
            renderFlightCards(currentFlights);
        }



        function applyFilters() {
            const maxPrice = parseFloat(document.getElementById('price-filter').value);
            const sel = Array.from(document.querySelectorAll('.airline-check:checked')).map(c => c.value);
            const filtered = currentFlights.filter(f =>
                parseFloat(f.price) <= maxPrice && (sel.length === 0 || sel.includes(f.airline))
            );
            renderFlightCards(filtered);
            document.getElementById('results-count').innerText = `${filtered.length} Flights found`;
        }

        function renderFlightCards(flights) {
            const container = document.getElementById('results');
            if (!container) return;
            container.innerHTML = '';
            document.getElementById('results-count').innerText = `${flights.length} Flights found`;

            if (flights.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:60px;opacity:0.5;"><i class="fa-solid fa-plane-slash" style="font-size:48px;"></i><br><br>No matching flights.</div>';
                return;
            }

            flights.forEach((f, i) => {
                const depTime = f.departure_at ? f.departure_at.substring(11, 16) : '--:--';
                const arrTime = f.arrival_at ? f.arrival_at.substring(11, 16) : '--:--';
                const airlineCode = f.airline;
                const airlineName = AIRLINE_NAMES[airlineCode] || airlineCode;

                let depAirport = '---', arrAirport = '---', stops = 'Direct';
                if (f.segments && f.segments[0] && f.segments[0].OriginDestinationOption) {
                    const opts = f.segments[0].OriginDestinationOption;
                    depAirport = opts[0].FlightSegment.DepartureAirportLocationCode;
                    arrAirport = opts[opts.length-1].FlightSegment.ArrivalAirportLocationCode;
                    if (opts.length > 1) stops = `${opts.length-1} Stop${opts.length>2?'s':''}`;
                }

                const card = document.createElement('div');
                card.className = 'flight-card';
                const fJson = JSON.stringify(f).replace(/'/g, '&#39;');
                card.innerHTML = `
                <div class="card-main">
                    <div style="display:flex;flex-direction:column;gap:24px;">
                        <div class="timeline">
                            <div class="time-block"><div class="time-val">${depTime}</div><div class="city-code">${depAirport}</div></div>
                            <div class="path-container">
                                <div class="duration">${f.flight_number || 'Direct'}</div>
                                <div class="path-visual"><i class="fa-solid fa-plane path-icon"></i></div>
                                <div style="font-size:11px;font-weight:700;color:var(--kiwi-teal);margin-top:10px;">${stops}</div>
                            </div>
                            <div class="time-block" style="text-align:right;"><div class="time-val">${arrTime}</div><div class="city-code">${arrAirport}</div></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="http://pics.avs.io/200/200/${airlineCode}.png" style="width:24px;height:24px;border-radius:4px;background:#fff;padding:2px;" onerror="this.style.display='none'">
                            <span style="font-size:13px;font-weight:700;color:var(--kiwi-text-dim);">${airlineName}</span>
                        </div>
                    </div>
                    <div class="price-zone">
                        <div class="price-tag">USD ${Math.round(f.price)}</div>
                        <div class="price-desc">Total per person</div>
                        <button class="kiwi-book-btn" onclick='openCheckout(${fJson})'>Select</button>
                        <div onclick='toggleDetails(${i})' style="margin-top:16px;color:var(--kiwi-teal);font-size:13px;font-weight:800;cursor:pointer;">
                            Details <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i>
                        </div>
                    </div>
                </div>
                <div id="extra-content-${i}" style="display:none;">
                    <div class="expansion-header">
                        <button class="ex-tab" onclick='loadDetail(${i},"itinerary")'>Itinerary</button>
                        <button class="ex-tab" onclick='loadDetail(${i},"baggage")'>Baggage</button>
                        <button class="ex-tab" onclick='loadDetail(${i},"fare_rules")'>Rules</button>
                    </div>
                    <div class="expansion-body" id="extra-inner-${i}"></div>
                </div>`;
                container.appendChild(card);
            });
        }

        function toggleDetails(i) {
            const el = document.getElementById(`extra-content-${i}`);
            if (el.style.display === 'block') { el.style.display = 'none'; return; }
            el.style.display = 'block';
            loadDetail(i, 'itinerary');
        }

        async function loadDetail(i, type) {
            const inner = document.getElementById(`extra-inner-${i}`);
            const f = currentFlights[i];
            inner.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fa-solid fa-spinner fa-spin" style="color:var(--kiwi-teal);font-size:24px;"></i></div>';

            document.querySelectorAll(`#extra-content-${i} .ex-tab`).forEach(t => t.classList.remove('active'));
            const tabs = document.querySelectorAll(`#extra-content-${i} .ex-tab`);
            const tabIdx = {itinerary:0, baggage:1, fare_rules:2}[type];
            if (tabs[tabIdx]) tabs[tabIdx].classList.add('active');

            if (type === 'itinerary') {
                let html = '<div style="display:grid;gap:16px;">';
                if (f.segments && f.segments[0] && f.segments[0].OriginDestinationOption) {
                    f.segments[0].OriginDestinationOption.forEach(opt => {
                        const fs = opt.FlightSegment;
                        html += `<div style="background:#1a1d23;border:1px solid var(--kiwi-border);padding:20px;border-radius:16px;display:flex;align-items:center;gap:16px;">
                            <img src="http://pics.avs.io/200/200/${fs.MarketingAirlineCode}.png" style="width:40px;height:40px;background:#fff;padding:4px;border-radius:8px;">
                            <div><div style="font-weight:800;">${fs.MarketingAirlineName || fs.MarketingAirlineCode} ${fs.FlightNumber}</div>
                            <div style="color:var(--kiwi-text-dim);font-size:13px;">${fs.DepartureAirportLocationCode} → ${fs.ArrivalAirportLocationCode}</div></div>
                            <div style="margin-left:auto;text-align:right;"><div style="font-size:20px;font-weight:900;">${fs.DepartureDateTime.substring(11,16)}</div><div style="font-size:11px;color:var(--kiwi-text-dim);">→ ${fs.ArrivalDateTime.substring(11,16)}</div></div>
                        </div>`;
                    });
                } else {
                    html += `<div style="padding:16px;color:var(--kiwi-text-dim);">Flight ${f.flight_number || ''}: ${f.departure_at || ''} → ${f.arrival_at || ''}</div>`;
                }
                inner.innerHTML = html + '</div>';
            } else {
                try {
                    const action = type === 'baggage' ? 'extra_services' : 'fare_rules';
                    const res = await fetch(`api_flights_extras.php?action=${action}&session_id=${encodeURIComponent(f.session_id)}&fare_source_code=${encodeURIComponent(f.fare_source_code)}`);
                    const data = await res.json();
                    if (data.success) {
                        if (type === 'baggage') {
                            const bags = (data.data?.ExtraServicesData || data.data)?.DynamicBaggage || [];
                            let html = '<div style="display:flex;flex-direction:column;gap:12px;">';
                            if (!bags.length) html += '<div style="color:var(--kiwi-text-dim);padding:16px;">No baggage options available.</div>';
                            bags.forEach(g => g.Services?.forEach(seg => seg.forEach(s => {
                                html += `<div style="background:#1a1d23;border:1px solid var(--kiwi-border);padding:16px;border-radius:14px;display:flex;justify-content:space-between;align-items:center;"><div style="display:flex;align-items:center;gap:12px;"><i class="fa-solid fa-suitcase" style="color:var(--kiwi-teal);"></i><div><div style="font-weight:700;">${s.Description}</div></div></div><div style="font-weight:900;color:var(--kiwi-teal);">${s.ServiceCost.CurrencyCode} ${s.ServiceCost.Amount}</div></div>`;
                            })));
                            inner.innerHTML = html + '</div>';
                        } else {
                            inner.innerHTML = `<div style="background:#0d0f12;padding:20px;border-radius:14px;font-size:13px;line-height:1.6;color:rgba(255,255,255,0.7);white-space:pre-wrap;">${typeof data.data==='string'?data.data:JSON.stringify(data.data,null,2)}</div>`;
                        }
                    } else {
                        inner.innerHTML = `<div style="text-align:center;padding:24px;"><div style="color:#ff4d4d;font-weight:700;margin-bottom:8px;">Session Expired</div><div style="color:var(--kiwi-text-dim);font-size:13px;">${data.message||'Please search again.'}</div><button onclick="location.reload()" style="margin-top:16px;background:var(--kiwi-teal);color:#fff;border:none;padding:10px 24px;border-radius:12px;font-weight:700;cursor:pointer;">New Search</button></div>`;
                    }
                } catch(e) { inner.innerHTML = '<div style="color:#ff4d4d;padding:16px;">Load failed.</div>'; }
            }
        }

        function openCheckout(f) {
            activeFlight = f;
            const airlineCode = f.airline;
            let depAirport = '---', arrAirport = '---', depDate = '';
            if (f.segments && f.segments[0] && f.segments[0].OriginDestinationOption) {
                const opts = f.segments[0].OriginDestinationOption;
                depAirport = opts[0].FlightSegment.DepartureAirportLocationCode;
                arrAirport = opts[opts.length-1].FlightSegment.ArrivalAirportLocationCode;
            }
            if (f.departure_at) depDate = f.departure_at.substring(0, 10);

            document.getElementById('chk-logo').src = `http://pics.avs.io/200/200/${airlineCode}.png`;
            document.getElementById('chk-origin').innerText = depAirport;
            document.getElementById('chk-dest').innerText = arrAirport;
            document.getElementById('chk-airline-name').innerText = AIRLINE_NAMES[airlineCode] || airlineCode;
            document.getElementById('chk-date').innerText = depDate;
            document.getElementById('chk-passengers').innerText = paxCounts.adt + paxCounts.chd + paxCounts.inf;
            document.getElementById('chk-total-price').innerText = `USD ${Math.round(f.price)}`;
            
            const container = document.getElementById('chk-passengers-container');
            container.innerHTML = '';
            let paxIndex = 0;

            const addPaxForm = (typeLabel, typeCode, count) => {
                for (let i = 0; i < count; i++) {
                    container.innerHTML += `
                    <div class="traveler-info-card passenger-form" style="margin-bottom: 12px;">
                        <span class="section-label" style="display:block; margin-bottom:8px; font-weight:600;">Passenger ${paxIndex + 1} (${typeLabel})</span>
                        <input type="hidden" id="pax-type-${paxIndex}" value="${typeCode}">
                        <div style="display: grid; grid-template-columns: 100px 1fr 1fr; gap: 16px;">
                            <select id="chk-title-${paxIndex}" class="drawer-input" style="padding: 0 10px;">
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                ${typeCode !== 'ADT' ? '<option value="Master">Master</option>' : ''}
                            </select>
                            <input type="text" id="chk-fname-${paxIndex}" class="drawer-input" placeholder="First Name" value="${paxIndex === 0 ? ('<?= $_COOKIE['qoon_user_name'] ?? '' ?>') : ''}">
                            <input type="text" id="chk-lname-${paxIndex}" class="drawer-input" placeholder="Last Name">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <input type="date" id="chk-dob-${paxIndex}" class="drawer-input" placeholder="Date of Birth" style="color-scheme: dark;">
                            <input type="text" id="chk-nationality-${paxIndex}" class="drawer-input" placeholder="Nationality (e.g. US)" maxlength="2">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <input type="text" id="chk-passport-${paxIndex}" class="drawer-input" placeholder="Passport Number">
                            <input type="text" id="chk-passport-issue-${paxIndex}" class="drawer-input" placeholder="Issue Country (e.g. US)" maxlength="2">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                            <input type="date" id="chk-passport-exp-${paxIndex}" class="drawer-input" placeholder="Passport Expiry" style="color-scheme: dark;">
                        </div>
                    </div>`;
                    paxIndex++;
                }
            };

            addPaxForm('Adult', 'ADT', paxCounts.adt);
            addPaxForm('Child', 'CHD', paxCounts.chd);
            addPaxForm('Infant', 'INF', paxCounts.inf);
            
            totalPaxCount = paxIndex;

            document.getElementById('chk-confirm-btn').onclick = bookFlightAPI;

            document.getElementById('drawer-overlay').classList.add('show');
            document.getElementById('checkout-drawer').classList.add('open');
        }

        async function bookFlightAPI() {
            const btn = document.getElementById('chk-confirm-btn');
            
            // Collect dynamic pax data
            let paxDetailsArray = [];
            for(let i = 0; i < totalPaxCount; i++) {
                if(!document.getElementById(`chk-fname-${i}`).value || !document.getElementById(`chk-passport-${i}`).value || !document.getElementById(`chk-dob-${i}`).value) {
                    showModernAlert(`Please fill in all required passenger details for Passenger ${i+1}.`);
                    return;
                }
                
                paxDetailsArray.push({
                    type: document.getElementById(`pax-type-${i}`).value,
                    title: document.getElementById(`chk-title-${i}`).value,
                    firstName: document.getElementById(`chk-fname-${i}`).value,
                    lastName: document.getElementById(`chk-lname-${i}`).value,
                    dob: document.getElementById(`chk-dob-${i}`).value,
                    nationality: document.getElementById(`chk-nationality-${i}`).value,
                    passportNo: document.getElementById(`chk-passport-${i}`).value,
                    passportIssueCountry: document.getElementById(`chk-passport-issue-${i}`).value,
                    passportExpiry: document.getElementById(`chk-passport-exp-${i}`).value
                });
            }

            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
            btn.disabled = true;

            const payload = {
                session_id: activeFlight.session_id,
                fare_source_code: activeFlight.fare_source_code,
                paxDetails: paxDetailsArray
            };

            try {
                const response = await fetch('process_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                
                closeCheckout();
                btn.innerHTML = 'Book Flight';
                btn.disabled = false;

                if (data.success) {
                    let tktMsg = data.tickets && data.tickets.length > 0 ? `Tickets: ${data.tickets.join(', ')}` : 'Tickets: Processing';
                    showModernAlert(`Flight successfully booked!\nPNR: ${data.pnr}\n${tktMsg}\n\nStatus: ${data.is_ticketed ? 'Ticketed' : 'Confirmed'}`);
                } else {
                    showModernAlert(`Booking Failed: ${data.message}`);
                }
            } catch (e) {
                console.error(e);
                closeCheckout();
                btn.innerHTML = 'Book Flight';
                btn.disabled = false;
                showModernAlert("A network error occurred while processing your booking.");
            }
        }

        async function viewFareRules() {
            if(!activeFlight) return;
            showModernAlert("Fetching fare rules...");
            try {
                const res = await fetch(`api_flights_extras.php?action=fare_rules&session_id=${activeFlight.session_id}&fare_source_code=${activeFlight.fare_source_code}`);
                const data = await res.json();
                if(data.success) {
                    let rulesText = typeof data.data === 'string' ? data.data : JSON.stringify(data.data, null, 2);
                    showModernAlert(`Fare Rules:\n\n${rulesText.substring(0, 500)}...`);
                } else {
                    showModernAlert(`Failed to load fare rules: ${data.message}`);
                }
            } catch(e) {
                showModernAlert("Network error fetching fare rules.");
            }
        }

        async function viewExtraServices() {
            if(!activeFlight) return;
            showModernAlert("Fetching available baggage options...");
            try {
                const res = await fetch(`api_flights_extras.php?action=extra_services&session_id=${activeFlight.session_id}&fare_source_code=${activeFlight.fare_source_code}`);
                const data = await res.json();
                if(data.success) {
                    if(Array.isArray(data.data) && data.data.length > 0) {
                        let servicesList = data.data.map(s => `${s.Behavior || ''} - ${s.Description || 'Baggage'} ($${s.Price || 0})`).join('\n');
                        showModernAlert(`Available Extra Services:\n\n${servicesList}\n\n(Selection will be supported in the next update)`);
                    } else {
                        showModernAlert("No extra baggage options available for this fare.");
                    }
                } else {
                    showModernAlert(`Failed to load extra services: ${data.message}`);
                }
            } catch(e) {
                showModernAlert("Network error fetching extra services.");
            }
        }

        async function fetchTripDetails() {
            const pnr = document.getElementById('manage-pnr').value;
            if(!pnr) return;
            
            document.getElementById('manage-results').style.display = 'flex';
            document.getElementById('manage-results').innerHTML = '<div style="color:#fff;"><i class="fa-solid fa-circle-notch fa-spin"></i> Fetching details...</div>';
            
            try {
                const res = await fetch(`api_manage_booking.php?action=trip_details&pnr=${pnr}&session_id=DUMMY_OR_ACTIVE_SESSION`);
                const data = await res.json();
                
                if(data.success && data.data) {
                    const status = data.data.BookingStatus || 'Unknown';
                    document.getElementById('manage-results').innerHTML = `
                        <div style="background: rgba(255,255,255,0.05); padding: 16px; border-radius: 12px;">
                            <div style="color: rgba(255,255,255,0.6); font-size: 12px; margin-bottom: 4px;">Status</div>
                            <div style="color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 12px;">${status}</div>
                            
                            <div style="display: flex; gap: 8px; margin-top: 16px;">
                                <button onclick="quoteAction('void_quote', '${pnr}')" style="flex: 1; background: #ff3b30; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer;">Void</button>
                                <button onclick="quoteAction('refund_quote', '${pnr}')" style="flex: 1; background: #ff9f0a; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer;">Refund</button>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('manage-results').innerHTML = `<div style="color:#ff3b30;">${data.message}</div>`;
                }
            } catch(e) {
                document.getElementById('manage-results').innerHTML = '<div style="color:#ff3b30;">Network error.</div>';
            }
        }

        async function quoteAction(action, pnr) {
            if(!confirm(`Are you sure you want to request a quote for this action?`)) return;
            
            showModernAlert("Fetching quote from airline...");
            try {
                const res = await fetch(`api_manage_booking.php?action=${action}&pnr=${pnr}&session_id=DUMMY_OR_ACTIVE_SESSION`);
                const data = await res.json();
                if(data.success) {
                    const executeActionStr = action === 'void_quote' ? 'process_void' : 'process_refund';
                    if(confirm(`Quote successful. Proceed with execution?`)) {
                        executeAction(executeActionStr, pnr);
                    }
                } else {
                    showModernAlert(`Quote Failed: ${data.message}`);
                }
            } catch(e) {
                showModernAlert("Network error fetching quote.");
            }
        }

        async function executeAction(action, pnr) {
            showModernAlert("Processing request...");
            try {
                const res = await fetch(`api_manage_booking.php?action=${action}&pnr=${pnr}&session_id=DUMMY_OR_ACTIVE_SESSION`);
                const data = await res.json();
                if(data.success) {
                    showModernAlert(`Success: ${data.message}`);
                    fetchTripDetails(); // Refresh
                } else {
                    showModernAlert(`Failed: ${data.message}`);
                }
            } catch(e) {
                showModernAlert("Network error executing action.");
            }
        }

        function closeCheckout() {
            document.getElementById('drawer-overlay').classList.remove('show');
            document.getElementById('checkout-drawer').classList.remove('open');
        }

        function showModernAlert(msg) {
            document.getElementById('alert-text').innerText = msg;
            document.getElementById('modern-alert').style.display = 'flex';
        }

        let totalPaxCount = 0;
        window.onload = fetchFlights;
    </script>
</body>
</html>