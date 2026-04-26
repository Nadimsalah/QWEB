<?php
define('FROM_UI', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Book Hotels - QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Calendar Library -->
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
        
        .header { display: flex; align-items: center; padding: 24px; justify-content: space-between; }
        .back-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.05); color: #fff; width: 44px; height: 44px; border-radius: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; font-size: 16px; }
        .back-btn:hover { background: rgba(255,255,255,0.1); }
        
        .content { padding: 20px; width: 100%; max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        
        .hero-title {
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 800; text-align: center; letter-spacing: -1.5px; line-height: 1.1;
            margin: 40px 0 16px 0; background: linear-gradient(180deg, #FFFFFF 0%, #B0B0B0 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-subtitle { text-align: center; color: rgba(255,255,255,0.6); font-size: clamp(16px, 2vw, 20px); margin-bottom: 40px; }

        .search-box { 
            background: rgba(20,20,20,0.4); border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 36px; padding: 8px; width: 100%; display: flex; flex-direction: column; gap: 8px; backdrop-filter: blur(20px);
        }
        .search-row { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .input-wrapper { 
            flex: 1; min-width: 200px; display: flex; align-items: center; 
            background: rgba(255,255,255,0.03); border-radius: 28px; padding: 0 20px; height: 64px; 
            border: 1px solid transparent; transition: all 0.2s; position: relative;
        }
        .input-wrapper:focus-within { background: rgba(20,17,236,0.05); border-color: rgba(20,17,236,0.5); }
        .input-wrapper i { color: rgba(255,255,255,0.4); font-size: 20px; margin-right: 16px; width: 24px; text-align: center; }
        .input-wrapper input { background: transparent; border: none; color: #fff; font-size: 16px; font-weight: 600; font-family: 'Inter', sans-serif; width: 100%; outline: none; }
        .input-wrapper input::placeholder { color: rgba(255,255,255,0.3); font-weight: 500; }
        
        .search-btn { 
            background: #1411EC; color: #fff; border: none; border-radius: 28px; width: 64px; height: 64px; 
            display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(20,17,236,0.3);
        }
        .search-btn:hover { background: #0c09d6; transform: translateY(-2px); box-shadow: 0 15px 30px rgba(20,17,236,0.4); }
        .search-btn i { font-size: 20px; }

        .hotel-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; overflow: hidden; display: flex; gap: 16px; padding: 12px; transition: all 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 16px; width: 100%; }
        .hotel-card:hover { transform: translateY(-4px); background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.15); box-shadow: 0 15px 40px rgba(0,0,0,0.5), 0 0 20px rgba(20,17,236,0.1); }
        .hc-img-wrapper { width: 160px; height: 160px; border-radius: 16px; overflow: hidden; flex-shrink: 0; position: relative; }
        .hc-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .hotel-card:hover .hc-img { transform: scale(1.05); }
        .hc-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 4px 0; }
        .hc-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .hc-name { font-size: 18px; font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 4px; }
        .hc-stars { color: #f1c40f; font-size: 12px; margin-bottom: 8px; }
        .hc-location { font-size: 13px; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 6px; }
        .hc-price-block { text-align: right; }
        .hc-price { font-size: 24px; font-weight: 800; color: #fff; }
        .hc-price-sub { font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 600; }
        .hc-footer { display: flex; justify-content: space-between; align-items: flex-end; }
        .hc-features { display: flex; gap: 8px; flex-wrap: wrap; }
        .hc-feature { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 4px 8px; font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.7); }
        .hc-book-btn { background: #1411EC; color: #fff; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .hc-book-btn:hover { background: #0c09d6; }
        
        #loading { display: none; text-align: center; padding: 40px; color: rgba(255,255,255,0.7); width: 100%; margin-top: 20px; }
        #results { width: 100%; margin-top: 32px; }

        @media (max-width: 600px) {
            .search-row { flex-direction: column; }
            .search-btn { width: 100%; border-radius: 20px; }
            .hotel-card { flex-direction: column; }
            .hc-img-wrapper { width: 100%; height: 200px; }
            .hc-header { flex-direction: column; gap: 12px; }
            .hc-price-block { text-align: left; }
            .hc-footer { flex-direction: column; align-items: stretch; gap: 16px; margin-top: 12px; }
            .hc-book-btn { width: 100%; }
        }
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
        <h1 class="hero-title">Book Hotels.</h1>
        <p class="hero-subtitle">Discover the best stays globally—powered by Travelpayouts.</p>
        
        <div class="search-box">
            <div class="search-row">
                <div class="input-wrapper" style="flex: 2;">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="destination" placeholder="Where to? (e.g., Dubai, Paris)">
                </div>
            </div>
            <div class="search-row">
                <div class="input-wrapper">
                    <i class="fa-regular fa-calendar-check"></i>
                    <input type="text" id="checkin" placeholder="Check-in">
                </div>
                <div class="input-wrapper">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <input type="text" id="checkout" placeholder="Check-out">
                </div>
                <div class="input-wrapper" style="flex: 0.5;">
                    <i class="fa-solid fa-user-group"></i>
                    <input type="number" id="guests" min="1" max="9" value="2" placeholder="Guests">
                </div>
                <button class="search-btn" onclick="searchHotels()">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <div id="loading">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: #1411EC; margin-bottom: 16px; display: block;"></i>
            <div style="font-weight: 600; font-size: 16px; color: #fff;">Scanning global hotel networks...</div>
            <div style="font-size: 13px;">Checking availability via Travelpayouts API</div>
        </div>
        
        <div id="results"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Modern Flatpickr Calendar
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const nextWeek = new Date(today);
            nextWeek.setDate(nextWeek.getDate() + 4);
            
            flatpickr("#checkin", {
                theme: "dark",
                defaultDate: tomorrow,
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: "true"
            });

            flatpickr("#checkout", {
                theme: "dark",
                defaultDate: nextWeek,
                minDate: tomorrow,
                dateFormat: "Y-m-d",
                disableMobile: "true"
            });
        });

        function searchHotels() {
            const dest = document.getElementById('destination').value.trim() || 'Paris';
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = document.getElementById('guests').value || 2;
            
            document.getElementById('results').innerHTML = '';
            document.getElementById('loading').style.display = 'block';
            
            // Fire async polling renderer
            renderHotels(dest, checkin, checkout, guests);
        }
        
        async function renderHotels(dest, checkin, checkout, guests) {
            const MARKER = '521631';
            const bookUrl = `https://search.hotellook.com/hotels?destination=${encodeURIComponent(dest)}&checkIn=${checkin}&checkOut=${checkout}&adults=${guests}&marker=${MARKER}`;
            
            try {
                // 1. Initialize the Search to get searchId
                const initResponse = await fetch('init_hotel_search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ destination: dest, checkIn: checkin, checkOut: checkout, guests: guests })
                });
                const initData = await initResponse.json();
                
                if (!initData.searchId) {
                    throw new Error("Invalid API Signature or missing Search ID");
                }
                
                const searchId = initData.searchId;
                
                // 2. Poll for results asynchronously
                const pollInterval = setInterval(async () => {
                    try {
                        const pollResponse = await fetch(`poll_hotel_search.php?searchId=${encodeURIComponent(searchId)}&dest=${encodeURIComponent(dest)}`);
                        const pollData = await pollResponse.json();
                        
                        if (pollData.status === 'Pending') {
                            // Still searching... keep polling
                            console.log("Travelpayouts API: Polling... (Pending)");
                            return;
                        }
                        
                        // Search Completed!
                        clearInterval(pollInterval);
                        
                        const hotels = pollData.result || [];
                        if (hotels.length === 0) {
                            document.getElementById('results').innerHTML = `<div style="text-align:center; padding: 40px; color: #fff;">No availability found for these dates. Try another search.</div>`;
                            return;
                        }
                        
                        let html = '';
                        hotels.forEach((h, idx) => {
                            let starsHtml = '';
                            for(let i=0; i<h.stars; i++) starsHtml += '<i class="fa-solid fa-star"></i>';
                            
                            let featuresHtml = h.features.map(f => `<div class="hc-feature">${f}</div>`).join('');
                            let demandBadge = idx === 0 ? `<div style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; color: #fff; z-index: 2;"><i class="fa-solid fa-fire" style="color: #ff4d4d; margin-right: 4px;"></i> High Demand</div>` : '';
                            
                            html += `
                            <div class="hotel-card">
                                <div class="hc-img-wrapper">
                                    ${demandBadge}
                                    <img src="${h.img}" class="hc-img" alt="${h.name}">
                                </div>
                                <div class="hc-info">
                                    <div class="hc-header">
                                        <div>
                                            <div class="hc-name">${h.name}</div>
                                            <div class="hc-stars">${starsHtml}</div>
                                            <div class="hc-location"><i class="fa-solid fa-map-pin"></i> ${dest} City Center</div>
                                        </div>
                                        <div class="hc-price-block">
                                            <div class="hc-price">$${h.price}</div>
                                            <div class="hc-price-sub">Per Night</div>
                                        </div>
                                    </div>
                                    <div class="hc-footer">
                                        <div class="hc-features">${featuresHtml}</div>
                                        <button class="hc-book-btn" onclick="window.open('${bookUrl}', '_blank')">Select Room</button>
                                    </div>
                                </div>
                            </div>
                            `;
                        });
                        
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('results').innerHTML = html;
                        
                    } catch (e) {
                        clearInterval(pollInterval);
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('results').innerHTML = `<div style="text-align:center; padding: 40px; color: #ff4d4d;">Polling error occurred.</div>`;
                    }
                }, 2000); // Poll every 2 seconds
                
            } catch (e) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('results').innerHTML = `<div style="text-align:center; padding: 40px; color: #ff4d4d;">Failed to initialize API search.</div>`;
            }
        }
    </script>
</body>
</html>
