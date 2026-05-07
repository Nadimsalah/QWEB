<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'conn.php';

$DomainNamee = 'https://qoon.app/dash/';
$reels = [];

try {
    if ($con) {
        $sql = "
            SELECT * FROM (
                SELECT 
                    Posts.PostID       AS id,
                    'post'             AS sourceType,
                    Posts.Video        AS rawMedia,
                    Posts.BunnyS       AS bunnyS,
                    Posts.PostText     AS caption,
                    'VIDEO'            AS storyType,
                    Shops.ShopName     AS shopName,
                    Shops.ShopLogo     AS shopLogo,
                    Foods.FoodID       AS productId,
                    Foods.FoodName     AS productName,
                    Foods.FoodPrice    AS productPrice,
                    Foods.FoodPhoto    AS productPhoto,
                    Categories.Type    AS shopType,
                    Categories.EnglishCategory AS catDesc
                FROM Posts
                JOIN Shops ON Shops.ShopID = Posts.ShopID
                JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID
                WHERE Shops.Status = 'ACTIVE' 
                  AND Posts.PostStatus = 'ACTIVE' 
                  AND Posts.Video != '' 
                  AND Posts.Video != '0'

                UNION ALL

                SELECT 
                    ShopStory.StotyID  AS id,
                    'story'            AS sourceType,
                    ShopStory.StoryPhoto AS rawMedia,
                    ShopStory.BunnyS   AS bunnyS,
                    ''                 AS caption,
                    ShopStory.StotyType AS storyType,
                    Shops.ShopName     AS shopName,
                    Shops.ShopLogo     AS shopLogo,
                    Foods.FoodID       AS productId,
                    Foods.FoodName     AS productName,
                    Foods.FoodPrice    AS productPrice,
                    Foods.FoodPhoto    AS productPhoto,
                    Categories.Type    AS shopType,
                    Categories.EnglishCategory AS catDesc
                FROM Shops
                JOIN Categories ON Categories.CategoryId = Shops.CategoryID
                JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID
                LEFT JOIN Foods ON ShopStory.ProductId = Foods.FoodID
                WHERE Shops.Status = 'ACTIVE' 
                  AND ShopStory.StoryStatus = 'ACTIVE'
            ) AS all_media
            ORDER BY id DESC
            LIMIT 50
        ";
        $res = $con->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $mediaUrl  = '';
                $mediaType = 'image';
                $bunnyS    = trim($row['bunnyS'] ?? '');
                $rawMedia  = trim($row['rawMedia'] ?? '');
                $storyType = strtoupper(trim($row['storyType'] ?? ''));

                // ── Build URL directly from DB value (no BunnyCDN) ──
                $rawMedia  = trim($row['rawMedia'] ?? '');
                $storyType = strtoupper(trim($row['storyType'] ?? ''));
                $mediaUrl  = '';
                $mediaType = 'image';

                if (!empty($rawMedia) && $rawMedia !== '0' && $rawMedia !== '-') {
                    $mediaUrl = (strpos($rawMedia, 'http') !== false)
                        ? $rawMedia
                        : $DomainNamee . 'photo/' . $rawMedia;

                    $ext = strtolower(pathinfo($rawMedia, PATHINFO_EXTENSION));
                    if ($storyType === 'VIDEO' || in_array($ext, ['mp4','mov','webm','avi','mkv'])) {
                        $mediaType = 'video';
                    } else {
                        $mediaType = 'image';
                    }
                }

                if (!$mediaUrl) continue;

                $shopLogo = trim($row['shopLogo'] ?? '');
                if ($shopLogo && strpos($shopLogo, 'http') === false) {
                    $shopLogo = $DomainNamee . 'photo/' . $shopLogo;
                }

                $bunnyS = trim($row['bunnyS'] ?? '');
                if ($bunnyS && strpos($bunnyS, 'http') === false) {
                    $bunnyS = $DomainNamee . 'photo/' . $bunnyS;
                }
                
                $productPhoto = trim($row['productPhoto'] ?? '');
                if ($productPhoto && strpos($productPhoto, 'http') === false) {
                    $productPhoto = $DomainNamee . 'photo/' . $productPhoto;
                }

                $reels[] = [
                    'id'         => (int)$row['id'],
                    'sourceType' => $row['sourceType'],
                    'mediaUrl'   => $mediaUrl,
                    'mediaType'  => $mediaType,
                    'thumbnailUrl' => $bunnyS ? $bunnyS : ($mediaType === 'image' ? $mediaUrl : ''),
                    'caption'    => $row['caption'] ?? '',
                    'shopName'   => $row['shopName'] ?? 'Shop',
                    'shopLogo'   => $shopLogo,
                    'product'    => empty($row['productId']) ? null : [
                        'id'    => $row['productId'],
                        'name'  => $row['productName'],
                        'price' => $row['productPrice'],
                        'photo' => $productPhoto
                    ],
                    'shopType'   => $row['shopType'] ?? '',
                    'catDesc'    => $row['catDesc'] ?? ''
                ];
            }
        }
    }
} catch (Throwable $e) {}

if (isset($con) && $con) mysqli_close($con);
echo json_encode(['reels' => $reels], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
