<?php
// ============================================================
// QOON — Search API (search_api.php)
// SECURITY: Prepared statements, CORS restricted, rate limited
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

// Restrict CORS to known origins
secureCors(['https://qoon.app', 'https://www.qoon.app']);

$ip = getClientIp();
if (!rateLimit($con, 'search', $ip, 30, 60)) {
    http_response_code(429);
    echo json_encode(['shops' => [], 'products' => [], 'posts' => [], 'reels' => [], 'error' => 'Too many requests']);
    exit;
}

$q = sanitizeString($_GET['q'] ?? '', 200);

if (strlen($q) < 1) {
    echo json_encode(['shops' => [], 'products' => [], 'posts' => [], 'reels' => []]);
    exit;
}

// LIKE pattern — safe for prepared statement binding
$like = "%$q%";

$results = ['shops' => [], 'products' => [], 'posts' => [], 'reels' => []];

function getSearchImage($photoRaw, $name, $domain) {
    if (!$photoRaw || $photoRaw === '0')
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=222&color=fff';
    if (strpos($photoRaw, '[') === 0) {
        $decoded = json_decode($photoRaw, true);
        if (is_array($decoded) && count($decoded) > 0) $photoRaw = $decoded[0];
    }
    if (strpos($photoRaw, 'http') === 0) return $photoRaw;
    return rtrim($domain, '/') . '/photo/' . ltrim($photoRaw, '/');
}

// ─── 1. SHOPS ────────────────────────────────────────────────
$stmt = $con->prepare("SELECT ShopID, ShopName, ShopLogo, ShopCover, CategoryId FROM Shops WHERE Status='ACTIVE' AND ShopName LIKE ? ORDER BY ShopName ASC LIMIT 10");
$stmt->bind_param("s", $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $row['ShopLogo']  = getSearchImage($row['ShopLogo'],  $row['ShopName'], $DomainNamee);
    $row['ShopCover'] = getSearchImage($row['ShopCover'], $row['ShopName'], $DomainNamee);
    $row['ShopDesc']  = 'Shop';
    $results['shops'][] = $row;
}
$stmt->close();

// ─── 2. PRODUCTS ─────────────────────────────────────────────
$stmt = $con->prepare("SELECT f.FoodID, f.FoodName, f.FoodDesc, f.FoodPrice, f.FoodPhoto as FoodImage, s.ShopID, s.ShopName, s.ShopLogo FROM Foods f JOIN ShopsCategory sc ON f.FoodCatID = sc.CategoryShopID JOIN Shops s ON sc.ShopID = s.ShopID WHERE s.Status='ACTIVE' AND (f.FoodName LIKE ? OR f.FoodDesc LIKE ?) ORDER BY f.FoodName ASC LIMIT 12");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $row['FoodImage'] = getSearchImage($row['FoodImage'], $row['FoodName'], $DomainNamee);
    $row['ShopLogo']  = getSearchImage($row['ShopLogo'],  $row['ShopName'], $DomainNamee);
    $results['products'][] = $row;
}
$stmt->close();

// ─── 3. POSTS ────────────────────────────────────────────────
$stmt = $con->prepare("SELECT p.PostId AS PostID, p.PostText, p.PostPhoto as Photo, p.CreatedAtPosts as PostDate, s.ShopID, s.ShopName, s.ShopLogo, (SELECT COUNT(*) FROM Likes WHERE PostID = p.PostId) AS LikesCount FROM Posts p JOIN Shops s ON p.ShopID = s.ShopID WHERE s.Status='ACTIVE' AND p.PostStatus='ACTIVE' AND p.PostText LIKE ? AND (p.Video IS NULL OR p.Video='' OR p.Video='0') ORDER BY p.CreatedAtPosts DESC LIMIT 10");
$stmt->bind_param("s", $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $row['Photo']    = getSearchImage($row['Photo'],    '', $DomainNamee);
    $row['ShopLogo'] = getSearchImage($row['ShopLogo'], $row['ShopName'], $DomainNamee);
    $results['posts'][] = $row;
}
$stmt->close();

// ─── 4. REELS ────────────────────────────────────────────────
$stmt = $con->prepare("SELECT p.PostId AS PostID, p.PostText, p.Video, p.BunnyS, p.CreatedAtPosts as PostDate, s.ShopID, s.ShopName, s.ShopLogo, (SELECT COUNT(*) FROM Likes WHERE PostID = p.PostId) AS LikesCount FROM Posts p JOIN Shops s ON p.ShopID = s.ShopID WHERE s.Status='ACTIVE' AND p.PostStatus='ACTIVE' AND (p.PostText LIKE ? OR s.ShopName LIKE ?) AND p.Video IS NOT NULL AND p.Video != '' AND p.Video != '0' ORDER BY p.CreatedAtPosts DESC LIMIT 8");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $row['Thumbnail'] = getSearchImage($row['BunnyS'] ?: $row['Video'], '', $DomainNamee);
    $row['ShopLogo']  = getSearchImage($row['ShopLogo'], $row['ShopName'], $DomainNamee);
    $results['reels'][] = $row;
}
$stmt->close();

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
