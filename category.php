<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$catId = intval($_GET['cat'] ?? 38);
$domain = $DomainNamee ?? 'https://qoon.app/dash/';

if ($catId === 55) {
    header("Location: express.php");
    exit;
}
if ($catId === 96) {
    include 'hotels.php';
    exit;
}
if ($catId === 97) {
    include 'esim.php';
    exit;
}

// â”€â”€ Category info â”€â”€
$cat = ['EnglishCategory' => 'Category', 'Photo' => '', 'CategoryId' => $catId];
if ($con) {
    $r = $con->query("SELECT * FROM Categories WHERE CategoryId = $catId LIMIT 1");
    if ($r && $r->num_rows)
        $cat = $r->fetch_assoc();
}
$catName = htmlspecialchars($cat['EnglishCategory'] ?? $cat['ArabCategory'] ?? 'Category');
$catPhoto = $cat['Photo'] ?? '';

function fullUrl($path, $domain)
{
    if (!$path || $path === '0' || $path === 'NONE')
        return '';
    if (strpos($path, 'http') !== false) {
        // Fix double slashes after the protocol (e.g. https://qoon.app//dash/...)
        return preg_replace('#(?<!:)//+#', '/', $path);
    }
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}

// â”€â”€ User location from cookie â”€â”€
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

// â”€â”€ Shops â”€â”€
$shops = [];
if ($con) {
    $hav_select = "";
    $hav_order = "ORDER BY priority DESC, ShopRate DESC";
    if ($userLat !== null && $userLon !== null) {
        $hav_select = ", (6371 * acos(cos(radians($userLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($userLon)) + sin(radians($userLat)) * sin(radians(ShopLat)))) AS distance";
        $hav_order = "ORDER BY distance ASC";
    }

    $r = $con->query("
        SELECT ShopID, ShopName, ShopLogo, ShopCover, ShopRate, ShopOpen, ShopLat, ShopLongt $hav_select
        FROM Shops 
        WHERE CategoryID=$catId AND Status='ACTIVE'
          AND (
            EXISTS (SELECT 1 FROM Posts WHERE ShopID = Shops.ShopID AND PostStatus='ACTIVE' AND PostPhoto != '' AND PostPhoto != '0')
            OR 
            EXISTS (
                SELECT 1 FROM Foods 
                JOIN ShopsCategory ON ShopsCategory.CategoryShopID = Foods.FoodCatID
                WHERE ShopsCategory.ShopID = Shops.ShopID
                  AND Foods.FoodPhoto != '' AND Foods.FoodPhoto != '0'
            )
          )
        $hav_order 
        LIMIT 12
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $shops[] = $row;
        }
    }
}

// ── Products ──
$products = [];
if ($con && count($shops)) {
    $shopIds = implode(',', array_column($shops, 'ShopID'));
    $r = $con->query("SELECT Foods.FoodID, Foods.FoodName, Foods.FoodPrice, Foods.FoodOfferPrice,
                             Foods.FoodPhoto, Shops.ShopName, Shops.ShopLogo, Shops.ShopID
                      FROM Foods
                      JOIN ShopsCategory ON ShopsCategory.CategoryShopID = Foods.FoodCatID
                      JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID
                      WHERE Shops.ShopID IN ($shopIds) AND Shops.Status='ACTIVE'
                        AND Foods.FoodPhoto != '' AND Foods.FoodPhoto != '0'
                      ORDER BY RAND() LIMIT 20");
    if ($r)
        while ($row = $r->fetch_assoc())
            $products[] = $row;
}

// â”€â”€ Posts â”€â”€
$posts = [];
if ($con && count($shops)) {
    $shopIds = implode(',', array_column($shops, 'ShopID'));
    $r = $con->query("SELECT Posts.PostId, Posts.PostPhoto, Posts.PostText, Posts.PostLikes, Posts.Postcomments,
                             Posts.CreatedAtPosts, Posts.ProductID,
                             Shops.ShopName, Shops.ShopLogo, Shops.ShopID,
                             Foods.FoodName, Foods.FoodPrice, Foods.FoodOfferPrice
                      FROM Posts
                      JOIN Shops ON Shops.ShopID = Posts.ShopID
                      LEFT JOIN Foods ON Foods.FoodID = Posts.ProductID
                      WHERE Posts.ShopID IN ($shopIds) AND Posts.PostStatus='ACTIVE'
                        AND (Posts.Video='' OR Posts.Video='0' OR Posts.Video IS NULL)
                        AND Posts.PostPhoto != '' AND Posts.PostPhoto != '0'
                      ORDER BY Posts.CreatedAtPosts DESC LIMIT 6");
    if ($r)
        while ($row = $r->fetch_assoc())
            $posts[] = $row;
}

// â”€â”€ Reels â”€â”€
$reels = [];
if ($con && count($shops)) {
    $shopIds = implode(',', array_column($shops, 'ShopID'));
    $r = $con->query("
        SELECT Posts.PostId AS id, Posts.Video AS media, Shops.ShopName, Shops.ShopLogo, 'post' AS sourceType
        FROM Posts JOIN Shops ON Shops.ShopID=Posts.ShopID
        WHERE Posts.ShopID IN ($shopIds) AND Posts.PostStatus='ACTIVE'
          AND Posts.Video != '' AND Posts.Video != '0' AND Posts.Video IS NOT NULL
        UNION ALL
        SELECT ShopStory.StotyID AS id, ShopStory.StoryPhoto AS media, Shops.ShopName, Shops.ShopLogo, 'story' AS sourceType
        FROM ShopStory JOIN Shops ON Shops.ShopID=ShopStory.ShopID
        WHERE Shops.ShopID IN ($shopIds) AND ShopStory.StoryStatus='ACTIVE'
          AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
        LIMIT 8
    ");
    if ($r)
        while ($row = $r->fetch_assoc())
            $reels[] = $row;
}

?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $catName ?> · QOON</title>
    <!-- ⚡ Apply theme BEFORE paint to prevent flash -->
    <script>
        (function() {
            var t = localStorage.getItem('qoon_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.add('light-mode');
        })();
    </script>
    <meta name="description" content="Explore <?= $catName ?> shops, products, stories and reels on QOON.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.10);
            --glass-hover: rgba(255, 255, 255, 0.10);
            --accent-glow-1: #4a25e1;
            --accent-glow-2: #ffffff;
            --accent-glow-3: #9b2df1;
            --accent: #ffffff;
        }

        html.light-mode {
            --bg-color: #ffffff;
            --text-main: #0f0f0f;
            --text-muted: rgba(0, 0, 0, 0.6);
            --glass-bg: rgba(0, 0, 0, 0.05);
            --glass-border: rgba(0, 0, 0, 0.10);
            --glass-hover: rgba(0, 0, 0, 0.10);
            --accent-glow-2: #cccccc;
            --accent: #0f0f0f;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
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
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        .location-overlay::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at 30% 30%, var(--accent-glow-1) 0%, transparent 40%),
                        radial-gradient(circle at 70% 70%, var(--accent-glow-2) 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, var(--accent-glow-3) 0%, transparent 50%);
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
            background: linear-gradient(135deg, var(--accent-glow-1), var(--accent-glow-3));
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
            border: 2px solid var(--accent-glow-3);
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
            box-shadow: 0 20px 40px rgba(255,255,255,0.1);
        }

        .location-btn:active {
            transform: scale(0.98);
        }

        .location-status {
            margin-top: 20px;
            font-size: 14px;
            color: var(--accent-glow-3);
            font-weight: 500;
            min-height: 20px;
        }

        body.location-locked {
            /* overflow: hidden removed to allow scrolling */
        }

        /* â”€â”€ Background layers (same as index.php) â”€â”€ */
        .grid-bg {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 24px 24px;
            transition: opacity 0.7s ease;
        }

        .grid-glow {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background-image: radial-gradient(rgba(255, 255, 255, 0.8) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
            opacity: 0.8;
            mask-image: radial-gradient(circle 300px at var(--mouse-x, 50%) var(--mouse-y, 50%), black 0%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle 300px at var(--mouse-x, 50%) var(--mouse-y, 50%), black 0%, transparent 100%);
            transition: opacity 0.7s ease;
        }

        .aurora-container {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
            transition: opacity 0.7s ease;
        }

        .aurora-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.55;
            animation: moveBlob 20s infinite alternate ease-in-out;
        }

        .blob-1 {
            width: 80vw;
            height: 40vh;
            background: var(--accent-glow-2);
            bottom: -10vh;
            left: -20vw;
        }

        .blob-2 {
            width: 70vw;
            height: 50vh;
            background: var(--accent-glow-1);
            bottom: -20vh;
            right: -10vw;
            animation-delay: -5s;
        }

        .blob-3 {
            width: 60vw;
            height: 30vh;
            background: var(--accent-glow-3);
            bottom: 10vh;
            left: 20vw;
            opacity: 0.3;
            animation-delay: -10s;
        }

        /* â”€â”€ After the hero scrolls away: fade out dots & aurora (feed is now pure black) â”€â”€ */
        body.hero-gone .aurora-container {
            opacity: 0;
        }

        body.hero-gone .grid-bg {
            opacity: 0;
        }

        body.hero-gone .grid-glow {
            opacity: 0 !important;
        }

        @keyframes moveBlob {
            0% {
                transform: scale(1) translate(0, 0) rotate(0deg);
            }

            50% {
                transform: scale(1.1) translate(5%, -10%) rotate(5deg);
            }

            100% {
                transform: scale(0.9) translate(-5%, 5%) rotate(-5deg);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: 400% 0
            }

            100% {
                background-position: -400% 0
            }
        }

        /* â”€â”€ Wrapper â”€â”€ */
        .page-wrapper {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px 100px;
        }

        /* â”€â”€ Navbar â”€â”€ */
        /* ── Search Bar ── */
        .search-container {
            width: 100%;
            max-width: 600px;
            margin: 32px auto 0;
            position: relative;
            z-index: 20;
        }

        .search-wrapper {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 2px;
            /* For the focus ring effect */
        }

        .search-wrapper:focus-within {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 255, 255, 0.05);
        }

        .search-wrapper i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 18px;
            transition: color 0.3s;
        }

        .search-wrapper:focus-within i {
            color: #fff;
        }

        .search-input {
            width: 100%;
            height: 56px;
            background: transparent;
            border: none;
            padding: 0 24px 0 54px;
            color: #fff;
            font-size: 16px;
            font-family: inherit;
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .topbar {
            position: sticky;
            top: 68px;
            z-index: 90;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            margin-bottom: 0;
            pointer-events: none;
        }

        .topbar>* {
            pointer-events: auto;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s, border-color 0.2s;
            flex-shrink: 0;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .back-btn:hover {
            background: var(--glass-hover);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .logo-text {
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .logo-badge {
            border: 1px solid var(--text-muted);
            border-radius: 99px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Featured Products Grid ── */
        .products-big-grid {
            display: flex;
            gap: 24px;
            overflow-x: auto;
            width: calc(100% + 50vw - 50%);
            padding-right: calc(50vw - 50% + 20px);
            margin-bottom: 40px;
            padding-bottom: 24px;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .products-big-grid::-webkit-scrollbar {
            display: none;
        }

        .premium-floating-card {
            position: relative;
            aspect-ratio: 4/5;
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            text-decoration: none;
            background: #111;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4), inset 0 1px 1px rgba(255,255,255,0.1);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 24px;
            cursor: pointer;
            flex: 0 0 260px;
            scroll-snap-align: start;
        }

        .premium-floating-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .pfc-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            background: #1a1a1a;
        }

        .premium-floating-card:hover .pfc-bg {
            transform: scale(1.06);
        }

        .pfc-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 40%, transparent 100%);
            z-index: 1;
        }

        .pfc-top-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(255, 255, 255, 0.95);
            color: #000;
            font-size: 13px;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 99px;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .pfc-action-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .premium-floating-card:hover .pfc-action-btn {
            background: #fff;
            color: #000;
            transform: scale(1.1);
        }

        .pfc-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pfc-name {
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }

        .pfc-shop {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .pfc-shop img {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ── Reels ── */
        /* ── Hero ── */
        .cat-hero {
            text-align: center;
            padding: 100px 0 20px;
        }

        .cat-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--accent);
            border-radius: 99px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .cat-hero-eyebrow img {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            object-fit: cover;
        }

        .cat-hero h1 {
            font-size: clamp(52px, 7vw, 88px);
            font-weight: 600;
            letter-spacing: -2px;
            line-height: 1.05;
            text-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }

        .cat-hero p {
            font-size: clamp(15px, 1.8vw, 20px);
            color: var(--text-muted);
            font-weight: 400;
            margin-bottom: 36px;
        }

        /* ── Logo Carousel (Liquid Glass) ── */
        .logo-carousel-outer {
            width: 100vw;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 24px;
            padding: 12px 0;
            overflow: hidden;
            /* Side Shadow Fades removed */
        }

        .logo-carousel-track {
            display: flex;
            align-items: center;
            gap: 40px;
            width: max-content;
            animation: scrollLogos 45s linear infinite;
        }

        .logo-carousel-item {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(.2, .8, .2, 1);
            cursor: pointer;
        }

        .logo-carousel-item:hover {
            transform: scale(1.18) translateY(-4px);
            border-color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.07);
        }

        .logo-carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(0.1);
            opacity: 0.9;
            transition: all 0.3s;
        }

        .logo-carousel-item:hover img {
            filter: grayscale(0);
            opacity: 1;
        }

        @keyframes scrollLogos {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .cat-stats {
            display: inline-flex;
            gap: 24px;
            align-items: center;
        }

        .cat-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 99px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 500;
        }

        .cat-stat i {
            color: var(--accent);
        }

        /* â”€â”€ Search â”€â”€ */
        .search-section {
            margin-bottom: 0;
        }

        .search-bar {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 99px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-bar:focus-within {
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(255, 255, 255, 0.07), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .search-bar i {
            color: var(--text-muted);
            font-size: 17px;
            padding: 0 4px;
        }

        .search-bar input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: #fff;
            font-size: 15px;
            font-family: Inter, sans-serif;
        }

        .search-bar input::placeholder {
            color: var(--text-muted);
        }

        /* â”€â”€ Divider â”€â”€ */
        .feed-divider {
            width: calc(100% + 80px);
            margin-left: -40px;
            border: none;
            height: 0;
            margin-top: 60px;
            margin-bottom: 0;
        }

        /* â”€â”€ The Black Feed Section â”€â”€ */
        .feed-section {
            background-color: #000;
            position: relative;
            z-index: 10;
            width: 100%;
            margin: 0;
            padding: 20px 40px 80px;
            border: none;
            /* Soft dark shadow at the top edge instead of a hard line */
            box-shadow: 0 -1px 0 rgba(0, 0, 0, 0.8),
                inset 0 40px 80px -20px rgba(0, 0, 0, 0.9);
        }

        /* Gradient veil that dissolves hero into feed */
        .feed-section::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to bottom, transparent, #000);
            pointer-events: none;
            z-index: 1;
        }

        .feed-divider {
            position: relative;
            z-index: 10;
        }

        .feed-inner {
            max-width: 1120px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* â”€â”€ Section Header â”€â”€ */
        .sec-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            /* Tighter gap */
        }

        .sec-title {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 500;
            letter-spacing: -1px;
        }

        .see-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 99px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .see-all-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .see-all-btn i {
            font-size: 11px;
        }

        /* â”€â”€ Products Grid â”€â”€ */
        /* ── Big Products Grid (Post Style) ── */
        .products-big-grid {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            width: calc(100% + 50vw - 50%);
            padding-right: calc(50vw - 50% + 20px);
            margin-bottom: 40px;
            padding-bottom: 24px;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .products-big-grid::-webkit-scrollbar {
            display: none;
        }

        .product-card-big {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: transform 0.3s cubic-bezier(.2, .8, .2, 1), border-color 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .product-card-big:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.14);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        }

        .pb-h {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pb-av {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pb-shop {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }

        .pb-photo {
            border-radius: 14px;
            overflow: hidden;
            background: #111;
            aspect-ratio: 1;
        }

        .pb-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s;
        }

        .product-card-big:hover .pb-photo img {
            transform: scale(1.06);
        }

        .pb-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .pb-name {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pb-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .pb-price {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent);
        }

        .pb-btn {
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .products-big-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }

            .product-card-big {
                padding: 12px;
            }

            .pb-name {
                font-size: 13px;
            }

            .pb-price {
                font-size: 12px;
            }

            .pb-btn {
                display: none;
            }
        }

        /* ── Shops Scroll ── */
        .shops-scroll {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            width: calc(100% + 50vw - 50%);
            padding-right: calc(50vw - 50% + 20px);
            margin-bottom: 40px;
            padding-bottom: 24px;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .shops-scroll::-webkit-scrollbar {
            display: none;
        }

        .shop-card {
            flex: 0 0 460px;
            aspect-ratio: 16 / 10.5;
            background: #0d0d0d;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 32px;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            position: relative;
            scroll-snap-align: start;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
            transition: transform 0.35s cubic-bezier(.2, .8, .2, 1), box-shadow 0.3s;
        }

        .shop-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.85);
        }

        /* Cover image */
        .shop-cover-wrap {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .shop-cover-wrap img.cover-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
            display: block;
        }

        .shop-card:hover .cover-img {
            transform: scale(1.08);
        }

        /* Top Gradient Shadow */
        .shop-top-shadow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
            z-index: 3;
        }

        /* Bottom Gradient Shadow */
        .shop-cover-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 30%, rgba(0, 0, 0, 0.92) 100%);
            z-index: 2;
        }

        /* Distance badge — top right */
        .shop-distance-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 99px;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 5;
        }

        /* Open/closed pill — top left */
        .shop-status-pill {
            position: absolute;
            top: 20px;
            left: 20px;
            border-radius: 99px;
            padding: 6px 14px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(10px);
            z-index: 5;
        }

        .shop-status-pill.open {
            background: rgba(34, 197, 94, 0.35);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #fff;
        }

        .shop-status-pill.closed {
            background: rgba(239, 68, 68, 0.35);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fff;
        }

        .status-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #fff;
        }

        /* Floating content footer */
        .shop-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 24px;
            z-index: 10;
            text-align: left;
        }

        .shop-footer-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 8px;
        }

        .shop-logo-mini {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 2px solid #000;
            overflow: hidden;
            background: #111;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
        }

        .shop-logo-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .shop-name-mini {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 1);
        }

        .shop-meta-mini {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
        }

        .star-rating {
            color: #fbbf24;
            font-size: 11px;
        }

        /* â”€â”€ Reels â”€â”€ */
        .reels-scroll {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            width: calc(100% + 50vw - 50%);
            padding-right: calc(50vw - 50% + 20px);
            padding-bottom: 8px;
            scrollbar-width: none;
        }

        .reels-scroll::-webkit-scrollbar {
            display: none;
        }

        .reel-card {
            flex: 0 0 155px;
            aspect-ratio: 9/16;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            background: #111;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: transform 0.3s cubic-bezier(.2, .8, .2, 1), box-shadow 0.3s;
        }

        .reel-card:hover {
            transform: scale(1.04);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .reel-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .reel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent 35%, rgba(0, 0, 0, 0.85));
        }

        .reel-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #fff;
        }

        .reel-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 10px 12px;
        }

        .reel-shop-name {
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            line-height: 1.3;
        }

        /* ── Posts Grid (Home Style) ── */
        .posts-grid {
            display: flex;
            flex-direction: column;
            gap: 40px;
            max-width: 680px;
            margin: 0 auto;
        }

        .post-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .post-card:hover {
            border-color: rgba(255, 255, 255, 0.14);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .post-shop-info {
            flex: 1;
        }

        .post-shop-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .post-time {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .post-text {
            font-size: 15px;
            line-height: 1.5;
            color: #e0e0e0;
        }

        .post-img {
            width: 100%;
            border-radius: 16px;
            object-fit: cover;
            max-height: 500px;
            background: #111;
            display: block;
        }

        .post-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 16px;
            margin-top: 8px;
        }

        .action-group {
            display: flex;
            gap: 16px;
        }

        .action-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .action-btn:hover {
            color: var(--text-main);
        }

        .action-btn i {
            font-size: 18px;
        }

        .action-btn i {
            font-size: 18px;
        }

        /* Unified Inline Product Design */
        .feed-inline-product {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 12px;
            margin-top: 12px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            text-decoration: none;
            color: white;
        }

        .feed-inline-product:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .fip-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .fip-icon-holder {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(44, 181, 232, 0.15);
            color: #2cb5e8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .fip-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .fip-name {
            font-size: 14px;
            font-weight: 600;
        }

        .fip-price {
            font-size: 13px;
            font-weight: 700;
            color: #2cb5e8;
        }

        .fip-right {
            color: rgba(255, 255, 255, 0.4);
            font-size: 12px;
            margin-right: 4px;
        }

        /* â”€â”€ Empty â”€â”€ */
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            font-size: 15px;
        }

        .empty i {
            font-size: 42px;
            margin-bottom: 16px;
            display: block;
            opacity: 0.25;
        }

        /* â”€â”€ Section separator â”€â”€ */
        .sec-gap {
            height: 70px;
        }

        /* â”€â”€ Responsive â”€â”€ */
        @media (max-width: 680px) {
            .topbar {
                padding: 20px;
            }

            .cat-hero {
                padding: 80px 0 20px;
            }

            .page-wrapper {
                padding: 0 20px 60px;
            }

            .feed-section {
                padding: 40px 20px 60px;
            }

            .feed-divider {
                display: none;
            }

            .cat-hero h1 {
                letter-spacing: -1px;
            }

            /* Fix shops for mobile - ensure edge-to-edge peek effect */
            .shops-scroll {
                width: calc(100% + 40px);
                margin-left: -20px;
                padding: 0 20px 20px;
                gap: 14px;
                scroll-snap-type: x mandatory;
                scroll-padding-left: 20px;
            }

            .shop-card {
                flex: 0 0 82vw;
                border-radius: 20px;
                aspect-ratio: 16/11;
                scroll-snap-align: start;
            }

            .shop-name-mini {
                font-size: 18px;
            }

            .shop-distance-badge {
                top: 16px;
                right: 16px;
                padding: 4px 10px;
                font-size: 10px;
            }

            .shop-status-pill {
                top: 16px;
                left: 16px;
                padding: 4px 10px;
            }

            .shop-footer {
                padding: 16px;
            }

            /* Fix Products for mobile (Horizontal scroll instead of tiny grid) */
            .products-big-grid {
                display: flex;
                overflow-x: auto;
                scrollbar-width: none;
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                width: calc(100% + 40px);
                margin: -10px -20px -40px -20px;
                padding: 10px 20px 40px 20px;
                gap: 14px;
            }

            .products-big-grid::-webkit-scrollbar {
                display: none;
            }

            .premium-floating-card {
                flex: 0 0 180px;
                scroll-snap-align: start;
                padding: 16px;
                aspect-ratio: 4/5;
            }
            .pfc-name { font-size: 16px !important; }
            .pfc-price { font-size: 14px !important; }

            .pb-photo {
                border-radius: 10px;
            }

            .pb-name {
                font-size: 13px;
            }

            .pb-shop {
                font-size: 12px;
            }

            .pb-price {
                font-size: 13px;
            }

            /* Fix Posts edge-to-edge */
            .posts-grid {
                gap: 24px;
            }

            .post-card {
                border-radius: 0;
                margin-left: -20px;
                margin-right: -20px;
                width: calc(100% + 40px);
                border-left: none;
                border-right: none;
                padding: 24px 20px;
            }

            .post-img {
                border-radius: 0;
            }
        }

        /* --- Light Mode Specifics --- */
        html.light-mode .grid-bg { background-image: radial-gradient(rgba(0, 0, 0, 0.08) 1px, transparent 1px); }
        html.light-mode .grid-glow { display: block; background-image: radial-gradient(rgba(44,181,232,0.8) 2px, transparent 2px); opacity: 0.6; }
        html.light-mode .aurora-container { display: block; }
        html.light-mode .aurora-blob { opacity: 0.15; mix-blend-mode: multiply; }
        
        html.light-mode .topbar { background: transparent; }
        html.light-mode .back-btn { background: rgba(0,0,0,0.05); color: #000; border-color: rgba(0,0,0,0.1); }
        html.light-mode .cat-hero h1 { text-shadow: none; color: #0f0f0f; }
        html.light-mode .cat-hero p { color: rgba(0,0,0,0.6); }
        html.light-mode .cat-hero-eyebrow { background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.1); color: #0f0f0f; }
        
        html.light-mode .sec-title { color: #0f0f0f; }
        html.light-mode .see-all-btn { background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.1); color: #0f0f0f; }
        html.light-mode .see-all-btn:hover { background: rgba(0,0,0,0.08); border-color: rgba(0,0,0,0.2); }
        
        html.light-mode .premium-floating-card { background: #fff; border-color: rgba(0,0,0,0.1); box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        html.light-mode .pfc-name { text-shadow: 0 1px 4px rgba(0,0,0,0.8); }
        
        html.light-mode .search-wrapper { background: #fff; border-color: rgba(0,0,0,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        html.light-mode .search-wrapper i { color: #000; }
        html.light-mode .search-input { color: #000; }
        html.light-mode .search-input::placeholder { color: rgba(0,0,0,0.5); }
        
        html.light-mode .cat-stat { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); }
        html.light-mode .logo-carousel-item { background: #fff; border-color: rgba(0,0,0,0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        html.light-mode .logo-carousel-item img { filter: none; opacity: 1; }
        html.light-mode .logo-carousel-item:hover img { transform: scale(1.05); }
        
        html.light-mode .product-card-big,
        html.light-mode .shop-card,
        html.light-mode .post-card,
        html.light-mode .reel-card {
            background: #fff;
            border-color: rgba(0,0,0,0.08);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        
        html.light-mode .pb-name,
        html.light-mode .shop-name,
        html.light-mode .post-shop-name,
        html.light-mode .post-text { color: #0f0f0f; }
        
        html.light-mode .pb-shop,
        html.light-mode .shop-cats,
        html.light-mode .post-time { color: rgba(0,0,0,0.6); }

        html.light-mode .tbb-gradient-text { background: none !important; -webkit-text-fill-color: #0f0f0f !important; color: #0f0f0f !important; }

        html.light-mode .feed-divider { background: rgba(0,0,0,0.08); }
        
        html.light-mode .feed-section { background-color: #ffffff; box-shadow: 0 -1px 0 rgba(0,0,0,0.05); }
        html.light-mode .feed-section::before { background: linear-gradient(to bottom, transparent, #ffffff); }

        html.light-mode .feed-inline-product { background: rgba(0, 0, 0, 0.03); border-color: rgba(0, 0, 0, 0.08); color: #000; }
        html.light-mode .feed-inline-product:hover { background: rgba(0, 0, 0, 0.06); border-color: rgba(0, 0, 0, 0.15); }
        html.light-mode .fip-icon-holder { background: rgba(44, 181, 232, 0.1); color: #0d8abc; }
        html.light-mode .fip-name { color: #000; }
        html.light-mode .fip-price { color: #0d8abc; }
        html.light-mode .fip-right { color: rgba(0, 0, 0, 0.3); }

        /* ✨ Theme Toggle FAB */
        .theme-fab {
            position: fixed; bottom: 28px; right: 28px; width: 52px; height: 52px;
            border-radius: 50%; border: none; cursor: pointer; z-index: 99999;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
            background: linear-gradient(135deg, #4a25e1, #2cb5e8); color: #fff;
            box-shadow: 0 4px 20px rgba(44, 181, 232, 0.4), 0 0 0 1px rgba(255,255,255,0.1);
        }
        .theme-fab:hover { transform: scale(1.12) rotate(20deg); box-shadow: 0 8px 32px rgba(44, 181, 232, 0.5), 0 0 0 1px rgba(255,255,255,0.15); }
        .theme-fab:active { transform: scale(0.96); }
        html.light-mode .theme-fab { background: linear-gradient(135deg, #ffffff, #f2f2f2); color: #4a25e1; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12), 0 0 0 1px #e5e5e5; }
        html.light-mode .theme-fab:hover { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.1); }
        .theme-fab .fab-icon { transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s; display: block; }
        .theme-fab.spinning .fab-icon { transform: rotate(360deg); }
        @media (max-width: 768px) { .theme-fab { bottom: 84px; right: 16px; width: 44px; height: 44px; font-size: 17px; } }
    </style>
</head>

    <body>

    <!-- Background layers -->
    <div class="grid-bg"></div>
    <div class="grid-glow"></div>
    <div class="aurora-container">
        <div class="aurora-blob blob-1"></div>
        <div class="aurora-blob blob-2"></div>
        <div class="aurora-blob blob-3"></div>
    </div>

    <?php require_once 'includes/header.php'; ?>

    <div class="page-wrapper">

        <!-- Topbar (Fixed) -->
        <div class="topbar">
            <a href="javascript:void(0)" onclick="history.length>1?history.back():location.href='index.php'"
                class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <button id="btn-loc" onclick="openTeleport()"
                style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.25);border-radius:99px;padding:8px 16px;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .2s; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);">
                <i class="fa-solid fa-location-crosshairs"></i>
                <span id="loc-label" style="pointer-events:none;">Change Location</span>
            </button>
        </div>

        <!-- Hero -->
        <div class="cat-hero">
            <div class="cat-hero-eyebrow">
                <?php if ($catPhoto): ?>
                    <img src="<?= htmlspecialchars($catPhoto) ?>" alt="">
                <?php else: ?>
                    <i class="fa-solid fa-border-all"></i>
                <?php endif; ?>
                Category
            </div>
            <h1><?= $catName ?></h1>

            <div class="search-container" id="main-search-container" <?= (!count($shops) && !count($posts) && !count($products)) ? 'style="display:none;"' : '' ?>>
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="global-search" class="search-input"
                        placeholder="Search in <?= htmlspecialchars($catName) ?>...">
                </div>
            </div>

            <?php if (count($shops)): ?>
                <div class="logo-carousel-outer">
                    <div class="logo-carousel-track">
                        <?php
                        // Double the array for infinite scroll effect
                        $displayShops = array_merge($shops, $shops);
                        foreach ($displayShops as $s):
                            $logo = fullUrl($s['ShopLogo'], $domain);
                            $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($s['ShopName']) . '&background=222&color=fff';
                            ?>
                            <div class="logo-carousel-item" onclick="location.href='shop.php?id=<?= $s['ShopID'] ?>&boutique=1'">
                                <img src="<?= htmlspecialchars($logo ?: $fallback) ?>" onerror="this.src='<?= $fallback ?>'"
                                    alt="<?= htmlspecialchars($s['ShopName']) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /cat-hero -->

    </div><!-- /page-wrapper -->

    <hr class="feed-divider">

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• Feed â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="feed-section">
        <div class="feed-inner">
            <?php if ($locationRequired): ?>
                <div id="locationOverlay" class="location-overlay">
                    <div class="location-content">
                        <div class="location-icon-pulsar">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h1>Know your location</h1>
                        <p>QOON needs your location to show the best stores, reels, and exclusive offers near you.</p>
                        <button id="getLocationBtn" class="location-btn" onclick="requestUserLocation()">
                            <span>Allow Access</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <div class="location-status" id="locationStatus"></div>
                    </div>
                </div>
            <?php else: ?>

            <!-- â”€â”€ SHOPS â”€â”€ -->
            <div class="sec-header">
                <div class="sec-title">Shops</div>
                <a class="see-all-btn" href="category_shops.php?cat=<?= $catId ?>">See all <i
                        class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if (count($shops)): ?>
                <div class="shops-scroll" id="shops-row">
                    <?php foreach ($shops as $s):
                        $logo = fullUrl($s['ShopLogo'], $domain);
                        $cover = fullUrl($s['ShopCover'], $domain);
                        $open = ($s['ShopOpen'] ?? '') === 'Open';
                        $rate = floatval($s['ShopRate'] ?? 0);
                        // Distance
                        $distKm = null;
                        if ($userLat !== null && $userLon !== null) {
                            $distKm = haversineKm($userLat, $userLon, floatval($s['ShopLat'] ?? 0), floatval($s['ShopLongt'] ?? 0));
                        }
                        $distLabel = '';
                        if ($distKm !== null && $distKm > 0) {
                            $distLabel = $distKm < 1
                                ? round($distKm * 1000) . ' m'
                                : number_format($distKm, 1) . ' km';
                        }
                        $fallbackLogo = 'https://ui-avatars.com/api/?name=' . urlencode($s['ShopName']) . '&background=2cb5e8&color=fff&size=128';
                        ?>
                        <a class="shop-card" href="shop.php?id=<?= $s['ShopID'] ?>&boutique=1"
                            data-name="<?= strtolower(htmlspecialchars($s['ShopName'])) ?>">
                            <div class="shop-cover-wrap">
                                <?php if ($cover): ?>
                                    <img class="cover-img" src="<?= htmlspecialchars($cover) ?>" loading="lazy"
                                        onerror="this.style.display='none'" alt="">
                                <?php else: ?>
                                    <div
                                        style="width:100%;height:100%;background:radial-gradient(at 0% 0%,rgba(255,255,255,0.1) 0,transparent 60%),#111;">
                                    </div>
                                <?php endif; ?>
                                <div class="shop-top-shadow"></div>
                                <div class="shop-cover-gradient"></div>

                                <div class="shop-status-pill <?= $open ? 'open' : 'closed' ?>">
                                    <span class="status-dot"></span>
                                    <?= $open ? 'Open' : 'Closed' ?>
                                </div>

                                <?php if ($distLabel): ?>
                                    <div class="shop-distance-badge">
                                        <i class="fa-solid fa-location-dot"></i> <?= $distLabel ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="shop-footer">
                                <div class="shop-footer-top">
                                    <div class="shop-logo-mini">
                                        <img src="<?= htmlspecialchars($logo ?: $fallbackLogo) ?>"
                                            onerror="this.src='<?= $fallbackLogo ?>'" alt="">
                                    </div>
                                    <div class="shop-name-mini"><?= htmlspecialchars($s['ShopName']) ?></div>
                                </div>
                                <div class="shop-meta-mini">
                                    <?php if ($rate > 0): ?>
                                        <span class="star-rating"><i class="fa-solid fa-star"></i>
                                            <?= number_format($rate, 1) ?></span>
                                        <span style="opacity:0.4">Â·</span>
                                    <?php endif; ?>
                                    <span><?= $catName ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty"><i class="fa-solid fa-store"></i>No shops yet</div>
            <?php endif; ?>

            <div class="sec-gap"></div>

            <!-- â”€â”€ STORIES & REELS â”€â”€ -->
            <div class="sec-header">
                <div class="sec-title">Stories &amp; Reels</div>
                <a class="see-all-btn" href="category_reels.php?cat=<?= $catId ?>">See more <i
                        class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if (count($reels)): ?>
                <div class="reels-scroll" id="reels-row">
                    <?php foreach ($reels as $rl):
                        $media = fullUrl($rl['media'], $domain);
                        $logo = fullUrl($rl['ShopLogo'], $domain);
                        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                        $isVid = in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv']);
                        ?>
                        <div class="reel-card"
                            onclick="location.href='category_reels.php?cat=<?= $catId ?>&id=<?= $rl['id'] ?>&type=<?= $rl['sourceType'] ?? 'story' ?>'"
                            data-name="<?= strtolower(htmlspecialchars($rl['ShopName'])) ?>">
                            <?php if ($isVid): ?>
                                <img src="<?= htmlspecialchars($logo ?: 'https://ui-avatars.com/api/?name=' . urlencode($rl['ShopName']) . '&background=2cb5e8&color=fff') ?>"
                                    loading="lazy" onerror="this.parentNode.style.background='#111'" alt="">
                                <div class="reel-overlay"></div>
                                <div class="reel-play"><i class="fa-solid fa-play" style="margin-left:2px;"></i></div>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($media) ?>" loading="lazy" onerror="this.style.display='none'"
                                    alt="">
                                <div class="reel-overlay"></div>
                            <?php endif; ?>
                            <div class="reel-footer">
                                <div class="reel-shop-name"><?= htmlspecialchars($rl['ShopName']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty"><i class="fa-solid fa-clapperboard"></i>No reels yet</div>
            <?php endif; ?>

            <div class="sec-gap"></div>
            <!-- ── PRODUCTS (BIG) ── -->
            <?php if (count($products)): ?>
                <div class="sec-header">
                    <div class="sec-title" style="display:flex; align-items:center;">
                        <style>
                        @keyframes float3d {
                            0% { transform: translateY(0) rotateX(10deg) rotateY(-10deg) scale(1); filter: drop-shadow(0 10px 15px rgba(245,0,87,0.3)); }
                            50% { transform: translateY(-8px) rotateX(25deg) rotateY(15deg) scale(1.05); filter: drop-shadow(0 20px 30px rgba(245,0,87,0.7)); }
                            100% { transform: translateY(0) rotateX(10deg) rotateY(-10deg) scale(1); filter: drop-shadow(0 10px 15px rgba(245,0,87,0.3)); }
                        }
                        .icon-3d-wrapper {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 38px;
                            height: 38px;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #f50057 0%, #8e2de2 100%);
                            box-shadow: 
                                inset 2px 2px 4px rgba(255,255,255,0.5),
                                inset -2px -2px 6px rgba(0,0,0,0.5),
                                0 8px 20px rgba(245,0,87,0.4);
                            animation: float3d 3.5s ease-in-out infinite;
                            margin-right: 14px;
                            perspective: 1000px;
                            transform-style: preserve-3d;
                        }
                        .icon-3d-wrapper i {
                            color: #fff;
                            font-size: 18px;
                            transform: translateZ(15px);
                            text-shadow: 0 4px 10px rgba(0,0,0,0.4);
                        }
                        .tbb-gradient-text {
                            background: linear-gradient(135deg, #fff 0%, #d4d4d4 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        }
                        </style>
                        <?php if (stripos($catName, 'Fashion') !== false || stripos($catName, 'أزياء') !== false || $catId == 5): ?>
                            <div class="icon-3d-wrapper">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <span class="tbb-gradient-text">Try before buy</span>
                        <?php else: ?>
                            <div class="icon-3d-wrapper" style="background: linear-gradient(135deg, #2cb5e8 0%, #4a25e1 100%); box-shadow: inset 2px 2px 4px rgba(255,255,255,0.5), inset -2px -2px 6px rgba(0,0,0,0.5), 0 8px 20px rgba(44,181,232,0.4);">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <span class="tbb-gradient-text">Products</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="products-big-grid">
                    <?php foreach ($products as $pr):
                        $photos = array_filter(array_map('trim', explode(',', $pr['FoodPhoto'] ?? '')));
                        $prImg = !empty($photos) ? fullUrl($photos[0], $domain) : '';
                        $prLogo = fullUrl($pr['ShopLogo'], $domain);
                        $prPrice = (float) ($pr['FoodOfferPrice'] ?? 0) > 0 ? $pr['FoodOfferPrice'] : $pr['FoodPrice'];
                        $targetUrl = "shop.php?id={$pr['ShopID']}&boutique=1&product={$pr['FoodID']}";
                        ?>
                        <a class="premium-floating-card" href="<?= htmlspecialchars($targetUrl) ?>"
                            data-name="<?= strtolower(htmlspecialchars($pr['FoodName'] . ' ' . $pr['ShopName'])) ?>">
                            <img src="<?= htmlspecialchars($prImg) ?>" class="pfc-bg" loading="lazy" alt="<?= htmlspecialchars($pr['FoodName']) ?>">
                            <div class="pfc-overlay"></div>
                            
                            <div class="pfc-top-badge">
                                <?= number_format($prPrice, 2) ?> DH
                            </div>
                            
                            <div class="pfc-action-btn">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>

                            <div class="pfc-content">
                                <div class="pfc-name">
                                    <?= htmlspecialchars($pr['FoodName']) ?> 
                                    <i class="fa-solid fa-circle-check" style="color: #3b82f6; font-size: 14px; margin-left: 6px;"></i>
                                </div>
                                <div class="pfc-shop">
                                    <img src="<?= htmlspecialchars($prLogo ?: 'https://ui-avatars.com/api/?name=' . urlencode($pr['ShopName'])) ?>" alt="">
                                    <?= htmlspecialchars($pr['ShopName']) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- â”€â”€ POSTS â”€â”€ -->
            <div class="sec-header">
                <div class="sec-title">Posts</div>
                <a class="see-all-btn" href="index.php">See more <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if (count($posts)): ?>
                <div class="posts-grid" id="posts-grid">
                    <?php foreach ($posts as $p):
                        $pImg = fullUrl($p['PostPhoto'], $domain);
                        $pLogo = fullUrl($p['ShopLogo'], $domain);
                        $pText = $p['PostText'] ?? '';
                        $pTime = date('M j, Y', strtotime($p['CreatedAtPosts'] ?? 'now'));
                        ?>
                        <?php
                        $pProductId = $p['ProductID'] ?? '';
                        $pFoodName = $p['FoodName'] ?? '';
                        $pFoodPrice = (float) ($p['FoodOfferPrice'] ?? 0) > 0 ? $p['FoodOfferPrice'] : ($p['FoodPrice'] ?? 0);
                        $hasProduct = !empty($pProductId) && $pProductId !== '0' && $pProductId !== 'NONE';
                        ?>
                        <div class="post-card" data-name="<?= strtolower(htmlspecialchars($p['ShopName'] . ' ' . $pText)) ?>">
                            <div class="post-header">
                                <img class="post-avatar"
                                    src="<?= htmlspecialchars($pLogo ?: 'https://ui-avatars.com/api/?name=' . urlencode($p['ShopName']) . '&background=2cb5e8&color=fff') ?>"
                                    onerror="this.src='https://ui-avatars.com/api/?name=S&background=2cb5e8&color=fff'" alt=""
                                    onclick="window.location.href='shop.php?id=<?= $p['ShopID'] ?>'" style="cursor:pointer;">
                                <div class="post-shop-info" onclick="window.location.href='shop.php?id=<?= $p['ShopID'] ?>'" style="cursor:pointer;">
                                    <div class="post-shop-name"><?= htmlspecialchars($p['ShopName']) ?></div>
                                    <div class="post-time"><?= $pTime ?></div>
                                </div>
                                <button class="action-btn" style="padding:0;"><i class="fa-solid fa-ellipsis"></i></button>
                            </div>

                            <?php if ($pText && $pText !== 'NONE'): ?>
                                <div class="post-text"><?= htmlspecialchars($pText) ?></div>
                            <?php endif; ?>

                            <?php if ($pImg): ?>
                                <img src="<?= htmlspecialchars($pImg) ?>" loading="lazy" class="post-img"
                                    onerror="this.style.display='none'" alt="">
                            <?php endif; ?>

                            <?php if ($hasProduct): 
                                $targetUrl = "shop.php?id={$p['ShopID']}&product={$pProductId}";
                            ?>
                                <a class="feed-inline-product" href="<?= htmlspecialchars($targetUrl) ?>">
                                    <div class="fip-left">
                                        <div class="fip-icon-holder"><i class="fa-solid fa-cart-shopping"></i></div>
                                        <div class="fip-info">
                                            <div class="fip-name"><?= htmlspecialchars($pFoodName) ?></div>
                                            <div class="fip-price"><?= number_format($pFoodPrice, 0) ?> MAD</div>
                                        </div>
                                    </div>
                                    <div class="fip-right"><i class="fa-solid fa-chevron-right"></i></div>
                                </a>
                            <?php endif; ?>

                            <div class="post-actions">
                                <div class="action-group">
                                    <button class="action-btn" onclick="handleLike(this, <?= $p['PostId'] ?? $p['PostID'] ?? '0' ?>, <?= $p['ShopID'] ?? '0' ?>)"><i class="fa-regular fa-heart"></i>
                                        <span><?= intval($p['PostLikes'] ?? 0) ?></span></button>
                                    <button class="action-btn" onclick="openCommentModal(<?= $p['PostId'] ?? $p['PostID'] ?? '0' ?>, '<?= addslashes(htmlspecialchars($p['ShopName'] ?? 'Shop')) ?>')"><i class="fa-regular fa-comment"></i>
                                        <?= intval($p['Postcomments'] ?? 0) ?></button>
                                    <button class="action-btn"><i class="fa-solid fa-share-nodes"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty"><i class="fa-solid fa-newspaper"></i>No posts yet</div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div><!-- /feed-section -->
    <script>
        // ── Mouse-tracking dot grid glow ──
        document.addEventListener('mousemove', e => {
            const pct = { x: (e.clientX / window.innerWidth * 100).toFixed(2) + '%', y: (e.clientY / window.innerHeight * 100).toFixed(2) + '%' };
            document.documentElement.style.setProperty('--mouse-x', pct.x);
            document.documentElement.style.setProperty('--mouse-y', pct.y);
        });

        // ── Fade out aurora+dots only after the hero has fully scrolled away ──
        const heroEl = document.querySelector('.cat-hero');
        if (heroEl && 'IntersectionObserver' in window) {
            const heroObs = new IntersectionObserver(entries => {
                document.body.classList.toggle('hero-gone', !entries[0].isIntersecting);
            }, { threshold: 0 });
            heroObs.observe(heroEl);
        }

        // ── Global Search Filtering ──
        document.getElementById('global-search')?.addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('.shop-card, .reel-card, .feed-post-card');

            items.forEach(el => {
                const name = (el.dataset.name || '').toLowerCase();
                const match = !q || name.includes(q);
                el.style.display = match ? '' : 'none';
            });

            // Hide/Show headers & spacers based on content
            ['.shops-scroll', '.reels-scroll', '.posts-grid'].forEach(gridSel => {
                const grid = document.querySelector(gridSel);
                if (!grid) return;
                const visibleChild = Array.from(grid.children).some(c => c.style.display !== 'none');
                // Find the preceding header (sec-header) and following spacer (sec-gap)
                const header = grid.previousElementSibling;
                const gap = grid.nextElementSibling;

                if (header && header.classList.contains('sec-header')) {
                    header.style.display = visibleChild ? '' : 'none';
                }
                if (grid) grid.style.display = visibleChild ? '' : 'none';
                if (gap && gap.classList.contains('sec-gap')) {
                    gap.style.display = visibleChild ? '' : 'none';
                }
            });
        });
    </script>
    <!-- â”€â”€ LOCATION MODAL â”€â”€ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <style>
        /* Overlay */
        #tp-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(16px);
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .35s;
        }

        #tp-overlay.open {
            display: flex;
            opacity: 1;
        }

        /* Modal */
        #tp-modal {
            width: 94%;
            max-width: 580px;
            max-height: 92vh;
            overflow-y: auto;
            background: rgba(14, 14, 14, 0.85);
            backdrop-filter: blur(32px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.85), inset 0 1px 0 rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            padding: 28px 24px 24px;
            transform: scale(0.93) translateY(20px);
            transition: transform .4s cubic-bezier(.175, .885, .32, 1.275);
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
        }

        #tp-overlay.open #tp-modal {
            transform: scale(1) translateY(0);
        }

        /* Close */
        .tp-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 34px;
            height: 34px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background .2s;
        }

        .tp-close:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        /* City chips */
        .city-chips {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            direction: rtl;
            /* scroll starts from right */
        }

        .city-chips>* {
            direction: ltr;
            flex-shrink: 0;
        }

        /* chips read left-to-right */
        .city-chips::-webkit-scrollbar {
            display: none;
        }

        .city-chip {
            padding: 8px 16px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: background .2s, border-color .2s, color .2s, transform .15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .city-chip:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
            transform: translateY(-1px);
        }

        .city-chip.active {
            background: rgba(255, 255, 255, 0.12);
            border-color: #ffffff;
            color: #ffffff;
        }

        /* Map */
        #tp-map {
            width: 100%;
            height: 280px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 1;
            cursor: crosshair;
            flex-shrink: 0;
        }

        .leaflet-container {
            cursor: crosshair !important;
        }

        /* Hint below map */
        .map-hint {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Save button */
        #tp-save {
            width: 100%;
            padding: 15px;
            border-radius: 16px;
            background: #fff;
            border: none;
            color: #000;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: .01em;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity .2s, transform .2s;
            box-shadow: 0 8px 28px rgba(255, 255, 255, 0.15);
        }

        #tp-save:hover {
            opacity: .88;
            transform: translateY(-1px);
        }

        #tp-save.visible {
            display: flex;
        }

        /* Toast notification */
        #tp-toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(24px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(16px);
            border-radius: 99px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transition: opacity .3s, transform .3s;
            z-index: 10001;
            pointer-events: none;
            white-space: nowrap;
        }

        #tp-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Leaflet overrides */
        .leaflet-popup-content-wrapper {
            background: rgba(14, 14, 14, 0.92) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5) !important;
        }

        .leaflet-popup-tip {
            background: rgba(14, 14, 14, 0.92) !important;
        }

        .leaflet-control-zoom a {
            background: rgba(20, 20, 20, 0.85) !important;
            color: #fff !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
    </style>

    <div id="tp-overlay">
        <div id="tp-modal">
            <button class="tp-close" onclick="closeTeleport()"><i class="fa-solid fa-xmark"></i></button>

            <div>
                <div
                    style="font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                    <i class="fa-solid fa-location-dot" style="color:#ffffff;font-size:15px;"></i> Choose Your City
                </div>
                <div style="font-size:12px;color:rgba(255,255,255,0.4);">Select a Moroccan city or drop a pin on the map
                </div>
            </div>

            <!-- Moroccan cities -->
            <div class="city-chips" id="city-chips">
                <!-- Injected by JS -->
            </div>

            <!-- Map -->
            <div>
                <div
                    style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-hand-pointer" style="color:#ffffff;font-size:10px;"></i>
                    Tap anywhere on the map to drop a pin
                </div>
                <div id="tp-map"></div>
            </div>

            <!-- Save button -->
            <button id="tp-save" onclick="saveLocation()">
                <i class="fa-solid fa-floppy-disk"></i>
                Save This Location
            </button>
        </div>
    </div>

    <!-- Toast -->
    <div id="tp-toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="tp-toast-text">Location updated!</span>
    </div>

    <script>
        (function () {
            const MOROCCO_CITIES = [
                { name: 'Casablanca', lat: 33.5731, lon: -7.5898 },
                { name: 'Rabat', lat: 34.0209, lon: -6.8416 },
                { name: 'Marrakech', lat: 31.6295, lon: -7.9811 },
                { name: 'FÃ¨s', lat: 34.0181, lon: -5.0078 },
                { name: 'Tanger', lat: 35.7595, lon: -5.8340 },
                { name: 'Agadir', lat: 30.4278, lon: -9.5981 },
                { name: 'MeknÃ¨s', lat: 33.8935, lon: -5.5473 },
                { name: 'Oujda', lat: 34.6867, lon: -1.9114 },
                { name: 'KÃ©nitra', lat: 34.2610, lon: -6.5802 },
                { name: 'TÃ©touan', lat: 35.5785, lon: -5.3684 },
                { name: 'Safi', lat: 32.2994, lon: -9.2372 },
                { name: 'El Jadida', lat: 33.2316, lon: -8.5007 },
                { name: 'Nador', lat: 35.1740, lon: -2.9287 },
                { name: 'Beni Mellal', lat: 32.3372, lon: -6.3498 },
                { name: 'Essaouira', lat: 31.5085, lon: -9.7595 },
                { name: 'Ouarzazate', lat: 30.9335, lon: -6.9370 },
                { name: 'Settat', lat: 33.0014, lon: -7.6196 },
                { name: 'BÃ©ni-Mellal', lat: 32.3372, lon: -6.3498 },
                { name: 'Larache', lat: 35.1932, lon: -6.1560 },
                { name: 'Dakhla', lat: 23.6848, lon: -15.9570 },
            ];

            let map = null, marker = null;
            let pendingLat = null, pendingLon = null, pendingName = null;

            function getCookie(n) { const v = document.cookie.match('(^|;) ?' + n + '=([^;]*)(;|$)'); return v ? v[2] : null; }

            function saveLocation() {
                if (pendingLat === null) return;
                document.cookie = 'qoon_lat=' + pendingLat + '; max-age=2592000; path=/';
                document.cookie = 'qoon_lon=' + pendingLon + '; max-age=2592000; path=/';

                // Update button label
                const lbl = document.getElementById('loc-label');
                if (lbl) lbl.textContent = pendingName || 'Updated';

                // Show toast
                const toast = document.getElementById('tp-toast');
                const toastTxt = document.getElementById('tp-toast-text');
                toastTxt.textContent = (pendingName ? pendingName : 'Location') + ' saved!';
                toast.classList.add('show');

                // Close modal after short delay, then reload
                setTimeout(() => closeTeleport(), 600);
                setTimeout(() => { toast.classList.remove('show'); location.reload(); }, 1800);
            }
            window.saveLocation = saveLocation;

            function setPending(lat, lon, name) {
                pendingLat = lat; pendingLon = lon; pendingName = name;

                // Deactivate all chips
                document.querySelectorAll('.city-chip').forEach(c => c.classList.remove('active'));

                // Show save button
                const btn = document.getElementById('tp-save');
                btn.classList.add('visible');
                btn.querySelector('span, i + *') // update label if needed
                document.getElementById('tp-save').innerHTML =
                    '<i class="fa-solid fa-floppy-disk"></i> Save â€” ' + (name || lat.toFixed(4) + ', ' + lon.toFixed(4));
            }

            // Build city chips
            const chipsEl = document.getElementById('city-chips');
            MOROCCO_CITIES.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'city-chip';
                btn.innerHTML = '<i class="fa-solid fa-location-dot" style="font-size:10px;color:#ffffff;"></i>' + c.name;
                btn.onclick = () => {
                    document.querySelectorAll('.city-chip').forEach(x => x.classList.remove('active'));
                    btn.classList.add('active');
                    if (!map) { initMap(); }
                    map.flyTo([c.lat, c.lon], 13, { duration: 1.4 });
                    setTimeout(() => placeMarker([c.lat, c.lon], c.name), 800);
                    setPending(c.lat, c.lon, c.name);
                };
                chipsEl.appendChild(btn);
            });

            window.openTeleport = function () {
                const ov = document.getElementById('tp-overlay');
                ov.style.display = 'flex';
                requestAnimationFrame(() => ov.classList.add('open'));
                if (!map) initMap();
                else setTimeout(() => map.invalidateSize(), 50);
            };
            window.closeTeleport = function () {
                const ov = document.getElementById('tp-overlay');
                ov.classList.remove('open');
                setTimeout(() => ov.style.display = 'none', 380);
            };
            document.getElementById('tp-overlay').addEventListener('click', e => {
                if (e.target === document.getElementById('tp-overlay')) closeTeleport();
            });

            function initMap() {
                const savedLat = getCookie('qoon_lat');
                const savedLon = getCookie('qoon_lon');
                const center = savedLat && savedLon ? [+savedLat, +savedLon] : [31.7917, -7.0926]; // Morocco center

                map = L.map('tp-map', { zoomControl: true }).setView(center, savedLat ? 12 : 6);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 20
                }).addTo(map);

                if (savedLat && savedLon) placeMarker([+savedLat, +savedLon], 'Current location');

                // Click on map â†’ drop pin + show save button
                map.on('click', function (e) {
                    const { lat, lng } = e.latlng;
                    placeMarker([lat, lng], lat.toFixed(4) + ', ' + lng.toFixed(4));
                    setPending(lat, lng, null);
                    // Reverse-geocode for name (optional, graceful fail)
                    fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
                        .then(r => r.json())
                        .then(d => {
                            const name = d.address?.city || d.address?.town || d.address?.village || d.address?.county || null;
                            if (name) {
                                pendingName = name;
                                document.getElementById('tp-save').innerHTML =
                                    '<i class="fa-solid fa-floppy-disk"></i> Save â€” ' + name;
                            }
                        }).catch(() => { });
                });
            }

            function placeMarker(ll, text) {
                if (marker) map.removeLayer(marker);
                const icon = L.divIcon({
                    className: '', iconSize: [20, 20], iconAnchor: [10, 10],
                    html: '<div style="width:20px;height:20px;background:#ffffff;border-radius:50%;border:3px solid #fff;box-shadow:0 0 16px #ffffff,0 0 6px rgba(0,0,0,0.6);"></div>'
                });
                marker = L.marker(ll, { icon }).addTo(map)
                    .bindPopup('<div style="font-family:Inter;font-size:13px;font-weight:600;padding:3px 2px;white-space:nowrap;color:#fff;">' + text + '</div>')
                    .openPopup();
            }

            // Auto-acquire GPS on first visit
            if (!getCookie('qoon_lat') && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(p => {
                    document.cookie = 'qoon_lat=' + p.coords.latitude + '; max-age=2592000; path=/';
                    document.cookie = 'qoon_lon=' + p.coords.longitude + '; max-age=2592000; path=/';
                }, () => { });
            }
        })();
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

                    // Fade out and reload
                    const overlay = document.getElementById('locationOverlay');
                    if (overlay) {
                        overlay.style.opacity = '0';
                        overlay.style.transition = 'opacity 0.5s ease';
                    }
                    
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
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
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

    <!-- ✨ Theme Toggle FAB -->
    <button class="theme-fab" id="qoon-theme-fab" aria-label="Toggle light/dark mode" onclick="qoonToggleTheme()">
        <i class="fab-icon" id="qoon-fab-icon"></i>
    </button>

    <script>
    /* ── Theme Toggle Button Logic ── */
    (function () {
        var DARK_ICON = '🌙';
        var LIGHT_ICON = '☀️';
        var htmlEl = document.documentElement;
        var fabIcon = document.getElementById('qoon-fab-icon');
        var fab = document.getElementById('qoon-theme-fab');

        function syncIcon() {
            if (!fabIcon) return;
            var isLight = htmlEl.classList.contains('light-mode');
            fabIcon.textContent = isLight ? DARK_ICON : LIGHT_ICON;
            fab.title = isLight ? 'Switch to dark mode' : 'Switch to light mode';
        }
        syncIcon();

        window.qoonToggleTheme = function () {
            var isLight = htmlEl.classList.contains('light-mode');
            if (isLight) {
                htmlEl.classList.remove('light-mode');
                localStorage.setItem('qoon_theme', 'dark');
            } else {
                htmlEl.classList.add('light-mode');
                localStorage.setItem('qoon_theme', 'light');
            }
            if (fab) {
                fab.classList.add('spinning');
                setTimeout(function () { fab.classList.remove('spinning'); }, 500);
            }
            syncIcon();
        };
    })();
    </script>
</body>
</html>


