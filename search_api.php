<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conn.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode(['shops' => [], 'products' => [], 'posts' => [], 'reels' => []]);
    exit;
}

$safe = $con->real_escape_string($q);

$results = ['shops' => [], 'products' => [], 'posts' => [], 'reels' => []];

function getSearchImage($photoRaw, $name, $domain) {
    if (!$photoRaw || $photoRaw === '0') return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=222&color=fff';
    if (strpos($photoRaw, '[') === 0) {
        $decoded = json_decode($photoRaw, true);
        if (is_array($decoded) && count($decoded) > 0) $photoRaw = $decoded[0];
    }
    // Return direct HTTP links immediately, bypassing localhost rewrites
    if (strpos($photoRaw, 'http') === 0) return $photoRaw;
    
    return rtrim($domain, '/') . '/photo/' . ltrim($photoRaw, '/');
}

// ─── 1. SHOPS ────────────────────────────────────────────────────────────────
$sql = "SELECT ShopID, ShopName, ShopLogo, ShopCover, CategoryId
        FROM Shops
        WHERE Status = 'ACTIVE'
          AND ShopName LIKE '%$safe%'
        ORDER BY ShopName ASC
        LIMIT 10";
$r = $con->query($sql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $row['ShopLogo']  = getSearchImage($row['ShopLogo'],  $row['ShopName'], $DomainNamee);
        $row['ShopCover'] = getSearchImage($row['ShopCover'], $row['ShopName'], $DomainNamee);
        $row['ShopDesc']  = 'Shop';
        $results['shops'][] = $row;
    }
}

// ─── 2. PRODUCTS ─────────────────────────────────────────────────────────────
$sql = "SELECT f.FoodID, f.FoodName, f.FoodDesc, f.FoodPrice, f.FoodPhoto as FoodImage,
               s.ShopID, s.ShopName, s.ShopLogo
        FROM Foods f
        JOIN ShopsCategory sc ON f.FoodCatID = sc.CategoryShopID
        JOIN Shops s ON sc.ShopID = s.ShopID
        WHERE s.Status = 'ACTIVE'
          AND (f.FoodName LIKE '%$safe%' OR f.FoodDesc LIKE '%$safe%')
        ORDER BY f.FoodName ASC
        LIMIT 12";
$r = $con->query($sql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $row['FoodImage'] = getSearchImage($row['FoodImage'], $row['FoodName'], $DomainNamee);
        $row['ShopLogo']  = getSearchImage($row['ShopLogo'],  $row['ShopName'], $DomainNamee);
        $row['is_global'] = false;
        $results['products'][] = $row;
    }
}

// ─── 2.5 ALIEXPRESS PRODUCTS (GLOBAL SEARCH) ─────────────────────────────────
// ─── 2.5 ALIEXPRESS PRODUCTS (GLOBAL SEARCH) ─────────────────────────────────
if (file_exists('aliexpress_api.php')) {
    require_once 'aliexpress_api.php';
} elseif (file_exists('dash/aliexpress_api.php')) {
    require_once 'dash/aliexpress_api.php';
} elseif (file_exists('../dash/aliexpress_api.php')) {
    require_once '../dash/aliexpress_api.php';
}

if (function_exists('getAliExpressSearch')) {
    $globalSearch = getAliExpressSearch($q);
    foreach ($globalSearch as $g) {
        $results['products'][] = [
            'FoodID' => $g['id'],
            'FoodName' => $g['name'],
            'FoodDesc' => $g['desc'],
            'FoodPrice' => $g['price'],
            'oldPrice' => $g['oldPrice'],
            'FoodImage' => $g['img'],
            'ShopID' => 'ali',
            'ShopName' => $g['shopName'],
            'ShopLogo' => $g['shopLogo'],
            'is_global' => true
        ];
    }
}


// ─── 3. POSTS ─────────────────────────────────────────────────────────────────
$sql = "SELECT p.PostId AS PostID, p.PostText, p.PostPhoto as Photo, p.CreatedAtPosts as PostDate,
               s.ShopID, s.ShopName, s.ShopLogo,
               (SELECT COUNT(*) FROM Likes WHERE PostID = p.PostId) AS LikesCount
        FROM Posts p
        JOIN Shops s ON p.ShopID = s.ShopID
        WHERE s.Status = 'ACTIVE' AND p.PostStatus = 'ACTIVE'
          AND (p.PostText LIKE '%$safe%')
          AND (p.Video IS NULL OR p.Video = '' OR p.Video = '0')
        ORDER BY p.CreatedAtPosts DESC
        LIMIT 10";
$r = $con->query($sql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $row['Photo']    = getSearchImage($row['Photo'],    '', $DomainNamee);
        $row['ShopLogo'] = getSearchImage($row['ShopLogo'], $row['ShopName'], $DomainNamee);
        $results['posts'][] = $row;
    }
}

// ─── 4. REELS ────────────────────────────────────────────────────────────────
$sql = "SELECT p.PostId AS PostID, p.PostText, p.Video, p.BunnyS, p.CreatedAtPosts as PostDate,
               s.ShopID, s.ShopName, s.ShopLogo,
               (SELECT COUNT(*) FROM Likes WHERE PostID = p.PostId) AS LikesCount
        FROM Posts p
        JOIN Shops s ON p.ShopID = s.ShopID
        WHERE s.Status = 'ACTIVE' AND p.PostStatus = 'ACTIVE'
          AND (p.PostText LIKE '%$safe%' OR s.ShopName LIKE '%$safe%')
          AND p.Video IS NOT NULL AND p.Video != '' AND p.Video != '0'
        ORDER BY p.CreatedAtPosts DESC
        LIMIT 8";
$r = $con->query($sql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $row['Thumbnail'] = getSearchImage($row['BunnyS'] ?: $row['Video'], '', $DomainNamee);
        $row['ShopLogo']  = getSearchImage($row['ShopLogo'], $row['ShopName'], $DomainNamee);
        $results['reels'][] = $row;
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
