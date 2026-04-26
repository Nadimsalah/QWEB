<?php
define('FROM_UI', true); // Suppress global error_db.php on conn timeout
require_once 'conn.php';

$startId = intval($_GET['id'] ?? 0);
$sourceType = $_GET['type'] ?? 'story';
$DomainNamee = 'https://qoon.app/dash/';

// ── Fetch ALL reels directly in PHP, embed as JSON ──
$reelsData = [];
try {
    if ($con) {
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

        $userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float) $_COOKIE['qoon_lat'] : null;
        $userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float) $_COOKIE['qoon_lon'] : null;

        if ($userLat !== null && $userLon !== null) {
            // Apply Haversine filter to block content from totally irrelevant countries (> 500km)
            $sql = "SELECT m.*, (6372.797 * acos(cos(radians($userLat)) * cos(radians(m.lat)) * cos(radians(m.lon) - radians($userLon)) + sin(radians($userLat)) * sin(radians(m.lat)))) AS distance
                    FROM ($innerSql) AS m 
                    HAVING distance <= 500 OR distance IS NULL 
                    ORDER BY distance ASC, id DESC LIMIT 80";
        } else {
            $sql = "SELECT * FROM ($innerSql) AS m ORDER BY id DESC LIMIT 80";
        }

        $res = $con->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $reelsData[] = $row;
            }
        }

        // Fallback: if geo-filtered results are too few, load all reels globally
        if (count($reelsData) < 5) {
            $reelsData = [];
            $sqlAll = "SELECT * FROM ($innerSql) AS m ORDER BY id DESC LIMIT 80";
            $resAll = $con->query($sqlAll);
            if ($resAll) {
                while ($row = $resAll->fetch_assoc()) {
                    $reelsData[] = $row;
                }
            }
        }

        // Process raw rows into final reel objects
        $rawRows = $reelsData;
        $reelsData = [];
        foreach ($rawRows as $row) {
            // ── Build URL directly from DB value (no BunnyCDN) ──
            $rawMedia = trim($row['rawMedia'] ?? '');
            $storyType = strtoupper(trim($row['storyType'] ?? ''));
            $mediaUrl = '';
            $mediaType = 'image';

            if (!empty($rawMedia) && $rawMedia !== '0' && $rawMedia !== '-') {
                // Rewrite old jibler.app domain to current qoon.app
                $rawMedia = str_replace('jibler.app', 'qoon.app', $rawMedia);

                // Build full URL: if already a full URL use as-is, else prefix domain
                $mediaUrl = (strpos($rawMedia, 'http') !== false)
                    ? $rawMedia
                    : $DomainNamee . 'photo/' . $rawMedia;

                // Detect type from DB flag or file extension
                $ext = strtolower(pathinfo($rawMedia, PATHINFO_EXTENSION));
                if ($storyType === 'VIDEO' || in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv'])) {
                    $mediaType = 'video';
                } else {
                    $mediaType = 'image';
                }
            }

            if (!$mediaUrl)
                continue;

            $logo = trim($row['shopLogo'] ?? '');
            if ($logo && strpos($logo, 'http') === false) {
                $logo = $DomainNamee . 'photo/' . $logo;
            }

            $foodName = $row['foodName'] ?? 'Product';
            $foodPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodOfferPrice']) : floatval($row['foodPrice'] ?? 0);
            $oldPrice = floatval($row['foodOfferPrice'] ?? 0) > 0 ? floatval($row['foodPrice'] ?? 0) : null;

            $foodPhoto = trim($row['foodPhoto'] ?? '');
            if ($foodPhoto && strpos($foodPhoto, 'http') === false) {
                $foodPhoto = $DomainNamee . 'photo/' . $foodPhoto;
            }

            $reelsData[] = [
                'id' => (int) $row['id'],
                'sourceType' => $row['sourceType'],
                'mediaUrl' => $mediaUrl,
                'mediaType' => $mediaType,
                'caption' => htmlspecialchars($row['caption'] ?? ''),
                'shopName' => htmlspecialchars($row['shopName'] ?? 'Shop'),
                'shopLogo' => $logo,
                'shopId' => (int) ($row['shopId'] ?? 0),
                'productId' => (int) ($row['productId'] ?? 0),
                'foodName' => htmlspecialchars($foodName),
                'foodPrice' => $foodPrice,
                'oldPrice' => $oldPrice,
                'foodPhoto' => $foodPhoto,
            ];
        }
    }
} catch (Throwable $e) {
}

if (isset($con) && $con)
    mysqli_close($con);

// Graceful offline fallback
if (empty($reelsData)) {
    $reelsData = [
        [
            'id' => 1,
            'sourceType' => 'post',
            'mediaUrl' => 'https://qoon.app/dash/photo/default_reel1.mp4',
            'mediaType' => 'video',
            'caption' => 'Discover QOON S-Commerce — Swipe to explore endless possibilities. (Offline Mode)',
            'shopName' => 'QOON Highlights',
            'shopLogo' => 'https://ui-avatars.com/api/?name=Q&background=2cb5e8&color=fff',
            'productId' => 0
        ],
        [
            'id' => 2,
            'sourceType' => 'post',
            'mediaUrl' => 'https://ui-avatars.com/api/?name=Offline&background=000&color=fff&size=800',
            'mediaType' => 'image',
            'caption' => 'Network connection seems unstable. We are loading fallback content.',
            'shopName' => 'System Alert',
            'shopLogo' => 'https://ui-avatars.com/api/?name=Sys&background=eab308&color=fff',
            'productId' => 0
        ]
    ];
}

$reelsJson = json_encode($reelsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
$startIdJs = (int) $startId;

$sourceJs = json_encode($sourceType);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stories & Reels · QOON</title>
    <meta name="theme-color" content="#000000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            width: 100%;
            height: 100dvh;
            overflow: hidden;
            background: #000;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }

        /* ── Ambient blurred background ── */
        #bg-blur {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: #111;
            filter: blur(40px) brightness(0.35) saturate(1.4);
            background-size: cover;
            background-position: center;
            transform: scale(1.1);
            transition: background-image 0.5s ease;
        }

        /* ── Desktop wrapper ── */
        #desktop-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 28px;
        }

        /* ── Stage: the phone-ratio card ── */
        #stage {
            position: relative;
            height: 100dvh;
            max-height: 820px;
            aspect-ratio: 9 / 16;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.8);
            flex-shrink: 0;
        }

        /* On small screens go full-width */
        @media (max-width: 600px) {
            #stage {
                border-radius: 0;
                max-height: 100dvh;
                width: 100%;
                aspect-ratio: unset;
            }

            #desktop-wrapper {
                gap: 0;
                display: block;
            }

            #right-panel {
                position: absolute;
                right: 8px;
                bottom: 40px;
                height: auto;
                padding: 0;
                z-index: 1000;
                justify-content: flex-end;
                pointer-events: none;
            }

            #right-panel>* {
                pointer-events: auto;
            }

            /* Hide the desktop up/down arrows and pill on mobile */
            .nav-btn {
                display: none !important;
            }

            #index-pill {
                display: none !important;
            }

            /* Compact actions for mobile */
            .action-circle {
                width: 44px;
                height: 44px;
                font-size: 18px;
                background: rgba(0, 0, 0, 0.4);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .action-label {
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
            }

            .shop-disc {
                width: 38px;
                height: 38px;
            }

            /* Adjust padding on the text so it doesn't collide with the buttons */
            .slide-bottom {
                padding: 80px 65px 30px 16px;
            }
        }

        .reel-slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            transition: transform .42s cubic-bezier(.4, 0, .2, 1), opacity .42s cubic-bezier(.4, 0, .2, 1);
            will-change: transform, opacity;
        }

        .reel-slide.above {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        .reel-slide.active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .reel-slide.below {
            transform: translateY(100%);
            opacity: 0;
            pointer-events: none;
        }

        .reel-slide.hidden {
            transform: translateY(200%);
            opacity: 0;
            pointer-events: none;
        }

        .slide-media {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #0a0a0a;
        }

        /* Gradients */
        .slide-top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, .5), transparent);
            z-index: 20;
            display: flex;
            align-items: flex-start;
            padding: 20px 16px 0;
            gap: 10px;
            pointer-events: none;
        }

        .slide-top>* {
            pointer-events: auto;
        }

        .slide-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 60px 16px 24px;
            background: linear-gradient(transparent, rgba(0, 0, 0, .6));
            z-index: 20;
            pointer-events: none;
        }

        .slide-bottom>* {
            pointer-events: auto;
        }

        .slide-shop-name {
            font-size: 15px;
            font-weight: 700;
            text-shadow: 0 1px 6px rgba(0, 0, 0, .9);
        }

        .slide-caption {
            font-size: 13px;
            color: rgba(255, 255, 255, .82);
            margin-top: 6px;
            line-height: 1.5;
            max-height: 56px;
            overflow: hidden;
        }

        /* Back btn */
        .btn-back {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            backdrop-filter: blur(8px);
            border: none;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            text-decoration: none;
            transition: background .2s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, .3);
        }

        .slide-shop-top .name {
            font-size: 14px;
            font-weight: 600;
        }

        .slide-shop-top .sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .6);
            margin-top: 1px;
        }

        .top-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, .5);
            flex-shrink: 0;
        }

        /* Right actions panel (desktop) */
        #right-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            height: min(820px, 100dvh);
            padding: 20px 0;
        }

        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .action-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: background .2s, transform .15s;
        }

        .action-circle:hover {
            background: rgba(255, 255, 255, .26);
            transform: scale(1.08);
        }

        .action-circle.liked {
            background: rgba(239, 68, 68, .45);
        }

        .action-label {
            font-size: 12px;
            color: rgba(255, 255, 255, .85);
            font-weight: 500;
        }

        .shop-disc {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, .7);
            object-fit: cover;
            animation: discSpin 5s linear infinite;
        }

        @keyframes discSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Nav arrows — now live in right panel as a vertical stack */
        #nav-row {
            display: none;
        }

        /* hidden — no longer used */
        .nav-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .14);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #fff;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s, opacity .3s;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, .28);
        }

        .nav-btn:disabled {
            opacity: .18;
            cursor: default;
        }

        /* On mobile keep nav on right */
        @media (max-width: 600px) {
            #nav-row {
                display: none;
            }

            .nav-btn-mobile {
                position: fixed;
                right: 14px;
                z-index: 60;
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .13);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, .2);
                color: #fff;
                cursor: pointer;
                font-size: 13px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Progress */
        #vid-progress {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #2cb5e8;
            width: 0%;
            z-index: 70;
            transition: width .25s linear;
        }

        /* Loader */
        .slide-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            background: rgba(0, 0, 0, .45);
        }

        /* Liquid Glass Order Button */
        .glass-order-btn {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.45);
            border-left: 1px solid rgba(255, 255, 255, 0.45);
            color: #fff;
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
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
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.6), transparent);
            transform: skewX(-25deg);
            animation: liquidShine 4s infinite;
        }

        @keyframes liquidShine {
            0% {
                left: -100%;
            }

            20% {
                left: 200%;
            }

            100% {
                left: 200%;
            }
        }

        .glass-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.5);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
        }

        .glass-order-btn:active {
            transform: translateY(1px);
        }

        .spin-ring {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, .15);
            border-top-color: #2cb5e8;
            animation: discSpin .8s linear infinite;
        }

        /* Pill counter — hidden */
        #index-pill {
            display: none;
        }

        /* ── Video player controls ── */
        .vid-overlay {
            position: absolute;
            inset: 0;
            z-index: 15;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: pan-y;
        }

        .play-flash {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            opacity: 0;
            transform: scale(0.7);
            transition: opacity 0.25s ease, transform 0.25s ease;
            pointer-events: none;
        }

        .play-flash.show {
            opacity: 1;
            transform: scale(1);
        }

        .mute-btn {
            position: absolute;
            top: 68px;
            right: 12px;
            z-index: 25;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .mute-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Seekable scrubber */
        .vid-scrubber {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            z-index: 30;
            background: rgba(255, 255, 255, 0.18);
            cursor: pointer;
        }

        .vid-scrubber:hover {
            height: 8px;
        }

        .vid-scrubber-fill {
            height: 100%;
            background: #2cb5e8;
            width: 0%;
            pointer-events: none;
        }

        .reel-slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            transition: transform .42s cubic-bezier(.4, 0, .2, 1), opacity .42s cubic-bezier(.4, 0, .2, 1);
            will-change: transform, opacity;
        }

        .reel-slide.above {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        .reel-slide.active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .reel-slide.below {
            transform: translateY(100%);
            opacity: 0;
            pointer-events: none;
        }

        .reel-slide.hidden {
            transform: translateY(200%);
            opacity: 0;
            pointer-events: none;
        }

        .slide-media {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #0a0a0a;
        }

        /* Gradients */
        .slide-top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 160px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, .75), transparent);
            z-index: 20;
            display: flex;
            align-items: flex-start;
            padding: 20px 16px 0;
            gap: 10px;
            pointer-events: none;
        }

        .slide-top>* {
            pointer-events: auto;
        }

        .slide-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 80px 72px 36px 16px;
            background: linear-gradient(transparent, rgba(0, 0, 0, .88));
            z-index: 20;
            pointer-events: none;
        }

        .slide-bottom>* {
            pointer-events: auto;
        }

        .slide-shop-name {
            font-size: 15px;
            font-weight: 700;
            text-shadow: 0 1px 6px rgba(0, 0, 0, .9);
        }

        .slide-caption {
            font-size: 13px;
            color: rgba(255, 255, 255, .82);
            margin-top: 6px;
            line-height: 1.5;
            max-height: 60px;
            overflow: hidden;
        }

        /* Back btn */
        .btn-back {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            backdrop-filter: blur(8px);
            border: none;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
            text-decoration: none;
            transition: background .2s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, .28);
        }

        .slide-shop-top .name {
            font-size: 14px;
            font-weight: 600;
        }

        .slide-shop-top .sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .6);
            margin-top: 1px;
        }

        /* Actions */
        .slide-actions {
            position: absolute;
            right: 10px;
            bottom: 88px;
            z-index: 20;
            display: flex;
            flex-direction: column;
            gap: 18px;
            align-items: center;
        }

        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        .action-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .14);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background .2s, transform .15s;
        }

        .action-circle:hover {
            background: rgba(255, 255, 255, .28);
            transform: scale(1.08);
        }

        .action-circle.liked {
            background: rgba(239, 68, 68, .45);
        }

        .action-label {
            font-size: 11px;
            color: rgba(255, 255, 255, .8);
        }

        .shop-disc {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, .7);
            object-fit: cover;
            animation: discSpin 5s linear infinite;
        }

        @keyframes discSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Nav buttons — stacked on right side */
        .nav-btn {
            position: fixed;
            right: 14px;
            z-index: 60;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .13);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .2);
            color: #fff;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s, opacity .3s;
        }

        #btn-up {
            bottom: 232px;
        }

        #btn-down {
            bottom: 182px;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, .28);
        }

        .nav-btn:disabled {
            opacity: .18;
            cursor: default;
        }

        /* Progress */
        #vid-progress {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #2cb5e8;
            width: 0%;
            z-index: 70;
            transition: width .25s linear;
        }

        /* Loader */
        .slide-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            background: rgba(0, 0, 0, .5);
        }

        .spin-ring {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, .15);
            border-top-color: #2cb5e8;
            animation: discSpin .8s linear infinite;
        }

        /* Pill */
        #index-pill {
            position: fixed;
            bottom: 158px;
            right: 6px;
            z-index: 60;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 11px;
            color: rgba(255, 255, 255, .75);
        }
    </style>
</head>

<body>

    <!-- Ambient blurred background -->
    <div id="bg-blur"></div>

    <div id="desktop-wrapper">

        <!-- The centered portrait card -->
        <div id="stage">
            <!-- slides injected by JS -->
        </div>

        <!-- Right-hand actions panel -->
        <div id="right-panel">

            <!-- TOP: up button + counter -->
            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                <button class="nav-btn" id="btn-up" onclick="navigate(-1)" title="Previous">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
                <div id="index-pill">…</div>
            </div>

            <!-- MIDDLE: Like / Comment / Share / disc -->
            <div style="display:flex; flex-direction:column; align-items:center; gap:22px;">
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

            <!-- BOTTOM: down button -->
            <button class="nav-btn" id="btn-down" onclick="navigate(1)" title="Next">
                <i class="fa-solid fa-chevron-down"></i>
            </button>

        </div>

    </div><!-- /desktop-wrapper -->

    <div id="vid-progress"></div>

    <script>
        // ── Data embedded directly from PHP (no AJAX needed) ──
        const REELS = <?= $reelsJson ?>;
        const START_ID = <?= $startIdJs ?>;
        const START_TYPE = <?= $sourceJs ?>;

        let currentIdx = 0;
        let isAnimating = false;

        const stage = document.getElementById('stage');
        const pill = document.getElementById('index-pill');
        const progress = document.getElementById('vid-progress');
        const btnUp = document.getElementById('btn-up');
        const btnDown = document.getElementById('btn-down');

        // ── Bootstrap ──
        function init() {
            if (!REELS.length) {
                stage.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;flex-direction:column;gap:12px;opacity:.4;"><i class="fa-solid fa-photo-film" style="font-size:48px"></i><p style="font-size:14px">No content available</p></div>';
                return;
            }

            currentIdx = REELS.findIndex(r => r.id === START_ID && r.sourceType === START_TYPE);
            if (currentIdx < 0) currentIdx = 0;

            buildSlide(currentIdx - 1, 'above');
            buildSlide(currentIdx, 'active');
            buildSlide(currentIdx + 1, 'below');

            activateSlide(currentIdx); // ← load and play the first slide immediately
            updateUI();
            preloadAhead(currentIdx);
        }

        // ── Build slide DOM ──
        function buildSlide(idx, cls) {
            if (idx < 0 || idx >= REELS.length) return null;
            const existId = 'slide-' + idx;
            if (document.getElementById(existId)) return document.getElementById(existId);

            const r = REELS[idx];
            const isVid = r.mediaType === 'video';
            const fallback = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(r.shopName) + '&background=2cb5e8&color=fff';

            const slide = document.createElement('div');
            slide.className = 'reel-slide ' + cls;
            slide.id = existId;
            slide.dataset.idx = idx;

            slide.innerHTML =
                // Spinner
                '<div class="slide-loader" id="ldr-' + idx + '"><div class="spin-ring"></div></div>' +

                // Media — video shows progressively as it buffers, poster gives instant visual
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
                '<a href="javascript:void(0)" onclick="history.length>1?history.back():location.href=\'index.php\'" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>' +
                '<img class="top-avatar" src="' + esc(r.shopLogo) + '" onerror="this.src=\'' + fallback + '\'" alt="">' +
                '<div class="slide-shop-top"><div class="name">' + esc(r.shopName) + '</div><div class="sub">Stories & Reels · QOON</div></div>' +
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

        // ── Activate: load src, start buffering, play immediately ──
        function activateSlide(idx) {
            if (idx < 0 || idx >= REELS.length) return;
            const med = document.getElementById('med-' + idx);
            if (!med) return;

            if (med.tagName === 'VIDEO') {
                // ★ Set preload BEFORE src so browser starts buffering immediately on src set
                med.preload = 'auto';
                med.muted = true; // always start muted (browsers allow muted autoplay)

                if (!med.getAttribute('src')) {
                    med.setAttribute('src', med.dataset.src);
                    med.load(); // explicit load() call forces buffering to begin
                }

                // Play muted first (guaranteed to work), then try to unmute
                med.play().then(() => {
                    // Play succeeded — now try unmuting
                    med.muted = false;
                    syncMuteIcon(idx, false);
                }).catch(() => {
                    // Still muted, user can tap speaker icon
                    syncMuteIcon(idx, true);
                });

                // Update scrubber as video plays
                med.addEventListener('timeupdate', () => {
                    if (!med.duration) return;
                    const fill = document.getElementById('scr-fill-' + idx);
                    if (fill) fill.style.width = ((med.currentTime / med.duration) * 100).toFixed(2) + '%';
                }, { passive: true });

                // Hide spinner on first data or canplay
                med.addEventListener('progress', () => hideLdr(idx), { once: true, passive: true });

            } else {
                // Image: set src if not yet set
                if (!med.getAttribute('src')) {
                    med.setAttribute('src', med.dataset.src);
                }
            }
        }

        function deactivateSlide(idx) {
            const med = document.getElementById('med-' + idx);
            if (med && med.tagName === 'VIDEO') { med.pause(); med.muted = true; }
            progress.style.width = '0%';
        }

        // ── Preload adjacent ──
        function preloadAhead(idx) {
            // 1. Aggressively preload the immediate next video (preload="auto")
            if (idx + 1 < REELS.length) {
                if (!document.getElementById('slide-' + (idx + 1))) buildSlide(idx + 1, 'below');
                const medNext = document.getElementById('med-' + (idx + 1));
                if (medNext && !medNext.getAttribute('src')) {
                    medNext.setAttribute('src', medNext.dataset.src);
                    if (medNext.tagName === 'VIDEO') { medNext.preload = 'auto'; medNext.load(); }
                }
            }

            // 2. Only load metadata for further videos to save bandwidth
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

        // ── Navigate ──
        function navigate(dir) {
            if (isAnimating) { console.log('[reel] blocked: isAnimating'); return; }
            const next = currentIdx + dir;
            console.log('[reel] navigate dir=' + dir + ' next=' + next + ' total=' + REELS.length);
            if (next < 0 || next >= REELS.length) { console.log('[reel] boundary reached'); return; }
            isAnimating = true;

            const curEl = document.getElementById('slide-' + currentIdx);
            const nextEl = buildSlide(next, dir > 0 ? 'below' : 'above');

            // Safety: if nextEl is null, unlock and bail to avoid permanent lock
            if (!nextEl || !curEl) {
                console.warn('[reel] slide element missing, resetting');
                isAnimating = false;
                return;
            }

            // Ensure next media is loaded
            const nextMed = document.getElementById('med-' + next);
            if (nextMed && !nextMed.getAttribute('src')) nextMed.setAttribute('src', nextMed.dataset.src);

            deactivateSlide(currentIdx);
            void nextEl.offsetWidth; // force reflow

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
                if (Math.abs(i - currentIdx) > 2) { // Reduced from 3 to 2 to free RAM faster
                    const m = s.querySelector('.slide-media');
                    if (m && m.tagName === 'VIDEO') {
                        m.pause();
                        m.removeAttribute('src');
                        m.load(); // Forces browser to drop the video buffer from memory
                    }
                    s.remove();
                }
            });
        }

        function updateUI() {
            pill.textContent = (currentIdx + 1) + ' / ' + REELS.length;
            btnUp.disabled = currentIdx === 0;
            btnDown.disabled = currentIdx === REELS.length - 1;

            const r = REELS[currentIdx];

            // Sync right-panel disc logo
            const disc = document.getElementById('global-disc');
            if (disc) {
                const fallback = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(r.shopName) + '&background=2cb5e8&color=fff';
                disc.src = r.shopLogo || fallback;
                disc.onerror = () => { disc.src = fallback; };
            }

            // Reset like state on navigation
            const likeCircle = document.getElementById('global-like-circle');
            if (likeCircle) {
                likeCircle.classList.remove('liked');
                likeCircle.innerHTML = '<i class="fa-regular fa-heart"></i>';
                const lbl = likeCircle.closest('.action-item')?.querySelector('.action-label');
                if (lbl) lbl.textContent = 'Like';
            }

            // Update ambient background
            const bg = document.getElementById('bg-blur');
            if (bg) bg.style.backgroundImage = 'url(' + r.mediaUrl + ')';

            history.replaceState(null, '', 'reel.php?id=' + r.id + '&type=' + r.sourceType + '&media=' + r.mediaType);
        }

        // ── Helpers ──
        function hideLdr(i) { const l = document.getElementById('ldr-' + i); if (l) l.style.display = 'none'; }
        function showMediaErr(i) {
            const l = document.getElementById('ldr-' + i);
            if (l) {
                l.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;gap:10px;opacity:.5;">' +
                    '<i class="fa-solid fa-video-slash" style="font-size:36px;color:#f87171"></i>' +
                    '<span style="font-size:12px;color:rgba(255,255,255,.6)">Media unavailable</span>' +
                    '</div>';
            }
        }
        function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }

        // ─ Play/Pause tap ─
        function toggleVideoPlay(idx) {
            const med = document.getElementById('med-' + idx);
            const flash = document.getElementById('flash-' + idx);
            if (!med || med.tagName !== 'VIDEO') return;

            if (med.paused) {
                med.play();
                if (flash) { flash.innerHTML = '<i class="fa-solid fa-play"></i>'; flashAnim(flash); }
            } else {
                med.pause();
                if (flash) { flash.innerHTML = '<i class="fa-solid fa-pause"></i>'; flashAnim(flash); }
            }
        }

        function flashAnim(el) {
            el.classList.add('show');
            clearTimeout(el._t);
            el._t = setTimeout(() => el.classList.remove('show'), 600);
        }

        // ─ Mute toggle ─
        function toggleMute(idx) {
            const med = document.getElementById('med-' + idx);
            if (!med || med.tagName !== 'VIDEO') return;
            med.muted = !med.muted;
            syncMuteIcon(idx, med.muted);
        }

        function syncMuteIcon(idx, isMuted) {
            const btn = document.getElementById('mute-' + idx);
            if (btn) btn.innerHTML = isMuted
                ? '<i class="fa-solid fa-volume-xmark"></i>'
                : '<i class="fa-solid fa-volume-high"></i>';
        }

        // ─ Seek on scrubber click ─
        function seekVideo(e, idx) {
            const med = document.getElementById('med-' + idx);
            const bar = document.getElementById('scr-' + idx);
            if (!med || !bar || !med.duration) return;
            const rect = bar.getBoundingClientRect();
            const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            med.currentTime = pct * med.duration;
        }
        function toggleLike(el) {
            const uid = (document.cookie.match('(^|;) ?qoon_user_id=([^;]*)(;|$)') || [])[2];
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
        function shareReel(id, type) {
            const url = location.origin + '/' + 'reel.php?id=' + id + '&type=' + type;
            navigator.share ? navigator.share({ title: 'QOON Reels', url }).catch(() => { }) : navigator.clipboard.writeText(url).then(() => alert('Link copied!'));
        }
        function shareCurrentReel() {
            const r = REELS[currentIdx];
            shareReel(r.id, r.sourceType);
        }

        // ── Swipe (with tap vs swipe detection) ──
        let touchStartY = 0;
        let touchStartX = 0;
        let touchStartTime = 0;

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

            // Only navigate if it's a clear vertical swipe (not a tap)
            if (Math.abs(dy) > 60 && Math.abs(dy) > Math.abs(dx) * 1.2) {
                navigate(dy > 0 ? 1 : -1);
            }
            // If finger barely moved and was quick, treat as tap (play/pause)
            else if (dist < 15 && elapsed < 300) {
                // Check if the tap was on the video area (not on buttons)
                const target = e.target;
                if (target.closest('.vid-overlay')) {
                    const idx = parseInt(target.closest('.reel-slide')?.dataset?.idx);
                    if (!isNaN(idx)) toggleVideoPlay(idx);
                }
            }
        }, { passive: true });

        // ── Keyboard ──
        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') navigate(1);
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') navigate(-1);
        });

        // ── Mouse wheel — one notch = one slide, no double-skip ──
        let wheelAccum = 0;
        let wheelTimer = null;
        let wheelLocked = false;
        let wheelUnlockTimer = null;

        window.addEventListener('wheel', e => {
            e.preventDefault(); // stop native page scroll

            // If locked, absorb the kinetic scroll events and extend the unlock timer slightly
            if (wheelLocked) {
                clearTimeout(wheelUnlockTimer);
                wheelUnlockTimer = setTimeout(() => { wheelLocked = false; wheelAccum = 0; }, 100);
                return;
            }

            wheelAccum += e.deltaY;
            clearTimeout(wheelTimer);

            if (Math.abs(wheelAccum) >= 60) {
                const dir = wheelAccum > 0 ? 1 : -1;
                wheelAccum = 0;

                if (!isAnimating) {
                    navigate(dir);
                    wheelLocked = true;
                    // Base lockout, will be extended if wheel events keep firing (kinetic scrolling)
                    clearTimeout(wheelUnlockTimer);
                    wheelUnlockTimer = setTimeout(() => { wheelLocked = false; wheelAccum = 0; }, 600);
                }
            } else {
                wheelTimer = setTimeout(() => { wheelAccum = 0; }, 250);
            }
        }, { passive: false }); // passive:false so preventDefault() works

        // ── Run ──
        init();
    </script>

    <!-- Firebase SDK & Config -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script>
        try {
            const firebaseConfig = {
                apiKey: "AIzaSyBASRuasrBZ3NUIc2HyW8HJ8G3tkxhrmyA",
                authDomain: "jibler-37339.firebaseapp.com",
                databaseURL: "https://jibler-37339-default-rtdb.firebaseio.com",
                projectId: "jibler-37339",
                storageBucket: "jibler-37339.firebasestorage.app",
                messagingSenderId: "874793508550",
                appId: "1:874793508550:web:1e16215a9b53f2314a41c7",
                measurementId: "G-6NWSEM7BK9"
            };
            firebase.initializeApp(firebaseConfig);
        } catch (e) { console.error("Firebase Init Error:", e); }

        window.googleLogin = function () {
            const provider = new firebase.auth.GoogleAuthProvider();
            const btn = document.querySelector('.btn-google');
            const originalHtml = btn.innerHTML;

            firebase.auth().signInWithPopup(provider).then((result) => {
                const user = result.user;
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating...';

                const formData = new FormData();
                formData.append('AccountType', 'Google');
                formData.append('GoogleID', user.uid);
                formData.append('name', user.displayName || 'User');
                formData.append('Email', user.email);
                formData.append('Photo', user.photoURL || '');
                formData.append('UserFirebaseToken', '');

                fetch('LogOrSign.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            btn.innerHTML = '<i class="fa-solid fa-check"></i> Welcome!';
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            btn.innerHTML = originalHtml;
                            alert('Error: ' + (json.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        btn.innerHTML = originalHtml;
                        alert("Could not connect to server.");
                    });
            }).catch((error) => {
                alert("Google Login failed: " + error.message);
            });
        };
    </script>

    <!-- MODALS -->
    <?php include 'includes/modals/auth.php'; ?>
    <?php include 'includes/modals/comments.php'; ?>
    <?php include 'includes/modals/product.php'; ?>

</body>

</html>