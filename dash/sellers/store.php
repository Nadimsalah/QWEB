<?php
session_start();
require_once __DIR__ . '/../api_conn.php';

$shopID = isset($_GET['id']) ? (int)$_GET['id'] : ((isset($_SESSION['SellerID'])) ? (int)$_SESSION['SellerID'] : 0);

if(isset($_GET['u'])) {
    $u = $con->real_escape_string($_GET['u']);
    $q = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$u' LIMIT 1");
    if($q && $q->num_rows > 0) $shopID = (int)$q->fetch_assoc()['ShopID'];
}

$shopQ = $con->query("SELECT * FROM Shops WHERE ShopID = $shopID");
$shop = $shopQ->fetch_assoc();
if (!$shop) die("Store Not Found.");

function normalizeMediaUrl($raw) {
    if (!$raw) return null;
    $raw = trim($raw);
    if (in_array(strtolower($raw), ['', 'none', '0', 'null'])) return null;
    if (str_starts_with($raw, 'http') && !str_contains($raw, 'jibler') && !str_contains($raw, 'qoon') && !str_contains($raw, 'localhost') && !str_contains($raw, '127.0.0.1')) return $raw;
    $parsed = parse_url($raw);
    $path = ltrim($parsed['path'] ?? $raw, '/');
    $domains = ['jibler.app/', 'jibler.ma/', 'qoon.app/', 'www.jibler.app/', 'www.jibler.ma/', 'dashboard.jibler.ma/', 'localhost/', 'localhost:8000/', '127.0.0.1/'];
    foreach ($domains as $d) { if (str_starts_with($path, $d)) { $path = substr($path, strlen($d)); break; } }
    if (str_starts_with($path, 'db/db/')) $path = substr($path, 6);
    else if (str_starts_with($path, 'db/')) $path = substr($path, 3);
    if (preg_match('/^(w-|p-|s-|v-)/', $path) && !str_contains($path, '/')) $path = 'dash/photo/' . $path;
    if (str_starts_with($path, 'photo/')) $path = 'dash/' . $path;
    return 'https://qoon.app/' . ltrim($path, '/');
}

$shopName = $shop['ShopName'];
$shopLogo = normalizeMediaUrl($shop['ShopLogo']) ?: "https://ui-avatars.com/api/?name=".urlencode($shopName)."&background=6C5CE7&color=FFF&bold=true";

// Fetch Active Stories
$storiesQ = $con->query("SELECT * FROM ShopStory WHERE ShopID = '$shopID' AND StoryStatus = 'ACTIVE' ORDER BY StotyID DESC");
$stories = [];
if($storiesQ) while ($st = $storiesQ->fetch_assoc()) $stories[] = $st;

// Fetch Posts (Reels = posts with video)
$postsQ = $con->query("SELECT * FROM Posts WHERE ShopID = '$shopID' AND PostStatus = 'ACTIVE' ORDER BY PostId DESC LIMIT 20");
$reels = []; $posts_img = [];
if($postsQ) while ($po = $postsQ->fetch_assoc()) {
    if (!empty($po['Video']) && !in_array(strtoupper(trim($po['Video'])), ['NONE', '0', 'NULL'])) $reels[] = $po;
    else $posts_img[] = $po;
}

// Fetch Products
$catQ = $con->query("SELECT * FROM ShopsCategory WHERE ShopID = '$shopID'");
$categories = [];
if($catQ) while ($c = $catQ->fetch_assoc()) $categories[] = $c;

$prodQ = $con->query("
    SELECT Foods.*, ShopsCategory.CategoryName as CatName 
    FROM Foods 
    JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID 
    WHERE ShopsCategory.ShopID = '$shopID' 
    ORDER BY Foods.FoodID DESC
");
$products = [];
if($prodQ) while ($p = $prodQ->fetch_assoc()) $products[] = $p;

// Fetch all extras for this shop
$extrasMap = []; // keyed by ProductID
if (!empty($products)) {
    $productIDs = implode(',', array_map(fn($p) => (int)$p['FoodID'], $products));
    $extraCatQ = $con->query("SELECT * FROM ExtraCategory WHERE ProductID IN ($productIDs) ORDER BY ExtraCategoryID ASC");
    $catRows = [];
    if ($extraCatQ) while ($ec = $extraCatQ->fetch_assoc()) $catRows[] = $ec;

    if (!empty($catRows)) {
        $catIDs = implode(',', array_map(fn($c) => (int)$c['ExtraCategoryID'], $catRows));
        $extraValQ = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID IN ($catIDs) ORDER BY ExtraInSideCategotyID ASC");
        $valRows = [];
        if ($extraValQ) while ($ev = $extraValQ->fetch_assoc()) $valRows[] = $ev;

        // Index values by category
        $valsByGroup = [];
        foreach ($valRows as $v) $valsByGroup[(int)$v['ExtraCategoryID']][] = $v;

        // Build extrasMap
        foreach ($catRows as $cat) {
            $pid   = (string)$cat['ProductID'];
            $catID = (int)$cat['ExtraCategoryID'];
            $items = [];
            foreach (($valsByGroup[$catID] ?? []) as $v) {
                $parts    = explode('|', $v['Name'], 2);
                $dispName = $parts[0];
                $colorHex = (isset($parts[1]) && preg_match('/^#[0-9A-Fa-f]{6}$/', $parts[1])) ? $parts[1] : null;
                $items[] = [
                    'id'    => (int)$v['ExtraInSideCategotyID'],
                    'name'  => $dispName,
                    'color' => $colorHex,
                    'price' => (float)$v['Price']
                ];
            }
            if (!isset($extrasMap[$pid])) $extrasMap[$pid] = [];
            $extrasMap[$pid][] = [
                'id'    => $catID,
                'name'  => $cat['ExtraCategoryName'],
                'multy' => $cat['Multy'],
                'items' => $items
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($shopName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #fff; -webkit-font-smoothing: antialiased; color: #1a1a2e; }

/* ── LAYOUT ── */
.layout { display: flex; min-height: 100vh; }
.main-content {
    flex: 1; min-width: 0;
    margin-left: 80px;
    transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.layout.expanded .main-content { margin-left: 260px; }
@media (max-width: 768px) {
    .main-content { margin-left: 0 !important; padding-bottom: 80px; }
}

/* ── SIDEBAR ── */
.sidebar {
    position: fixed;
    top: 0; left: 0; bottom: 0;
    width: 80px;
    background: #fff;
    border-right: 1px solid #f0f0f0;
    z-index: 200;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    box-shadow: 2px 0 20px rgba(0,0,0,0.04);
}
.layout.expanded .sidebar { width: 260px; }
@media (max-width: 768px) { .sidebar { display: none; } }

/* Mobile Brand Header */
.mobile-brand-header {
    display: none; align-items: center; gap: 12px;
    padding: 16px 24px; background: #fff;
}
.mobile-brand-logo { width: 44px; height: 44px; border-radius: 14px; object-fit: cover; box-shadow: 0 4px 12px rgba(108,92,231,0.15); }
.mobile-brand-info { display: flex; flex-direction: column; gap: 2px; }
.mobile-brand-name { font-size: 16px; font-weight: 800; color: #111; line-height: 1; }
.mobile-brand-handle { font-size: 13px; font-weight: 600; color: #a0a0b0; line-height: 1; }
@media (max-width: 768px) { .mobile-brand-header { display: flex; } }

/* Top branding area */
.sb-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 24px 20px 22px;
    width: 100%;
    flex-shrink: 0;
    border-bottom: 1px solid #f5f5f5;
    margin-bottom: 12px;
    overflow: hidden;
    white-space: nowrap;
}
.sb-logo {
    width: 40px; height: 40px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(108,92,231,0.2);
}
.sb-brand-name {
    font-size: 14px;
    font-weight: 800;
    color: #111;
    letter-spacing: -0.3px;
    opacity: 0;
    transition: opacity 0.3s 0.05s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
}
.layout.expanded .sb-brand-name { opacity: 1; }

/* Nav */
.sb-nav {
    display: flex; flex-direction: column;
    gap: 2px; width: 100%;
    padding: 0 12px;
    flex: 1;
}

/* Nav label above group */
.sb-group-label {
    font-size: 9px;
    font-weight: 800;
    color: #a0a0b0;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    padding: 8px 8px 4px;
    opacity: 0;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.layout.expanded .sb-group-label { opacity: 1; }

/* Nav item */
.sb-item {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 0;
    border-radius: 14px;
    cursor: pointer;
    border: none;
    background: transparent;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #7a7a8c;
    transition: all 0.2s;
    width: 100%;
    text-align: left;
    white-space: nowrap;
    position: relative;
    overflow: visible;
}
.sb-item:hover { background: #f8f8fc; color: #111; }
.sb-item.active { background: rgba(108,92,231,0.06); color: #6C5CE7; }

/* Icon box */
.sb-icon {
    width: 56px; height: 46px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.25s;
    color: #999;
    position: relative;
}
.sb-item:hover .sb-icon { color: #111; }
.sb-item.active .sb-icon {
    color: #6C5CE7;
}

/* Tooltip (shown in collapsed mode on hover) */
.sb-item .sb-tip {
    display: none;
    position: absolute;
    left: 68px; top: 50%;
    transform: translateY(-50%);
    background: #111;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 10px;
    white-space: nowrap;
    pointer-events: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 9999;
}
.sb-item .sb-tip::before {
    content: '';
    position: absolute;
    right: 100%; top: 50%;
    transform: translateY(-50%);
    border: 5px solid transparent;
    border-right-color: #111;
}
.layout:not(.expanded) .sb-item:hover .sb-tip { display: block; }

/* Label */
.sb-label {
    opacity: 0; width: 0;
    transition: opacity 0.25s 0.05s;
    pointer-events: none;
    font-size: 14px; font-weight: 700;
    color: inherit; overflow: hidden;
}
.layout.expanded .sb-label { opacity: 1; width: auto; }

/* Badge */
.sb-badge {
    margin-left: auto; margin-right: 10px;
    min-width: 20px; height: 20px;
    background: #ef4444;
    color: #fff; font-size: 10px; font-weight: 900;
    border-radius: 50px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 5px;
    opacity: 0; transition: opacity 0.25s;
    flex-shrink: 0;
}
.layout.expanded .sb-badge { opacity: 1; }

/* Divider */
.sb-divider {
    width: calc(100% - 24px);
    height: 1px;
    background: #f0f0f0;
    margin: 10px auto;
}

/* Bottom */
.sb-bottom { width: 100%; padding: 0 12px 16px; }
.sb-store-item {
    display: flex; align-items: center; gap: 0;
    padding: 10px 0; border-radius: 14px;
    cursor: default; overflow: hidden;
}
.sb-store-avatar {
    width: 56px; height: 46px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sb-store-avatar img {
    width: 28px; height: 28px;
    border-radius: 10px; object-fit: cover;
}
.sb-store-info {
    opacity: 0; width: 0; overflow: hidden;
    transition: opacity 0.25s, width 0.25s;
}
.layout.expanded .sb-store-info { opacity: 1; width: auto; }
.sb-store-name {
    font-size: 12px; font-weight: 700; color: #7a7a8c;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 140px;
}
.sb-online-dot {
    width: 6px; height: 6px;
    border-radius: 50%; background: #22c55e;
    display: inline-block; margin-right: 5px;
}

/* ── BOTTOM TAB BAR (mobile) ── */
.bottom-tabs {
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0; z-index: 200;
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-top: 1px solid #f0f0f0;
    padding: 10px 8px max(12px, env(safe-area-inset-bottom));
    box-shadow: 0 -12px 40px rgba(0,0,0,0.06);
}
@media (max-width: 768px) { .bottom-tabs { display: flex; } }
.bt-item {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 4px;
    cursor: pointer; border: none; background: none;
    font-family: 'Inter', sans-serif;
    padding: 4px 0; transition: transform 0.15s;
}
.bt-item:active { transform: scale(0.88); }
.bt-icon {
    width: 34px; height: 34px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    color: #999;
    position: relative;
}
.bt-item.active .bt-icon {
    color: #6C5CE7;
    background: rgba(108,92,231,0.1);
}
.bt-label {
    font-size: 10px; font-weight: 700;
    color: #a0a0b0;
    transition: color 0.2s;
}
.bt-item.active .bt-label { color: #6C5CE7; }


/* ── STORIES SECTION ── */
.stories-section {
    background: #fff;
    border-bottom: 1px solid #f5f5f5;
    padding: 40px 0 48px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Section header same as products */
.stories-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 0 24px;
    margin-bottom: 28px;
    gap: 16px;
}
.stories-head-title {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: -1px;
    color: #111;
    line-height: 1;
}
.stories-head-title span {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #aaa;
    letter-spacing: 0;
    margin-bottom: 6px;
}
.stories-head-count {
    font-size: 12px;
    font-weight: 700;
    color: #6C5CE7;
    background: #f0eefe;
    padding: 6px 14px;
    border-radius: 50px;
    white-space: nowrap;
    flex-shrink: 0;
}

.stories-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 8px 24px 12px;
    scrollbar-width: none;
}
.stories-scroll::-webkit-scrollbar { display: none; }

/* ── STORY CARD ── */
.story-card {
    flex-shrink: 0;
    width: 190px;
    height: 330px;
    border-radius: 28px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    background: #1a1a2e;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    transition: transform 0.35s cubic-bezier(0.34,1.4,0.64,1), box-shadow 0.35s;
}
.story-card:hover {
    transform: scale(1.04) translateY(-6px);
    box-shadow: 0 28px 70px rgba(0,0,0,0.24);
}
.story-card img.story-bg {
    width: 100%; height: 100%; object-fit: cover;
    position: absolute; inset: 0;
    transition: transform 0.55s ease;
}
.story-card:hover img.story-bg { transform: scale(1.07); }

/* Rich gradient overlay */
.story-card .story-gradient {
    position: absolute; inset: 0;
    background: linear-gradient(
        180deg,
        rgba(0,0,0,0.32) 0%,
        transparent 30%,
        transparent 52%,
        rgba(0,0,0,0.80) 100%
    );
}

/* Avatar ring */
.story-avatar-wrap {
    position: absolute; top: 14px; left: 14px;
    width: 52px; height: 52px;
    border-radius: 50%;
    padding: 2.5px;
    background: linear-gradient(135deg, #f9ce34, #ee2a7b, #6228d7);
    box-shadow: 0 4px 16px rgba(238,42,123,0.5);
}
.story-avatar {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; border: 3px solid #fff;
}

/* Story type badge */
.story-type-badge {
    position: absolute; top: 14px; right: 14px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff; font-size: 10px; font-weight: 800;
    padding: 4px 10px; border-radius: 50px;
    letter-spacing: 0.5px; text-transform: uppercase;
}

/* Label area */
.story-label {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 16px 16px 20px;
    font-size: 14px; font-weight: 800; color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    line-height: 1.35;
}
.story-sub {
    font-size: 11px; font-weight: 600;
    color: rgba(255,255,255,0.65);
    margin-top: 3px;
    display: block;
    white-space: nowrap;
}

/* Video play icon */
.reel-play {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,0.16);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 2px solid rgba(255,255,255,0.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.25s, background 0.25s;
}
.story-card:hover .reel-play {
    transform: translate(-50%, -50%) scale(1.14);
    background: rgba(255,255,255,0.28);
}
.reel-play svg { fill: #fff; width: 26px; height: 26px; margin-left: 4px; }

/* Add Story card */
.add-story-card {
    flex-shrink: 0; width: 190px; height: 330px;
    border-radius: 28px; overflow: hidden; position: relative;
    cursor: pointer; background: #f8f8fc;
    border: 2px dashed #e0e0ee;
    display: flex; flex-direction: column;
    align-items: center; justify-content: flex-end;
    padding-bottom: 22px;
    transition: border-color 0.2s, background 0.2s, transform 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.add-story-card:hover { border-color: #6C5CE7; background: #f3f0ff; transform: translateY(-5px); }
.add-story-top {
    width: 100%; height: 65%; background: linear-gradient(135deg, #e8e5fd 0%, #f3f0ff 100%);
    display: flex; align-items: center; justify-content: center;
    position: absolute; top: 0; left: 0;
}
.add-story-plus {
    width: 56px; height: 56px; border-radius: 50%;
    background: #6C5CE7; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; font-weight: 300; line-height: 1;
    box-shadow: 0 8px 28px rgba(108,92,231,0.45);
}
.add-story-label {
    font-size: 13px; font-weight: 800; color: #1a1a2e;
    position: absolute; bottom: 20px; text-align: center; width: 100%;
}

/* ── PRODUCTS SECTION ── */
.products-section {
    padding: 48px 24px 64px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ── PRODUCTS HEADER ── */
.products-header {
    margin-bottom: 20px;
}
.products-title {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: -1px;
    color: #111;
    line-height: 1;
}
.products-title span {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #a0a0b0;
    letter-spacing: 1px;
    margin-bottom: 6px;
    text-transform: uppercase;
}

/* ── CATEGORIES NAV ── */
.cats-wrap {
    margin-bottom: 32px;
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 16px 0;
    margin-left: -24px;
    margin-right: -24px;
}
.cats-scroll {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    scrollbar-width: none;
    padding: 0 24px;
}
.cats-scroll::-webkit-scrollbar { display: none; }
.cat-pill {
    padding: 10px 20px;
    border-radius: 50px;
    background: #f4f4f8;
    color: #7a7a8c;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: inherit;
    flex-shrink: 0;
}
.cat-pill:hover {
    background: #ebebf2;
    color: #111;
}
.cat-pill.active {
    background: #111;
    color: #fff;
    box-shadow: 0 6px 16px rgba(0,0,0,0.18);
}

/* Product Grid — 2 col → 3 col → 4 col */
.products-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (min-width: 600px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
@media (min-width: 960px) { .products-grid { grid-template-columns: repeat(4, 1fr); gap: 28px; } }

/* Product Card */
.p-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.64, 1), box-shadow 0.3s;
    display: none;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.p-card.visible {
    display: flex;
    flex-direction: column;
    animation: fadeUp 0.4s ease both;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.p-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 60px rgba(108,92,231,0.14);
}

/* Card image */
.p-img-wrap {
    aspect-ratio: 4/5;
    overflow: hidden;
    background: #f8f8fc;
    position: relative;
    flex-shrink: 0;
}
.p-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.p-card:hover .p-img { transform: scale(1.08); }

/* Discount badge */
.p-discount {
    position: absolute; top: 12px; left: 12px;
    background: #ef4444; color: #fff;
    font-size: 10px; font-weight: 900;
    padding: 4px 10px; border-radius: 50px;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 10px rgba(239,68,68,0.3);
}

/* Card body */
.p-body {
    padding: 16px 18px 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}
.p-cat {
    font-size: 10px; font-weight: 800;
    color: #6C5CE7; text-transform: uppercase;
    letter-spacing: 0.8px;
}
.p-name {
    font-size: 14px; font-weight: 800;
    color: #111; line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.p-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 12px;
}
.p-prices { display: flex; flex-direction: column; gap: 1px; }
.p-price {
    font-size: 16px; font-weight: 900; color: #111;
    letter-spacing: -0.3px;
}
.p-old {
    font-size: 11px; color: #ccc;
    text-decoration: line-through; font-weight: 600;
}
.p-add {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #111; color: #fff;
    border: none; font-size: 22px;
    font-weight: 300; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s, background 0.2s;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
}
.p-add:hover { background: #6C5CE7; transform: scale(1.12); box-shadow: 0 6px 20px rgba(108,92,231,0.4); }
.p-add:active { transform: scale(0.9); }

/* Load More */
.load-more-wrap {
    margin-top: 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.load-more-progress {
    font-size: 12px; color: #bbb; font-weight: 600;
}
.load-more-btn {
    display: inline-flex; align-items: center; gap: 10px;
    background: #111; color: #fff;
    border: none;
    padding: 16px 40px; border-radius: 50px;
    font-size: 14px; font-weight: 800; cursor: pointer;
    transition: all 0.25s;
    letter-spacing: -0.2px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.load-more-btn:hover {
    background: #6C5CE7;
    box-shadow: 0 12px 32px rgba(108,92,231,0.35);
    transform: translateY(-2px);
}
.load-more-btn.loading { opacity: 0.55; pointer-events: none; }
.load-more-btn .spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: none;
}
.load-more-btn.loading .spinner { display: block; }
@keyframes spin { to { transform: rotate(360deg); } }
.viewer {
    display: none; position: fixed; inset: 0; z-index: 999999;
    background: #000; flex-direction: column;
    align-items: center; justify-content: center;
}
.viewer.open { display: flex; }

/* Progress bars */
.progress-bars {
    position: absolute; top: 0; left: 0; right: 0;
    display: flex; gap: 4px; padding: 12px 14px 0;
    z-index: 10;
}
.prog-bar {
    flex: 1; height: 3px; background: rgba(255,255,255,0.35); border-radius: 3px; overflow: hidden;
}
.prog-fill { height: 100%; background: #fff; border-radius: 3px; width: 0%; transition: none; }

/* Viewer header */
.viewer-head {
    position: absolute; top: 22px; left: 14px; right: 14px;
    display: flex; align-items: center; gap: 10px; z-index: 10;
}
.viewer-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.5); }
.viewer-info { flex: 1; }
.viewer-name { font-size: 13px; font-weight: 700; color: #fff; }
.viewer-time { font-size: 11px; color: rgba(255,255,255,0.65); }
.viewer-close {
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba(255,255,255,0.15); border: none;
    color: #fff; font-size: 18px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}

/* Media */
.viewer-media { width: 100%; max-width: 420px; aspect-ratio: 9/16; position: relative; border-radius: 16px; overflow: hidden; }
.viewer-media img, .viewer-media video { width: 100%; height: 100%; object-fit: cover; }

/* Tap zones */
.tap-left { position: absolute; left: 0; top: 0; width: 40%; height: 100%; cursor: pointer; }
.tap-right { position: absolute; right: 0; top: 0; width: 40%; height: 100%; cursor: pointer; }

/* ── POSTS SECTION (X.com Style) ── */
.posts-section {
    padding: 0 24px 64px;
    max-width: 1200px;
    margin: 0 auto;
}
.posts-feed {
    display: flex;
    flex-direction: column;
    max-width: 600px; /* Keeps text readable without stretching the entire screen */
}
.x-post {
    display: flex;
    gap: 12px;
    padding: 20px 0;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    background: transparent;
}
.x-post:hover {
    background: transparent;
}
.x-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.x-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.x-header {
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 4px; flex-wrap: nowrap;
}
.x-name { font-size: 15px; font-weight: 800; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.x-handle { font-size: 13px; font-weight: 600; color: #a0a0b0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90px; }
.x-time { font-size: 13px; font-weight: 500; color: #a0a0b0; margin-left: auto; flex-shrink: 0; }
.x-text {
    font-size: 15px; line-height: 1.5; color: #111;
    margin-bottom: 12px; word-break: break-word;
}
.x-media {
    width: 100%;
    border-radius: 16px;
    object-fit: cover;
    max-height: 480px;
    background: #f8f8fc;
    margin-bottom: 12px;
    border: 1px solid #f0f0f0;
}
.x-actions {
    display: flex; align-items: center; justify-content: space-between;
    color: #7a7a8c;
    margin-top: 4px;
    padding-right: 0;
}
.x-acts-left {
    display: flex; align-items: center; gap: 24px;
}
.x-act {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600;
    cursor: pointer; transition: color 0.2s;
}
.x-act:hover { color: #6C5CE7; }
.x-act.like:hover { color: #f91880; }
.x-act svg {
    width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;
    transition: transform 0.2s;
}
.x-act:hover svg { transform: scale(1.1); }

.x-inline-order-btn {
    padding: 6px 14px;
    border-radius: 50px;
    background: #6C5CE7;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s;
    margin-left: auto;
}
.x-inline-order-btn:hover { background: #5a4bcf; }
.x-inline-order-btn:active { transform: scale(0.95); }

/* Comments expanded section (Hidden Data Source) */
.x-comments-section { display: none; }

.x-cmt { display: flex; gap: 10px; margin-bottom: 12px; }
.x-cmt:last-child { margin-bottom: 0; }
.x-cmt-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.x-cmt-body { background: #f8f8fc; padding: 10px 14px; border-radius: 16px; border-top-left-radius: 4px; font-size: 14px; color: #111; display: inline-block; }
.x-cmt-name { font-weight: 800; display: block; margin-bottom: 2px; color: #111; font-size: 13px; }
.x-cmt-text { line-height: 1.45; color: #333; word-break: break-word; }
.x-cmt-empty { font-size: 14px; color: #a0a0b0; padding: 20px 0; text-align: center; font-weight: 500; }

/* MODERN COMMENTS MODAL */
.comments-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    z-index: 100000;
    display: flex; align-items: flex-end; justify-content: center;
    opacity: 0; pointer-events: none;
    transition: opacity 0.3s ease;
}
.comments-modal-overlay.active { opacity: 1; pointer-events: auto; }
.comments-modal-content {
    width: 100%; max-width: 500px; background: #fff;
    border-top-left-radius: 28px; border-top-right-radius: 28px;
    padding: 24px 20px 40px;
    transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.34, 1.4, 0.64, 1);
    max-height: 85vh; display: flex; flex-direction: column;
}
@media(min-width: 768px) {
    .comments-modal-overlay { align-items: center; }
    .comments-modal-content { border-radius: 24px; padding: 32px 24px 32px; max-height: 70vh; }
}
.comments-modal-overlay.active .comments-modal-content { transform: translateY(0); }
.cmt-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.cmt-modal-header h3 { font-size: 20px; font-weight: 800; color: #111; margin: 0; letter-spacing: -0.5px; }
.cmt-modal-close {
    background: #f0f0f5; border: none; width: 36px; height: 36px;
    border-radius: 50%; font-size: 20px; cursor: pointer; color: #111;
    display: flex; align-items: center; justify-content: center; transition: background 0.2s;
}
.cmt-modal-close:hover { background: #e0e0e8; }
.cmt-modal-body { flex: 1; overflow-y: auto; padding-right: 8px; }
.cmt-modal-body::-webkit-scrollbar { width: 4px; }
.cmt-modal-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

/* SHIMMER LOADER */
.shimmer-overlay {
    position: fixed; inset: 0; z-index: 99999; background: #fff;
    display: flex; transition: opacity 0.4s ease;
}
.shimmer-sidebar {
    width: 260px; height: 100%; border-right: 1px solid #f0f0f0; padding: 32px 24px;
    display: none;
}
@media(min-width: 1024px) {
    .shimmer-sidebar { display: block; flex-shrink: 0; }
}
.shimmer-main {
    flex: 1; padding: 40px 24px; overflow: hidden;
}
.shimmer-row {
    display: flex; gap: 16px; overflow: hidden;
}
@media(min-width: 768px) {
    .shimmer-row .sbox:nth-child(3), .shimmer-row .sbox:nth-child(4) { display: block !important; }
}
.shimmer-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;
}
@media(min-width: 768px) { .shimmer-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
@media(min-width: 1024px) { .shimmer-grid { grid-template-columns: repeat(4, 1fr); gap: 24px; } }

.sbox {
    background: #f6f7f8;
    background-image: linear-gradient(90deg, #f6f7f8 0px, #edeef1 80px, #f6f7f8 160px);
    background-size: 600px;
    animation: shimmer 1.2s infinite linear;
}
@keyframes shimmer { 0% { background-position: -300px; } 100% { background-position: 600px; } }

/* POST MEDIA SWIPE CAROUSEL */
.x-media-carousel-wrap {
    position: relative; width: 100%; margin-top: 12px; margin-bottom: 12px;
}
.x-media-carousel {
    display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none;
    border-radius: 16px; border: 1px solid rgba(0,0,0,0.08); background: #000; width: 100%;
}
.x-media-carousel::-webkit-scrollbar { display: none; }
.x-media-carousel-item {
    scroll-snap-align: start; flex: 0 0 100%; width: 100%; max-height: 550px; object-fit: contain; cursor: pointer;
}

@media (max-width: 600px) {
    .posts-section { padding: 0 16px 80px; }
    .x-post { padding: 16px 0; gap: 8px; }
    .x-avatar { width: 38px; height: 38px; }
    .x-name { font-size: 14px; max-width: 100px; }
    .x-handle { font-size: 12px; max-width: 80px; }
    .x-text { font-size: 14px; margin-bottom: 8px; }
    .x-actions { flex-direction: column; align-items: stretch; gap: 16px; margin-top: 8px; }
    .x-acts-left { gap: 16px; justify-content: flex-start; width: 100%; }
    .x-inline-order-btn { font-size: 14px; padding: 12px 14px; width: 100%; border-radius: 12px; margin-left: 0; text-align: center; }
    
    .x-media-carousel-item { max-height: 480px; }
    
    .story-card { width: 110px; height: 180px; border-radius: 18px; }
    .story-avatar-wrap { width: 40px; height: 40px; top: 8px; left: 8px; }
    .add-story-card { width: 110px; height: 180px; border-radius: 18px; }
    .story-label { padding: 10px 10px 14px; font-size: 12px; }
    .story-type-badge { top: 8px; right: 8px; padding: 2px 6px; font-size: 9px; }
    .reel-play { width: 40px; height: 40px; }
    .reel-play svg { width: 18px; height: 18px; }
    
    .viewer-media { max-width: 100%; border-radius: 0; aspect-ratio: auto; min-height: 100vh; }
}

/* ── DYNAMIC CART UI ── */
#floating-cart-btn {
    position: fixed; bottom: 30px; right: 30px; z-index: 999;
    width: 65px; height: 65px; border-radius: 50%;
    background: #6C5CE7; color: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 25px rgba(108,92,231,0.4); cursor: pointer;
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.64, 1);
    transform: scale(0);
}
#floating-cart-btn.visible { transform: scale(1); }
#floating-cart-btn:hover { transform: scale(1.1); }
#floating-cart-btn svg { width: 26px; height: 26px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.cart-badge {
    position: absolute; top: -2px; right: -2px;
    background: #EF4444; color: #fff; font-size: 11px; font-weight: 800;
    min-width: 22px; height: 22px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
}

#cart-drawer-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 10000; opacity: 0; pointer-events: none; transition: 0.3s;
}
#cart-drawer-overlay.active { opacity: 1; pointer-events: auto; }

#cart-drawer {
    position: fixed; top: 0; bottom: 0; right: 0; width: 100%; max-width: 420px;
    background: #fff; z-index: 10001;
    transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.34, 1.4, 0.64, 1);
    display: flex; flex-direction: column;
    box-shadow: -10px 0 30px rgba(0,0,0,0.1);
}
#cart-drawer.active { transform: translateX(0); }

.cd-header { display: flex; align-items: center; justify-content: space-between; padding: 24px; border-bottom: 1px solid #f0f0f0; }
.cd-header h3 { font-size: 20px; font-weight: 800; color: #111; margin: 0; }
.cd-close { background: #f4f4f8; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 18px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.cd-close:hover { background: #e0e0e8; }

.cd-body { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 20px; }
.cd-empty { text-align: center; color: #a0a0b0; font-weight: 600; font-size: 15px; margin-top: 40px; display: none; }

.cd-item { display: flex; gap: 16px; align-items: center; }
.cd-item-img { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; background: #f8f8fc; }
.cd-item-info { flex: 1; min-width: 0; }
.cd-item-name { font-size: 14px; font-weight: 800; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
.cd-item-price { font-size: 13px; font-weight: 700; color: #6C5CE7; }
.cd-item-ctrls { display: flex; align-items: center; gap: 10px; background: #f8f8fc; padding: 6px; border-radius: 12px; }
.cd-btn { width: 28px; height: 28px; border: none; background: #fff; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.cd-btn:hover { background: #111; color: #fff; }
.cd-qty { font-size: 13px; font-weight: 800; width: 16px; text-align: center; }

.cd-footer { padding: 24px; border-top: 1px solid #f0f0f0; background: #fff; }
.cd-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.cd-total-lbl { font-size: 16px; font-weight: 600; color: #7a7a8c; }
.cd-total-val { font-size: 24px; font-weight: 900; color: #111; }
.cd-checkout-btn { width: 100%; border: none; background: #111; color: #fff; padding: 18px; border-radius: 16px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.25s; }
.cd-checkout-btn:hover { background: #25D366; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3); }

@keyframes pingDrop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}
.ping { animation: pingDrop 0.3s ease-out !important; }

/* ── FULL PAGE PRODUCT DETAILS ── */
.product-page-section {
    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999;
    background: #fff; overflow-y: auto; overflow-x: hidden; padding-bottom: 60px;
    animation: fadeUp 0.3s ease;
}

.pp-hero { width: 100%; max-width: 800px; margin: 0 auto; padding: 16px; position: relative; }
.pp-hero img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 24px; box-shadow: 0 12px 30px rgba(0,0,0,0.06); }

.pp-back {
    position: absolute; top: max(32px, env(safe-area-inset-top)); left: 32px; z-index: 10;
    display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;
    border: none; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    border-radius: 50%; color: #111; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.pp-back:hover { transform: scale(1.05); }

.pp-body { max-width: 800px; margin: 0 auto; padding: 16px 24px 60px; }

.pp-pills-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.pp-pill { padding: 6px 14px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap; }
.pp-pill-cat { background: #b5c2f0; color: #fff; }
.pp-pill-hot { background: #f4f4f8; color: #777; }

.pp-rating-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
.pp-stars { display: flex; gap: 2px; color: #E1C15B; font-size: 14px; }
.pp-rating-text { font-size: 13px; font-weight: 600; color: #555; }

.pp-title { font-size: 28px; font-weight: 900; color: #111; line-height: 1.2; margin-bottom: 12px; letter-spacing: -0.5px; }

.pp-desc { font-size: 15px; font-weight: 500; color: #777; line-height: 1.6; margin-bottom: 24px; white-space: pre-wrap; }

.pp-checks { display: flex; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 36px; }
.pp-check-item { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #555; }
.pp-check-icon { display: flex; align-items: center; justify-content: center; width: 16px; height: 16px; background: #E4AFAF; color: white; border-radius: 50%; stroke-width: 3; }

.pp-checkout-block { margin-top: 32px; display: flex; flex-direction: column; align-items: center; gap: 14px; }
.pp-qty-picker { display: flex; align-items: center; background: #f4f4f8; padding: 6px; border-radius: 100px; margin-bottom: 4px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
.pp-qty-btn { width: 44px; height: 44px; border-radius: 50%; border: none; background: #fff; color: #111; font-size: 24px; font-weight: 500; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: center; }
.pp-qty-btn:active { transform: scale(0.95); }
.pp-qty-val { width: 50px; text-align: center; font-size: 18px; font-weight: 800; color: #111; }
.pp-checkout-btn { width: 100%; display: flex; align-items: center; justify-content: center; background: #111; color: #fff; border: none; border-radius: 16px; padding: 20px; font-family: 'Inter', sans-serif; cursor: pointer; transition: 0.2s; box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
.pp-checkout-btn:active { transform: scale(0.97); }
.pp-checkout-price { font-size: 16px; font-weight: 800; }
.pp-checkout-divider { margin: 0 12px; font-weight: 300; opacity: 0.3; }
.pp-checkout-text { font-size: 14px; font-weight: 700; }
.pp-supported-text { font-size: 13px; font-weight: 600; color: #888; }

/* ── PRODUCT EXTRAS (Colors, Sizes, Options) ── */
#pp-extras-container { margin-bottom: 28px; display: flex; flex-direction: column; gap: 20px; }
.pp-extra-group { display: flex; flex-direction: column; gap: 10px; }
.pp-extra-label {
    font-size: 12px; font-weight: 800; color: #111;
    text-transform: uppercase; letter-spacing: 0.8px;
    display: flex; align-items: center; gap: 8px;
}
.pp-extra-required {
    font-size: 10px; font-weight: 700; color: #6C5CE7;
    background: #f0eefe; padding: 2px 8px; border-radius: 50px;
    text-transform: none; letter-spacing: 0;
}
/* Color swatches */
.pp-extra-colors { display: flex; flex-wrap: wrap; gap: 10px; }
.pp-color-swatch {
    width: 38px; height: 38px; border-radius: 50%;
    border: 3px solid transparent; cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
    position: relative; box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.pp-color-swatch:hover { transform: scale(1.12); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.pp-color-swatch.selected {
    border-color: #111; transform: scale(1.15);
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #111;
}
/* Size/option chips */
.pp-extra-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.pp-chip {
    padding: 8px 16px; border-radius: 50px;
    border: 2px solid #f0f0f0; background: #f8f8fc;
    font-size: 13px; font-weight: 700; color: #444;
    cursor: pointer; transition: all 0.15s; font-family: inherit;
    display: flex; align-items: center; gap: 6px;
}
.pp-chip:hover { border-color: #6C5CE7; color: #6C5CE7; background: #f0eefe; }
.pp-chip.selected { background: #111; color: #fff; border-color: #111; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.pp-chip-price { font-size: 10px; font-weight: 800; opacity: 0.75; }
</style>

</head>
<body>

<!-- PREMIUM SHIMMER LOADER -->
<div id="shimmerOverlay" class="shimmer-overlay">
    <div class="shimmer-sidebar">
        <div class="sbox" style="height:34px; width:120px; border-radius:8px; margin-bottom:48px;"></div>
        <div class="sbox" style="height:20px; width:140px; border-radius:4px; margin-bottom:24px;"></div>
        <div class="sbox" style="height:20px; width:140px; border-radius:4px; margin-bottom:24px;"></div>
        <div class="sbox" style="height:20px; width:140px; border-radius:4px; margin-bottom:24px;"></div>
    </div>
    <div class="shimmer-main">
        <div class="sbox" style="width:200px; height:34px; border-radius:8px; margin-bottom:24px;"></div>
        <div class="shimmer-row">
            <div class="sbox" style="width:190px; height:330px; border-radius:28px; flex-shrink:0;"></div>
            <div class="sbox" style="width:190px; height:330px; border-radius:28px; flex-shrink:0;"></div>
            <div class="sbox" style="width:190px; height:330px; border-radius:28px; flex-shrink:0; display:none;"></div>
            <div class="sbox" style="width:190px; height:330px; border-radius:28px; flex-shrink:0; display:none;"></div>
        </div>
        <div class="sbox" style="width:240px; height:34px; border-radius:8px; margin:48px 0 24px;"></div>
        <div class="shimmer-grid">
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
            <div class="sbox" style="height:320px; border-radius:24px;"></div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', () => {
    setTimeout(() => {
        const loader = document.getElementById('shimmerOverlay');
        if(loader) {
            loader.style.opacity = '0';
            setTimeout(() => loader.remove(), 400);
        }
    }, 700); // brief artificial delay for premium app feel
});
</script>

<div class="layout" id="layout">

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sb-brand">
        <img class="sb-logo"
             src="<?= htmlspecialchars($shopLogo) ?>"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($shopName) ?>&background=6C5CE7&color=FFF&bold=true'"
             alt="">
        <span class="sb-brand-name"><?= htmlspecialchars($shopName) ?></span>
    </div>

    <!-- Nav -->
    <nav class="sb-nav">

        <span class="sb-group-label">Navigation</span>

        <button class="sb-item active" onclick="setTab('home', this)" id="tab-home">
            <span class="sb-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>
            <span class="sb-label">Home</span>
            <span class="sb-tip">Home</span>
        </button>

        <button class="sb-item" onclick="setTab('store', this)" id="tab-store">
            <span class="sb-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </span>
            <span class="sb-label">Store</span>
            <span class="sb-tip">Store</span>
        </button>

        <button class="sb-item" onclick="setTab('posts', this)" id="tab-posts">
            <span class="sb-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </span>
            <span class="sb-label">Posts</span>
            <span class="sb-tip">Posts</span>
        </button>


    </nav>

    <div class="sb-divider"></div>

    <!-- Store info bottom -->
    <div class="sb-bottom">
        <div class="sb-store-item">
            <div class="sb-store-avatar">
                <img src="<?= htmlspecialchars($shopLogo) ?>"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($shopName) ?>&background=6C5CE7&color=FFF&bold=true'"
                     alt="">
            </div>
            <div class="sb-store-info">
                <div class="sb-store-name">
                    <span class="sb-online-dot"></span><?= htmlspecialchars($shopName) ?>
                </div>
            </div>
        </div>
    </div>

</aside>


<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">

<!-- MOBILE BRAND HEADER -->
<div class="mobile-brand-header">
    <img class="mobile-brand-logo" src="<?= htmlspecialchars($shopLogo) ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($shopName) ?>&background=6C5CE7&color=FFF&bold=true'">
    <div class="mobile-brand-info">
        <div class="mobile-brand-name"><?= htmlspecialchars($shopName) ?></div>
        <div class="mobile-brand-handle">@<?= preg_replace("/[^a-zA-Z0-9]+/", "", strtolower($shopName)) ?></div>
    </div>
</div>

<!-- STORIES + REELS -->
<div class="stories-section">

    <!-- Section Header -->
    <div class="stories-head">
        <h2 class="stories-head-title">
            <span>Live Content</span>
            Stories &amp; Reels
        </h2>
        <span class="stories-head-count">
            <?= count($stories) + count($reels) ?> items
        </span>
    </div>

    <?php if(!empty($stories) || !empty($reels)): ?>
    <div class="stories-scroll" id="storiesScroll">

        <?php foreach($stories as $i => $st):
            $sPhoto = normalizeMediaUrl($st['StoryPhoto'] ?? '');
            $thumb = $sPhoto ?: $shopLogo;
            $isVideo = (!empty($st['StotyType']) && strtolower($st['StotyType']) === 'video') || (!empty($st['BunnyV']) && !in_array(strtolower(trim($st['BunnyV'])), ['','none','0','-','null'])) || (!empty($st['BunnyS']) && !in_array(strtolower(trim($st['BunnyS'])), ['','none','0','-','null']));
        ?>
        <div class="story-card" onclick="openViewer(<?= $i ?>, 'story')">
            <?php if($isVideo): ?>
                <video class="story-bg" src="<?= $thumb ?>#t=0.1" preload="metadata" muted playsinline style="object-fit:cover; pointer-events:none;"></video>
            <?php else: ?>
                <img class="story-bg" src="<?= $thumb ?>" onerror="this.src='<?= $shopLogo ?>'">
            <?php endif; ?>
            <div class="story-gradient"></div>
            <div class="story-avatar-wrap">
                <img class="story-avatar" src="<?= htmlspecialchars($shopLogo) ?>">
            </div>
            <span class="story-type-badge"><?= $isVideo ? '🎬 Reel' : '📸 Story' ?></span>
            <?php if($isVideo): ?>
            <div class="reel-play">
                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <?php endif; ?>
            <div class="story-label">
                <?= htmlspecialchars($st['AdName'] ?: $shopName) ?>
                <span class="story-sub"><?= $isVideo ? 'Video story' : 'Tap to view' ?></span>
            </div>
        </div>
        <?php endforeach; ?>

        <?php foreach($reels as $i => $r):
            $actualVideo = normalizeMediaUrl($r['Video'] ?? '');
            $useVideoFrame = !empty($actualVideo);
            $vThumb = normalizeMediaUrl($r['VideoThumbnail'] ?? '');
            $pThumb = normalizeMediaUrl($r['PostPhoto'] ?? '');
            $thumb = $vThumb ?: ($pThumb ?: $shopLogo);
        ?>
        <div class="story-card" onclick="openViewer(<?= $i ?>, 'reel')">
            <?php if ($useVideoFrame): ?>
                <video class="story-bg" src="<?= $actualVideo ?>#t=0.1" preload="metadata" muted playsinline style="object-fit:cover; pointer-events:none;"></video>
            <?php else: ?>
                <img class="story-bg" src="<?= $thumb ?>" onerror="this.src='<?= $shopLogo ?>'">
            <?php endif; ?>
            <div class="story-gradient"></div>
            <div class="story-avatar-wrap">
                <img class="story-avatar" src="<?= htmlspecialchars($shopLogo) ?>">
            </div>
            <span class="story-type-badge">🎬 Reel</span>
            <div class="reel-play">
                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <div class="story-label">
                <?= mb_strimwidth(htmlspecialchars($r['PostText'] ?: $shopName), 0, 22, '…') ?>
                <span class="story-sub">Tap to watch</span>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <?php else: ?>
    <!-- No content yet: show placeholder cards -->
    <div class="stories-scroll">
        <div class="add-story-card">
            <div class="add-story-top">
                <div class="add-story-plus">＋</div>
            </div>
            <span class="add-story-label">Add Story</span>
        </div>
        <?php for($x=0; $x<4; $x++): ?>
        <div class="story-card" style="background: #f3f3f3;">
            <div class="story-gradient" style="background: linear-gradient(to top, rgba(0,0,0,0.3) 30%, transparent 70%);"></div>
            <div class="story-avatar-wrap" style="background: #ddd;">
                <img class="story-avatar" src="<?= htmlspecialchars($shopLogo) ?>">
            </div>
            <div class="story-label" style="color: rgba(255,255,255,0.5);">· · ·</div>
        </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div><!-- /stories-section -->

<!-- PRODUCTS SECTION -->
<?php if(!empty($products)): ?>
<section class="products-section">
    <!-- Title -->
    <div class="products-header">
        <h2 class="products-title">
            <span>Menu</span>
            Discover our products
        </h2>
    </div>

    <!-- Category Pills -->
    <div class="cats-wrap">
        <div class="cats-scroll">
            <button class="cat-pill active" onclick="filterCat('all', this)">All Items</button>
            <?php foreach($categories as $c): ?>
            <button class="cat-pill" onclick="filterCat('<?= htmlspecialchars($c['CategoryShopID']) ?>', this)">
                <?= htmlspecialchars($c['CategoryName']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="products-grid" id="productsGrid">
        <?php 
        $jsonProducts = [];
        foreach($products as $i => $p):
            $hasOffer = !empty($p['FoodOfferPrice']) && (float)$p['FoodOfferPrice'] > 0 && (float)$p['FoodOfferPrice'] < (float)$p['FoodPrice'];
            $price    = $hasOffer ? (float)$p['FoodOfferPrice'] : (float)$p['FoodPrice'];
            $oldPrice = $hasOffer ? (float)$p['FoodPrice'] : null;
            $fallback = "https://ui-avatars.com/api/?name=".urlencode($p['FoodName'])."&background=EEEBfd&color=6C5CE7&bold=true";
            $pimg     = !empty($p['FoodPhoto']) ? htmlspecialchars($p['FoodPhoto']) : $fallback;
            
            $jsonProducts[$p['FoodID']] = [
                'id'     => $p['FoodID'],
                'name'   => $p['FoodName'],
                'desc'   => $p['FoodDesc'] ?? '',
                'cat'    => $p['CatName'] ?? '',
                'price'  => $price,
                'oldPrice' => $oldPrice,
                'img'    => $pimg,
                'extras' => $extrasMap[(string)$p['FoodID']] ?? []
            ];
        ?>
        <div class="p-card" data-index="<?= $i ?>" data-cat="<?= htmlspecialchars($p['FoodCatID']) ?>" onclick="productApp.open(<?= $p['FoodID'] ?>)">
            <div class="p-img-wrap">
                <img class="p-img" src="<?= $pimg ?>" onerror="this.src='<?= $fallback ?>'" loading="lazy" alt="<?= htmlspecialchars($p['FoodName']) ?>">
                <?php if($hasOffer): ?>
                <span class="p-discount">-<?= round((($oldPrice - $price) / $oldPrice) * 100) ?>%</span>
                <?php endif; ?>
            </div>
        <div class="p-body">
                <div class="p-cat"><?= htmlspecialchars($p['CatName'] ?? '') ?></div>
                <div class="p-name"><?= htmlspecialchars($p['FoodName']) ?></div>
                <div class="p-footer">
                    <div class="p-prices">
                        <span class="p-price"><?= number_format($price, 2) ?> <small style="font-size:10px;font-weight:700;color:#aaa;">MAD</small></span>
                        <?php if($oldPrice): ?>
                        <span class="p-old"><?= number_format($oldPrice, 2) ?> MAD</span>
                        <?php endif; ?>
                    </div>
                    <button class="p-add" onclick="event.stopPropagation(); cartEngine.add(<?= $p['FoodID'] ?>, `<?= addslashes(htmlspecialchars($p['FoodName'])) ?>`, <?= $price ?>, `<?= addslashes($pimg) ?>`)" title="Add to bag">＋</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="load-more-wrap" id="loadMoreWrap">
        <p class="load-more-progress" id="loadMoreProgress">Showing 4 of <?= count($products) ?></p>
        <button class="load-more-btn" id="loadMoreBtn" onclick="loadMore()">
            <span class="spinner"></span>
            <span id="loadMoreTxt">Load More</span>
        </button>
    </div>
</section>
<?php endif; ?>

<script>
const STORE_PRODUCTS = <?= json_encode($jsonProducts ?? []) ?>;
</script>

<!-- POSTS SECTION (X Style) -->
<section class="posts-section">
    <div class="products-header">
        <h2 class="products-title">
            <span>Updates</span>
            Latest Posts
        </h2>
    </div>
    
    <div class="posts-feed">
        <?php if(!empty($posts_img)): foreach($posts_img as $post): 
            $linkedProduct = null;
            if(!empty($post['ProductID'])) {
                foreach($products as $p) {
                    if($p['FoodID'] == $post['ProductID']) { $linkedProduct = $p; break; }
                }
            }
        ?>
        <div class="x-post">
            <img class="x-avatar" src="<?= htmlspecialchars($shopLogo) ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($shopName) ?>'">
            <div class="x-body">
                <div class="x-header">
                    <span class="x-name"><?= htmlspecialchars($shopName) ?></span>
                    <span class="x-handle">@<?= preg_replace("/[^a-zA-Z0-9]+/", "", strtolower($shopName)) ?></span>
                    <span class="x-time">latest</span>
                </div>
                
                <?php if(!empty($post['PostText'])): ?>
                <div class="x-text">
                    <?= htmlspecialchars($post['PostText']) ?>
                </div>
                <?php endif; ?>
                <?php 
                    $mediaPhotos = array_filter([
                        $post['PostPhoto'] ?? '', 
                        $post['PostPhoto2'] ?? '', 
                        $post['PostPhoto3'] ?? '', 
                        $post['PostPhoto4'] ?? ''
                    ]);
                ?>
                <?php if(!empty($mediaPhotos)): ?>
                <div class="x-media-carousel-wrap">
                    <div class="x-media-carousel">
                        <?php foreach($mediaPhotos as $mImg): ?>
                            <img class="x-media-carousel-item" src="<?= htmlspecialchars(normalizeMediaUrl($mImg)) ?>" loading="lazy" onclick="window.open(this.src, '_blank')">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="x-actions">
                    <div class="x-acts-left">
                        <div class="x-act like">
                            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            <span><?= (int)($post['PostLikes'] ?? 0) ?></span>
                        </div>
                        <div class="x-act comment" onclick="openCommentsModal(<?= (int)$post['PostId'] ?>, event)">
                            <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            <span><?= (int)($post['Postcomments'] ?? 0) ?></span>
                        </div>
                        <div class="x-act share" onclick="sharePost(<?= (int)$post['PostId'] ?>, '<?= htmlspecialchars(addslashes($shopName), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes(substr($post['PostText'] ?? '', 0, 100)), ENT_QUOTES) ?>', event)">
                            <svg viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                        </div>
                    </div>
                    <?php if($linkedProduct): ?>
                    <button class="x-inline-order-btn" onclick="event.stopPropagation(); cartEngine.add(<?= $linkedProduct['FoodID'] ?>, `<?= addslashes(htmlspecialchars($linkedProduct['FoodName'])) ?>`, <?= $linkedProduct['FoodPrice'] ?>, `<?= addslashes(!empty($linkedProduct['FoodPhoto']) ? htmlspecialchars($linkedProduct['FoodPhoto']) : 'https://ui-avatars.com/api/?name='.urlencode($linkedProduct['FoodName']).'&background=EEEBfd&color=6C5CE7') ?>`)">
                        Order • <?= number_format($linkedProduct['FoodPrice'], 2) ?> MAD
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php 
                    $postIdAttr = (int)$post['PostId'];
                    $commentsQ = $con->query("SELECT Comments.*, Users.name, Users.UserPhoto FROM Comments LEFT JOIN Users ON Comments.UserID = Users.UserID WHERE PostID = $postIdAttr ORDER BY CommentID DESC LIMIT 10");
                    $commentsList = [];
                    if($commentsQ) while($c = $commentsQ->fetch_assoc()) $commentsList[] = $c;
                ?>
                <div class="x-comments-section" id="comments-<?= $postIdAttr ?>" onclick="event.stopPropagation()">
                    <?php if(empty($commentsList)): ?>
                    <div class="x-cmt-empty">No comments yet.</div>
                    <?php else: foreach($commentsList as $cmt): 
                        $cmtName = !empty($cmt['name']) ? $cmt['name'] : 'Store User';
                        $cmtAvatar = !empty($cmt['UserPhoto']) && $cmt['UserPhoto'] !== 'NONE' ? htmlspecialchars($cmt['UserPhoto']) : "https://ui-avatars.com/api/?name=".urlencode($cmtName)."&background=EEEBfd&color=6C5CE7&bold=true";
                    ?>
                    <div class="x-cmt">
                        <img class="x-cmt-avatar" src="<?= $cmtAvatar ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($cmtName) ?>'">
                        <div class="x-cmt-body">
                            <span class="x-cmt-name"><?= htmlspecialchars($cmtName) ?></span>
                            <span class="x-cmt-text"><?= nl2br(htmlspecialchars($cmt['CommentText'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; else: ?>
        <div style="padding: 40px 20px; text-align: center; color: #a0a0b0; font-size: 14px;">No posts yet.</div>
        <?php endif; ?>
    </div>
</section>

</div><!-- /main-content -->
</div><!-- /layout -->

<!-- VIEWER OVERLAY -->
<div class="viewer" id="viewer">
    <!-- Progress -->
    <div class="progress-bars" id="progBars"></div>

    <!-- Head -->
    <div class="viewer-head">
        <img class="viewer-avatar" src="<?= htmlspecialchars($shopLogo) ?>">
        <div class="viewer-info">
            <div class="viewer-name"><?= htmlspecialchars($shopName) ?></div>
            <div class="viewer-time" id="viewerTime">Just now</div>
        </div>
        <button class="viewer-close" onclick="closeViewer()">✕</button>
    </div>

    <!-- Media -->
    <div class="viewer-media" id="viewerMedia"></div>
</div>

<script>
const STORIES = <?= json_encode(array_values($stories)) ?>;
const REELS   = <?= json_encode(array_values($reels)) ?>;
const LOGO    = "<?= addslashes($shopLogo) ?>";

let currentIndex = 0;
let currentType  = 'story';
let progInterval = null;
let progVal      = 0;

function getList() { return currentType === 'reel' ? REELS : STORIES; }

function openViewer(idx, type) {
    currentIndex = idx;
    currentType  = type;
    document.getElementById('viewer').classList.add('open');
    renderSlide();
}

function closeViewer() {
    document.getElementById('viewer').classList.remove('open');
    clearInterval(progInterval);
    const media = document.getElementById('viewerMedia');
    media.querySelectorAll('video').forEach(v => { v.pause(); v.src = ''; });
}

function renderSlide() {
    clearInterval(progInterval);
    progVal = 0;

    const list = getList();
    if (!list.length) { closeViewer(); return; }
    if (currentIndex >= list.length) { closeViewer(); return; }
    if (currentIndex < 0) currentIndex = 0;

    const item = list[currentIndex];

    // Progress bars
    const bars = document.getElementById('progBars');
    bars.innerHTML = list.map((_, i) => `
        <div class="prog-bar">
            <div class="prog-fill" id="prog_${i}" style="width: ${i < currentIndex ? '100' : '0'}%"></div>
        </div>
    `).join('');

    // Media
    const media = document.getElementById('viewerMedia');
    media.innerHTML = '';

    const isBunnyV = item.BunnyV && !['none','-','0','null',''].includes(item.BunnyV.toLowerCase().trim());
    const isBunnyS = item.BunnyS && !['none','-','0','null',''].includes(item.BunnyS.toLowerCase().trim());
    const isVideo = currentType === 'reel' || isBunnyV || isBunnyS || (item.StotyType && item.StotyType.toLowerCase() === 'video');
    let src = LOGO;
    if (currentType === 'reel') {
        src = item.Video || item.PostPhoto || LOGO;
    } else {
        src = item.StoryPhoto || LOGO;
    }

    if (isVideo) {
        const vid = document.createElement('video');
        vid.src = src; vid.autoplay = true; vid.loop = false; vid.muted = false; vid.playsInline = true;
        vid.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        media.appendChild(vid);
    } else {
        const img = document.createElement('img');
        img.src = src;
        img.onerror = () => { img.src = LOGO; };
        media.appendChild(img);
    }

    // Tap zones
    const tl = document.createElement('div'); tl.className = 'tap-left'; tl.onclick = prevSlide;
    const tr = document.createElement('div'); tr.className = 'tap-right'; tr.onclick = nextSlide;
    media.appendChild(tl); media.appendChild(tr);

    // Duration
    const duration = isVideo ? 15000 : 5000;
    const step = 100 / (duration / 50);

    progInterval = setInterval(() => {
        progVal += step;
        const fill = document.getElementById(`prog_${currentIndex}`);
        if (fill) fill.style.width = Math.min(progVal, 100) + '%';
        if (progVal >= 100) nextSlide();
    }, 50);
}

function nextSlide() {
    const list = getList();
    if (currentIndex < list.length - 1) {
        currentIndex++;
        renderSlide();
    } else {
        closeViewer();
    }
}

function prevSlide() {
    if (currentIndex > 0) { currentIndex--; renderSlide(); }
}

// Close on backdrop (outside media)
document.getElementById('viewer').addEventListener('click', function(e) {
    if (e.target === this) closeViewer();
});

/* ── PRODUCTS LOAD MORE & FILTER ── */
const BATCH = 4;
let shown = 0;
const cards = document.querySelectorAll('.p-card');
let filteredCards = Array.from(cards);

function filterCat(catId, btn) {
    // Update active state
    document.querySelectorAll('.cat-pill').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');

    // Hide all
    cards.forEach(c => c.classList.remove('visible'));

    // Filter array
    if (catId === 'all') {
        filteredCards = Array.from(cards);
    } else {
        filteredCards = Array.from(cards).filter(c => c.getAttribute('data-cat') === String(catId));
    }

    shown = 0;
    
    // Smooth reset grid
    setTimeout(() => showBatch(), 50);
}

function showBatch() {
    const end = Math.min(shown + BATCH, filteredCards.length);
    for (let i = shown; i < end; i++) {
        const card = filteredCards[i];
        card.classList.add('visible');
        card.style.animationDelay = ((i - shown) * 70) + 'ms';
    }
    shown = end;
    updateLoadMore();
}

function loadMore() {
    const btn = document.getElementById('loadMoreBtn');
    btn.classList.add('loading');
    document.getElementById('loadMoreTxt').textContent = 'Loading...';
    setTimeout(() => {
        btn.classList.remove('loading');
        document.getElementById('loadMoreTxt').textContent = 'Load More';
        showBatch();
    }, 500);
}

function updateLoadMore() {
    const wrap  = document.getElementById('loadMoreWrap');
    const prog  = document.getElementById('loadMoreProgress');
    if (!wrap) return;
    if (prog) prog.textContent = 'Showing ' + shown + ' of ' + filteredCards.length;
    
    if (shown >= filteredCards.length) {
        wrap.style.display = 'none';
    } else {
        wrap.style.display = 'flex';
    }
}

if (cards.length > 0) showBatch();

/* ── SIDEBAR & TABS ── */
const sidebarPanel = document.getElementById('sidebar');
const layoutWrap = document.getElementById('layout');

if (sidebarPanel && layoutWrap) {
    sidebarPanel.addEventListener('mouseenter', () => {
        layoutWrap.classList.add('expanded');
    });
    sidebarPanel.addEventListener('mouseleave', () => {
        layoutWrap.classList.remove('expanded');
    });
}

function openCommentsModal(postId, event) {
    if (event) event.stopPropagation();
    const source = document.getElementById('comments-' + postId);
    const modalBody = document.getElementById('cmtModalBody');
    if (!source || !modalBody) return;
    
    // Copy HTML from the hidden source block into the modal body
    modalBody.innerHTML = source.innerHTML;
    
    // Show modal
    document.getElementById('commentsModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCommentsModal() {
    document.getElementById('commentsModal').classList.remove('active');
    document.body.style.overflow = '';
}

function sharePost(id, shopName, text, event) {
    if(event) event.stopPropagation();
    const url = window.location.href.split('#')[0] + '#post-' + id;
    if (navigator.share) {
        navigator.share({
            title: shopName,
            text: text,
            url: url
        }).catch(console.error);
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('Post link copied to clipboard!');
        });
    }
}

function setTab(tab, btn) {
    // Update sidebar active state
    document.querySelectorAll('.sb-item').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    // Update bottom tabs
    document.querySelectorAll('.bt-item').forEach(el => el.classList.remove('active'));
    const btMatch = document.querySelector(`.bt-item[data-tab="${tab}"]`);
    if (btMatch) btMatch.classList.add('active');
    // Show/hide sections
    showSection(tab);
}

function setTabMobile(tab, btn) {
    document.querySelectorAll('.bt-item').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.sb-item').forEach(el => el.classList.remove('active'));
    const sbMatch = document.getElementById('tab-' + tab);
    if (sbMatch) sbMatch.classList.add('active');
    showSection(tab);
}

function showSection(tab) {
    const storiesSection = document.querySelector('.stories-section');
    const productsSection = document.querySelector('.products-section');
    const postsSection = document.querySelector('.posts-section');
    const productPageSection = document.getElementById('product-page-section');

    if (productPageSection) productPageSection.style.display = 'none';


    // Default: show all for 'home'
    if (tab === 'home') {
        if (storiesSection) storiesSection.style.display = '';
        if (productsSection) productsSection.style.display = '';
        if (postsSection) postsSection.style.display = '';
        return;
    }
    if (tab === 'store') {
        if (storiesSection) storiesSection.style.display = 'none';
        if (productsSection) productsSection.style.display = '';
        if (postsSection) postsSection.style.display = 'none';
        return;
    }
    if (tab === 'posts') {
        if (storiesSection) storiesSection.style.display = '';
        if (productsSection) productsSection.style.display = 'none';
        if (postsSection) postsSection.style.display = '';
        return;
    }
    if (tab === 'offres') {
        // Show only discounted products
        if (storiesSection) storiesSection.style.display = 'none';
        if (productsSection) productsSection.style.display = '';
        if (postsSection) postsSection.style.display = 'none';
        
        document.querySelectorAll('.p-card').forEach(c => {
            const disc = c.querySelector('.p-discount');
            c.style.display = disc ? 'flex' : 'none';
            if (!disc) c.classList.remove('visible');
            else c.classList.add('visible');
        });
        return;
    }
}
</script>

<!-- BOTTOM TABS (mobile) -->
<nav class="bottom-tabs">
    <button class="bt-item active" data-tab="home" onclick="setTabMobile('home', this)">
        <span class="bt-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </span>
        <span class="bt-label">Home</span>
    </button>
    <button class="bt-item" data-tab="store" onclick="setTabMobile('store', this)">
        <span class="bt-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </span>
        <span class="bt-label">Store</span>
    </button>
    <button class="bt-item" data-tab="posts" onclick="setTabMobile('posts', this)">
        <span class="bt-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </span>
        <span class="bt-label">Posts</span>
    </button>

</nav>

<!-- COMMENTS MODAL OVERLAY -->
<div class="comments-modal-overlay" id="commentsModal" onclick="closeCommentsModal()">
    <div class="comments-modal-content" onclick="event.stopPropagation()">
        <div class="cmt-modal-header">
            <h3>Comments</h3>
            <button class="cmt-modal-close" onclick="closeCommentsModal()">&times;</button>
        </div>
        <div class="cmt-modal-body" id="cmtModalBody">
            <!-- Content injected via JS -->
        </div>
    </div>
</div>

<!-- FLOATING CART -->
<div id="floating-cart-btn" onclick="cartEngine.open()">
    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    <div class="cart-badge" id="floating-cart-badge">0</div>
</div>

<div id="cart-drawer-overlay" onclick="cartEngine.close()"></div>
<div id="cart-drawer">
    <div class="cd-header">
        <h3>Your Bag</h3>
        <button class="cd-close" onclick="cartEngine.close()">&times;</button>
    </div>
    <div class="cd-body" id="cart-items-container">
        <!-- Rendered via JS -->
    </div>
    <div class="cd-footer">
        <div class="cd-total-row">
            <span class="cd-total-lbl">Subtotal</span>
            <span class="cd-total-val" id="cart-total-price">0.00 MAD</span>
        </div>
        <button class="cd-checkout-btn" onclick="cartEngine.checkout()">Complete Checkout</button>
    </div>
</div>

<script>
const SHOP_NAME = <?= json_encode($shopName) ?>;
const SHOP_PHONE = <?= json_encode($shop['ShopPhone'] ?? $shop['OwnerPhone'] ?? '') ?>;

const cartEngine = {
    items: [],
    
    init() {
        try {
            const saved = localStorage.getItem('qoon_store_cart_' + <?= $shopID ?>);
            if (saved) this.items = JSON.parse(saved);
        } catch(e) {}
        this.render();
    },
    
    save() {
        localStorage.setItem('qoon_store_cart_' + <?= $shopID ?>, JSON.stringify(this.items));
        this.render();
    },
    
    add(id, name, price, photo, addQty = 1) {
        const exist = this.items.find(x => x.id === id);
        if (exist) {
            exist.qty += addQty;
        } else {
            this.items.push({id, name, price, photo, qty: addQty});
        }
        this.save();
        
        // ping animation
        const btn = document.getElementById('floating-cart-btn');
        btn.classList.add('ping');
        setTimeout(() => btn.classList.remove('ping'), 300);
    },
    
    update(id, delta) {
        const exist = this.items.find(x => x.id === id);
        if (exist) {
            exist.qty += delta;
            if (exist.qty <= 0) this.items = this.items.filter(x => x.id !== id);
            this.save();
        }
    },
    
    render() {
        let total = 0;
        let count = 0;
        const container = document.getElementById('cart-items-container');
        
        if (this.items.length === 0) {
            container.innerHTML = `<div class="cd-empty">Your bag is currently empty.</div>`;
        } else {
            container.innerHTML = this.items.map(item => {
                total += item.price * item.qty;
                count += item.qty;
                return `
                <div class="cd-item">
                    <img class="cd-item-img" src="${item.photo}">
                    <div class="cd-item-info">
                        <div class="cd-item-name">${item.name}</div>
                        <div class="cd-item-price">${Number(item.price).toFixed(2)} MAD</div>
                    </div>
                    <div class="cd-item-ctrls">
                        <button class="cd-btn" onclick="cartEngine.update(${item.id}, -1)">-</button>
                        <span class="cd-qty">${item.qty}</span>
                        <button class="cd-btn" onclick="cartEngine.update(${item.id}, 1)">+</button>
                    </div>
                </div>
                `;
            }).join('');
        }
        
        document.getElementById('cart-total-price').innerText = total.toFixed(2) + ' MAD';
        document.getElementById('floating-cart-badge').innerText = count;
        
        const floatingBtn = document.getElementById('floating-cart-btn');
        if (count > 0) floatingBtn.classList.add('visible');
        else floatingBtn.classList.remove('visible');
    },
    
    open() {
        document.getElementById('cart-drawer-overlay').classList.add('active');
        document.getElementById('cart-drawer').classList.add('active');
        document.body.style.overflow = 'hidden';
    },
    
    close() {
        document.getElementById('cart-drawer-overlay').classList.remove('active');
        document.getElementById('cart-drawer').classList.remove('active');
        document.body.style.overflow = '';
    },
    
    checkout() {
        if (this.items.length === 0) return alert('Your cart is empty.');
        
        // Ensure cart is saved to local storage
        this.save();
        
        // Redirect to a dedicated checkout application page
        window.location.href = 'checkout.php?id=' + <?= $shopID ?>;
    }
};

const productApp = {
    currentId: null,
    currentPrice: 0,
    currentQty: 1,

    open(id, skipHistory = false) {
        const p = STORE_PRODUCTS[id];
        if(!p) return;
        
        this.currentId    = p.id;
        this.currentPrice = Number(p.price);
        this.currentQty   = 1;
        this.selectedExtras = {}; // { groupId: { id, name, price } }
        
        document.getElementById('pp-img').src = p.img;
        document.getElementById('pp-title').innerText = p.name;
        document.getElementById('pp-desc').innerText = p.desc || 'No additional description available for this product.';
        document.getElementById('pp-cat-pill').innerText = '• ' + (p.cat || 'Product');
        
        // Render extras
        this.renderExtras(p.extras || []);
        this.renderPriceUI();
        
        document.getElementById('pp-add-btn').onclick = () => {
            const extras = Object.values(this.selectedExtras);
            cartEngine.add(p.id, p.name, this.computePrice(), p.img, this.currentQty, extras);
            cartEngine.open();
        };
        
        // Push State History
        if (!skipHistory) {
            const url = new URL(window.location);
            url.searchParams.set('p', id);
            window.history.pushState({ productId: id }, '', url);
        }
        
        // Show Product Page on top of everything
        const page = document.getElementById('product-page-section');
        page.style.display = 'block';
        page.scrollTop = 0;
    },

    computePrice() {
        let base = Number(this.currentPrice);
        Object.values(this.selectedExtras).forEach(e => { base += Number(e.price || 0); });
        return base;
    },

    renderExtras(groups) {
        const container = document.getElementById('pp-extras-container');
        container.innerHTML = '';
        if (!groups || groups.length === 0) return;

        groups.forEach(grp => {
            const isColor = grp.name.toLowerCase().includes('color');
            const isSize  = grp.name.toLowerCase().includes('size');
            const isMulty = grp.multy === 'YES';

            const section = document.createElement('div');
            section.className = 'pp-extra-group';

            const label = document.createElement('div');
            label.className = 'pp-extra-label';
            label.innerHTML = `${grp.name} <span class="pp-extra-required">${isMulty ? 'Multiple' : 'Pick one'}</span>`;
            section.appendChild(label);

            const items = document.createElement('div');
            items.className = isColor ? 'pp-extra-colors' : 'pp-extra-chips';

            grp.items.forEach(itm => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('data-grp', grp.id);
                btn.setAttribute('data-id',  itm.id);

                if (isColor && itm.color) {
                    btn.className = 'pp-color-swatch';
                    btn.style.background = itm.color;
                    btn.title = itm.name + (itm.price > 0 ? ` +${itm.price.toFixed(2)} MAD` : '');
                } else {
                    btn.className = 'pp-chip';
                    const priceTag = itm.price > 0 ? `<span class="pp-chip-price">+${itm.price.toFixed(2)}</span>` : '';
                    btn.innerHTML = itm.name + priceTag;
                }

                btn.addEventListener('click', () => {
                    // If single-pick: unselect siblings
                    if (!isMulty) {
                        items.querySelectorAll('[data-grp="'+grp.id+'"]').forEach(b => b.classList.remove('selected'));
                        // Toggle off if already selected
                        if (this.selectedExtras[grp.id]?.id === itm.id) {
                            delete this.selectedExtras[grp.id];
                            this.renderPriceUI();
                            return;
                        }
                    }
                    btn.classList.toggle('selected');
                    if (btn.classList.contains('selected')) {
                        if (isMulty) {
                            if (!this.selectedExtras[grp.id]) this.selectedExtras[grp.id] = {};
                            this.selectedExtras[grp.id][itm.id] = itm;
                        } else {
                            this.selectedExtras[grp.id] = itm;
                        }
                    } else {
                        if (isMulty && this.selectedExtras[grp.id]) {
                            delete this.selectedExtras[grp.id][itm.id];
                        } else {
                            delete this.selectedExtras[grp.id];
                        }
                    }
                    this.renderPriceUI();
                });

                items.appendChild(btn);
            });

            section.appendChild(items);
            container.appendChild(section);
        });
    },


    updateQty(diff) {
        let q = this.currentQty + diff;
        if(q < 1) q = 1;
        if(q > 99) q = 99;
        this.currentQty = q;
        this.renderPriceUI();
    },
    
    renderPriceUI() {
        const total = this.computePrice() * this.currentQty;
        document.getElementById('pp-qty-val').innerText  = this.currentQty;
        document.getElementById('pp-btn-price').innerText = total.toFixed(2) + ' MAD';
    },
    
    close(skipHistory = false) {
        if (!skipHistory) {
            const url = new URL(window.location);
            url.searchParams.delete('p');
            window.history.pushState({ productId: null }, '', url);
        }
        
        document.getElementById('product-page-section').style.display = 'none';
    }
};

window.addEventListener('popstate', (e) => {
    if (e.state && e.state.productId) {
        productApp.open(e.state.productId, true);
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('p')) {
            productApp.open(urlParams.get('p'), true);
        } else {
            productApp.close(true);
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    cartEngine.init();
    
    // Check initial direct link loading
    const urlParams = new URLSearchParams(window.location.search);
    const initialProductId = urlParams.get('p');
    if (initialProductId && STORE_PRODUCTS[initialProductId]) {
        window.history.replaceState({ productId: initialProductId }, '', window.location);
        productApp.open(initialProductId, true);
    } else {
        window.history.replaceState({ productId: null }, '', window.location);
    }
});
</script>

<!-- PRODUCT DETAILS PAGE SECTION -->
<section id="product-page-section" class="product-page-section">
    <div class="pp-hero">
        <button class="pp-back" onclick="productApp.close()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        </button>
        <img id="pp-img" src="" alt="">
    </div>
    
    <div class="pp-body">
        
        <div class="pp-pills-row">
            <span class="pp-pill pp-pill-cat" id="pp-cat-pill">• Category</span>
            <span class="pp-pill pp-pill-hot">• HOT</span>
        </div>
        
        <div class="pp-rating-row">
            <div class="pp-stars">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v15.77l-6.18 3.25 1.18-6.88L2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <span class="pp-rating-text">4.9 Stars</span>
        </div>

        <div class="pp-title" id="pp-title"></div>

        <div class="pp-desc" id="pp-desc"></div>

        <!-- EXTRA MODIFIERS -->
        <div id="pp-extras-container"></div>

        <div class="pp-checkout-block">
            <div class="pp-qty-picker">
                <button class="pp-qty-btn" onclick="productApp.updateQty(-1)">−</button>
                <div class="pp-qty-val" id="pp-qty-val">1</div>
                <button class="pp-qty-btn" onclick="productApp.updateQty(1)">+</button>
            </div>
            
            <button class="pp-checkout-btn" id="pp-add-btn" onclick="">
                <span class="pp-checkout-price" id="pp-btn-price"></span> 
                <span class="pp-checkout-divider">|</span> 
                <span class="pp-checkout-text">ADD TO CART & COMPLETE PURCHASE</span>
            </button>
            <div class="pp-supported-text">Supported by <?= htmlspecialchars($shopName) ?></div>
        </div>
    </div>
</section>

</body>
</html>
