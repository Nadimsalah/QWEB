<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$catId = intval($_GET['cat'] ?? 0);
$domain = $DomainNamee ?? 'https://qoon.app/dash/';

// --- 1. Fetch Sub-Categories of Kenz Mdinty ---
$subCategories = [];
if ($con) {
    // Assuming Kenz Mdinty is a special view of all 'Small' categories or specific ones
    $res = $con->query("SELECT * FROM Categories WHERE Type != 'Top' ORDER BY priority DESC LIMIT 15");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $subCategories[] = $row;
        }
    }
}

// --- 2. Fetch Stories & Reels ---
$reels = [];
if ($con) {
    $catFilter = $catId > 0
        ? " AND Shops.CategoryID = $catId "
        : " AND Categories.Type = 'Small' ";

    $reelSql = "
        SELECT SortID, Video, ShopName, ShopLogo, SourceType, MediaType FROM (
            SELECT ShopStory.StotyID AS SortID, ShopStory.StoryPhoto AS Video, Shops.ShopName, Shops.ShopLogo, 'story' AS SourceType, ShopStory.StotyType AS MediaType
            FROM ShopStory 
            JOIN Shops ON Shops.ShopID=ShopStory.ShopID
            JOIN Categories ON Categories.CategoryId = Shops.CategoryID
            WHERE ShopStory.StoryStatus='ACTIVE' AND Shops.Status='ACTIVE'
              AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
              $catFilter
            UNION ALL
            SELECT Posts.PostID AS SortID, Posts.Video AS Video, Shops.ShopName, Shops.ShopLogo, 'post' AS SourceType, 'VIDEO' AS MediaType
            FROM Posts
            JOIN Shops ON Shops.ShopID = Posts.ShopID
            JOIN Categories ON Categories.CategoryId = Shops.CategoryID
            WHERE Posts.PostStatus='ACTIVE' AND Shops.Status='ACTIVE'
              AND Posts.Video != '' AND Posts.Video != '0'
              AND Posts.Video NOT LIKE '%jibler.app%'
              $catFilter
        ) AS combined
        ORDER BY SortID DESC
        LIMIT 20
    ";
    $r = $con->query($reelSql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $reels[] = $row;
        }
    }

    // Fallback: if specific category had no reels, show all Small categories
    if (empty($reels) && $catId > 0) {
        $r2 = $con->query("
            SELECT SortID, Video, ShopName, ShopLogo, SourceType, MediaType FROM (
                SELECT ShopStory.StotyID AS SortID, ShopStory.StoryPhoto AS Video, Shops.ShopName, Shops.ShopLogo, 'story' AS SourceType, ShopStory.StotyType AS MediaType
                FROM ShopStory 
                JOIN Shops ON Shops.ShopID=ShopStory.ShopID
                JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                WHERE ShopStory.StoryStatus='ACTIVE' AND Shops.Status='ACTIVE'
                  AND ShopStory.StoryPhoto != '' AND ShopStory.StoryPhoto != '0'
                  AND Categories.Type = 'Small'
                UNION ALL
                SELECT Posts.PostID AS SortID, Posts.Video AS Video, Shops.ShopName, Shops.ShopLogo, 'post' AS SourceType, 'VIDEO' AS MediaType
                FROM Posts
                JOIN Shops ON Shops.ShopID = Posts.ShopID
                JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                WHERE Posts.PostStatus='ACTIVE' AND Shops.Status='ACTIVE'
                  AND Posts.Video != '' AND Posts.Video != '0'
                  AND Posts.Video NOT LIKE '%jibler.app%'
                  AND Categories.Type = 'Small'
            ) AS combined
            ORDER BY SortID DESC
            LIMIT 20
        ");
        if ($r2) {
            while ($row = $r2->fetch_assoc()) {
                $reels[] = $row;
            }
        }
    }
}

// Prepare JSON for JS logic
$reelJsonArr = [];
foreach ($reels as $reel) {
    $raw = str_replace('jibler.app', 'qoon.app', trim($reel['Video'] ?? ''));
    $ext = strtolower(pathinfo($raw, PATHINFO_EXTENSION));
    $mt = strtoupper(trim($reel['MediaType'] ?? ''));
    $isV = ($mt === 'VIDEO' || in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv']));
    $url = !empty($raw) && $raw !== '0' && $raw !== '-'
        ? (strpos($raw, 'http') !== false ? $raw : $domain . 'photo/' . $raw) : '';
    if (!$url) continue;
    $logo = trim($reel['ShopLogo'] ?? '');
    if ($logo && strpos($logo, 'http') === false) $logo = $domain . 'photo/' . $logo;
    $reelJsonArr[] = [
        'id' => (int) ($reel['SortID'] ?? 0),
        'type' => $reel['SourceType'] ?? 'post',
        'media' => $isV ? 'video' : 'image',
        'url' => $url,
        'logo' => $logo,
        'shop' => $reel['ShopName'] ?? 'Shop',
        'isVideo' => $isV,
    ];
}

// --- 3. Fetch Posts (Social Data & Products) ---
$posts = [];
if ($con) {
    $r = $con->query("
        SELECT Posts.*, Shops.ShopName, Shops.ShopLogo, Shops.ShopID,
               Foods.FoodID, Foods.FoodPrice
        FROM Posts 
        JOIN Shops ON Shops.ShopID = Posts.ShopID 
        JOIN Categories ON Categories.CategoryId = Shops.CategoryID
        LEFT JOIN Foods ON (Posts.ProductID = Foods.FoodID AND Foods.FoodID != 0)
        WHERE Posts.PostStatus='ACTIVE' AND Categories.Type = 'Small' AND (Posts.Video='' OR Posts.Video='0' OR Posts.Video IS NULL)
        ORDER BY Posts.CreatedAtPosts DESC 
        LIMIT 10
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $posts[] = $row;
        }
    }
}

// --- 4. User location from cookie ---
$userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float) $_COOKIE['qoon_lat'] : null;
$userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float) $_COOKIE['qoon_lon'] : null;
$locationRequired = (!$userLat || !$userLon);

function fullUrl($path, $domain) {
    if (!$path || $path === '0' || $path === 'NONE') return '';
    if (strpos($path, 'http') !== false) return $path;
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenz Mdinty · Discover Local Treasures</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #0a0a1f;
            --purple-glow: #6a11cb;
            --pink-glow: #ff0080;
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

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--pink-glow); }

        /* --- Hero Section --- */
        .kenz-header {
            position: relative;
            height: 70vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .kenz-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('kenz_bg.png') center/cover no-repeat;
            filter: brightness(0.7) saturate(1.2);
            z-index: 0;
            animation: slowZoom 60s infinite alternate linear;
        }

        @keyframes slowZoom {
            from { transform: scale(1); }
            to { transform: scale(1.15); }
        }

        .kenz-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 40%, var(--bg-deep));
            z-index: 1;
        }

        .header-content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 0 20px;
        }

        .header-content h1 {
            font-size: clamp(48px, 10vw, 110px);
            font-weight: 700;
            letter-spacing: -4px;
            line-height: 0.9;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, #ff75a0, #6a11cb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.5));
        }

        .header-content p {
            font-size: clamp(16px, 2vw, 22px);
            color: rgba(255,255,255,0.8);
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        /* --- Sections Layout --- */
        .page-container {
            position: relative;
            z-index: 20;
            margin-top: -100px;
            padding: 0 40px 100px;
        }

        .sec-wrap { margin-bottom: 80px; }

        .sec-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .sec-title {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sec-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--pink-glow);
            border-radius: 99px;
            box-shadow: 0 0 15px var(--pink-glow);
        }

        /* --- Categories Section (Synced with Home) --- */
        .categories-section {
            width: calc(100% + 40px);
            margin: 0 -40px 40px 0;
            padding: 40px 0;
            overflow: hidden;
        }

        .cat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px 0 0;
            margin-bottom: 32px;
        }

        .category-grid {
            display: flex;
            gap: 24px;
            padding: 0 40px 80px 0;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }
        .category-grid::-webkit-scrollbar { display: none; }

        .cat-card {
            flex: 0 0 auto;
            width: clamp(240px, 20vw, 280px);
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            scroll-snap-align: start;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background-image: radial-gradient(at 0% 0%, rgba(44, 181, 232, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(155, 45, 241, 0.15) 0px, transparent 50%);
        }

        .cat-card:hover { transform: translateY(-8px); background-color: rgba(255, 255, 255, 0.05); border-color: var(--pink-glow); box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6); }

        .cat-img-wrapper {
            width: 100%; aspect-ratio: 1; border-radius: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 24px;
            background: linear-gradient(90deg, rgba(44, 181, 232, 0.1) 25%, rgba(155, 45, 241, 0.3) 50%, rgba(74, 37, 225, 0.1) 75%);
            background-size: 200% 100%; animation: shimmer 3s infinite linear;
        }

        .cat-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .cat-card:hover .cat-img { transform: scale(1.1); }
        .cat-name-home { font-size: 20px; font-weight: 600; color: var(--text-main); line-height: 1.2; margin-bottom: 8px; }
        .cat-tag { font-size: 13px; color: var(--text-muted); background: rgba(255, 255, 255, 0.08); padding: 4px 12px; border-radius: 99px; display: inline-block; align-self: flex-start; }

        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }

        /* --- Stories / Reels (Synced with Home Logic) --- */
        .reels-section {
            width: calc(100% + 40px);
            margin: 0 -40px 40px 0;
            overflow: hidden;
        }
        .reels-track {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 40px 50px 0;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
        }
        .reels-track::-webkit-scrollbar { display: none; }

        .reel-card-real {
            width: 200px;
            aspect-ratio: 9/16;
            border-radius: 16px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #000;
            transition: transform .2s;
            scroll-snap-align: start;
        }
        .reel-card-real:hover { transform: scale(1.03); border-color: rgba(255,255,255,0.3); }

        .reel-shimmer {
            width: 200px;
            aspect-ratio: 9/16;
            border-radius: 16px;
            background: linear-gradient(90deg, #111 25%, #1a1a1a 50%, #111 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite linear;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* --- Social Feed (Sync with Home) --- */
        .feed-container { max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 40px; }
        .post-card-social { background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 32px; padding: 32px; display: flex; flex-direction: column; gap: 20px; transition: all 0.3s; }
        .post-card-social:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.2); }
        .post-header-social { display: flex; align-items: center; gap: 14px; }
        .post-avatar-social { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid var(--purple-glow); }
        .post-shop-info-social { flex: 1; }
        .post-shop-name-social { font-size: 17px; font-weight: 700; color: var(--text-main); }
        .post-time-social { font-size: 13px; color: var(--text-muted); }
        .post-text-social { font-size: 16px; line-height: 1.6; color: #efefef; }
        .post-img-social { width: 100%; border-radius: 20px; object-fit: cover; max-height: 550px; background: #000; }
        .post-actions-social { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; margin-top: 10px; }
        .action-group-social { display: flex; gap: 24px; }
        .action-btn-social { background: transparent; border: none; color: var(--text-muted); font-size: 16px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s; }
        .action-btn-social:hover { color: var(--pink-glow); }
        .order-btn-social { background: #fff; color: #000; padding: 10px 24px; border-radius: 99px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: all 0.2s; }
        .order-btn-social:hover { background: var(--pink-glow); color: #fff; transform: scale(1.05); }

        /* --- Navigation --- */
        .top-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 24px 40px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to bottom, rgba(10,10,31,0.8), transparent); backdrop-filter: blur(5px); }
        .btn-circle { width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; }
        .btn-circle:hover { background: var(--pink-glow); border-color: transparent; transform: scale(1.1); box-shadow: 0 0 20px var(--pink-glow); }

        /* --- Comment Modal --- */
        .comment-overlay {
            position: fixed;
            inset: 0;
            z-index: 20000;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(15px);
            display: none;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .comment-modal {
            width: 100%;
            max-width: 550px;
            background: #0f0f1f;
            border: 1px solid var(--glass-border);
            border-radius: 40px 40px 0 0;
            padding: 30px;
            height: 80vh;
            display: flex;
            flex-direction: column;
            transform: translateY(100%);
            transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .comment-feed { flex: 1; overflow-y: auto; padding-right: 10px; margin-bottom: 20px; }
        .comment-input-area {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.05);
            padding: 12px 20px;
            border-radius: 99px;
            border: 1px solid var(--glass-border);
        }
        .comment-input {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            outline: none;
            font-size: 15px;
        }
        .comment-send-btn {
            background: var(--pink-glow);
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .comment-send-btn:hover { transform: scale(1.1); box-shadow: 0 0 15px var(--pink-glow); }
    </style>
</head>
<body class="">

    <nav class="top-nav">
        <div style="display:flex; align-items:center; gap:20px;">
            <a href="index.php" class="btn-circle"><i class="fa-solid fa-arrow-left"></i></a>
            <img src="logo_qoon_white.png" alt="QOON" style="height:32px; width:auto; object-fit:contain;">
        </div>
        
        <div class="header-actions">
            <?php if (isset($_COOKIE['qoon_user_id'])): 
                $uName = $_COOKIE['qoon_user_name'] ?? 'User';
                $uPhoto = $_COOKIE['qoon_user_photo'] ?? '';
                $uPhotoUrl = "";
                if(!$uPhoto || $uPhoto == 'NONE' || $uPhoto == '0') {
                    $uPhotoUrl = "https://ui-avatars.com/api/?name=".urlencode($uName)."&background=random&color=fff";
                } else {
                    if (strpos($uPhoto, 'http') !== false) {
                        $uPhotoUrl = $uPhoto;
                    } else {
                        $uPhotoUrl = (strpos($uPhoto, 'photo/') !== false) ? $DomainNamee . $uPhoto : $DomainNamee . 'photo/' . $uPhoto;
                    }
                }
            ?>
                <div class="profile-menu-container" style="position:relative;">
                    <a href="javascript:void(0)" onclick="toggleProfileMenu(event)" class="profile-link" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; background:rgba(255,255,255,0.1); padding:5px 15px; border-radius:99px; border:1px solid rgba(255,255,255,0.1);">
                        <img src="<?= htmlspecialchars($uPhotoUrl) ?>" alt="Profile" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.3);">
                        <span style="font-weight:600; font-size:14px; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($uName) ?></span>
                    </a>
                    <!-- Simplified menu for Kenz -->
                    <div class="profile-dropdown" id="profileDropdown" style="position:absolute; top:110%; right:0; background:#111; border:1px solid rgba(255,255,255,0.1); border-radius:15px; padding:10px; display:none; flex-direction:column; min-width:160px; z-index:5000;">
                        <a href="orders.php" style="color:#fff; text-decoration:none; padding:10px; font-size:14px; display:flex; align-items:center; gap:10px;"><i class="fa-solid fa-clock-rotate-left"></i> Orders</a>
                        <a href="logout.php" style="color:#ff3b30; text-decoration:none; padding:10px; font-size:14px; display:flex; align-items:center; gap:10px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="openSignup()" style="padding: 8px 20px; border-radius: 99px; font-weight: 600; font-size: 13px; text-decoration: none; background: rgba(255,255,255,0.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); color: #fff; border: 1px solid rgba(255,255,255,0.18); display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; cursor: pointer;"><i class="fa-solid fa-right-to-bracket" style="font-size: 12px;"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="kenz-header">
        <div class="header-content">
            <h1>Kenz Mdinty</h1>
            <p>Unveiling the spirit of the city through local treasures, authentic stories, and exclusive urban finds.</p>
        </div>
    </header>

    <div class="page-container">

        <!-- Sub-Categories Row -->
        <section class="categories-section">
            <div class="cat-header">
                <h2 class="sec-title">Explore Hubs</h2>
            </div>
            <div class="category-grid">
                <?php foreach ($subCategories as $sc): ?>
                    <a href="category.php?cat=<?= $sc['CategoryId'] ?>" style="text-decoration:none; color:inherit;">
                        <div class="cat-card">
                            <div class="cat-img-wrapper">
                                <img src="<?= fullUrl($sc['Photo'], $domain) ?>" class="cat-img" onerror="this.src='https://ui-avatars.com/api/?name=C&background=6a11cb&color=fff'">
                            </div>
                            <div class="cat-name-home"><?= htmlspecialchars($sc['EnglishCategory'] ?? $sc['ArabCategory'] ?? 'Sub') ?></div>
                            <div class="cat-tag">Explore</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Dynamic Stories (Synced with Home Logic) -->
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
        <section class="sec-wrap">
            <div class="sec-title-row">
                <h2 class="sec-title">Reels & Stories</h2>
                <a href="#" style="color:var(--pink-glow); font-size:14px; font-weight:600; text-decoration:none;">View All</a>
            </div>
            <div class="reels-section">
                <div class="reels-track" id="reels-track">
                    <?php for($k=0;$k<8;$k++): ?><div class="reel-shimmer"></div><?php endfor; ?>
                </div>
            </div>
        </section>

        <!-- Social Feed -->
        <section class="sec-wrap">
            <div class="sec-title-row">
                <h2 class="sec-title">The City Beat</h2>
            </div>
            <div class="feed-container">
                <?php foreach ($posts as $pst): ?>
                    <div class="post-card-social">
                        <div class="post-header-social">
                            <img src="<?= fullUrl($pst['ShopLogo'], $domain) ?>" class="post-avatar-social" onerror="this.src='https://ui-avatars.com/api/?name=S&background=6a11cb&color=fff'" onclick="window.location.href='shop.php?id=<?= $pst['ShopID'] ?>'" style="cursor:pointer;">
                            <div class="post-shop-info-social" onclick="window.location.href='shop.php?id=<?= $pst['ShopID'] ?>'" style="cursor:pointer;">
                                <div class="post-shop-name-social"><?= htmlspecialchars($pst['ShopName']) ?></div>
                                <div class="post-time-social">Authentic Selection</div>
                            </div>
                        </div>
                        <div class="post-text-social"><?= nl2br(htmlspecialchars($pst['PostText'])) ?></div>
                        <?php if ($pst['PostPhoto']): ?>
                            <img src="<?= fullUrl($pst['PostPhoto'], $domain) ?>" class="post-img-social" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div class="post-actions-social">
                            <div class="action-group-social">
                                <button class="action-btn-social" onclick="window.handleLike(this)"><i class="fa-regular fa-heart"></i> <?= number_format($pst['PostLikes']) ?></button>
                                <button class="action-btn-social" onclick="openCommentModal('<?= $pst['PostId'] ?>', '<?= addslashes($pst['ShopName']) ?>')">
                                    <i class="fa-regular fa-comment"></i> <?= number_format($pst['Postcomments']) ?>
                                </button>
                                <button class="action-btn-social"><i class="fa-regular fa-paper-plane"></i></button>
                            </div>
                            <?php if ($pst['FoodID']): ?>
                                <button class="order-btn-social" onclick="location.href='shop.php?id=<?= $pst['ShopID'] ?>&product=<?= $pst['FoodID'] ?>'">
                                    Order Now · <?= $pst['FoodPrice'] ?> MAD
                                </button>
                            <?php else: ?>
                                <button class="order-btn-social" onclick="location.href='shop.php?id=<?= $pst['ShopID'] ?>'">Explore Shop</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
    <?php endif; ?>

    <!-- Comment Modal -->
    <div class="comment-overlay" id="comment-overlay">
        <div class="comment-modal" id="comment-modal">
            <div class="comment-header">
                <h3 style="font-size: 20px; font-weight: 700;">Comments</h3>
                <button onclick="closeCommentModal()" style="background:transparent; border:none; color:#fff; font-size:24px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="comment-feed" id="comment-feed">
                <!-- Comments load here -->
            </div>
            <div class="comment-input-area">
                <input type="text" class="comment-input" id="comment-input" placeholder="Add a comment...">
                <button class="comment-send-btn" id="comment-send-btn" onclick="sendComment()">
                    <i class="fa-solid fa-arrow-up"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- JS Logic: Cloned from Home Screen -->
    <script>
        (function () {
            const REELS_DATA = <?= json_encode($reelJsonArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
            const track = document.getElementById('reels-track');
            const shimmers = Array.from(track.querySelectorAll('.reel-shimmer'));
            const BATCH = 4;
            let idx = 0;

            function buildCard(r) {
                const card = document.createElement('div');
                card.className = 'reel-card-real';
                card.onclick = () => location.href = 'category_reels.php?cat=<?= $catId ?>&id=' + r.id + '&type=' + r.type;

                if (r.isVideo) {
                    const vid = document.createElement('video');
                    vid.muted = true; vid.playsInline = true; vid.preload = 'none';
                    vid.dataset.src = r.url;
                    vid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;opacity:0;transition:opacity .4s;';
                    vid.onerror = () => { vid.style.display='none'; img2.style.display='block'; };
                    const img2 = document.createElement('img');
                    img2.src = r.logo;
                    img2.style.cssText = 'display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;';
                    card.appendChild(vid); card.appendChild(img2);
                    const badge = document.createElement('div');
                    badge.innerHTML = '<i class="fa-solid fa-play" style="margin-left:3px"></i>';
                    badge.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:2;width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;border:1px solid rgba(255,255,255,0.3);';
                    card.appendChild(badge);
                } else {
                    const img = document.createElement('img');
                    img.loading = 'lazy'; img.src = r.url; img.alt = r.shop;
                    img.onerror = () => { img.src = r.logo; };
                    card.appendChild(img);
                }

                const grad = document.createElement('div');
                grad.style.cssText = 'position:absolute;bottom:0;left:0;right:0;height:55%;background:linear-gradient(transparent,rgba(0,0,0,0.88));z-index:1;pointer-events:none;';
                card.appendChild(grad);

                const brand = document.createElement('div');
                brand.style.cssText = 'position:absolute;bottom:12px;left:12px;right:12px;z-index:2;display:flex;align-items:center;gap:7px;pointer-events:none;';
                brand.innerHTML = '<img src="' + r.logo + '" onerror="this.src=\'https://ui-avatars.com/api/?name=S\'" style="width:24px;height:24px;border-radius:50%;border:1.5px solid #fff;object-fit:cover;flex-shrink:0;">'
                    + '<span style="font-size:11px;font-weight:600;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,0.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + r.shop + '</span>';
                card.appendChild(brand);

                return card;
            }

            function renderBatch() {
                const end = Math.min(idx + BATCH, REELS_DATA.length);
                for (; idx < end; idx++) {
                    const card = buildCard(REELS_DATA[idx]);
                    if (shimmers[idx]) track.replaceChild(card, shimmers[idx]);
                    else track.appendChild(card);
                }
                if (idx < REELS_DATA.length) {
                    requestAnimationFrame(renderBatch);
                } else {
                    // Remove any leftover shimmers
                    shimmers.slice(idx).forEach(s => s.remove());
                    initVideoThumbs();
                }
            }

            function initVideoThumbs() {
                const vids = track.querySelectorAll('video[data-src]');
                if (!('IntersectionObserver' in window)) { vids.forEach(loadThumb); return; }
                const obs = new IntersectionObserver((entries, o) => {
                    entries.forEach(e => { if (e.isIntersecting) { loadThumb(e.target); o.unobserve(e.target); } });
                }, { rootMargin: '0px 400px 0px 400px' });
                vids.forEach(v => obs.observe(v));
            }

            function loadThumb(vid) {
                if (vid.dataset.loaded) return;
                vid.dataset.loaded = '1'; vid.preload = 'auto';
                vid.addEventListener('loadedmetadata', () => { vid.currentTime = Math.min(0.5, vid.duration || 0.5); }, { once: true });
                vid.addEventListener('seeked', () => { vid.style.opacity = '1'; }, { once: true });
                setTimeout(() => { vid.style.opacity = '1'; }, 2000);
                vid.src = vid.dataset.src; vid.load();
            }

            if (REELS_DATA.length > 0) renderBatch();
            else track.innerHTML = '<div style="color:rgba(255,255,255,0.4); padding:20px;">No moments found for this hub yet.</div>';

            /* --- Comment System Logic --- */
            const commentOverlay = document.getElementById('comment-overlay');
            const commentModal = document.getElementById('comment-modal');
            const commentFeed = document.getElementById('comment-feed');
            const commentInput = document.getElementById('comment-input');
            let currentPostId = null;

            window.handleLike = function(btn) {
                const isLoggedIn = <?= isset($_COOKIE['qoon_user_id']) ? 'true' : 'false' ?>;
                if (!isLoggedIn) {
                    window.location.href = 'index.php?auth_required=1';
                    return;
                }
                const icon = btn.querySelector('i');
                if (icon.classList.contains('fa-regular')) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                    icon.style.color = '#ff3b30';
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                    icon.style.color = '';
                }
            };

            window.openCommentModal = function(postId, shopName) {
                const isLoggedIn = <?= isset($_COOKIE['qoon_user_id']) ? 'true' : 'false' ?>;
                if (!isLoggedIn) {
                    window.location.href = 'index.php?auth_required=1';
                    return;
                }
                currentPostId = postId;
                commentOverlay.style.display = 'flex';
                setTimeout(() => {
                    commentOverlay.style.opacity = '1';
                    commentModal.style.transform = 'translateY(0)';
                }, 10);
                
                commentFeed.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i></div>';
                
                let fd = new FormData();
                fd.append('PostID', postId);
                
                fetch('GetPostCommentsWeb.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(json => {
                    if (json.success && json.data.length > 0) {
                        let html = '';
                        json.data.forEach(c => {
                            let userName = (c.AuthorName || c.name || 'User');
                            let color = stringToColor(userName);
                            let photo = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff`;

                            if (c.UserPhoto && c.UserPhoto !== 'NONE' && c.UserPhoto !== '0') {
                                if (c.UserPhoto.includes('http')) {
                                    photo = c.UserPhoto;
                                } else {
                                    let base = '<?= $DomainNamee ?>';
                                    photo = c.UserPhoto.startsWith('photo/') ? base + c.UserPhoto : base + 'photo/' + c.UserPhoto;
                                }
                            }

                            html += `
                                <div style="display:flex; gap:12px; margin-bottom:20px;">
                                    <img src="${photo}" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff'">
                                    <div>
                                        <div style="font-size:14px; font-weight:700;">${userName} <span style="font-weight:400; color:rgba(255,255,255,0.4); font-size:12px; margin-left:8px;">${timeAgo(c.CreatedAtComments)}</span></div>
                                        <div style="font-size:14px; color:rgba(255,255,255,0.8); margin-top:4px;">${c.CommentText}</div>
                                    </div>
                                </div>
                            `;
                        });
                        commentFeed.innerHTML = html;
                    } else {
                        commentFeed.innerHTML = '<div style="text-align:center; padding:40px; color:rgba(255,255,255,0.4);">No comments yet.</div>';
                    }
                });
            };

            window.closeCommentModal = function() {
                commentOverlay.style.opacity = '0';
                commentModal.style.transform = 'translateY(100%)';
                setTimeout(() => { commentOverlay.style.display = 'none'; }, 400);
            };

            window.sendComment = function() {
                const text = commentInput.value.trim();
                const userId = getCookie('qoon_user_id');

                if (!userId || userId === '0') {
                    alert('Please log in to leave a comment.');
                    if(window.openSignup) window.openSignup();
                    return;
                }

                if (!text || !currentPostId) return;

                const btn = document.getElementById('comment-send-btn');
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

                let fd = new FormData();
                fd.append('PostID', currentPostId);
                fd.append('CommentText', text);
                fd.append('UserID', userId);
                fd.append('ShopID', '0');

                fetch('AddComment.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        commentInput.value = '';
                        openCommentModal(currentPostId);
                    }
                })
                .finally(() => {
                    btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
                });
            };

            function getCookie(name) {
                let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                if (match) return match[2];
                return null;
            }

            function timeAgo(date) {
                if (!date) return 'now';
                const now = new Date();
                const past = new Date(date.replace(' ', 'T'));
                let diff = Math.floor((now - past) / 1000);
                
                if (diff < 0) diff = 0; // Handle localized clock drift
                if (diff < 30) return 'now';
                if (diff < 60) return diff + 's';
                if (diff < 3600) return Math.floor(diff / 60) + 'm';
                if (diff < 86400) return Math.floor(diff / 3600) + 'h';
                return Math.floor(diff / 86400) + 'd';
            }

            function stringToColor(str) {
                let hash = 0;
                for (let i = 0; i < str.length; i++) { hash = str.charCodeAt(i) + ((hash << 5) - hash); }
                let color = '';
                for (let i = 0; i < 3; i++) {
                    const value = (hash >> (i * 8)) & 0xFF;
                    color += ('00' + value.toString(16)).substr(-2);
                }
                return color;
            }

            window.toggleProfileMenu = function(e) {
                if(e) e.stopPropagation();
                const dropdown = document.getElementById('profileDropdown');
                const isVisible = dropdown.style.display === 'flex';
                dropdown.style.display = isVisible ? 'none' : 'flex';
            };

            document.addEventListener('click', function(e) {
                const container = document.querySelector('.profile-menu-container');
                const dropdown = document.getElementById('profileDropdown');
                if (container && !container.contains(e.target)) {
                    if(dropdown) dropdown.style.display = 'none';
                }
            });

            commentInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendComment(); });
            commentOverlay.addEventListener('click', (e) => { if (e.target === commentOverlay) closeCommentModal(); });
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

    <!-- Firebase SDK (for Google Login) -->
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
        } catch (e) {}
    </script>

    <?php include 'includes/modals/auth.php'; ?>

    <script>
        /* --- GOOGLE LOGIN --- */
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
                    .catch(() => { btn.innerHTML = originalHtml; alert("Could not connect to server."); });
            }).catch((error) => {
                if (error.code === 'auth/unauthorized-domain') {
                    alert("Error: This domain is not authorized in Firebase Console.");
                } else {
                    alert("Google Login failed: " + error.message);
                }
            });
        };
    </script>
</body>
</html>
