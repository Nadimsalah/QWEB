<?php session_start();
require "conn.php";
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>QOON Express · Driver Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-alt: #f1f5f9;
            --brand-primary: #6366f1; /* Indigo */
            --brand-secondary: #0ea5e9; /* Cyan */
            --brand-accent: #f43f5e; /* Rose */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --radius-xl: 24px;
            --radius-lg: 18px;
            --radius-md: 12px;
            --bg-nav: rgba(255, 255, 255, 0.8);
        }



        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body { 
            height: 100%; 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main); 
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(14, 165, 233, 0.05) 0px, transparent 50%);
        }

        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Navigation */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            height: 70px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; background: var(--bg-nav); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
        }
        .logo { height: 36px; object-fit: contain; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        
        .status-pill {
            display: flex; align-items: center; gap: 8px; padding: 8px 18px;
            border-radius: 99px; background: var(--bg-card); border: 1px solid var(--border-light);
            font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }
        .status-pill.online {
            background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3); color: #16a34a;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; transition: 0.3s; }
        .status-pill.online .status-dot { background: #22c55e; box-shadow: 0 0 10px #22c55e; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .icon-btn {
            width: 42px; height: 42px; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-light);
            color: var(--text-main); display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: var(--shadow-sm);
        }
        .icon-btn:hover { background: var(--brand-accent); color: #ffffff; border-color: transparent; transform: rotate(15deg); }

        /* Main Container */
        .container { padding: 90px 20px 100px; max-width: 800px; margin: 0 auto; }

        /* Hero Section */
        .hero-card {
            background: var(--bg-card); border-radius: var(--radius-xl); padding: 30px;
            border: 1px solid var(--border-light); box-shadow: var(--shadow-md);
            margin-bottom: 24px; position: relative; overflow: hidden;
        }
        .hero-card::after {
            content: ''; position: absolute; top: 0; right: 0; width: 150px; height: 150px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
            border-radius: 50%; transform: translate(30%, -30%);
        }
        .hero-header { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
        .hero-avatar {
            width: 70px; height: 70px; border-radius: 50%; object-fit: cover;
            border: 3px solid var(--bg-card); box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hero-info h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .hero-info p { font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-top: 2px; }

        .hero-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .stat-item {
            background: var(--bg-alt); padding: 16px 12px; border-radius: var(--radius-lg); text-align: center;
            transition: 0.2s;
        }
        .stat-item:hover { background: #e2e8f0; transform: translateY(-2px); }
        .stat-value { font-size: 24px; font-weight: 900; color: var(--brand-primary); letter-spacing: -0.5px; }
        .stat-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px; }

        /* Tabs Navigation */
        .tab-bar {
            background: var(--bg-card); border: 1px solid var(--border-light); border-radius: 20px;
            display: flex; padding: 6px; gap: 6px; margin-bottom: 24px; box-shadow: var(--shadow-sm);
        }
        .tab-item {
            flex: 1; padding: 12px; border-radius: 14px; text-align: center; font-size: 14px;
            font-weight: 700; color: var(--text-muted); cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .tab-item i { font-size: 18px; opacity: 0.7; }
        .tab-item.active { background: var(--brand-primary); color: #ffffff; }
        .tab-item.active i { opacity: 1; }

        /* Section Title */
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 0 4px; }
        .section-header h2 { font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .section-header h2 i { color: var(--brand-primary); }
        .live-badge {
            background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2;
            padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 800;
            display: flex; align-items: center; gap: 6px;
        }
        .live-dot { width: 6px; height: 6px; border-radius: 50%; background: #ef4444; animation: pulse 1.5s infinite; }

        /* Order Cards */
        .order-list { display: flex; flex-direction: column; gap: 16px; }
        .order-card {
            background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--border-light);
            overflow: hidden; box-shadow: var(--shadow-md); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; animation: slideUp 0.4s ease both;
        }
        .order-card:hover { transform: translateY(-4px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .order-status-bar { height: 4px; background: var(--brand-primary); width: 100%; }
        .order-status-bar.scheduled { background: #f59e0b; }
        
        .card-content { padding: 20px; }
        .shop-row { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        .shop-img { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 1px solid var(--border-light); flex-shrink: 0; }
        .shop-info { flex: 1; min-width: 0; }
        .shop-name { font-size: 17px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        .shop-address { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
        .order-tag {
            display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
            padding: 4px 12px; border-radius: 99px; font-size: 10px; font-weight: 800;
            background: var(--bg-alt); color: var(--text-muted); text-transform: uppercase;
        }
        .order-tag.express { background: rgba(99, 102, 241, 0.1); color: var(--brand-primary); }

        .order-details-box {
            background: var(--bg-alt); border-radius: var(--radius-md); padding: 12px 16px;
            font-size: 13px; color: var(--text-main); margin-bottom: 16px; border: 1px solid var(--bg-alt);
        }

        .customer-row {
            display: flex; align-items: center; gap: 10px; padding: 12px 16px;
            background: var(--bg-card); border: 1px solid var(--bg-alt); border-radius: var(--radius-md);
            margin-bottom: 16px;
        }
        .customer-img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .customer-name { font-size: 14px; font-weight: 700; flex: 1; }
        .order-id-label { font-size: 12px; font-weight: 600; color: var(--text-muted); background: var(--bg-alt); padding: 3px 10px; border-radius: 6px; }

        .card-actions { display: flex; gap: 10px; }
        .btn-primary {
            flex: 1; height: 52px; background: var(--brand-primary); color: #ffffff;
            border: none; border-radius: 16px; font-size: 15px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            cursor: pointer; transition: 0.2s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); }
        .btn-primary:active { transform: translateY(0); }
        
        .btn-secondary {
            width: 52px; height: 52px; background: var(--bg-card); border: 1px solid var(--border-light);
            color: var(--text-muted); border-radius: 16px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; cursor: pointer; transition: 0.2s;
        }
        .btn-secondary:hover { background: var(--bg-card)1f2; color: #e11d48; border-color: #fecdd3; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 50px; color: #cbd5e1; margin-bottom: 20px; display: block; }
        .empty-state h3 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: var(--text-muted); max-width: 280px; margin: 0 auto; }

        /* Modals */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 2000; background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: var(--bg-card); border-radius: 30px; padding: 35px 25px; width: 100%; max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: center;
            transform: scale(0.9); transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.active .modal-box { transform: scale(1); }

        .modal-icon {
            width: 80px; height: 80px; border-radius: 50%; background: #f5f3ff;
            color: var(--brand-primary); display: flex; align-items: center; justify-content: center;
            font-size: 32px; margin: 0 auto 20px; border: 1px solid #ede9fe;
        }
        .modal-box h3 { font-size: 24px; font-weight: 900; letter-spacing: -0.5px; margin-bottom: 10px; }
        .modal-box p { font-size: 15px; color: var(--text-muted); line-height: 1.6; margin-bottom: 25px; }

        .form-group { text-align: left; margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 800; color: var(--text-main); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .price-input {
            width: 100%; padding: 15px 20px; border-radius: 16px; border: 2px solid var(--bg-alt);
            background: var(--bg-alt); font-size: 18px; font-weight: 800; color: var(--text-main);
            outline: none; transition: 0.3s;
        }
        .price-input:focus { border-color: var(--brand-primary); background: var(--bg-card); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .modal-actions { display: flex; gap: 12px; }
        .btn-cancel {
            flex: 1; height: 54px; background: var(--bg-alt); color: var(--text-muted);
            border: none; border-radius: 16px; font-size: 15px; font-weight: 700; cursor: pointer;
        }

        /* Trip Specific UI */
        .trip-card {
            background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-light);
            padding: 24px; box-shadow: var(--shadow-md); cursor: pointer; transition: 0.3s;
        }
        .trip-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .trip-id { font-size: 14px; font-weight: 800; color: var(--brand-primary); }
        .trip-status { font-size: 12px; font-weight: 800; background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 99px; }
        .trip-status.cancelled { background: #fee2e2; color: #991b1b; }
        .trip-status.returned { background: #ffedd5; color: #9a3412; }
        
        .trip-route { position: relative; display: flex; flex-direction: column; gap: 20px; padding-left: 30px; }
        .trip-route::before {
            content: ''; position: absolute; left: 6px; top: 8px; bottom: 8px; width: 2px;
            background: repeating-linear-gradient(to bottom, #e2e8f0, #e2e8f0 4px, transparent 4px, transparent 8px);
        }
        .route-point { position: relative; }
        .route-point::after {
            content: ''; position: absolute; left: -29px; top: 4px; width: 10px; height: 10px;
            border-radius: 50%; background: var(--bg-card); border: 3px solid var(--brand-primary);
        }
        .route-point.end::after { border-color: var(--brand-accent); }
        .route-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .route-name { font-size: 15px; font-weight: 800; }

        /* Skeletons */
        .skeleton-card {
            background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--border-light);
            padding: 20px; margin-bottom: 16px; box-shadow: var(--shadow-sm);
        }
        .skeleton-row { display: flex; gap: 16px; margin-bottom: 16px; }
        .skeleton-avatar { width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0; }
        .skeleton-line { height: 14px; border-radius: 4px; margin-bottom: 8px; }
        .skeleton-line.w-50 { width: 50%; }
        .skeleton-line.w-80 { width: 80%; }
        .skeleton-btn { height: 52px; border-radius: 16px; width: 100%; margin-top: 16px; }
        
        .shimmer-anim {
            background: var(--bg-alt);
            background-image: linear-gradient(90deg, var(--bg-alt) 0px, #e2e8f0 40px, var(--bg-alt) 80px);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }



        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .hero-card { padding: 20px 16px; }
            .hero-header { flex-direction: column; text-align: center; gap: 12px; }
            .hero-info h1 { font-size: 20px; }
            .hero-stats { gap: 8px; }
            .stat-item { padding: 12px 6px; }
            .stat-value { font-size: 18px; }
            .stat-label { font-size: 9px; }
            .tab-bar { overflow-x: auto; flex-wrap: nowrap; justify-content: flex-start; -webkit-overflow-scrolling: touch; }
            .tab-bar::-webkit-scrollbar { display: none; }
            .tab-item { flex: 0 0 auto; white-space: nowrap; padding: 10px 16px; font-size: 13px; }
            .shop-row { gap: 10px; }
            .shop-name { font-size: 15px; }
            .shop-img { width: 48px; height: 48px; }
            .btn-primary { font-size: 14px; height: 48px; }
            .btn-secondary { width: 48px; height: 48px; }
            .finance-bars { grid-template-columns: 1fr !important; gap: 8px !important; margin: 0 16px 20px 16px !important; }
            .nav-right { gap: 8px; }
            nav { padding: 0 16px; }
            .logo { height: 28px; }
            .container { padding: 90px 12px 100px; }
            .card-content { padding: 16px; }
        }

        /* Desktop Adjustments */
        @media (min-width: 769px) {
            nav { padding: 0 40px; }
            .container { padding-top: 110px; }
            .hero-stats { grid-template-columns: repeat(3, 1fr); gap: 20px; }
            .stat-item { padding: 24px; }
            .order-list { display: flex; flex-direction: column; gap: 24px; }
        }
    </style>
</head>
<body>

<nav>
    <img src="logo_express.png" alt="QOON Express" class="logo" onerror="this.src='https://qoon.app/logo.png'">
    <div class="nav-right">
        <div class="status-pill" id="status-pill" onclick="toggleOnline()">
            <div class="status-dot"></div>
            <span id="status-text">Offline</span>
        </div>
        <a href="qoonexpress.login.php" class="icon-btn" onclick="localStorage.removeItem('qoon_driver')" title="Logout">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>
</nav>

<div class="container">
    <!-- HERO SECTION -->
    <div class="hero-card">
        <div class="hero-header">
            <img id="driver-avatar" src="https://ui-avatars.com/api/?name=Driver&background=6366f1&color=fff&size=128" class="hero-avatar">
            <div class="hero-info">
                <h1 id="driver-name-display">Loading...</h1>
                <p id="location-display"><i class="fa-solid fa-location-arrow fa-fade"></i> Locating you...</p>
            </div>
        </div>
        
        <!-- Financial Overview Bar -->
        <div class="finance-bars" style="margin: 0 24px 20px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <!-- Wallet Balance -->
            <div id="wallet-balance-bar" style="padding: 15px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; background: #e0e7ff; border: 1px solid #c7d2fe; transition: 0.3s;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 11px; font-weight: 700; color: #4338ca; text-transform: uppercase; letter-spacing: 0.5px;">My Wallet (Earned)</span>
                    <span style="font-size: 18px; font-weight: 800; color: #312e81; margin-top: 2px;"><span id="stat-wallet-balance">0.00</span> <span style="font-size: 12px; opacity: 0.8;">MAD</span></span>
                </div>
                <i class="fa-solid fa-credit-card" style="font-size: 20px; color: #4f46e5;"></i>
            </div>

            <!-- Cash Collect Balance Bar -->
            <div id="cash-collect-bar" style="padding: 15px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; background: #d1fae5; border: 1px solid #a7f3d0; transition: 0.3s; cursor: pointer;" onclick="openReturnCashModal()">
                <div style="display: flex; flex-direction: column;">
                    <span id="cash-collect-title" style="font-size: 11px; font-weight: 700; color: #10b981; text-transform: uppercase; letter-spacing: 0.5px;">Debt to Company</span>
                    <span style="font-size: 18px; font-weight: 800; color: #047857; margin-top: 2px;"><span id="stat-cash-collected">0.00</span> <span style="font-size: 12px; opacity: 0.8;">MAD</span></span>
                </div>
                <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                    <button style="background: #10b981; color: white; border: none; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 800; cursor: pointer; margin-bottom: 4px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3); pointer-events: none;">SEND CASH</button>
                    <div id="cash-collect-limit-text" style="font-size: 10px; font-weight: 700; color: #10b981; opacity: 0.8;">Limit: 350</div>
                </div>
            </div>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-value" id="stat-today">0</div>
                <div class="stat-label">Today's Trips</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="stat-rating">5.0 <i class="fa-solid fa-star" style="font-size: 14px; color: #f59e0b;"></i></div>
                <div class="stat-label">Rating</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="stat-earnings">0</div>
                <div class="stat-label">Earnings (MAD)</div>
            </div>
        </div>
    </div>

    <!-- TABS BAR -->
    <div class="tab-bar">
        <div class="tab-item active" onclick="switchTab('orders', this)">
            <i class="fa-solid fa-list-ul"></i> New Orders <span id="orders-badge-count" style="display:none; font-size:10px; background:#ef4444; color: #ffffff; padding:2px 6px; border-radius:10px; margin-left:5px;">0</span>
        </div>
        <div class="tab-item" onclick="switchTab('trips', this)">
            <i class="fa-solid fa-truck-fast"></i> active Trips
        </div>
        <div class="tab-item" onclick="switchTab('transactions', this)">
            <i class="fa-solid fa-file-invoice-dollar"></i> Transactions
        </div>
    </div>

    <!-- SECTION TITLE -->
    <div class="section-header" id="section-header">
        <h2 id="section-title"><i class="fa-solid fa-bolt"></i> Available Orders</h2>
        <div class="live-badge" id="live-indicator" style="display:none;">
            <div class="live-dot"></div> LIVE
        </div>
    </div>

    <!-- LIST CONTAINER -->
    <div class="order-list" id="main-list">
        <div class="skeleton-card">
            <div class="skeleton-row">
                <div class="skeleton-avatar shimmer-anim"></div>
                <div style="flex: 1;">
                    <div class="skeleton-line shimmer-anim w-50" style="height: 18px;"></div>
                    <div class="skeleton-line shimmer-anim w-80"></div>
                </div>
            </div>
            <div class="skeleton-line shimmer-anim" style="height: 40px; border-radius: 8px;"></div>
            <div class="skeleton-btn shimmer-anim"></div>
        </div>
        <div class="skeleton-card" style="opacity: 0.6;">
            <div class="skeleton-row">
                <div class="skeleton-avatar shimmer-anim"></div>
                <div style="flex: 1;">
                    <div class="skeleton-line shimmer-anim w-50" style="height: 18px;"></div>
                    <div class="skeleton-line shimmer-anim w-80"></div>
                </div>
            </div>
            <div class="skeleton-line shimmer-anim" style="height: 40px; border-radius: 8px;"></div>
            <div class="skeleton-btn shimmer-anim"></div>
        </div>
    </div>
</div>

<!-- OFFER MODAL -->
<div class="modal-overlay" id="offer-modal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-rocket"></i></div>
        <h3 id="modal-order-id">New Delivery Offer</h3>
        <p id="modal-order-desc">Deliver to boutique and customer.</p>
        
        <div class="form-group">
            <label class="form-label">Your Delivery Fee (MAD)</label>
            <input type="number" id="input-price" class="price-input" placeholder="e.g. 25" autofocus>
        </div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Back</button>
            <button class="btn-primary" id="confirm-offer-btn" style="flex:2;">Send Offer <i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<!-- WAITING MODAL -->
<div class="modal-overlay" id="waiting-modal" style="z-index: 10000;">
    <div class="modal-box">
        <div class="modal-icon" style="background: var(--bg-card)beb; color: #f59e0b; border-color: #fef3c7;">
            <i class="fa-solid fa-hourglass-half fa-spin"></i>
        </div>
        <h3>Sent!</h3>
        <p>Your offer has been sent to the customer. We're waiting for their confirmation.</p>
        <div style="font-size: 42px; font-weight: 900; color: #f59e0b; margin-bottom: 20px;" id="waiting-timer">01:00</div>
        <button class="btn-cancel" id="waiting-close-btn" disabled style="width:100%; opacity:0.5;">Please wait...</button>
    </div>
</div>

<!-- LIMIT MODAL (APP STOP) -->
<div class="modal-overlay" id="limit-modal" style="z-index: 20000; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(15px);">
    <div class="modal-box" style="border: 2px solid #ef4444; box-shadow: 0 0 50px rgba(239, 68, 68, 0.4);">
        <div class="modal-icon" style="background: #fee2e2; color: #ef4444; border-color: #fecaca; width: 90px; height: 90px; font-size: 40px;">
            <i class="fa-solid fa-triangle-exclamation fa-beat-fade"></i>
        </div>
        <h3 style="color:#b91c1c; font-size: 26px; text-transform: uppercase;">App Locked</h3>
        <p style="font-weight:800; font-size: 15px; color:#0f172a;">You have exceeded your cash debt limit.</p>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; text-align: left;">
            <p style="color:#991b1b; font-size: 13px; margin-bottom: 0; line-height: 1.5;">
                <i class="fa-solid fa-gavel"></i> <strong>URGENT WARNING:</strong> You are legally required to return the collected cash within <strong>24 hours</strong>. Failure to transfer the money will result in the immediate opening of a legal case against you and permanent suspension of your account.
            </p>
        </div>
        <div class="modal-actions" style="margin-top:20px;">
            <button class="btn-primary" style="width:100%; background: #ef4444; font-size: 16px; font-weight: 900;" onclick="document.getElementById('limit-modal').classList.remove('active'); openReturnCashModal();">TRANSFER MONEY NOW</button>
        </div>
    </div>
</div>

<!-- RETURN CASH MODAL -->
<div class="modal-overlay" id="return-cash-modal">
    <div class="modal-box">
        <div class="modal-icon" style="background:#dcfce7; color:#16a34a; border-color:#bbf7d0;"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <h3 style="font-size: 20px;">Send Cash Collected</h3>
        <p style="font-size: 13px;">Review your balance before sending the return request to the admin.</p>
        
        <div style="background:#f8fafc; border-radius:12px; padding:15px; margin-bottom:20px; text-align:left;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="color:#64748b; font-size:13px; font-weight:600;">Total Cash Debt:</span>
                <span style="font-weight:700;" id="modal-raw-debt">0.00 MAD</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#10b981;">
                <span style="font-size:13px; font-weight:600;">Minus Wallet (Online):</span>
                <span style="font-weight:700;">- <span id="modal-wallet">0.00</span> MAD</span>
            </div>
            <hr style="border:none; border-top:1px dashed #cbd5e1; margin:10px 0;">
            <div style="display:flex; justify-content:space-between; font-size:16px;">
                <span style="font-weight:800; color:#0f172a;">Total to Return:</span>
                <span style="font-weight:900; color:#ef4444;" id="modal-net-debt">0.00 MAD</span>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('return-cash-modal').classList.remove('active')">Cancel</button>
            <button class="btn-primary" style="background:#16a34a; flex:2;" onclick="submitCashReturn()">Transfer <i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>

const DriverData = JSON.parse(localStorage.getItem('qoon_driver') || '{}');
let userLat = 0, userLng = 0, isOnline = false, pollTimer = null, skippedOrders = new Set(), todayCount = 0, totalEarned = 0, activeOrderId = null;
let currentTab = 'orders';
let transactionsData = [];
let driverCashCollected = 0.0, driverCashLimit = 350.0, driverWalletBalance = 0.0;

function updateAvatar(name, photo) {
    const n = (name || 'Driver').trim();
    document.getElementById('driver-name-display').textContent = n;
    const photoUrl = (photo && photo !== '0' && photo !== '') ? photo : `https://ui-avatars.com/api/?name=${encodeURIComponent(n)}&background=6366f1&color=fff&size=128`;
    document.getElementById('driver-avatar').src = photoUrl;
}

async function loadDriverStats() {
    try {
        const fd = new FormData();
        fd.append('DriverID', DriverData.id || 0);
        const response = await fetch('api_driver_stats.php', { method: 'POST', body: fd });
        const result = await response.json();
        if (result.success) {
            const data = result.data;
            document.getElementById('stat-today').textContent = data.todayTrips;
            document.getElementById('stat-rating').innerHTML = parseFloat(data.driverRating).toFixed(1) + ' <i class="fa-solid fa-star" style="font-size: 14px; color: #f59e0b;"></i>';
            document.getElementById('stat-earnings').textContent = Math.round(data.totalEarnings);
            
            const cc = parseFloat(data.cashCollected);
            const limit = parseFloat(data.cashLimit);
            
            driverCashCollected = cc;
            driverCashLimit = limit;
            
            document.getElementById('stat-cash-collected').textContent = cc.toFixed(2);
            document.getElementById('cash-collect-limit-text').textContent = 'Limit: ' + limit;
            
            if (data.walletBalance !== undefined) {
                driverWalletBalance = parseFloat(data.walletBalance);
                document.getElementById('stat-wallet-balance').textContent = driverWalletBalance.toFixed(2);
            }
            
            const bar = document.getElementById('cash-collect-bar');
            const title = document.getElementById('cash-collect-title');
            const val = document.getElementById('stat-cash-collected').parentElement;
            const limitText = document.getElementById('cash-collect-limit-text');
            const icon = document.getElementById('cash-collect-icon');

            if (cc >= limit) {
                bar.style.background = '#fee2e2';
                title.style.color = '#ef4444';
                val.style.color = '#b91c1c';
                limitText.style.color = '#ef4444';
                icon.style.color = '#ef4444';
                icon.className = 'fa-solid fa-triangle-exclamation';
                
                document.getElementById('limit-modal').classList.add('active');
            } else {
                bar.style.background = '#d1fae5';
                title.style.color = '#10b981';
                val.style.color = '#047857';
                limitText.style.color = '#10b981';
                icon.style.color = '#10b981';
                icon.className = 'fa-solid fa-wallet';
            }
        }
    } catch(e) {}
}

function openReturnCashModal() {
    const net = driverCashCollected;
    const wallet = driverWalletBalance;
    const raw = net + wallet;
    
    document.getElementById('modal-raw-debt').textContent = raw.toFixed(2) + ' MAD';
    document.getElementById('modal-wallet').textContent = wallet.toFixed(2);
    document.getElementById('modal-net-debt').textContent = net.toFixed(2) + ' MAD';
    
    document.getElementById('return-cash-modal').classList.add('active');
}

function submitCashReturn() {
    const btn = document.querySelector('#return-cash-modal .btn-primary');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;
    
    setTimeout(() => {
        document.getElementById('return-cash-modal').classList.remove('active');
        
        const successModal = document.createElement('div');
        successModal.className = 'modal-overlay active';
        successModal.style.zIndex = '10000';
        successModal.innerHTML = `
            <div class="modal-box">
                <div class="modal-icon" style="background:#dcfce7; color:#16a34a; border-color:#bbf7d0;"><i class="fa-solid fa-check"></i></div>
                <h3 style="color:#16a34a;">Request Sent!</h3>
                <p style="font-size:14px;">Your request to return cash has been successfully submitted to the admin.</p>
                <div class="modal-actions" style="margin-top:20px;">
                    <button class="btn-primary" style="width:100%; background:#16a34a;" onclick="this.closest('.modal-overlay').remove()">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(successModal);
        
        btn.innerHTML = 'Transfer <i class="fa-solid fa-paper-plane"></i>';
        btn.disabled = false;
    }, 1500);
}


async function syncProfile() {
    if (!DriverData.id) return;
    try {
        const fd = new FormData(); fd.append('DriverID', DriverData.id);
        const r = await fetch('GetDriverInfo.php', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success && j.data) {
            const db = j.data;
            const fullName = ((db.FName || '') + ' ' + (db.LName || '')).trim();
            updateAvatar(fullName, db.PersonalPhoto);
            DriverData.name = fullName; DriverData.photo = db.PersonalPhoto;
            localStorage.setItem('qoon_driver', JSON.stringify(DriverData));
        }
    } catch (e) { console.error("Sync Profile Error:", e); }
}

function toggleOnline() {
    isOnline = !isOnline;
    const pill = document.getElementById('status-pill');
    pill.classList.toggle('online', isOnline);
    document.getElementById('status-text').textContent = isOnline ? 'Online' : 'Offline';
    document.getElementById('live-indicator').style.display = isOnline ? 'flex' : 'none';
    
    if (isOnline) {
        startLocating();
        startPolling();
    } else {
        if (pollTimer) clearInterval(pollTimer);
        showEmptyState('Go online to receive new delivery orders near you.');
    }
}

function startLocating() {
    if (!navigator.geolocation) {
        document.getElementById('location-display').innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> GPS Not Supported';
        return;
    }
    navigator.geolocation.watchPosition(pos => {
        userLat = pos.coords.latitude; userLng = pos.coords.longitude;
        document.getElementById('location-display').innerHTML = `<i class="fa-solid fa-location-dot" style="color:var(--brand-primary)"></i> ${userLat.toFixed(4)}, ${userLng.toFixed(4)}`;
    }, err => {
        userLat = 33.5731; userLng = -7.5898; // Default
        document.getElementById('location-display').innerHTML = '<i class="fa-solid fa-location-dot"></i> Default: Casablanca';
    }, { enableHighAccuracy: true });
}

const shimmerHtml = `
        <div class="skeleton-card">
            <div class="skeleton-row">
                <div class="skeleton-avatar shimmer-anim"></div>
                <div style="flex: 1;">
                    <div class="skeleton-line shimmer-anim w-50" style="height: 18px;"></div>
                    <div class="skeleton-line shimmer-anim w-80"></div>
                </div>
            </div>
            <div class="skeleton-line shimmer-anim" style="height: 40px; border-radius: 8px;"></div>
            <div class="skeleton-btn shimmer-anim"></div>
        </div>
        <div class="skeleton-card" style="opacity: 0.6;">
            <div class="skeleton-row">
                <div class="skeleton-avatar shimmer-anim"></div>
                <div style="flex: 1;">
                    <div class="skeleton-line shimmer-anim w-50" style="height: 18px;"></div>
                    <div class="skeleton-line shimmer-anim w-80"></div>
                </div>
            </div>
            <div class="skeleton-line shimmer-anim" style="height: 40px; border-radius: 8px;"></div>
            <div class="skeleton-btn shimmer-anim"></div>
        </div>
`;

function switchTab(tab, element) {
    document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
    element.classList.add('active');
    currentTab = tab;
    
    const titleEl = document.getElementById('section-title');
    if (tab === 'orders') titleEl.innerHTML = '<i class="fa-solid fa-bolt"></i> Available Orders';
    else if (tab === 'trips') titleEl.innerHTML = '<i class="fa-solid fa-route"></i> Active Trips';
    else if (tab === 'transactions') titleEl.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Transactions';

    document.getElementById('main-list').innerHTML = shimmerHtml;
    refreshData();
}

function startPolling() {
    refreshData();
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(refreshData, 12000);
}

function refreshData() {
    if (currentTab === 'orders') {
        if (!isOnline) {
            showEmptyState('Go online to receive new delivery orders near you.');
            return;
        }
        fetchAvailableOrders();
    }
    else if (currentTab === 'trips') fetchActiveTrips();
    else if (currentTab === 'transactions') fetchTransactions();
}

async function fetchAvailableOrders() {
    const lat = userLat || 33.5731, lng = userLng || -7.5898;
    try {
        const response = await fetch(`api_driver_orders.php?lat=${lat}&lng=${lng}&_=${Date.now()}`);
        const result = await response.json();
        if (result.success) {
            renderOrders(result.data);
            // document.getElementById('stat-nearby').textContent = result.count || 0; // Removed to prevent crash
        } else {
            showEmptyState('Scanning for new orders in your area...');
        }
    } catch (e) { showEmptyState('Searching for signals...'); }
}

async function fetchActiveTrips() {
    try {
        const fd = new FormData();
        fd.append('DelvryId', DriverData.id || 0);
        fd.append('Page', '0');
        const response = await fetch('getDriveCurrentOrders.php', { method: 'POST', body: fd });
        const result = await response.json();
        if (result.success && result.data) {
            const activeTrips = result.data;
            if (activeTrips.length > 0) {
                renderTrips(activeTrips);
            } else {
                showEmptyState('You have no active deliveries right now.');
            }
        } else {
            showEmptyState('You have no active deliveries right now.');
        }
    } catch (e) { showEmptyState('Error fetching trips.'); }
}

let transactionPage = 0;
let hasMoreTransactions = true;
let isLoadingTransactions = false;

async function fetchTransactions(loadMore = false) {
    if (isLoadingTransactions || (!hasMoreTransactions && loadMore)) return;
    
    isLoadingTransactions = true;
    if (!loadMore) {
        transactionPage = 0;
        hasMoreTransactions = true;
    }
    
    try {
        const fd = new FormData();
        fd.append('DriverID', DriverData.id || 0);
        fd.append('Page', transactionPage);
        const response = await fetch('api_driver_transactions.php', { method: 'POST', body: fd });
        const result = await response.json();
        if (result.success) {
            if (!loadMore) {
                transactionsData = result.data;
            } else {
                transactionsData = transactionsData.concat(result.data);
            }
            
            hasMoreTransactions = result.data.length >= 20;
            renderTransactions();
        } else {
            if (!loadMore) showEmptyState('No transactions found.');
        }
    } catch (e) { 
        if (!loadMore) showEmptyState('Error fetching transactions.'); 
    }
    
    isLoadingTransactions = false;
}

function loadMoreTransactions() {
    const btn = document.getElementById('load-more-btn');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
    transactionPage++;
    fetchTransactions(true);
}

function renderTransactions() {
    if (!transactionsData || transactionsData.length === 0) {
        document.getElementById('main-list').innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-receipt empty-icon"></i>
                <h3>No Transactions</h3>
                <p>You haven't completed any deliveries yet.</p>
            </div>`;
        return;
    }

    let listHtml = transactionsData.map(t => {
        const earnings = parseFloat(t.Earnings || 0);
        const cashCollect = parseFloat(t.CashCollected || 0);
        const showCash = (t.ShopRecive === 'NO' && cashCollect > 0);
        
        let moneyBadges = `<div style="font-weight:800; font-size:15px; color:#10b981; text-align:right;">+${earnings.toFixed(2)} MAD <span style="font-size:10px; opacity:0.8; font-weight:600; display:block;">EARNINGS</span></div>`;
        
        if (showCash) {
            moneyBadges += `<div style="font-weight:800; font-size:15px; color:#ef4444; text-align:right; margin-top:8px;">${cashCollect.toFixed(2)} MAD <span style="font-size:10px; opacity:0.8; font-weight:600; display:block;">CASH COLLECTED</span></div>`;
        }

        return `
        <div style="background:var(--bg-card); border-radius:16px; padding:16px; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:44px; height:44px; border-radius:12px; background:var(--bg-alt); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div>
                    <div style="font-weight:700; color:var(--text-main); font-size:15px;">Order #${t.OrderID}</div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:3px;"><i class="fa-regular fa-clock"></i> ${getTimeAgo(t.CreatedAtOrders)}</div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:1px;"><i class="fa-solid fa-store"></i> ${escapeHtml(t.DestinationName)}</div>
                </div>
            </div>
            <div>
                ${moneyBadges}
            </div>
        </div>`;
    }).join('');

    if (hasMoreTransactions) {
        listHtml += `
            <button id="load-more-btn" onclick="loadMoreTransactions()" style="width:100%; padding:15px; background:rgba(99, 102, 241, 0.1); color:var(--brand-primary); border:none; border-radius:16px; font-size:15px; font-weight:800; cursor:pointer; margin-top:10px; margin-bottom: 20px; transition:0.2s;">
                Load More Transactions
            </button>
        `;
    }

    document.getElementById('main-list').innerHTML = listHtml;
}

function renderOrders(orders) {
    const filtered = orders.filter(o => !skippedOrders.has(parseInt(o.OrderID)));
    if (filtered.length === 0) {
        showEmptyState('Everything looks clear! We will alert you when a new order drops.');
        return;
    }
    
    // Update Badge
    const badge = document.getElementById('orders-badge-count');
    badge.textContent = filtered.length;
    badge.style.display = 'inline-block';

    document.getElementById('main-list').innerHTML = filtered.slice(0, 1).map((o, idx) => {
        const isScheduled = o.OrderType === 'SLOW';
        const shopName = o.DestinationName || 'Boutique';
        const shopPhoto = (o.DestnationPhoto && o.DestnationPhoto !== '0') ? o.DestnationPhoto : `https://ui-avatars.com/api/?name=${encodeURIComponent(shopName)}&background=6366f1&color=fff&size=128`;
        const userName = o.UserName || 'Customer';
        const userPhoto = (o.UserPhoto && o.UserPhoto !== '0') ? o.UserPhoto : `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=0ea5e9&color=fff&size=64`;
        const snippet = (o.OrderDetails || 'No specific instructions').replace(/\n/g, ' | ').substring(0, 100);
        
        return `
        <div class="order-card" style="animation-delay: ${idx * 0.1}s">
            <div class="order-status-bar ${isScheduled ? 'scheduled' : ''}"></div>
            <div class="card-content">
                <div class="shop-row">
                    <img src="${escapeHtml(shopPhoto)}" class="shop-img">
                    <div class="shop-info">
                        <div class="shop-name">${escapeHtml(shopName)}</div>
                        <div class="shop-address"><i class="fa-solid fa-map-pin" style="color:#ef4444"></i> ${escapeHtml(o.DestnationAddress || 'Pickup Point')}</div>
                        <div class="order-tag ${!isScheduled ? 'express' : ''}">
                            <i class="fa-solid ${isScheduled ? 'fa-clock' : 'fa-bolt'}"></i> ${isScheduled ? 'Scheduled Delivery' : 'Express Delivery'}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:900; color:var(--brand-primary); font-size:14px;">${o.distance || '0.0'} km</div>
                        <div style="font-size:10px; color:var(--text-muted); font-weight:700;">${getTimeAgo(o.CreatedAtOrders)}</div>
                    </div>
                </div>
                <div class="order-details-box">${escapeHtml(snippet)}</div>
                <div class="customer-row">
                    <img src="${escapeHtml(userPhoto)}" class="customer-img">
                    <div class="customer-name">${escapeHtml(userName)}</div>
                    <div class="order-id-label">#${o.OrderID}</div>
                </div>
                <div class="card-actions">
                    <button class="btn-primary" onclick="openOfferModal(${o.OrderID}, '${escapeHtml(shopName)}', '${o.distance || '0.0'}')">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Accept Now
                    </button>
                    <button class="btn-secondary" onclick="skipOrder(${o.OrderID})"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>`;
    }).join('');
}

function renderTrips(trips) {
    document.getElementById('main-list').innerHTML = trips.map(t => {
        let stateDisplay = t.OrderState;
        if (stateDisplay && stateDisplay.toLowerCase() === 'rated') {
            stateDisplay = 'Delivered';
        }
        
        let statusClass = 'trip-status';
        let sLower = (t.OrderState || '').toLowerCase();
        if (sLower === 'cancelled' || sLower === 'canceled') {
            statusClass += ' cancelled';
        } else if (sLower === 'returned' || sLower === 'return') {
            statusClass += ' returned';
        }
        
        return `
        <div class="trip-card" onclick="window.location.href='driver_chat.php?orderId=${t.OrderID}'">
            <div class="trip-header">
                <div class="trip-id">Delivery #${t.OrderID}</div>
                <div class="${statusClass}">${stateDisplay}</div>
            </div>
            <div class="trip-route">
                <div class="route-point">
                    <div class="route-label">Pickup</div>
                    <div class="route-name">${escapeHtml(t.DestinationName || 'Boutique')}</div>
                </div>
                <div class="route-point end">
                    <div class="route-label">Destination</div>
                    <div class="route-name">${escapeHtml(t.UserName || 'Customer')}</div>
                </div>
            </div>
            <button class="btn-primary" style="width:100%; margin-top:20px; background:var(--brand-secondary); box-shadow:0 4px 15px rgba(14, 165, 233, 0.3);">
                <i class="fa-solid fa-location-arrow"></i> Open Map & Tracker
            </button>
        </div>`;
    }).join('');
}

function showEmptyState(message) {
    document.getElementById('main-list').innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-radar empty-icon"></i>
            <h3>No Orders Found</h3>
            <p>${message}</p>
        </div>`;
    document.getElementById('orders-badge-count').style.display = 'none';
}

function openOfferModal(id, shopName, distance) {
    if (driverCashCollected >= driverCashLimit) {
        document.getElementById('limit-modal').classList.add('active');
        return;
    }
    
    activeOrderId = id;
    document.getElementById('modal-order-id').textContent = `Order #${id}`;
    document.getElementById('modal-order-desc').innerHTML = `Boutique: <strong>${shopName}</strong><br>Distance: ${distance} km from you`;
    document.getElementById('input-price').value = '';
    document.getElementById('offer-modal').classList.add('active');
    document.getElementById('input-price').focus();
    
    document.getElementById('confirm-offer-btn').onclick = () => sendOffer(id);
}

function closeModal() { document.getElementById('offer-modal').classList.remove('active'); }

async function sendOffer(id) {
    const price = document.getElementById('input-price').value;
    if (!price || isNaN(price) || price <= 0) {
        alert("Please specify a delivery fee.");
        return;
    }

    const btn = document.getElementById('confirm-offer-btn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;

    try {
        const fd = new FormData(); 
        fd.append('OrderId', id); 
        fd.append('DriverID', DriverData.id || 0); 
        fd.append('Price', price);
        
        await fetch('addoffer.php', { method: 'POST', body: fd });
        
        skippedOrders.add(id);
        todayCount++;
        totalEarned += parseFloat(price);
        document.getElementById('stat-today').textContent = todayCount;
        document.getElementById('stat-earnings').textContent = totalEarned.toFixed(0);
        
        closeModal();
        startWaitingCountdown(id);
        
    } catch (e) { alert("Failed to send offer. Check connection."); }
    
    btn.innerHTML = 'Send Offer <i class="fa-solid fa-paper-plane"></i>';
    btn.disabled = false;
}

function startWaitingCountdown(orderId) {
    document.getElementById('waiting-modal').classList.add('active');
    let secondsLeft = 60;
    const timerEl = document.getElementById('waiting-timer');
    const closeBtn = document.getElementById('waiting-close-btn');
    
    timerEl.textContent = "01:00";
    closeBtn.disabled = true;
    closeBtn.style.opacity = '0.5';
    closeBtn.textContent = "Waiting for customer...";
    closeBtn.style.background = '';
    
    let isConfirmed = false;
    const pollInterval = setInterval(async () => {
        if (isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('DelvryId', DriverData.id || 0);
            fd.append('Page', '0');
            const r = await fetch('getDriveCurrentOrders.php', { method: 'POST', body: fd });
            const j = await r.json();
            if (j.success && j.data && j.data.find(o => parseInt(o.OrderID) === parseInt(orderId))) {
                isConfirmed = true;
                clearInterval(countdownInterval);
                clearInterval(pollInterval);
                
                // Show Success in Waiting Modal
                document.querySelector('#waiting-modal h3').textContent = "Offer Accepted! 🎉";
                document.querySelector('#waiting-modal p').textContent = "Success! The customer has accepted your offer. Let's go!";
                timerEl.style.display = 'none';
                
                closeBtn.disabled = false;
                closeBtn.style.opacity = '1';
                
                setTimeout(() => {
                    window.location.href = `driver_chat.php?orderId=${orderId}`;
                }, 1000);
                closeBtn.textContent = "Go to Order Tracking";
                closeBtn.style.background = '#22c55e';
                closeBtn.style.color = 'var(--bg-card)';
                closeBtn.onclick = () => {
                    document.getElementById('waiting-modal').classList.remove('active');
                    window.location.href = `driver_chat.php?orderId=${orderId}`;
                };
            }
        } catch (e) {}
    }, 3000);

    const countdownInterval = setInterval(() => {
        if (isConfirmed) return;
        secondsLeft--;
        if (secondsLeft < 0) secondsLeft = 0;
        timerEl.textContent = `00:${secondsLeft < 10 ? '0' + secondsLeft : secondsLeft}`;
        
        if (secondsLeft <= 0) {
            clearInterval(countdownInterval);
            clearInterval(pollInterval);
            
            closeBtn.disabled = false;
            closeBtn.style.opacity = '1';
            closeBtn.textContent = "View Other Orders";
            closeBtn.style.background = 'var(--brand-primary)';
            closeBtn.style.color = 'var(--bg-card)';
            closeBtn.onclick = () => {
                document.getElementById('waiting-modal').classList.remove('active');
                if (currentTab === 'orders') fetchAvailableOrders();
            };
        }
    }, 1000);
}

function skipOrder(id) { skippedOrders.add(id); fetchAvailableOrders(); }
function escapeHtml(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
function getTimeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    return Math.floor(diff / 3600) + 'h ago';
}

document.addEventListener('DOMContentLoaded', () => {
    if (DriverData.id) {
        updateAvatar(DriverData.name, DriverData.photo);
        loadDriverStats();
        syncProfile();
        setTimeout(toggleOnline, 500);
    } else {
        window.location.href = 'qoonexpress.login.php';
    }
});
</script>
</body>
</html>
