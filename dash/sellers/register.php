<?php
session_start();
require_once __DIR__ . '/../api_conn.php';
mysqli_set_charset($con, "utf8mb4");

// ============================================
// 1. AJAX ENDPOINT: REAL-TIME USERNAME CHECK
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_user') {
    header('Content-Type: application/json');
    $logname = mysqli_real_escape_string($con, trim($_GET['username'] ?? ''));
    if (empty($logname)) { echo json_encode(['available' => false]); exit; }
    
    $check = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$logname'");
    echo json_encode(['available' => ($check->num_rows === 0)]);
    exit;
}

// ============================================
// 2. MAIN SUBMISSION HANDLER
// ============================================
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopName = mysqli_real_escape_string($con, trim($_POST['shop_name'] ?? ''));
    $ownerName = mysqli_real_escape_string($con, trim($_POST['owner_name'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $ownerPhone = mysqli_real_escape_string($con, trim($_POST['owner_phone'] ?? ''));
    $shopPhone = mysqli_real_escape_string($con, trim($_POST['shop_phone'] ?? ''));
    $bankNum = mysqli_real_escape_string($con, trim($_POST['bank_rib'] ?? ''));
    $shopLogName = mysqli_real_escape_string($con, trim($_POST['username'] ?? ''));
    $password = mysqli_real_escape_string($con, trim($_POST['password'] ?? ''));

    // Check if ShopLogName or Phones already exist (Secondary security)
    $checkExists = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$shopLogName' OR ShopPhone = '$shopPhone'");
    if ($checkExists && $checkExists->num_rows > 0) {
        $error = "Registration failed! Username or Phone number already exists.";
    } else {
        // Handle File Uploads securely with Full Network Path Mapping
        $uploadDir = __DIR__ . '/../photo/';
        if(!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        
        $baseUrl = "https://qoon.app";
        $dirUrl = "/dash";
        $networkBaseUrl = $baseUrl . $dirUrl . "/photo/";
        
        $logoPath = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logoFilename = 'w-logo_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoFilename)) {
                $logoPath = $networkBaseUrl . $logoFilename;
            }
        }
        
        $coverPath = '';
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] == UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $coverFilename = 'w-cover_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $coverFilename)) {
                $coverPath = $networkBaseUrl . $coverFilename;
            }
        }

        // Location data
        $shopLat = (float)($_POST['shop_lat'] ?? 0); 
        $shopLongt = (float)($_POST['shop_longt'] ?? 0);

        // Insert into database with Defaults
        $shopRate = 5.0; $ratePoints = 0; $rateTime = 0; $shopRatedTime = 0;
        $shopOpen = 'Open'; $type = 'Standard'; $priority = 0; $inHome = 0; $hasStory = 0; $storyCount = 0; 
        $shopFbToken = ''; $token = ''; $loged = 0; $lastUpdate = 0; $createdAt = date('Y-m-d H:i:s'); $adminID = 0; 
        $lastPaid = 0; $cityID = 0; $status = 'ACTIVE'; $bakatID = 0; $lang = 'EN'; $paySub = 0; 
        $bankName = 'USER_AUTO_RIB';

        // Note: Using owner_phone as email fallback for architecture if null, but setting empty default
        $emailPlaceholder = strtolower($shopLogName)."@qoon.app";

        $qStr = "INSERT INTO Shops (
            ShopName, ShopLogName, ShopPassword, Email, ShopPhone, ShopLat, ShopLongt, 
            ShopRate, RatePoints, RateTime, ShopRatedTime, ShopOpen, ShopLogo, ShopCover, 
            CategoryID, Type, priority, InHome, HasStory, StoryCount, ShopFirebaseToken, 
            Token, Loged, lastShopsUpdated, CreatedAtShops, AdminID, LastPaid, CityID, 
            FullName, Status, OwnerPhone, BakatID, LANG, PaySub, BankName, BankNum
        ) VALUES (
            '$shopName', '$shopLogName', '$password', '$emailPlaceholder', '$shopPhone', $shopLat, $shopLongt,
            $shopRate, $ratePoints, $rateTime, $shopRatedTime, '$shopOpen', '$logoPath', '$coverPath',
            $categoryId, '$type', $priority, $inHome, $hasStory, $storyCount, '$shopFbToken',
            '$token', $loged, $lastUpdate, '$createdAt', $adminID, $lastPaid, $cityID,
            '$ownerName', '$status', '$ownerPhone', $bakatID, '$lang', $paySub, '$bankName', '$bankNum'
        )";

        if ($con->query($qStr)) {
            $_SESSION['SellerID'] = $con->insert_id;
            $_SESSION['SellerName'] = $shopName;
            header("Location: index.php?onboard=true");
            exit;
        } else {
            $error = "System Insert Error: " . $con->error;
        }
    }
}

// Fetch Dynamics Categories map (ignore 55 = QOON Express, 56 = Kenz Mdinty Parent)
$categories = [];
$res = $con->query("SELECT CategoryId, EnglishCategory, FrenchCategory, Photo, Type, Pro FROM Categories WHERE CategoryId NOT IN (55, 56) ORDER BY priority DESC");
while ($row = $res->fetch_assoc()) $categories[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | QOON Partners</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet Location Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --brand-color: #000000;
            --brand-hover: #333333;
            --bg-body: #FAFAFA;
            --bg-card: #FFFFFF;
            --text-main: #111827;
            --text-muted: #6B7280;
            --border-light: #E5E7EB;
            --input-bg: #F9FAFB;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 520px;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-light);
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        /* Top Progress Indicator */
        .progress-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #F3F4F6;
            z-index: 1;
            transform: translateY(-50%);
        }

        .progress-bar-fill {
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: var(--brand-color);
            z-index: 2;
            transform: translateY(-50%);
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0%;
        }

        .step-indicator {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #FFF;
            border: 2px solid #F3F4F6;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            color: #9CA3AF;
            z-index: 3;
            transition: all 0.4s ease;
        }

        .step-indicator.active {
            border-color: var(--brand-color);
            color: var(--brand-color);
            box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
        }

        .step-indicator.completed {
            background: var(--brand-color);
            border-color: var(--brand-color);
            color: #FFF;
            font-size: 0;
        }
        .step-indicator.completed::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 12px;
        }

        /* Headings */
        .step-header { margin-bottom: 32px; text-align: center; }
        .step-title { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
        .step-desc { font-size: 15px; color: var(--text-muted); }

        /* Wizard Logic */
        .wizard-step { display: none; animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .wizard-step.active { display: block; }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Inputs */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 8px; }
        
        .input-box {
            width: 100%;
            padding: 16px;
            background: var(--input-bg);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-main);
            transition: all 0.2s;
            outline: none;
        }

        .input-box:focus {
            background: #FFF;
            border-color: var(--brand-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .input-box::placeholder { color: #9CA3AF; }

        /* Profile Builder Zone */
        .profile-builder-zone {
            position: relative;
            margin-bottom: 40px;
        }

        .cover-banner {
            display: block;
            width: 100%;
            height: 150px;
            background: #F3F4F6;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            border: 1px dashed #D1D5DB;
            background-size: cover;
            background-position: center;
            transition: all 0.2s;
        }

        .cover-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.02); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9CA3AF; transition: 0.2s;
        }
        .cover-banner:hover { border-color: var(--brand-color); }
        .cover-banner:hover .cover-overlay { background: rgba(0,0,0,0.3); color: #FFF; }
        .cover-overlay i { font-size: 24px; }
        .cover-overlay span { font-size: 13px; font-weight: 600; margin-top: 6px; }

        .logo-avatar {
            position: absolute;
            bottom: -25px;
            left: 24px;
            width: 80px;
            height: 80px;
            background: #FFF;
            border-radius: 50%;
            border: 4px solid var(--bg-card);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            cursor: pointer;
            background-size: cover;
            background-position: center;
            background-image: url('https://ui-avatars.com/api/?name=S&background=F3F4F6&color=9CA3AF');
            overflow: hidden;
            z-index: 10;
        }

        .logo-overlay {
            position: absolute; inset:0; background: rgba(0,0,0,0.3); display: flex; align-items:center; justify-content:center; color: #FFF; opacity: 0; transition:0.2s;
        }
        .logo-overlay i { font-size: 18px; }
        .logo-avatar:hover .logo-overlay { opacity: 1; }

        .profile-builder-zone input[type="file"] { display: none; }

        /* Category System */
        .macro-filters {
            display: flex;
            background: var(--input-bg);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .macro-btn {
            flex: 1;
            padding: 10px 0;
            background: transparent;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .macro-btn.active {
            background: #FFF;
            color: var(--text-main);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .category-drawer {
            max-height: 280px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 4px;
        }

        /* Customize Scrollbar for drawer */
        .category-drawer::-webkit-scrollbar { width: 6px; }
        .category-drawer::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }

        .cat-item {
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cat-item img {
            width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #F3F4F6;
        }

        .cat-item span { font-size: 14px; font-weight: 600; color: var(--text-main); }

        .cat-item:hover { border-color: #9CA3AF; }
        .cat-item.selected {
            border-color: var(--brand-color);
            background: #FAFAFA;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--border-light);
        }

        .btn {
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-prev {
            background: #FFF;
            border: 1px solid var(--border-light);
            color: var(--text-main);
            width: 30%;
        }
        .btn-prev:hover { background: var(--input-bg); }

        .btn-next {
            background: var(--brand-color);
            color: #FFF;
            flex: 1;
        }
        .btn-next:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-next:disabled { background: var(--text-muted); cursor: not-allowed; transform: none; box-shadow: none; }

        /* Username Validations */
        .async-wrap { position: relative; }
        .async-status {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 12px; font-weight: 700;
        }
        .async-status.ok { color: #10B981; }
        .async-status.err { color: #EF4444; }

        /* Error Banner */
        .error-banner {
            background: #FEF2F2; color: #DC2626; padding: 16px; border-radius: 12px;
            font-size: 14px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
        }

        .login-back {
            text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted); font-weight: 500;
        }
        .login-back a { color: var(--brand-color); font-weight: 700; text-decoration: none; }

        @media (max-width: 600px) {
            .auth-container { padding: 32px 24px; border-radius: 20px; }
            .category-drawer { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        
        <!-- Top Progress Flow -->
        <div class="progress-wrapper">
            <div class="progress-line"></div>
            <div class="progress-bar-fill" id="progBar"></div>
            <div class="step-indicator active" data-step="1">1</div>
            <div class="step-indicator" data-step="2">2</div>
            <div class="step-indicator" data-step="3">3</div>
            <div class="step-indicator" data-step="4">4</div>
            <div class="step-indicator" data-step="5">5</div>
            <div class="step-indicator" data-step="6"><i class="fas fa-lock"></i></div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-banner"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="regForm" action="register.php" method="POST" enctype="multipart/form-data">

            <!-- STEP 1: IDENTITY -->
            <div class="wizard-step active" data-step="1">
                <div class="step-header">
                    <h2 class="step-title">Store Identity</h2>
                    <p class="step-desc">Provide your foundational brand visuals.</p>
                </div>

                <div class="profile-builder-zone">
                    <!-- Cover Canvas -->
                    <label class="cover-banner" id="coverPreview">
                        <div class="cover-overlay">
                            <i class="fa-solid fa-camera"></i>
                            <span id="coverText">Upload Store Cover</span>
                        </div>
                        <input type="file" name="cover" accept="image/*" onchange="previewImage(this, 'coverPreview')">
                    </label>
                    
                    <!-- Circular Logo -->
                    <label class="logo-avatar" id="logoPreview">
                        <div class="logo-overlay">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <input type="file" name="logo" accept="image/*" onchange="previewImage(this, 'logoPreview')">
                    </label>
                </div>

                <div class="form-group">
                    <label>Store Brand Name</label>
                    <input type="text" name="shop_name" class="input-box" placeholder="e.g. Qoon Electronics" required>
                </div>
                <div class="form-group">
                    <label>Owner Full Name</label>
                    <input type="text" name="owner_name" class="input-box" placeholder="John Doe" required>
                </div>
            </div>

            <!-- STEP 2: CATEGORY -->
            <div class="wizard-step" data-step="2">
                <div class="step-header">
                    <h2 class="step-title">Select Sector</h2>
                    <p class="step-desc">Choose the category algorithm mapping.</p>
                </div>
                
                <input type="hidden" name="category_id" id="categoryId" required>
                
                <div class="macro-filters">
                    <button type="button" class="macro-btn active" onclick="filterCategories('QOON', this)">QOON</button>
                    <button type="button" class="macro-btn" onclick="filterCategories('Kenz', this)">Kenz madinty</button>
                    <button type="button" class="macro-btn" onclick="filterCategories('Pro', this)">QOON Pro</button>
                </div>

                <div class="category-drawer" id="categoryDrawer">
                    <?php foreach($categories as $c): ?>
                    <div class="cat-item" data-type="<?= htmlspecialchars($c['Type']) ?>" data-pro="<?= htmlspecialchars($c['Pro']) ?>" onclick="selectCat(this, <?= $c['CategoryId'] ?>)">
                        <img src="<?= htmlspecialchars($c['Photo']) ?>" onerror="this.src='https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=100&auto=format&fit=crop'">
                        <span><?= htmlspecialchars($c['EnglishCategory'] ?: $c['FrenchCategory']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: LOCATION -->
            <div class="wizard-step" data-step="3">
                <div class="step-header">
                    <h2 class="step-title">Store Location</h2>
                    <p class="step-desc">Pinpoint your exact geographical location.</p>
                </div>
                
                <input type="hidden" name="shop_lat" id="shopLat" required>
                <input type="hidden" name="shop_longt" id="shopLong" required>
                
                <div id="mapHost" style="width: 100%; height: 260px; border-radius: 16px; border: 1px solid var(--border-light); z-index: 1;"></div>
                <p style="text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 12px;"><i class="fa-solid fa-crosshairs"></i> Drag the map to physically verify location</p>
            </div>

            <!-- STEP 4: CONTACT -->
            <div class="wizard-step" data-step="4">
                <div class="step-header">
                    <h2 class="step-title">Communications</h2>
                    <p class="step-desc">How can customers and QOON reach you?</p>
                </div>
                
                <div class="form-group">
                    <label>Owner Private Phone</label>
                    <input type="tel" name="owner_phone" class="input-box" placeholder="+212 ..." required>
                </div>
                <div class="form-group">
                    <label>Public Store Phone</label>
                    <input type="tel" name="shop_phone" class="input-box" placeholder="+212 ..." required>
                </div>
            </div>

            <!-- STEP 5: FINANCE -->
            <div class="wizard-step" data-step="5">
                <div class="step-header">
                    <h2 class="step-title">Financial Gateway</h2>
                    <p class="step-desc">Secure your payout routing digits.</p>
                </div>
                
                <div class="form-group">
                    <label>Bank RIB (24 Digits)</label>
                    <div style="position:relative;">
                        <input type="text" name="bank_rib" id="bank_rib" class="input-box" placeholder="000123456789012345678900" maxlength="24" oninput="parseRIB(this)" spellcheck="false" required>
                        <div id="ribCounter" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); font-size:12px; font-weight:700; color:#9CA3AF;">0/24</div>
                    </div>
                </div>
            </div>

            <!-- STEP 6: CREDS -->
            <div class="wizard-step" data-step="6">
                <div class="step-header">
                    <h2 class="step-title">Security Gateway</h2>
                    <p class="step-desc">Establish your unique network credentials.</p>
                </div>
                
                <div class="form-group">
                    <label>Username Handle</label>
                    <div class="async-wrap">
                        <input type="text" name="username" id="username" class="input-box" placeholder="qoonstore" onkeyup="checkUsername(this.value)" autocomplete="off" required>
                        <div id="userStatus" class="async-status"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Platform Password</label>
                    <input type="password" name="password" class="input-box" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Global Footer Controls -->
            <div class="btn-group">
                <button type="button" class="btn btn-prev" id="btnBack" onclick="changeStep(-1)" style="display:none;">Back</button>
                <button type="button" class="btn btn-next" id="btnNext" onclick="changeStep(1)">Continue</button>
                <button type="submit" class="btn btn-next" id="btnSubmit" style="display:none;">Launch Store</button>
            </div>

            <div class="login-back">
                Already registered? <a href="login.php">Sign In</a>
            </div>
        </form>

    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 6;
        let isUsernameValid = false;
        
        // Map States
        let map;
        let marker;
        let mapInit = false;

        function updateUI() {
            // Update Active Display
            document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');

            // Update Progress Tracking
            const dots = document.querySelectorAll('.step-indicator');
            dots.forEach((dot, index) => {
                let stepNumber = index + 1;
                dot.classList.remove('active', 'completed');
                if(stepNumber < currentStep) dot.classList.add('completed');
                if(stepNumber === currentStep) dot.classList.add('active');
            });

            // Width logic for progress bar line
            let fillWidth = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progBar').style.width = fillWidth + '%';

            // Button Logics
            document.getElementById('btnBack').style.display = currentStep > 1 ? 'block' : 'none';
            if (currentStep === totalSteps) {
                document.getElementById('btnNext').style.display = 'none';
                document.getElementById('btnSubmit').style.display = 'block';
                checkFinalState();
            } else {
                document.getElementById('btnNext').style.display = 'block';
                document.getElementById('btnSubmit').style.display = 'none';
            }

            // Map Initialization logic when arriving at Step 3
            if (currentStep === 3) {
                if (!mapInit) {
                    map = L.map('mapHost').setView([33.5908, -7.6186], 12); // Default to Casablanca
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; CartoDB'
                    }).addTo(map);
                    
                    marker = L.marker(map.getCenter()).addTo(map);
                    
                    document.getElementById('shopLat').value = map.getCenter().lat;
                    document.getElementById('shopLong').value = map.getCenter().lng;
                    
                    map.on('move', function(e) {
                        marker.setLatLng(map.getCenter());
                    });
                    
                    map.on('moveend', function(e) {
                        const pos = map.getCenter();
                        document.getElementById('shopLat').value = pos.lat;
                        document.getElementById('shopLong').value = pos.lng;
                    });
                    
                    mapInit = true;
                }
                setTimeout(() => { map.invalidateSize(); }, 200);
            }
        }

        function changeStep(dir) {
            if (dir === 1) {
                // Strict validation gates
                const currentWrap = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
                const inputs = currentWrap.querySelectorAll('input[required]');
                let valid = true;
                
                inputs.forEach(inp => {
                    if(!inp.value.trim()) {
                        inp.style.borderColor = '#EF4444';
                        valid = false;
                    } else {
                        inp.style.borderColor = '';
                    }
                });

                if(currentStep === 2 && !document.getElementById('categoryId').value) {
                    alert('You must select a specialized mapping category.');
                    return;
                }

                if(currentStep === 5) {
                    const ribLen = document.getElementById('bank_rib').value.length;
                    if(ribLen !== 24) {
                        alert('Bank RIB requires exactly 24 digits.');
                        return;
                    }
                }

                if(!valid) return;
            }

            currentStep += dir;
            updateUI();
        }

        function filterCategories(macro, btnObj = null) {
            // Update button states
            const buttons = document.querySelectorAll('.macro-btn');
            buttons.forEach(b => b.classList.remove('active'));
            if(btnObj) {
                btnObj.classList.add('active');
            } else {
                // Default to QOON button if triggered programmatically
                if(macro === 'QOON') buttons[0].classList.add('active');
                if(macro === 'Kenz') buttons[1].classList.add('active');
                if(macro === 'Pro') buttons[2].classList.add('active');
            }

            // Filter items
            const items = document.querySelectorAll('.cat-item');
            items.forEach(item => {
                const type = item.getAttribute('data-type');
                const pro = item.getAttribute('data-pro');
                
                let show = false;
                if (macro === 'QOON' && type === 'Top' && pro === 'Normal') show = true;
                if (macro === 'Kenz' && type === 'Small') show = true;
                if (macro === 'Pro' && pro === 'Pro') show = true;
                
                item.style.display = show ? 'flex' : 'none';
            });

            // Reset selection to prevent bypassing category choice physically
            document.getElementById('categoryId').value = '';
            items.forEach(c => c.classList.remove('selected'));
        }

        // Initialize default filter
        window.addEventListener('DOMContentLoaded', () => { filterCategories('QOON'); });

        function selectCat(el, id) {
            document.querySelectorAll('.cat-item').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('categoryId').value = id;
        }

        function parseRIB(inp) {
            inp.value = inp.value.replace(/\\D/g, '').substring(0, 24);
            document.getElementById('ribCounter').innerText = inp.value.length + "/24";
            if(inp.value.length === 24) {
                inp.style.borderColor = '#10B981';
            } else {
                inp.style.borderColor = '';
            }
        }

        let debounceTimer;
        function checkUsername(v) {
            const status = document.getElementById('userStatus');
            clearTimeout(debounceTimer);
            if (v.trim().length === 0) { status.innerHTML = ''; isUsernameValid = false; checkFinalState(); return; }
            
            status.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            status.className = 'async-status';

            debounceTimer = setTimeout(() => {
                fetch('register.php?ajax=check_user&username=' + encodeURIComponent(v))
                .then(r => r.json())
                .then(data => {
                    if(data.available) {
                        status.innerHTML = '<i class="fas fa-check"></i> OK';
                        status.className = 'async-status ok';
                        isUsernameValid = true;
                    } else {
                        status.innerHTML = 'Taken';
                        status.className = 'async-status err';
                        isUsernameValid = false;
                    }
                    checkFinalState();
                });
            }, 500);
        }

        function checkFinalState() {
            if (currentStep === 6) {
                document.getElementById('btnSubmit').disabled = !isUsernameValid;
            }
        }

        function previewImage(input, targetId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(targetId).style.backgroundImage = `url('${e.target.result}')`;
                    document.getElementById(targetId).style.border = 'none';
                    if (targetId === 'coverPreview') {
                        document.querySelector('#coverPreview .cover-overlay span').innerText = 'Change Cover';
                        document.querySelector('#coverPreview .cover-overlay i').style.display = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
