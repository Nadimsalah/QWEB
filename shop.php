<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$shopId = intval($_GET['id'] ?? $_GET['shop'] ?? 0);
$domain = $DomainNamee ?? 'https://qoon.app/dash/';

// Always use production URL for images — /photo/ dir only exists on qoon.app server
$imageDomain = 'https://qoon.app/userDriver/UserDriverApi/';

function fullUrl($path, $domain)
{
    global $imageDomain;
    if (!$path || $path === '0' || $path === 'NONE')
        return '';
    if (strpos($path, 'http') !== false) {
        return preg_replace('#(?<!:)//+#', '/', $path);
    }
    // Use production image server for all relative paths
    return rtrim($imageDomain, '/') . '/photo/' . ltrim($path, '/');
}

// User location
$userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float) $_COOKIE['qoon_lat'] : null;
$userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float) $_COOKIE['qoon_lon'] : null;
$locationRequired = (!$userLat || !$userLon);

function haversineKm($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat2 || !$lon2)
        return null;
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ── Shop Data ──
$shop = [];
if ($con && $shopId > 0) {
    $r = $con->query("SELECT * FROM Shops WHERE ShopID = $shopId");
    if ($r && $r->num_rows)
        $shop = $r->fetch_assoc();
}

if (empty($shop)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shop Not Found | QOON</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root {
                --bg-color: #050505;
                --text-main: #ffffff;
                --text-muted: rgba(255, 255, 255, 0.6);
                --accent-glow: #2cb5e8;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Inter', sans-serif;
            }

            body {
                background-color: var(--bg-color);
                color: var(--text-main);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
            }

            .aurora-container {
                position: absolute;
                inset: 0;
                z-index: 0;
                overflow: hidden;
            }

            .aurora-blob {
                position: absolute;
                width: 80vw;
                height: 60vh;
                background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
                filter: blur(100px);
                opacity: 0.2;
                animation: drift 20s infinite alternate linear;
            }

            @keyframes drift {
                from {
                    transform: translate(-10%, -10%) scale(1);
                }

                to {
                    transform: translate(10%, 10%) scale(1.1);
                }
            }

            .container {
                position: relative;
                z-index: 10;
                text-align: center;
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(32px);
                -webkit-backdrop-filter: blur(32px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 40px;
                padding: 60px 40px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
            }

            .logo {
                height: 40px;
                margin-bottom: 30px;
            }

            h2 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 12px;
            }

            p {
                color: var(--text-muted);
                line-height: 1.6;
                margin-bottom: 32px;
            }

            .home-btn {
                background: #fff;
                color: #000;
                padding: 14px 32px;
                border-radius: 99px;
                font-weight: 700;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s;
            }

            .home-btn:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
            }
        </style>
    </head>

    <body>
        <div class="aurora-container">
            <div class="aurora-blob"></div>
        </div>
        <div class="container">
            <img src="logo_qoon_white.png" alt="QOON" class="logo">
            <i class="fa-solid fa-store-slash"
                style="font-size: 64px; color: var(--accent-glow); margin-bottom: 24px; display: block;"></i>
            <h2>Store Not Found</h2>
            <p>We couldn't find the store you were looking for. It might have moved or the link is expired.</p>
            <a href="index.php" class="home-btn"><i class="fa-solid fa-house"></i> Back to Home</a>
        </div>
    </body>

    </html>
    <?php
    die();
}

$shopName = $shop['ShopName'] ?? 'QOON Shop';
$shopLogo = fullUrl($shop['ShopLogo'] ?? '', $domain) ?: 'https://ui-avatars.com/api/?name=' . urlencode($shopName);
$shopCover = fullUrl($shop['ShopCover'] ?? '', $domain);
$shopRate = floatval($shop['ShopRate'] ?? 0);
$shopReviewers = intval($shop['UsersRate'] ?? 100);

$distKm = '';
if ($userLat !== null && $userLon !== null) {
    $km = haversineKm($userLat, $userLon, floatval($shop['ShopLat'] ?? 0), floatval($shop['ShopLongt'] ?? 0));
    if ($km !== null && $km > 0) {
        $distKm = number_format($km, 2) . ' KM';
    }
}

// ── Posts Data ──
$posts = [];
$r = $con->query("SELECT Posts.*, Foods.FoodName, Foods.FoodPrice, Foods.FoodOfferPrice,
                  Foods.FoodPhoto, Foods.FoodDesc
                  FROM Posts
                  LEFT JOIN Foods ON Foods.FoodID = Posts.ProductID
                  WHERE Posts.ShopID = $shopId AND Posts.PostStatus='ACTIVE'
                    AND (Posts.Video='' OR Posts.Video='0' OR Posts.Video IS NULL)
                  ORDER BY Posts.CreatedAtPosts DESC LIMIT 20");
if ($r)
    while ($row = $r->fetch_assoc())
        $posts[] = $row;


// ── Stories & Reels ──
$reels = [];
if ($con && $shopId > 0) {
    $r = $con->query("
        SELECT Posts.PostId AS id, Posts.Video AS media, 'post' AS sourceType
        FROM Posts 
        WHERE Posts.ShopID = $shopId AND Posts.PostStatus='ACTIVE'
          AND Posts.Video != '' AND Posts.Video != '0' AND Posts.Video IS NOT NULL
        UNION ALL
        SELECT ShopStory.StotyID AS id, ShopStory.StoryPhoto AS media, 'story' AS sourceType
        FROM ShopStory 
        WHERE ShopStory.ShopID = $shopId AND ShopStory.StoryStatus='ACTIVE'
          AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
        LIMIT 10
    ");
    if ($r)
        while ($row = $r->fetch_assoc())
            $reels[] = $row;
}

// ── Boutique (Categories & Foods) ──
$boutiqueCategories = [];
$boutiqueFoodsByCat = [];

$r = $con->query("SELECT * FROM ShopsCategory WHERE ShopID = $shopId");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $bCatId = $row['CategoryShopID'] ?? $row['id'] ?? 0;
        $bCatName = $row['CategoryName'] ?? $row['categoryName'] ?? 'Category';
        $boutiqueCategories[$bCatId] = $bCatName;
        $boutiqueFoodsByCat[$bCatId] = [];
    }
}

$r = $con->query("SELECT * FROM Foods WHERE FoodCatID IN (SELECT CategoryShopID FROM ShopsCategory WHERE ShopID=$shopId)");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $bCatId = $row['FoodCatID'];
        if (isset($boutiqueFoodsByCat[$bCatId])) {
            $boutiqueFoodsByCat[$bCatId][] = $row;
        }
    }
}

// Remove empty categories
foreach ($boutiqueFoodsByCat as $bCatId => $arr) {
    if (empty($arr)) {
        unset($boutiqueCategories[$bCatId]);
        unset($boutiqueFoodsByCat[$bCatId]);
    }
}
// DB connection closed after HTML output (header.php needs it)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($shopName) ?> - Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Prevent theme flashing
        if (localStorage.getItem('qoon_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    <style>
        :root {
            --bg: #000000;
            --surface: #0a0a0c;
            --primary: #f50057;
            /* Pink accent from screenshot */
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            overflow-x: hidden;
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
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
        }

        .location-overlay::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at 30% 30%, #f50057 0%, transparent 40%),
                radial-gradient(circle at 70% 70%, #2cb5e8 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, #9b2df1 0%, transparent 50%);
            opacity: 0.2;
            filter: blur(80px);
            animation: rotateBG 20s infinite linear;
        }

        @keyframes rotateBG {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
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
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8);
            transform: translateY(0);
            animation: slideUpL 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideUpL {
            from {
                transform: translateY(40px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .location-icon-pulsar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), #2cb5e8);
            border-radius: 35%;
            margin: 0 auto 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #fff;
            position: relative;
            box-shadow: 0 20px 40px rgba(245, 0, 87, 0.3);
        }

        .location-icon-pulsar::after {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: inherit;
            border: 2px solid var(--primary);
            opacity: 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.5;
            }

            100% {
                transform: scale(1.4);
                opacity: 0;
            }
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
            color: var(--text-muted);
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
            box-shadow: 0 20px 40px rgba(255, 255, 255, 0.1);
        }

        .location-status {
            margin-top: 20px;
            font-size: 14px;
            color: var(--primary);
            font-weight: 500;
        }

        body.location-locked {
            /* allow scrolling */
        }

        .cover-section {
            position: relative;
            width: 100%;
            height: clamp(200px, 28vh, 320px);
            background: #111;
        }

        .cover-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, transparent 30%, transparent 60%, rgba(0, 0, 0, 0.7) 100%);
            pointer-events: none;
        }

        .safe-top-bar {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
        }

        .round-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-size: 18px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            outline: none;
            transition: 0.3s;
            cursor: pointer;
        }

        .round-btn:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.6);
        }

        .shop-profile-card {
            position: relative;
            background: var(--bg);
            border-radius: 36px 36px 0 0;
            margin: -50px auto 0;
            padding: 30px 24px;
            z-index: 20;
            min-height: 60vh;
            max-width: 1000px;
        }

        .shop-header-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .shop-avatar-wrap {
            position: relative;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #000;
            box-shadow: 0 0 0 3px var(--primary);
            /* Pink ring */
        }

        .shop-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #000;
        }

        .shop-status-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 14px;
            height: 14px;
            background: #2ecc71;
            border: 2px solid #000;
            border-radius: 50%;
        }

        .shop-info-col {
            flex: 1;
        }

        .shop-name-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .shop-name {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .bluetick {
            color: #3b82f6;
            font-size: 14px;
        }

        .shop-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 4px;
        }

        .shop-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }

        .shop-rating i {
            color: #facc15;
            font-size: 12px;
        }

        .shop-rating span {
            color: var(--text-muted);
            font-weight: 500;
        }

        .shop-dist {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Segmented Tabs */
        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            outline: none;
            padding: 12px 24px;
            border-radius: 99px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn i {
            font-size: 16px;
        }

        .tab-btn.active {
            background: rgba(130, 140, 255, 0.2);
            /* Soft purple tint */
            color: #fff;
        }

        /* Content Sections */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── POSTS TAB ── */
        .shop-stories-container {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding-bottom: 20px;
            margin: 0 -24px 24px -24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            /* Break out padding sideways (edge to edge) */
            padding-left: 24px;
            padding-right: 24px;
            /* Keep initial alignment equal to posts */
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
        }

        @media (min-width: 700px) {
            .shop-stories-container {
                margin: 0 auto 24px auto;
                max-width: 650px;
                padding-left: 0;
                padding-right: 0;
            }
        }

        .shop-stories-container::-webkit-scrollbar {
            display: none;
        }

        .story-item {
            min-width: 130px;
            width: 130px;
            height: 200px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            background: #1a1a1a;
            flex-shrink: 0;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 0 0 2px var(--primary);
            /* Glowing ring effect */
            margin: 2px 2px 2px 0;
            transition: transform 0.3s;
            scroll-snap-align: start;
        }

        .story-item:hover {
            transform: scale(1.05);
        }

        .story-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .story-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: 10px;
            padding: 3px 6px;
            border-radius: 6px;
            font-weight: bold;
            backdrop-filter: blur(5px);
        }

        .story-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.1) 40%, transparent);
        }

        /* --- SHIMMER LOADER --- */
        @keyframes shimmerAnim {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .shimmer-box {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.03) 25%, rgba(255, 255, 255, 0.08) 50%, rgba(255, 255, 255, 0.03) 75%);
            background-size: 200% 100%;
            animation: shimmerAnim 1.5s infinite linear;
            border-radius: 12px;
        }

        .post-card {
            margin: 0 auto 40px;
            max-width: 650px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 24px 0;
            transition: transform 0.3s;
        }

        .post-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }

        .p-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid var(--border);
        }

        .p-info {
            flex: 1;
        }

        .p-name-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 16px;
            font-weight: 600;
        }

        .p-time {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .p-text {
            font-size: 15px;
            color: #e0e0e0;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .p-img {
            width: 100%;
            border-radius: 16px;
            object-fit: cover;
            max-height: 500px;
            background: #111;
            display: block;
        }

        /* Inline Product */
        .feed-inline-product {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 16px;
            border-radius: 16px;
            margin-top: 12px;
            text-decoration: none;
            color: #fff;
        }

        .fip-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .fip-icon-holder {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(34, 197, 94, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2ecc71;
            font-size: 18px;
        }

        .fip-name {
            font-size: 14px;
            font-weight: 600;
        }

        .fip-price {
            font-size: 13px;
            font-weight: 700;
            color: #2ecc71;
            margin-top: 2px;
        }

        .p-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 16px;
            padding: 0 4px;
        }

        .p-action-btn {
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .p-action-btn:hover {
            color: var(--primary);
        }

        .p-action-btn span {
            font-size: 14px;
            font-weight: 500;
        }

        .p-order {
            margin-left: auto;
            background: #2ecc71;
            color: #000;
            padding: 10px 20px;
            border-radius: 99px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: none;
        }

        /* ── BOUTIQUE TAB ── */
        .boutique-categories {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 16px;
            margin-bottom: 8px;
            scrollbar-width: none;
        }

        .boutique-categories::-webkit-scrollbar {
            display: none;
        }

        .b-cat-btn {
            background: rgba(255, 255, 255, 0.03);
            color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 10px 24px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .b-cat-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 10px 25px rgba(245, 0, 87, 0.3);
            transform: translateY(-1px);
        }

        .b-cat-btn:not(.active):hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.15);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        @media (min-width: 640px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
            }
        }

        @media (min-width: 900px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 30px;
            }

            .shop-profile-card {
                border-radius: 40px;
                padding: 40px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
        }

        .prod-card {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            background: rgba(255, 255, 255, 0.02);
            padding: 12px;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }

        .prod-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .prod-img-wrap {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 20px;
            overflow: hidden;
            background: #1a1a1a;
            margin-bottom: 12px;
        }

        .prod-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .prod-card:hover .prod-img-wrap img {
            transform: scale(1.05);
        }

        .prod-name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            line-height: 1.3;
            margin-top: 6px;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            letter-spacing: -0.2px;
        }

        .prod-price {
            font-size: 16px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        /* Override header to float over cover image */
        body > header,
        header:first-of-type {
            position: absolute !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 200 !important;
            background: linear-gradient(to bottom, rgba(0,0,0,0.55) 0%, transparent 100%) !important;
        }

        /* Styles for product modal and checkout moved to includes/modals/product.php */

        /* ✨ HIGH UI/UX WHITE MODE OVERRIDES */
        html.light-mode {
            --bg: #fafafa;
            --surface: #ffffff;
            --text-main: #0f1115;
            --text-muted: #6b7280;
            --border: rgba(0, 0, 0, 0.08);
        }
        
        html.light-mode .shop-profile-card { box-shadow: 0 -20px 40px rgba(0,0,0,0.03); }
        html.light-mode .shop-avatar-wrap { background: #fff; }
        html.light-mode .shop-avatar { border-color: #fff; }
        html.light-mode .shop-name { color: #0f1115; }
        html.light-mode .shop-rating, html.light-mode .shop-dist { color: #4b5563; }
        html.light-mode .round-btn { background: rgba(255, 255, 255, 0.8); color: #000; border-color: rgba(0,0,0,0.1); }
        html.light-mode .round-btn:hover { background: #fff; transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        html.light-mode .tab-btn { background: rgba(0,0,0,0.04); color: #6b7280; }
        html.light-mode .tab-btn.active { background: rgba(130, 140, 255, 0.15); color: #0f1115; }
        
        html.light-mode .b-cat-btn { background: #f3f4f6; color: #6b7280; border-color: rgba(0,0,0,0.03); }
        html.light-mode .b-cat-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 10px 20px rgba(245, 0, 87, 0.2); }
        html.light-mode .b-cat-btn:not(.active):hover { background: #e5e7eb; color: #000; }
        
        html.light-mode .story-item { background: #fff; box-shadow: 0 0 0 2px var(--primary), 0 4px 10px rgba(0,0,0,0.05); }
        html.light-mode .post-card { background: #fff; border-color: rgba(0,0,0,0.08); box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        html.light-mode .post-card:hover { border-color: rgba(0,0,0,0.15); box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        html.light-mode .p-name-wrap { color: #0f1115; }
        html.light-mode .p-text { color: #374151; }
        html.light-mode .post-header button { color: #0f1115 !important; }
        
        html.light-mode .feed-inline-product { background: rgba(0, 0, 0, 0.03); border-color: rgba(0, 0, 0, 0.08); color: #000; }
        html.light-mode .feed-inline-product:hover { background: rgba(0, 0, 0, 0.06); border-color: rgba(0, 0, 0, 0.15); }
        html.light-mode .fip-icon-holder { background: rgba(44, 181, 232, 0.1); color: #0d8abc; }
        html.light-mode .fip-name { color: #000; }
        html.light-mode .fip-price { color: #0d8abc; }
        html.light-mode .fip-right { color: rgba(0, 0, 0, 0.3); }
        
        html.light-mode .prod-card { background: #ffffff; border-color: rgba(0,0,0,0.05); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        html.light-mode .prod-card:hover { background: #ffffff; border-color: rgba(0,0,0,0.08); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
        html.light-mode .prod-name { color: #0f1115; }
        html.light-mode .prod-price { color: var(--primary); }
        
        html.light-mode .cart-slider { background: #fff; border-top: 1px solid rgba(0,0,0,0.08); box-shadow: 0 -10px 30px rgba(0,0,0,0.05); }
        html.light-mode .cart-header h3 { color: #000; }
        html.light-mode .cart-total-label { color: #6b7280; }
        html.light-mode .cart-total-value { color: #0f1115; }
        html.light-mode .cart-item { border-bottom-color: rgba(0,0,0,0.05); }
        html.light-mode .cart-item-name { color: #0f1115; }
        html.light-mode .ci-btn { background: rgba(0,0,0,0.05); color: #000; }
        html.light-mode .ci-btn:hover { background: rgba(0,0,0,1); }
        
        /* Modals Light Mode Overrides */
        html.light-mode #share-modal-overlay { background: rgba(255, 255, 255, 0.4); }
        html.light-mode #share-modal { background: #ffffff !important; border-top-color: rgba(0,0,0,0.08) !important; box-shadow: 0 -10px 40px rgba(0,0,0,0.05) !important; }
        html.light-mode #share-modal h3 { color: #0f1115 !important; }
        html.light-mode #share-modal button { color: #0f1115 !important; }
        html.light-mode #share-modal .share-divider { background: rgba(0,0,0,0.08) !important; }
        html.light-mode #copy-link-btn { background: #f3f4f6 !important; border-color: rgba(0,0,0,0.08) !important; color: #0f1115 !important; }
        html.light-mode #copy-link-btn div { background: rgba(0,0,0,0.05) !important; }
        
        /* ✨ Theme Toggle FAB */
        .theme-fab {
            position: fixed; bottom: 28px; left: 28px; width: 52px; height: 52px;
            border-radius: 50%; border: none; cursor: pointer; z-index: 99999;
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            color: #fff; font-size: 22px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .theme-fab:hover { transform: scale(1.1) rotate(15deg); background: rgba(255, 255, 255, 0.15); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4); }
        html.light-mode .theme-fab { background: #fff; color: #f59e0b; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); border-color: rgba(0, 0, 0, 0.05); }
        html.light-mode .theme-fab:hover { background: #f8f9fa; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); }


    </style>
</head>

<body>


    <!-- Cover Image -->
    <div class="cover-section">
        <?php if ($shopCover): ?>
            <img src="<?= htmlspecialchars($shopCover) ?>" class="cover-img" alt="">
        <?php else: ?>
            <div class="cover-img" style="background: linear-gradient(135deg, #1f1c2c 0%, #928DAB 100%); opacity: 0.8;">
            </div>
        <?php endif; ?>
        <div class="cover-overlay"></div>
        <div class="safe-top-bar">
            <a href="javascript:void(0)" onclick="if(document.referrer){window.location.href=document.referrer;}else{history.back();}"
                class="round-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <button class="round-btn" onclick="openShareModal()">
                <i class="fa-solid fa-arrow-up-from-bracket" style="margin-bottom:2px;"></i>
            </button>
        </div>
    </div>

    <!-- Shop Profile -->
    <div class="shop-profile-card">
        <div class="shop-header-row">
            <div class="shop-avatar-wrap">
                <img src="<?= htmlspecialchars($shopLogo) ?>" class="shop-avatar" alt="">
                <div class="shop-status-dot"></div>
            </div>
            <div class="shop-info-col">
                <div class="shop-name-wrap">
                    <div class="shop-name"><?= htmlspecialchars($shopName) ?></div>
                    <i class="fa-solid fa-circle-check bluetick"></i>
                </div>
                <div class="shop-meta">
                    <div class="shop-rating">
                        <i class="fa-solid fa-star"></i> <?= number_format($shopRate, 1) ?>
                        <span>(<?= $shopReviewers ?>)</span>
                    </div>
                    <?php if ($distKm): ?>
                        <div class="shop-dist"><?= $distKm ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('posts', this)">
                    <i class="fa-solid fa-fire"></i> Posts
                </button>
                <button class="tab-btn" onclick="switchTab('boutique', this)">
                    <i class="fa-solid fa-shop"></i> Boutique
                </button>
            </div>

            <!-- TAB CONTENT: Posts -->
            <div id="tab-posts" class="tab-content active">
                <!-- Shimmer Skeletons for Posts -->
                <div id="posts-shimmer-ui" style="display: block;">
                    <!-- Stories Shimmer -->
                    <?php if (count($reels)): ?>
                        <div class="shop-stories-container" style="overflow: hidden;">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <div class="story-item shimmer-box" style="border:none; box-shadow:none;"></div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Posts Shimmer -->
                    <?php for ($i = 0; $i < 2; $i++): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="shimmer-box" style="width: 44px; height: 44px; border-radius: 50%;"></div>
                                <div class="p-info">
                                    <div class="shimmer-box" style="width: 120px; height: 16px; margin-bottom: 8px;"></div>
                                    <div class="shimmer-box" style="width: 80px; height: 12px;"></div>
                                </div>
                            </div>
                            <div style="padding: 0 24px; margin-bottom: 16px;">
                                <div class="shimmer-box" style="width: 100%; height: 14px; margin-bottom: 6px;"></div>
                                <div class="shimmer-box" style="width: 70%; height: 14px;"></div>
                            </div>
                            <div class="shimmer-box" style="width: 100%; height: 300px; border-radius: 0;"></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Real Posts Content -->
                <div id="posts-real-ui" style="display: none; opacity: 0; transition: opacity 0.4s ease;">
                    <?php if (count($reels)): ?>
                        <div class="shop-stories-container">
                            <?php foreach ($reels as $reel):
                                $mediaSrc = fullUrl($reel['media'], $domain);
                                // Determine video vs image heuristically or by source type, we'll assume image thumbnail for now or video poster
                                $isVid = preg_match('/\.(mp4|mov|webm)$/i', $mediaSrc);
                                $link = "reel.php?id=" . $reel['id'] . "&type=" . $reel['sourceType'] . "&media=" . ($isVid ? 'video' : 'image');
                                ?>
                                <a href="<?= htmlspecialchars($link) ?>" class="story-item">
                                    <?php if ($isVid): ?>
                                        <video src="<?= htmlspecialchars($mediaSrc) ?>" style="width:100%;height:100%;object-fit:cover;"
                                            muted autoplay loop playsinline></video>
                                        <div class="story-badge"><i class="fa-solid fa-play"></i> Reel</div>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($mediaSrc) ?>" alt="Story">
                                        <div class="story-badge"><i class="fa-solid fa-bolt"></i> Story</div>
                                    <?php endif; ?>
                                    <div class="story-overlay"></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (count($posts)): ?>
                        <?php foreach ($posts as $p):
                            $pText = $p['PostText'] ?? '';
                            $pTime = date('M j, Y', strtotime($p['CreatedAtPosts'] ?? 'now'));
                            $pProductId = $p['ProductID'] ?? '';
                            $hasProduct = !empty($pProductId) && $pProductId !== '0' && $pProductId !== 'NONE';
                            $pFoodPrice = (float) ($p['FoodOfferPrice'] ?? 0) > 0 ? $p['FoodOfferPrice'] : ($p['FoodPrice'] ?? 0);
                            ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <img src="<?= htmlspecialchars($shopLogo) ?>" class="p-avatar" alt="">
                                    <div class="p-info">
                                        <div class="p-name-wrap">
                                            <?= htmlspecialchars($shopName) ?> <i class="fa-solid fa-circle-check bluetick"
                                                style="font-size:12px;"></i>
                                        </div>
                                        <div class="p-time"><?= $pTime ?></div>
                                    </div>
                                    <button style="background:none;border:none;color:#fff;"><i
                                            class="fa-solid fa-ellipsis-vertical"></i></button>
                                </div>

                                <?php if ($pText && $pText !== 'NONE'): ?>
                                    <div class="p-text"><?= nl2br(htmlspecialchars($pText)) ?></div>
                                <?php endif; ?>

                                <?php
                                $photos = [];
                                for ($i = 1; $i <= 4; $i++) {
                                    $col = ($i === 1) ? 'PostPhoto' : 'PostPhoto' . $i;
                                    if (!empty($p[$col]) && $p[$col] !== 'NONE' && $p[$col] !== '0') {
                                        $photos[] = fullUrl($p[$col], $domain);
                                    }
                                }
                                ?>
                                <?php if (count($photos) == 1): ?>
                                    <img src="<?= htmlspecialchars($photos[0]) ?>" class="p-img" loading="lazy" alt="">
                                <?php elseif (count($photos) > 1): ?>
                                    <div class="carousel-container"
                                        style="position: relative; margin: 0 0 16px 0; border-radius: 16px; overflow: hidden;">
                                        <div class="no-scrollbar"
                                            style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none;"
                                            onscroll="updateCarouselIndicator(this)">
                                            <?php foreach ($photos as $url): ?>
                                                <img src="<?= htmlspecialchars($url) ?>" class="p-img" loading="lazy" alt=""
                                                    style="flex: 0 0 100%; scroll-snap-align: center; border-radius: 0; margin-bottom: 0;">
                                            <?php endforeach; ?>
                                        </div>
                                        <div
                                            style="position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; z-index: 10; pointer-events: none;">
                                            <?php foreach ($photos as $i => $url): ?>
                                                <div class="carousel-dot <?= $i === 0 ? 'active' : '' ?>"
                                                    style="width:6px; height:6px; border-radius:50%; background: <?= $i === 0 ? '#fff' : 'rgba(255,255,255,0.4)' ?>; transition: background 0.3s;">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasProduct):
                                    $rawPhotos1 = array_filter(array_map('trim', explode(',', $p['FoodPhoto'] ?? '')));
                                    $imgUrls1 = [];
                                    foreach($rawPhotos1 as $rp) { if($rp) $imgUrls1[] = fullUrl($rp, $domain); }
                                    $inlineFoodJson = json_encode([
                                        'id' => $pProductId,
                                        'name' => $p['FoodName'] ?? 'Product',
                                        'price' => $pFoodPrice,
                                        'oldPrice' => floatval($p['FoodOfferPrice'] ?? 0) > 0 ? floatval($p['FoodPrice'] ?? 0) : null,
                                        'img' => !empty($imgUrls1) ? $imgUrls1[0] : '',
                                        'images' => $imgUrls1,
                                        'desc' => $p['FoodDesc'] ?? '',
                                        'cat_id' => $shop['CategoryID'] ?? 0,
                                        'extra1' => $p['Extraone'] ?? '',
                                        'extra2' => $p['Extratwo'] ?? '',
                                        'extra1_p' => floatval($p['ExtraPriceOne'] ?? 0),
                                        'extra2_p' => floatval($p['ExtraPriceTwo'] ?? 0)
                                    ]);
                                    ?>
                                    <a href="javascript:void(0)" onclick="openProductModal(this)"
                                        data-product='<?= htmlspecialchars($inlineFoodJson, ENT_QUOTES, 'UTF-8') ?>'
                                        class="feed-inline-product">
                                        <div class="fip-left">
                                            <div class="fip-icon-holder"><i class="fa-solid fa-utensils"></i></div>
                                            <div>
                                                <div class="fip-name"><?= htmlspecialchars($p['FoodName'] ?? 'Product') ?></div>
                                                <div class="fip-price"><?= number_format($pFoodPrice, 2) ?> MAD</div>
                                            </div>
                                        </div>
                                        <i class="fa-solid fa-chevron-right" style="color: rgba(255,255,255,0.3);"></i>
                                    </a>
                                <?php endif; ?>

                                <div class="p-actions">
                                    <button class="p-action-btn" onclick="handleLike(this, <?= $p['PostId'] ?? $p['PostID'] ?? '0' ?>, <?= $shopId ?>)"><i class="fa-regular fa-heart"></i>
                                        <span><?= intval($p['PostLikes'] ?? 0) ?></span></button>
                                    <button class="p-action-btn"
                                        onclick="openCommentModal(<?= $p['PostId'] ?? $p['PostID'] ?? '0' ?>, '<?= addslashes(htmlspecialchars($shopName)) ?>')"><i
                                            class="fa-regular fa-comment"></i>
                                        <span><?= intval($p['Postcomments'] ?? 0) ?></span></button>
                                    <?php if ($hasProduct): ?>
                                        <a href="javascript:void(0)" onclick="openProductModal(this)" data-product='<?= htmlspecialchars($inlineFoodJson, ENT_QUOTES, 'UTF-8') ?>' class="p-order">Commande</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-solid fa-camera" style="font-size:32px; margin-bottom:10px; opacity:0.3;"></i>
                            <p>No posts yet</p>
                        </div>
                    <?php endif; ?>
                </div> <!-- End posts-real-ui -->
            </div>

            <!-- TAB CONTENT: Boutique -->
            <div id="tab-boutique" class="tab-content">
                <!-- Shimmer Skeletons for Boutique -->
                <div id="boutique-shimmer-ui" style="display: block;">
                    <!-- Categories Shimmer -->
                    <?php if (count($boutiqueCategories)): ?>
                        <div class="boutique-categories">
                            <div class="shimmer-box"
                                style="height: 38px; width: 90px; border-radius: 99px; margin-right: 10px; display: inline-block;">
                            </div>
                            <div class="shimmer-box"
                                style="height: 38px; width: 110px; border-radius: 99px; margin-right: 10px; display: inline-block;">
                            </div>
                            <div class="shimmer-box"
                                style="height: 38px; width: 80px; border-radius: 99px; display: inline-block;"></div>
                        </div>
                        <div class="products-grid">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <div class="prod-card" style="border:none; box-shadow:none;">
                                    <div class="shimmer-box"
                                        style="width: 100%; aspect-ratio: 1; border-radius: 20px; margin-bottom: 12px;"></div>
                                    <div class="shimmer-box" style="width: 80%; height: 16px; margin-bottom: 8px;"></div>
                                    <div class="shimmer-box" style="width: 50%; height: 16px;"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Real Boutique Content -->
                <div id="boutique-real-ui" style="display: none; opacity: 0; transition: opacity 0.4s ease;">
                    <?php if (count($boutiqueCategories)): ?>
                    				<div class="boutique-categories">
									<?php $firstCat = true;
									foreach ($boutiqueCategories as $catId => $catName): ?>
										<button class="b-cat-btn <?= $firstCat ? 'active' : '' ?>" onclick="filterCat(this, <?= $catId ?>)">
											<?= htmlspecialchars(strtoupper($catName)) ?>
										</button>
										<?php $firstCat = false; endforeach; ?>
								</div>

                        <!-- Category Switching Shimmer Grid -->
                        <div class="products-grid" id="cat-shimmer-grid" style="display:none;">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <div class="prod-card" style="border:none; box-shadow:none;">
                                    <div class="shimmer-box"
                                        style="width: 100%; aspect-ratio: 1; border-radius: 20px; margin-bottom: 12px;"></div>
                                    <div class="shimmer-box" style="width: 80%; height: 16px; margin-bottom: 8px;"></div>
                                    <div class="shimmer-box" style="width: 50%; height: 16px;"></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="products-grid" id="products-grid">
                            <?php foreach ($boutiqueFoodsByCat as $catId => $items): ?>
                                <?php foreach ($items as $item):
                                    $rawPhotos2 = array_filter(array_map('trim', explode(',', $item['FoodPhoto'] ?? '')));
                                    $imgUrls2 = [];
                                    foreach($rawPhotos2 as $rp) { if($rp) $imgUrls2[] = fullUrl($rp, $domain); }
                                    $img = !empty($imgUrls2) ? $imgUrls2[0] : '';
                                    $price = floatval($item['FoodOfferPrice'] ?? 0) > 0 ? floatval($item['FoodOfferPrice']) : floatval($item['FoodPrice']);
                                    $foodJson = json_encode([
                                        'id' => $item['FoodID'],
                                        'name' => $item['FoodName'] ?? 'Product',
                                        'price' => $price,
                                        'oldPrice' => floatval($item['FoodOfferPrice'] ?? 0) > 0 ? floatval($item['FoodPrice'] ?? 0) : null,
                                        'img' => $img,
                                        'images' => $imgUrls2,
                                        'desc' => $item['FoodDesc'] ?? '',
                                        'cat_id' => $shop['CategoryID'] ?? 0,
                                        'extra1' => $item['Extraone'] ?? '',
                                        'extra2' => $item['Extratwo'] ?? '',
                                        'extra1_p' => floatval($item['ExtraPriceOne'] ?? 0),
                                        'extra2_p' => floatval($item['ExtraPriceTwo'] ?? 0)
                                    ]);
                                    ?>
                                    <a href="javascript:void(0)" onclick="openProductModal(this)"
                                        data-product="<?= htmlspecialchars($foodJson, ENT_COMPAT, 'UTF-8') ?>" class="prod-card"
                                        data-cat="<?= $catId ?>">
                                        <div class="prod-img-wrap">
                                            <?php if ($img): ?>
                                                <img src="<?= htmlspecialchars($img) ?>" loading="lazy" alt="">
                                            <?php endif; ?>
                                        </div>
                                        <div class="prod-name"><?= htmlspecialchars($item['FoodName']) ?></div>
                                        <div class="prod-price"><?= number_format($price, 0) ?> MAD</div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-solid fa-store-slash" style="font-size:32px; margin-bottom:10px; opacity:0.3;"></i>
                            <p>No products available</p>
                        </div>
                    <?php endif; ?>
                </div> <!-- End boutique-real-ui -->
            </div>

        </div>
    <!-- End of tabs -->

    <script>
        let boutiqueLoaded = false;

        // Init Shimmer logic for Posts directly on load (since it's the default active tab)
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                // Reveal Posts
                const postsShimmer = document.getElementById('posts-shimmer-ui');
                const postsReal = document.getElementById('posts-real-ui');
                if (postsShimmer && postsReal) {
                    postsShimmer.style.display = 'none';
                    postsReal.style.display = 'block';
                    // Trigger reflow allowing opacity to transition
                    void postsReal.offsetWidth;
                    postsReal.style.opacity = '1';
                }

                // Initialize Category
                const firstCatBtn = document.querySelector('.b-cat-btn.active');
                if (firstCatBtn) firstCatBtn.click();
            }, 600); // 600ms delay to simulate loading or let dom paint
        });

        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            else {
                const targetBtn = document.querySelector(`.tab-btn[onclick*="'${tabId}'"]`);
                if (targetBtn) targetBtn.classList.add('active');
            }

            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.add('active');

            // Trigger Boutique Shimmer visually if it's the first time being opened
            if (tabId === 'boutique' && !boutiqueLoaded) {
                boutiqueLoaded = true;
                setTimeout(() => {
                    const bShimmer = document.getElementById('boutique-shimmer-ui');
                    const bReal = document.getElementById('boutique-real-ui');
                    if (bShimmer && bReal) {
                        bShimmer.style.display = 'none';
                        bReal.style.display = 'block';
                        // Trigger reflow
                        void bReal.offsetWidth;
                        bReal.style.opacity = '1';
                    }
                }, 600);
            }
        }

        let filterTimeout;
        function filterCat(btn, catId) {
            document.querySelectorAll('.b-cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const shimmerGrid = document.getElementById('cat-shimmer-grid');
            const realGrid = document.getElementById('products-grid');

            if (shimmerGrid && realGrid) {
                clearTimeout(filterTimeout);
                realGrid.style.display = 'none';
                shimmerGrid.style.display = 'grid';

                // Filter invisibly
                document.querySelectorAll('.prod-card[data-cat]').forEach(card => {
                    if (card.dataset.cat == catId) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                filterTimeout = setTimeout(() => {
                    shimmerGrid.style.display = 'none';
                    realGrid.style.display = 'grid';
                }, 400); // 400ms synthetic loading effect
            }
        }

        // Handle dots in multi-image carousel
        function updateCarouselIndicator(el) {
            const index = Math.round(el.scrollLeft / el.offsetWidth);
            const dots = el.nextElementSibling.querySelectorAll('.carousel-dot');
            dots.forEach((dot, i) => {
                dot.style.background = i === index ? '#fff' : 'rgba(255,255,255,0.4)';
            });
        }
    </script>

    <!-- COMMENTS MODAL -->
    <div id="comments-modal-overlay"
        style="position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
        <div id="comments-modal"
            style="width: 100%; max-width: 600px; height: 75vh; background: #111; border-top-left-radius: 24px; border-top-right-radius: 24px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.5);">
            <div style="width: 100%; display: flex; justify-content: center; padding: 12px 0; cursor: pointer;"
                onclick="closeCommentModal()">
                <div style="width: 40px; height: 5px; background: rgba(255,255,255,0.2); border-radius: 10px;"></div>
            </div>
            <h3
                style="text-align: center; font-size: 16px; font-weight: 600; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff;">
                Comments</h3>
            <div id="comments-feed" style="flex: 1; overflow-y: auto; padding: 20px;"></div>
            <div
                style="padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); background: #0a0a0a; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; padding-bottom: 32px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="https://ui-avatars.com/api/?name=Me&background=2cb5e8&color=fff"
                        style="width: 36px; height: 36px; border-radius: 50%;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" id="comment-input" placeholder="Add a comment..."
                            style="width: 100%; height: 44px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 22px; padding: 0 44px 0 16px; color: #fff; font-size: 14px; font-family: Inter, sans-serif; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='rgba(255,255,255,0.3)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <button onclick="sendComment()"
                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; background: #fff; color: #000; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-50%) scale(1.1)'"
                            onmouseout="this.style.transform='translateY(-50%) scale(1)'">
                            <i class="fa-solid fa-arrow-up" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var commentModalOverlay = document.getElementById('comments-modal-overlay');
        var commentModal = document.getElementById('comments-modal');
        var commentInput = document.getElementById('comment-input');
        var commentsFeed = document.getElementById('comments-feed');

        var currentPostId = null;
        var currentShopName = null;

        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) { hash = str.charCodeAt(i) + ((hash << 5) - hash); }
            let c = (hash & 0x00FFFFFF).toString(16).toUpperCase();
            return "00000".substring(0, 6 - c.length) + c;
        }

        function timeAgo(dateString) {
            if (!dateString) return '1m';
            var d = new Date(dateString.replace(' ', 'T'));
            var now = new Date();
            var diff = Math.floor((now - d) / 1000);
            if (diff < 60) return diff + 's';
            if (diff < 3600) return Math.floor(diff / 60) + 'm';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            return Math.floor(diff / 86400) + 'd';
        }


        function openCommentModal(postId, shopName) {
            const isLoggedIn = <?= isset($_COOKIE['qoon_user_id']) ? 'true' : 'false' ?>;
            if (!isLoggedIn) {
                window.location.href = 'index.php?auth_required=1';
                return;
            }
            currentPostId = postId;
            currentShopName = shopName;

            commentModalOverlay.style.display = 'flex';
            setTimeout(() => {
                commentModalOverlay.style.opacity = '1';
                commentModal.style.transform = 'translateY(0)';
            }, 10);
            document.body.style.overflow = 'hidden';

            commentInput.placeholder = shopName ? `Reply to ${shopName}...` : `Add a comment...`;
            commentInput.value = '';

            commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i></div>';

            let formData = new FormData();
            formData.append('PostID', postId);

            fetch('GetPostCommentsWeb.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(json => {
                    if (json.success && json.data && json.data.length > 0) {
                        let html = '';
                        json.data.forEach(c => {
                            let userName = (c.name || 'User');
                            let color = stringToColor(userName);
                            let photo = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff`;

                            if (c.UserPhoto && c.UserPhoto !== 'NONE' && c.UserPhoto.trim() !== '') {
                                if (c.UserPhoto.includes('http')) {
                                    photo = c.UserPhoto;
                                } else {
                                    let base = '<?= $DomainNamee ?? "https://qoon.app/dash/" ?>';
                                    photo = c.UserPhoto.startsWith('photo/') ? base + c.UserPhoto : base + 'photo/' + c.UserPhoto;
                                }
                            }
                            let text = (c.CommentText || '').replace(/\n/g, '<br>');

                            html += `
                            <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                                <img src="${photo}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff'">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: baseline; gap: 8px;">
                                        <span style="font-weight: 600; font-size: 14px; color: #fff;">${userName}</span>
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5);">${timeAgo(c.CreatedAtComments)}</span>
                                    </div>
                                    <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 4px; line-height: 1.4; word-break: break-word;">${text}</p>
                                    <div style="display: flex; gap: 16px; margin-top: 8px;">
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'" onclick="setReply('${userName}')">Reply</span>
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer;"><i class="fa-regular fa-heart"></i> 0</span>
                                    </div>
                                </div>
                            </div>
                            `;
                        });
                        commentsFeed.innerHTML = html;
                    } else {
                        commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">No comments yet. Be the first to comment!</div>';
                    }
                })
                .catch(err => {
                    commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">Failed to load comments</div>';
                });
        }

        function setReply(name) {
            commentInput.placeholder = `Reply to ${name}...`;
            commentInput.focus();
        }

        function sendComment() {
            const text = commentInput.value.trim();
            if (!text || !currentPostId) return;

            const btn = document.querySelector('#comments-modal button i');
            btn.className = 'fa-solid fa-circle-notch fa-spin';

            let formData = new FormData();
            formData.append('PostID', currentPostId);
            formData.append('CommentText', text);
            formData.append('UserID', '1000000'); // Demo UserID
            formData.append('ShopID', '0');      // Default

            fetch('AddComment.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        commentInput.value = '';
                        openCommentModal(currentPostId, currentShopName); // Refresh list
                    } else {
                        alert('Could not post comment. Please try again.');
                    }
                })
                .finally(() => { btn.className = 'fa-solid fa-arrow-up'; });
        }

        commentInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendComment();
        });

        function closeCommentModal() {
            commentModalOverlay.style.opacity = '0';
            commentModal.style.transform = 'translateY(100%)';
            setTimeout(() => {
                commentModalOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 400);
        }

        commentModalOverlay.addEventListener('click', (e) => {
            if (e.target === commentModalOverlay) closeCommentModal();
        });

        /* --- SHARE MODAL --- */
        function openShareModal() {
            document.getElementById('share-modal-overlay').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('share-modal-overlay').style.opacity = '1';
                document.getElementById('share-modal').style.transform = 'translateY(0)';
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeShareModal() {
            document.getElementById('share-modal-overlay').style.opacity = '0';
            document.getElementById('share-modal').style.transform = 'translateY(100%)';
            setTimeout(() => {
                document.getElementById('share-modal-overlay').style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }

        function shareTo(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            let shareUrl = '';

            if (platform === 'whatsapp') shareUrl = `https://api.whatsapp.com/send?text=${title}%20${url}`;
            if (platform === 'facebook') shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            // Instagram doesn't support web direct links natively through URL parameters easily, so fallback to clipboard copy
            if (platform === 'instagram') return copyLink();
            if (platform === 'twitter') shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;

            if (shareUrl) window.open(shareUrl, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const btn = document.getElementById('copy-link-btn');
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check" style="font-size:20px; margin-bottom:8px;"></i>Copied!';
                btn.style.color = '#2ecc71';
                setTimeout(() => {
                    btn.innerHTML = origHtml;
                    btn.style.color = '';
                }, 2000);
            });
        }
    </script>

    <?php include 'includes/modals/product.php'; ?>
    <!-- SHARE MODAL HTML & LOGIC -->
    <div id="share-modal-overlay"
        style="position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
        <div id="share-modal"
            style="width: 100%; max-width: 500px; padding: 24px 20px; background: #1a1a1a; border-top-left-radius: 28px; border-top-right-radius: 28px; border-top: 1px solid rgba(255,255,255,0.1); transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.8);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #fff;">Share to</h3>
                <button onclick="closeShareModal()"
                    style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
                <!-- WhatsApp -->
                <button onclick="shareTo('whatsapp')"
                    style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="width: 56px; height: 56px; border-radius: 50%; background: #25D366; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(37,211,102,0.4);">
                        <i class="fa-brands fa-whatsapp"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">WhatsApp</span>
                </button>
                <!-- Instagram (Copy Link representation) -->
                <button onclick="shareTo('instagram')"
                    style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(220,39,67,0.4);">
                        <i class="fa-brands fa-instagram"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">Instagram</span>
                </button>
                <!-- Facebook -->
                <button onclick="shareTo('facebook')"
                    style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="width: 56px; height: 56px; border-radius: 50%; background: #1877F2; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(24,119,242,0.4);">
                        <i class="fa-brands fa-facebook-f"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">Facebook</span>
                </button>
                <!-- Twitter -->
                <button onclick="shareTo('twitter')"
                    style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="width: 56px; height: 56px; border-radius: 50%; background: #000; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fa-brands fa-x-twitter"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">X</span>
                </button>
            </div>

            <div class="share-divider" style="width: 100%; height: 1px; background: rgba(255,255,255,0.1); margin-bottom: 16px;"></div>

            <button id="copy-link-btn" onclick="copyLink()"
                style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 16px; color: #fff; font-size: 15px; font-weight: 600; font-family: inherit; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s;">
                <div
                    style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-link"></i></div>
                <span style="flex: 1; text-align: left;">Copy Link</span>
            </button>
        </div>
    </div>
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
        // --- CART RESTORATION AFTER LOGIN ---
        document.addEventListener('DOMContentLoaded', () => {
            const pendingCart = localStorage.getItem('qoon_pending_cart_<?= $shopId ?>');
            if (pendingCart) {
                try {
                    cartItems = JSON.parse(pendingCart);
                    updateCartWidget();
                    localStorage.removeItem('qoon_pending_cart_<?= $shopId ?>');
                    // Automatically open checkout if they successfully logged in
                    const isLoggedIn = <?= isset($_COOKIE['qoon_user_id']) ? 'true' : 'false' ?>;
                    if (isLoggedIn) {
                        openCheckoutModal();
                    }
                } catch (e) {
                    console.error("Failed to restore cart", e);
                }
            }
        });
    </script>

    
    <script>
         catch (e) { console.error("Firebase Init Error:", e); }
    </script>

    <!-- MODALS -->
    <?php include 'includes/modals/comments.php'; ?>
    <?php include 'includes/modals/auth.php'; ?>

    <script>
        

        // --- Auto-open Product Modal Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('product');
            const forceBoutique = urlParams.get('boutique');
            
            if (productId || forceBoutique) {
                switchTab('boutique');
                
                // Wait for the shimmer and render to complete (600ms shimmer + 100ms buffer)
                setTimeout(() => {
                    const productLinks = document.querySelectorAll('a[onclick*="openProductModal"]');
                    for (let i = 0; i < productLinks.length; i++) {
                        try {
                            const data = JSON.parse(productLinks[i].getAttribute('data-product'));
                            if (data.id == productId) {
                                openProductModal(productLinks[i]);
                                break;
                            }
                        } catch(e) {}
                    }
                }, 800);
            }
        });
    </script>
    
    <!-- Theme Toggle FAB -->
    <button class="theme-fab" id="themeToggleBtn" aria-label="Toggle Light/Dark Mode">
        <i class="fa-solid fa-moon"></i>
    </button>
    <script>
        const themeBtn = document.getElementById('themeToggleBtn');
        const themeIcon = themeBtn.querySelector('i');
        const html = document.documentElement;

        function updateThemeIcon() {
            if (html.classList.contains('light-mode')) {
                themeIcon.className = 'fa-solid fa-sun';
            } else {
                themeIcon.className = 'fa-solid fa-moon';
            }
        }

        updateThemeIcon();

        themeBtn.addEventListener('click', () => {
            html.classList.toggle('light-mode');
            if (html.classList.contains('light-mode')) {
                localStorage.setItem('qoon_theme', 'light');
            } else {
                localStorage.setItem('qoon_theme', 'dark');
            }
            updateThemeIcon();
        });
    </script>
</body>

</html>
<?php if (isset($con) && $con) mysqli_close($con); ?>




