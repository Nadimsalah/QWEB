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
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0; 
            font-family: 'Inter', sans-serif; 
            background-color: #050505; 
            background-image: 
                radial-gradient(circle at 50% 120%, rgba(80, 30, 150, 0.25) 0%, transparent 60%),
                radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 100% 100%, 24px 24px;
            background-attachment: fixed;
            color: #fff; 
            min-height: 100vh; 
        }
        
        .header { 
            display: flex; align-items: center; padding: 24px; justify-content: space-between;
        }
        .back-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.05); color: #fff; width: 44px; height: 44px; border-radius: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; font-size: 16px; }
        .back-btn:hover { background: rgba(255,255,255,0.1); }
        
        .content { padding: 20px; width: 100%; max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        
        .hero-title {
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 800;
            text-align: center;
            letter-spacing: -1.5px;
            line-height: 1.1;
            margin: 40px 0 16px 0;
            background: linear-gradient(180deg, #FFFFFF 0%, #B0B0B0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: clamp(16px, 2vw, 20px);
            font-weight: 400;
            margin-bottom: 40px;
        }

        /* QOON Unified Pill Search Box */
        .search-box { 
            background: rgba(20,20,20,0.4); 
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 36px; 
            padding: 8px; 
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
            backdrop-filter: blur(20px);
        }
        .search-row { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .input-wrapper { 
            flex: 1; min-width: 200px;
            display: flex; align-items: center; 
            background: rgba(255,255,255,0.03); 
            border-radius: 28px; 
            padding: 0 20px; 
            height: 64px; 
            border: 1px solid transparent; 
            transition: all 0.2s; 
            position: relative;
        }
        .input-wrapper:focus-within { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); }
        .input-wrapper i { color: rgba(255,255,255,0.4); font-size: 18px; width: 24px; }
        .input-wrapper input { flex: 1; background: transparent; border: none; color: #fff; font-size: 15px; font-weight: 500; padding: 0 12px; height: 100%; outline: none; font-family: 'Inter', sans-serif; width: 100%; }
        .input-wrapper input::placeholder { color: rgba(255,255,255,0.3); }
        
        /* Custom Modern Calendar Styling (Flatpickr Override) */
        .flatpickr-calendar.dark { background: rgba(20,20,20,0.95); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; backdrop-filter: blur(20px); box-shadow: 0 20px 40px rgba(0,0,0,0.5); padding: 10px; }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3); color: #fff !important; }
        input[type="date"]::-webkit-calendar-picker-indicator { display: none; }
        input[type="date"]::-webkit-inner-spin-button { display: none; }
        
        .search-btn { 
            background: rgba(255,255,255,0.08); 
            color: #fff; border: 1px solid rgba(255,255,255,0.05); 
            width: 64px; height: 64px; 
            border-radius: 32px; 
            font-size: 20px; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; 
            transition: background 0.2s; 
        }
        .search-btn:hover { background: rgba(255,255,255,0.15); }

        .autocomplete-list { position: absolute; top: 70px; left: 0; right: 0; background: #111; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        .ac-item { padding: 14px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .ac-item:hover { background: rgba(255,255,255,0.05); }
        .ac-item-name { font-size: 14px; font-weight: 500; color: #fff; }
        .ac-item-code { font-size: 12px; font-weight: 700; color: #fff; background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 10px; }

        /* QOON Quick Actions Row */
        .quick-actions { display: flex; gap: 12px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
        .quick-action-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: #fff; font-size: 14px; font-weight: 500; padding: 10px 20px; border-radius: 20px; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .quick-action-btn i { color: rgba(255,255,255,0.5); }
        
        #loading { display: none; text-align: center; padding: 60px 0; color: rgba(255,255,255,0.4); font-weight: 500; }
        
        #results { width: 100%; margin-top: 40px; }
        /* Refined Native Flight Cards */
        .flight-card { background: rgba(20,20,20,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 32px; padding: 24px; margin-bottom: 16px; backdrop-filter: blur(10px); }
        .fc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .fc-airline { display: flex; align-items: center; gap: 12px; }
        .fc-airline img { width: 32px; height: 32px; border-radius: 16px; background: #fff; }
        .fc-airline span { font-weight: 500; font-size: 15px; color: rgba(255,255,255,0.8); }
        .fc-price { color: #fff; font-weight: 700; font-size: 24px; }
        
        .fc-route { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .fc-time-block { text-align: center; }
        .fc-time { font-size: 22px; font-weight: 700; }
        .fc-airport { font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 4px; font-weight: 500; }
        .fc-duration { flex: 1; text-align: center; padding: 0 20px; }
        .fc-duration span { display: block; font-size: 12px; color: rgba(255,255,255,0.4); }
        .fc-line { width: 100%; height: 1px; background: rgba(255,255,255,0.1); margin: 8px 0; position: relative; }
        .fc-line i { position: absolute; top: -7px; left: 50%; transform: translateX(-50%); background: #0a0a0a; color: rgba(255,255,255,0.5); font-size: 14px; padding: 0 4px; }
        
        .fc-book-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.05); width: 100%; height: 50px; border-radius: 16px; color: #fff; font-weight: 500; font-family: 'Inter', sans-serif; cursor: pointer; transition: background 0.2s; }
        .fc-book-btn:hover { background: rgba(255,255,255,0.15); }
        
        /* Modern Alert Modal */
        #modern-alert { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(5,5,5,0.8); backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 24px; }
        .alert-box { background: rgba(20,20,20,0.95); border: 1px solid rgba(20,17,236,0.3); border-radius: 28px; padding: 32px 24px; max-width: 360px; width: 100%; text-align: center; box-shadow: 0 24px 48px rgba(20,17,236,0.15); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { from { opacity: 0; transform: scale(0.85) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .alert-icon { font-size: 56px; color: #1411EC; margin-bottom: 20px; filter: drop-shadow(0 0 16px rgba(20,17,236,0.4)); }
        .alert-message { color: rgba(255,255,255,0.95); font-size: 16px; font-weight: 500; line-height: 1.6; margin-bottom: 32px; }
        .alert-btn { background: #1411EC; color: #fff; border: none; border-radius: 18px; padding: 16px 32px; font-weight: 700; font-size: 16px; cursor: pointer; width: 100%; transition: all 0.2s; box-shadow: 0 8px 24px rgba(20,17,236,0.3); }
        .alert-btn:hover { background: #0c09d6; }
        .alert-btn:active { transform: scale(0.96); }

        /* Checkout Drawer */
        #checkout-drawer {
            position: fixed; top: 0; right: -100%; width: 100%; max-width: 480px; height: 100vh;
            background: #0a0a0a; z-index: 10000; transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: -20px 0 60px rgba(0,0,0,0.8); display: flex; flex-direction: column;
            border-left: 1px solid rgba(255,255,255,0.05);
        }
        #checkout-drawer.open { right: 0; }
        .drawer-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
            z-index: 9999; display: none; opacity: 0; transition: opacity 0.3s;
        }
        .drawer-overlay.show { display: block; opacity: 1; }
        
        .drawer-header { padding: 32px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .drawer-title { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .drawer-close { width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,0.05); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        
        .drawer-body { flex: 1; overflow-y: auto; padding: 32px 24px; }
        .checkout-section { margin-bottom: 40px; }
        .section-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4); margin-bottom: 16px; display: block; }
        
        .flight-summary-mini { background: rgba(255,255,255,0.03); border-radius: 24px; padding: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .mini-route { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .mini-airport { font-size: 20px; font-weight: 800; }
        .mini-airline { display: flex; align-items: center; gap: 8px; font-size: 14px; color: rgba(255,255,255,0.6); }
        
        .passenger-form { display: flex; flex-direction: column; gap: 16px; }
        .drawer-input { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; height: 56px; padding: 0 20px; color: #fff; font-family: inherit; font-size: 15px; outline: none; }
        .drawer-input:focus { border-color: #1411EC; background: rgba(20,17,236,0.05); }
        
        .price-breakdown { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; }
        .price-row { display: flex; justify-content: space-between; font-size: 15px; color: rgba(255,255,255,0.6); }
        .price-total { font-size: 20px; font-weight: 800; color: #fff; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 16px; margin-top: 4px; }
        
        .drawer-footer { padding: 24px; border-top: 1px solid rgba(255,255,255,0.05); }
        .confirm-btn { background: #1411EC; color: #fff; border: none; border-radius: 20px; height: 64px; width: 100%; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 30px rgba(20,17,236,0.3); }
        .confirm-btn:hover { transform: translateY(-4px); box-shadow: 0 15px 40px rgba(20,17,236,0.5); }

        /* Traveler Info Summary Style */
        .traveler-info-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .info-row { display: flex; flex-direction: column; }
        .info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 2px; }
        .info-value { font-size: 16px; font-weight: 600; color: #fff; }
    </style>
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center; gap: 16px;">
            <button class="back-btn" onclick="window.location.href='index.php'"><i class="fa-solid fa-arrow-left"></i></button>
            <img src="logo_qoon_white.png" alt="QOON" style="height: 28px; width: auto; object-fit: contain;">
        </div>
        <div></div>
    </div>
    
    <div class="content">
        <h1 class="hero-title">Book Flights.</h1>
        <p class="hero-subtitle">Search hundreds of airlines—unified in a single experience.</p>
        
        <!-- Unified QOON Pill Search Form -->
        <div class="search-box">
            <div class="search-row">
                <div class="input-wrapper" style="flex: 1.5;">
                    <i class="fa-solid fa-plane-departure"></i>
                    <input type="text" id="origin" placeholder="Where from?" autocomplete="off" oninput="fetchAutocomplete(this.value, 'origin-list', 'origin_code')">
                    <input type="hidden" id="origin_code">
                    <div class="autocomplete-list" id="origin-list"></div>
                </div>
                
                <div class="input-wrapper" style="flex: 1.5;">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="destination" placeholder="Where to?" autocomplete="off" oninput="fetchAutocomplete(this.value, 'destination-list', 'destination_code')">
                    <input type="hidden" id="destination_code">
                    <div class="autocomplete-list" id="destination-list"></div>
                </div>
            </div>
            
            <div class="search-row">
                <div class="input-wrapper">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="text" id="date" placeholder="Departure">
                </div>
                <div class="input-wrapper">
                    <i class="fa-regular fa-calendar-check"></i>
                    <input type="text" id="return_date" placeholder="Return (Optional)">
                </div>
                <div class="input-wrapper" style="flex: 0.5; min-width: 120px;">
                    <i class="fa-solid fa-user"></i>
                    <input type="number" id="passengers" min="1" max="9" value="1">
                </div>
                
                <button class="search-btn" onclick="searchFlights()">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="quick-actions">
            <div id="class-btn-2" class="quick-action-btn" onclick="selectClass(2)" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05);"><i class="fa-solid fa-star"></i> First Class</div>
            <div id="class-btn-1" class="quick-action-btn" onclick="selectClass(1)" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05);"><i class="fa-solid fa-briefcase"></i> Business</div>
            <div id="class-btn-0" class="quick-action-btn" onclick="selectClass(0)" style="background: rgba(255,255,255,0.15); border-color: #fff; opacity: 1;"><i class="fa-solid fa-chair"></i> Economy</div>
        </div>
        
        <div id="loading">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 24px; margin-bottom: 16px; display: block; color: rgba(255,255,255,0.8);"></i>
            Scanning global airlines...
        </div>
        
        <!-- Filters Container -->
        <div id="filters-container" style="display: none; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; padding: 20px; margin-top: 24px;">
            <div style="font-weight: 700; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-filter" style="color: #fff;"></i> Filter Results
            </div>
            
            <!-- Price Filter -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; color: rgba(255,255,255,0.6);">Max Price</span>
                    <span id="price-display" style="font-size: 14px; font-weight: 700; color: #fff;">$0</span>
                </div>
                <input type="range" id="price-filter" min="0" max="1000" value="1000" style="width: 100%; accent-color: #fff;" oninput="document.getElementById('price-display').innerText = '$' + this.value; applyFilters();">
            </div>
            
            <!-- Airlines Filter -->
            <div>
                <span style="font-size: 13px; color: rgba(255,255,255,0.6); display: block; margin-bottom: 8px;">Airlines</span>
                <div id="airline-filters" style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px;">
                    <!-- Airline toggles will be injected here -->
                </div>
            </div>
        </div>

        <div id="results"></div>
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
                <span class="section-label">Travelers</span>
                <div class="traveler-info-card">
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value" id="chk-user-name"><?= $_COOKIE['qoon_user_name'] ?? 'Nadim' ?></span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="info-row">
                            <span class="info-label">Passport</span>
                            <span class="info-value">41154</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nationality</span>
                            <span class="info-value">DZDZ</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value">25/04/2026</span>
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

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        const API_TOKEN = '0ca3dc3467606e4a114830217d4adf73';
        const MARKER = '521631'; // Your Travelpayouts TRS
        
        let currentFlights = [];
        let selectedAirlines = new Set();
        let fallbackBannerHtml = '';
        let currentTripClass = 0;

        function selectClass(val) {
            currentTripClass = val;
            [0, 1, 2].forEach(c => {
                const btn = document.getElementById(`class-btn-${c}`);
                if (c === val) {
                    btn.style.background = 'rgba(255,255,255,0.15)';
                    btn.style.borderColor = '#fff';
                } else {
                    btn.style.background = 'rgba(255,255,255,0.03)';
                    btn.style.borderColor = 'rgba(255,255,255,0.05)';
                }
            });
            // Auto search if fields are filled
            if (document.getElementById('origin_code').value && document.getElementById('destination_code').value) {
                searchFlights();
            }
        }

        // Initialize Modern Flatpickr Calendar
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(today.getDate() + 7);
            
            flatpickr("#date", {
                theme: "dark",
                defaultDate: nextWeek,
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: "true"
            });

            flatpickr("#return_date", {
                theme: "dark",
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: "true"
            });
        });

        // Autocomplete JS logic
        async function fetchAutocomplete(query, listId, codeInputId) {
            const list = document.getElementById(listId);
            if(query.length < 2) {
                list.style.display = 'none';
                return;
            }
            
            try {
                // Travelpayouts Places API (Public, no token needed)
                const res = await fetch(`https://autocomplete.travelpayouts.com/places2?locale=en&types[]=city&types[]=airport&term=${query}`);
                const data = await res.json();
                
                if(data.length > 0) {
                    list.innerHTML = data.map(item => `
                        <div class="ac-item" onclick="selectLocation('${item.name}', '${item.code}', '${listId}', '${codeInputId}')">
                            <div class="ac-item-name">${item.name} <span style="font-size:12px; color:rgba(255,255,255,0.4);">(${item.country_name})</span></div>
                            <div class="ac-item-code">${item.code}</div>
                        </div>
                    `).join('');
                    list.style.display = 'block';
                } else {
                    list.style.display = 'none';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function selectLocation(name, code, listId, codeInputId) {
            // Find the input wrapper of the autocomplete
            const inputEl = document.getElementById(listId).previousElementSibling.previousElementSibling;
            inputEl.value = `${name} (${code})`;
            document.getElementById(codeInputId).value = code;
            document.getElementById(listId).style.display = 'none';
        }

        // Close dropdowns on outside click
        document.addEventListener('click', (e) => {
            if(!e.target.closest('.input-wrapper') && !e.target.closest('.autocomplete-list')) {
                document.getElementById('origin-list').style.display = 'none';
                document.getElementById('destination-list').style.display = 'none';
            }
        });

        function showModernAlert(msg) {
            document.getElementById('alert-text').innerText = msg;
            document.getElementById('modern-alert').style.display = 'flex';
        }

        async function searchFlights() {
            const originCode = document.getElementById('origin_code').value;
            const destCode = document.getElementById('destination_code').value;
            const date = document.getElementById('date').value;
            const returnDate = document.getElementById('return_date').value;
            
            if(!originCode || !destCode || !date) {
                showModernAlert("Please select valid cities from the dropdown and fill in all fields.");
                return;
            }

            document.getElementById('results').innerHTML = '';
            document.getElementById('filters-container').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            fallbackBannerHtml = '';
            currentFlights = [];
            selectedAirlines.clear();

            try {
                // Actual API call using PHP backend to bypass CORS and hide API Key
                const url = `search_flights.php?origin=${originCode}&destination=${destCode}&depart_date=${date}&return_date=${returnDate}&trip_class=${currentTripClass}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.data && data.data[destCode]) {
                    // Extract the flight options
                    const flightList = data.data[destCode];

                    // Check for alternative dates fallback
                    if (data.is_alternative_dates) {
                        fallbackBannerHtml = `
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-radius: 16px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
                            <i class="fa-solid fa-calendar-day" style="font-size: 24px; color: #fff;"></i>
                            <div>
                                <div style="font-weight: 700; color: #fff; font-size: 15px; margin-bottom: 4px;">Alternative Dates Available</div>
                                <div style="color: rgba(255,255,255,0.7); font-size: 13px;">We couldn't find flights for your exact date, but we found these great alternatives.</div>
                            </div>
                        </div>`;
                    } else if (data.has_alternatives_appended) {
                        fallbackBannerHtml = `
                        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
                            <i class="fa-solid fa-list-ul" style="font-size: 24px; color: #fff;"></i>
                            <div>
                                <div style="font-weight: 700; color: #fff; font-size: 15px; margin-bottom: 4px;">More Options Added</div>
                                <div style="color: rgba(255,255,255,0.7); font-size: 13px;">We included flights from surrounding dates to give you more choices.</div>
                            </div>
                        </div>`;
                    }

                    currentFlights = flightList.map(f => {
                        // Format ISO dates to readable time
                        const flightDate = new Date(f.departure_at);
                        const departTime = flightDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        // Always include the date in the time display now that lists are mixed
                        const displayTime = flightDate.toLocaleDateString([], {month: 'short', day: 'numeric'}) + ' • ' + departTime;

                        const returnFlightDate = f.return_at ? new Date(f.return_at) : null;
                        const arrivalTime = returnFlightDate ? returnFlightDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'TBD';
                        const displayReturn = returnFlightDate ? 
                                            returnFlightDate.toLocaleDateString([], {month: 'short', day: 'numeric'}) + ' • ' + arrivalTime : 
                                            arrivalTime;

                        return {
                            airline: f.airline, 
                            price: f.price,
                            departure: displayTime,
                            arrival: displayReturn,
                            duration: f.flight_number ? 'Flight ' + f.flight_number : 'Direct',
                            code: f.airline
                        };
                    });

                    renderFilters();
                    applyFilters();

                } else {
                    // Try fallback to Jetradar entirely if STILL empty
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('results').innerHTML = `
                    <div style="text-align:center; padding: 20px;">
                        <i class="fa-solid fa-plane-slash" style="font-size:40px; color:rgba(255,255,255,0.2); margin-bottom:16px;"></i>
                        <div style="color:rgba(255,255,255,0.7); font-size:16px; margin-bottom:8px;">No cached flights found for this route.</div>
                        <div style="color:rgba(255,255,255,0.4); font-size:13px; margin-bottom:20px;">For a full live search of all global airlines, please use the live engine.</div>
                        <button onclick="window.open('https://search.jetradar.com/flights/?origin_iata=${originCode}&destination_iata=${destCode}&depart_date=${document.getElementById('date').value}&return_date=${document.getElementById('return_date').value}&adults=${document.getElementById('passengers').value}&marker=${MARKER}', '_blank')" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600;">Search Live on JetRadar</button>
                    </div>`;
                }
            } catch (error) {
                console.error(error);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('results').innerHTML = '<div style="text-align:center; color:#ff3b30;">Error connecting to API.</div>';
            }
        }

        function openCheckout(f, originCode, destCode, passengers) {
            document.getElementById('chk-origin').innerText = originCode;
            document.getElementById('chk-dest').innerText = destCode;
            document.getElementById('chk-airline-name').innerText = AIRLINE_NAMES[f.code] || f.airline;
            document.getElementById('chk-logo').src = `http://pics.avs.io/200/200/${f.code}.png`;
            document.getElementById('chk-date').innerText = f.departure.split(' • ')[0];
            document.getElementById('chk-passengers').innerText = passengers;
            document.getElementById('chk-base-price').innerText = `$${f.price}`;
            document.getElementById('chk-total-price').innerText = `$${f.price}`;
            
            const url = `https://search.jetradar.com/flights/?origin_iata=${originCode}&destination_iata=${destCode}&depart_date=${document.getElementById('date').value}&return_date=${document.getElementById('return_date').value}&adults=${passengers}&marker=${MARKER}`;
            document.getElementById('chk-confirm-btn').onclick = () => window.open(url, '_blank');

            document.getElementById('drawer-overlay').classList.add('show');
            document.getElementById('checkout-drawer').classList.add('open');
        }

        function closeCheckout() {
            document.getElementById('drawer-overlay').classList.remove('show');
            document.getElementById('checkout-drawer').classList.remove('open');
        }

        function renderFilters() {
            if (currentFlights.length === 0) return;

            document.getElementById('filters-container').style.display = 'block';

            // 1. Setup Price Slider
            const prices = currentFlights.map(f => f.price);
            const minPrice = Math.min(...prices);
            const maxPrice = Math.max(...prices);
            
            const priceSlider = document.getElementById('price-filter');
            priceSlider.min = minPrice;
            priceSlider.max = maxPrice;
            priceSlider.value = maxPrice;
            document.getElementById('price-display').innerText = '$' + maxPrice;

            // 2. Setup Airline Logos
            const uniqueAirlines = [...new Set(currentFlights.map(f => f.code))];
            selectedAirlines = new Set(uniqueAirlines); // Initially all selected

            const airlineContainer = document.getElementById('airline-filters');
            airlineContainer.innerHTML = uniqueAirlines.map(code => {
                const logo = `http://pics.avs.io/200/200/${code}.png`;
                const name = AIRLINE_NAMES[code] || code;
                return `
                <div id="airline-btn-${code}" onclick="toggleAirline('${code}')" style="background: rgba(255,255,255,0.15); border: 2px solid #fff; border-radius: 16px; padding: 8px 16px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; white-space: nowrap;">
                    <img src="${logo}" style="width: 24px; height: 24px; border-radius: 12px; background: #fff; object-fit: contain;">
                    <span style="font-weight: 600; font-size: 13px;">${name}</span>
                </div>`;
            }).join('');
        }

        function toggleAirline(code) {
            const btn = document.getElementById(`airline-btn-${code}`);
            if (selectedAirlines.has(code)) {
                selectedAirlines.delete(code);
                btn.style.background = 'rgba(255,255,255,0.05)';
                btn.style.borderColor = 'rgba(255,255,255,0.1)';
                btn.style.opacity = '0.5';
            } else {
                selectedAirlines.add(code);
                btn.style.background = 'rgba(255,255,255,0.15)';
                btn.style.borderColor = '#fff';
                btn.style.opacity = '1';
            }
            applyFilters();
        }

        function applyFilters() {
            const maxPrice = parseInt(document.getElementById('price-filter').value);
            
            const filteredData = currentFlights.filter(f => {
                return f.price <= maxPrice && selectedAirlines.has(f.code);
            });
            
            renderFlights(filteredData, document.getElementById('origin').value, document.getElementById('destination').value);
        }

        function renderFlights(flights, origin, destination) {
            document.getElementById('loading').style.display = 'none';
            let html = fallbackBannerHtml;
            
            if (flights.length === 0) {
                html += `<div style="text-align:center; color: rgba(255,255,255,0.5); padding: 40px 0; font-weight: 500;">No flights match your filters.</div>`;
                document.getElementById('results').innerHTML = html;
                return;
            }
            
            flights.forEach(f => {
                // Live real airline logos from Aviasales
                const logo = `http://pics.avs.io/200/200/${f.code}.png`;
                
                html += `
                <div class="flight-card">
                    <div class="fc-header">
                        <div class="fc-airline">
                            <img src="${logo}" alt="${f.airline}" onerror="this.src='https://ui-avatars.com/api/?name=${f.code}&background=random&color=fff'">
                            <span style="font-weight: 700; color: #fff;">${AIRLINE_NAMES[f.code] || f.airline}</span>
                        </div>
                        <div class="fc-price">$${f.price}</div>
                    </div>
                    
                    <div class="fc-route">
                        <div class="fc-time-block">
                            <div class="fc-time">${f.departure}</div>
                            <div class="fc-airport">${origin}</div>
                        </div>
                        
                        <div class="fc-duration">
                            <span>${f.duration}</span>
                            <div class="fc-line">
                                <i class="fa-solid fa-plane"></i>
                            </div>
                            <span style="color:#fff; font-weight:600; margin-top:4px;">Direct</span>
                        </div>
                        
                        <div class="fc-time-block">
                            <div class="fc-time">${f.arrival}</div>
                            <div class="fc-airport">${destination}</div>
                        </div>
                    </div>
                    
                    <button class="fc-book-btn" onclick='openCheckout(${JSON.stringify(f)}, "${document.getElementById("origin_code").value}", "${document.getElementById("destination_code").value}", ${document.getElementById("passengers").value})'>Select Flight</button>
                </div>`;
            });
            
            document.getElementById('results').innerHTML = html;
        }
    </script>
</body>
</html>
