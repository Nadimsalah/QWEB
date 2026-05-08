<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];
$shopName = $_SESSION['SellerName'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- POSTS CRUD ---
    if ($_POST['action'] === 'add_post') {
        $pText = $con->real_escape_string($_POST['PostText'] ?? '');
        $prodID = $con->real_escape_string($_POST['ProductID'] ?? '');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        $baseMediaUrl = $isLocal ? ($protocol . $host . "/photo/") : ($protocol . $host . "/dash/photo/");

        $photos = ['','','',''];
        for($i=0; $i<4; $i++) {
            $key = ($i === 0) ? 'Photo' : 'Photo'.($i+1);
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $photoName = "p-".rand().".png";
                if (move_uploaded_file($_FILES[$key]["tmp_name"], __DIR__ . '/../photo/' . $photoName)) {
                    $photos[$i] = $baseMediaUrl . $photoName;
                }
            }
        }
        for($i=0; $i<4; $i++) {
            if (empty($photos[$i])) $photos[$i] = '0';
        }
        $con->query("INSERT INTO Posts (ShopID, ProductID, PostText, PostPhoto, PostPhoto2, PostPhoto3, PostPhoto4, PostStatus, Video, VideoThumbnail, BunnyV, BunnyS) 
                    VALUES ('$sellerID', '$prodID', '$pText', '{$photos[0]}', '{$photos[1]}', '{$photos[2]}', '{$photos[3]}', 'PENDING', '0', '0', '0', '0')");
        @file_get_contents("http://127.0.0.1/dashx/dash/tick_ai_worker.php");
        header("Location: content.php?msg=post_added&tab=posts");
        exit;
    }
    if ($_POST['action'] === 'delete_post') {
        $pID = (int)$_POST['PostId'];
        $con->query("DELETE FROM Posts WHERE PostId='$pID' AND ShopID='$sellerID'");
        header("Location: content.php?msg=deleted&tab=posts");
        exit;
    }
    
    // --- STORIES CRUD ---
    if ($_POST['action'] === 'add_story') {
        $stName = $con->real_escape_string($_POST['AdName'] ?? '');
        $prodID = $con->real_escape_string($_POST['ProductID'] ?? '');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        $baseMediaUrl = $isLocal ? ($protocol . $host . "/photo/") : ($protocol . $host . "/dash/photo/");

        $actualpath = '';
        $type = 'Photos'; // Default
        if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['Photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
                $type = 'video';
                $photoName = "v-".rand().".mp4";
            } else {
                $photoName = "s-".rand()."." . ($ext ?: 'png');
            }
            if (move_uploaded_file($_FILES["Photo"]["tmp_name"], __DIR__ . '/../photo/' . $photoName)) {
                $actualpath = $baseMediaUrl . $photoName;
            }
        }
        $con->query("INSERT INTO ShopStory (ShopID, ProductId, AdName, StoryPhoto, StoryStatus, StotyType) VALUES ('$sellerID', '$prodID', '$stName', '$actualpath', 'PENDING', '$type')");
        header("Location: content.php?msg=story_added&tab=stories");
        exit;
    }
    if ($_POST['action'] === 'delete_story') {
        $sID = (int)$_POST['StoryID'];
        $con->query("DELETE FROM ShopStory WHERE StotyID='$sID' AND ShopID='$sellerID'");
        header("Location: content.php?msg=deleted&tab=stories");
        exit;
    }
    
    // --- REELS CRUD ---
    if ($_POST['action'] === 'add_reel') {
        $rText = $con->real_escape_string($_POST['PostText'] ?? '');
        $prodID = $con->real_escape_string($_POST['ProductID'] ?? '');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        $baseMediaUrl = $isLocal ? ($protocol . $host . "/photo/") : ($protocol . $host . "/dash/photo/");

        $vidPath = '';
        if (isset($_FILES['Video']) && $_FILES['Video']['error'] === UPLOAD_ERR_OK) {
            $vidName = "v-".rand().".mp4";
            if (move_uploaded_file($_FILES["Video"]["tmp_name"], __DIR__ . '/../photo/' . $vidName)) {
                $vidPath = $baseMediaUrl . $vidName;
            }
        }
        $defaultThumb = "https://ui-avatars.com/api/?name=Video&background=000000&color=FFFFFF&size=400";
        // Need to pass strict defaults for Bunny/Thumbnail/Photo to satisfy mobile app strict checks
        $con->query("INSERT INTO Posts (ShopID, ProductID, PostText, Video, PostStatus, PostPhoto, PostPhoto2, PostPhoto3, PostPhoto4, VideoThumbnail, BunnyV, BunnyS) 
                     VALUES ('$sellerID', '$prodID', '$rText', '$vidPath', 'PENDING', '$defaultThumb', '0', '0', '0', '$defaultThumb', '0', '0')");

        @file_get_contents("http://127.0.0.1/dashx/dash/tick_ai_worker.php");
        header("Location: content.php?msg=reel_added&tab=reels");
        exit;
    }
    
    // --- REPLY COMMENT ---
    if ($_POST['action'] === 'reply_comment') {
        $cPostID = (int)$_POST['PostId'];
        $cText = $con->real_escape_string($_POST['CommentText'] ?? '');
        // Explicitly set ShopID identifier for "Us", UserID='0'
        $con->query("INSERT INTO Comments (UserID, ShopID, CommentText, PostID) VALUES ('0', '$sellerID', '$cText', '$cPostID')");
        $con->query("UPDATE Posts SET Postcomments = Postcomments + 1 WHERE PostId='$cPostID'");
        // Assuming user replies from either Posts or Reels tab
        $tab = $_POST['return_tab'] ?? 'posts';
        header("Location: content.php?msg=replied&tab={$tab}");
        exit;
    }
}

// Fetch all Posts (No video)
$postsRes = $con->query("SELECT * FROM Posts WHERE ShopID='$sellerID' AND (Video IS NULL OR Video = '' OR Video = 'NONE' OR Video = '0') ORDER BY PostId DESC");
$posts = [];
if($postsRes) { while($r = $postsRes->fetch_assoc()) { $posts[] = $r; } }

// Fetch Reels (Has video)
$reelsRes = $con->query("SELECT * FROM Posts WHERE ShopID='$sellerID' AND Video IS NOT NULL AND Video != '' AND Video != 'NONE' AND Video != '0' ORDER BY PostId DESC");
$reels = [];
if($reelsRes) { while($r = $reelsRes->fetch_assoc()) { $reels[] = $r; } }

// Fetch all Stories
$storiesRes = $con->query("SELECT * FROM ShopStory WHERE ShopID='$sellerID' ORDER BY StotyID DESC");
$stories = [];
if($storiesRes) { while($r = $storiesRes->fetch_assoc()) { $stories[] = $r; } }

// Fetch Catalog for tagging (Foods table)
$catalogRes = $con->query("
    SELECT F.FoodID, F.FoodName, F.FoodPrice, F.FoodPhoto 
    FROM Foods F
    JOIN ShopsCategory SC ON F.FoodCatID = SC.CategoryShopID
    WHERE SC.ShopID='$sellerID'
    ORDER BY F.FoodName ASC
");
$catalog = [];
if($catalogRes) { while($r = $catalogRes->fetch_assoc()) { $catalog[] = $r; } }

// Build a faster product lookup map for frontend display
$productMap = [];
foreach($catalog as $p) { $productMap[$p['FoodID']] = $p; }
$productMapJson = json_encode($productMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Fetch Comments for all posts of this Shop
$commentsSql = "
    SELECT 
        C.CommentID, C.PostID, C.CommentText, C.CreatedAtComments, C.UserID, C.ShopID,
        U.name AS UserName, U.UserPhoto AS UserPhoto
    FROM Comments C
    LEFT JOIN Users U ON C.UserID = U.UserID
    WHERE C.PostID IN (SELECT PostId FROM Posts WHERE ShopID='$sellerID')
    ORDER BY C.CreatedAtComments ASC
";
$comRes = $con->query($commentsSql);
$commentsSet = [];
if ($comRes) {
    while($cr = $comRes->fetch_assoc()) {
        $pid = $cr['PostID'];
        if (!isset($commentsSet[$pid])) $commentsSet[$pid] = [];
        
        // Normalize User Photo URL
        $uPhoto = trim((string)($cr['UserPhoto'] ?? ''));
        if ($uPhoto !== '' && $uPhoto !== 'None' && !str_starts_with($uPhoto, 'http')) {
            $uPhoto = "https://qoon.app/" . ltrim($uPhoto, '/');
        }
        // Fix double slashes except for http:// or https://
        $uPhoto = preg_replace('/([^:])(\/{2,})/', '$1/', $uPhoto);
        $cr['UserPhoto'] = $uPhoto;
        
        $commentsSet[$pid][] = $cr;
    }
}
$commentsJson = json_encode($commentsSet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Shop Icon fallback for replies
$defaultShopIcon = !empty($SHOP_DATA['ShopLogo']) ? htmlspecialchars($SHOP_DATA['ShopLogo']) : "https://ui-avatars.com/api/?name=".urlencode($_SESSION['SellerName'])."&background=EBE8FA&color=6B4EE6&bold=true";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Manager | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-master: #F3F5FA; --bg-surface: #FFFFFF;
            --text-strong: #111827; --text-base: #374151; --text-muted: #9CA3AF;
            --brand-purple: #6B4EE6; --brand-purple-light: #EBE8FA;
            --brand-purple-grad: linear-gradient(135deg, #7C5CFF 0%, #5235E8 100%);
            --accent-pink: #F9E7F6; --accent-pink-text: #D63384;
            --accent-blue: #E8F4F8; --accent-blue-text: #0DCAF0;
            --danger-text: #EE5D50; --danger-bg: rgba(238, 93, 80, 0.1);
            --radius-lg: 24px; --radius-md: 16px; --radius-sm: 12px;
            --shadow-soft: 0 4px 6px -1px rgba(0,0,0,0.02);
            --shadow-float: 0 10px 25px rgba(107, 78, 230, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-master); color: var(--text-base); display: flex; height: 100vh; overflow: hidden; font-family: 'Poppins', sans-serif;}
        .app-envelope { width: 100%; height: 100%; display: flex; background: var(--bg-surface); overflow: hidden; }

        /* Sidebar CSS Centralized in sidebar.php */

        /* ====== MAIN COMPONENT ====== */
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; background: var(--bg-master); }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; display: flex; flex-direction: column; gap: 30px; margin: 0 auto; }
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; }
        .search-bar { background: var(--bg-surface); border-radius: 30px; padding: 12px 24px; display: flex; align-items: center; gap: 12px; width: 400px; box-shadow: var(--shadow-soft);}
        .search-bar input { border: none; outline: none; background: transparent; width: 100%; font-family: 'Inter', sans-serif; font-size: 13px; color: var(--text-strong); }
        .user-nav { display: flex; align-items: center; gap: 20px; }
        .profile-btn { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .profile-badge { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-surface); background: var(--brand-purple-light); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--brand-purple); }
        .profile-name { font-size: 14px; font-weight: 600; color: var(--text-strong); }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .s-title { font-size: 24px; font-weight: 700; color: var(--text-strong); margin-bottom: 4px; }
        .s-subtitle { font-size: 13px; color: var(--text-muted); font-family: 'Inter', sans-serif; }
        
        .btn-primary { background: var(--brand-purple-grad); color: #FFF; border: none; padding: 12px 24px; border-radius: 30px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 15px rgba(107, 78, 230, 0.3); font-family: 'Poppins', sans-serif;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: var(--shadow-float); }

        /* ====== TABS ====== */
        .tabs-header { display: flex; gap: 10px; margin-bottom: 30px; background: var(--bg-surface); padding: 8px; border-radius: 20px; box-shadow: var(--shadow-soft); display: inline-flex; }
        .tab-btn { padding: 10px 24px; border-radius: 14px; border: none; background: transparent; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.2s; font-family: 'Poppins', sans-serif;}
        .tab-btn.active { background: var(--brand-purple-light); color: var(--brand-purple); }
        .tab-btn:hover:not(.active) { color: var(--text-strong); }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ====== CONTENT GRID ====== */
        .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; padding-bottom: 40px; }
        .cnt-card { background: var(--bg-surface); border-radius: var(--radius-md); padding: 16px; display: flex; flex-direction: column; gap: 12px; transition: 0.2s; box-shadow: var(--shadow-soft); border: 1px solid transparent; overflow: hidden; }
        .cnt-card:hover { border-color: rgba(107, 78, 230, 0.2); box-shadow: var(--shadow-float); transform: translateY(-2px); }

        /* ====== FACEBOOK STYLE FEED (POSTS) ====== */
        .fb-feed { max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; padding-bottom: 40px; font-family: 'Inter', sans-serif;}
        .fb-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #E5E7EB;}
        .fb-header { padding: 12px 16px; display: flex; align-items: center; gap: 10px; }
        .fb-name-box { flex: 1; }
        .fb-name { font-size: 14px; font-weight: 600; color: #050505; }
        .fb-time { font-size: 12px; color: #65676B; display: flex; align-items: center; gap: 4px; margin-top:2px; }
        .fb-text { padding: 4px 16px 12px; font-size: 15px; color: #050505; line-height: 1.4; white-space: pre-wrap; }
        .fb-img-wrapper { background: #F0F2F5; text-align: center; }
        .fb-img { width: 100%; max-height: 600px; object-fit: contain; display: block; border-top: 1px solid #E5E7EB; border-bottom: 1px solid #E5E7EB;}
        .fb-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
        .fb-stats { display: flex; gap: 12px; color: #65676B; font-size: 13px; font-weight: 500; }
        .fb-stats i { margin-right: 4px; }
        
        .cnt-media { width: 100%; height: 160px; border-radius: var(--radius-sm); object-fit: cover; background: #F3F4F6; position: relative; }
        .cnt-media.vertical { aspect-ratio: 9/16; height: auto; min-height: 380px; }
        .cnt-overlay { position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.4); border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center; color:#FFF; font-size:24px; opacity: 0.8; }
        .cnt-desc { font-size: 13px; color: var(--text-strong); min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-family: 'Inter', sans-serif;}
        
        /* ====== FB IMAGE GRID ====== */
        .fb-img-grid { display: grid; gap: 2px; background: #E5E7EB; border-top: 1px solid #E5E7EB; border-bottom: 1px solid #E5E7EB; }
        .fb-img-grid.count-1 { grid-template-columns: 1fr; }
        .fb-img-grid.count-2 { grid-template-columns: 1fr 1fr; }
        .fb-img-grid.count-3 { grid-template-columns: 1fr 1fr; grid-template-rows: 200px 200px; }
        .fb-img-grid.count-3 img:first-child { grid-row: span 2; }
        .fb-img-grid.count-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 200px 200px; }
        .fb-img-grid img { width: 100%; height: 100%; object-fit: cover; display: block; background: #F0F2F5; cursor: pointer; }
        
        .cnt-meta { display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E5E7EB; padding-top: 12px; margin-top: auto; }
        
        .act-btn { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; border:none; cursor: pointer; transition: 0.2s; }
        .act-del { background: var(--danger-bg); color: var(--danger-text); }
        .act-del:hover { background: var(--danger-text); color: #FFF; }
        .fb-btn { background: none; border:none; cursor: pointer; color: #65676B; font-size: 13px; font-weight: 600; display:flex; align-items:center; gap:6px; transition:0.2s; font-family: 'Inter', sans-serif;}
        .fb-btn:hover { color: var(--brand-purple); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); font-size: 14px; font-family: 'Inter', sans-serif;}

        /* ====== MODAL STYLES ====== */
        dialog { margin: auto; border: none; border-radius: var(--radius-lg); padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); background: transparent; width: 100%; max-width: 500px; }
        dialog::backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-box { background: var(--bg-surface); padding: 30px; border-radius: var(--radius-lg); display: flex; flex-direction: column; gap: 20px; max-height: 85vh; overflow-y: auto;}
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F3F4F6; padding-bottom: 16px; }
        .modal-header h3 { font-size: 20px; font-weight: 700; color: var(--text-strong); }
        .close-btn { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; }
        .close-btn:hover { color: var(--text-strong); }
        
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-base); font-family: 'Inter', sans-serif; }
        .form-control { background: #FAFAFB; border: 1px solid transparent; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 14px; color: var(--text-strong); font-family: 'Inter', sans-serif; outline: none; transition: 0.2s; width: 100%; }
        .form-control:focus { border-color: var(--brand-purple); background: var(--bg-surface); box-shadow: 0 0 0 4px var(--brand-purple-light); }
        .btn-submit { width: 100%; background: var(--brand-purple-grad); color: #FFF; border: none; padding: 14px; border-radius: var(--radius-md); font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; font-family: 'Poppins', sans-serif; transition: 0.2s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: var(--shadow-float); }

        /* ====== CONVERSATION MODAL ====== */
        .chat-flow { display: flex; flex-direction: column; gap: 16px; height: 350px; overflow-y: auto; padding-right: 10px; }
        .chat-row { display: flex; gap: 12px; }
        .chat-row.reply { flex-direction: row-reverse; }
        .chat-avt { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; background: #E5E7EB; flex-shrink: 0; }
        .chat-bubble { max-width: 80%; background: #F3F4F6; padding: 10px 14px; border-radius: 16px; border-top-left-radius: 4px; font-size: 13px; color: #111827; font-family: 'Inter', sans-serif;}
        .reply .chat-bubble { background: var(--brand-purple-grad); color: #FFF; border-radius: 16px; border-top-right-radius: 4px; }
        .chat-name { font-size: 11px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; }
        .reply .chat-name { text-align: right; color: var(--brand-purple); }
        
        .chat-input-bar { display: flex; gap: 10px; border-top: 1px solid #E5E7EB; padding-top: 16px; margin-top: 10px;}
        .chat-input-bar .form-control { border: 1px solid #D1D5DB; border-radius: 20px; }
        .chat-send-btn { background: var(--brand-purple-grad); color: #FFF; border: none; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; box-shadow: 0 4px 10px rgba(107, 78, 230, 0.3);}
        .chat-send-btn:hover { transform: translateY(-2px); }

        /* ====== PRODUCT TAG STYLES ====== */
        .tagged-prod-box { display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: #F8F9FA; border-top: 1px solid #F3F4F6; cursor: pointer; transition: 0.2s; }
        .tagged-prod-box:hover { background: #F1F3F5; }
        .tag-img { width: 34px; height: 34px; border-radius: 6px; object-fit: cover; background: #FFF; }
        .tag-info { flex: 1; }
        .tag-name { font-size: 13px; font-weight: 600; color: var(--text-strong); }
        .tag-price { font-size: 11px; color: var(--brand-purple); font-weight: 700; margin-top: 1px; }
        .shop-now-text { font-size: 11px; font-weight: 700; color: var(--brand-purple); text-transform: uppercase; letter-spacing: 0.5px; }

        .fb-tag-footer { padding: 10px 16px; border-top: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; background: #FAFAFB; }
        .fb-tag-info { display: flex; align-items: center; gap: 8px; }
        .fb-tag-img { width: 28px; height: 28px; border-radius: 4px; object-fit: cover; }
        .fb-tag-name { font-size: 12px; font-weight: 600; color: var(--text-base); }

        /* ====== MODERN UPLOAD FRAMES ====== */
        .media-upload-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .upload-frame { 
            aspect-ratio: 1; border: 2px dashed #E5E7EB; border-radius: var(--radius-md); 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            cursor: pointer; position: relative; overflow: hidden; transition: 0.2s; background: #FAFBFC;
        }
        .upload-frame:hover { border-color: var(--brand-purple); background: var(--brand-purple-light); }
        .upload-frame i { font-size: 20px; color: var(--text-muted); margin-bottom: 8px; }
        .upload-frame span { font-size: 11px; font-weight: 600; color: var(--text-muted); }
        .upload-frame img { position: absolute; top:0; left:0; width:100%; height:100%; object-fit: cover; z-index: 5; }
        .upload-frame input[type="file"] { position: absolute; top:0; left:0; width:100%; height:100%; opacity: 0; cursor: pointer; z-index:10; }
        
        /* ====== PRODUCT PICKER GALLERY ====== */
        .product-gallery { display: flex; gap: 12px; overflow-x: auto; padding: 10px 4px; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none; }
        .product-gallery::-webkit-scrollbar { display: none; }
        .pg-item { 
            flex: 0 0 120px; background: #FFF; border: 1px solid #E5E7EB; border-radius: var(--radius-sm); 
            padding: 8px; cursor: pointer; transition: 0.2s; position: relative; scroll-snap-align: start;
        }
        .pg-item:hover { border-color: var(--brand-purple); transform: translateY(-2px); }
        .pg-item.selected { border: 2px solid var(--brand-purple); background: var(--brand-purple-light); }
        .pg-img { width: 100%; aspect-ratio: 1; border-radius: 6px; object-fit: cover; margin-bottom: 8px; }
        .pg-name { font-size: 11px; font-weight: 700; color: var(--text-strong); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.2; height: 26px;}
        .pg-price { font-size: 10px; font-weight: 800; color: var(--brand-purple); margin-top: 4px; }
        .pg-check { 
            position: absolute; top: 4px; right: 4px; width: 18px; height: 18px; background: var(--brand-purple-grad); 
            color: #FFF; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 9px; z-index: 10;
        }
        .pg-item.selected .pg-check { display: flex; }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; }
            .top-navbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            .search-bar { width: 100%; }
            .user-nav { width: 100%; justify-content: space-between; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .tabs-header { width: 100%; justify-content: space-between; overflow-x: auto; padding-bottom: 5px; -webkit-overflow-scrolling: touch; }
            .tab-btn { flex: 1; text-align: center; white-space: nowrap; padding: 10px 12px; font-size: 13px; }
            .content-grid { grid-template-columns: 1fr; gap: 16px; }
            .cnt-card { border-radius: 16px; }
            .fb-card { border-radius: 16px; margin-bottom: 16px;}
            dialog { max-width: calc(100% - 32px); margin: 16px auto; }
            .modal-box { width: 100% !important; padding: 20px; }
        }

        /* Shimmer Loading Skeleton */
        @keyframes shimmerEffect {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }
        .shimmer-bg {
            background: #F3F4F6;
            background-image: linear-gradient(to right, #F3F4F6 0%, #E5E7EB 20%, #F3F4F6 40%, #F3F4F6 100%);
            background-repeat: no-repeat;
            background-size: 800px 100%; 
            animation: shimmerEffect 1.5s infinite linear;
        }

        .fade-in-up { animation: fadeInUp 0.4s ease forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <div class="content-wrapper">
                
                <header class="top-navbar">
                    <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search content..."></div>
                    <div class="user-nav">
                        <div class="profile-btn">
                            <img src="<?= $defaultShopIcon ?>" class="profile-badge">
                            <div class="profile-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                        </div>
                    </div>
                </header>

                <div>
                    <div class="section-header">
                        <div>
                            <div class="s-title">Content Manager</div>
                            <div class="s-subtitle">Post updates to the timeline, share store stories, or upload vertical reels.</div>
                        </div>
                        <button class="btn-primary" id="mainAddBtn" onclick="window.postModal.showModal()">
                            <i class="fas fa-plus"></i> Create Post
                        </button>
                    </div>

                    <div class="tabs-header">
                        <button class="tab-btn active" onclick="openTab('posts', this, 'Create Post', window.postModal)">Posts</button>
                        <button class="tab-btn" onclick="openTab('stories', this, 'Upload Story', window.storyModal)">Stories</button>
                        <button class="tab-btn" onclick="openTab('reels', this, 'Upload Reel', window.reelModal)">Reels</button>
                    </div>

                    <!-- TABS AREA -->
                    <div id="posts" class="tab-content active">
                        <!-- Skeleton Posts -->
                        <div id="skeleton-posts" class="fb-feed">
                            <?php for($s=0; $s<3; $s++): ?>
                            <div class="fb-card" style="border:none;">
                                <div class="fb-header">
                                    <div class="shimmer-bg" style="width:40px; height:40px; border-radius:50%;"></div>
                                    <div class="fb-name-box">
                                        <div class="shimmer-bg" style="height:14px; width:40%; border-radius:4px; margin-bottom:4px;"></div>
                                        <div class="shimmer-bg" style="height:10px; width:20%; border-radius:4px;"></div>
                                    </div>
                                </div>
                                <div class="shimmer-bg" style="height:14px; width:80%; border-radius:4px; margin: 0 16px 8px;"></div>
                                <div class="shimmer-bg" style="height:14px; width:60%; border-radius:4px; margin: 0 16px 12px;"></div>
                                <div class="shimmer-bg" style="height:300px; width:100%;"></div>
                                <div class="fb-footer" style="padding:16px;">
                                    <div class="shimmer-bg" style="height:14px; width:30%; border-radius:4px;"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Real Posts -->
                        <div id="real-posts" style="display:none;">
                            <?php if (count($posts) > 0): ?>
                                <div class="fb-feed">
                                <?php 
                                    $delayP = 0;
                                    foreach($posts as $p): 
                                    $pht = !empty(trim((string)$p['PostPhoto'])) ? htmlspecialchars($p['PostPhoto']) : "";
                                    $dText = $p['CreatedAtPosts'] ? date('M d \a\t h:i A', strtotime($p['CreatedAtPosts'])) : 'Just now';
                                    $pStat = strtoupper((string)($p['PostStatus'] ?? 'ACTIVE'));
                                    $isRejected = ($pStat === 'REJECTED');
                                    $isPending = ($pStat === 'PENDING');
                                ?>
                                    <div class="fb-card fade-in-up" style="animation-delay: <?= $delayP ?>s; <?= $isRejected ? 'border: 2px solid var(--danger-text);' : ($isPending ? 'border: 2px solid #F59E0B;' : '') ?>">
                                        <?php if($isRejected): ?>
                                        <div style="background: var(--danger-text); color: #FFF; padding: 6px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-top-left-radius: 16px; border-top-right-radius: 16px;"><i class="fas fa-exclamation-triangle"></i> Rejected by Team QOON</div>
                                        <?php elseif($isPending): ?>
                                        <div style="background: #F59E0B; color: #FFF; padding: 6px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-top-left-radius: 16px; border-top-right-radius: 16px;"><i class="fas fa-clock"></i> Pending AI/Admin Approval</div>
                                        <?php else: ?>
                                        <div style="background: #10B981; color: #FFF; padding: 3px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; display:inline-block; border-bottom-right-radius: 8px; border-top-left-radius: 16px; margin-bottom: 5px;"><i class="fas fa-check-circle"></i> Active</div>
                                        <?php endif; ?>
                                        <div class="fb-header">
                                            <img src="<?= $defaultShopIcon ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #EBE8FA;">
                                            <div class="fb-name-box">
                                                <div class="fb-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                                                <div class="fb-time"><i class="fas fa-globe-americas" style="font-size:10px;"></i> <?= $dText ?></div>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Delete this post?');" style="margin:0;">
                                                <input type="hidden" name="action" value="delete_post">
                                                <input type="hidden" name="PostId" value="<?= $p['PostId'] ?>">
                                                <button type="submit" class="act-btn act-del" style="background:transparent; color:#65676B; font-size:16px;" title="Delete post"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                        <?php if(!empty($p['PostText'])): ?>
                                            <div class="fb-text"><?= htmlspecialchars($p['PostText']) ?></div>
                                        <?php endif; ?>
                                        
                                        <?php 
                                            // Normalize function identical to dash logic for robust display
                                            if(!function_exists('normalizeMediaUrl')){
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
                                            }

                                            $allPhotos = array_filter([normalizeMediaUrl($p['PostPhoto']), normalizeMediaUrl($p['PostPhoto2']), normalizeMediaUrl($p['PostPhoto3']), normalizeMediaUrl($p['PostPhoto4'])]);
                                            if(!empty($allPhotos)): 
                                                $count = count($allPhotos);
                                        ?>
                                            <div class="fb-img-grid count-<?= $count ?>">
                                                <?php foreach($allPhotos as $img): ?>
                                                    <img src="<?= htmlspecialchars($img) ?>" onclick="window.open('<?= htmlspecialchars($img) ?>', '_blank')">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="fb-footer">
                                            <div class="fb-stats">
                                                <span><i class="fas fa-thumbs-up" style="color:var(--brand-purple);"></i> <?= $p['PostLikes'] ?? 0 ?> Likes</span>
                                                <button type="button" class="fb-btn" onclick="openComments(<?= $p['PostId'] ?>, 'posts')"><i class="fas fa-comment-alt"></i> <?= $p['Postcomments'] ?? 0 ?> Comments</button>
                                            </div>
                                        </div>
                                        <?php if(!empty($p['ProductID'])): 
                                            $tp = $productMap[$p['ProductID']] ?? null;
                                            if($tp):
                                                $tpImg = (!empty($tp['FoodPhoto'])) ? $tp['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($tp['FoodName'])."&background=EBE8FA&color=6B4EE6&bold=true";
                                        ?>
                                            <div class="fb-tag-footer">
                                                <div class="fb-tag-info">
                                                    <img src="<?= $tpImg ?>" class="fb-tag-img">
                                                    <div class="fb-tag-name"><?= htmlspecialchars($tp['FoodName']) ?></div>
                                                </div>
                                                <div class="shop-now-text">Shop Now <i class="fas fa-chevron-right" style="font-size:9px;"></i></div>
                                            </div>
                                        <?php endif; endif; ?>
                                    </div>
                                <?php 
                                    $delayP += 0.05;
                                    endforeach; 
                                ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state fade-in-up">
                                    <i class="fas fa-images" style="font-size: 40px; color: #E5E7EB; margin-bottom: 16px;"></i>
                                    <p>You haven't created any timeline posts yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="stories" class="tab-content">
                        <!-- Skeleton Stories -->
                        <div id="skeleton-stories" class="content-grid">
                            <?php for($s=0; $s<4; $s++): ?>
                            <div class="cnt-card" style="border:none;">
                                <div class="shimmer-bg cnt-media vertical" style="min-height:280px; border-radius:12px;"></div>
                                <div class="shimmer-bg" style="height:14px; width:70%; border-radius:4px; margin-top:8px;"></div>
                                <div class="cnt-meta" style="border:none;">
                                    <div class="shimmer-bg" style="height:12px; width:40%; border-radius:4px;"></div>
                                    <div class="shimmer-bg" style="width:32px; height:32px; border-radius:50%;"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Real Stories -->
                        <div id="real-stories" style="display:none;">
                            <?php if (count($stories) > 0): ?>
                                <div class="content-grid">
                                <?php 
                                    $delayS = 0;
                                    foreach($stories as $s): 
                                    $pht = !empty(trim((string)$s['StoryPhoto'])) ? htmlspecialchars($s['StoryPhoto']) : "https://ui-avatars.com/api/?name=Story&background=F3F4F6&color=6B4EE6&size=400";
                                    $sStat = strtoupper((string)($s['StoryStatus'] ?? 'ACTIVE'));
                                    $isRejected = ($sStat === 'REJECTED');
                                    $isPending = ($sStat === 'PENDING');
                                ?>
                                    <div class="cnt-card fade-in-up" style="animation-delay: <?= $delayS ?>s; <?= $isRejected ? 'border: 2px solid var(--danger-text);' : ($isPending ? 'border: 2px solid #F59E0B;' : '') ?>">
                                        <div style="position:relative; cursor:pointer;" onclick="window.open('<?= $pht ?>', '_blank')">
                                            <img src="<?= $pht ?>" class="cnt-media vertical" style="<?= $isRejected||$isPending ? 'filter: grayscale(100%) opacity(50%);' : '' ?>">
                                            <div style="position: absolute; top:12px; left:12px; background:var(--brand-purple); color:#FFF; font-size:10px; padding:4px 10px; border-radius:20px; font-weight:700; letter-spacing:0.5px;">STORY</div>
                                            <?php if($isRejected): ?>
                                                <div style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); background: var(--danger-text); color: #FFF; padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; width: 80%;"><i class="fas fa-ban"></i><br>Rejected</div>
                                            <?php elseif($isPending): ?>
                                                <div style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); background: #F59E0B; color: #FFF; padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; width: 80%;"><i class="fas fa-clock"></i><br>Pending</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cnt-desc" style="font-weight:700; color:var(--brand-purple);"><?= htmlspecialchars($s['AdName'] ?? 'Store Story') ?></div>
                                        
                                        <?php if(!empty($s['ProductId'])): 
                                            $tp = $productMap[$s['ProductId']] ?? null;
                                            if($tp):
                                                $tpImg = (!empty($tp['FoodPhoto'])) ? $tp['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($tp['FoodName'])."&background=EBE8FA&color=6B4EE6&bold=true";
                                        ?>
                                            <div class="tagged-prod-box">
                                                <img src="<?= $tpImg ?>" class="tag-img">
                                                <div class="tag-info">
                                                    <div class="tag-name"><?= htmlspecialchars($tp['FoodName']) ?></div>
                                                    <div class="tag-price"><?= number_format($tp['FoodPrice'], 2) ?> USD</div>
                                                </div>
                                                <div class="shop-now-text">Shop</div>
                                            </div>
                                        <?php endif; endif; ?>

                                        <div class="cnt-meta">
                                            <div class="cnt-stats" style="font-size:10px; font-weight:700;">
                                                <span style="color: <?= $isRejected ? 'var(--danger-text)' : ($isPending ? '#F59E0B' : '#10B981') ?>;">
                                                    <i class="fas fa-circle"></i> <?= $sStat ?>
                                                </span>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Delete this story?');" style="margin:0;">
                                                <input type="hidden" name="action" value="delete_story">
                                                <input type="hidden" name="StoryID" value="<?= $s['StotyID'] ?>">
                                                <button type="submit" class="act-btn act-del" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php 
                                    $delayS += 0.05;
                                    endforeach; 
                                ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state fade-in-up">
                                    <i class="fas fa-camera-retro" style="font-size: 40px; color: #E5E7EB; margin-bottom: 16px;"></i>
                                    <p>No active stories currently loaded for your store.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="reels" class="tab-content">
                        <!-- Skeleton Reels -->
                        <div id="skeleton-reels" class="content-grid">
                            <?php for($s=0; $s<4; $s++): ?>
                            <div class="cnt-card" style="border:none;">
                                <div class="shimmer-bg cnt-media vertical" style="min-height:280px; border-radius:12px;"></div>
                                <div class="shimmer-bg" style="height:14px; width:70%; border-radius:4px; margin-top:8px;"></div>
                                <div class="cnt-meta" style="border:none;">
                                    <div class="shimmer-bg" style="height:12px; width:40%; border-radius:4px;"></div>
                                    <div class="shimmer-bg" style="width:32px; height:32px; border-radius:50%;"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Real Reels -->
                        <div id="real-reels" style="display:none;">
                            <?php if (count($reels) > 0): ?>
                                <div class="content-grid">
                                <?php 
                                    $delayR = 0;
                                    foreach($reels as $r): 
                                    $pht = !empty(trim((string)$r['PostPhoto'])) ? htmlspecialchars($r['PostPhoto']) : "https://ui-avatars.com/api/?name=Reel&background=111827&color=FFFFFF&size=400";
                                    $rStat = strtoupper((string)($r['PostStatus'] ?? 'ACTIVE'));
                                    $isRejected = ($rStat === 'REJECTED');
                                    $isPending = ($rStat === 'PENDING');
                                ?>
                                    <div class="cnt-card fade-in-up" style="animation-delay: <?= $delayR ?>s; <?= $isRejected ? 'border: 2px solid var(--danger-text);' : ($isPending ? 'border: 2px solid #F59E0B;' : '') ?>">
                                        <div style="position:relative; cursor:pointer;" onclick="window.open('<?= htmlspecialchars($r['Video'] ?? $pht) ?>', '_blank')">
                                            <img src="<?= $pht ?>" class="cnt-media vertical" style="<?= $isRejected||$isPending ? 'filter: grayscale(100%) opacity(50%);' : '' ?>">
                                            <div class="cnt-overlay"><i class="fas fa-play"></i></div>
                                            <div style="position: absolute; top:12px; left:12px; background:#000; color:#FFF; font-size:10px; padding:4px 10px; border-radius:20px; font-weight:700; letter-spacing:0.5px; display:flex; align-items:center; gap:5px;"><i class="fas fa-video"></i> REEL</div>
                                            <?php if($isRejected): ?>
                                                <div style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); background: var(--danger-text); color: #FFF; padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; width: 80%; z-index: 10;"><i class="fas fa-ban"></i><br>Rejected</div>
                                            <?php elseif($isPending): ?>
                                                <div style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); background: #F59E0B; color: #FFF; padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; width: 80%; z-index: 10;"><i class="fas fa-clock"></i><br>Pending AI</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cnt-desc"><?= htmlspecialchars($r['PostText'] ?? 'Reel Video') ?></div>
                                        
                                        <?php if(!empty($r['ProductID'])): 
                                            $tp = $productMap[$r['ProductID']] ?? null;
                                            if($tp):
                                                $tpImg = (!empty($tp['FoodPhoto'])) ? $tp['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($tp['FoodName'])."&background=EBE8FA&color=6B4EE6&bold=true";
                                        ?>
                                            <div class="tagged-prod-box">
                                                <img src="<?= $tpImg ?>" class="tag-img">
                                                <div class="tag-info">
                                                    <div class="tag-name"><?= htmlspecialchars($tp['FoodName']) ?></div>
                                                    <div class="tag-price"><?= number_format($tp['FoodPrice'], 2) ?> USD</div>
                                                </div>
                                                <div class="shop-now-text">Buy</div>
                                            </div>
                                        <?php endif; endif; ?>

                                        <div class="cnt-meta">
                                            <div class="cnt-stats">
                                                <span>
                                                    <i class="fas fa-circle" style="color: <?= $isRejected ? 'var(--danger-text)' : ($isPending ? '#F59E0B' : '#10B981') ?>; font-size:9px;"></i> 
                                                    <span style="font-weight:700; font-size:10px; color: <?= $isRejected ? 'var(--danger-text)' : ($isPending ? '#F59E0B' : '#10B981') ?>;"><?= $rStat ?></span>
                                                </span>
                                                <button type="button" class="fb-btn" style="color:var(--text-muted); font-weight:600;" onclick="openComments(<?= $r['PostId'] ?>, 'reels')"><i class="fas fa-comment"></i> <?= $r['Postcomments'] ?? 0 ?></button>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Delete this Reel video?');" style="margin:0;">
                                                <input type="hidden" name="action" value="delete_post">
                                                <input type="hidden" name="PostId" value="<?= $r['PostId'] ?>">
                                                <button type="submit" class="act-btn act-del" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php 
                                    $delayR += 0.05;
                                    endforeach; 
                                ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state fade-in-up">
                                    <i class="fas fa-video" style="font-size: 40px; color: #E5E7EB; margin-bottom: 16px;"></i>
                                    <p>You don't have any uploaded vertical video reels.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- CREATE POST MODAL -->
    <dialog id="postModal"><div class="modal-box" style="width: 550px;"><div class="modal-header"><h3>Create Timeline Post</h3><button class="close-btn" onclick="window.postModal.close()"><i class="fas fa-times"></i></button></div>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add_post">
            <div class="form-group"><label>Message / Caption</label><textarea name="PostText" class="form-control" rows="2" placeholder="What's new at your store?" required></textarea></div>
            
            <div class="form-group">
                <label>Tag a Product</label>
                <div style="position:relative; margin-bottom: 8px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:12px; font-size:12px; color:var(--text-muted);"></i>
                    <input type="text" class="form-control" placeholder="Search your catalog..." style="padding-left:34px; height:38px; font-size:13px;" onkeyup="filterProducts(this)">
                </div>
                <input type="hidden" name="ProductID" id="postTagID">
                <div class="product-gallery">
                    <?php foreach($catalog as $pc): 
                        $pcImg = (!empty($pc['FoodPhoto'])) ? $pc['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($pc['FoodName'])."&background=F3F4F6&color=6B4EE6&bold=true";
                    ?>
                        <div class="pg-item" onclick="pickProduct('<?= $pc['FoodID'] ?>', this, 'postTagID')">
                            <div class="pg-check"><i class="fas fa-check"></i></div>
                            <img src="<?= $pcImg ?>" class="pg-img">
                            <div class="pg-name"><?= htmlspecialchars($pc['FoodName']) ?></div>
                            <div class="pg-price"><?= number_format($pc['FoodPrice'], 2) ?> MAD</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group"><label>Media Gallery (Add up to 4 Photos)</label>
                <div class="media-upload-grid">
                    <?php for($m=1; $m<=4; $m++): 
                        $mKey = ($m === 1) ? 'Photo' : 'Photo'.$m;
                    ?>
                        <div class="upload-frame" id="frame-p<?= $m ?>">
                            <i class="fas fa-image"></i><span>Slide <?= $m ?></span>
                            <input type="file" name="<?= $mKey ?>" accept="image/*" onchange="previewInFrame(this, 'frame-p<?= $m ?>')">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <button type="submit" class="btn-submit">Publish Post</button>
    </form></div></dialog>

    <!-- CREATE STORY MODAL -->
    <dialog id="storyModal"><div class="modal-box"><div class="modal-header"><h3>Upload Story</h3><button class="close-btn" onclick="window.storyModal.close()"><i class="fas fa-times"></i></button></div>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add_story">
            <div class="form-group"><label>Internal Campaign Name</label><input type="text" name="AdName" class="form-control" placeholder="e.g. Weekend Flash Sale" required></div>
            
            <div class="form-group">
                <label>Tag Product (Optional)</label>
                <div style="position:relative; margin-bottom: 8px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:12px; font-size:12px; color:var(--text-muted);"></i>
                    <input type="text" class="form-control" placeholder="Search your catalog..." style="padding-left:34px; height:38px; font-size:13px;" onkeyup="filterProducts(this)">
                </div>
                <input type="hidden" name="ProductID" id="storyTagID">
                <div class="product-gallery">
                    <?php foreach($catalog as $pc): 
                        $pcImg = (!empty($pc['FoodPhoto'])) ? $pc['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($pc['FoodName'])."&background=F3F4F6&color=6B4EE6&bold=true";
                    ?>
                        <div class="pg-item" onclick="pickProduct('<?= $pc['FoodID'] ?>', this, 'storyTagID')">
                            <div class="pg-check"><i class="fas fa-check"></i></div>
                            <img src="<?= $pcImg ?>" class="pg-img">
                            <div class="pg-name"><?= htmlspecialchars($pc['FoodName']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group"><label>Vertical Story Media (Photo or Video)</label>
                <div class="upload-frame" id="frame-s1" style="aspect-ratio: 9/16; height: 300px;">
                    <i class="fas fa-camera"></i><span>Tap to upload story</span>
                    <input type="file" name="Photo" accept="image/*,video/mp4,video/*" required onchange="previewInFrame(this, 'frame-s1')">
                </div>
            </div>
            <button type="submit" class="btn-submit">Publish Story</button>
    </form></div></dialog>

    <!-- CREATE REEL MODAL -->
    <dialog id="reelModal"><div class="modal-box"><div class="modal-header"><h3>Upload Reel Video</h3><button class="close-btn" onclick="window.reelModal.close()"><i class="fas fa-times"></i></button></div>
        <form method="POST" enctype="multipart/form-data" id="reelUploadForm"><input type="hidden" name="action" value="add_reel">
            <div class="form-group"><label>Video Caption</label><textarea name="PostText" class="form-control" rows="2" placeholder="Tell more about this video..."></textarea></div>
            
            <div class="form-group">
                <label>Featured Product</label>
                <div style="position:relative; margin-bottom: 8px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:12px; font-size:12px; color:var(--text-muted);"></i>
                    <input type="text" class="form-control" placeholder="Search your catalog..." style="padding-left:34px; height:38px; font-size:13px;" onkeyup="filterProducts(this)">
                </div>
                <input type="hidden" name="ProductID" id="reelTagID">
                <div class="product-gallery">
                    <?php foreach($catalog as $pc): 
                        $pcImg = (!empty($pc['FoodPhoto'])) ? $pc['FoodPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($pc['FoodName'])."&background=F3F4F6&color=6B4EE6&bold=true";
                    ?>
                        <div class="pg-item" onclick="pickProduct('<?= $pc['FoodID'] ?>', this, 'reelTagID')">
                            <div class="pg-check"><i class="fas fa-check"></i></div>
                            <img src="<?= $pcImg ?>" class="pg-img">
                            <div class="pg-name"><?= htmlspecialchars($pc['FoodName']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group"><label>Video File (MP4 Only)</label>
                <div class="upload-frame" style="height: 120px;">
                    <i class="fas fa-video"></i><span>Tap to pick .mp4 video</span>
                    <input type="file" name="Video" id="reelVideoInput" accept="video/mp4,video/*" required>
                </div>
            </div>
            
            <div id="reelProgressContainer" style="display:none; margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:4px;">
                    <span id="reelProgressText">Uploading...</span>
                    <span id="reelProgressPercent">0%</span>
                </div>
                <div style="width:100%; height:8px; background:#E5E7EB; border-radius:4px; overflow:hidden;">
                    <div id="reelProgressBar" style="width:0%; height:100%; background:var(--primary); transition:0.1s linear;"></div>
                </div>
            </div>

            <button type="submit" id="reelSubmitBtn" class="btn-submit">Upload Reel</button>
    </form></div></dialog>

    <!-- COMMENTS MODAL -->
    <dialog id="commentModal">
        <div class="modal-box" style="padding:0; overflow:hidden;">
            <div class="modal-header" style="padding: 20px 20px 10px; border-bottom: 1px solid #E5E7EB;">
                <h3>Post Comments</h3>
                <button class="close-btn" onclick="window.commentModal.close()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="chat-flow" id="chatEngine" style="padding: 20px;">
                <!-- Dynamically populated bubbles -->
            </div>

            <form method="POST" style="background:#FAFAFB; padding: 16px 20px;">
                <input type="hidden" name="action" value="reply_comment">
                <input type="hidden" name="PostId" id="c_postID">
                <input type="hidden" name="return_tab" id="c_returnTab">
                <div class="chat-input-bar" style="border:none; padding:0; margin:0;">
                    <input type="text" name="CommentText" class="form-control" placeholder="Write a reply..." required autocomplete="off">
                    <button type="submit" class="chat-send-btn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        const COMMENTS_DATA = <?= $commentsJson ?>;
        const DEFAULT_SHOP_ICON = "<?= $defaultShopIcon ?>";
        const SHOP_NAME = "<?= htmlspecialchars($_SESSION['SellerName']) ?>";

        let currentModalTarget = window.postModal;
        
        function openTab(tabName, btnElement, btnText, modalObject) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            btnElement.classList.add('active');

            let mainBtn = document.getElementById('mainAddBtn');
            mainBtn.innerHTML = '<i class="fas fa-plus"></i> ' + btnText;
            currentModalTarget = modalObject;
            mainBtn.onclick = function() { currentModalTarget.showModal(); };
        }
        
        function openComments(pid, tabName) {
            document.getElementById('c_postID').value = pid;
            document.getElementById('c_returnTab').value = tabName;
            
            let chatBox = document.getElementById('chatEngine');
            chatBox.innerHTML = '';

            let comments = COMMENTS_DATA[pid] || [];
            if(comments.length === 0) {
                chatBox.innerHTML = '<div class="empty-state" style="padding: 40px 20px;"><i class="far fa-comments" style="font-size:30px; margin-bottom:10px;"></i><p>No comments yet. Be the first to start the conversation!</p></div>';
            } else {
                comments.forEach(c => {
                    let isShop = (!c.UserID || c.UserID === '0'); 
                    let name = c.UserName || "User";
                    let fallback = "https://ui-avatars.com/api/?name=" + encodeURIComponent(name) + "&background=E5E7EB&color=374151&bold=true";
                    
                    let avatar = '';
                    if(isShop) {
                        avatar = DEFAULT_SHOP_ICON;
                        name = SHOP_NAME + " (You)";
                    } else {
                        avatar = (c.UserPhoto && c.UserPhoto.trim() !== '' && c.UserPhoto !== 'None' && c.UserPhoto.length > 5) ? c.UserPhoto : fallback;
                    }

                    chatBox.innerHTML += `
                    <div class="chat-row ${isShop ? 'reply' : ''}">
                        <img src="${avatar}" onerror="this.onerror=null;this.src='${fallback}';" class="chat-avt">
                        <div style="display:flex; flex-direction:column;">
                            <div class="chat-name">${name}</div>
                            <div class="chat-bubble">${c.CommentText}</div>
                        </div>
                    </div>`;
                });
            }
            window.commentModal.showModal();
            setTimeout(() => { chatBox.scrollTop = chatBox.scrollHeight; }, 50);
        }

        /* ====== MODERN UI HELPERS ====== */
        function previewInFrame(input, frameID) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let frame = document.getElementById(frameID);
                    // Clear existing img if any
                    let existing = frame.querySelector('img');
                    if(existing) existing.remove();
                    
                    let img = document.createElement('img');
                    img.src = e.target.result;
                    frame.appendChild(img);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function pickProduct(pid, element, inputID) {
            let container = element.parentElement;
            // Remove selection from others
            container.querySelectorAll('.pg-item').forEach(el => el.classList.remove('selected'));
            
            // Toggle selection
            let hiddenInput = document.getElementById(inputID);
            if(hiddenInput.value === pid) {
                hiddenInput.value = '';
                element.classList.remove('selected');
            } else {
                hiddenInput.value = pid;
                element.classList.add('selected');
            }
        }

        function filterProducts(input) {
            let filter = input.value.toLowerCase();
            let gallery = input.parentElement.parentElement.querySelector('.product-gallery');
            let items = gallery.querySelectorAll('.pg-item');
            
            items.forEach(it => {
                let name = it.querySelector('.pg-name').innerText.toLowerCase();
                if (name.includes(filter)) {
                    it.style.display = 'block';
                } else {
                    it.style.display = 'none';
                }
            });
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabUrl = urlParams.get('tab');
            if(tabUrl === 'stories') {
                openTab('stories', document.querySelectorAll('.tab-btn')[1], 'Upload Story', window.storyModal);
            } else if (tabUrl === 'reels') {
                openTab('reels', document.querySelectorAll('.tab-btn')[2], 'Upload Reel', window.reelModal);
            }
            if(tabUrl) window.history.replaceState({}, document.title, "content.php");

            // --- SPA SHIMMER HANDLER ---
            setTimeout(() => {
                ['posts', 'stories', 'reels'].forEach(t => {
                    const sk = document.getElementById('skeleton-' + t);
                    const rl = document.getElementById('real-' + t);
                    if(sk && rl) {
                        sk.style.display = 'none';
                        rl.style.display = 'block';
                    }
                });
            }, 500);

            // --- REEL UPLOAD XHR PROGRESS INTERCEPT ---
            const reelForm = document.getElementById('reelUploadForm');
            if (reelForm) {
                reelForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = document.getElementById('reelSubmitBtn');
                    const progContainer = document.getElementById('reelProgressContainer');
                    const progBar = document.getElementById('reelProgressBar');
                    const progPct = document.getElementById('reelProgressPercent');
                    const progText = document.getElementById('reelProgressText');
                    
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    progContainer.style.display = 'block';
                    
                    const formData = new FormData(reelForm);
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', window.location.href, true);
                    
                    xhr.upload.onprogress = function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            progBar.style.width = percentComplete + '%';
                            progPct.innerText = percentComplete + '%';
                            
                            if (percentComplete === 100) {
                                progText.innerText = "Processing video... please wait.";
                            }
                        }
                    };
                    
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            window.location.href = 'content.php?msg=reel_added&tab=reels';
                        } else {
                            alert('Upload failed. Server returned status: ' + xhr.status);
                            submitBtn.disabled = false;
                            submitBtn.style.opacity = '1';
                            progContainer.style.display = 'none';
                        }
                    };
                    
                    xhr.onerror = function() {
                        alert('Network Error during upload.');
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        progContainer.style.display = 'none';
                    };
                    
                    xhr.send(formData);
                });
            }
        }
    </script>
</body>
</html>
