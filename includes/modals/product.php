<style>
:root { --primary: #f50057; }
        /* ╔══════════════════════════════════╗
           ║  RIGHT-DRAWER CHECKOUT SYSTEM    ║
           ╚══════════════════════════════════╝ */
        #co-drawer-overlay {
            position: fixed; inset: 0; z-index: 200000;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none; opacity: 0;
            transition: opacity 0.35s ease;
        }
        #co-drawer {
            position: fixed; top: 0; right: 0; bottom: 0;
            width: 100%; max-width: 480px;
            background: #0a0a0f;
            border-left: 1px solid rgba(255,255,255,0.08);
            z-index: 200001;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        #co-drawer.open { transform: translateX(0); }

        /* ── Steps Navigation ── */
        .co-steps { display: flex; padding: 0 20px; gap: 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .co-step-btn {
            flex: 1; padding: 14px 0; font-size: 12px; font-weight: 700;
            color: rgba(255,255,255,0.3); background: none; border: none;
            border-bottom: 2px solid transparent; cursor: pointer;
            transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px;
            display: flex; flex-direction: column; align-items: center; gap: 4px;
        }
        .co-step-btn.active { color: #fff; border-bottom-color: #f50057; }
        .co-step-btn .step-num {
            width: 22px; height: 22px; border-radius: 50%;
            background: rgba(255,255,255,0.08); font-size: 11px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .co-step-btn.active .step-num { background: #f50057; color: #fff; }
        .co-step-btn.done .step-num { background: #2ecc71; color: #fff; }
        .co-step-btn.done { color: #2ecc71; border-bottom-color: #2ecc71; }

        /* ── Step Panels ── */
        .co-step-panel { display: none; flex: 1; overflow-y: auto; padding: 20px; }
        .co-step-panel.active { display: block; }
        .co-step-panel::-webkit-scrollbar { width: 4px; }
        .co-step-panel::-webkit-scrollbar-track { background: transparent; }
        .co-step-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }

        /* ── Drawer Header ── */
        .co-drawer-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 20px 0;
        }
        .co-drawer-title { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .co-close-btn {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.08); border: none; color: #fff;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: 0.2s;
        }
        .co-close-btn:hover { background: rgba(255,255,255,0.15); }

        /* ── Order Item ── */
        .co2-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px; border-radius: 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            margin-bottom: 10px;
        }
        .co2-img {
            width: 60px; height: 60px; border-radius: 12px;
            object-fit: cover; background: #1a1a1a; flex-shrink: 0;
        }
        .co2-name { font-size: 15px; font-weight: 700; color: #fff; }
        .co2-ext { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 2px; }
        .co2-price { font-size: 15px; font-weight: 800; color: #f50057; }
        .co2-qty {
            margin-left: auto; background: rgba(255,255,255,0.08);
            border-radius: 99px; padding: 4px 12px;
            font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
        }

        /* ── Price Breakdown ── */
        .price-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px; color: rgba(255,255,255,0.6);
        }
        .price-row:last-child { border-bottom: none; }
        .price-row span:last-child { color: #fff; font-weight: 700; }
        .price-row.total span { font-size: 18px; font-weight: 800; color: #fff; }
        .price-row.total span:first-child { color: rgba(255,255,255,0.8); font-size: 14px; }
        .price-row.platform span:last-child { color: #f50057; }

        /* ── Address Types ── */
        .addr-type-grid { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .addr-type-chip {
            flex: 1; min-width: 80px; padding: 14px 10px;
            border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.6);
            font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .addr-type-chip i { font-size: 20px; }
        .addr-type-chip.active {
            border-color: #f50057; background: rgba(245,0,87,0.1); color: #fff;
        }
        .addr-type-chip.active i { color: #f50057; }

        /* ── Saved Address Card ── */
        .saved-addr-card {
            padding: 16px; border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            display: flex; align-items: center; gap: 14px;
            cursor: pointer; transition: 0.2s; margin-bottom: 10px;
        }
        .saved-addr-card.selected { border-color: #f50057; background: rgba(245,0,87,0.08); }
        .saved-addr-icon {
            width: 42px; height: 42px; border-radius: 50%;
            background: rgba(245,0,87,0.15); color: #f50057;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
            flex-shrink: 0;
        }
        .new-loc-btn {
            width: 100%; padding: 14px; border-radius: 16px;
            border: 1px dashed rgba(255,255,255,0.2);
            background: transparent; color: rgba(255,255,255,0.5);
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.2s;
        }
        .new-loc-btn:hover { border-color: #f50057; color: #f50057; }

        /* ── Map for new location ── */
        #co-map-container {
            display: none; border-radius: 20px; overflow: hidden;
            height: 220px; position: relative; margin-top: 14px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        #co-leaflet-map { width: 100%; height: 100%; }
        .co-map-pin {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -100%);
            font-size: 32px; color: #f50057;
            z-index: 999; pointer-events: none;
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        .co-map-note {
            font-size: 12px; color: rgba(255,255,255,0.4);
            text-align: center; margin-top: 8px;
        }

        /* ── Note Input ── */
        .co-note-input {
            width: 100%; padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; color: #fff; font-size: 14px;
            font-family: inherit; resize: none; outline: none;
            transition: border-color 0.2s;
        }
        .co-note-input:focus { border-color: rgba(255,255,255,0.3); }
        .co-note-input::placeholder { color: rgba(255,255,255,0.3); }

        /* ── Payment Cards ── */
        .pay-card {
            padding: 18px; border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            cursor: pointer; transition: all 0.25s;
            margin-bottom: 12px; position: relative; overflow: hidden;
        }
        .pay-card.active { border-color: #f50057; background: rgba(245,0,87,0.06); }
        .pay-card-row { display: flex; align-items: center; gap: 14px; }
        .pay-card-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .pay-card-label { font-size: 16px; font-weight: 700; color: #fff; }
        .pay-card-sub { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 2px; }
        .pay-card-check {
            margin-left: auto; width: 24px; height: 24px;
            border-radius: 50%; border: 2px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: 0.2s;
        }
        .pay-card.active .pay-card-check {
            background: #f50057; border-color: #f50057; color: #fff;
        }
        /* QOON Pay special card */
        .qpay-checkout-card {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, rgba(245,0,87,0.25) 0%, rgba(120,0,120,0.2) 50%, rgba(44,181,232,0.15) 100%);
            border: 1px solid rgba(245,0,87,0.3);
        }
        .qpay-checkout-card::before {
            content: ''; position: absolute;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(245,0,87,0.4) 0%, transparent 70%);
            top: -80px; right: -60px; border-radius: 50%;
        }
        .qpay-bal-chip {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.1); border-radius: 99px;
            padding: 6px 14px; font-size: 14px; font-weight: 800; color: #fff;
            margin-top: 10px; position: relative; z-index: 1;
        }
        /* Bank Card */
        .bank-card-visual {
            margin-top: 12px; padding: 14px;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);
            display: none;
        }
        .bank-card-visual.show { display: block; }
        .bank-input {
            width: 100%; padding: 12px 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; color: #fff; font-size: 14px;
            font-family: inherit; outline: none; margin-bottom: 10px;
        }
        .bank-input:focus { border-color: rgba(255,255,255,0.3); }
        .bank-input::placeholder { color: rgba(255,255,255,0.3); }

        /* ── Bottom CTA Bar ── */
        .co-cta-bar {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            background: #0a0a0f;
        }
        .co-next-btn {
            width: 100%; padding: 16px;
            border-radius: 18px; border: none;
            background: #f50057; color: #fff;
            font-size: 16px; font-weight: 800;
            cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .co-next-btn:hover { background: #d4004d; transform: translateY(-1px); }
        .co-next-btn:active { transform: scale(0.98); }
        .co-next-btn:disabled { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.3); transform: none; cursor: not-allowed; }
        .co-next-btn.green { background: #2ecc71; }
        .co-next-btn.green:hover { background: #27ae60; }

        /* ── Section Label ── */
        .co-section-lbl {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.35);
            text-transform: uppercase; letter-spacing: 1px; margin: 18px 0 10px;
        }

    </style>

    <!-- RIGHT-DRAWER CHECKOUT -->
    <div id="co-drawer-overlay" onclick="closeCheckoutDrawer()"></div>
    <div id="co-drawer">

        <!-- Header -->
        <div class="co-drawer-header">
            <div class="co-drawer-title">Checkout</div>
            <button class="co-close-btn" onclick="closeCheckoutDrawer()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- Step Nav -->
        <div class="co-steps">
            <button class="co-step-btn active" id="step-nav-1" onclick="goToStep(1)">
                <span class="step-num">1</span> Order
            </button>
            <button class="co-step-btn" id="step-nav-2" onclick="goToStep(2)">
                <span class="step-num">2</span> Delivery
            </button>
            <button class="co-step-btn" id="step-nav-3" onclick="goToStep(3)">
                <span class="step-num">3</span> Payment
            </button>
        </div>

        <!-- ─── STEP 1: Order Summary ─── -->
        <div class="co-step-panel active" id="co-panel-1">
            <div class="co-section-lbl">Your Items</div>
            <div id="co2-items-list"></div>

            <div class="co-section-lbl" style="margin-top: 24px;">Price Breakdown</div>
            <div style="background: rgba(255,255,255,0.03); border-radius: 18px; border: 1px solid rgba(255,255,255,0.07); padding: 4px 16px;">
                <div class="price-row">
                    <span>Subtotal</span>
                    <span id="co-subtotal">— MAD</span>
                </div>
                <div class="price-row platform">
                    <span><i class="fa-solid fa-circle-info" style="font-size:11px; margin-right:4px;"></i> Platform Fee</span>
                    <span id="co-platform-fee">Loading...</span>
                </div>
                <div class="price-row total">
                    <span>Total</span>
                    <span id="co-total-all">— MAD</span>
                </div>
            </div>
        </div>

        <!-- ─── STEP 2: Delivery Address ─── -->
        <div class="co-step-panel" id="co-panel-2">
            <div class="co-section-lbl">Address Type</div>
            <div class="addr-type-grid">
                <div class="addr-type-chip active" onclick="selectAddrType(this,'Home')">
                    <i class="fa-solid fa-house"></i> Home
                </div>
                <div class="addr-type-chip" onclick="selectAddrType(this,'Work')">
                    <i class="fa-solid fa-briefcase"></i> Work
                </div>
                <div class="addr-type-chip" onclick="selectAddrType(this,'Hotel')">
                    <i class="fa-solid fa-building"></i> Hotel
                </div>
                <div class="addr-type-chip" onclick="selectAddrType(this,'Other')">
                    <i class="fa-solid fa-location-dot"></i> Other
                </div>
            </div>

            <div class="co-section-lbl">Saved Addresses</div>
            <div id="co-saved-addresses">
                <div style="text-align:center; padding:24px; color:rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                </div>
            </div>

            <button class="new-loc-btn" onclick="toggleNewLocation()">
                <i class="fa-solid fa-plus"></i> Use a new location on map
            </button>

            <!-- Map for new location -->
            <div id="co-map-container">
                <div id="co-leaflet-map"></div>
                <div class="co-map-pin"><i class="fa-solid fa-location-dot"></i></div>
                <!-- Locate Button -->
                <button id="co-locate-btn" onclick="locateOnMap()" title="Use my current location" style="
                    position: absolute; bottom: 14px; right: 14px;
                    width: 44px; height: 44px; border-radius: 50%;
                    background: #0a0a0f; border: 2px solid rgba(255,255,255,0.15);
                    color: #fff; display: flex; align-items: center; justify-content: center;
                    font-size: 18px; cursor: pointer; z-index: 800;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.6);
                    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
                ">
                    <i class="fa-solid fa-location-crosshairs" id="co-locate-icon"></i>
                </button>
            </div>
            <div class="co-map-note" id="co-map-note" style="display:none;">
                Slide the map to position the pin · <span style="color:#f50057; font-weight:700;">tap <i class="fa-solid fa-location-crosshairs" style="font-size:11px;"></i> to auto-locate</span>
            </div>

            <div class="co-section-lbl" style="margin-top:18px;">Order Note <span style="font-weight:400; text-transform:none; font-size:11px;">(optional)</span></div>
            <textarea class="co-note-input" id="co-order-note" rows="3" placeholder="E.g. Ring the bell, leave at door, etc."></textarea>
        </div>

        <!-- ─── STEP 3: Payment ─── -->
        <div class="co-step-panel" id="co-panel-3">
            <div class="co-section-lbl">Choose Payment Method</div>

            <!-- Cash -->
            <div class="pay-card active" id="pay-card-COD" onclick="selectNewPayment('COD')">
                <div class="pay-card-row">
                    <div class="pay-card-icon" style="background: rgba(46,204,113,0.15); color: #2ecc71;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <div class="pay-card-label">Cash on Delivery</div>
                        <div class="pay-card-sub">Pay when your order arrives</div>
                    </div>
                    <div class="pay-card-check"><i class="fa-solid fa-check" style="font-size:12px;"></i></div>
                </div>
            </div>

            <!-- QOON Pay -->
            <div class="pay-card qpay-checkout-card" id="pay-card-QOON" onclick="selectNewPayment('QOON')">
                <div class="pay-card-row">
                    <div class="pay-card-icon" style="background: rgba(245,0,87,0.2); color: #f50057; position:relative; z-index:1;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div style="position:relative; z-index:1;">
                        <div class="pay-card-label">QOON Pay</div>
                        <div class="pay-card-sub">Instant. No hassle.</div>
                    </div>
                    <div class="pay-card-check" style="position:relative; z-index:1;"><i class="fa-solid fa-check" style="font-size:12px;"></i></div>
                </div>
                <div style="position:relative; z-index:1; margin-top:12px; display:flex; align-items:center; gap:12px;">
                    <img src="qoon_pay_logo.png" alt="QOON Pay" style="height:22px; filter:brightness(0) invert(1); opacity:0.9;" onerror="this.style.display='none'">
                    <div class="qpay-bal-chip">
                        <i class="fa-solid fa-circle" style="font-size:8px; color:#2ecc71;"></i>
                        Balance: <strong id="co-qpay-balance">— MAD</strong>
                    </div>
                </div>
            </div>

            <!-- Bank Card -->
            <div class="pay-card" id="pay-card-CARD" onclick="selectNewPayment('CARD')">
                <div class="pay-card-row">
                    <div class="pay-card-icon" style="background: rgba(44,181,232,0.15); color: #2cb5e8;">
                        <i class="fa-regular fa-credit-card"></i>
                    </div>
                    <div>
                        <div class="pay-card-label">Bank Card</div>
                        <div class="pay-card-sub">Visa / Mastercard</div>
                    </div>
                    <div class="pay-card-check"><i class="fa-solid fa-check" style="font-size:12px;"></i></div>
                </div>
                <div class="bank-card-visual" id="bank-card-visual">
                    <input type="text" class="bank-input" id="card-number" placeholder="Card Number" maxlength="19" oninput="formatCardNum(this)">
                    <div style="display:flex; gap:10px;">
                        <input type="text" class="bank-input" id="card-expiry" placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this)" style="flex:1; margin-bottom:0;">
                        <input type="text" class="bank-input" id="card-cvv" placeholder="CVV" maxlength="3" style="width:80px; margin-bottom:0;">
                    </div>
                </div>
            </div>

            <!-- Total reminder -->
            <div style="margin-top: 20px; padding: 16px; border-radius: 18px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); display:flex; justify-content:space-between; align-items:center;">
                <span style="color:rgba(255,255,255,0.6); font-size:14px;">Total to pay</span>
                <span style="font-size:22px; font-weight:800; color:#fff;" id="co-final-total">— MAD</span>
            </div>
        </div>

        <!-- ─── CTA Bar ─── -->
        <div class="co-cta-bar">
            <button class="co-next-btn" id="co-cta-btn" onclick="handleCheckoutCTA()">
                <span id="co-cta-label">Continue</span>
                <i class="fa-solid fa-arrow-right" id="co-cta-icon"></i>
            </button>
        </div>
    </div>

<style>
        /* ╔══════════════════════════════════╗
           ║      MODERN PRODUCT MODAL        ║
           ╚══════════════════════════════════╝ */
        #pm-overlay {
            position: fixed; inset: 0; z-index: 100000;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
        }
        #pm-modal {
            width: 100%; max-width: 440px; max-height: 85vh;
            background: #0a0a0f; border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex; flex-direction: column;
            transform: translateY(30px) scale(0.95);
            opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.9);
            overflow: hidden; position: relative;
        }
        .pm-img-wrap {
            width: 100%; aspect-ratio: 1; position: relative;
            background: #111; flex-shrink: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        .pm-img-wrap.scrolled {
            aspect-ratio: 21/9;
            opacity: 0.8;
            border-bottom: 1px solid rgba(245, 0, 87, 0.3);
        }
        .pm-close {
            position: absolute; top: 20px; right: 20px;
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff; display: flex; justify-content: center; align-items: center;
            cursor: pointer; z-index: 100; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pm-close:hover { background: rgba(255, 0, 87, 0.8); transform: rotate(90deg); border-color: transparent; }

        .pm-scrollable {
            flex: 1; overflow-y: auto; padding: 28px;
            padding-bottom: 100px; scrollbar-width: none;
        }
        .pm-scrollable::-webkit-scrollbar { display: none; }

        .pm-title { font-size: 26px; font-weight: 800; line-height: 1.1; margin-bottom: 10px; color: #fff; letter-spacing: -0.5px; }
        .pm-price-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .pm-price { font-size: 24px; font-weight: 800; color: #f50057; }
        .pm-old-price { font-size: 16px; text-decoration: line-through; color: rgba(255, 255, 255, 0.3); }

        .pm-desc { font-size: 15px; color: rgba(255, 255, 255, 0.5); line-height: 1.6; margin-bottom: 28px; }

        .pm-section-title {
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.5px; margin-bottom: 14px; color: rgba(255, 255, 255, 0.4);
            display: flex; align-items: center; justify-content: space-between;
        }

        /* ── Modern Chips ── */
        .pm-chips { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 28px; }
        .pm-chip {
            padding: 14px 20px; background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px;
            font-size: 14px; font-weight: 700; cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); color: #fff;
            display: flex; flex-direction: column; align-items: center; min-width: 90px;
        }
        .pm-chip:hover { background: rgba(255, 255, 255, 0.06); transform: translateY(-2px); border-color: rgba(255,255,255,0.2); }
        .pm-chip.active { background: rgba(245, 0, 87, 0.1); border-color: #f50057; color: #f50057; box-shadow: 0 10px 20px rgba(245, 0, 87, 0.15); }
        .pm-chip span.ch-price { font-size: 11px; opacity: 0.6; font-weight: 600; margin-top: 4px; color: #fff; }
        .pm-chip.active span.ch-price { color: #f50057; opacity: 1; }

        /* ── Extra Modifiers ── */
        .pm-extra-card {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 20px;
            margin-bottom: 10px; cursor: pointer; transition: 0.25s;
        }
        .pm-extra-card:hover { background: rgba(255,255,255,0.06); }
        .pm-extra-card.active { border-color: #f50057; background: rgba(245,0,87,0.06); }
        .pm-extra-info { display: flex; flex-direction: column; }
        .pm-extra-name { font-size: 15px; font-weight: 700; color: #fff; }
        .pm-extra-price { font-size: 12px; color: #f50057; font-weight: 600; margin-top: 2px; }
        .pm-extra-check {
            width: 24px; height: 24px; border-radius: 8px;
            border: 2px solid rgba(255,255,255,0.1); display: flex;
            align-items: center; justify-content: center; font-size: 12px;
            color: transparent; transition: 0.2s;
        }
        .pm-extra-card.active .pm-extra-check { background: #f50057; border-color: #f50057; color: #fff; }

        /* ── Bottom Bar ── */
        .pm-bottom-bar {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 20px 28px; background: rgba(10, 10, 15, 0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex; gap: 16px; align-items: center;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5); z-index: 1000;
        }
        .pm-qty {
            display: flex; align-items: center; gap: 4px;
            background: rgba(255, 255, 255, 0.06); border-radius: 99px;
            height: 54px; padding: 4px; border: 1px solid rgba(255,255,255,0.05);
        }
        .pm-qty-btn {
            width: 46px; height: 46px; display: flex; justify-content: center; align-items: center;
            background: transparent; border: none; color: #fff; font-size: 18px;
            cursor: pointer; border-radius: 50%; transition: 0.2s;
        }
        .pm-qty-btn:hover { background: rgba(255,255,255,0.1); }
        .pm-qty-val { width: 32px; text-align: center; font-weight: 800; font-size: 17px; color: #fff; }
        
        .pm-add-btn {
            flex: 1; height: 54px; background: #f50057; border-radius: 99px;
            color: #fff; font-weight: 800; font-size: 16px; border: none;
            cursor: pointer; display: flex; justify-content: center; align-items: center;
            gap: 12px; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(245, 0, 87, 0.3);
        }
        .pm-add-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 35px rgba(245, 0, 87, 0.4); background: #ff1a6d; }
        .pm-add-btn:active { transform: scale(0.96); }

        /* --- DYNAMIC CART WIDGET & CHECKOUT --- */
        #floating-cart-widget {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(200%);
            width: calc(100% - 40px);
            max-width: 400px;
            background: var(--primary);
            color: #fff;
            padding: 14px 20px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 25px rgba(245, 0, 87, 0.4);
            z-index: 99000;
            transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.5s, visibility 0.5s;
            cursor: pointer;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
        }

        #floating-cart-widget.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }

        .fc-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 16px;
        }

        .fc-badge {
            background: #fff;
            color: var(--primary);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
        }

        #checkout-overlay {
            position: fixed;
            inset: 0;
            z-index: 100000;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        #checkout-modal {
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            background: #111;
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.8);
        }

        .co-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .co-title {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }

        .co-scrollable {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
        }

        .co-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .co-img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: #222;
            object-fit: cover;
        }

        .co-det {
            flex: 1;
        }

        .co-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }

        .co-ext {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 4px;
        }

        .co-price {
            font-size: 15px;
            font-weight: 800;
            color: var(--primary);
        }

        .co-qty {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 8px;
            border-radius: 8px;
        }

        .pay-method-btn {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: 0.2s;
            color: #fff;
            text-align: left;
        }

        .pay-method-btn:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .pay-method-btn.active {
            border-color: var(--primary);
            background: rgba(245, 0, 87, 0.1);
        }

        .pay-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
        }

        .pay-method-btn.active .pay-icon {
            background: var(--primary);
            color: #fff;
        }

        .pay-text {
            flex: 1;
            font-weight: 600;
            font-size: 15px;
        }

        .pay-check {
            font-size: 18px;
            color: var(--primary);
            opacity: 0;
            transform: scale(0.5);
            transition: 0.2s;
        }

        .pay-method-btn.active .pay-check {
            opacity: 1;
            transform: scale(1);
        }

        .co-checkout-btn {
            width: 100%;
            padding: 16px;
            background: #2ecc71;
            color: #000;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 800;
            font-size: 16px;
            transition: 0.2s;
            margin-top: 10px;
        }

        .co-checkout-btn:hover {
            background: #27ae60;
        }

        @keyframes vtoLogoPulse {
            0%,100% { transform: scale(1);   opacity: 1; }
            50%      { transform: scale(1.08); opacity: 0.8; }
        }
        @keyframes vtoBarShimmer {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }
        .vto-progress-wrap {
            position: absolute;
            inset: 0;
            z-index: 11;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 0 28px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
        }
        .vto-progress-logo {
            height: 38px;
            width: auto;
            object-fit: contain;
            animation: vtoLogoPulse 1.8s ease-in-out infinite;
            filter: brightness(0) invert(1);
        }
        .vto-progress-label {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.75);
            letter-spacing: 0.5px;
            text-align: center;
            min-height: 18px;
        }
        .vto-progress-track {
            width: 100%;
            max-width: 260px;
            height: 6px;
            background: rgba(255,255,255,0.15);
            border-radius: 99px;
            overflow: hidden;
        }
        .vto-progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 99px;
            background: linear-gradient(90deg, #f50057, #ff4081, #f50057);
            background-size: 200% 100%;
            animation: vtoBarShimmer 1.5s linear infinite;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .vto-progress-pct {
            font-size: 28px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -1px;
        }

        /* ╔════════════════════════════════╗
           ║      CART DRAWER STYLES          ║
           ╚════════════════════════════════╝ */
        #cart-drawer-overlay {
            position: fixed; inset: 0; z-index: 190000;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            display: none; opacity: 0;
            transition: opacity 0.3s ease;
        }
        #cart-drawer {
            position: fixed; top: 0; right: 0; bottom: 0;
            width: 100%; max-width: 420px;
            background: #0c0c14;
            border-left: 1px solid rgba(255,255,255,0.07);
            z-index: 190001;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #cart-drawer.open { transform: translateX(0); }
        .cart-drawer-hd {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .cart-drawer-title {
            font-size: 22px; font-weight: 800; color: #fff;
            letter-spacing: -0.5px;
            display: flex; align-items: center; gap: 10px;
        }
        .cart-count-badge {
            background: #f50057; color: #fff;
            border-radius: 99px; font-size: 12px; font-weight: 800;
            padding: 2px 9px; min-width: 24px; text-align: center;
        }
        .cart-close-btn {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.07); border: none;
            color: rgba(255,255,255,0.7); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; transition: 0.2s;
        }
        .cart-close-btn:hover { background: rgba(255,255,255,0.14); color: #fff; }
        .cart-items-scroll {
            flex: 1; overflow-y: auto; padding: 12px 16px;
        }
        .cart-items-scroll::-webkit-scrollbar { width: 3px; }
        .cart-items-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }
        /* Single cart item */
        .cart-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 14px;
            border-radius: 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 10px;
            transition: background 0.2s;
            position: relative;
        }
        .cart-item:hover { background: rgba(255,255,255,0.05); }
        .cart-item-img {
            width: 68px; height: 68px; border-radius: 14px;
            object-fit: cover; background: #1a1a1a; flex-shrink: 0;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .cart-item-no-img {
            width: 68px; height: 68px; border-radius: 14px;
            background: #1a1a1a; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.2); font-size: 22px;
        }
        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-name {
            font-size: 14px; font-weight: 700; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin-bottom: 4px;
        }
        .cart-item-meta {
            font-size: 11px; color: rgba(255,255,255,0.35);
            margin-bottom: 8px;
        }
        .cart-item-price {
            font-size: 16px; font-weight: 800; color: #f50057;
        }
        .cart-qty-ctrl {
            display: flex; align-items: center; gap: 0;
            background: rgba(255,255,255,0.06);
            border-radius: 99px; padding: 2px;
            flex-shrink: 0;
        }
        .cq-btn {
            width: 32px; height: 32px; border-radius: 50%;
            background: none; border: none; color: rgba(255,255,255,0.6);
            font-size: 15px; cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            transition: 0.15s;
        }
        .cq-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .cq-val {
            width: 26px; text-align: center; font-size: 14px;
            font-weight: 800; color: #fff;
        }
        .cart-remove-btn {
            position: absolute; top: 10px; right: 10px;
            width: 24px; height: 24px; border-radius: 50%;
            background: rgba(245,0,87,0.12); border: none;
            color: rgba(245,0,87,0.6); font-size: 11px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.2s;
        }
        .cart-remove-btn:hover { background: rgba(245,0,87,0.25); color: #f50057; }
        /* Empty state */
        .cart-empty-state {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            flex: 1; gap: 14px; color: rgba(255,255,255,0.25);
            padding: 40px 20px;
        }
        .cart-empty-icon {
            font-size: 56px;
            animation: cart-float 3s ease-in-out infinite;
        }
        @keyframes cart-float {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .cart-empty-txt { font-size: 18px; font-weight: 700; color: rgba(255,255,255,0.4); }
        .cart-empty-sub { font-size: 13px; color: rgba(255,255,255,0.2); }
        /* Footer */
        .cart-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
            background: #0c0c14;
        }
        .cart-summary-row {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 14px;
        }
        .cart-summary-lbl {
            font-size: 14px; color: rgba(255,255,255,0.5);
        }
        .cart-summary-val {
            font-size: 22px; font-weight: 800; color: #fff;
        }
        .cart-checkout-btn {
            width: 100%; padding: 17px; border-radius: 18px;
            background: #f50057; color: #fff; border: none;
            font-size: 16px; font-weight: 800; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.2s;
            box-shadow: 0 8px 30px rgba(245,0,87,0.35);
        }
        .cart-checkout-btn:hover { background: #d4004d; transform: translateY(-1px); }
        .cart-checkout-btn:active { transform: scale(0.98); }
        .cart-clear-btn {
            width: 100%; padding: 10px; background: none; border: none;
            color: rgba(255,255,255,0.2); font-size: 13px; cursor: pointer;
            margin-top: 8px; transition: 0.2s;
        }
        .cart-clear-btn:hover { color: rgba(245,0,87,0.6); }
</style>
<div id="pm-overlay">
    <div id="pm-modal">
        <!-- Intercepted layout will be injected dynamically -->
    </div>
</div>
<input type="file" id="vto-upload" accept="image/*" capture="environment" style="display:none;" onchange="handleVTOUpload(event)">
    <script>
        window.addEventListener('error', function(e) {
            console.error('GLOBAL ERROR:', e);
            // alert('Debug Error: ' + e.message);
        });
        
        let currentProductData = null;
        let currentModalPrice = 0;
        let currentModalQty = 1;
        let activeSizePriceAddon = 0;
        let selectedExtras = []; // Stores objects {name, price}
        
        let cartItems = [];

        function openProductModal(element) {
            // alert('openProductModal called');
            let data;
            try {
                const raw = element.getAttribute('data-product');
                data = JSON.parse(raw);
            } catch(e) {
                console.error('Product JSON parse error:', e, element.getAttribute('data-product'));
                return;
            }
            currentProductData = data;
            currentModalPrice = parseFloat(data.price);
            currentModalQty = 1;
            activeSizePriceAddon = 0;
            selectedExtras = [];

            const modal = document.getElementById('pm-modal');
            const overlay = document.getElementById('pm-overlay');
            
            let carouselHtml = '';
            if (data.images && data.images.length > 1) {
                let slides = '';
                let carouselDots = '';
                data.images.forEach((img, idx) => {
                    slides += `<div class="pm-slide" style="min-width: 100%; height: 100%; flex-shrink: 0; scroll-snap-align: start;">
                        <img src="${img}" style="width:100%; height:100%; object-fit: cover;">
                    </div>`;
                    carouselDots += `<div class="pm-dot" data-idx="${idx}" style="width:8px;height:8px;border-radius:50%;background:${idx === 0 ? '#fff' : 'rgba(255,255,255,0.3)'};transition:0.3s;flex-shrink:0;"></div>`;
                });
                
                carouselHtml = `
                    <div class="pm-carousel-container" style="position:relative; width:100%; height:100%;">
                        <div class="pm-slides" id="pm-slides" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; height: 100%; scrollbar-width: none; -ms-overflow-style: none;">
                            ${slides}
                        </div>
                        <div class="pm-dots" style="position: absolute; bottom: 16px; left: 0; right: 0; display: flex; flex-direction: row; justify-content: center; align-items: center; gap: 6px; z-index: 5;">
                            ${carouselDots}
                        </div>
                    </div>
                `;
            } else {
                carouselHtml = data.img ? `<img src="${data.img}" alt="" style="width:100%; height:100%; object-fit:cover;">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.2);"><i class="fa-solid fa-image" style="font-size:48px;"></i></div>`;
            }

            // Build Base Layout immediately
            modal.innerHTML = `
                <button class="pm-close" onclick="closeProductModal()"><i class="fa-solid fa-xmark"></i></button>
                <div class="pm-img-wrap" id="pm-img-wrap">
                    ${carouselHtml}
                </div>
                <div class="pm-scrollable" id="pm-scroll-area">
                    <div class="pm-title">${data.name}</div>
                    <div class="pm-price-row">
                        <div class="pm-price" id="pm-display-price">${data.price} MAD</div>
                        ${data.oldPrice ? `<div class="pm-old-price">${data.oldPrice} MAD</div>` : ''}
                    </div>
                    ${data.desc && data.desc.length > 2 && data.desc !== 'NONE' ? `<div class="pm-desc">${data.desc}</div>` : ''}
                    
                    <div id="pm-extras-container">
                        <!-- Shimmers while we fetch AJAX extras -->
                        <div class="pm-section-title">Loading Options... <div class="shimmer-box" style="width:20px;height:20px;border-radius:50%;"></div></div>
                    </div>

                    <div id="pm-static-extras">
                        ${(data.extra1 && data.extra1 !== '0' && data.extra1 !== 'NONE') ? `
                            <div class="pm-section-title">Add Extras</div>
                            <div class="pm-extra-card" onclick="toggleExtra(this, '${data.extra1}', ${data.extra1_p})">
                                <div class="pm-extra-info">
                                    <div class="pm-extra-name">${data.extra1}</div>
                                    <div class="pm-extra-price">+${data.extra1_p} MAD</div>
                                </div>
                                <div class="pm-extra-check"><i class="fa-solid fa-check"></i></div>
                            </div>
                        ` : ''}
                        ${(data.extra2 && data.extra2 !== '0' && data.extra2 !== 'NONE') ? `
                            ${(!data.extra1 || data.extra1 === '0') ? '<div class="pm-section-title">Add Extras</div>' : ''}
                            <div class="pm-extra-card" onclick="toggleExtra(this, '${data.extra2}', ${data.extra2_p})">
                                <div class="pm-extra-info">
                                    <div class="pm-extra-name">${data.extra2}</div>
                                    <div class="pm-extra-price">+${data.extra2_p} MAD</div>
                                </div>
                                <div class="pm-extra-check"><i class="fa-solid fa-check"></i></div>
                            </div>
                        ` : ''}
                    </div>

                    ${String(data.cat_id) === '85' ? `
                    <div class="pm-try-now" onclick="startVirtualTryOn()" style="margin-top: 16px; margin-bottom: 8px; background: linear-gradient(135deg, rgba(245,0,87,0.15), rgba(255,64,129,0.1)); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 18px; border-radius: 20px; color: #fff; text-align: center; font-weight: 800; font-size: 16px; cursor: pointer; box-shadow: 0 4px 20px rgba(245,0,87,0.3); border: 1px solid rgba(245,0,87,0.4); display: flex; align-items: center; justify-content: center; gap: 12px; position: relative; overflow: hidden; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                        <div style="position: absolute; inset: 0; background: linear-gradient(135deg, transparent 0%, rgba(245,0,87,0.2) 50%, transparent 100%); pointer-events: none;"></div>
                        <i class="fa-solid fa-camera" style="font-size: 22px; color: #f50057; text-shadow: 0 0 10px rgba(245,0,87,0.8); position: relative; z-index: 1;"></i>
                        <span style="position: relative; z-index: 1; letter-spacing: 0.5px;">Try Now — Virtual Try-On</span>
                    </div>
                    ` : ''}
                </div>
                <div class="pm-bottom-bar">
                    <div class="pm-qty">
                        <button class="pm-qty-btn" onclick="updateQty(-1)"><i class="fa-solid fa-minus"></i></button>
                        <div class="pm-qty-val" id="pm-qty-val">1</div>
                        <button class="pm-qty-btn" onclick="updateQty(1)"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <button class="pm-add-btn" onclick="addToCart()"><i class="fa-solid fa-bag-shopping"></i> <span id="pm-add-total">${data.price} MAD</span></button>
                </div>
            `;

            overlay.style.display = 'flex';
            setTimeout(() => {
                overlay.style.opacity = '1';
                modal.style.transform = 'translateY(0) scale(1)';
                modal.style.opacity = '1';
            }, 10);
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                const scrollArea = document.getElementById('pm-scroll-area');
                const imgWrap = document.getElementById('pm-img-wrap');
                if (scrollArea && imgWrap) {
                    scrollArea.addEventListener('scroll', () => {
                        if (scrollArea.scrollTop > 30) {
                            imgWrap.classList.add('scrolled');
                        } else {
                            imgWrap.classList.remove('scrolled');
                        }
                    });
                }

                const slidesContainer = document.getElementById('pm-slides');
                if (slidesContainer) {
                    slidesContainer.addEventListener('scroll', () => {
                        const index = Math.round(slidesContainer.scrollLeft / slidesContainer.clientWidth);
                        document.querySelectorAll('.pm-dot').forEach((dot, idx) => {
                            dot.style.background = (idx === index) ? '#fff' : 'rgba(255,255,255,0.3)';
                        });
                    });
                }
            }, 50);

            loadProductExtras(data.id);
        }

        function closeProductModal() {
            const overlay = document.getElementById('pm-overlay');
            const modal = document.getElementById('pm-modal');
            overlay.style.opacity = '0';
            modal.style.transform = 'translateY(30px) scale(0.95)';
            modal.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 400);
        }

        async function loadProductExtras(foodId) {
            try {
                const fd1 = new FormData(); fd1.append('FoodID', foodId);
                const req1 = fetch('GetProdColor.php', { method: 'POST', body: fd1 }).then(r => r.json()).catch(()=>({success:false}));
                const fd2 = new FormData(); fd2.append('FoodID', foodId);
                const req2 = fetch('GetProdSizes.php', { method: 'POST', body: fd2 }).then(r => r.json()).catch(()=>({success:false}));

                const [resColor, resSize] = await Promise.all([req1, req2]);
                
                let extrasHtml = '';
                if(resSize && resSize.success && resSize.data && resSize.data.length > 0) {
                    extrasHtml += `<div class="pm-section-title">Select Size</div><div class="pm-chips">`;
                    resSize.data.forEach((sz, idx) => {
                        const szPrice = parseFloat(sz.SizePrice || 0);
                        extrasHtml += `<div class="pm-chip size-chip ${idx===0 ? 'active' : ''}" onclick="selectSize(this, ${szPrice})">
                            ${sz.SizeName}
                            ${szPrice > 0 ? `<span class="ch-price">+${szPrice} MAD</span>` : `<span class="ch-price">Included</span>`}
                        </div>`;
                        if(idx === 0) activeSizePriceAddon = szPrice;
                    });
                    extrasHtml += `</div>`;
                }
                if(resColor && resColor.success && resColor.data && resColor.data.length > 0) {
                    extrasHtml += `<div class="pm-section-title">Select Color</div><div class="pm-chips">`;
                    resColor.data.forEach((cl, idx) => {
                        let colorHex = cl.ColorCode || cl.ColorName;
                        const bgStyle = colorHex.includes('#') ? `background:${colorHex};` : '';
                        extrasHtml += `<div class="pm-chip color-chip ${idx===0 ? 'active' : ''}" onclick="selectColor(this)" style="flex-direction:row; gap:8px;">
                            ${bgStyle ? `<div style="width:16px;height:16px;border-radius:50%;${bgStyle} border:1px solid rgba(255,255,255,0.2);"></div>` : ''}
                            ${cl.ColorName}
                        </div>`;
                    });
                    extrasHtml += `</div>`;
                }

                const extContainer = document.getElementById('pm-extras-container');
                if(extContainer) {
                    extContainer.style.opacity = 0;
                    setTimeout(() => {
                        extContainer.innerHTML = extrasHtml;
                        extContainer.style.transition = 'opacity 0.3s ease';
                        extContainer.style.opacity = 1;
                        updateModalPrice();
                    }, 200);
                }
            } catch (e) {
                document.getElementById('pm-extras-container').innerHTML = '';
            }
        }

        function selectSize(element, priceAddon) {
            document.querySelectorAll('.size-chip').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            activeSizePriceAddon = parseFloat(priceAddon);
            updateModalPrice();
        }

        function selectColor(element) {
            document.querySelectorAll('.color-chip').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        function toggleExtra(element, name, price) {
            element.classList.toggle('active');
            const isActive = element.classList.contains('active');
            if (isActive) {
                selectedExtras.push({ name, price: parseFloat(price) });
            } else {
                selectedExtras = selectedExtras.filter(ex => ex.name !== name);
            }
            updateModalPrice();
        }

        function updateQty(change) {
            currentModalQty = Math.max(1, currentModalQty + change);
            document.getElementById('pm-qty-val').innerText = currentModalQty;
            updateModalPrice();
        }

        function updateModalPrice() {
            let extrasAddon = selectedExtras.reduce((sum, ex) => sum + ex.price, 0);
            const totalItem = currentModalPrice + activeSizePriceAddon + extrasAddon;
            const grandTotal = totalItem * currentModalQty;
            
            const priceDisp = document.getElementById('pm-display-price');
            if (priceDisp) priceDisp.innerText = totalItem.toFixed(2).replace(/\.00$/, '') + ' MAD';
            
            const addTotal = document.getElementById('pm-add-total');
            if (addTotal) addTotal.innerText = grandTotal.toFixed(2).replace(/\.00$/, '') + ' MAD';
        }

        function addToCart() {
            const btn = document.querySelector('.pm-add-btn');
            const activeSizeEl = document.querySelector('.size-chip.active');
            const sizeName = activeSizeEl ? activeSizeEl.innerText.split('\n')[0].trim() : '';
            const activeColorEl = document.querySelector('.color-chip.active');
            const colorName = activeColorEl ? activeColorEl.innerText.trim() : '';
            
            let extrasAddon = selectedExtras.reduce((sum, ex) => sum + ex.price, 0);
            const unitPrice = currentModalPrice + activeSizePriceAddon + extrasAddon;

            const item = {
                id: currentProductData.id,
                name: currentProductData.name,
                img: currentProductData.img,
                qty: currentModalQty,
                unitPrice: unitPrice,
                size: sizeName,
                color: colorName,
                extras: selectedExtras,
                totalPrice: unitPrice * currentModalQty
            };

            cartItems.push(item);
            updateCartWidget();

            const originalHTML = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-check"></i> Added`;
            btn.style.background = '#2ecc71';
            setTimeout(() => {
                closeProductModal();
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 300);
            }, 500);
        }

        function updateCartWidget() {
            const widget = document.getElementById('floating-cart-widget');
            if(cartItems.length === 0) {
                widget.classList.remove('visible');
                return;
            }
            
            let totalItems = 0;
            let totalPrice = 0;
            cartItems.forEach(i => {
                totalItems += i.qty;
                totalPrice += i.totalPrice;
            });

            document.getElementById('fc-count').innerText = totalItems;
            document.getElementById('fc-total').innerText = totalPrice.toFixed(2).replace(/\.00$/, '') + ' MAD';
            widget.classList.add('visible');

            // If cart drawer is open, refresh it live
            const drawer = document.getElementById('cart-drawer');
            if (drawer && drawer.classList.contains('open')) renderCartDrawer();
        }

        /* ═══════════════════════════════════════════════
           CART DRAWER SYSTEM
        ═══════════════════════════════════════════════ */

        function openCartDrawer() {
            if (cartItems.length === 0) return;
            renderCartDrawer();
            const overlay = document.getElementById('cart-drawer-overlay');
            const drawer  = document.getElementById('cart-drawer');
            overlay.style.display = 'block';
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                drawer.classList.add('open');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeCartDrawer() {
            const overlay = document.getElementById('cart-drawer-overlay');
            const drawer  = document.getElementById('cart-drawer');
            overlay.style.opacity = '0';
            drawer.classList.remove('open');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 380);
        }

        function renderCartDrawer() {
            const container = document.getElementById('cart-items-scroll');
            const badge     = document.getElementById('cart-badge');
            const footerTotal = document.getElementById('cart-footer-total');

            if (!container) return;

            // Empty state
            if (cartItems.length === 0) {
                container.innerHTML = `
                    <div class="cart-empty-state">
                        <div class="cart-empty-icon">🛍️</div>
                        <div class="cart-empty-txt">Your cart is empty</div>
                        <div class="cart-empty-sub">Add some items from the store</div>
                    </div>`;
                if (badge) badge.textContent = '0';
                if (footerTotal) footerTotal.textContent = '0 MAD';
                return;
            }

            let totalItems = 0, totalPrice = 0;
            let html = '';

            cartItems.forEach((item, idx) => {
                totalItems += item.qty;
                totalPrice += item.totalPrice;

                const imgHtml = item.img
                    ? `<img class="cart-item-img" src="${item.img}" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                       <div class="cart-item-no-img" style="display:none;"><i class="fa-solid fa-image"></i></div>`
                    : `<div class="cart-item-no-img"><i class="fa-solid fa-image"></i></div>`;

                const extrasArr = [];
                if (item.size) extrasArr.push(item.size);
                if (item.color) extrasArr.push(item.color);
                if (item.extras && item.extras.length > 0) item.extras.forEach(ex => extrasArr.push(ex.name));
                const meta = extrasArr.join(' · ');

                html += `
                    <div class="cart-item" id="cart-item-${idx}">
                        <button class="cart-remove-btn" onclick="cartRemoveItem(${idx})" title="Remove">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        ${imgHtml}
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            ${meta ? `<div class="cart-item-meta">${meta}</div>` : ''}
                            <div class="cart-item-price">${item.totalPrice.toFixed(2).replace(/\.00$/, '')} MAD</div>
                        </div>
                        <div class="cart-qty-ctrl">
                            <button class="cq-btn" onclick="cartChangeQty(${idx}, -1)"><i class="fa-solid fa-minus"></i></button>
                            <span class="cq-val" id="cq-val-${idx}">${item.qty}</span>
                            <button class="cq-btn" onclick="cartChangeQty(${idx}, 1)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>`;
            });

            container.innerHTML = html;
            if (badge) badge.textContent = totalItems;
            if (footerTotal) footerTotal.textContent = totalPrice.toFixed(2).replace(/\.00$/, '') + ' MAD';
        }

        function cartChangeQty(idx, delta) {
            if (!cartItems[idx]) return;
            cartItems[idx].qty = Math.max(1, cartItems[idx].qty + delta);
            cartItems[idx].totalPrice = cartItems[idx].unitPrice * cartItems[idx].qty;
            renderCartDrawer();
            updateCartWidget();
        }

        function cartRemoveItem(idx) {
            const el = document.getElementById('cart-item-' + idx);
            if (el) {
                el.style.transition = 'opacity 0.2s, transform 0.2s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    cartItems.splice(idx, 1);
                    renderCartDrawer();
                    updateCartWidget();
                    if (cartItems.length === 0) closeCartDrawer();
                }, 200);
            }
        }

        function clearCart() {
            cartItems = [];
            updateCartWidget();
            closeCartDrawer();
        }

        let selectedPaymentMethod = 'COD';

        /* ═══════════════════════════════════════════════
           NEW RIGHT-DRAWER CHECKOUT SYSTEM
        ═══════════════════════════════════════════════ */
        let coCurrentStep = 1;
        let coPlatformFee = 0;
        let coSubtotal = 0;
        let coDeliveryLat = null;
        let coDeliveryLon = null;
        let coDeliveryMap = null;
        let coAddrType = 'Home';
        let coUseNewLocation = false;
        let coSelectedAddr = null;

        function openCheckoutModal() {
            // Alias — open the new drawer
            openCheckoutDrawer();
        }

        function openCheckoutDrawer() {
            if (cartItems.length === 0) return;

            const uid = getCookie('qoon_user_id');
            if (!uid || uid === '0') {
                localStorage.setItem('qoon_pending_cart_<?= $shopId ?>', JSON.stringify(cartItems));
                window.location.href = 'index.php?auth_required=1&return_to=' + encodeURIComponent(window.location.href);
                return;
            }

            // Reset to step 1
            coCurrentStep = 1;
            coUseNewLocation = false;
            coSelectedAddr = null;
            goToStep(1);

            // Populate items
            coSubtotal = 0;
            let html = '';
            cartItems.forEach(item => {
                coSubtotal += item.totalPrice;
                let ext = [];
                if (item.size && item.size !== 'null') ext.push(item.size);
                if (item.color && item.color !== 'null') ext.push(item.color);
                if (item.extras && item.extras.length > 0) item.extras.forEach(ex => ext.push(ex.name));
                html += `
                <div class="co2-item">
                    ${item.img ? `<img src="${item.img}" class="co2-img">` : `<div class="co2-img" style="display:flex;align-items:center;justify-content:center;color:#333;"><i class="fa-solid fa-image" style="font-size:24px;"></i></div>`}
                    <div style="flex:1; min-width:0;">
                        <div class="co2-name">${item.name}</div>
                        ${ext.length > 0 ? `<div class="co2-ext">${ext.join(' · ')}</div>` : ''}
                        <div class="co2-price">${item.unitPrice.toFixed(2)} MAD</div>
                    </div>
                    <div class="co2-qty">×${item.qty}</div>
                </div>`;
            });
            document.getElementById('co2-items-list').innerHTML = html;
            document.getElementById('co-subtotal').textContent = coSubtotal.toFixed(2) + ' MAD';
            document.getElementById('co-total-all').textContent = '...';
            document.getElementById('co-final-total').textContent = '...';

            // Disable CTA until fees load
            const ctaBtn = document.getElementById('co-cta-btn');
            const ctaLabel = document.getElementById('co-cta-label');
            const ctaIcon = document.getElementById('co-cta-icon');
            
            if (ctaBtn) ctaBtn.disabled = true;
            if (ctaLabel) ctaLabel.textContent = "Loading Fees...";
            if (ctaIcon) ctaIcon.className = "fa-solid fa-circle-notch fa-spin";

            // Load platform fee
            loadPlatformFee().then(() => {
                // Ensure we haven't changed steps while loading
                if (coCurrentStep === 1) {
                    if (ctaBtn) ctaBtn.disabled = false;
                    if (ctaLabel) ctaLabel.textContent = "Go to Delivery";
                    if (ctaIcon) ctaIcon.className = "fa-solid fa-arrow-right";
                }
            });

            // Load saved addresses
            loadSavedAddresses();

            // Open drawer
            const overlay = document.getElementById('co-drawer-overlay');
            const drawer = document.getElementById('co-drawer');
            overlay.style.display = 'block';
            setTimeout(() => {
                overlay.style.opacity = '1';
                drawer.classList.add('open');
            }, 10);
            document.body.style.overflow = 'hidden';

            // Hide cart widget
            document.getElementById('floating-cart-widget').style.transform = 'translateX(-50%) translateY(200%)';
        }

        function closeCheckoutDrawer() {
            const overlay = document.getElementById('co-drawer-overlay');
            const drawer = document.getElementById('co-drawer');
            overlay.style.opacity = '0';
            drawer.classList.remove('open');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
                updateCartWidget();
                // Destroy map if it was created
                if (coDeliveryMap) { coDeliveryMap.remove(); coDeliveryMap = null; }
            }, 420);
        }

        // Alias for old code
        function closeCheckoutModal() { closeCheckoutDrawer(); }

        function getCookie(name) {
            return (document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)') || [])[2] || '';
        }

        /* ── Fee from API ── */
        async function loadPlatformFee() {
            document.getElementById('co-platform-fee').textContent = '...';
            try {
                // Fetch the user fee percentage from OrdersJiblerpercentage
                const fd = new FormData();
                // We pass cartTotal so the server can calculate min fee
                fd.append('total', coSubtotal);
                const res = await fetch('GetFeesJiblerUserPrice.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data && data.success && data.data !== undefined) {
                    coPlatformFee = parseFloat(data.data) || 3;
                } else {
                    // Fallback: fetch percentage and calculate
                    const r2 = await fetch('GetJiblerCommesion.php');
                    const d2 = await r2.json();
                    const pct = parseFloat(d2?.data?.DriverCommesion || 10);
                    coPlatformFee = Math.max(3, coSubtotal * pct / 100);
                }
            } catch(e) {
                coPlatformFee = 3;
            }
            document.getElementById('co-platform-fee').textContent = coPlatformFee.toFixed(2) + ' MAD';
            const total = coSubtotal + coPlatformFee;
            document.getElementById('co-total-all').textContent = total.toFixed(2) + ' MAD';
            document.getElementById('co-final-total').textContent = total.toFixed(2) + ' MAD';
        }

        /* ── Saved Addresses ── */
        async function loadSavedAddresses() {
            const uid = getCookie('qoon_user_id');
            const container = document.getElementById('co-saved-addresses');
            try {
                const fd = new FormData(); fd.append('UserID', uid);
                const res = await fetch('GetAllAddress.php', { method: 'POST', body: fd });
                const data = await res.json();
                const addresses = data?.data || [];
                if (addresses.length === 0) {
                    container.innerHTML = `<div style="color:rgba(255,255,255,0.3); font-size:13px; padding:10px 0;">No saved addresses yet.</div>`;
                    return;
                }
                let html = '';
                addresses.forEach((addr, i) => {
                    const street = addr.StreetName || addr.AdressName || addr.AddressText || 'Address ' + (i+1);
                    const city = addr.CityName || addr.City || '';
                    // Escape quotes for the onclick attribute
                    const safeStreet = street.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    html += `<div class="saved-addr-card ${i===0?'selected':''}" onclick="selectSavedAddr(this, ${addr.AddressLat||0}, ${addr.AddressLon||addr.AddressLongt||0}, '${safeStreet}')">
                        <div class="saved-addr-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; color:#fff; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${street}</div>
                            ${city ? `<div style="font-size:12px; color:rgba(255,255,255,0.4); margin-top:2px;">${city}</div>` : ''}
                        </div>
                        <i class="fa-solid fa-circle-check" style="color:#f50057; font-size:18px; ${i===0?'':'opacity:0;'} transition:0.2s;"></i>
                    </div>`;
                });
                container.innerHTML = html;
                // Auto-select first
                const firstAddr = addresses[0];
                coDeliveryLat = parseFloat(firstAddr.AddressLat || 0) || null;
                coDeliveryLon = parseFloat(firstAddr.AddressLon || firstAddr.AddressLongt || 0) || null;
            } catch(e) {
                container.innerHTML = `<div style="color:rgba(255,255,255,0.3); font-size:13px; padding:10px 0;">Could not load addresses.</div>`;
            }
        }

        function selectSavedAddr(el, lat, lon, label) {
            document.querySelectorAll('.saved-addr-card').forEach(c => {
                c.classList.remove('selected');
                const chk = c.querySelector('.fa-circle-check');
                if (chk) chk.style.opacity = '0';
            });
            el.classList.add('selected');
            const chk = el.querySelector('.fa-circle-check');
            if (chk) chk.style.opacity = '1';
            coDeliveryLat = lat || null;
            coDeliveryLon = lon || null;
            coSelectedAddr = label;
            coUseNewLocation = false;
            // Hide map if shown
            document.getElementById('co-map-container').style.display = 'none';
            document.getElementById('co-map-note').style.display = 'none';
        }

        /* ── Address Type ── */
        function selectAddrType(el, type) {
            document.querySelectorAll('.addr-type-chip').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            coAddrType = type;
        }

        /* ── New Location Map ── */
        function toggleNewLocation() {
            const mapContainer = document.getElementById('co-map-container');
            const mapNote = document.getElementById('co-map-note');
            coUseNewLocation = !coUseNewLocation;
            if (coUseNewLocation) {
                mapContainer.style.display = 'block';
                mapNote.style.display = 'block';
                // Deselect saved addresses
                document.querySelectorAll('.saved-addr-card').forEach(c => {
                    c.classList.remove('selected');
                    const chk = c.querySelector('.fa-circle-check');
                    if (chk) chk.style.opacity = '0';
                });
                initCoMap();
            } else {
                mapContainer.style.display = 'none';
                mapNote.style.display = 'none';
            }
        }

        function initCoMap() {
            if (coDeliveryMap) return; // already init
            const startLat = parseFloat(getCookie('qoon_lat')) || 33.9716;
            const startLon = parseFloat(getCookie('qoon_lon')) || -6.8498;

            setTimeout(() => {
                if (typeof L === 'undefined') return;
                coDeliveryMap = L.map('co-leaflet-map', {
                    center: [startLat, startLon],
                    zoom: 15,
                    zoomControl: false,
                    attributionControl: false
                });
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19
                }).addTo(coDeliveryMap);

                coDeliveryLat = startLat;
                coDeliveryLon = startLon;

                coDeliveryMap.on('move', () => {
                    const c = coDeliveryMap.getCenter();
                    coDeliveryLat = c.lat;
                    coDeliveryLon = c.lng;
                });

                coDeliveryMap.on('moveend', () => {
                    const c = coDeliveryMap.getCenter();
                    coDeliveryLat = c.lat;
                    coDeliveryLon = c.lng;
                });
            }, 50);
        }

        /* ── GPS Locate Button ── */
        let coLocateCircle = null;
        function locateOnMap() {
            if (!navigator.geolocation) return;
            const btn  = document.getElementById('co-locate-btn');
            const icon = document.getElementById('co-locate-icon');

            // Pulse animation while loading
            btn.style.borderColor  = '#f50057';
            btn.style.background   = 'rgba(245,0,87,0.15)';
            icon.className         = 'fa-solid fa-circle-notch fa-spin';
            btn.disabled           = true;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;
                    const acc = pos.coords.accuracy;

                    // Ensure map is ready
                    if (!coDeliveryMap) { initCoMap(); }

                    setTimeout(() => {
                        if (!coDeliveryMap) return;

                        // Fly to real location
                        coDeliveryMap.flyTo([lat, lon], 17, { animate: true, duration: 1.2 });
                        coDeliveryLat = lat;
                        coDeliveryLon = lon;

                        // Draw accuracy circle
                        if (coLocateCircle) coLocateCircle.remove();
                        coLocateCircle = L.circle([lat, lon], {
                            radius: acc,
                            color: '#f50057',
                            fillColor: 'rgba(245,0,87,0.12)',
                            fillOpacity: 1,
                            weight: 1.5
                        }).addTo(coDeliveryMap);

                        // Success state
                        btn.disabled         = false;
                        btn.style.borderColor = '#2ecc71';
                        btn.style.background  = 'rgba(46,204,113,0.18)';
                        icon.className        = 'fa-solid fa-location-crosshairs';
                        icon.style.color      = '#2ecc71';

                        // Reset button style after 3s
                        setTimeout(() => {
                            btn.style.borderColor = 'rgba(255,255,255,0.15)';
                            btn.style.background  = '#0a0a0f';
                            icon.style.color      = '#fff';
                        }, 3000);
                    }, coDeliveryMap ? 0 : 200);
                },
                (err) => {
                    btn.disabled         = false;
                    btn.style.borderColor = 'rgba(255,255,255,0.15)';
                    btn.style.background  = '#0a0a0f';
                    icon.className        = 'fa-solid fa-location-crosshairs';
                    icon.style.color      = '#e74c3c';
                    setTimeout(() => { icon.style.color = '#fff'; }, 2000);
                    console.warn('[Locate] Geolocation error:', err.message);
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
            );
        }

        /* ── Payment Method ── */
        let coQPayLoaded = false;
        function selectNewPayment(method) {
            selectedPaymentMethod = method;
            document.querySelectorAll('.pay-card').forEach(c => c.classList.remove('active'));
            document.getElementById('pay-card-' + method).classList.add('active');
            // Toggle bank card fields
            const bv = document.getElementById('bank-card-visual');
            if (bv) bv.classList.toggle('show', method === 'CARD');
            // Load QOON Pay balance once
            if (method === 'QOON' && !coQPayLoaded) {
                coQPayLoaded = true;
                fetch('qpay_balance.php')
                    .then(r => r.json())
                    .then(d => {
                        const bal = parseFloat(d?.balance || 0);
                        document.getElementById('co-qpay-balance').textContent = bal.toFixed(2) + ' MAD';
                    })
                    .catch(() => {});
            }
        }

        // Kept for legacy calls
        function selectPayment(m) { selectNewPayment(m); }

        /* ── Card Formatters ── */
        function formatCardNum(el) {
            let v = el.value.replace(/\D/g,'').substring(0,16);
            el.value = v.replace(/(\d{4})(?=\d)/g,'$1 ');
        }
        function formatExpiry(el) {
            let v = el.value.replace(/\D/g,'').substring(0,4);
            if (v.length > 2) v = v.slice(0,2) + '/' + v.slice(2);
            el.value = v;
        }

        /* ── Step Navigation ── */
        function goToStep(step) {
            coCurrentStep = step;
            document.querySelectorAll('.co-step-btn').forEach((b,i) => {
                b.classList.remove('active','done');
                if (i+1 === step) b.classList.add('active');
                else if (i+1 < step) b.classList.add('done');
            });
            document.querySelectorAll('.co-step-panel').forEach((p,i) => {
                p.classList.toggle('active', i+1 === step);
            });
            // Update CTA label
            const labels = ['', 'Go to Delivery', 'Go to Payment', 'Confirm Order'];
            const icons  = ['', 'fa-arrow-right', 'fa-arrow-right', 'fa-check'];
            document.getElementById('co-cta-label').textContent = labels[step];
            const iconEl = document.getElementById('co-cta-icon');
            if (iconEl) iconEl.className = 'fa-solid ' + icons[step];
            if (step === 3) {
                document.getElementById('co-cta-btn').classList.add('green');
            } else {
                document.getElementById('co-cta-btn').classList.remove('green');
            }
        }

        function handleCheckoutCTA() {
            if (coCurrentStep < 3) {
                goToStep(coCurrentStep + 1);
            } else {
                confirmOrderFinal();
            }
        }

        /* ── Final Confirmation ── */
        function confirmOrderFinal() {
            const btn = document.getElementById('co-cta-btn');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...`;

            const total = coSubtotal + coPlatformFee;
            const note  = document.getElementById('co-order-note')?.value || '';

            // Hide the drawer behind
            const drawer = document.getElementById('co-drawer');
            const drawerOverlay = document.getElementById('co-drawer-overlay');
            if (drawer) drawer.style.display = 'none';
            if (drawerOverlay) drawerOverlay.style.display = 'none';
            document.body.style.overflow = ''; // restore scrolling for background just in case

            // Fire-and-forget saving the address if they used the map
            if (coUseNewLocation && coDeliveryLat && coDeliveryLon) {
                const uid = getCookie('qoon_user_id') || 9999;
                const fdAddr = new FormData();
                fdAddr.append('UserID', uid);
                fdAddr.append('AddressType', coAddrType);
                fdAddr.append('AddressName', coAddrType); // Use type as name for now
                fdAddr.append('AddressText', 'New Location from Checkout');
                fdAddr.append('AddressLat', coDeliveryLat);
                fdAddr.append('AddressLongt', coDeliveryLon);
                
                // Fire and forget to AddAddressUser.php
                fetch('AddAddressUser.php', { method: 'POST', body: fdAddr }).catch(console.error);
            }

            // Show the full-screen modern loading overlay
            const loadingScreen = document.getElementById('qoon-checkout-loading-screen');
            if (loadingScreen) {
                loadingScreen.style.display = 'flex';
                // Trigger reflow
                void loadingScreen.offsetWidth;
                loadingScreen.style.opacity = '1';
            }

            // Wait 3 seconds to let the user read the animation before POSTing
            setTimeout(() => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delivery_offers.php';

                const fields = {
                    total:       total,
                    shopId:      '<?= $shopId ?>',
                    shopName:    '<?= addslashes($shopName) ?>',
                    cart:        JSON.stringify(cartItems),
                    payMethod:   selectedPaymentMethod,
                    addrType:    coAddrType,
                    addrLat:     coDeliveryLat || '',
                    addrLon:     coDeliveryLon || '',
                    orderNote:   note,
                    platformFee: coPlatformFee,
                    deliveryFee: typeof coDeliveryFee !== 'undefined' ? coDeliveryFee : 12
                };
                Object.entries(fields).forEach(([k,v]) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = k; inp.value = v;
                    form.appendChild(inp);
                });
                document.body.appendChild(form);
                form.submit();
                cartItems = [];
                updateCartWidget();
            }, 3000);
        }

        // Close bindings
        document.addEventListener("DOMContentLoaded", () => {
            const pmOverlay = document.getElementById('pm-overlay');
            if (pmOverlay) pmOverlay.addEventListener('click', (e) => { if (e.target === pmOverlay) closeProductModal(); });

            if (coOverlay) coOverlay.addEventListener('click', (e) => { if (e.target === coOverlay) closeCheckoutModal(); });
        });
    </script>

    <!-- FLOATING CART WIDGET -->
    <div id="floating-cart-widget" onclick="openCartDrawer()">
        <div class="fc-info">
            <div class="fc-badge" id="fc-count">0</div>
            <span>View Cart</span>
        </div>
        <div style="font-weight: 800; font-size: 18px;" id="fc-total">0 MAD</div>
    </div>

    <!-- CART DRAWER -->
    <div id="cart-drawer-overlay" onclick="closeCartDrawer()"></div>
    <div id="cart-drawer">
        <div class="cart-drawer-hd">
            <div class="cart-drawer-title">
                <i class="fa-solid fa-bag-shopping" style="color:#f50057;"></i>
                My Cart
                <span class="cart-count-badge" id="cart-badge">0</span>
            </div>
            <button class="cart-close-btn" onclick="closeCartDrawer()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Items List -->
        <div class="cart-items-scroll" id="cart-items-scroll">
            <!-- populated by JS -->
        </div>

        <!-- Footer -->
        <div class="cart-footer">
            <div class="cart-summary-row">
                <span class="cart-summary-lbl">Subtotal</span>
                <span class="cart-summary-val" id="cart-footer-total">0 MAD</span>
            </div>
            <button class="cart-checkout-btn" onclick="closeCartDrawer(); openCheckoutDrawer();">
                <i class="fa-solid fa-bolt"></i>
                Proceed to Checkout
            </button>
            <button class="cart-clear-btn" onclick="clearCart()">
                <i class="fa-solid fa-trash-can" style="margin-right:6px;"></i> Clear cart
            </button>
        </div>
    </div>

    <!-- CHECKOUT LOADING SCREEN -->
    <div id="qoon-checkout-loading-screen" style="display: none; position: fixed; inset: 0; z-index: 9999999; background: #010008; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.5s ease;">
        <!-- Glowing background effect -->
        <div style="position: absolute; width: 300px; height: 300px; background: radial-gradient(circle, rgba(123,0,255,0.3) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(40px); animation: pulseGlow 2s infinite alternate;"></div>
        
        <!-- Logo and Ring -->
        <div style="position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; margin-bottom: 30px;">
            <svg class="loading-ring" viewBox="0 0 100 100" style="position: absolute; width: 100%; height: 100%; transform: rotate(-90deg);">
                <circle cx="50" cy="50" r="46" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="4"></circle>
                <circle cx="50" cy="50" r="46" fill="none" stroke="#7b00ff" stroke-width="4" stroke-dasharray="289" stroke-dashoffset="289" style="animation: drawRing 3s cubic-bezier(0.4, 0, 0.2, 1) forwards;"></circle>
            </svg>
            <img src="logo_qoon_white.png" alt="QOON" style="width: 60px; z-index: 2; animation: pulseLogo 1.5s infinite alternate;" onerror="this.src='https://ui-avatars.com/api/?name=Q&background=random&color=fff'">
        </div>

        <h2 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 12px; z-index: 2; text-align: center;">Processing Order</h2>
        <p style="font-size: 15px; color: rgba(255,255,255,0.6); max-width: 280px; text-align: center; line-height: 1.5; z-index: 2;">
            Sending your request to nearby drivers...<br>
            <span style="color: #7b00ff; font-weight: 700;">Get ready for delivery offers!</span>
        </p>
    </div>

    <style>
        @keyframes drawRing {
            to { stroke-dashoffset: 0; }
        }
        @keyframes pulseLogo {
            from { transform: scale(0.95); opacity: 0.8; }
            to { transform: scale(1.05); opacity: 1; }
        }
        @keyframes pulseGlow {
            from { transform: scale(0.8); opacity: 0.5; }
            to { transform: scale(1.2); opacity: 1; }
        }
    </style>

    <!-- VTO DEDICATED MODAL -->
    <div id="vto-modal-container" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
        <div style="background: #111; width: 90%; max-width: 400px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; position: relative; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
            
            <div style="padding: 16px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="font-weight: 800; font-size: 18px; color: #fff;">Virtual Try-On <i class="fa-solid fa-wand-magic-sparkles" style="color: #f50057; margin-left: 8px;"></i></div>
                <button onclick="closeVTOModal()" style="background: rgba(255,255,255,0.1); border: none; width: 32px; height: 32px; border-radius: 50%; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div id="vto-content-area" style="position: relative; width: 100%; aspect-ratio: 3/4; background: #050505; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <!-- Initial State -->
                <div id="vto-initial-state" style="text-align: center; padding: 20px;">
                    <i class="fa-solid fa-camera" style="font-size: 48px; color: rgba(255,255,255,0.2); margin-bottom: 16px;"></i>
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin-bottom: 24px;">Upload a full-body photo to see how this looks on you.</p>
                    <button onclick="document.getElementById('vto-upload').click()" style="background: linear-gradient(135deg, #f50057, #ff4081); border: none; padding: 12px 24px; border-radius: 12px; color: #fff; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(245,0,87,0.4); display: flex; align-items: center; gap: 8px; margin: 0 auto;">
                        <i class="fa-solid fa-upload"></i> Upload Photo
                    </button>
                </div>

                <!-- Processing & Result State -->
                <div id="vto-result-state" style="display: none; position: absolute; inset: 0; width: 100%; height: 100%;">
                    <!-- Injected dynamically -->
                </div>
            </div>

            <div style="padding: 16px; background: rgba(255,255,255,0.02); text-align: center; font-size: 12px; color: rgba(255,255,255,0.4);">
                Powered by <span style="font-weight: 800; color: #fff; letter-spacing: 1px;">QOON</span> AI
            </div>
        </div>
    </div>

    <!-- VTO Script Logic -->
    <script>
        function startVirtualTryOn() {
            // ── Capture product image URL NOW, before the modal is hidden ──
            // currentProductData is set when the product modal opens.
            // We prefer the first image in the carousel; fallback to data.img.
            const _imgArr = currentProductData && currentProductData.images && currentProductData.images.length > 0
                ? currentProductData.images
                : (currentProductData && currentProductData.img ? [currentProductData.img] : []);
            window._vtoProductImg = _imgArr[0] || '';
            console.log('[VTO] Stored product image for upload:', window._vtoProductImg);

            // Reset the file input so onchange always fires even if same file chosen
            const vtoInput = document.getElementById('vto-upload');
            if (vtoInput) vtoInput.value = '';

            // Smoothly hide product modal
            const pmOverlay = document.getElementById('pm-overlay');
            const pmModal = document.getElementById('pm-modal');
            pmOverlay.style.opacity = '0';
            pmModal.style.transform = 'scale(0.95)';
            pmModal.style.opacity = '0';

            const vtoContainer = document.getElementById('vto-modal-container');
            const vtoInner = vtoContainer.querySelector('div');
            
            // Set initial states for transition
            vtoContainer.style.opacity = '0';
            vtoContainer.style.transition = 'opacity 0.4s ease';
            vtoInner.style.transform = 'translateY(40px) scale(0.95)';
            vtoInner.style.transition = 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
            
            document.getElementById('vto-initial-state').style.display = 'block';
            document.getElementById('vto-result-state').style.display = 'none';
            document.getElementById('vto-result-state').innerHTML = ''; // reset
            // Kill any running timer from a previous VTO attempt
            clearInterval(window._vtoProgressTimer);
            window._vtoProgress = 0;

            setTimeout(() => {
                pmOverlay.style.display = 'none'; // fully close product modal

                vtoContainer.style.display = 'flex';
                // Trigger reflow
                void vtoContainer.offsetWidth;

                vtoContainer.style.opacity = '1';
                vtoInner.style.transform = 'translateY(0) scale(1)';
            }, 300);
        }

        function closeVTOModal() {
            const vtoContainer = document.getElementById('vto-modal-container');
            const vtoInner = vtoContainer.querySelector('div');
            
            vtoContainer.style.opacity = '0';
            vtoInner.style.transform = 'translateY(40px) scale(0.95)';
            
            setTimeout(() => {
                vtoContainer.style.display = 'none';
                document.body.style.overflow = ''; // Restore page scrolling
            }, 400);
        }

        // ── Global VTO state (module-level so old timers are always killed) ──
        window._vtoProgress = 0;
        window._vtoProgressTimer = null;

           async function handleVTOUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Use the product image URL captured when the VTO was opened.
            // DO NOT read from .pm-img-wrap — that modal is already hidden.
            const prodImg = window._vtoProductImg || '';
            console.log('[VTO] handleVTOUpload — prodImg from stored var:', prodImg);

            // Switch to result state with overlay
            document.getElementById('vto-initial-state').style.display = 'none';
            const resultState = document.getElementById('vto-result-state');
            resultState.style.display = 'block';

            // Build the scanning overlay using a data URL preview of the user photo
            const previewUrl = await new Promise(r => {
                const rd = new FileReader();
                rd.onload = e => r(e.target.result);
                rd.readAsDataURL(file);
            });

            resultState.innerHTML = `
            <div id="vto-overlay" style="position:absolute;inset:0;z-index:10;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#000;">
                <img id="vto-user-img" src="${previewUrl}" style="width:100%;height:100%;object-fit:cover;filter:blur(6px);opacity:0.8;transition:filter 1s,opacity 1s;">
                ${prodImg ? `<div style="position:absolute;top:16px;right:16px;width:64px;height:64px;border-radius:12px;overflow:hidden;border:2px solid rgba(255,255,255,0.2);box-shadow:0 4px 15px rgba(0,0,0,0.6);z-index:15;background:#fff;"><img src="${prodImg}" style="width:100%;height:100%;object-fit:cover;"></div>` : ''}
                <div class="vto-progress-wrap" id="vto-progress-wrap">
                    <img src="logo_qoon_white.png" class="vto-progress-logo" alt="QOON"
                         onerror="this.src='https://ui-avatars.com/api/?name=Q&background=f50057&color=fff&size=80'">
                    <div class="vto-progress-pct" id="vto-pct">0%</div>
                    <div class="vto-progress-track">
                        <div class="vto-progress-fill" id="vto-bar"></div>
                    </div>
                    <div class="vto-progress-label" id="vto-status-msg">Preparing...</div>
                </div>
            </div>`;

            // ── Kill any leftover timer from a previous run ──
            clearInterval(window._vtoProgressTimer);
            window._vtoProgress = 0;

            function setVtoProgress(pct, label) {
                window._vtoProgress = Math.min(100, Math.max(window._vtoProgress, pct));
                const bar   = document.getElementById('vto-bar');
                const pctEl = document.getElementById('vto-pct');
                const lbl   = document.getElementById('vto-status-msg');
                if (bar)   bar.style.width = window._vtoProgress + '%';
                if (pctEl) pctEl.textContent = Math.round(window._vtoProgress) + '%';
                if (lbl && label) lbl.textContent = label;
            }


            // Slow auto-creep so bar always feels alive during long waits
            function startVtoCreep(targetPct, durationMs) {
                clearInterval(window._vtoProgressTimer);
                const start = window._vtoProgress;
                const steps = 60;
                const stepMs = durationMs / steps;
                let i = 0;
                window._vtoProgressTimer = setInterval(() => {
                    i++;
                    const frac = i / steps;
                    const next = start + (targetPct - start) * (1 - Math.exp(-4 * frac));
                    setVtoProgress(next, null);
                    if (i >= steps) clearInterval(window._vtoProgressTimer);
                }, stepMs);
            }

            const setStatus = (msg) => {
                const el = document.getElementById('vto-status-msg');
                if (el) el.textContent = msg;
            };


            try {
                // ── Step 1: Upload user photo ──
                setVtoProgress(5, 'Uploading your photo...');
                startVtoCreep(20, 4000);
                const userForm = new FormData();
                userForm.append('action', 'upload');
                userForm.append('file', file);
                const userRes  = await fetch('NanoBananaApi.php', { method: 'POST', body: userForm });
                const userData = await userRes.json();
                if (!userData.url) throw new Error('Photo upload failed: ' + JSON.stringify(userData).substring(0, 80));
                const userImgUrl = userData.url;
                console.log('[VTO] User image URL:', userImgUrl);

                // ── Step 2: Upload product image ──
                setVtoProgress(22, 'Preparing product image...');
                startVtoCreep(38, 4000);
                let publicProdImg = prodImg;
                try {
                    const prodForm = new FormData();
                    prodForm.append('action', 'proxy_image');
                    prodForm.append('url', prodImg);
                    const prodRes  = await fetch('NanoBananaApi.php', { method: 'POST', body: prodForm });
                    const prodData = await prodRes.json();
                    if (prodData.url) publicProdImg = prodData.url;
                    console.log('[VTO] Product image URL:', publicProdImg);
                } catch(e) {
                    console.warn('[VTO] Product proxy failed, using original:', e);
                }

                // ── Step 3: Submit to AI ──
                setVtoProgress(40, 'Submitting to AI...');
                startVtoCreep(55, 6000);
                console.log('[VTO] FINAL URLs → userImg:', userImgUrl, '| prodImg:', publicProdImg);
                const submitForm = new FormData();
                submitForm.append('action', 'submit');
                submitForm.append('userImg', userImgUrl);
                submitForm.append('prodImg', publicProdImg);
                submitForm.append('prompt', 'Virtual try-on fashion photography, high quality, photorealistic clothing, natural lighting.');
                const submitRes  = await fetch('NanoBananaApi.php', { method: 'POST', body: submitForm });
                const submitData = await submitRes.json();
                console.log('[VTO] Submit response:', submitData);

                if (!submitData || submitData.code !== 200) {
                    throw new Error('Submit failed: ' + JSON.stringify(submitData).substring(0, 120));
                }
                const taskId = submitData.data && submitData.data.taskId;
                if (!taskId) throw new Error('No taskId in response');

                // ── Step 4: Poll for result ──
                let resultUrl = null;

                // Fast path: if PHP detected an immediate result (sync mode)
                if (taskId.startsWith('DONE:')) {
                    resultUrl = taskId.slice(5);
                    console.log('[VTO] Immediate sync result:', resultUrl);
                } else {
                const pollLabels = [
                    'Analyzing body shape...', 'Fitting clothing...', 'Adjusting fabric...',
                    'Blending textures...', 'Refining details...', 'Almost ready...'
                ];
                for (let i = 0; i < 60; i++) {
                    // Progress: 55% → 92% over 60 polls
                    const pollPct = 55 + Math.min(37, i * 0.62);
                    const label = pollLabels[Math.floor(i / 10) % pollLabels.length];
                    setVtoProgress(pollPct, label);
                    await new Promise(r => setTimeout(r, 3000));
                    const pollRes  = await fetch(`NanoBananaApi.php?action=poll&taskId=${encodeURIComponent(taskId)}`);
                    const pollData = await pollRes.json();
                    console.log(`[Poll ${i+1}]`, JSON.stringify(pollData).substring(0, 600));

                    if (pollData.data) {
                        const flag = pollData.data.successFlag;
                        if (flag == 1) {
                            const raw = pollData.data._raw || {};
                            // Try every known Pruna result URL location
                            resultUrl = pollData.data.response?.resultImageUrl
                                     || pollData.data.response?.originImageUrl
                                     || pollData.data.generation_url
                                     || raw.generation_url
                                     || (Array.isArray(raw.output) ? raw.output[0] : raw.output)
                                     || raw.urls?.result
                                     || null;
                            console.log('[VTO] successFlag=1 | resultUrl:', resultUrl);
                            console.log('[VTO] full pollData.data:', JSON.stringify(pollData.data).substring(0, 600));
                            if (resultUrl) break;
                            console.warn('[VTO] successFlag=1 but no resultUrl — raw:', JSON.stringify(raw).substring(0, 400));
                        }
                        if (flag == 2 || flag == 3) {
                            const errMsg = pollData.data.errorMessage || 'No details';
                            const errCode = pollData.data.errorCode || '';
                            throw new Error(`AI generation failed [${errCode}]: ${errMsg}`);
                        }
                    }
                }
                } // end else (polling branch)

                if (!resultUrl) throw new Error('Timed out after 180s — try again');

                // ── Step 5: Show result ──
                // PHP already saved the image to vto_temp/ and returned a localhost URL.
                // No need to proxy again — just display it directly.
                clearInterval(window._vtoProgressTimer);
                setVtoProgress(100, 'Done! ✓');
                await new Promise(r => setTimeout(r, 300));

                const vtoImg    = document.getElementById('vto-user-img');
                const vtoOverlay = document.getElementById('vto-overlay');
                console.log('[VTO] Displaying result:', resultUrl);

                // Set the image src directly — NO crossOrigin (that causes CORS failures on localhost)
                vtoImg.src = resultUrl;
                vtoImg.style.transition = 'filter 0.8s, opacity 0.8s';
                vtoImg.style.filter  = 'blur(0)';
                vtoImg.style.opacity = '1';

                // Fade out progress overlay
                const pw = document.getElementById('vto-progress-wrap');
                if (pw) {
                    pw.style.transition = 'opacity 0.8s';
                    pw.style.opacity = '0';
                    setTimeout(() => { if (pw.parentNode) pw.remove(); }, 800);
                }
                await new Promise(r => setTimeout(r, 800));
                finishVTOAnimation(resultState, true);


            } catch(error) {
                clearInterval(window._vtoProgressTimer);
                console.error('VTO AI Error:', error);
                const msgEl = document.getElementById('vto-status-msg');
                const pctEl = document.getElementById('vto-pct');
                const bar   = document.getElementById('vto-bar');
                if (msgEl) msgEl.innerHTML = `<span style="color:#ffcccb">AI Request Failed<br><span style="font-size:11px;font-weight:400">${error.message || 'Unknown error'}</span></span>`;
                if (pctEl) pctEl.textContent = '!';
                if (bar)   { bar.style.background = '#e74c3c'; bar.style.animation = 'none'; }
                const vtoImg = document.getElementById('vto-user-img');
                if (vtoImg) { vtoImg.style.filter = 'blur(0)'; vtoImg.style.opacity = '1'; }
            }
        }

        function finishVTOAnimation(wrap, success) {
            const pw = wrap.querySelector('.vto-progress-wrap');
            if (pw) pw.style.display = 'none';

            const userImg = wrap.querySelector('#vto-user-img');
            if (userImg) {
                userImg.style.filter = 'blur(0)';
                userImg.style.opacity = '1';
            }
        }
    </script>
