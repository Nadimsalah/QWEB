<?php
define('FROM_UI', true);
require_once 'conn.php';

$catRaw     = $_GET['cat'] ?? '38';
$catId      = is_numeric($catRaw) ? intval($catRaw) : 0;
$isVirtual  = !is_numeric($catRaw); // e.g. 'esims', 'flights'
$startId    = intval($_GET['id']  ?? 0);
$sourceType = $_GET['type'] ?? 'story';
$DomainNamee = 'https://qoon.app/dash/';

// Virtual category name map
$virtualNames = ['esims' => 'Global eSIM', 'flights' => 'Book Flight'];

// User location
$userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float)$_COOKIE['qoon_lat'] : null;
$userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float)$_COOKIE['qoon_lon'] : null;
$locationRequired = (!$userLat || !$userLon);

// ── Category name ──
$catName = 'Category';
if ($isVirtual) {
    $catName = $virtualNames[strtolower($catRaw)] ?? ucfirst($catRaw);
} elseif ($con) {
    $r = $con->query("SELECT EnglishCategory FROM Categories WHERE CategoryId=$catId LIMIT 1");
    if ($r && $r->num_rows) $catName = $r->fetch_assoc()['EnglishCategory'];
}

// ── Shop IDs for this category (only for real categories) ──
$shopIds = [];
if (!$isVirtual && $con) {
    $r = $con->query("SELECT ShopID FROM Shops WHERE CategoryID=$catId AND Status='ACTIVE'");
    if ($r) while ($row = $r->fetch_assoc()) $shopIds[] = $row['ShopID'];
}

// ── Fetch reels/stories ──
$reelsData = [];
// For virtual categories (esims, flights) OR real categories with shops
if ($con && ($isVirtual || count($shopIds))) {
    $shopFilter = '';
    if (!$isVirtual && count($shopIds)) {
        $inList = implode(',', $shopIds);
        $shopFilter = " AND Posts.ShopID IN ($inList)";
    }
    $shopFilterStory = '';
    if (!$isVirtual && count($shopIds)) {
        $inList = implode(',', $shopIds);
        $shopFilterStory = " AND Shops.ShopID IN ($inList)";
    }

    $innerSql = "
        SELECT Posts.PostID AS id, 'post' AS sourceType,
               Posts.Video AS rawMedia,
               Posts.PostText AS caption, 'VIDEO' AS storyType,
               Shops.ShopName AS shopName, Shops.ShopLogo AS shopLogo,
               Shops.ShopID AS shopId, Shops.ShopLat AS lat, Shops.ShopLongt AS lon,
               Posts.ProductID AS productId,
               Foods.FoodName AS foodName, Foods.FoodPrice AS foodPrice, Foods.FoodOfferPrice AS foodOfferPrice, Foods.FoodPhoto AS foodPhoto
        FROM Posts 
        JOIN Shops ON Shops.ShopID = Posts.ShopID
        LEFT JOIN Foods ON Foods.FoodID = Posts.ProductID
        WHERE Shops.Status='ACTIVE' AND Posts.PostStatus='ACTIVE'
          AND Posts.Video != '' AND Posts.Video != '0'
          $shopFilter
        UNION ALL
        SELECT ShopStory.StotyID AS id, 'story' AS sourceType,
               ShopStory.StoryPhoto AS rawMedia,
               '' AS caption, ShopStory.StotyType AS storyType,
               Shops.ShopName AS shopName, Shops.ShopLogo AS shopLogo,
               Shops.ShopID AS shopId, Shops.ShopLat AS lat, Shops.ShopLongt AS lon,
               ShopStory.ProductId AS productId,
               Foods.FoodName AS foodName, Foods.FoodPrice AS foodPrice, Foods.FoodOfferPrice AS foodOfferPrice, Foods.FoodPhoto AS foodPhoto
        FROM Shops 
        JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID
        LEFT JOIN Foods ON Foods.FoodID = ShopStory.ProductId
        WHERE Shops.Status='ACTIVE' AND ShopStory.StoryStatus='ACTIVE'
          AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
          $shopFilterStory
    ";

    $sql = "SELECT * FROM ($innerSql) AS m ORDER BY id DESC LIMIT 80";
    $res = $con->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rawMedia  = trim($row['rawMedia'] ?? '');
            $storyType = strtoupper(trim($row['storyType'] ?? ''));
            $mediaUrl  = '';
            $mediaType = 'image';

            if (!empty($rawMedia) && $rawMedia !== '0' && $rawMedia !== '-') {
                $rawMedia = str_replace('jibler.app', 'qoon.app', $rawMedia);
                // Fix double slashes
                $rawMedia = preg_replace('#(?<!:)//+#', '/', $rawMedia);
                $mediaUrl = (strpos($rawMedia, 'http') !== false)
                    ? $rawMedia
                    : $DomainNamee . 'photo/' . $rawMedia;
                $ext = strtolower(pathinfo($rawMedia, PATHINFO_EXTENSION));
                $mediaType = ($storyType === 'VIDEO' || in_array($ext, ['mp4','mov','webm','avi','mkv']))
                    ? 'video' : 'image';
            }
            if (!$mediaUrl) continue;

            $logo = trim($row['shopLogo'] ?? '');
            if ($logo && strpos($logo, 'http') === false) {
                $logo = $DomainNamee . 'photo/' . $logo;
            }
            $logo = preg_replace('#(?<!:)//+#', '/', $logo);

            $foodName = $row['foodName'] ?? 'Product';
            $foodPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodOfferPrice']) : floatval($row['foodPrice'] ?? 0);
            $oldPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodPrice'] ?? 0) : null;
            
            $foodPhoto = trim($row['foodPhoto'] ?? '');
            if ($foodPhoto && strpos($foodPhoto, 'http') === false) {
                $foodPhoto = $DomainNamee . 'photo/' . $foodPhoto;
            }

            $reelsData[] = [
                'id'         => (int)$row['id'],
                'sourceType' => $row['sourceType'],
                'mediaUrl'   => $mediaUrl,
                'mediaType'  => $mediaType,
                'caption'    => htmlspecialchars($row['caption'] ?? ''),
                'shopName'   => htmlspecialchars($row['shopName'] ?? 'Shop'),
                'shopLogo'   => $logo,
                'shopId'     => (int)($row['shopId'] ?? 0),
                'productId'  => (int)($row['productId'] ?? 0),
                'foodName'   => htmlspecialchars($foodName),
                'foodPrice'  => $foodPrice,
                'oldPrice'   => $oldPrice,
                'foodPhoto'  => $foodPhoto,
            ];
        }
    }
}

// Fallback: if category had no reels, show global feed
if (empty($reelsData) && $con && !$isVirtual && $catId > 0) {
    $fallbackSql = "
        SELECT Posts.PostID AS id, 'post' AS sourceType,
               Posts.Video AS rawMedia,
               Posts.PostText AS caption, 'VIDEO' AS storyType,
               Shops.ShopName AS shopName, Shops.ShopLogo AS shopLogo,
               Shops.ShopID AS shopId, Shops.ShopLat AS lat, Shops.ShopLongt AS lon,
               Posts.ProductID AS productId,
               Foods.FoodName AS foodName, Foods.FoodPrice AS foodPrice, Foods.FoodOfferPrice AS foodOfferPrice, Foods.FoodPhoto AS foodPhoto
        FROM Posts 
        JOIN Shops ON Shops.ShopID = Posts.ShopID
        LEFT JOIN Foods ON Foods.FoodID = Posts.ProductID
        WHERE Shops.Status='ACTIVE' AND Posts.PostStatus='ACTIVE'
          AND Posts.Video != '' AND Posts.Video != '0'
        UNION ALL
        SELECT ShopStory.StotyID AS id, 'story' AS sourceType,
               ShopStory.StoryPhoto AS rawMedia,
               '' AS caption, ShopStory.StotyType AS storyType,
               Shops.ShopName AS shopName, Shops.ShopLogo AS shopLogo,
               Shops.ShopID AS shopId, Shops.ShopLat AS lat, Shops.ShopLongt AS lon,
               ShopStory.ProductId AS productId,
               Foods.FoodName AS foodName, Foods.FoodPrice AS foodPrice, Foods.FoodOfferPrice AS foodOfferPrice, Foods.FoodPhoto AS foodPhoto
        FROM Shops 
        JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID
        LEFT JOIN Foods ON Foods.FoodID = ShopStory.ProductId
        WHERE Shops.Status='ACTIVE' AND ShopStory.StoryStatus='ACTIVE'
          AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
    ";
    $fbRes = $con->query("SELECT * FROM ($fallbackSql) AS m ORDER BY id DESC LIMIT 80");
    if ($fbRes) {
        while ($row = $fbRes->fetch_assoc()) {
            $rawMedia  = trim($row['rawMedia'] ?? '');
            $storyType = strtoupper(trim($row['storyType'] ?? ''));
            $mediaUrl  = ''; $mediaType = 'image';
            if (!empty($rawMedia) && $rawMedia !== '0' && $rawMedia !== '-') {
                $rawMedia = str_replace('jibler.app', 'qoon.app', $rawMedia);
                $rawMedia = preg_replace('#(?<!:)//+#', '/', $rawMedia);
                $mediaUrl = (strpos($rawMedia, 'http') !== false) ? $rawMedia : $DomainNamee . 'photo/' . $rawMedia;
                $ext = strtolower(pathinfo($rawMedia, PATHINFO_EXTENSION));
                $mediaType = ($storyType === 'VIDEO' || in_array($ext, ['mp4','mov','webm','avi','mkv'])) ? 'video' : 'image';
            }
            if (!$mediaUrl) continue;
            $logo = trim($row['shopLogo'] ?? '');
            if ($logo && strpos($logo, 'http') === false) $logo = $DomainNamee . 'photo/' . $logo;
            $logo = preg_replace('#(?<!:)//+#', '/', $logo);
            $foodName = $row['foodName'] ?? 'Product';
            $foodPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodOfferPrice']) : floatval($row['foodPrice'] ?? 0);
            $oldPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodPrice'] ?? 0) : null;
            $foodPhoto = trim($row['foodPhoto'] ?? '');
            if ($foodPhoto && strpos($foodPhoto, 'http') === false) $foodPhoto = $DomainNamee . 'photo/' . $foodPhoto;
            $reelsData[] = [
                'id' => (int)$row['id'], 'sourceType' => $row['sourceType'], 'mediaUrl' => $mediaUrl,
                'mediaType' => $mediaType, 'caption' => htmlspecialchars($row['caption'] ?? ''),
                'shopName' => htmlspecialchars($row['shopName'] ?? 'Shop'), 'shopLogo' => $logo,
                'productId' => (int)($row['productId'] ?? 0), 'foodName' => htmlspecialchars($foodName),
                'foodPrice' => $foodPrice, 'oldPrice' => $oldPrice, 'foodPhoto' => $foodPhoto,
            ];
        }
    }
}

if (isset($con) && $con) mysqli_close($con);

$reelsJson = json_encode($reelsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
$startIdJs = (int)$startId;
$sourceJs  = json_encode($sourceType);
$catNameJs = json_encode($catName);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($catName) ?> · Stories &amp; Reels · QOON</title>
    <meta name="theme-color" content="#000000">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        html, body { width:100%; height:100dvh; overflow:hidden; background:#000; font-family:'Inter',sans-serif; color:#fff; }

        /* --- Location Request Overlay --- */
        .location-overlay {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
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
            color: rgba(255,255,255,0.6);
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
            overflow: hidden !important;
        }

        #bg-blur {
            position:fixed; inset:0; z-index:0; background:#111;
            filter:blur(40px) brightness(0.35) saturate(1.4);
            background-size:cover; background-position:center;
            transform:scale(1.1); transition:background-image .5s ease;
        }

        #desktop-wrapper {
            position:relative; z-index:1; width:100%; height:100dvh;
            display:flex; align-items:center; justify-content:center; gap:28px;
        }

        #stage {
            position:relative; height:100dvh; max-height:820px;
            aspect-ratio:9/16; border-radius:20px; overflow:hidden;
            box-shadow:0 32px 80px rgba(0,0,0,.8); flex-shrink:0;
            touch-action: pan-y;
        }

        @media (max-width:600px) {
            #stage { border-radius:0; max-height:100dvh; width:100%; aspect-ratio:unset; }
            #desktop-wrapper { gap:0; }
            #right-panel { display:none !important; }
            .nav-btn { display:none !important; }
            #index-pill { display:none !important; }
        }

        .reel-slide {
            position:absolute; inset:0;
            display:flex; align-items:center; justify-content:center;
            background:#000;
            transition:transform .42s cubic-bezier(.4,0,.2,1), opacity .42s cubic-bezier(.4,0,.2,1);
            will-change:transform, opacity;
        }
        .reel-slide.above  { transform:translateY(-100%); opacity:0; pointer-events:none; }
        .reel-slide.active { transform:translateY(0);     opacity:1; pointer-events:auto; }
        .reel-slide.below  { transform:translateY(100%);  opacity:0; pointer-events:none; }
        .reel-slide.hidden { transform:translateY(200%);  opacity:0; pointer-events:none; }

        .slide-media { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; background:#0a0a0a; }

        /* Category badge at top-center */
        .cat-badge {
            position:absolute; top:16px; left:50%; transform:translateX(-50%);
            background:rgba(44,181,232,0.18); border:1px solid rgba(44,181,232,.35);
            color:#2cb5e8; border-radius:99px; padding:5px 14px;
            font-size:12px; font-weight:700; z-index:20; white-space:nowrap;
            backdrop-filter:blur(6px);
        }

        .slide-top {
            position:absolute; top:0; left:0; right:0; height:160px;
            background:linear-gradient(to bottom,rgba(0,0,0,.5),transparent);
            z-index:20; display:flex; align-items:flex-start; padding:20px 16px 0; gap:10px;
            pointer-events: none;
        }
        .slide-top > * { pointer-events: auto; }
        
        .slide-bottom {
            position:absolute; bottom:0; left:0; right:0;
            padding:80px 72px 36px 16px;
            background:linear-gradient(transparent,rgba(0,0,0,.6)); z-index:20;
            pointer-events: none;
        }
        .slide-bottom > * { pointer-events: auto; }
        .slide-shop-name { font-size:15px; font-weight:700; text-shadow:0 1px 6px rgba(0,0,0,.9); }
        .slide-caption   { font-size:13px; color:rgba(255,255,255,.82); margin-top:6px; line-height:1.5; max-height:60px; overflow:hidden; }

        .btn-back {
            width:40px; height:40px; border-radius:50%;
            background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
            border:none; color:#fff; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            font-size:15px; flex-shrink:0; text-decoration:none; transition:background .2s;
        }
        .btn-back:hover { background:rgba(255,255,255,.28); }
        .slide-shop-top .name { font-size:14px; font-weight:600; }
        .slide-shop-top .sub  { font-size:11px; color:rgba(255,255,255,.6); margin-top:1px; }
        .top-avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.5); flex-shrink:0; }

        .slide-actions {
            position:absolute; right:10px; bottom:88px; z-index:20;
            display:flex; flex-direction:column; gap:18px; align-items:center;
        }
        .action-item  { display:flex; flex-direction:column; align-items:center; gap:4px; cursor:pointer; }
        .action-circle {
            width:46px; height:46px; border-radius:50%;
            background:rgba(255,255,255,.14); backdrop-filter:blur(6px);
            border:1px solid rgba(255,255,255,.18);
            display:flex; align-items:center; justify-content:center;
            font-size:18px; transition:background .2s, transform .15s;
        }
        .action-circle:hover { background:rgba(255,255,255,.28); transform:scale(1.08); }
        .action-circle.liked { background:rgba(239,68,68,.45); }
        .action-label { font-size:11px; color:rgba(255,255,255,.8); }
        .shop-disc {
            width:38px; height:38px; border-radius:8px;
            border:2px solid rgba(255,255,255,.7); object-fit:cover;
            animation:discSpin 5s linear infinite;
        }
        @keyframes discSpin { to { transform:rotate(360deg); } }

        #right-panel {
            display:flex; flex-direction:column; align-items:center;
            justify-content:space-between; height:min(820px,100dvh); padding:20px 0;
        }

        .nav-btn {
            position:fixed; right:14px; z-index:60;
            width:38px; height:38px; border-radius:50%;
            background:rgba(255,255,255,.13); backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.2);
            color:#fff; cursor:pointer; font-size:13px;
            display:flex; align-items:center; justify-content:center;
            transition:background .2s, opacity .3s;
        }
        #btn-up   { bottom:232px; }
        #btn-down { bottom:182px; }
        .nav-btn:hover    { background:rgba(255,255,255,.28); }
        .nav-btn:disabled { opacity:.18; cursor:default; }

        #vid-progress { position:fixed; bottom:0; left:0; height:3px; background:#2cb5e8; width:0%; z-index:70; transition:width .25s linear; }

        .slide-loader { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:5; background:rgba(0,0,0,.5); }
        .spin-ring { width:40px; height:40px; border-radius:50%; border:3px solid rgba(255,255,255,.15); border-top-color:#2cb5e8; animation:discSpin .8s linear infinite; }

        /* Liquid Glass Order Button */
        .glass-order-btn {
            position: relative;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.25);
            border-top: 1px solid rgba(255,255,255,0.45);
            border-left: 1px solid rgba(255,255,255,0.45);
            color: #fff;
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.3);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            overflow: hidden;
        }
        .glass-order-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.6), transparent);
            transform: skewX(-25deg);
            animation: liquidShine 4s infinite;
        }
        @keyframes liquidShine {
            0% { left: -100%; }
            20% { left: 200%; }
            100% { left: 200%; }
        }
        .glass-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(0,0,0,0.5);
            background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.1) 100%);
        }
        .glass-order-btn:active {
            transform: translateY(1px);
        }

        #index-pill {
            position:fixed; bottom:158px; right:6px; z-index:60;
            background:rgba(0,0,0,.55); backdrop-filter:blur(6px);
            border:1px solid rgba(255,255,255,.12); border-radius:20px;
            padding:3px 9px; font-size:11px; color:rgba(255,255,255,.75);
            display:none;
        }

        .vid-overlay {
            position:absolute; inset:0; z-index:15; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            touch-action: pan-y;
        }
        .play-flash {
            width:72px; height:72px; border-radius:50%;
            background:rgba(0,0,0,.55);
            display:flex; align-items:center; justify-content:center;
            font-size:28px; color:#fff;
            opacity:0; transform:scale(.7);
            transition:opacity .25s ease, transform .25s ease;
            pointer-events:none;
        }
        .play-flash.show { opacity:1; transform:scale(1); }
        .mute-btn {
            position:absolute; top:68px; right:12px; z-index:25;
            width:36px; height:36px; border-radius:50%;
            background:rgba(0,0,0,.5); backdrop-filter:blur(6px);
            border:1px solid rgba(255,255,255,.2);
            color:#fff; cursor:pointer; font-size:14px;
            display:flex; align-items:center; justify-content:center;
            transition:background .2s;
        }
        .mute-btn:hover { background:rgba(255,255,255,.2); }
        .vid-scrubber {
            position:absolute; bottom:0; left:0; right:0;
            height:5px; z-index:30; background:rgba(255,255,255,.18); cursor:pointer;
        }
        .vid-scrubber:hover { height:8px; }
        .vid-scrubber-fill { height:100%; background:#2cb5e8; width:0%; pointer-events:none; }

        /* Empty state */
        .empty-state {
            position:absolute; inset:0; display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:16px;
            color:rgba(255,255,255,.4);
        }
        .empty-state i { font-size:52px; }
        .empty-state p { font-size:15px; }
        .empty-state a {
            margin-top:8px; padding:10px 24px; border-radius:99px;
            background:rgba(44,181,232,.2); border:1px solid rgba(44,181,232,.4);
            color:#2cb5e8; font-size:14px; font-weight:600; text-decoration:none;
        }
    </style>
</head>
<body class="<?= $locationRequired ? 'location-locked' : '' ?>">

<?php if ($locationRequired): ?>
    <div id="locationOverlay" class="location-overlay">
        <div class="location-content">
            <div class="location-icon-pulsar">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <h1>Know your location</h1>
            <p>QOON needs your location to show the best reels and exclusive offers near you.</p>
            <button id="getLocationBtn" class="location-btn" onclick="requestUserLocation()">
                <span>Allow Access</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <div class="location-status" id="locationStatus"></div>
        </div>
    </div>
<?php endif; ?>

<div id="bg-blur"></div>

<div id="desktop-wrapper">

    <div id="stage"><!-- slides injected by JS --></div>

    <div id="right-panel">
        <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
            <button class="nav-btn" id="btn-up" onclick="navigate(-1)" title="Previous">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
            <div id="index-pill">…</div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;gap:22px;">
            <div class="action-item" onclick="toggleLike(this)">
                <div class="action-circle" id="global-like-circle"><i class="fa-regular fa-heart"></i></div>
                <span class="action-label">Like</span>
            </div>
            <div class="action-item" onclick="openCommentModal(REELS[currentIdx].id, REELS[currentIdx].shopName)">
                <div class="action-circle"><i class="fa-regular fa-comment-dots"></i></div>
                <span class="action-label">Comment</span>
            </div>
            <div class="action-item" id="global-share" onclick="shareCurrentReel()">
                <div class="action-circle"><i class="fa-solid fa-share-nodes"></i></div>
                <span class="action-label">Share</span>
            </div>
            <div class="action-item">
                <img id="global-disc" class="shop-disc" src="" alt="">
            </div>
        </div>
        <button class="nav-btn" id="btn-down" onclick="navigate(1)" title="Next">
            <i class="fa-solid fa-chevron-down"></i>
        </button>
    </div>

</div>

<div id="vid-progress"></div>

<script>
const REELS      = <?= $reelsJson ?>;
const START_ID   = <?= $startIdJs ?>;
const START_TYPE = <?= $sourceJs ?>;
const CAT_NAME   = <?= $catNameJs ?>;
const CAT_ID     = <?= json_encode($catRaw) ?>;

let currentIdx  = 0;
let isAnimating = false;

const stage    = document.getElementById('stage');
const pill     = document.getElementById('index-pill');
const progress = document.getElementById('vid-progress');
const btnUp    = document.getElementById('btn-up');
const btnDown  = document.getElementById('btn-down');

function init() {
    if (!REELS.length) {
        stage.innerHTML =
            '<div class="empty-state">' +
            '<i class="fa-solid fa-clapperboard"></i>' +
            '<p>No stories or reels in ' + esc(CAT_NAME) + ' yet</p>' +
            '<a href="category.php?cat=' + CAT_ID + '">\u2190 Back to ' + esc(CAT_NAME) + '</a>' +
            '</div>';
        return;
    }
    currentIdx = REELS.findIndex(r => r.id === START_ID && r.sourceType === START_TYPE);
    if (currentIdx < 0) currentIdx = 0;

    buildSlide(currentIdx - 1, 'above');
    buildSlide(currentIdx,     'active');
    buildSlide(currentIdx + 1, 'below');

    activateSlide(currentIdx);
    updateUI();
    preloadAhead(currentIdx);
}

function buildSlide(idx, cls) {
    if (idx < 0 || idx >= REELS.length) return null;
    const existId = 'slide-' + idx;
    if (document.getElementById(existId)) return document.getElementById(existId);

    const r        = REELS[idx];
    const isVid    = r.mediaType === 'video';
    const fallback = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(r.shopName) + '&background=2cb5e8&color=fff';

    const slide = document.createElement('div');
    slide.className = 'reel-slide ' + cls;
    slide.id = existId;
    slide.dataset.idx = idx;

    slide.innerHTML =
        '<div class="slide-loader" id="ldr-' + idx + '"><div class="spin-ring"></div></div>' +

        (isVid
            ? `<video id="med-${idx}" class="slide-media" playsinline loop muted preload="none" data-src="${esc(r.mediaUrl)}" poster="${r.foodPhoto ? esc(r.foodPhoto) : esc(r.shopLogo)}" onprogress="hideLdr(${idx})" oncanplay="hideLdr(${idx})" onerror="showMediaErr(${idx})"></video>` +
              `<div class="vid-overlay" onclick="toggleVideoPlay(${idx})">` +
                  '<div class="play-flash" id="flash-' + idx + '"><i class="fa-solid fa-play"></i></div>' +
              '</div>' +
              '<button class="mute-btn" id="mute-' + idx + '" onclick="toggleMute(' + idx + ')" title="Toggle mute"><i class="fa-solid fa-volume-high"></i></button>' +
              '<div class="vid-scrubber" id="scr-' + idx + '" onclick="seekVideo(event,' + idx + ')">' +
                  '<div class="vid-scrubber-fill" id="scr-fill-' + idx + '"></div>' +
              '</div>'
            : '<img id="med-' + idx + '" class="slide-media" data-src="' + esc(r.mediaUrl) + '" alt="' + esc(r.shopName) + '" onload="hideLdr(' + idx + ')" onerror="showMediaErr(' + idx + ')">') +

        // Top bar
        '<div class="slide-top">' +
            '<a href="category.php?cat=' + CAT_ID + '" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>' +
            '<img class="top-avatar" src="' + esc(r.shopLogo) + '" onerror="this.src=\'' + fallback + '\'" alt="">' +
            '<div class="slide-shop-top"><div class="name">' + esc(r.shopName) + '</div><div class="sub">' + esc(CAT_NAME) + ' · QOON</div></div>' +
        '</div>' +

        // Bottom
        '<div class="slide-bottom">' +
            '<div class="slide-shop-name">' + esc(r.shopName) + '</div>' +
            (r.caption ? '<div class="slide-caption">' + esc(r.caption) + '</div>' : '') +
            (r.productId > 0 && r.shopId > 0 ? `<div style="margin-top:18px;"><button class="glass-order-btn" onclick="location.href='shop.php?id=${r.shopId}&product=${r.productId}'"><i class="fa-solid fa-cart-shopping"></i>Order Now</button></div>` : '') +
        '</div>';

    stage.appendChild(slide);
    return slide;
}

function activateSlide(idx) {
    if (idx < 0 || idx >= REELS.length) return;
    const med = document.getElementById('med-' + idx);
    if (!med) return;
    if (med.tagName === 'VIDEO') {
        med.preload = 'auto';
        med.muted   = true;
        if (!med.getAttribute('src')) { med.setAttribute('src', med.dataset.src); med.load(); }
        med.play().then(() => { med.muted = false; syncMuteIcon(idx, false); }).catch(() => syncMuteIcon(idx, true));
        med.addEventListener('timeupdate', () => {
            if (!med.duration) return;
            const fill = document.getElementById('scr-fill-' + idx);
            if (fill) fill.style.width = ((med.currentTime / med.duration) * 100).toFixed(2) + '%';
        }, { passive: true });
        med.addEventListener('progress', () => hideLdr(idx), { once: true, passive: true });
    } else {
        if (!med.getAttribute('src')) med.setAttribute('src', med.dataset.src);
    }
}

function deactivateSlide(idx) {
    const med = document.getElementById('med-' + idx);
    if (med && med.tagName === 'VIDEO') { med.pause(); med.muted = true; }
    progress.style.width = '0%';
}

function preloadAhead(idx) {
    if (idx + 1 < REELS.length) {
        if (!document.getElementById('slide-' + (idx + 1))) buildSlide(idx + 1, 'below');
        const medNext = document.getElementById('med-' + (idx + 1));
        if (medNext && !medNext.getAttribute('src')) {
            medNext.setAttribute('src', medNext.dataset.src);
            if (medNext.tagName === 'VIDEO') { medNext.preload = 'auto'; medNext.load(); }
        }
    }
    [idx + 2, idx - 1].forEach(i => {
        if (i < 0 || i >= REELS.length) return;
        if (!document.getElementById('slide-' + i)) buildSlide(i, i < idx ? 'above' : 'below');
        const med = document.getElementById('med-' + i);
        if (med && !med.getAttribute('src')) {
            med.setAttribute('src', med.dataset.src);
            if (med.tagName === 'VIDEO') med.preload = 'metadata';
        }
    });
}

function navigate(dir) {
    if (isAnimating) return;
    const next = currentIdx + dir;
    if (next < 0 || next >= REELS.length) return;
    isAnimating = true;

    const curEl  = document.getElementById('slide-' + currentIdx);
    const nextEl = buildSlide(next, dir > 0 ? 'below' : 'above');
    if (!nextEl || !curEl) { isAnimating = false; return; }

    const nextMed = document.getElementById('med-' + next);
    if (nextMed && !nextMed.getAttribute('src')) nextMed.setAttribute('src', nextMed.dataset.src);

    deactivateSlide(currentIdx);
    void nextEl.offsetWidth;
    curEl.classList.remove('active');
    curEl.classList.add(dir > 0 ? 'above' : 'below');
    nextEl.classList.remove('above', 'below', 'hidden');
    nextEl.classList.add('active');
    activateSlide(next);
    currentIdx = next;
    cleanup(); preloadAhead(currentIdx); updateUI();
    setTimeout(() => { isAnimating = false; }, 350);
}

function cleanup() {
    document.querySelectorAll('.reel-slide').forEach(s => {
        const i = parseInt(s.dataset.idx);
        if (Math.abs(i - currentIdx) > 2) {
            const m = s.querySelector('.slide-media');
            if (m && m.tagName === 'VIDEO') { m.pause(); m.removeAttribute('src'); m.load(); }
            s.remove();
        }
    });
}

function updateUI() {
    pill.textContent = (currentIdx + 1) + ' / ' + REELS.length;
    btnUp.disabled   = currentIdx === 0;
    btnDown.disabled = currentIdx === REELS.length - 1;
    const r = REELS[currentIdx];
    const disc = document.getElementById('global-disc');
    if (disc) {
        const fallback = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(r.shopName) + '&background=2cb5e8&color=fff';
        disc.src = r.shopLogo || fallback;
        disc.onerror = () => { disc.src = fallback; };
    }
    const lc = document.getElementById('global-like-circle');
    if (lc) {
        lc.classList.remove('liked');
        lc.innerHTML = '<i class="fa-regular fa-heart"></i>';
        const lbl = lc.closest('.action-item')?.querySelector('.action-label');
        if (lbl) lbl.textContent = 'Like';
    }
    const bg = document.getElementById('bg-blur');
    if (bg) bg.style.backgroundImage = 'url(' + r.mediaUrl + ')';
    history.replaceState(null, '', 'category_reels.php?cat=' + CAT_ID + '&id=' + r.id + '&type=' + r.sourceType);
}

function hideLdr(i)  { const l = document.getElementById('ldr-' + i); if (l) l.style.display = 'none'; }
function showMediaErr(i) {
    const l = document.getElementById('ldr-' + i);
    if (l) l.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;gap:10px;opacity:.5;"><i class="fa-solid fa-video-slash" style="font-size:36px;color:#f87171"></i><span style="font-size:12px;color:rgba(255,255,255,.6)">Media unavailable</span></div>';
}
function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

function toggleVideoPlay(idx) {
    const med = document.getElementById('med-' + idx), flash = document.getElementById('flash-' + idx);
    if (!med || med.tagName !== 'VIDEO') return;
    if (med.paused) { med.play(); if (flash) { flash.innerHTML='<i class="fa-solid fa-play"></i>'; flashAnim(flash); } }
    else { med.pause(); if (flash) { flash.innerHTML='<i class="fa-solid fa-pause"></i>'; flashAnim(flash); } }
}
function flashAnim(el) { el.classList.add('show'); clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove('show'), 600); }
function toggleMute(idx) { const med = document.getElementById('med-'+idx); if (!med||med.tagName!=='VIDEO') return; med.muted=!med.muted; syncMuteIcon(idx,med.muted); }
function syncMuteIcon(idx, m) { const b=document.getElementById('mute-'+idx); if(b) b.innerHTML=m?'<i class="fa-solid fa-volume-xmark"></i>':'<i class="fa-solid fa-volume-high"></i>'; }
function seekVideo(e,idx) { const med=document.getElementById('med-'+idx),bar=document.getElementById('scr-'+idx); if(!med||!bar||!med.duration) return; const r=bar.getBoundingClientRect(); med.currentTime=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width))*med.duration; }
function toggleLike(el) {
    const uid = (document.cookie.match('(^|;) ?qoon_user_id=([^;]*)(;|$)')||[])[2];
    const isLoggedIn = (uid && uid !== '0' && uid !== '');
    if (!isLoggedIn) {
        if (typeof openSignup === 'function') openSignup();
        return;
    }
    const c = el.querySelector('.action-circle'), lbl = el.querySelector('.action-label');
    const on = c.classList.toggle('liked');
    c.innerHTML = on ? '<i class="fa-solid fa-heart" style="color:#f87171"></i>' : '<i class="fa-regular fa-heart"></i>';
    if (lbl) lbl.textContent = on ? 'Liked!' : 'Like';
}
function shareCurrentReel() { const r=REELS[currentIdx]; const url=location.origin+'/category_reels.php?cat='+CAT_ID+'&id='+r.id+'&type='+r.sourceType; navigator.share?navigator.share({title:'QOON · '+CAT_NAME,url}).catch(()=>{}):navigator.clipboard.writeText(url).then(()=>alert('Link copied!')); }

// ── Swipe (with tap vs swipe detection) ──
let touchStartY = 0, touchStartX = 0, touchStartTime = 0;
window.addEventListener('touchstart', e => {
    touchStartY = e.touches[0].clientY;
    touchStartX = e.touches[0].clientX;
    touchStartTime = Date.now();
}, { passive: true });
window.addEventListener('touchend', e => {
    const dy = touchStartY - e.changedTouches[0].clientY;
    const dx = touchStartX - e.changedTouches[0].clientX;
    const elapsed = Date.now() - touchStartTime;
    const dist = Math.sqrt(dy * dy + dx * dx);
    if (Math.abs(dy) > 60 && Math.abs(dy) > Math.abs(dx) * 1.2) {
        navigate(dy > 0 ? 1 : -1);
    } else if (dist < 15 && elapsed < 300) {
        const target = e.target;
        if (target.closest('.vid-overlay')) {
            const idx = parseInt(target.closest('.reel-slide')?.dataset?.idx);
            if (!isNaN(idx)) toggleVideoPlay(idx);
        }
    }
}, { passive: true });

// Keyboard
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') navigate(1);
    if (e.key === 'ArrowUp'   || e.key === 'ArrowLeft')  navigate(-1);
});

// Mouse wheel
let wheelAccum = 0, wheelTimer = null, wheelLocked = false, wheelUnlockTimer = null;
window.addEventListener('wheel', e => {
    e.preventDefault();
    if (wheelLocked) { 
        clearTimeout(wheelUnlockTimer);
        wheelUnlockTimer = setTimeout(() => { wheelLocked = false; wheelAccum = 0; }, 100);
        return; 
    }
    wheelAccum += e.deltaY;
    clearTimeout(wheelTimer);
    if (Math.abs(wheelAccum) >= 60) {
        const dir = wheelAccum > 0 ? 1 : -1; wheelAccum = 0;
        if (!isAnimating) { 
            navigate(dir); 
            wheelLocked = true; 
            clearTimeout(wheelUnlockTimer);
            wheelUnlockTimer = setTimeout(() => { wheelLocked = false; wheelAccum = 0; }, 600); 
        }
    } else { wheelTimer = setTimeout(() => { wheelAccum = 0; }, 250); }
}, { passive: false });

init();
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

    
    <script>
         catch (e) { console.error("Firebase Init Error:", e); }

        
    </script>

    <!-- MODALS -->
    <?php include 'includes/modals/auth.php'; ?>
    <?php include 'includes/modals/comments.php'; ?>
    <?php include 'includes/modals/product.php'; ?>

</body>
</html>



