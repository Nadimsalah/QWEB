<?php
define('FROM_UI', true);
require_once 'conn.php';

$catId = intval($_GET['cat'] ?? 38);

function fullUrlS($path, $domain) {
    if (!$path || $path === '0' || $path === 'NONE') return '';
    if (strpos($path, 'http') !== false) return preg_replace('#(?<!:)//+#', '/', $path);
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}

$userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float)$_COOKIE['qoon_lat'] : null;
$userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float)$_COOKIE['qoon_lon'] : null;
$locationRequired = (!$userLat || !$userLon);

function haversineKmS($lat1, $lon1, $lat2, $lon2) {
    if (!$lat2 || !$lon2) return null;
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1); $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// Category name â€” only fetch columns that exist
$catName  = '';
if ($con) {
    $r = $con->query("SELECT EnglishCategory FROM Categories WHERE CategoryId=$catId LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) $catName = $row['EnglishCategory'];
}
if (!$catName) $catName = 'Category';

// All shops in this category
$shops = [];
if ($con) {
    $r = $con->query("SELECT ShopID, ShopName, ShopLogo, ShopCover, ShopRate, ShopOpen, ShopLat, ShopLongt
                      FROM Shops WHERE CategoryID=$catId AND Status='ACTIVE'
                      ORDER BY priority DESC, ShopRate DESC");
    if ($r) while ($row = $r->fetch_assoc()) $shops[] = $row;
}

if (isset($con) && $con) mysqli_close($con);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($catName) ?> Â· Shops Â· QOON</title>
    <meta name="description" content="All shops in <?= htmlspecialchars($catName) ?> on QOON.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif;
            background:#000; color:#fff;
            min-height:100vh;
            overflow-x:hidden;
        }

        /* --- Location Request Overlay --- */
        .location-overlay {
            position: relative;
            width: 100%;
            padding: 80px 20px;
            background: rgba(20, 20, 30, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 40px auto;
            max-width: 800px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        .location-overlay::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at 30% 30%, #4a25e1 0%, transparent 40%),
                        radial-gradient(circle at 70% 70%, #2cb5e8 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, #9b2df1 0%, transparent 50%);
            opacity: 0.2;
            filter: blur(80px);
            animation: rotateBG 20s infinite linear;
        }

        @keyframes rotateBG {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .location-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 440px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 48px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            transform: translateY(0);
            animation: slideUpL 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideUpL {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .location-icon-pulsar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #4a25e1, #2cb5e8);
            border-radius: 35%;
            margin: 0 auto 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #fff;
            position: relative;
            box-shadow: 0 20px 40px rgba(44, 181, 232, 0.3);
        }

        .location-icon-pulsar::after {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: inherit;
            border: 2px solid #2cb5e8;
            opacity: 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        .location-content h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
            color: #fff;
        }

        .location-content p {
            font-size: 16px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .location-btn {
            width: 100%;
            background: #fff;
            color: #000;
            border: none;
            padding: 20px 32px;
            border-radius: 24px;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .location-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(255,255,255,0.1);
        }

        .location-status {
            margin-top: 20px;
            font-size: 14px;
            color: #2cb5e8;
            font-weight: 500;
        }

        body.location-locked {
            /* allow scrolling */
        }

        :root {
            --accent: #ffffff;
            --accent2: #9b2df1;
            --muted: rgba(255,255,255,0.5);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(255,255,255,0.08);
        }

        /* â”€â”€ Aurora background â”€â”€ */
        .aurora {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 20% 0%, rgba(255,255,255,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 80% 10%, rgba(155,45,241,0.10) 0%, transparent 70%);
        }

        /* â”€â”€ Header â”€â”€ */
        .header {
            position: sticky; top: 0; z-index: 100;
            padding: 0 20px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .header-inner {
            max-width: 640px; margin: 0 auto;
            display: flex; align-items: center; gap: 14px;
            padding: 14px 0 0;
        }
        .back-btn {
            width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: #fff; display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 15px;
            transition: background .2s, transform .2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.16); transform: scale(1.06); }

        .header-text { flex: 1; min-width: 0; }
        .header-label {
            font-size: 11px; font-weight: 600; letter-spacing: .08em;
            text-transform: uppercase; color: var(--accent); margin-bottom: 2px;
        }
        .header-title { font-size: 20px; font-weight: 800; line-height: 1.1; }
        .header-sub { font-size: 13px; color: var(--muted); margin-top: 3px; }

        /* Search */
        .search-wrap {
            position: relative; margin: 14px 0;
        }
        .search-wrap i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--muted); font-size: 14px; pointer-events: none;
        }
        #search-input {
            width: 100%; padding: 13px 16px 13px 42px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; color: #fff; font-size: 14px;
            font-family: inherit; outline: none;
            transition: border-color .2s, background .2s;
        }
        #search-input::placeholder { color: var(--muted); }
        #search-input:focus {
            border-color: rgba(44,181,232,.45);
            background: rgba(255,255,255,0.09);
        }

        /* â”€â”€ Feed â”€â”€ */
        .feed {
            position: relative; z-index: 1;
            max-width: 640px; margin: 0 auto;
            padding: 24px 20px 80px;
            display: flex; flex-direction: column; gap: 20px;
        }

        /* â”€â”€ Shop card â”€â”€ */
        .shop-card {
            position: relative;
            display: block; text-decoration: none; color: inherit;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            overflow: visible;
            transition: transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s, border-color .3s;
        }
        .shop-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.18);
            box-shadow: 0 24px 60px rgba(0,0,0,.7);
        }

        /* Cover */
        .cover-wrap {
            height: 220px; border-radius: 28px 28px 0 0;
            overflow: hidden; background: #111; position: relative;
        }
        .cover-img {
            width: 100%; height: 100%; object-fit: cover; display: block;
            transition: transform .5s ease;
        }
        .shop-card:hover .cover-img { transform: scale(1.05); }
        .cover-placeholder {
            width: 100%; height: 100%;
            background:
                radial-gradient(at 0% 0%, rgba(44,181,232,.22) 0, transparent 60%),
                radial-gradient(at 100% 100%, rgba(155,45,241,.18) 0, transparent 60%),
                #0e0e0e;
        }
        .cover-shade {
            position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,.05) 0%, rgba(0,0,0,.65) 100%);
        }

        /* Pills */
        .pill {
            position: absolute; display: flex; align-items: center; gap: 5px;
            border-radius: 99px; padding: 5px 12px; font-size: 11px; font-weight: 700;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        }
        .pill-status { top: 14px; left: 14px; }
        .pill-open   { background: rgba(34,197,94,.22); border: 1px solid rgba(34,197,94,.45); color: #4ade80; }
        .pill-closed { background: rgba(239,68,68,.22);  border: 1px solid rgba(239,68,68,.45);  color: #f87171; }
        .pill-dot    { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .pill-dist   { top: 14px; right: 14px; background: rgba(0,0,0,.5); border: 1px solid rgba(255,255,255,.14); color: #fff; }
        .pill-dist i { color: var(--accent); font-size: 10px; }

        /* Floating logo */
        .logo-float {
            position: absolute;
            top: calc(220px - 32px);   /* cover height - half logo */
            left: 50%; transform: translateX(-50%);
            width: 64px; height: 64px; border-radius: 18px;
            border: 3px solid #000; background: #1a1a1a;
            overflow: hidden; z-index: 5;
            box-shadow: 0 8px 28px rgba(0,0,0,.75);
        }
        .logo-float img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* Body */
        .card-body {
            padding: 44px 24px 22px;
            background: rgba(255,255,255,0.025);
            border-radius: 0 0 28px 28px;
            text-align: center;
        }
        .shop-name { font-size: 19px; font-weight: 800; margin-bottom: 8px; }
        .shop-meta {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; font-size: 13px; color: var(--muted); flex-wrap: wrap;
        }
        .meta-star { color: #fbbf24; display: flex; align-items: center; gap: 4px; }
        .meta-sep  { opacity: .28; }
        .meta-cat  { display: flex; align-items: center; gap: 5px; }
        .meta-cat i { color: var(--accent); font-size: 11px; }

        /* â”€â”€ Empty â”€â”€ */
        .empty {
            text-align: center; padding: 80px 20px; color: var(--muted);
        }
        .empty i { font-size: 52px; display: block; margin-bottom: 16px; opacity: .35; }
        .empty p { font-size: 15px; }

        /* â”€â”€ No-result hide â”€â”€ */
        .shop-card[hidden] { display: none !important; }

        @media (max-width: 480px) {
            .cover-wrap { height: 180px; }
            .logo-float { top: calc(180px - 32px); }
            .shop-name  { font-size: 17px; }
            .feed { padding: 20px 12px 80px; }
        }
    </style>
</head>
<body>

<div class="aurora"></div>

<!-- Header -->
<div class="header">
    <div class="header-inner">
        <a href="javascript:void(0)"
           onclick="history.length>1?history.back():location.href='category.php?cat=<?= $catId ?>'"
           class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="header-text">
            <div class="header-title"><?= htmlspecialchars($catName) ?></div>
        </div>
    </div>
    <div style="max-width:640px;margin:0 auto;">
        <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="search-input" type="text"
                   placeholder="Search in <?= htmlspecialchars($catName) ?>..."
                   autocomplete="off">
        </div>
    </div>
</div>

<!-- Feed -->
<div class="feed" id="feed">
<?php if ($locationRequired): ?>
    <div id="locationOverlay" class="location-overlay">
        <div class="location-content">
            <div class="location-icon-pulsar">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <h1>Know your location</h1>
            <p>QOON needs your location to show the best stores near you.</p>
            <button id="getLocationBtn" class="location-btn" onclick="requestUserLocation()">
                <span>Allow Access</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <div class="location-status" id="locationStatus"></div>
        </div>
    </div>
<?php else: ?>

    <?php if (empty($shops)): ?>
    <div class="empty">
        <i class="fa-solid fa-store"></i>
        <p>No shops found in <?= htmlspecialchars($catName) ?></p>
    </div>
    <?php else: ?>

    <?php foreach ($shops as $s):
        $logo  = fullUrlS($s['ShopLogo'],  $DomainNamee);
        $cover = fullUrlS($s['ShopCover'], $DomainNamee);
        $open  = ($s['ShopOpen'] ?? '') === 'Open';
        $rate  = floatval($s['ShopRate']  ?? 0);
        $fb    = 'https://ui-avatars.com/api/?name=' . urlencode($s['ShopName']) . '&background=2cb5e8&color=fff&size=128';

        $distLabel = '';
        if ($userLat !== null && $userLon !== null) {
            $km = haversineKmS($userLat, $userLon, floatval($s['ShopLat'] ?? 0), floatval($s['ShopLongt'] ?? 0));
            if ($km !== null && $km > 0)
                $distLabel = $km < 1 ? round($km * 1000) . ' m' : number_format($km, 1) . ' km';
        }
    ?>
    <a class="shop-card"
       href="shop.php?id=<?= $s['ShopID'] ?>&boutique=1"
       data-name="<?= htmlspecialchars(mb_strtolower($s['ShopName'])) ?>">

        <!-- Cover -->
        <div class="cover-wrap">
            <?php if ($cover): ?>
                <img class="cover-img" src="<?= htmlspecialchars($cover) ?>" loading="lazy"
                     onerror="this.parentNode.querySelector('.cover-placeholder')?.remove(); this.remove();" alt="">
            <?php endif; ?>
            <div class="cover-placeholder" <?= $cover ? 'style="display:none"' : '' ?>></div>
            <div class="cover-shade"></div>

            <!-- Status -->
            <div class="pill pill-status <?= $open ? 'pill-open' : 'pill-closed' ?>">
                <span class="pill-dot"></span>
                <?= $open ? 'Open' : 'Closed' ?>
            </div>

            <!-- Distance -->
            <?php if ($distLabel): ?>
            <div class="pill pill-dist">
                <i class="fa-solid fa-location-dot"></i>
                <?= $distLabel ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Floating logo -->
        <div class="logo-float">
            <img src="<?= htmlspecialchars($logo ?: $fb) ?>"
                 onerror="this.src='<?= $fb ?>'"
                 alt="<?= htmlspecialchars($s['ShopName']) ?>">
        </div>

        <!-- Info -->
        <div class="card-body">
            <div class="shop-name"><?= htmlspecialchars($s['ShopName']) ?></div>
            <div class="shop-meta">
                <?php if ($rate > 0): ?>
                <span class="meta-star">
                    <i class="fa-solid fa-star"></i>
                    <?= number_format($rate, 1) ?>
                </span>
                <span class="meta-sep">Â·</span>
                <?php endif; ?>
                <span class="meta-cat">
                    <i class="fa-solid fa-utensils"></i>
                    <?= htmlspecialchars($catName) ?>
                </span>
                <?php if ($distLabel): ?>
                <span class="meta-sep">Â·</span>
                <span style="display:flex;align-items:center;gap:4px;color:var(--accent);">
                    <i class="fa-solid fa-location-dot" style="font-size:10px;"></i>
                    <?= $distLabel ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endforeach; ?>

    <div class="empty" id="no-results" style="display:none;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <p>No shops match your search</p>
    </div>

    <?php endif; ?>
<?php endif; ?>
</div>

<script>
const input     = document.getElementById('search-input');
const cards     = document.querySelectorAll('.shop-card');
const noResults = document.getElementById('no-results');

input?.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let vis = 0;
    cards.forEach(c => {
        const show = !q || (c.dataset.name || '').includes(q);
        c.hidden = !show;
        if (show) vis++;
    });
    if (noResults) noResults.style.display = (vis === 0) ? 'block' : 'none';
});
</script>
    <script>
        // --- LOCATION REQUEST LOGIC ---
        async function requestUserLocation() {
            const btn = document.getElementById('getLocationBtn');
            const status = document.getElementById('locationStatus');
            const originalBtnText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Requesting...';
            status.innerText = 'Checking browser permissions...';

            if (!navigator.geolocation) {
                status.innerText = 'Geolocation is not supported by your browser.';
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    status.innerText = 'Location found! Synchronizing...';
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    // Set cookies for 30 days
                    const d = new Date();
                    d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
                    const expires = "expires=" + d.toUTCString();
                    document.cookie = `qoon_lat=${lat}; ${expires}; path=/`;
                    document.cookie = `qoon_lon=${lon}; ${expires}; path=/`;

                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                },
                (error) => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                    console.error("Location error:", error);
                    
                    if (error.code === error.PERMISSION_DENIED) {
                        status.innerHTML = "<span style='color:#ff3b30;'><i class='fa-solid fa-triangle-exclamation'></i> You denied the request.</span><br>Please click the <b>Lock icon 🔒</b> in your address bar, switch Location to <b>Allow</b>, and then click below.";
                        btn.innerHTML = '<span>Reload Page</span> <i class="fa-solid fa-rotate-right"></i>';
                        btn.onclick = () => window.location.reload();
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        status.innerText = "Location information is unavailable. Check your device GPS.";
                    } else if (error.code === error.TIMEOUT) {
                        status.innerText = "The request timed out. Please try again.";
                    } else {
                        status.innerText = "Error: " + error.message;
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    </script>
</body>
</html>

