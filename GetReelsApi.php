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
                    Shops.ShopLogo     AS shopLogo
                FROM Posts
                JOIN Shops ON Shops.ShopID = Posts.ShopID
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
                    Shops.ShopLogo     AS shopLogo
                FROM Shops
                JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID
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

                $reels[] = [
                    'id'         => (int)$row['id'],
                    'sourceType' => $row['sourceType'],
                    'mediaUrl'   => $mediaUrl,
                    'mediaType'  => $mediaType,
                    'caption'    => $row['caption'] ?? '',
                    'shopName'   => $row['shopName'] ?? 'Shop',
                    'shopLogo'   => $shopLogo,
                ];
            }
        }
    }
} catch (Throwable $e) {}

if (isset($con) && $con) mysqli_close($con);
echo json_encode(['reels' => $reels], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
