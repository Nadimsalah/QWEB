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
    
    <div class="content">
        <h1 class="hero-title">Book Flights.</h1>
        <p class="hero-subtitle">Search hundreds of airlines—unified in a single experience.</p>
        
        <!-- Unified QOON Pill Search Form -->
        <div class="search-box">
            <!-- Trip Types -->
            <div style="display: flex; gap: 16px; padding: 4px 12px; margin-bottom: 4px; overflow-x: auto; white-space: nowrap;">
                <label style="color: #fff; font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="trip_type" value="oneway" checked style="accent-color: #fff;"> One Way
                </label>
                <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="trip_type" value="roundtrip" style="accent-color: #fff;"> Round Trip
                </label>
                <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="trip_type" value="multicity" style="accent-color: #fff;"> Multi City
                </label>
                <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="trip_type" value="advance" style="accent-color: #fff;"> Advance Search
                </label>
                <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer; color: #ff9f0a; font-weight: 600;">
                    <input type="radio" name="trip_type" value="ai" style="accent-color: #ff9f0a;"> Flight Booking with AI
                </label>
            </div>

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
                <div class="input-wrapper" style="flex: 0.5; min-width: 140px; cursor: pointer;" onclick="togglePaxSelector(event)">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="pax-display" value="1 Adult" readonly style="cursor: pointer; pointer-events: none;">
                    
                    <div id="pax-selector" style="display: none; position: absolute; top: 70px; left: 0; background: #111; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 16px; width: 240px; z-index: 1000; box-shadow: 0 20px 40px rgba(0,0,0,0.5); cursor: default;" onclick="event.stopPropagation()">
                        <!-- Adults -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: #fff;">Adults</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.4);">12+ years</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <button onclick="updatePax('adt', -1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">-</button>
                                <span id="pax-adt" style="font-weight: 600; width: 12px; text-align: center;">1</span>
                                <button onclick="updatePax('adt', 1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">+</button>
                            </div>
                        </div>
                        <!-- Children -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: #fff;">Children</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.4);">2-11 years</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <button onclick="updatePax('chd', -1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">-</button>
                                <span id="pax-chd" style="font-weight: 600; width: 12px; text-align: center;">0</span>
                                <button onclick="updatePax('chd', 1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">+</button>
                            </div>
                        </div>
                        <!-- Infants -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: #fff;">Infants</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.4);">Under 2 years</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <button onclick="updatePax('inf', -1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">-</button>
                                <span id="pax-inf" style="font-weight: 600; width: 12px; text-align: center;">0</span>
                                <button onclick="updatePax('inf', 1)" style="width: 28px; height: 28px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; cursor: pointer;">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Advanced Options -->
            <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 16px;">
                
                <!-- Fare Type Selector -->
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 24px;">
                    <span style="color: #fff; font-size: 14px; font-weight: 600;">Select a fare type</span>
                    <label style="color: rgba(255,255,255,0.8); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="fare_type" value="regular" checked style="accent-color: #fff;"> Regular Fares
                    </label>
                    <label style="color: rgba(255,255,255,0.8); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="fare_type" value="student" style="accent-color: #fff;"> Student Fares
                    </label>
                </div>

                <!-- Filters -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; padding: 0 4px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Cross Selling
                    </label>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Preferred Airline
                    </label>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Markup (In Percentage)
                    </label>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Direct Flight
                    </label>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Flexible Dates +/- 3
                    </label>
                    <label style="color: rgba(255,255,255,0.7); font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #fff;"> Select Flights Separately
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-start; margin-top: 8px;">
                    <button class="search-btn" style="width: auto; padding: 0 24px; border-radius: 24px; gap: 8px;" onclick="searchFlights()">
                        <i class="fa-solid fa-search"></i> Search
                    </button>
                </div>
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
        


    <!-- Modern Alert -->
    <div id="modern-alert" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
        <div style="background: #111; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 32px; width: 100%; max-width: 350px; text-align: center; display: flex; flex-direction: column; gap: 16px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 40px; color: #ff9f0a;"></i>
            <div id="alert-text" style="color: #fff; font-size: 15px; font-weight: 500;"></div>
            <button onclick="document.getElementById('modern-alert').style.display='none'" style="background: #fff; color: #000; font-weight: 700; border: none; border-radius: 12px; padding: 12px; cursor: pointer; margin-top: 8px;">Got it</button>
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

        let paxCounts = { adt: 1, chd: 0, inf: 0 };
        
        function togglePaxSelector(e) {
            e.stopPropagation();
            const selector = document.getElementById('pax-selector');
            selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
        }
        
        function updatePax(type, delta) {
            const newVal = paxCounts[type] + delta;
            if (newVal < 0) return;
            if (type === 'adt' && newVal < 1) return; // Min 1 adult
            
            paxCounts[type] = newVal;
            document.getElementById(`pax-${type}`).innerText = newVal;
            
            let displayParts = [];
            if (paxCounts.adt > 0) displayParts.push(`${paxCounts.adt} Adult${paxCounts.adt > 1 ? 's' : ''}`);
            if (paxCounts.chd > 0) displayParts.push(`${paxCounts.chd} Child${paxCounts.chd > 1 ? 'ren' : ''}`);
            if (paxCounts.inf > 0) displayParts.push(`${paxCounts.inf} Infant${paxCounts.inf > 1 ? 's' : ''}`);
            
            document.getElementById('pax-display').value = displayParts.join(', ');
        }

        document.addEventListener('click', () => {
            const selector = document.getElementById('pax-selector');
            if (selector) selector.style.display = 'none';
        });

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

            const url = `flight_results.php?origin=${originCode}&dest=${destCode}&date=${date}&return=${returnDate}&class=${currentTripClass}&adt=${paxCounts.adt}&chd=${paxCounts.chd}&inf=${paxCounts.inf}`;
            window.location.href = url;
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
    </script>
</body>
</html>
