<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$domain = $DomainNamee ?? 'https://qoon.app/dash/';

// Fetch Moroccan cities from DeliveryZone
$cities = [];
if ($con) {
    // Assuming CountryID 1 is Morocco or we just fetch all cities for now
    $res = $con->query("SELECT DeliveryZoneID as CityID, CityName, Photo FROM DeliveryZone ORDER BY CityName ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cities[] = $row;
        }
    }
}

// Helper for generating deterministic colors based on string
function stringToColorCode($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $c = ($hash & 0x00FFFFFF);
    return str_pad(dechex($c), 6, '0', STR_PAD_LEFT);
}

// Fetch logged-in user profile
$userProfile = null;
if (isset($_COOKIE['qoon_user_id'])) {
    $uid = intval($_COOKIE['qoon_user_id']);
    if ($con) {
        $uRes = $con->query("SELECT * FROM Users WHERE UserID = $uid");
        if ($uRes && $uRow = $uRes->fetch_assoc()) {
            $userProfile = $uRow;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QOON Express · Send & Receive</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            --bg-deep: #050510;
            --express-blue: #2cb5e8;
            --express-pink: #ff0080;
            --express-purple: #6a11cb;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass: rgba(20, 20, 40, 0.4);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            line-height: 1.6;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(44, 181, 232, 0.05), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(255, 0, 128, 0.05), transparent 25%);
        }

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--express-blue); }

        /* --- Navigation --- */
        .top-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 24px 40px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to bottom, rgba(5,5,16,0.9), transparent); backdrop-filter: blur(10px); }
        .btn-circle { width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; }
        .btn-circle:hover { background: var(--express-blue); border-color: transparent; transform: scale(1.1); box-shadow: 0 0 20px var(--express-blue); }

        @media (max-width: 768px) {
            .top-nav { padding: 15px 20px; }
            .btn-circle { width: 40px; height: 40px; }
        }

        /* --- Hero Section --- */
        .express-header {
            position: relative;
            padding: 160px 40px 100px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .header-bg-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80vw;
            height: 80vw;
            max-width: 800px;
            max-height: 800px;
            background: radial-gradient(circle, rgba(44, 181, 232, 0.15) 0%, transparent 60%);
            z-index: 0;
            filter: blur(50px);
        }

        .header-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 800px;
        }

        .express-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(44, 181, 232, 0.1);
            border: 1px solid rgba(44, 181, 232, 0.3);
            color: #2cb5e8;
            padding: 6px 16px;
            border-radius: 99px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
        }

        .header-content h1 {
            font-size: clamp(40px, 8vw, 80px);
            font-weight: 800;
            letter-spacing: -2px;
            line-height: 1.1;
            margin-bottom: 24px;
        }

        .header-content h1 span {
            background: linear-gradient(135deg, #2cb5e8, #6a11cb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-content p {
            font-size: clamp(16px, 2.5vw, 20px);
            color: rgba(255,255,255,0.7);
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto 40px;
        }

        /* --- Action Buttons --- */
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 220px;
            height: 160px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(44, 181, 232, 0.2), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .action-btn:nth-child(2)::before {
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.2), transparent);
        }

        .action-btn:hover {
            transform: translateY(-8px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        
        .action-btn:hover::before { opacity: 1; }

        .action-btn i {
            font-size: 40px;
            color: #2cb5e8;
            transition: transform 0.3s;
            position: relative;
            z-index: 2;
        }

        .action-btn:nth-child(2) i { color: #ff0080; }
        
        .action-btn:hover i { transform: scale(1.1) translateY(-5px); }

        .action-btn span {
            font-size: 18px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        /* --- Cities Section --- */
        .cities-section {
            width: calc(100% + 40px);
            margin: 0 -40px 100px 0;
            padding: 0 0 40px 0;
            overflow: hidden;
        }

        .cities-header-wrap {
            padding: 0 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sec-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sec-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--express-blue);
            border-radius: 99px;
            box-shadow: 0 0 15px var(--express-blue);
        }

        .city-grid {
            display: flex;
            gap: 20px;
            padding: 0 40px 50px 40px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .city-grid::-webkit-scrollbar { display: none; }

        .city-card {
            width: 200px;
            aspect-ratio: 9/16;
            border-radius: 20px;
            flex-shrink: 0;
            scroll-snap-align: start;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #111;
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.2,0.8,0.2,1);
            text-decoration: none;
            display: flex;
            align-items: flex-end;
        }

        .city-card:hover {
            transform: scale(1.05);
            border-color: var(--express-blue);
            box-shadow: 0 20px 40px rgba(44, 181, 232, 0.3);
        }

        .city-img {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.8s;
            z-index: 1;
        }

        .city-card:hover .city-img {
            transform: scale(1.1);
        }

        .city-card-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 60%;
            background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, transparent 100%);
            z-index: 2;
        }

        .city-name-wrap {
            position: relative;
            z-index: 3;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .city-name {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
            line-height: 1.1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cities-header-wrap { padding: 0 20px; }
            .city-grid { padding: 0 20px 40px 20px; gap: 15px; }
            .city-card { width: 160px; border-radius: 16px; }
            .city-name { font-size: 18px; }
            .action-btn { width: 160px; height: 140px; }
            .action-btn i { font-size: 32px; }
            .action-btn span { font-size: 15px; }
        }

        /* --- Floating Package Modal (Optional for future) --- */
        #packageModal {
            position: fixed; inset: 0; z-index: 2000;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        #packageModal.active { display: flex; opacity: 1; }
        .modal-content {
            background: #0a0a1a; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px; padding: 40px; max-width: 500px; width: 90%;
            transform: translateY(20px); transition: transform 0.3s;
            position: relative;
            max-height: 90vh; overflow-y: auto;
        }
        #packageModal.active .modal-content { transform: translateY(0); }
        .close-modal { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }

        /* --- Modal Multi-step Form --- */
        .step { display: none; animation: slideIn 0.3s ease; }
        .step.active { display: block; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; color: rgba(255,255,255,0.7); margin-bottom: 5px; }
        .form-input { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: #fff; font-size: 15px; outline: none; transition: border-color 0.3s; }
        .form-input:focus { border-color: var(--express-blue); }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .btn-primary { width: 100%; padding: 15px; background: var(--express-blue); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-primary:hover { transform: scale(1.02); box-shadow: 0 5px 15px rgba(44, 181, 232, 0.4); }
        .btn-secondary { width: 100%; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }

        .step-indicator { display: flex; gap: 8px; justify-content: center; margin-bottom: 25px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,0.2); transition: 0.3s; }
        .step-dot.active { background: var(--express-blue); transform: scale(1.2); box-shadow: 0 0 10px var(--express-blue); }

        /* --- Custom Size Selector Modal --- */
        .size-selector { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        #sizeModal {
            position: fixed; inset: 0; z-index: 3000;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        #sizeModal.active { display: flex; opacity: 1; }
        .size-modal-content {
            background: #0a0a1a; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px; padding: 30px; width: 90%; max-width: 500px;
            transform: scale(0.95) translateY(20px); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        #sizeModal.active .size-modal-content { transform: scale(1) translateY(0); }
        
        .size-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;
        }
        .size-card {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 20px 15px; text-align: center; cursor: pointer;
            transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .size-card:hover, .size-card.selected {
            background: rgba(44, 181, 232, 0.1); border-color: var(--express-blue);
            transform: translateY(-5px);
        }
        .size-card i { font-size: 32px; color: var(--express-blue); }
        .size-card-title { font-size: 16px; font-weight: 700; color: #fff; }
        .size-card-desc { font-size: 12px; color: rgba(255,255,255,0.6); }

        /* --- Map Modal --- */
        #mapModal {
            position: fixed; inset: 0; z-index: 4000;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
            display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        #mapModal.active { display: flex; opacity: 1; }
        .map-modal-content {
            background: #0a0a1a; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px; width: 95%; max-width: 600px; height: 80vh;
            display: flex; flex-direction: column; overflow: hidden;
            transform: scale(0.95); transition: transform 0.3s;
        }
        #mapModal.active .map-modal-content { transform: scale(1); }
        #mapContainer { flex: 1; width: 100%; background: #222; }
        .map-header { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.05); }
        .map-footer { padding: 15px 20px; background: rgba(255,255,255,0.05); }

    </style>
</head>
<body>

    <nav class="top-nav">
        <div style="display:flex; align-items:center; gap:20px;">
            <a href="index.php" class="btn-circle"><i class="fa-solid fa-arrow-left"></i></a>
            <img src="logo_qoon_white.png" alt="QOON" style="height:32px; width:auto; object-fit:contain;" onerror="this.style.display='none'">
        </div>
        <div class="header-actions">
            <?php if (isset($_COOKIE['qoon_user_id'])): ?>
                <a href="orders.php" class="btn-circle" title="My Orders"><i class="fa-solid fa-box-open"></i></a>
            <?php else: ?>
                <a href="index.php?auth_required=1" style="color:#fff; text-decoration:none; font-weight:600; padding:10px 20px; background:rgba(255,255,255,0.1); border-radius:99px;">Sign In</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="express-header">
        <div class="header-bg-glow"></div>
        <div class="header-content">
            <img src="logo_express.png" alt="QOON Express" style="width: 100%; max-width: 380px; height: auto; margin: 0 auto 30px; display: block; filter: drop-shadow(0 15px 35px rgba(255,0,128,0.4)); animation: floatLogo 4s ease-in-out infinite;">
            
            <style>
                @keyframes floatLogo {
                    0% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                    100% { transform: translateY(0); }
                }
            </style>
            
            <h1>Fast Delivery Across <span>Morocco</span></h1>
            <p>Send and receive boxes seamlessly between cities. Reliable, trackable, and built for your convenience.</p>
            
            <div class="action-buttons">
                <a href="javascript:void(0)" onclick="openAction('send')" class="action-btn">
                    <i class="fa-solid fa-box-open"></i>
                    <span>Send a Box</span>
                </a>
                <a href="javascript:void(0)" onclick="openAction('receive')" class="action-btn">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                    <span>Receive a Box</span>
                </a>
            </div>
        </div>
    </header>

    <section class="cities-section" id="citiesList">
        <div class="cities-header-wrap">
            <h2 class="sec-title">Available Cities</h2>
        </div>
        
        <?php if (empty($cities)): ?>
            <div style="text-align:center; padding: 50px; color: rgba(255,255,255,0.5);">
                <i class="fa-solid fa-map-location-dot" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>No cities available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="city-grid">
                <?php foreach ($cities as $city): 
                    $cName = htmlspecialchars($city['CityName'] ?? 'City');
                    $cPhoto = trim($city['Photo'] ?? '');
                    
                    if (!empty($cPhoto) && $cPhoto !== '0' && $cPhoto !== 'NONE') {
                        // Check if absolute URL or relative path
                        if (strpos($cPhoto, 'http') === 0) {
                            $imgUrl = $cPhoto;
                        } else {
                            $imgUrl = rtrim($domain, '/') . '/photo/' . ltrim($cPhoto, '/');
                        }
                    } else {
                        // Fallback to beautiful color-coded UI avatar
                        $bgColor = stringToColorCode($cName);
                        $imgUrl = "https://ui-avatars.com/api/?name=".urlencode($cName)."&background=".$bgColor."&color=fff&size=400&font-size=0.33&bold=true";
                    }
                ?>
                    <a href="javascript:void(0)" class="city-card" onclick="selectCity('<?= addslashes($cName) ?>', <?= intval($city['CityID']) ?>)">
                        <img src="<?= $imgUrl ?>" alt="<?= $cName ?>" class="city-img" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($cName) ?>&background=random&color=fff&size=400'">
                        <div class="city-card-overlay"></div>
                        <div class="city-name-wrap">
                            <span class="city-name"><?= $cName ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Multi-step Package Modal -->
    <div id="packageModal" onclick="if(event.target===this) closePackageModal()">
        <div class="modal-content">
            <button class="close-modal" onclick="closePackageModal()"><i class="fa-solid fa-xmark"></i></button>
            <h2 id="modalTitle" style="margin-bottom: 5px; font-size: 24px;">Setup Delivery</h2>
            <p style="color: rgba(255,255,255,0.6); margin-bottom: 25px;" id="modalDesc">City: <span id="modalCityName" style="color:#2cb5e8; font-weight:bold;"></span></p>

            <div class="step-indicator">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
            </div>

            <!-- Step 1: Contact Info -->
            <div class="step active" id="step-1">
                <div class="form-group">
                    <label class="form-label">Recipient Name</label>
                    <input type="text" class="form-input" id="exName" placeholder="Full Name">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-input" id="exPhone" placeholder="+212 600 000 000">
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex; justify-content:space-between;">
                        <span>Exact Address / Location</span>
                        <a href="javascript:void(0)" onclick="openMapModal()" style="color:var(--express-blue); text-decoration:none; font-weight:700;"><i class="fa-solid fa-map-location-dot"></i> Pick on Map</a>
                    </label>
                    <textarea class="form-input" id="exAddress" rows="2" placeholder="Street, Building, Apt..."></textarea>
                    <input type="hidden" id="exLat" value="">
                    <input type="hidden" id="exLng" value="">
                </div>
                <button class="btn-primary" onclick="goToStep(2)">Next: Package Details <i class="fa-solid fa-arrow-right"></i></button>
            </div>

            <!-- Step 2: Package Info -->
            <div class="step" id="step-2">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" class="form-input" id="exWeight" placeholder="e.g. 1.5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Volume (Size)</label>
                        <div class="form-input size-selector" onclick="openSizeModal()">
                            <span id="exSizeText">Medium (Shoebox)</span>
                            <input type="hidden" id="exSize" value="Medium">
                            <i class="fa-solid fa-chevron-down" style="color:rgba(255,255,255,0.5);"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">What's inside?</label>
                    <input type="text" class="form-input" id="exContent" placeholder="e.g. Clothes, Documents...">
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="exFragile" style="width:18px; height:18px; accent-color:var(--express-pink);">
                    <label class="form-label" style="margin:0; cursor:pointer;" onclick="document.getElementById('exFragile').click()">Fragile item (Handle with care)</label>
                </div>
                <div style="display:flex; gap:10px;">
                    <button class="btn-secondary" onclick="goToStep(1)">Back</button>
                    <button class="btn-primary" onclick="goToStep(3)">Review <i class="fa-solid fa-check"></i></button>
                </div>
            </div>

            <!-- Step 3: Summary -->
            <div class="step" id="step-3">
                <div style="max-height: 55vh; overflow-y: auto; padding-right: 5px; margin-bottom: 20px;">
                    <!-- Recipient Info -->
                    <h4 style="font-size: 13px; color: var(--express-blue); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Recipient Details</h4>
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="color:rgba(255,255,255,0.6);">Action:</span>
                            <strong id="sumAction" style="text-transform:capitalize; color:#2cb5e8;">Send</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="color:rgba(255,255,255,0.6);">City:</span>
                            <strong id="sumCity"></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="color:rgba(255,255,255,0.6);">Recipient:</span>
                            <strong id="sumName"></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="color:rgba(255,255,255,0.6);">Phone:</span>
                            <strong id="sumPhone"></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:rgba(255,255,255,0.6);">Package:</span>
                            <strong id="sumPackage"></strong>
                        </div>
                    </div>

                    <!-- Sender Info (My Profile) -->
                    <h4 style="font-size: 13px; color: var(--express-pink); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Your Details (Pick Up)</h4>
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                        <?php if ($userProfile): ?>
                            <div style="display:flex; align-items:center; gap: 15px; margin-bottom: 15px;">
                                <?php 
                                $uPic = !empty($userProfile['Photo']) ? rtrim($domain, '/') . '/photo/' . ltrim($userProfile['Photo'], '/') : 'https://ui-avatars.com/api/?name='.urlencode($userProfile['FullName'] ?? 'User').'&background=random&color=fff';
                                ?>
                                <img src="<?= htmlspecialchars($uPic) ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--express-pink);">
                                <div>
                                    <div style="font-weight: 700; font-size: 16px;"><?= htmlspecialchars($userProfile['FullName'] ?? 'My Profile') ?></div>
                                    <div style="font-size: 12px; color: rgba(255,255,255,0.6);">Sender</div>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="form-label">My Phone Number</label>
                                <input type="tel" class="form-input" id="myPhone" value="<?= htmlspecialchars($userProfile['Phone'] ?? '') ?>" placeholder="Update your phone number">
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom: 15px; color: #ff0080; font-size: 13px;"><i class="fa-solid fa-circle-info"></i> Please log in to auto-fill your details.</div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="form-label">My Phone Number</label>
                                <input type="tel" class="form-input" id="myPhone" placeholder="Enter your phone number">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="display:flex; justify-content:space-between;">
                                <span>My Pick Up Location</span>
                                <a href="javascript:void(0)" onclick="openMapModal(true)" style="color:var(--express-pink); text-decoration:none; font-weight:700;"><i class="fa-solid fa-map-location-dot"></i> Pick on Map</a>
                            </label>
                            <textarea class="form-input" id="myAddress" rows="2" placeholder="Where should the driver pick this up?"></textarea>
                            <input type="hidden" id="myLat" value="">
                            <input type="hidden" id="myLng" value="">
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button class="btn-secondary" onclick="goToStep(2)">Back</button>
                    <button class="btn-primary" onclick="submitExpress()">Confirm Request</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Graphical Size Selector Modal -->
    <div id="sizeModal" onclick="if(event.target===this) closeSizeModal()">
        <div class="size-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="font-size:20px; font-weight:700;">Select Volume</h3>
                <button onclick="closeSizeModal()" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p style="color:rgba(255,255,255,0.6); font-size:14px;">Choose the size that best matches your package.</p>
            
            <div class="size-grid">
                <div class="size-card" onclick="pickSize('Small', 'Small (Envelope)', this)">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <div class="size-card-title">Small</div>
                        <div class="size-card-desc">Envelope / Document</div>
                    </div>
                </div>
                <div class="size-card selected" onclick="pickSize('Medium', 'Medium (Shoebox)', this)">
                    <i class="fa-solid fa-box"></i>
                    <div>
                        <div class="size-card-title">Medium</div>
                        <div class="size-card-desc">Shoebox size</div>
                    </div>
                </div>
                <div class="size-card" onclick="pickSize('Large', 'Large (Moving box)', this)">
                    <i class="fa-solid fa-box-open"></i>
                    <div>
                        <div class="size-card-title">Large</div>
                        <div class="size-card-desc">Moving box size</div>
                    </div>
                </div>
                <div class="size-card" onclick="pickSize('Heavy', 'Extra Heavy', this)">
                    <i class="fa-solid fa-weight-hanging"></i>
                    <div>
                        <div class="size-card-title">Extra Heavy</div>
                        <div class="size-card-desc">Furniture / Appliances</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Selection Modal -->
    <div id="mapModal">
        <div class="map-modal-content">
            <div class="map-header">
                <h3 style="font-size:18px; font-weight:700; margin:0;">Pick Location</h3>
                <button onclick="closeMapModal()" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="mapContainer" style="position:relative;">
                <!-- Fixed Center Pin -->
                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -100%); z-index:1000; pointer-events:none; font-size:42px; color:var(--express-pink); filter:drop-shadow(0 5px 5px rgba(0,0,0,0.5));">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
            </div>
            <div class="map-footer">
                <button onclick="confirmMapLocation()" class="btn-primary" style="margin-top:0;">Confirm Location</button>
            </div>
        </div>
    </div>

    <script>
        let currentAction = 'send';
        
        function openAction(action) {
            currentAction = action;
            document.getElementById('citiesList').scrollIntoView({ behavior: 'smooth' });
        }

        function goToStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-dot').forEach(el => el.classList.remove('active'));
            
            document.getElementById('step-' + step).classList.add('active');
            for(let i=1; i<=step; i++) {
                document.getElementById('dot-' + i).classList.add('active');
            }
            
            if (step === 3) {
                document.getElementById('sumAction').innerText = currentAction;
                document.getElementById('sumCity').innerText = document.getElementById('modalCityName').innerText;
                document.getElementById('sumName').innerText = document.getElementById('exName').value || 'N/A';
                document.getElementById('sumPhone').innerText = document.getElementById('exPhone').value || 'N/A';
                
                let weight = document.getElementById('exWeight').value || '0';
                let size = document.getElementById('exSize').value;
                document.getElementById('sumPackage').innerText = weight + 'kg (' + size + ')';
            }
        }

        function selectCity(cityName, cityId) {
            document.getElementById('packageModal').classList.add('active');
            document.getElementById('modalTitle').innerText = currentAction === 'send' ? 'Send a Box' : 'Receive a Box';
            document.getElementById('modalCityName').innerText = cityName;
            
            // Reset form to step 1
            document.getElementById('exName').value = '';
            document.getElementById('exPhone').value = '';
            document.getElementById('exAddress').value = '';
            document.getElementById('exWeight').value = '';
            document.getElementById('exContent').value = '';
            document.getElementById('exFragile').checked = false;
            goToStep(1);
        }

        function closePackageModal() {
            document.getElementById('packageModal').classList.remove('active');
        }

        function submitExpress() {
            // Placeholder for backend submission
            alert("Request submitted successfully! A driver will contact you soon.");
            closePackageModal();
        }

        // --- Size Modal Logic ---
        function openSizeModal() {
            document.getElementById('sizeModal').classList.add('active');
        }
        
        function closeSizeModal() {
            document.getElementById('sizeModal').classList.remove('active');
        }

        function pickSize(val, text, element) {
            document.getElementById('exSize').value = val;
            document.getElementById('exSizeText').innerText = text;
            
            document.querySelectorAll('.size-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            
            setTimeout(closeSizeModal, 200);
        }

        // --- Map Modal Logic ---
        let map;
        let mapTargetSender = false;

        function openMapModal(isSender = false) {
            mapTargetSender = isSender;
            document.getElementById('mapModal').classList.add('active');
            if (!map) {
                // Initialize map (default to Morocco coordinates)
                map = L.map('mapContainer', { zoomControl: false }).setView([31.7917, -7.0926], 6);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }
            
            // Try to set map to the selected city if possible, but for now just wait a bit to invalidate size
            setTimeout(() => { map.invalidateSize(); }, 300);
            
            // If we have geolocation, try to center on user
            if (navigator.geolocation && map.getZoom() === 6) {
                navigator.geolocation.getCurrentPosition(pos => {
                    let lat = pos.coords.latitude;
                    let lng = pos.coords.longitude;
                    map.setView([lat, lng], 14);
                });
            }
        }

        function closeMapModal() {
            document.getElementById('mapModal').classList.remove('active');
        }

        function confirmMapLocation() {
            let pos = map.getCenter();
            
            let latId = mapTargetSender ? 'myLat' : 'exLat';
            let lngId = mapTargetSender ? 'myLng' : 'exLng';
            let addrId = mapTargetSender ? 'myAddress' : 'exAddress';

            document.getElementById(latId).value = pos.lat;
            document.getElementById(lngId).value = pos.lng;
            
            let addrField = document.getElementById(addrId);
            if(addrField.value.trim() === '') {
                addrField.value = "Pinned on map (" + pos.lat.toFixed(4) + ", " + pos.lng.toFixed(4) + ")";
            } else if (addrField.value.indexOf('Pinned on map') === -1) {
                addrField.value = addrField.value + "\n[Location Pinned on Map]";
            }
            closeMapModal();
        }
    </script>
</body>
</html>
