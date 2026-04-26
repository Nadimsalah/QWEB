<?php
define('FROM_UI', true);
define('OFFLINE_MODE', false);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);
require_once 'includes/helpers.php';
require_once 'includes/components.php';

// ── AJAX early exit — must be BEFORE header.php to avoid injecting nav HTML into feed ──
if (isset($_GET['ajax_load_posts'])) {
    // All AJAX post loading logic runs here then exits, never reaching header.php
    // (handled below in the full AJAX block — we just need to skip header loading)
    define('IS_AJAX_REQUEST', true);
}

if (!defined('IS_AJAX_REQUEST')) {
    // Include the new global header which handles categories fetching & sorting
    require_once 'includes/header.php';
}

/* Fetch Feed Posts */
$posts = [];
try {
    if ($con) {
        // --- Geolocation Sorting Logic ---
        $userLat = isset($_COOKIE['qoon_lat']) && is_numeric($_COOKIE['qoon_lat']) ? (float) $_COOKIE['qoon_lat'] : null;
        $userLon = isset($_COOKIE['qoon_lon']) && is_numeric($_COOKIE['qoon_lon']) ? (float) $_COOKIE['qoon_lon'] : null;
        $locationRequired = (!$userLat || !$userLon);

        $haversineField = "";
        $havingClause = "";
        $orderClause = "ORDER BY (COALESCE(Posts.Postcomments, 0) * 2 * RAND()) DESC, Posts.CreatedAtPosts DESC";

        if ($userLat !== null && $userLon !== null) {
            $haversineField = ", (6372.797 * acos(cos(radians($userLat)) * cos(radians(Shops.ShopLat)) * cos(radians(Shops.ShopLongt) - radians($userLon)) + sin(radians($userLat)) * sin(radians(Shops.ShopLat)))) AS distance";
            $havingClause = "HAVING distance <= 500 OR distance IS NULL";
            $orderClause = "ORDER BY distance IS NULL, (((100 / (distance + 0.1)) + (COALESCE(Posts.Postcomments, 0) * 2)) * RAND()) DESC, Posts.CreatedAtPosts DESC";
        }

        // Helper function for random products from all categories
        if (!function_exists('getRandomProductsHtml')) {
            function getRandomProductsHtml($con, $DomainNamee)
            {
                if (!$con)
                    return '';
                $html = '';
                $rpQuery = "SELECT Foods.FoodID, Foods.FoodName, Foods.FoodPrice, Foods.FoodPhoto, Shops.ShopName, Shops.ShopLogo, Shops.ShopID
                            FROM Foods 
                            JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID
                            JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID
                            JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                            WHERE (Categories.Pro IS NULL OR Categories.Pro != 'Pro')
                              AND (Categories.Type IS NULL OR Categories.Type != 'B2B')
                              AND Foods.FoodPhoto != '' AND Foods.FoodPhoto IS NOT NULL AND Foods.FoodPhoto != 'NONE'
                            ORDER BY RAND() 
                            LIMIT 100";
                $rpProductsByShop = [];
                $rpUniqueProducts = [];
                $rpUniqueNames = [];
                $stmtRP = $con->prepare($rpQuery);
                if ($stmtRP) {
                    $stmtRP->execute();
                    $rpRes = $stmtRP->get_result();
                    if ($rpRes && $rpRes->num_rows > 0) {
                        while ($row = $rpRes->fetch_assoc()) {
                            $foodId = $row['FoodID'] ?? $row['ProductID'] ?? 0;
                            $foodName = strtolower(trim($row['FoodName'] ?? ''));
                            if (!isset($rpUniqueProducts[$foodId]) && !isset($rpUniqueNames[$foodName])) {
                                $rpUniqueProducts[$foodId] = true;
                                if ($foodName)
                                    $rpUniqueNames[$foodName] = true;
                                $shopId = $row['ShopID'];
                                if (!isset($rpProductsByShop[$shopId])) {
                                    $rpProductsByShop[$shopId] = [];
                                }
                                $rpProductsByShop[$shopId][] = $row;
                            }
                        }
                    }
                    $stmtRP->close();
                }

                $finalRp = [];
                $added = true;
                $idx = 0;
                while ($added && count($finalRp) < 10) {
                    $added = false;
                    foreach ($rpProductsByShop as $shopId => $items) {
                        if (isset($items[$idx])) {
                            $finalRp[] = $items[$idx];
                            $added = true;
                        }
                        if (count($finalRp) >= 10)
                            break;
                    }
                    $idx++;
                }

                shuffle($finalRp); // Scatters products from same shop

                if (!empty($finalRp)) {
                    $html .= '<div style="width:100%;padding:30px 0;margin-top:20px;margin-bottom:20px;background:transparent;overflow:hidden;border-top:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.05);">';
                    $html .= '<h3 style="font-size:18px;font-weight:600;padding:0;margin-bottom:24px;color:var(--text-main);">Discover Products</h3>';
                    $html .= '<div class="no-scrollbar" style="display:flex;gap:16px;overflow-x:auto;padding:0;width:100%;scrollbar-width:none; scroll-snap-type: x mandatory;">';
                    foreach ($finalRp as $kp) {
                        $kpPhotoRaw = $kp['FoodPhoto'] ?? $kp['PostPhoto'] ?? $kp['PostPhoto1'] ?? null;
                        $kpPhoto = get_img_url($kpPhotoRaw, $DomainNamee ?? null);
                        $kpShopLogo = get_img_url($kp['ShopLogo'] ?? null, $DomainNamee ?? null);
                        $kpName = htmlspecialchars($kp['FoodName'] ?? $kp['PostText'] ?? 'Exclusive Item');
                        $kpImg = htmlspecialchars($kpPhoto ?: 'https://ui-avatars.com/api/?name=Item&background=222&color=fff');
                        $kpSLogo = htmlspecialchars($kpShopLogo ?: 'https://ui-avatars.com/api/?name=S');
                        $kpSName = htmlspecialchars($kp['ShopName'] ?? 'Shop');
                        $kpPriceHtml = (!empty($kp['FoodPrice']) && $kp['FoodPrice'] > 0) ? '<div style="font-size: 12px; font-weight: 700; color: var(--purple-glow);">' . htmlspecialchars($kp['FoodPrice']) . ' MAD</div>' : '';
                        $kpPriceVal = floatval($kp['FoodOfferPrice'] ?? 0) > 0 ? floatval($kp['FoodOfferPrice']) : floatval($kp['FoodPrice'] ?? 0);
                        $kpOldPrice = floatval($kp['FoodOfferPrice'] ?? 0) > 0 ? floatval($kp['FoodPrice'] ?? 0) : null;
                        $foodJson = json_encode([
                            'id' => $kp['FoodID'] ?? $kp['ProductID'] ?? 0,
                            'name' => $kp['FoodName'] ?? 'Product',
                            'price' => $kpPriceVal,
                            'oldPrice' => $kpOldPrice,
                            'img' => $kpImg,
                            'desc' => $kp['FoodDesc'] ?? '',
                            'cat_id' => $kp['CategoryId'] ?? $kp['CategoryID'] ?? 0,
                            'extra1' => $kp['Extraone'] ?? '',
                            'extra2' => $kp['Extratwo'] ?? '',
                            'extra1_p' => floatval($kp['ExtraPriceOne'] ?? 0),
                            'extra2_p' => floatval($kp['ExtraPriceTwo'] ?? 0)
                        ]);
                        $html .= '<div style="flex: 0 0 160px; scroll-snap-align: start; display: flex; flex-direction: column; gap: 8px; cursor: pointer; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); overflow: hidden; padding-bottom: 12px; transition: background 0.2s;" onclick="openProductModal(this)" data-product=\'' . htmlspecialchars($foodJson, ENT_QUOTES, 'UTF-8') . '\'>
                                      <img src="' . $kpImg . '" onerror="this.src=\'https://ui-avatars.com/api/?name=Item&background=222&color=fff\'" style="width:100%; aspect-ratio: 1; object-fit: cover; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                      <div style="padding: 0 12px;">
                                          <div style="font-size: 13px; font-weight: 600; color: #fff; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; margin-bottom: 4px;">' . $kpName . '</div>
                                          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                              <img src="' . $kpSLogo . '" onerror="this.src=\'https://ui-avatars.com/api/?name=S\'" style="width: 14px; height: 14px; border-radius: 50%; object-fit: cover;">
                                              <span style="font-size: 11px; color: var(--text-muted); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">' . $kpSName . '</span>
                                          </div>
                                          ' . $kpPriceHtml . '
                                      </div>
                                  </div>';
                    }
                    $html .= '</div></div>';
                }
                return $html;
            }
        }

        if (!function_exists('getDynamicReelsHtml')) {
            function getDynamicReelsHtml($con, $DomainNamee)
            {
                if (!$con)
                    return '';
                $query = "
                    SELECT * FROM (
                        SELECT Posts.Video, Posts.BunnyS as Thumbnail, Shops.ShopName, Shops.ShopLogo, Posts.PostID AS SortID, 'Video' AS MediaType, 'post' AS SourceType
                        FROM Posts 
                        JOIN Shops ON Shops.ShopID = Posts.ShopID 
                        WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus = 'ACTIVE' AND (Posts.Video != '' AND Posts.Video != '0')
                        UNION ALL 
                        SELECT ShopStory.StoryPhoto AS Video, ShopStory.BunnyS as Thumbnail, Shops.ShopName, Shops.ShopLogo, ShopStory.StotyID AS SortID, ShopStory.StotyType AS MediaType, 'story' AS SourceType
                        FROM Shops 
                        JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID 
                        WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus = 'ACTIVE'
                    ) AS combined ORDER BY RAND() LIMIT 8";

                $stmt = $con->prepare($query);
                if (!$stmt)
                    return '';
                $stmt->execute();
                $res = $stmt->get_result();
                if (!$res || $res->num_rows == 0)
                    return '';

                $domain = $DomainNamee ?? 'https://qoon.app/dash/';
                $uuid = uniqid();

                $html = '<div class="feed-inline-reels" style="width:100vw;position:relative;left:50%;transform:translateX(-50%);padding:40px 0;margin-top:20px;margin-bottom:20px;background:transparent;overflow:hidden;border-top:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.05);">';
                $html .= '<h3 style="font-size:18px;font-weight:600;padding:0 max(20px,calc(50vw - 340px));margin-bottom:24px;color:var(--text-main);">Suggested Reels</h3>';
                $html .= '<div id="reels-track-' . $uuid . '" class="reels-track" style="display:flex;gap:16px;overflow-x:auto;padding:0 max(20px,calc(50vw - 340px));scroll-padding-inline:max(20px,calc(50vw - 340px));width:100%;scrollbar-width:none;scroll-snap-type:x mandatory;">';

                while ($row = $res->fetch_assoc()) {
                    $rawThumb = str_replace('jibler.app', 'qoon.app', trim($row['Thumbnail'] ?? ''));
                    $rawVideo = str_replace('jibler.app', 'qoon.app', trim($row['Video'] ?? ''));

                    $thumbUrl = get_img_url($rawThumb, $domain);
                    $mediaUrl = get_img_url($rawVideo === '-' ? '' : $rawVideo, $domain);
                    $logoUrl = get_img_url(trim($row['ShopLogo'] ?? ''), $domain);

                    $ext = strtolower(pathinfo($rawVideo, PATHINFO_EXTENSION));
                    $mt = strtoupper(trim($row['MediaType']));
                    $isV = ($mt === 'VIDEO' || in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv']));
                    $media = $isV ? 'video' : 'image';

                    $url = 'reel.php?id=' . $row['SortID'] . '&type=' . $row['SourceType'] . '&media=' . $media;

                    $html .= '<div class="reel-card-real" onclick="location.href=\'' . $url . '\'" style="width:160px;aspect-ratio:9/16;border-radius:16px;flex-shrink:0;position:relative;overflow:hidden;cursor:pointer;background:#111;scroll-snap-align:start;">';

                    // Thumbnail Rendering
                    if ($isV) {
                        if ($thumbUrl && $rawThumb != '' && $rawThumb != '0' && $rawThumb != '-') {
                            $html .= '<img src="' . $thumbUrl . '" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" onerror="this.src=\'' . $logoUrl . '\'">';
                        } else {
                            $html .= '<video src="' . $mediaUrl . '#t=0.5" preload="metadata" muted playsinline style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"></video>';
                            $html .= '<img src="' . $logoUrl . '" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" />';
                        }
                    } else {
                        $html .= '<img src="' . $mediaUrl . '" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" onerror="this.src=\'' . $logoUrl . '\'">';
                    }

                    // Gradient overlay
                    $html .= '<div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 40%);"></div>';
                    // Logo and text
                    $html .= '<div style="position:absolute;bottom:12px;left:12px;right:12px;display:flex;align-items:center;gap:8px;z-index:2;">';
                    $html .= '<img src="' . $logoUrl . '" style="width:24px;height:24px;border-radius:50%;border:2px solid #fff;object-fit:cover;" onerror="this.style.display=\'none\'">';
                    $html .= '<span style="color:#fff;font-size:12px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,0.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . htmlspecialchars($row['ShopName']) . '</span>';
                    $html .= '</div></div>';
                }

                $html .= '</div></div>';
                $stmt->close();
                return $html;
            }
        }

        // --- AJAX Pagination Interceptor ---
        if (isset($_GET['ajax_load_posts'])) {
            $limit = 5;

            $seenArr = isset($_COOKIE['qoon_seen_posts']) ? explode(',', $_COOKIE['qoon_seen_posts']) : [];
            $seenList = !empty($seenArr) ? implode(',', array_map('intval', array_filter($seenArr))) : "";
            $seenClause = !empty($seenList) ? "AND Posts.PostID NOT IN ($seenList)" : "";

            $ajaxQuery = "SELECT Shops.*, Posts.*, Foods.* $haversineField
                          FROM Posts 
                          LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                          LEFT JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                          LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID 
                          WHERE Posts.PostStatus='ACTIVE' 
                            AND (Categories.Pro IS NULL OR Categories.Pro != 'Pro')
                            AND (Categories.Type IS NULL OR Categories.Type != 'B2B')
                            AND (Posts.Video = '' OR Posts.Video = '0' OR Posts.Video IS NULL)
                            $seenClause
                          $havingClause
                          $orderClause 
                          LIMIT ?";
            $stmt = $con->prepare($ajaxQuery);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $ajaxRes = $stmt->get_result();

                // --- START FROM ZERO LOGIC ---
                if ($ajaxRes && $ajaxRes->num_rows == 0 && !empty($seenArr)) {
                    $seenArr = [];
                    setcookie('qoon_seen_posts', '', time() - 3600, '/');
                    $seenClause = "";
                    $orderClause = "ORDER BY RAND()"; // Mix it up when recycling
                    $ajaxQuery = "SELECT Shops.*, Posts.*, Foods.* $haversineField
                          FROM Posts 
                          LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                          LEFT JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                          LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID 
                          WHERE Posts.PostStatus='ACTIVE' 
                            AND (Categories.Pro IS NULL OR Categories.Pro != 'Pro')
                            AND (Categories.Type IS NULL OR Categories.Type != 'B2B')
                            AND (Posts.Video = '' OR Posts.Video = '0' OR Posts.Video IS NULL)
                          $havingClause
                          $orderClause 
                          LIMIT ?";
                    $stmt = $con->prepare($ajaxQuery);
                    $stmt->bind_param("i", $limit);
                    $stmt->execute();
                    $ajaxRes = $stmt->get_result();
                }
                if ($ajaxRes && $ajaxRes->num_rows > 0) {
                    $ajaxIndex = 0;
                    $offset = intval($_GET['offset'] ?? 0);
                    $htmlOut = "";
                    while ($post = $ajaxRes->fetch_assoc()) {
                        // Mark as seen
                        if (!in_array($post['PostID'], $seenArr)) {
                            $seenArr[] = $post['PostID'];
                        }

                        // Extract safe variables
                        // Render HTML Block using component
                        $htmlOut .= renderPostCard($post, $DomainNamee ?? null);

                        $globalIndex = $offset + $ajaxIndex;
                        if ($globalIndex % 4 == 2 && $globalIndex >= 2) {
                            $htmlOut .= getDynamicReelsHtml($con, $DomainNamee);
                        }
                        if ($globalIndex % 4 == 0 && $globalIndex >= 4) {
                            $htmlOut .= getRandomProductsHtml($con, $DomainNamee);
                        }
                        $ajaxIndex++;
                    }
                    if (count($seenArr) > 100)
                        $seenArr = array_slice($seenArr, -100);
                    setcookie('qoon_seen_posts', implode(',', $seenArr), time() + 86400 * 7, '/');
                    echo $htmlOut;
                } else {
                    echo "END_OF_FEED"; // Signal JS to stop observing
                }
                $stmt->close();
            } else {
                echo "END_OF_FEED";
            }
            if (isset($con) && $con)
                mysqli_close($con);
            exit;
        }

        // Fetch Kenz Madinty Products
        $kenzProducts = [];
        $kenzQ = "SELECT Foods.FoodID, Foods.FoodName, Foods.FoodPrice, Foods.FoodPhoto, Shops.ShopName, Shops.ShopLogo, Shops.ShopID
                  FROM Foods 
                  JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID
                  JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID
                  JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                  WHERE Categories.Type = 'Small' 
                    AND (Categories.Pro IS NULL OR Categories.Pro != 'Pro') 
                    AND Foods.FoodPhoto != '' AND Foods.FoodPhoto IS NOT NULL AND Foods.FoodPhoto != 'NONE'
                  ORDER BY RAND() 
                  LIMIT 100";
        $productsByShop = [];
        $uniqueProducts = [];
        $uniqueNames = [];
        $stmtK = $con->prepare($kenzQ);
        if ($stmtK) {
            $stmtK->execute();
            $kRes = $stmtK->get_result();
            if ($kRes && $kRes->num_rows > 0) {
                while ($row = $kRes->fetch_assoc()) {
                    $foodId = $row['FoodID'] ?? $row['ProductID'] ?? 0;
                    $foodName = strtolower(trim($row['FoodName'] ?? ''));
                    if (!isset($uniqueProducts[$foodId]) && !isset($uniqueNames[$foodName])) {
                        $uniqueProducts[$foodId] = true;
                        if ($foodName)
                            $uniqueNames[$foodName] = true;
                        $shopId = $row['ShopID'];
                        if (!isset($productsByShop[$shopId])) {
                            $productsByShop[$shopId] = [];
                        }
                        $productsByShop[$shopId][] = $row;
                    }
                }
            }
            $stmtK->close();
        }

        $kenzProducts = [];
        $added = true;
        $idx = 0;
        while ($added && count($kenzProducts) < 15) {
            $added = false;
            foreach ($productsByShop as $shopId => $items) {
                if (isset($items[$idx])) {
                    $kenzProducts[] = $items[$idx];
                    $added = true;
                }
                if (count($kenzProducts) >= 15)
                    break;
            }
            $idx++;
        }

        shuffle($kenzProducts); // Scatters products from same shop

        // --- Standard Initial Load ---
        $seenArr = isset($_COOKIE['qoon_seen_posts']) ? explode(',', $_COOKIE['qoon_seen_posts']) : [];
        $seenList = !empty($seenArr) ? implode(',', array_map('intval', array_filter($seenArr))) : "";
        $seenClause = !empty($seenList) ? "AND Posts.PostID NOT IN ($seenList)" : "";

        $postQuery = "SELECT Shops.*, Posts.*, Foods.* $haversineField
                      FROM Posts 
                      LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                      LEFT JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                      LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID 
                      WHERE Posts.PostStatus='ACTIVE' 
                        AND (Categories.Pro IS NULL OR Categories.Pro != 'Pro')
                        AND (Categories.Type IS NULL OR Categories.Type != 'B2B')
                        AND (Posts.Video = '' OR Posts.Video = '0' OR Posts.Video IS NULL)
                        $seenClause
                      $havingClause
                      $orderClause 
                      LIMIT 5";
        $stmt = $con->prepare($postQuery);
        if ($stmt) {
            $stmt->execute();
            $pRes = $stmt->get_result();

            // --- START FROM ZERO LOGIC FOR INITIAL LOAD ---
            if ($pRes && $pRes->num_rows == 0 && !empty($seenArr)) {
                $seenArr = [];
                setcookie('qoon_seen_posts', '', time() - 3600, '/');
                $seenClause = "";
                $postQuery = "SELECT Shops.*, Posts.*, Foods.* $haversineField
                      FROM Posts 
                      LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                      LEFT JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                      LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID 
                      WHERE Posts.PostStatus='ACTIVE' 
                        AND (Categories.Pro IS NULL OR Categories.Pro != 'Pro')
                        AND (Categories.Type IS NULL OR Categories.Type != 'B2B')
                        AND (Posts.Video = '' OR Posts.Video = '0' OR Posts.Video IS NULL)
                      $havingClause
                      ORDER BY RAND() 
                      LIMIT 5";
                $stmt = $con->prepare($postQuery);
                $stmt->execute();
                $pRes = $stmt->get_result();
            }

            if ($pRes && $pRes->num_rows > 0) {
                while ($row = $pRes->fetch_assoc()) {
                    if (!in_array($row['PostID'], $seenArr)) {
                        $seenArr[] = $row['PostID'];
                    }
                    $posts[] = $row;
                }
            }
            if (count($seenArr) > 100)
                $seenArr = array_slice($seenArr, -100);
            setcookie('qoon_seen_posts', implode(',', $seenArr), time() + 86400 * 7, '/');
            $stmt->close();
        }
    }
} catch (Throwable $e) {
}

/* Fetch Suggested Slides (Boosts -> Sliders) */
$slides = [];
try {
    if ($con) {
        $slideQuery = "SELECT Sliders.*, Shops.ShopName 
                       FROM Sliders 
                       LEFT JOIN Shops ON Sliders.ShopID = Shops.ShopID 
                       WHERE Sliders.DefaultPhoto = 'YES' OR Sliders.INHOME = 'YES'
                       LIMIT 10";
        $stmtS = $con->prepare($slideQuery);
        if ($stmtS) {
            $stmtS->execute();
            $sRes = $stmtS->get_result();
            if ($sRes && $sRes->num_rows > 0) {
                while ($row = $sRes->fetch_assoc()) {
                    $slides[] = $row;
                }
            }
            $stmtS->close();
        }
    }
} catch (Throwable $e) {
}

/* Fetch Shorts / Reels */
$reels = [];
try {
    if ($con) {
        $reelQuery = "
            SELECT * FROM (
                SELECT Posts.Video, Posts.BunnyS as Thumbnail, Posts.Video AS BunnyV, Shops.ShopName, Shops.ShopLogo, Posts.PostID AS SortID, 'Video' AS MediaType, 'post' AS SourceType
                FROM Posts 
                JOIN Shops ON Shops.ShopID = Posts.ShopID 
                WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus = 'ACTIVE' AND (Posts.Video != '' AND Posts.Video != '0')
                
                UNION ALL 
                
                SELECT ShopStory.StoryPhoto AS Video, ShopStory.BunnyS as Thumbnail, ShopStory.StoryPhoto AS BunnyV, Shops.ShopName, Shops.ShopLogo, ShopStory.StotyID AS SortID, ShopStory.StotyType AS MediaType, 'story' AS SourceType
                FROM Shops 
                JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID 
                WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus = 'ACTIVE'
            ) AS all_videos 
            ORDER BY SortID DESC 
            LIMIT 12
        ";
        $stmtR = $con->prepare($reelQuery);
        if ($stmtR) {
            $stmtR->execute();
            $rRes = $stmtR->get_result();
            if ($rRes && $rRes->num_rows > 0) {
                while ($row = $rRes->fetch_assoc()) {
                    $reels[] = $row;
                }
            }
            $stmtR->close();
        }
    }
} catch (Throwable $e) {
}

// Fallback logic for offline mock environment parity
if (empty($reels)) {
    for ($i = 0; $i < 6; $i++) {
        $reels[] = [
            'Video' => '',
            'Thumbnail' => "https://picsum.photos/400/700?random=10$i",
            'ShopName' => 'Creator Shop ' . ($i + 1),
            'ShopLogo' => "https://ui-avatars.com/api/?name=Cr&background=random",
            'MediaType' => ($i % 2 == 0) ? 'Video' : 'Image' // alternate mock types
        ];
    }
}

if (empty($slides)) {
    // Fallback Mock Slides
    $slides = [
        ['ShopName' => 'Promo Store', 'BoostPhoto' => 'https://ui-avatars.com/api/?name=Promo+Store&background=ffbb00&color=000&size=300'],
        ['ShopName' => 'Featured Shop', 'BoostPhoto' => 'https://ui-avatars.com/api/?name=Featured+Shop&background=1e90ff&color=fff&size=300'],
        ['ShopName' => 'Local Deal', 'BoostPhoto' => 'https://ui-avatars.com/api/?name=Local+Deal&background=32cd32&color=fff&size=300'],
        ['ShopName' => 'Trending Food', 'BoostPhoto' => 'https://ui-avatars.com/api/?name=Trending+Food&background=ff4500&color=fff&size=300'],
        ['ShopName' => 'Hot Fashion', 'BoostPhoto' => 'https://ui-avatars.com/api/?name=Hot+Fashion&background=800080&color=fff&size=300']
    ];
}

if (empty($posts)) {
    // Fallback Mock Posts
    $posts = [
        [
            'ShopName' => 'Stitch Burger',
            'ShopPhoto' => '',
            'PostPhoto' => '',
            'PostText' => 'Enjoy our ultimate double-cheese burger designed perfectly for you.',
            'PostLikes' => '1.2k',
            'Postcomments' => '128',
            'ProductID' => '123',
            'FoodNameEn' => 'Double Cheese Burger',
            'Price' => '12.00',
            'CreatedAtPosts' => '2026-04-21 12:00:00'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QOON - S-Commerce Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- Firebase SDK (Compat Version) -->
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
            console.log("Firebase Initialized Successfully");
        } catch (e) {
            console.error("Firebase Initialization Error:", e);
        }
    </script>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        /* ─── GUARANTEED FULL-BLEED LAYOUT (BYPASS CACHE) ─── */
        html, body {
            max-width: 100vw !important;
            overflow-x: hidden !important;
            overscroll-behavior-x: none !important;
        }
        
        .content-wrapper {
            padding-left: 0 !important;
            padding-right: 0 !important;
            overflow-x: hidden !important;
        }
        
        .content-wrapper > main {
            padding-left: 40px !important;
            padding-right: 40px !important;
        }
        
        .feed-section {
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: none !important;
        }

        /* Force category cards to touch screen edges WITHOUT negative margins */
        .categories-section {
            width: 100% !important;
            overflow: hidden !important;
            padding: 16px 0 0 0 !important;
            margin: 0 !important;
        }
        .category-grid {
            display: flex !important;
            overflow-x: auto !important;
            padding: 0 40px 0 40px !important;
            margin-bottom: 0 !important;
            gap: 24px !important;
            scrollbar-width: none !important;
            scroll-snap-type: x mandatory !important;
            -webkit-overflow-scrolling: touch !important;
            touch-action: pan-x !important;
        }
        .category-grid::-webkit-scrollbar {
            display: none !important;
        }
        .category-grid::after {
            content: '' !important;
            display: block !important;
            flex: 0 0 20px !important;
        }
        .cat-header {
            padding-left: 40px !important;
            padding-right: 40px !important;
        }
        @media (max-width: 768px) {
            .cat-header {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            .category-grid {
                padding: 0 20px 0 20px !important;
                gap: 16px !important;
            }
        }
        
        /* ─── PREMIUM UI ENHANCEMENTS ─── */

        /* 1. Glassmorphism Hover Effects for Cards */
        div[onclick*="openProductModal"],
        div[onclick*="window.location.href"],
        .cat-card,
        .reel-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        }

        div[onclick*="openProductModal"]:hover,
        div[onclick*="window.location.href"]:hover,
        .cat-card:hover,
        .reel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(245, 0, 87, 0.1) !important;
            background: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        /* 2. Tactile Click/Tap Effect */
        div[onclick*="openProductModal"]:active,
        div[onclick*="window.location.href"]:active,
        .cat-card:active,
        .glass-action-btn:active {
            transform: scale(0.96) !important;
        }

        /* 3. Smooth Fade-in & Slide-up Animation for Page Sections */
        .content-wrapper>div {
            animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .content-wrapper>div:nth-child(1) {
            animation-delay: 0.1s;
        }

        .content-wrapper>div:nth-child(2) {
            animation-delay: 0.2s;
        }

        .content-wrapper>div:nth-child(3) {
            animation-delay: 0.3s;
        }

        .content-wrapper>div:nth-child(4) {
            animation-delay: 0.4s;
        }

        .content-wrapper>div:nth-child(5) {
            animation-delay: 0.5s;
        }

        .content-wrapper>div:nth-child(6) {
            animation-delay: 0.6s;
        }

        .content-wrapper>div:nth-child(7) {
            animation-delay: 0.7s;
        }

        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 4. Elegant Typography Enhancements */
        .section-title {
            letter-spacing: 0.5px;
            text-transform: capitalize;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            margin-bottom: 16px !important;
        }

        /* 5. Sleeker Scrollbars for Carousels */
        ::-webkit-scrollbar {
            height: 4px;
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>

    <!-- Background Elements -->
    <div class="aurora-container">
        <div class="aurora-blob blob-1"></div>
        <div class="aurora-blob blob-2"></div>
        <div class="aurora-blob blob-3"></div>
    </div>

    <div class="grid-bg"></div>
    <div class="grid-glow" id="gridGlow"></div>

    <!-- Main Application UI -->
    <div class="content-wrapper">

        <main>
            <?php
            $activeOrders = [];
            if ($con && isset($_COOKIE['qoon_user_id'])) {
                $uid = $con->real_escape_string($_COOKIE['qoon_user_id']);
                $q = "SELECT o.OrderID, o.OrderState, o.OrderDetails, s.ShopName, s.ShopLogo 
                      FROM Orders o 
                      LEFT JOIN Shops s ON o.ShopID = s.ShopID 
                      WHERE o.UserID = '$uid' 
                      AND LOWER(o.OrderState) NOT IN ('cancelled', 'canceled', 'finish', 'done', 'rated', 'delivered', 'order delivered', 'cancel')
                      ORDER BY o.OrderID DESC LIMIT 10";
                $res = $con->query($q);
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $activeOrders[] = $r;
                    }
                }
            }
            ?>

            <div class="hero-text">
                <h1>Where Everything<br>Connects.</h1>
                <p>Everything you love, everything you need &mdash; unified in a single experience.</p>
            </div>

            <?php if (count($activeOrders) > 0): ?>
                <div class="active-orders-slider no-scrollbar"
                    style="display:flex; overflow-x:auto; scroll-snap-type: x mandatory; gap: 12px; padding-bottom: 8px; margin-bottom: 24px;">
                    <?php foreach ($activeOrders as $ao):
                        $statusRaw = strtolower(trim($ao['OrderState'] ?? 'waiting'));
                        $statusMap = [
                            'waiting' => 'Waiting for Driver',
                            'doing' => 'Driver Assigned',
                            'driver_offer' => 'Driver Offers',
                            'come to take it' => 'Driver on the way',
                            'on way' => 'Driver on the way',
                            'on the way' => 'Driver on the way',
                            'order pickup' => 'Order Picked Up',
                            'pickup' => 'Order Picked Up',
                            'prepared' => 'Order Prepared',
                            'processed' => 'Order Processed'
                        ];
                        $statusStr = $statusMap[$statusRaw] ?? ucfirst($statusRaw);

                        $shopName = htmlspecialchars($ao['ShopName'] ?? 'QOON Shop');
                        $logoRaw = $ao['ShopLogo'] ?? '';
                        $logo = '';
                        if (!empty($logoRaw)) {
                            if (strpos($logoRaw, 'http') === 0)
                                $logo = $logoRaw;
                            else
                                $logo = 'https://qoon.app/userDriver/UserDriverApi/photo/' . $logoRaw;
                        } else {
                            $logo = 'https://ui-avatars.com/api/?name=' . urlencode($shopName) . '&background=random';
                        }
                        $desc = htmlspecialchars(mb_strimwidth($ao['OrderDetails'] ?? 'Your order is active', 0, 40, "..."));
                        ?>
                        <div class="active-order-card"
                            style="flex: 0 0 <?= count($activeOrders) > 1 ? 'calc(100% - 32px)' : '100%' ?>; scroll-snap-align: start; min-width: <?= count($activeOrders) > 1 ? 'calc(100% - 32px)' : '100%' ?>; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(16px); border-radius: 20px; padding: 16px; display: flex; align-items: center; gap: 16px; cursor: pointer; transition: 0.3s; box-shadow: 0 8px 32px rgba(0,0,0,0.2);"
                            onclick="window.location.href='track_order.php?orderId=<?= $ao['OrderID'] ?>'">
                            <img src="<?= $logo ?>" onerror="this.src='https://ui-avatars.com/api/?name=S'"
                                style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.1);">
                            <div style="flex: 1; min-width: 0;">
                                <div
                                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                    <div
                                        style="font-weight: 800; color: #fff; font-size: 15px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                        <?= $shopName ?></div>
                                    <div
                                        style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 8px; text-transform: uppercase;">
                                        <i class="fa-solid fa-satellite-dish fa-fade"
                                            style="margin-right:4px;"></i><?= $statusStr ?></div>
                                </div>
                                <div
                                    style="font-size: 13px; color: rgba(255,255,255,0.6); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                    <?= $desc ?></div>
                            </div>
                            <div
                                style="width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0;">
                                <i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="search-wrapper">
                <div class="prompt-container" id="promptBox">
                    <button class="icon-btn" style="margin-left: 4px;"><i
                            class="fa-solid fa-magnifying-glass"></i></button>

                    <input type="text" class="prompt-input" readonly onclick="window.location.href='search.php'"
                        onfocus="window.location.href='search.php'" placeholder="Search shops, products, reels...">

                    <div class="prompt-toolbar">
                        <div class="platform-toggle">
                            <button type="button" class="toggle-btn active" id="btn-current-loc">
                                <i class="fa-solid fa-location-crosshairs"></i> <span>Current Location</span>
                            </button>
                            <button type="button" class="toggle-btn" id="btn-teleport">
                                <i class="fa-solid fa-plane"></i> <span>Teleport Mode</span>
                            </button>
                        </div>

                        <button class="submit-btn"><i class="fa-solid fa-arrow-up"></i></button>
                    </div>
                </div>

                <!-- NEW: Search Extras (Logo & Glass Buttons) -->
                <div class="search-extras" style="position: relative; z-index: 50; pointer-events: auto;">
                    <div class="extra-left">
                        <img src="qoon_pay_logo.png" alt="QOON Pay" class="qpay-extra-logo"
                            onclick="<?php echo isset($_COOKIE['qoon_user_id']) ? "openQpayDrawer()" : "openSignup()"; ?>"
                            style="cursor:pointer;">
                    </div>
                    <div class="extra-right">
                        <button class="glass-action-btn"
                            onclick="<?php echo isset($_COOKIE['qoon_user_id']) ? "openQpayDrawer()" : "openSignup()"; ?>">
                            <i class="fa-solid fa-plus"></i> Topup
                        </button>
                        <button class="glass-action-btn"
                            onclick="<?php echo isset($_COOKIE['qoon_user_id']) ? "openQpayDrawer()" : "openSignup()"; ?>">
                            <i class="fa-solid fa-paper-plane"></i> Transfer
                        </button>
                        <button class="glass-action-btn"
                            onclick="<?php echo isset($_COOKIE['qoon_user_id']) ? "showMyQR()" : "openSignup()"; ?>">
                            <i class="fa-solid fa-qrcode"></i> QR Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- End of main content block -->
        </main>

        <!-- --- Mini Apps (Categories) Section --- -->
        <section class="categories-section">
            <div class="cat-header">
                <h3 style="font-size: 18px; font-weight: 600; margin: 0; color: var(--text-main);">Mini Apps</h3>
                <div class="cat-nav">
                    <button class="nav-arrow" id="scrollLeft"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="nav-arrow" id="scrollRight"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="category-grid" id="catGrid">
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $catTitle = $cat['EnglishCategory'] ?? $cat['ArabCategory'] ?? $cat['NameEn'] ?? '';
                    if ($cat['CategoryId'] === 'flights') {
                        $targetUrl = "flights.php";
                    } elseif ($cat['CategoryId'] === 'esims') {
                        $targetUrl = "esim.php";
                    } else {
                        $targetUrl = (stripos($catTitle, 'Kenz') !== false) ? "kenz.php?cat=" . $cat['CategoryId'] : "category.php?cat=" . $cat['CategoryId'];
                    }
                    ?>
                    <div class="cat-card" onclick="window.location.href='<?= $targetUrl ?>'">
                        <div class="cat-img-wrapper">
                            <img class="cat-img" loading="lazy" src="<?= htmlspecialchars($cat['Photo'] ?? '') ?>"
                                onerror="this.src='https://ui-avatars.com/api/?name=S&background=2cb5e8&color=fff'"
                                style="pointer-events: none; -webkit-user-drag: none;">
                        </div>
                        <div class="cat-name">
                            <?= htmlspecialchars($catTitle) ?>
                        </div>
                        <div class="cat-tag">Explore</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- --- Feed Section --- -->
        <section class="feed-section">
            <h2 class="section-title" style="text-align: center; margin-bottom: 48px;">S-Commerce</h2>

            <?php if ($locationRequired ?? true): ?>
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

                <div class="feed-container">
                    <?php ob_start(); ?>
                    <!-- YouTube Shorts Style Reels Lane -->
                    <div class="feed-inline-reels"
                        style="margin: 20px calc(-50vw + 50%); width: 100vw; padding: 40px 0; background: transparent; border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <h3
                            style="font-size:18px;font-weight:600;padding:0 max(20px,calc(50vw - 320px));margin-bottom:24px;color:var(--text-main);">
                            Stories &amp; Reels</h3>

                        <!-- Track: shimmer cards shown immediately, real cards injected by JS -->
                        <div id="reels-track" class="reels-track"
                            style="display:flex;gap:16px;overflow-x:auto;padding:0 max(20px,calc(50vw - 320px));scroll-padding-inline:max(20px,calc(50vw - 320px));width:100%;scrollbar-width:none;scroll-snap-type:x mandatory;">

                            <?php
                            /* â”€â”€ Pre-render shimmer placeholders (shown before JS loads) â”€â”€ */
                            for ($s = 0; $s < 5; $s++): ?>
                                <div class="reel-shimmer"
                                    style="width:200px;aspect-ratio:9/16;border-radius:16px;flex-shrink:0;overflow:hidden;background:rgba(255,255,255,0.05);position:relative;">
                                    <div
                                        style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.07) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.4s infinite;">
                                    </div>
                                    <!-- bottom bar shimmer -->
                                    <div
                                        style="position:absolute;bottom:16px;left:12px;right:12px;display:flex;align-items:center;gap:8px;">
                                        <div
                                            style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.1);flex-shrink:0;">
                                        </div>
                                        <div style="height:11px;border-radius:4px;background:rgba(255,255,255,0.08);flex:1;">
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Shimmer keyframe (only defined once) -->


                        <!-- Embed all reel data as JSON â€” no PHP HTML loop needed -->
                        <?php
                        $domain = $DomainNamee ?? 'https://qoon.app/dash/';
                        $reelJsonArr = [];
                        foreach ($reels as $reel) {
                            $raw = str_replace('jibler.app', 'qoon.app', trim($reel['Video'] ?? ''));
                            $ext = strtolower(pathinfo($raw, PATHINFO_EXTENSION));
                            $mt = strtoupper(trim($reel['MediaType'] ?? ''));
                            $isV = ($mt === 'VIDEO' || in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv']));
                            $url = get_img_url($raw === '-' ? '' : $raw, $domain);
                            if (!$url)
                                continue;
                            $logo = get_img_url(trim($reel['ShopLogo'] ?? ''), $domain);
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
                        ?>
                        <script>
                            (function () {
                                const REELS_DATA = <?= json_encode($reelJsonArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>.sort(() => 0.5 - Math.random());
                                const track = document.getElementById('reels-track');
                                const shimmers = Array.from(track.querySelectorAll('.reel-shimmer'));
                                const BATCH = 4; // cards per frame
                                let idx = 0;

                                function buildCard(r) {
                                    const card = document.createElement('div');
                                    card.className = 'reel-card-real';
                                    card.onclick = () => location.href = 'reel.php?id=' + r.id + '&type=' + r.type + '&media=' + r.media;

                                    if (r.isVideo) {
                                        // Video: show black initially, seek to 0.5s on load
                                        const vid = document.createElement('video');
                                        vid.muted = true;
                                        vid.playsInline = true;
                                        vid.preload = 'none';
                                        vid.dataset.src = r.url;
                                        vid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;opacity:0;transition:opacity .4s;';
                                        vid.onerror = () => { vid.style.display = 'none'; img2.style.display = 'block'; };

                                        const img2 = document.createElement('img');
                                        img2.src = r.logo;
                                        img2.style.cssText = 'display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;';

                                        card.appendChild(vid);
                                        card.appendChild(img2);

                                        // Play icon badge
                                        const badge = document.createElement('div');
                                        badge.innerHTML = '<i class="fa-solid fa-play" style="margin-left:3px"></i>';
                                        badge.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:2;width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;border:1px solid rgba(255,255,255,0.3);';
                                        card.appendChild(badge);
                                    } else {
                                        // Image story
                                        const img = document.createElement('img');
                                        img.loading = 'lazy';
                                        img.src = r.url;
                                        img.alt = r.shop;
                                        img.onerror = () => { img.src = r.logo; };
                                        card.appendChild(img);
                                    }

                                    // Gradient overlay
                                    const grad = document.createElement('div');
                                    grad.style.cssText = 'position:absolute;bottom:0;left:0;right:0;height:55%;background:linear-gradient(transparent,rgba(0,0,0,0.5));z-index:1;pointer-events:none;';
                                    card.appendChild(grad);

                                    // Shop branding
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
                                        // Replace shimmer if available, otherwise append
                                        if (shimmers[idx]) {
                                            track.replaceChild(card, shimmers[idx]);
                                        } else {
                                            track.appendChild(card);
                                        }
                                    }
                                    if (idx < REELS_DATA.length) {
                                        requestAnimationFrame(renderBatch); // next frame
                                    } else {
                                        // All rendered â€” start lazy-loading video thumbs
                                        initVideoThumbs();
                                    }
                                }

                                function initVideoThumbs() {
                                    const vids = track.querySelectorAll('video[data-src]');
                                    if (!('IntersectionObserver' in window)) {
                                        vids.forEach(loadThumb); return;
                                    }
                                    const obs = new IntersectionObserver((entries, o) => {
                                        entries.forEach(e => {
                                            if (e.isIntersecting) { loadThumb(e.target); o.unobserve(e.target); }
                                        });
                                    }, { rootMargin: '0px 400px 0px 400px' });
                                    vids.forEach(v => obs.observe(v));
                                }

                                function loadThumb(vid) {
                                    if (vid.dataset.loaded) return;
                                    vid.dataset.loaded = '1';
                                    vid.preload = 'auto';
                                    vid.addEventListener('loadedmetadata', () => {
                                        vid.currentTime = Math.min(0.5, vid.duration || 0.5);
                                    }, { once: true });
                                    vid.addEventListener('seeked', () => { vid.style.opacity = '1'; }, { once: true });
                                    setTimeout(() => { vid.style.opacity = '1'; }, 2000); // hard fallback
                                    vid.src = vid.dataset.src;
                                    vid.load();
                                }

                                // Start after DOM is painted â€” use rAF so page renders first
                                requestAnimationFrame(renderBatch);
                            })();
                        </script>
                    </div>
                    <?php
                    $reelsHtml = ob_get_clean();
                    if (!function_exists('getRandomReelsHtml')) {
                        function getRandomReelsHtml($src)
                        {
                            $uuid = uniqid();
                            return str_replace('reels-track', 'reels-track-' . $uuid, $src);
                        }
                    }
                    ?>

                    <?php if (empty($posts))
                        echo getRandomReelsHtml($reelsHtml); ?>

                    <?php foreach ($posts as $index => $post): ?>
                        <?php echo renderPostCard($post, $DomainNamee ?? null); ?>

                        <?php if ($index > 0 && $index % 5 == 4 && count($slides) > 0): ?>
                            <!-- 3D Auto-Rotating Single Card Slider -->
                            <div class="post-card"
                                style="padding: 16px; margin-bottom: 24px; display: flex; flex-direction: column;">
                                <h3
                                    style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-bullseye" style="color: #2cb5e8;"></i> Promoted For You
                                </h3>

                                <div class="promo-3d-viewport" data-slider-group="<?= $index ?>"
                                    style="position: relative; width: 100%; aspect-ratio: 16/9; perspective: 1200px;">
                                    <?php
                                    // Shuffle slides for each instance so they look different
                                    $shuffledSlides = $slides;
                                    shuffle($shuffledSlides);
                                    foreach ($shuffledSlides as $i => $sugg): ?>
                                        <?php
                                        $suggPhoto = get_img_url($sugg['SliderPhoto'] ?? null, $DomainNamee ?? null);
                                        ?>
                                        <div class="promo-3d-slide" data-index="<?= $i ?>"
                                            style="position: absolute; inset: 0; width: 100%; height: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transform-style: preserve-3d; transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
                                        opacity: <?= $i === 0 ? '1' : '0' ?>; 
                                        transform: <?= $i === 0 ? 'rotateY(0deg) translateZ(0)' : 'rotateY(90deg) translateZ(100px)' ?>; 
                                        z-index: <?= $i === 0 ? '2' : '1' ?>; pointer-events: <?= $i === 0 ? 'auto' : 'none' ?>;">

                                            <img loading="lazy" src="<?= htmlspecialchars($suggPhoto) ?>"
                                                style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                                onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($sugg['ShopName'] ?? 'S') ?>&background=random&color=fff'">

                                            <div
                                                style="position: absolute; bottom: 0; left: 0; right: 0; padding: 16px; background: linear-gradient(transparent, rgba(0,0,0,0.4)); display: flex; align-items: center; gap: 8px;">
                                                <div
                                                    style="font-weight: 600; font-size: 14px; text-shadow: 0 2px 4px rgba(0,0,0,0.8); color: white;">
                                                    <?= htmlspecialchars($sugg['ShopName'] ?? 'QOON Promoted') ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($index == 1 && !empty($kenzProducts)): ?>
                            <div
                                style="width:100%;padding:30px 0;margin-top:20px;margin-bottom:20px;background:transparent;overflow:hidden;border-top:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.05);">
                                <h3 style="font-size:18px;font-weight:600;padding:0;margin-bottom:24px;color:var(--text-main);">
                                    Suggested from Kenz Madinty</h3>
                                <div class="no-scrollbar"
                                    style="display:flex;gap:16px;overflow-x:auto;padding:0;width:100%;scrollbar-width:none; scroll-snap-type: x mandatory;">
                                    <?php foreach ($kenzProducts as $kp): ?>
                                        <?php
                                        $kpPhotoRaw = $kp['FoodPhoto'] ?? $kp['PostPhoto'] ?? $kp['PostPhoto1'] ?? null;
                                        $kpPhoto = get_img_url($kpPhotoRaw, $DomainNamee ?? null);
                                        $kpShopLogo = get_img_url($kp['ShopLogo'] ?? null, $DomainNamee ?? null);
                                        $kpPriceVal = floatval($kp['FoodOfferPrice'] ?? 0) > 0 ? floatval($kp['FoodOfferPrice']) : floatval($kp['FoodPrice'] ?? 0);
                                        $kpOldPrice = floatval($kp['FoodOfferPrice'] ?? 0) > 0 ? floatval($kp['FoodPrice'] ?? 0) : null;
                                        $foodJson = json_encode([
                                            'id' => $kp['FoodID'] ?? $kp['ProductID'] ?? 0,
                                            'name' => $kp['FoodName'] ?? 'Product',
                                            'price' => $kpPriceVal,
                                            'oldPrice' => $kpOldPrice,
                                            'img' => htmlspecialchars($kpPhoto ?: 'https://ui-avatars.com/api/?name=Item&background=222&color=fff'),
                                            'desc' => $kp['FoodDesc'] ?? '',
                                            'cat_id' => $kp['CategoryId'] ?? $kp['CategoryID'] ?? 0,
                                            'extra1' => $kp['Extraone'] ?? '',
                                            'extra2' => $kp['Extratwo'] ?? '',
                                            'extra1_p' => floatval($kp['ExtraPriceOne'] ?? 0),
                                            'extra2_p' => floatval($kp['ExtraPriceTwo'] ?? 0)
                                        ]);
                                        ?>
                                        <div style="flex: 0 0 160px; scroll-snap-align: start; display: flex; flex-direction: column; gap: 8px; cursor: pointer; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); overflow: hidden; padding-bottom: 12px; transition: background 0.2s;"
                                            onclick="openProductModal(this)"
                                            data-product='<?= htmlspecialchars($foodJson, ENT_QUOTES, 'UTF-8') ?>'>
                                            <img src="<?= htmlspecialchars($kpPhoto ?: 'https://ui-avatars.com/api/?name=Item&background=222&color=fff') ?>"
                                                onerror="this.src='https://ui-avatars.com/api/?name=Item&background=222&color=fff'"
                                                style="width:100%; aspect-ratio: 1; object-fit: cover; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <div style="padding: 0 12px;">
                                                <div
                                                    style="font-size: 13px; font-weight: 600; color: #fff; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; margin-bottom: 4px;">
                                                    <?= htmlspecialchars($kp['FoodName'] ?? $kp['PostText'] ?? 'Exclusive Item') ?>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                                    <img src="<?= htmlspecialchars($kpShopLogo ?: 'https://ui-avatars.com/api/?name=S') ?>"
                                                        onerror="this.src='https://ui-avatars.com/api/?name=S'"
                                                        style="width: 14px; height: 14px; border-radius: 50%; object-fit: cover;">
                                                    <span
                                                        style="font-size: 11px; color: var(--text-muted); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"><?= htmlspecialchars($kp['ShopName'] ?? 'Shop') ?></span>
                                                </div>
                                                <?php if (!empty($kp['FoodPrice']) && $kp['FoodPrice'] > 0): ?>
                                                    <div style="font-size: 12px; font-weight: 700; color: var(--purple-glow);">
                                                        <?= htmlspecialchars($kp['FoodPrice']) ?> MAD
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($index == 0)
                            echo getRandomReelsHtml($reelsHtml); ?>
                        <?php if ($index == 3)
                            echo getRandomProductsHtml($con, $DomainNamee); ?>
                    <?php endforeach; ?>

                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            document.querySelectorAll('.promo-3d-viewport').forEach(vp => {
                                const slides = vp.querySelectorAll('.promo-3d-slide');
                                if (slides.length <= 1) return;
                                let cur = 0;
                                setInterval(() => {
                                    slides[cur].style.opacity = '0';
                                    slides[cur].style.transform = 'rotateY(-90deg) translateZ(100px)';
                                    slides[cur].style.zIndex = '1';
                                    slides[cur].style.pointerEvents = 'none';
                                    cur = (cur + 1) % slides.length;
                                    slides[cur].style.transition = 'none';
                                    slides[cur].style.transform = 'rotateY(90deg) translateZ(100px)';
                                    void slides[cur].offsetWidth;
                                    slides[cur].style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                                    slides[cur].style.opacity = '1';
                                    slides[cur].style.transform = 'rotateY(0deg) translateZ(0)';
                                    slides[cur].style.zIndex = '2';
                                    slides[cur].style.pointerEvents = 'auto';
                                }, 3000 + Math.random() * 1000); // stagger timings
                            });
                        });
                    </script>

                    <!-- Shimmer Loading Skeletons -->
                    <div id="feed-shimmer"
                        style="display: none; width: 100%; flex-direction: column; gap: 24px; margin-top: 24px;">
                        <div class="post-card" style="box-shadow: none; opacity: 0.5;">
                            <div class="post-header">
                                <div class="post-avatar"
                                    style="background: rgba(255,255,255,0.05); border:none; border-radius: 50%;"></div>
                                <div class="post-shop-info" style="gap: 8px; display: flex; flex-direction: column;">
                                    <div
                                        style="width: 120px; height: 14px; background: rgba(255,255,255,0.08); border-radius: 4px;">
                                    </div>
                                    <div
                                        style="width: 80px; height: 10px; background: rgba(255,255,255,0.04); border-radius: 4px;">
                                    </div>
                                </div>
                            </div>
                            <div
                                style="width: 100%; height: 300px; background: rgba(255,255,255,0.03); border-radius: 16px; margin-top: 20px;">
                            </div>
                        </div>
                    </div>

                    <!-- Infinite Scroll Trigger -->
                    <div id="feed-bottom-trigger" style="height: 20px; width: 100%; margin-top: 20px;"></div>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <script>
        function updateCarouselIndicator(el) {
            const wrapper = el.closest('.carousel-container');
            if (!wrapper) return;
            const dots = wrapper.querySelectorAll('.carousel-dot');
            const index = Math.round(el.scrollLeft / el.clientWidth);
            dots.forEach((dot, i) => {
                if (i === index) dot.classList.add('active');
                else dot.classList.remove('active');
            });
        }

        // --- Interactive Mouse Logic --- //

        const root = document.documentElement;
        const promptBox = document.getElementById('promptBox');
        if (promptBox) {
            promptBox.addEventListener('click', () => {
                // Smooth transition effect
                promptBox.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                promptBox.style.transform = 'scale(1.05)';
                promptBox.style.boxShadow = '0 0 30px rgba(255,255,255,0.2)';

                document.body.style.transition = 'opacity 0.3s ease';
                document.body.style.opacity = '0';

                setTimeout(() => {
                    window.location.href = 'search.php';
                }, 300);
            });
        }

        // Track mouse movement across the document
        window.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;

            // Set exact coordinates for the grid spotlight mask
            root.style.setProperty('--mouse-x', `${x}px`);
            root.style.setProperty('--mouse-y', `${y}px`);

            // Calculate normalized coordinates (-1 to 1) for the aurora parallax effect
            const normX = (x / window.innerWidth) * 2 - 1;
            const normY = (y / window.innerHeight) * 2 - 1;
            root.style.setProperty('--mouse-x-norm', normX);
            root.style.setProperty('--mouse-y-norm', normY);
        });

        // Add local mouse tracking specifically for the prompt box interior glow
        promptBox.addEventListener('mousemove', (e) => {
            const rect = promptBox.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            promptBox.style.setProperty('--mouse-x', `${x}px`);
            promptBox.style.setProperty('--mouse-y', `${y}px`);
        });

        // Toggle Platform Buttons Interaction
        const toggleBtns = document.querySelectorAll('.toggle-btn');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                toggleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Horizontal scrolling for Categories Carousel
        const catGrid = document.getElementById('catGrid');
        const scrollAmount = 360; // Card width + gap

        document.getElementById('scrollLeft').addEventListener('click', () => {
            catGrid.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        document.getElementById('scrollRight').addEventListener('click', () => {
            catGrid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });


        // --- Continuous Smooth Auto-Scrolling (Right to Left flow) ---
        const carousel = document.getElementById('promo-carousel');
        let autoScrollSpeed = 0.8; // Moving scrollbar right makes content glide Leftwards!
        let isUserHovering = false;

        function autoScrollCarousel() {
            if (!carousel) return;

            if (!isUserHovering) {
                carousel.scrollLeft += autoScrollSpeed;

                // Calculate max boundaries safely
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;

                // Ping-pong softly if it hits the edges, but ONLY if the track is actually wider than the screen
                if (maxScroll > 10) {
                    if (carousel.scrollLeft >= maxScroll - 2) {
                        autoScrollSpeed = -0.8; // Reverse backwards smoothly
                    } else if (carousel.scrollLeft <= 0) {
                        autoScrollSpeed = 0.8; // Forwards again (Right to left)
                    }
                }
            }
            requestAnimationFrame(autoScrollCarousel);
        }

        if (carousel) {
            // Start the buttery continuous scroll
            requestAnimationFrame(autoScrollCarousel);

            // Allow manual swipe on mobile without fighting the bot
            carousel.addEventListener('touchstart', () => isUserHovering = true, { passive: true });
            carousel.addEventListener('touchend', () => setTimeout(() => isUserHovering = false, 1500));
            carousel.addEventListener('wheel', () => {
                isUserHovering = true;
                setTimeout(() => isUserHovering = false, 500);
            }, { passive: true });

            // --- Mouse Drag-To-Scroll Logic (Desktop) ---
            let isDragging = false;
            let startX;
            let startScrollLeft;

            carousel.style.cursor = 'grab';

            carousel.addEventListener('mousedown', (e) => {
                isDragging = true;
                isUserHovering = true; // Pause auto-scroll while physically grabbing
                startX = e.pageX - carousel.offsetLeft;
                startScrollLeft = carousel.scrollLeft;
                carousel.style.cursor = 'grabbing';
            });

            carousel.addEventListener('mouseleave', () => {
                isDragging = false;
                isUserHovering = false; // Resume auto-scroll
                carousel.style.cursor = 'grab';
            });

            carousel.addEventListener('mouseup', () => {
                isDragging = false;
                isUserHovering = false; // Resume auto-scroll
                carousel.style.cursor = 'grab';
            });

            carousel.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.pageX - carousel.offsetLeft;
                const dragDistance = (x - startX) * 1.5;
                carousel.scrollLeft = startScrollLeft - dragDistance;
            });
        } // end if(carousel)
    </script>

    <!-- Reel Immersive Shorts Modal (outside script) -->
    <div id="reel-modal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: #000;">
        <button onclick="closeReelModal()"
            style="position: absolute; top: 24px; left: 24px; z-index: 100000; background: rgba(255,255,255,0.2); border: none; border-radius: 50%; width: 44px; height: 44px; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); transition: background 0.3s;">
            <i class="fa-solid fa-arrow-left" style="font-size: 18px;"></i>
        </button>

        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; max-width: 480px; height: 100vh; display: flex; background: #111;">

            <!-- Media Container -->
            <div id="reel-media-container" style="width: 100%; height: 100%; position: relative;">
                <!-- Video or Image injected via JS -->
            </div>

            <!-- Bottom Left Info Overlay -->
            <div
                style="position: absolute; bottom: 32px; left: 16px; right: 80px; z-index: 10; display: flex; flex-direction: column; gap: 12px; pointer-events: none;">
                <div style="display: flex; align-items: center; gap: 12px; pointer-events: auto;">
                    <img id="reel-modal-logo" src=""
                        style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid white; object-fit: cover; background: #000;">
                    <div style="display: flex; flex-direction: column;">
                        <span id="reel-modal-shop"
                            style="color: white; font-weight: 600; font-size: 16px; text-shadow: 0 1px 4px rgba(0,0,0,0.8);"></span>
                        <span
                            style="color: rgba(255,255,255,0.8); font-size: 13px; text-shadow: 0 1px 4px rgba(0,0,0,0.8);">Stories
                            & Reels</span>
                    </div>
                </div>
            </div>

            <!-- Right Action Bar -->
            <div
                style="position: absolute; bottom: 32px; right: 8px; z-index: 10; display: flex; flex-direction: column; gap: 20px; align-items: center; width: 64px;">
                <div class="reel-action"
                    style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: white; transition: background 0.3s; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fa-solid fa-heart" style="font-size: 18px;"></i>
                    </div>
                    <span
                        style="color: white; font-size: 13px; margin-top: 6px; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.8);">Like</span>
                </div>
                <div class="reel-action"
                    style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: white; transition: background 0.3s; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fa-solid fa-comment-dots" style="font-size: 18px;"></i>
                    </div>
                    <span
                        style="color: white; font-size: 13px; margin-top: 6px; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.8);">Comm</span>
                </div>
                <div class="reel-action"
                    style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div
                        style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: white; transition: background 0.3s; border: 1px solid rgba(255,255,255,0.1);">
                        <i class="fa-solid fa-share" style="font-size: 18px;"></i>
                    </div>
                    <span
                        style="color: white; font-size: 13px; margin-top: 6px; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.8);">Share</span>
                </div>
                <div class="reel-action"
                    style="display: flex; flex-direction: column; align-items: center; cursor: pointer; margin-top: 8px;">
                    <img id="reel-modal-bottom-logo" src=""
                        style="width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid white; object-fit: cover; animation: spin 4s linear infinite;">
                </div>
            </div>
        </div>
    </div>



    <script>
        function openReelModal(mediaUrl, isVideo, logoUrl, shopName) {
            const modal = document.getElementById('reel-modal');
            const container = document.getElementById('reel-media-container');
            document.getElementById('reel-modal-logo').src = logoUrl || 'https://ui-avatars.com/api/?name=S';
            document.getElementById('reel-modal-bottom-logo').src = logoUrl || 'https://ui-avatars.com/api/?name=S';
            document.getElementById('reel-modal-shop').textContent = shopName || 'Shop';
            if (isVideo) {
                container.innerHTML = '<video src="' + mediaUrl + '" controls autoplay playsinline loop style="width:100%;height:100%;object-fit:contain;background:#000;"></video>';
            } else {
                container.innerHTML = '<img src="' + mediaUrl + '" style="width:100%;height:100%;object-fit:contain;background:#000;">';
            }
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        function closeReelModal() {
            document.getElementById('reel-modal').style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('reel-media-container').innerHTML = '';
        }
    </script>



    <script>
        // --- Infinite Scroll Live Feed Logic --- //
        let currentPostOffset = 5;
        let isLoadingPosts = false;
        let endOfFeed = false;
        const feedShimmer = document.getElementById("feed-shimmer");

        if ("IntersectionObserver" in window) {
            let feedObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !isLoadingPosts && !endOfFeed) {
                    isLoadingPosts = true;
                    feedShimmer.style.display = "flex";

                    fetch(window.location.pathname + "?ajax_load_posts=1&offset=" + currentPostOffset)
                        .then(r => r.text())
                        .then(html => {
                            feedShimmer.style.display = "none";
                            if (html.trim() === "END_OF_FEED" || html.includes("END_OF_FEED")) {
                                endOfFeed = true;
                                // Remove the trigger
                                document.getElementById("feed-bottom-trigger").remove();
                            } else {
                                // Inject raw HTML immediately before the shimmer loader
                                feedShimmer.insertAdjacentHTML('beforebegin', html);
                                currentPostOffset += 5;
                            }
                            isLoadingPosts = false;
                        })
                        .catch(e => {
                            console.error("Feed load failed", e);
                            feedShimmer.style.display = "none";
                            isLoadingPosts = false;
                        });
                }
            }, { rootMargin: "0px 0px 400px 0px" }); // Trigger earlier before user hits rock bottom

            const bottomTrigger = document.getElementById("feed-bottom-trigger");
            if (bottomTrigger) feedObserver.observe(bottomTrigger);
        }
    </script>

    <!-- TELEPORT MODAL (LIQUID GLASS LOGIC) -->


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

    <?php include 'includes/modals/teleport.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnTeleport = document.getElementById('btn-teleport');
            const btnCurrent = document.getElementById('btn-current-loc');
            const overlay = document.getElementById('teleport-modal-overlay');
            const closeBtn = document.getElementById('teleport-close-btn');
            const searchInput = document.getElementById('teleport-search-bar');
            const resultsBox = document.getElementById('teleport-results');

            let map = null;
            let currentMarker = null;

            // Cookie Utilities to sync JS state with PHP Haversine Sorting Engine
            function setQoonLocation(lat, lon) {
                const currentLat = getCookie('qoon_lat');
                // Only reload if we actually moved somewhere new
                if (currentLat != lat) {
                    document.cookie = `qoon_lat=${lat}; max-age=2592000; path=/`;
                    document.cookie = `qoon_lon=${lon}; max-age=2592000; path=/`;
                    setTimeout(() => location.reload(), 300); // Trigger PHP re-sort instantly
                }
            }
            function getCookie(name) {
                const v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
                return v ? v[2] : null;
            }

            // Immediately acquire native location on boot to organize the S-Commerce Feed optimally
            if (!getCookie('qoon_lat') && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    setQoonLocation(pos.coords.latitude, pos.coords.longitude);
                }, () => console.log("Location access denied by user."));
            }

            if (btnTeleport) {
                btnTeleport.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    btnTeleport.classList.add('active');
                    if (btnCurrent) btnCurrent.classList.remove('active');

                    overlay.classList.add('open');
                    setTimeout(() => {
                        if (!map) initMap();
                        else map.invalidateSize(); // Fixes tile rendering error in hidden divs
                    }, 400); // Wait for transition
                });
            }

            if (btnCurrent) {
                btnCurrent.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    btnCurrent.classList.add('active');
                    btnTeleport.classList.remove('active');
                    document.querySelector('.prompt-input').value = '';
                    document.querySelector('.prompt-input').placeholder = 'Search local shops, food, and exclusive deals...';

                    // Restore true GPS tracking
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            setQoonLocation(pos.coords.latitude, pos.coords.longitude);
                        });
                    }
                });
            }

            closeBtn.addEventListener('click', () => overlay.classList.remove('open'));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('open');
            });

            function initMap() {
                // Initialize default view to global
                map = L.map('teleport-map', { zoomControl: false }).setView([31.7917, -7.0926], 6);

                // Dark mode CartoDB minimal tiles
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    subdomains: 'abcd',
                    maxZoom: 20
                }).addTo(map);

                // Auto geolocate user to "defult see my location"
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.flyTo([lat, lng], 14, { duration: 1.5 });
                    }, (err) => console.log('Geolocation skipped:', err));
                }
            }

            window.flyToCity = function (lat, lng) {
                if (map) map.flyTo([lat, lng], 14, { duration: 1.5 });
            };

            window.geolocateOnMap = function () {
                if (navigator.geolocation && map) {
                    navigator.geolocation.getCurrentPosition((pos) => {
                        map.flyTo([pos.coords.latitude, pos.coords.longitude], 14, { duration: 1.5 });
                    }, (err) => console.log('Geolocation failed:', err));
                }
            };

            window.confirmTeleport = function () {
                if (!map) return;
                const pos = map.getCenter();
                setQoonLocation(pos.lat, pos.lng);
                overlay.classList.remove('open');
                document.querySelector('.prompt-input').value = 'Exploring near pinned location...';
            };

            // Real-time API search (OpenStreetMap Nominatim)
            let searchTimeout = null;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();

                if (query.length < 3) {
                    resultsBox.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&countrycodes=ma&format=json&addressdetails=1&limit=5`, {
                        headers: { 'Accept-Language': 'ar,fr,en;q=0.9' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            resultsBox.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(city => {
                                    const div = document.createElement('div');
                                    div.className = 'teleport-result-item';
                                    div.innerHTML = `<i class="fa-solid fa-earth-americas"></i> ${city.display_name}`;
                                    div.onclick = () => {
                                        const shortName = city.display_name.split(',')[0];
                                        searchInput.value = shortName;
                                        resultsBox.style.display = 'none';

                                        const lat = parseFloat(city.lat);
                                        const lon = parseFloat(city.lon);

                                        map.flyTo([lat, lon], 14, { duration: 1.5, easeLinearity: 0.25 });
                                    };
                                    resultsBox.appendChild(div);
                                });
                                resultsBox.style.display = 'block';
                            } else {
                                resultsBox.style.display = 'none';
                            }
                        }).catch(() => {
                            resultsBox.style.display = 'none';
                        });
                }, 500); // 500ms debounce
            });

            // Hide results on outside click
            document.addEventListener('click', (e) => {
                if (e.target !== searchInput && e.target !== resultsBox) {
                    resultsBox.style.display = 'none';
                }
            });
        });
    </script>

    <?php include 'includes/modals/comments.php'; ?>
    <?php include 'includes/modals/auth.php'; ?>

    <script>


        /* --- GOOGLE LOGIN INTEGRATION --- */
        function decodeJwtResponse(token) {
            let base64Url = token.split('.')[1];
            let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            let jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        }

        window.googleLogin = function () {
            const provider = new firebase.auth.GoogleAuthProvider();
            const btn = document.querySelector('.btn-google');
            const originalHtml = btn.innerHTML;

            console.log("Starting Google Login...");

            firebase.auth().signInWithPopup(provider).then((result) => {
                const user = result.user;
                console.log("Google Auth Success:", user.email);
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
                        console.log("Backend Response:", json);
                        if (json.success) {
                            btn.innerHTML = '<i class="fa-solid fa-check"></i> Welcome!';
                            const urlP = new URLSearchParams(window.location.search);
                            const rTo = urlP.get('return_to');
                            setTimeout(() => {
                                if (rTo) window.location.href = rTo;
                                else location.reload();
                            }, 1000);
                        } else {
                            btn.innerHTML = originalHtml;
                            alert('Backend Error: ' + (json.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        btn.innerHTML = originalHtml;
                        console.error("Fetch Error:", err);
                        alert("Could not connect to server.");
                    });
            }).catch((error) => {
                console.error("Firebase Login Error:", error.code, error.message);
                if (error.code === 'auth/unauthorized-domain') {
                    alert("Error: This domain is not authorized in Firebase Console. Add your localhost/domain to 'Authorized Domains' in Firebase Authentication settings.");
                } else {
                    alert("Google Login failed: " + error.message);
                }
            });
        };
        // --- LOGOUT CONFIRMATION ---
        function confirmLogout() {
            const modal = document.createElement('div');
            modal.id = 'logoutModal';
            modal.className = 'logout-overlay';
            modal.innerHTML = `
    <div class="glass-modal logout-card">
        <div class="logout-icon-box"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h2>Are you sure?</h2>
        <p>Do you really want to sign out of QOON?</p>
        <div class="logout-btns">
            <button class="l-btn cancel" onclick="this.closest('.logout-overlay').remove()">No, stay</button>
            <button class="l-btn confirm" onclick="window.location.href='logout.php'">Yes, logout</button>
        </div>
    </div>

    `;
            document.body.appendChild(modal);
        }

        if (typeof window.openSignup !== 'function') {
            window.openSignup = function () {
                window.location.href = 'LogOrSign.php';
            };
        }

        // --- MY QR MODAL ---
        function showMyQR() {
            const userId = <?= json_encode($_COOKIE['qoon_user_id'] ?? 'ID-ERROR') ?>;
            const userName = <?= json_encode($_COOKIE['qoon_user_name'] ?? 'QOON User') ?>;
            const qrUrl =
                `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(userId)}&bgcolor=ffffff&color=000&margin=10`;

            const modal = document.createElement('div');
            modal.id = 'qrModal';
            modal.className = 'qr-overlay';
            modal.innerHTML = `
                <div class="qr-flip-container" onclick="this.classList.toggle('flipped')">
                    <div class="qr-flipper">
                        <!-- FRONT OF CARD -->
                        <div class="qr-face qr-front">
                            <button class="close-qr" onclick="event.stopPropagation(); this.closest('.qr-overlay').remove()"><i class="fa-solid fa-xmark"></i></button>
                            
                            <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:40px; filter:brightness(0) invert(1); margin-bottom:20px; opacity:0.9;">
                            
                            <div class="user-badge-qr">
                                <div class="u-name-qr">${userName}</div>
                                <div class="u-phone-qr">ID: ${userId}</div>
                            </div>

                            <div style="margin-top:auto;">
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:rgba(255,255,255,0.1); border-radius:20px; font-size:14px; font-weight:600; color:#fff;">
                                    Tap to Reveal QR <i class="fa-solid fa-hand-pointer fa-bounce"></i>
                                </div>
                            </div>
                        </div>

                        <!-- BACK OF CARD (QR CODE) -->
                        <div class="qr-face qr-back">
                            <button class="close-qr" onclick="event.stopPropagation(); this.closest('.qr-overlay').remove()"><i class="fa-solid fa-xmark"></i></button>
                            <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:30px; filter:brightness(0) invert(1); margin-bottom:15px; opacity:0.5;">
                            
                            <div class="qr-frame" onclick="event.stopPropagation();">
                                <img src="${qrUrl}" alt="QR Code">
                                <div class="qr-corner top-left"></div>
                                <div class="qr-corner top-right"></div>
                                <div class="qr-corner bottom-left"></div>
                                <div class="qr-corner bottom-right"></div>
                            </div>

                            <button class="share-qr-btn" onclick="event.stopPropagation(); shareQR('${userName}', '${userId}')">
                                <i class="fa-solid fa-share-nodes"></i> Share My Code
                            </button>
                        </div>
                    </div>
                </div>
                <style>
                    .qr-overlay { position:fixed; inset:0; z-index:10001; background:rgba(0,0,0,0.85); backdrop-filter:blur(15px); display:flex; align-items:center; justify-content:center; animation: fadeIn 0.4s ease forwards; }
                    
                    .qr-flip-container {
                        perspective: 1500px;
                        width: 90%;
                        max-width: 360px;
                        height: 480px;
                        cursor: pointer;
                        animation: slideUpModal 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
                    }
                    
                    .qr-flip-container.flipped .qr-flipper {
                        transform: rotateY(180deg);
                    }
                    
                    .qr-flipper {
                        position: relative;
                        width: 100%;
                        height: 100%;
                        transition: transform 0.8s cubic-bezier(0.4, 0.0, 0.2, 1);
                        transform-style: preserve-3d;
                    }

                    .qr-face {
                        position: absolute;
                        inset: 0;
                        backface-visibility: hidden;
                        -webkit-backface-visibility: hidden;
                        background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.03));
                        border: 1px solid rgba(255,255,255,0.15);
                        border-radius: 35px;
                        padding: 40px 30px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        box-shadow: 0 30px 60px rgba(0,0,0,0.6), inset 0 0 20px rgba(255,255,255,0.05);
                    }

                    .qr-front {
                        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                        transform: rotateY(0deg);
                        justify-content: center;
                    }

                    .qr-back {
                        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                        transform: rotateY(180deg);
                    }

                    @keyframes slideUpModal { from { transform:translateY(60px) scale(0.95); opacity:0; } to { transform:translateY(0) scale(1); opacity:1; } }
                    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

                    .close-qr { position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.1); border:none; width:36px; height:36px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10; transition: background 0.3s; }
                    .close-qr:hover { background: rgba(255,255,255,0.2); }
                    
                    .qr-frame { width:240px; height:240px; padding:15px; background:#fff; border-radius:24px; margin:0 auto 25px; position:relative; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
                    .qr-frame img { width:100%; height:100%; object-fit:contain; border-radius:10px; }
                    .qr-corner { position:absolute; width:40px; height:40px; border:4px solid var(--accent-glow, #2cb5e8); }
                    .qr-corner.top-left { top:-10px; left:-10px; border-right:none; border-bottom:none; border-radius:20px 0 0 0; }
                    .qr-corner.top-right { top:-10px; right:-10px; border-left:none; border-bottom:none; border-radius:0 20px 0 0; }
                    .qr-corner.bottom-left { bottom:-10px; left:-10px; border-right:none; border-top:none; border-radius:0 0 0 20px; }
                    .qr-corner.bottom-right { bottom:-10px; right:-10px; border-left:none; border-top:none; border-radius:0 0 20px 0 ; }
                    
                    .user-badge-qr { margin-bottom:30px; margin-top:20px; text-align:center; }
                    .u-name-qr { font-size:22px; font-weight:800; color:#fff; margin-bottom:8px; letter-spacing:0.5px; }
                    .u-phone-qr { font-size:14px; color:rgba(255,255,255,0.6); font-family:monospace; background:rgba(0,0,0,0.3); padding:6px 12px; border-radius:12px; display:inline-block; }
                    
                    .share-qr-btn { width:100%; background:linear-gradient(135deg, #2cb5e8 0%, #1e88e5 100%); color:#fff; border:none; padding:16px; border-radius:18px; font-size:15px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:10px; cursor:pointer; transition:all 0.3s; margin-top:auto; box-shadow: 0 10px 20px rgba(44, 181, 232, 0.3); }
                    .share-qr-btn:hover { transform:translateY(-2px); box-shadow: 0 15px 30px rgba(44, 181, 232, 0.4); }
                </style>
            `;
            document.body.appendChild(modal);
        }

        window.shareQR = async (name, phone) => {
            const btn = document.querySelector('.share-qr-btn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Generating image...';
            btn.disabled = true;

            try {
                const qrCard = document.querySelector('.qr-card');
                // Temporarily hide the close and share buttons for the screenshot
                const closeBtn = qrCard.querySelector('.close-qr');
                const shareBtn = qrCard.querySelector('.share-qr-btn');
                closeBtn.style.visibility = 'hidden';
                shareBtn.style.visibility = 'hidden';

                const canvas = await html2canvas(qrCard, {
                    backgroundColor: '#111',
                    scale: 2,
                    useCORS: true,
                    borderRadius: 35
                });

                closeBtn.style.visibility = 'visible';
                shareBtn.style.visibility = 'visible';

                canvas.toBlob(async (blob) => {
                    if (!blob) {
                        alert("Could not generate image");
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                        return;
                    }

                    const file = new File([blob], `QOON_Pay_${phone}.png`, { type: 'image/png' });

                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                files: [file],
                                title: 'My QOON Pay Code',
                                text: `Send money to ${name} via QOON Pay.`
                            });
                        } catch (err) {
                            console.log("Share cancelled or failed", err);
                        }
                    } else {
                        // Fallback: Download
                        const link = document.createElement('a');
                        link.download = `QOON_Pay_${phone}.png`;
                        link.href = URL.createObjectURL(blob);
                        link.click();
                        alert("Sharing image not supported in this browser. Image downloaded instead.");
                    }
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });

            } catch (err) {
                console.error("Capture failed:", err);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert("Error generating shareable image");
            }
        };

        // --- AUTH REDUCTION FLOW ---
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('auth_required')) {
                // If the user was redirected here from a protected page
                setTimeout(() => {
                    if (typeof openSignup === 'function') openSignup();
                }, 500); // Small delay for smoothness
            }
        });

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
                    document.getElementById('locationOverlay').style.opacity = '0';
                    document.getElementById('locationOverlay').style.transition = 'opacity 0.5s ease';

                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                },
                (error) => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                    console.error("Location error:", error);

                    if (error.code === error.PERMISSION_DENIED) {
                        status.innerHTML = "<span style='color:#ff3b30;'><i class='fa-solid fa-triangle-exclamation'></i> You denied the
                        request.</span > <br>Please click the <b>Lock icon ðŸ”’</b> in your address bar, switch Location to <b>Allow</b>,
                            and then click below.";
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


    <!-- PRODUCT MODAL HTML & LOGIC -->
    <?php include 'includes/modals/product.php'; ?>

    <!-- SHARE MODAL HTML & LOGIC -->
    <?php include 'includes/modals/share.php'; ?>
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
                                status.innerHTML = "<span style='color:#ff3b30;'><i class='fa-solid fa-triangle-exclamation'></i> You denied the request.</span><br>Please click the <b>Lock icon ðŸ”’</b> in your address bar, switch Location to <b>Allow</b>, and then click below.";
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
                            {enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                            );
        }
                            // --- CART RESTORATION AFTER LOGIN ---
                            <?php if (isset($shopId) && !empty($shopId)): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const pendingCart = localStorage.getItem('qoon_pending_cart_<?= $shopId ?>');
                            if (pendingCart) {
                try {
                    if (typeof cartItems !== 'undefined') {
                                cartItems = JSON.parse(pendingCart);
                            if (typeof updateCartWidget === 'function') updateCartWidget();
                            localStorage.removeItem('qoon_pending_cart_<?= $shopId ?>');
                            const isLoggedIn = <?= isset($_COOKIE['qoon_user_id']) ? 'true' : 'false' ?>;
                            if (isLoggedIn && typeof openCheckoutModal === 'function') {
                                openCheckoutModal();
                        }
                    }
                } catch (e) {
                                console.error("Failed to restore cart", e);
                }
            }
        });
                            <?php endif; ?>

    </script>


</body>

</html>
<?php
if (isset($con) && $con) {
    mysqli_close($con);
}
?>