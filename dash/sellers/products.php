<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];
$shopName = $_SESSION['SellerName'];

// --- SERVER-SIDE CRUD LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ADD PRODUCT
    if ($_POST['action'] === 'add') {
        $pName = $con->real_escape_string($_POST['FoodName']);
        $pCat = (int)$_POST['FoodCatID'];
        $pPrice = (float)$_POST['FoodPrice'];
        $pOffer = (float)$_POST['FoodOfferPrice'];
        $pDesc = $con->real_escape_string($_POST['FoodDesc']);
        
        $actualpath = "https://ui-avatars.com/api/?name=".urlencode($pName)."&background=EBE8FA&color=6B4EE6&size=200";
        if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] === UPLOAD_ERR_OK) {
            $photoName = "w-".rand().".png";
            $uploadPath = __DIR__ . '/../photo/' . $photoName;
            if (move_uploaded_file($_FILES["Photo"]["tmp_name"], $uploadPath)) {
                $actualpath = "https://qoon.app/dash/photo/" . $photoName;
            }
        }
        $sql = "INSERT INTO Foods (FoodName, FoodCatID, FoodPrice, FoodOfferPrice, FoodDesc, FoodPhoto, Extraone, ExtraPriceOne, Extratwo, ExtraPriceTwo) 
                VALUES ('$pName', '$pCat', '$pOffer', '$pPrice', '$pDesc', '$actualpath', '#', '#', '#', '#')";
        $con->query($sql);
        header("Location: products.php?msg=added");
        exit;
    }
    
    // EDIT PRODUCT
    if ($_POST['action'] === 'edit') {
        $fID = (int)$_POST['FoodID'];
        $pName = $con->real_escape_string($_POST['FoodName']);
        $pCat = (int)$_POST['FoodCatID'];
        $pPrice = (float)$_POST['FoodPrice'];
        $pOffer = (float)$_POST['FoodOfferPrice'];
        $pDesc = $con->real_escape_string($_POST['FoodDesc']);
        
        $updateSql = "UPDATE Foods SET FoodName='$pName', FoodCatID='$pCat', FoodPrice='$pOffer', FoodOfferPrice='$pPrice', FoodDesc='$pDesc'";
        if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] === UPLOAD_ERR_OK) {
            $photoName = "w-".rand().".png";
            $uploadPath = __DIR__ . '/../photo/' . $photoName;
            if (move_uploaded_file($_FILES["Photo"]["tmp_name"], $uploadPath)) {
                $actualpath = "https://qoon.app/dash/photo/" . $photoName;
                $updateSql .= ", FoodPhoto='$actualpath'";
            }
        }
        $updateSql .= " WHERE FoodID='$fID'";
        $con->query($updateSql);
        header("Location: products.php?msg=edited");
        exit;
    }

    // DELETE PRODUCT
    if ($_POST['action'] === 'delete') {
        $fID = (int)$_POST['FoodID'];
        $con->query("DELETE FROM Foods WHERE FoodID='$fID'");
        header("Location: products.php?msg=deleted");
        exit;
    }
    
    // ADD SHOP CATEGORY
    if ($_POST['action'] === 'add_cat') {
        $catName = $con->real_escape_string($_POST['CategoryName']);
        $con->query("INSERT INTO ShopsCategory (ShopID, CategoryName) VALUES ('$sellerID', '$catName')");
        header("Location: products.php?msg=cat_added");
        exit;
    }

    // DELETE SHOP CATEGORY
    if ($_POST['action'] === 'delete_cat') {
        $cID = (int)$_POST['CategoryShopID'];
        $con->query("DELETE FROM ShopsCategory WHERE CategoryShopID='$cID' AND ShopID='$sellerID'");
        header("Location: products.php?msg=cat_deleted");
        exit;
    }
    
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

    // ADD EXTRA CATEGORY (Group)
    if ($_POST['action'] === 'add_extragrp') {
        $prodID  = (int)$_POST['ProductID'];
        $grpName = $con->real_escape_string($_POST['ExtraCategoryName']);
        $con->query("INSERT INTO ExtraCategory (ProductID, ExtraCategoryName, Multy, ShopId) VALUES ('$prodID', '$grpName', 'YES', '$sellerID')");
        $newID = $con->insert_id;
        if ($isAjax) {
            // Return fresh extras for this product
            $out = [];
            $er2 = $con->query("SELECT * FROM ExtraCategory WHERE ShopId='$sellerID' AND ProductID='$prodID'");
            while ($r2 = $er2->fetch_assoc()) {
                $gid = $r2['ExtraCategoryID'];
                $items = [];
                $ir2 = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$gid'");
                while ($i2 = $ir2->fetch_assoc()) $items[] = $i2;
                $out[] = ['id'=>$gid,'name'=>$r2['ExtraCategoryName'],'multy'=>$r2['Multy'],'items'=>$items];
            }
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true,'groups'=>$out]);
            exit;
        }
        header("Location: products.php?msg=extragrp_added"); exit;
    }

    // DELETE EXTRA CATEGORY (Group)
    if ($_POST['action'] === 'delete_extragrp') {
        $grpID  = (int)$_POST['ExtraCategoryID'];
        $prodID = (int)($_POST['ProductID'] ?? 0);
        $con->query("DELETE FROM ExtraInSideCategoty WHERE ExtraCategoryID='$grpID'");
        $con->query("DELETE FROM ExtraCategory WHERE ExtraCategoryID='$grpID'");
        if ($isAjax) {
            $out = [];
            if ($prodID) {
                $er2 = $con->query("SELECT * FROM ExtraCategory WHERE ProductID='$prodID'");
                while ($r2 = $er2->fetch_assoc()) {
                    $gid = $r2['ExtraCategoryID'];
                    $items = [];
                    $ir2 = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$gid'");
                    while ($i2 = $ir2->fetch_assoc()) $items[] = $i2;
                    $out[] = ['id'=>$gid,'name'=>$r2['ExtraCategoryName'],'multy'=>$r2['Multy'],'items'=>$items];
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true,'groups'=>$out]);
            exit;
        }
        header("Location: products.php?msg=extragrp_deleted"); exit;
    }

    // ADD EXTRA ITEM
    if ($_POST['action'] === 'add_extraval') {
        $grpID  = (int)$_POST['ExtraCategoryID'];
        $prodID = (int)($_POST['ProductID'] ?? 0);
        $vName  = $con->real_escape_string($_POST['Name']);
        $vPrice = (float)$_POST['Price'];
        $con->query("INSERT INTO ExtraInSideCategoty (ExtraCategoryID, Name, Price) VALUES ('$grpID', '$vName', '$vPrice')");
        $newVID = $con->insert_id;
        if ($isAjax) {
            $out = [];
            if ($prodID) {
                $er2 = $con->query("SELECT * FROM ExtraCategory WHERE ProductID='$prodID'");
                while ($r2 = $er2->fetch_assoc()) {
                    $gid = $r2['ExtraCategoryID'];
                    $items = [];
                    $ir2 = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$gid'");
                    while ($i2 = $ir2->fetch_assoc()) $items[] = $i2;
                    $out[] = ['id'=>$gid,'name'=>$r2['ExtraCategoryName'],'multy'=>$r2['Multy'],'items'=>$items];
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true,'groups'=>$out]);
            exit;
        }
        header("Location: products.php?msg=extraval_added"); exit;
    }

    // DELETE EXTRA ITEM
    if ($_POST['action'] === 'delete_extraval') {
        $vID    = (int)$_POST['ExtraInSideCategotyID'];
        $prodID = (int)($_POST['ProductID'] ?? 0);
        $con->query("DELETE FROM ExtraInSideCategoty WHERE ExtraInSideCategotyID='$vID'");
        if ($isAjax) {
            $out = [];
            if ($prodID) {
                $er2 = $con->query("SELECT * FROM ExtraCategory WHERE ProductID='$prodID'");
                while ($r2 = $er2->fetch_assoc()) {
                    $gid = $r2['ExtraCategoryID'];
                    $items = [];
                    $ir2 = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$gid'");
                    while ($i2 = $ir2->fetch_assoc()) $items[] = $i2;
                    $out[] = ['id'=>$gid,'name'=>$r2['ExtraCategoryName'],'multy'=>$r2['Multy'],'items'=>$items];
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true,'groups'=>$out]);
            exit;
        }
        header("Location: products.php?msg=extraval_deleted"); exit;
    }
}

// Fetch categories
$catRes = $con->query("SELECT * FROM ShopsCategory WHERE ShopID='$sellerID'");
$categories = [];
if($catRes) { while($c = $catRes->fetch_assoc()) { $categories[] = $c; } }

// Fetch Extras mapping — query by ProductID so both web-dashboard AND Flutter-app extras are found
// (Flutter's AddCatExtraApi.php inserts WITHOUT ShopId, so we can't filter by ShopId alone)
$extrasData = [];
$prodRes2 = $con->query("SELECT FoodID FROM Foods WHERE FoodID IN (SELECT FoodCatID FROM ShopsCategory WHERE ShopID='$sellerID') OR FoodID IN (SELECT Foods.FoodID FROM Foods JOIN ShopsCategory ON Foods.FoodCatID=ShopsCategory.CategoryShopID WHERE ShopsCategory.ShopID='$sellerID')");
// Get all product IDs for this seller
$sellerProductIDs = [];
$prQ = $con->query("SELECT Foods.FoodID FROM Foods JOIN ShopsCategory ON Foods.FoodCatID=ShopsCategory.CategoryShopID WHERE ShopsCategory.ShopID='$sellerID'");
if ($prQ) while($prRow = $prQ->fetch_assoc()) $sellerProductIDs[] = (int)$prRow['FoodID'];

if (!empty($sellerProductIDs)) {
    $pidList   = implode(',', $sellerProductIDs);
    $extraSql  = "SELECT * FROM ExtraCategory WHERE ProductID IN ($pidList) ORDER BY ExtraCategoryID ASC";
    $extraRes  = $con->query($extraSql);
    if ($extraRes) {
        while($er = $extraRes->fetch_assoc()) {
            $grpID = $er['ExtraCategoryID'];
            $pID   = $er['ProductID'];
            if (!isset($extrasData[$pID])) $extrasData[$pID] = [];
            $extrasData[$pID][$grpID] = [
                'id'    => $grpID,
                'name'  => $er['ExtraCategoryName'],
                'multy' => $er['Multy'],
                'items' => []
            ];
        }
    }
}
if (!empty($extrasData)) {
    $allGrpIds = [];
    foreach($extrasData as $pID => $grps) {
        foreach($grps as $gID => $gData) { $allGrpIds[] = $gID; }
    }
    if (count($allGrpIds) > 0) {
        $idsList = implode(',', $allGrpIds);
        $itemRes = $con->query("SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID IN ($idsList)");
        if ($itemRes) {
            while($ir = $itemRes->fetch_assoc()) {
                $gID = $ir['ExtraCategoryID'];
                foreach($extrasData as $pID => $grps) {
                    if (isset($grps[$gID])) {
                        $extrasData[$pID][$gID]['items'][] = $ir;
                        break;
                    }
                }
            }
        }
    }
    // Reindex JSON to array instead of map for Javascript iterations
    foreach($extrasData as $pID => $grps) {
        $extrasData[$pID] = array_values($grps);
    }
}
$extrasJSON = json_encode($extrasData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | QOON Partner</title>
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
            --accent-green: #059669; --accent-green-bg: #ECFDF5;
            --danger-text: #EE5D50; --danger-bg: rgba(238, 93, 80, 0.1);
            --radius-lg: 24px; --radius-md: 16px; --radius-sm: 12px;
            --shadow-soft: 0 4px 6px -1px rgba(0,0,0,0.02);
            --shadow-float: 0 10px 25px rgba(107, 78, 230, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-master); color: var(--text-base); display: flex; height: 100vh; overflow: hidden; font-family: 'Poppins', sans-serif;}
        .app-envelope { width: 100%; height: 100%; display: flex; background: var(--bg-surface); overflow: hidden; }


        /* Sidebar styles are centralized in sidebar.php */

        /* ====== MAIN COMPONENT ====== */
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; background: #FAFAFB; }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; display: flex; flex-direction: column; gap: 30px; margin: 0 auto; }
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; }
        .search-bar { background: var(--bg-surface); border-radius: 30px; padding: 12px 24px; display: flex; align-items: center; gap: 12px; width: 400px; box-shadow: var(--shadow-soft);}
        .search-bar input { border: none; outline: none; background: transparent; width: 100%; font-family: 'Inter', sans-serif; font-size: 13px; color: var(--text-strong); }
        .search-bar i { color: var(--text-muted); }
        .user-nav { display: flex; align-items: center; gap: 20px; }
        .nav-btn { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-surface); display: flex; align-items: center; justify-content: center; color: var(--text-strong); box-shadow: var(--shadow-soft); cursor: pointer; transition: 0.2s;}
        .profile-btn { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .profile-badge { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-surface); background: var(--brand-purple-light); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--brand-purple); }
        .profile-name { font-size: 14px; font-weight: 600; color: var(--text-strong); }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .s-title { font-size: 24px; font-weight: 700; color: var(--text-strong); }
        .s-subtitle { font-size: 13px; color: var(--text-muted); font-family: 'Inter', sans-serif; }
        
        .btn-primary { background: var(--brand-purple-grad); color: #FFF; border: none; padding: 12px 24px; border-radius: 30px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 15px rgba(107, 78, 230, 0.3); font-family: 'Poppins', sans-serif;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: var(--shadow-float); }
        .btn-secondary { background: var(--bg-surface); color: var(--text-strong); border: 1px solid #E5E7EB; padding: 12px 24px; border-radius: 30px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; font-family: 'Poppins', sans-serif;}
        .btn-secondary:hover { background: #F9FAFB; transform: translateY(-2px); }

        /* ====== PRODUCT GRID ====== */
        .prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; padding-bottom: 40px; }
        .prod-card { background: var(--bg-surface); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column; gap: 16px; transition: 0.2s; box-shadow: var(--shadow-soft); position: relative; border: 1px solid transparent;}
        .prod-card:hover { border-color: rgba(107, 78, 230, 0.2); box-shadow: var(--shadow-float); transform: translateY(-4px); }

        .p-top { display: flex; gap: 16px; align-items: center; }
        .p-img { width: 72px; height: 72px; border-radius: var(--radius-sm); object-fit: cover; background: #F3F4F6; flex-shrink:0; }
        .p-info { flex: 1; overflow: hidden; }
        .p-name { font-size: 15px; font-weight: 700; color: var(--text-strong); line-height: 1.2; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .p-cat { font-size: 10px; font-weight: 600; color: var(--brand-purple); background: var(--brand-purple-light); padding: 4px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .p-price { font-size: 18px; font-weight: 800; color: var(--text-strong); display: flex; align-items: center; gap: 6px; margin-top: 10px; font-family: 'Poppins', sans-serif;}
        .p-price span { font-size: 12px; font-weight: 600; color: var(--text-muted); }

        .p-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #E5E7EB; padding-top: 16px; margin-top: auto; }
        .p-date { font-size: 12px; font-weight: 500; color: var(--text-muted); display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; }
        
        .p-actions { display: flex; gap: 8px; }
        .act-btn { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; border:none; cursor: pointer; transition: 0.2s; }
        .act-opts { background: var(--accent-blue); color: var(--accent-blue-text); }
        .act-opts:hover { background: var(--accent-blue-text); color: #FFF; }
        .act-edit { background: var(--bg-master); color: var(--text-base); }
        .act-edit:hover { background: var(--brand-purple-light); color: var(--brand-purple); }
        .act-del { background: var(--danger-bg); color: var(--danger-text); }
        .act-del:hover { background: var(--danger-text); color: #FFF; }

        .empty-state { grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); font-size: 14px; font-family: 'Inter', sans-serif;}

        /* ====== MODAL STYLES ====== */
        dialog { margin: auto; border: none; border-radius: var(--radius-lg); padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); background: transparent; width: 100%; max-width: 500px; }
        dialog::backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-box { background: var(--bg-surface); padding: 30px; border-radius: var(--radius-lg); display: flex; flex-direction: column; gap: 20px; max-height: 85vh; overflow-y: auto;}
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F3F4F6; padding-bottom: 16px; margin-bottom: 4px; }
        .modal-header h3 { font-size: 20px; font-weight: 700; color: var(--text-strong); }
        .close-btn { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; }
        .close-btn:hover { color: var(--text-strong); }
        
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-base); font-family: 'Inter', sans-serif; }
        .form-control { background: var(--bg-master); border: 1px solid transparent; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 14px; color: var(--text-strong); font-family: 'Inter', sans-serif; outline: none; transition: 0.2s; width: 100%; }
        .form-control:focus { border-color: var(--brand-purple); background: var(--bg-surface); box-shadow: 0 0 0 4px var(--brand-purple-light); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-submit { width: 100%; background: var(--brand-purple-grad); color: #FFF; border: none; padding: 14px; border-radius: var(--radius-md); font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; font-family: 'Poppins', sans-serif; box-shadow: 0 4px 15px rgba(107, 78, 230, 0.3); transition: 0.2s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: var(--shadow-float); }

        /* LIST ITEMS INSIDE MODAL */
        .list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #FAFAFB; border-radius: var(--radius-sm); margin-bottom: 8px; border: 1px solid #F3F4F6; }
        .list-title { font-size: 14px; font-weight: 600; color: var(--text-strong); }
        .list-sub { font-size: 11px; color: var(--brand-purple); font-weight: 600; background: var(--brand-purple-light); padding: 2px 8px; border-radius: 10px; }
        .pill-add { font-size: 12px; font-weight: 600; color: var(--accent-blue-text); background: var(--accent-blue); border:none; padding:4px 10px; border-radius: 12px; cursor:pointer;}
        .extra-child { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #FFF; border-left: 2px solid var(--border-color); margin-left: 20px; margin-bottom: 4px;}
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; }
            .top-navbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            .search-bar { width: 100%; }
            .user-nav { width: 100%; justify-content: space-between; }
            .prod-grid { grid-template-columns: 1fr; gap: 16px; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            dialog { max-width: calc(100% - 32px); margin: 16px auto; }
            .form-row { grid-template-columns: 1fr; }
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
                    <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search products..."></div>
                    <div class="user-nav">
                        <div class="profile-btn">
                            <?php if (!empty($SHOP_DATA['ShopLogo'])): ?>
                                <img src="<?= htmlspecialchars($SHOP_DATA['ShopLogo']) ?>" class="profile-badge">
                            <?php else: ?>
                                <div class="profile-badge"><?= substr($_SESSION['SellerName'] ?? 'S', 0, 1) ?></div>
                            <?php endif; ?>
                            <div class="profile-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                        </div>
                    </div>
                </header>

                <div>
                    <div class="section-header">
                        <div>
                            <div class="s-title">Products</div>
                            <div class="s-subtitle">Manage pricing, inventory, and menu layout.</div>
                        </div>
                        <div style="display:flex; gap: 12px;">
                            <button class="btn-secondary" onclick="window.catModal.showModal()">
                                <i class="fas fa-list"></i> Manage Categories
                            </button>
                            <button class="btn-primary" onclick="window.addModal.showModal()">
                                <i class="fas fa-plus"></i> Add New Product
                            </button>
                        </div>
                    </div>

                    <!-- Skeleton Shimmer Layout -->
                    <div id="skeleton-grid" class="prod-grid">
                        <?php for($s=0; $s<8; $s++): ?>
                        <div class="prod-card" style="border:none;">
                            <div class="p-top">
                                <div class="shimmer-bg" style="width:72px; height:72px; border-radius:var(--radius-sm); flex-shrink:0;"></div>
                                <div class="p-info" style="display:flex; flex-direction:column; gap:10px; justify-content:center; width:100%;">
                                    <div class="shimmer-bg" style="height:14px; width:75%; border-radius:4px;"></div>
                                    <div class="shimmer-bg" style="height:12px; width:35%; border-radius:12px;"></div>
                                    <div class="shimmer-bg" style="height:16px; width:45%; border-radius:4px; margin-top:2px;"></div>
                                </div>
                            </div>
                            <div class="p-footer">
                                <div class="shimmer-bg" style="height:12px; width:40%; border-radius:4px;"></div>
                                <div class="p-actions">
                                    <div class="shimmer-bg" style="width:32px; height:32px; border-radius:50%;"></div>
                                    <div class="shimmer-bg" style="width:32px; height:32px; border-radius:50%;"></div>
                                    <div class="shimmer-bg" style="width:32px; height:32px; border-radius:50%;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Hidden Real Content -->
                    <div id="real-grid" style="display: none;">
                        <?php 
                        if ($stmt = $con->prepare("SELECT Foods.*, ShopsCategory.CategoryName FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID WHERE ShopsCategory.ShopID=? ORDER BY FoodID DESC")) {
                            $stmt->bind_param("s", $sellerID);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            
                            if ($res->num_rows > 0) {
                                echo '<div class="prod-grid">';
                                $delay = 0;
                                while ($row = $res->fetch_assoc()) {
                                    $pht = trim((string)$row["FoodPhoto"]);
                                    $imgSrc = (!empty($pht)) ? htmlspecialchars($pht) : "https://ui-avatars.com/api/?name=".urlencode($row["FoodName"])."&background=F3F4F6&color=6B4EE6&size=200";
                        ?>
                                    <div class="prod-card fade-in-up" style="animation-delay: <?= $delay ?>s;">
                                        <div class="p-top">
                                            <img class="p-img" src="<?= $imgSrc ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row["FoodName"]) ?>&background=F3F4F6&color=6B4EE6';">
                                            <div class="p-info">
                                                <div class="p-name" title="<?= htmlspecialchars($row["FoodName"]) ?>"><?= htmlspecialchars($row["FoodName"]) ?></div>
                                                <div class="p-cat"><?= htmlspecialchars($row["CategoryName"]) ?></div>
                                                <div class="p-price"><?= number_format($row["FoodPrice"], 2) ?> <span>MAD</span></div>
                                            </div>
                                        </div>
                                        <div class="p-footer">
                                            <div class="p-date"><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($row["CreatedAtFoods"])) ?></div>
                                            <div class="p-actions">
                                                <button class="act-btn act-opts" onclick="triggerExtras(<?= $row['FoodID'] ?>, '<?= htmlspecialchars(addslashes($row['FoodName'])) ?>')" title="Manage Extra Addons"><i class="fas fa-list-ul"></i></button>
                                                <button class="act-btn act-edit" onclick="triggerEdit(<?= htmlspecialchars(json_encode($row)) ?>)" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                                
                                                <form method="POST" style="display:inline;" id="delForm_<?= $row['FoodID'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="FoodID" value="<?= $row['FoodID'] ?>">
                                                    <button type="button" class="act-btn act-del" title="Delete Product" onclick="confirmDelete('delForm_<?= $row['FoodID'] ?>', 'product', '<?= addslashes(htmlspecialchars($row['FoodName'])) ?>')"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                        <?php
                                $delay += 0.05;
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="empty-state fade-in-up">
                                        <i class="fas fa-box-open" style="font-size: 40px; color: #E5E7EB; margin-bottom: 16px;"></i>
                                        <div>No products found. Start by adding one.</div>
                                      </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- CATEGORY MODAL -->
    <dialog id="catModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Menu Categories</h3>
                <button class="close-btn" onclick="window.catModal.close()"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" style="display:flex; gap:10px; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_cat">
                <input type="text" name="CategoryName" class="form-control" placeholder="New Category (e.g. Burgers)" required>
                <button type="submit" class="btn-primary" style="margin:0; border-radius: 12px; padding: 0 20px;">Add</button>
            </form>
            
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach($categories as $cat): ?>
                    <div class="list-item">
                        <div class="list-title"><?= htmlspecialchars($cat['CategoryName']) ?></div>
                        <form method="POST" id="delCat_<?= $cat['CategoryShopID'] ?>">
                            <input type="hidden" name="action" value="delete_cat">
                            <input type="hidden" name="CategoryShopID" value="<?= $cat['CategoryShopID'] ?>">
                            <button type="button" class="act-btn act-del" onclick="confirmDelete('delCat_<?= $cat['CategoryShopID'] ?>', 'category', '<?= addslashes(htmlspecialchars($cat['CategoryName'])) ?>')"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </dialog>

    <!-- EXTRAS MODAL — rebuilt with color/size presets -->
    <dialog id="extraModal" style="max-width:620px; width:95%;">
        <div class="modal-box" style="gap:0; padding:0; overflow:hidden;">

            <!-- Header -->
            <div style="padding:24px 28px 0; border-bottom:1px solid #F3F4F6; padding-bottom:16px; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h3 style="font-size:18px; font-weight:800; color:#111827; margin-bottom:4px;">Extra Modifiers</h3>
                    <p id="ex_prod_name" style="font-size:12px; color:var(--brand-purple); font-weight:700; margin:0;"></p>
                </div>
                <button class="close-btn" onclick="window.extraModal.close()"><i class="fas fa-times"></i></button>
            </div>

            <!-- Quick Preset Tabs -->
            <div style="padding:16px 28px; background:#FAFBFF; border-bottom:1px solid #F3F4F6;">
                <p style="font-size:11px; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;">Quick Add Group</p>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="preset-btn" onclick="addPresetGroup('Colors', 'color')">
                        <i class="fas fa-palette"></i> Colors
                    </button>
                    <button type="button" class="preset-btn" onclick="addPresetGroup('Sizes', 'size')">
                        <i class="fas fa-ruler"></i> Sizes
                    </button>
                    <button type="button" class="preset-btn" onclick="addPresetGroup('Material', 'text')">
                        <i class="fas fa-layer-group"></i> Material
                    </button>
                    <button type="button" class="preset-btn" onclick="addPresetGroup('Extras', 'text')">
                        <i class="fas fa-plus-circle"></i> Extras
                    </button>
                    <button type="button" class="preset-btn" onclick="showCustomGroupForm()">
                        <i class="fas fa-pen"></i> Custom…
                    </button>
                </div>

                <!-- Custom group form (hidden by default) -->
                <form id="customGrpForm" method="POST" style="display:none; margin-top:12px; display:none; gap:8px; align-items:center;">
                    <input type="hidden" name="action" value="add_extragrp">
                    <input type="hidden" name="ProductID" id="ex_prod_id">
                    <input type="text" name="ExtraCategoryName" class="form-control" placeholder="Group name e.g. Flavour" required style="flex:1; padding:10px 14px; font-size:13px;">
                    <button type="submit" class="btn-primary" style="margin:0; border-radius:10px; padding:10px 18px; font-size:13px;">Add</button>
                </form>
                <!-- Hidden auto-submit form for presets -->
                <form id="presetGrpForm" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="add_extragrp">
                    <input type="hidden" name="ProductID" id="preset_prod_id">
                    <input type="hidden" name="ExtraCategoryName" id="preset_grp_name">
                </form>
            </div>

            <!-- Modifier Tree -->
            <div style="padding:16px 28px; max-height:380px; overflow-y:auto;" id="extra_tree"></div>

            <!-- Add value panel (shown when adding items to a group) -->
            <div id="addValuePanel" style="display:none; padding:14px 28px; background:#F8FAFC; border-top:1px solid #F1F5F9;">
                <p id="addValueLabel" style="font-size:12px; font-weight:700; color:#6B4EE6; margin-bottom:10px;"></p>
                
                <!-- Color picker mode -->
                <div id="colorPickerMode" style="display:none;">
                    <p style="font-size:11px; color:#9CA3AF; margin-bottom:8px;">Quick colors:</p>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;" id="colorSwatches">
                        <?php
                        $colors = [
                            ['name'=>'Black','hex'=>'#000000'],['name'=>'White','hex'=>'#FFFFFF'],
                            ['name'=>'Red','hex'=>'#EF4444'],['name'=>'Blue','hex'=>'#3B82F6'],
                            ['name'=>'Green','hex'=>'#10B981'],['name'=>'Yellow','hex'=>'#F59E0B'],
                            ['name'=>'Pink','hex'=>'#EC4899'],['name'=>'Purple','hex'=>'#8B5CF6'],
                            ['name'=>'Orange','hex'=>'#F97316'],['name'=>'Gray','hex'=>'#6B7280'],
                            ['name'=>'Brown','hex'=>'#92400E'],['name'=>'Navy','hex'=>'#1E3A5F'],
                            ['name'=>'Beige','hex'=>'#D4B896'],['name'=>'Burgundy','hex'=>'#800020'],
                        ];
                        foreach($colors as $c): ?>
                        <button type="button" class="color-swatch" onclick="quickAddColor('<?= $c['name'] ?>', '<?= $c['hex'] ?>')"
                            title="<?= $c['name'] ?>"
                            style="width:28px;height:28px;border-radius:50%;background:<?= $c['hex'] ?>;border:2px solid <?= $c['hex']==='#FFFFFF'?'#E5E7EB':'transparent' ?>;cursor:pointer;transition:transform 0.15s;"
                            onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                        </button>
                        <?php endforeach; ?>
                        <!-- Custom color -->
                        <label style="width:28px;height:28px;border-radius:50%;border:2px dashed #CBD5E1;display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;" title="Custom color">
                            <input type="color" id="customColorPicker" onchange="quickAddCustomColor(this.value)" style="opacity:0;width:1px;height:1px;position:absolute;">
                            <i class="fas fa-plus" style="font-size:10px;color:#9CA3AF;"></i>
                        </label>
                    </div>
                    <p style="font-size:11px; color:#9CA3AF; margin-bottom:6px;">Or type a custom color name:</p>
                </div>

                <!-- Size chips mode -->
                <div id="sizeChipsMode" style="display:none; margin-bottom:12px;">
                    <p style="font-size:11px; color:#9CA3AF; margin-bottom:8px;">Quick sizes:</p>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <?php foreach(['XS','S','M','L','XL','XXL','XXXL','One Size','36','37','38','39','40','41','42','43','44','45'] as $sz): ?>
                        <button type="button" class="size-chip" onclick="quickAddValue('<?= $sz ?>', 0)">
                            <?= $sz ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:11px; color:#9CA3AF; margin:10px 0 6px;">Or type custom size:</p>
                </div>

                <!-- Generic add form -->
                <form id="addValueForm" method="POST" style="display:flex; gap:8px; align-items:center;">
                    <input type="hidden" name="action" value="add_extraval">
                    <input type="hidden" name="ExtraCategoryID" id="avf_grp_id">
                    <input type="text" name="Name" id="avf_name" class="form-control" placeholder="Name" required style="flex:1; padding:10px 14px; font-size:13px;">
                    <input type="number" step="0.01" min="0" name="Price" id="avf_price" class="form-control" placeholder="+MAD" value="0" style="width:90px; padding:10px 12px; font-size:13px;">
                    <button type="submit" class="btn-primary" style="margin:0; border-radius:10px; padding:10px 18px; font-size:13px; white-space:nowrap;">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </form>
            </div>

        </div>
    </dialog>

    <style>
        .preset-btn {
            display:inline-flex; align-items:center; gap:6px;
            padding:8px 14px; border-radius:20px;
            background:#FFF; border:1.5px solid #E5E7EB;
            font-size:12px; font-weight:700; color:#374151;
            cursor:pointer; transition:all 0.15s;
            font-family:inherit;
        }
        .preset-btn:hover { border-color:var(--brand-purple); color:var(--brand-purple); background:#F3F0FF; }
        .size-chip {
            padding:6px 14px; border-radius:8px;
            background:#F3F4F6; border:1.5px solid transparent;
            font-size:12px; font-weight:700; color:#374151;
            cursor:pointer; transition:all 0.15s; font-family:inherit;
        }
        .size-chip:hover { border-color:var(--brand-purple); color:var(--brand-purple); background:#F3F0FF; }
        .size-chip.used { border-color:#10B981; color:#10B981; background:#ECFDF5; }
        .mod-group { border:1.5px solid #E5E7EB; border-radius:14px; margin-bottom:12px; overflow:hidden; }
        .mod-group-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#FAFBFF; }
        .mod-group-title { font-size:14px; font-weight:700; color:#111827; display:flex; align-items:center; gap:8px; }
        .mod-group-title i { color:var(--brand-purple); }
        .mod-items { padding:8px 12px 10px; display:flex; flex-wrap:wrap; gap:6px; }
        .mod-item-chip {
            display:inline-flex; align-items:center; gap:6px;
            padding:5px 10px; background:#F8FAFC; border:1px solid #E5E7EB;
            border-radius:20px; font-size:12px; font-weight:600; color:#374151;
        }
        .mod-item-color { width:12px; height:12px; border-radius:50%; flex-shrink:0; border:1px solid rgba(0,0,0,0.1); }
        .mod-item-del { background:none; border:none; cursor:pointer; color:#9CA3AF; padding:0; margin-left:2px; font-size:10px; }
        .mod-item-del:hover { color:#EF4444; }
        .add-to-grp-btn {
            display:inline-flex; align-items:center; gap:4px;
            padding:5px 12px; border-radius:20px;
            background:var(--brand-purple-light); color:var(--brand-purple);
            border:none; cursor:pointer; font-size:11px; font-weight:700;
            font-family:inherit; transition:0.15s;
        }
        .add-to-grp-btn:hover { background:var(--brand-purple); color:#FFF; }
    </style>

    <script>
        // Live in-memory store for the current product's groups
        // Populated from PHP on load, then kept in sync via AJAX
        const EXTRAS_DATA = <?= $extrasJSON ?>;
        let _curPID  = null;
        let _curGrpID = null;

        /* ── Open modal ─────────────────────────────────── */
        function triggerExtras(pID, pName) {
            _curPID = pID;
            document.getElementById('ex_prod_id').value      = pID;
            document.getElementById('preset_prod_id').value  = pID;
            document.getElementById('ex_prod_name').innerText = 'For: ' + pName;
            document.getElementById('addValuePanel').style.display = 'none';
            renderTree(EXTRAS_DATA[pID] || []);
            window.extraModal.showModal();
        }

        /* ── Render the modifier tree ────────────────────── */
        function renderTree(arr) {
            const tree = document.getElementById('extra_tree');
            tree.innerHTML = '';
            if (!arr || arr.length === 0) {
                tree.innerHTML = '<div style="padding:24px; text-align:center; color:#9CA3AF; font-size:13px;"><i class="fas fa-layer-group" style="font-size:28px; display:block; margin-bottom:8px; color:#E5E7EB;"></i>No modifiers yet. Use the quick-add buttons above.</div>';
                return;
            }
            arr.forEach(grp => {
                const icon = grp.name.toLowerCase().includes('color') ? 'fa-palette'
                           : grp.name.toLowerCase().includes('size')  ? 'fa-ruler' : 'fa-list-ul';
                let itemsHtml = '';
                (grp.items || []).forEach(itm => {
                    const parts      = (itm.Name || '').split('|');
                    const dispName   = parts[0];
                    const colorHex   = parts[1] || '';
                    const priceLabel = parseFloat(itm.Price) > 0 ? ' +' + parseFloat(itm.Price).toFixed(2) : '';
                    itemsHtml += `
                    <span class="mod-item-chip">
                        ${colorHex ? `<span class="mod-item-color" style="background:${colorHex};"></span>` : ''}
                        ${dispName}${priceLabel}
                        <button class="mod-item-del" type="button"
                            onclick="ajaxDelVal(${itm.ExtraInSideCategotyID})" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>`;
                });
                tree.innerHTML += `
                <div class="mod-group" id="modgrp_${grp.id}">
                    <div class="mod-group-header">
                        <div class="mod-group-title"><i class="fas ${icon}"></i>${grp.name}</div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" class="add-to-grp-btn" onclick="openAddPanel(${grp.id},'${grp.name.replace(/'/g,"\\'")}')"><i class="fas fa-plus"></i> Add Option</button>
                            <button type="button" class="act-btn act-del" style="width:28px;height:28px;"
                                onclick="ajaxDelGrp(${grp.id},'${grp.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash" style="font-size:10px;"></i></button>
                        </div>
                    </div>
                    <div class="mod-items" id="modgrp_items_${grp.id}">${itemsHtml||'<span style="font-size:12px;color:#9CA3AF;padding:4px 8px;">No options yet</span>'}</div>
                </div>`;
            });
        }

        /* ── Generic AJAX POST helper ────────────────────── */
        async function ajaxPost(body) {
            const fd = new FormData();
            Object.entries(body).forEach(([k,v]) => fd.append(k,v));
            const r = await fetch('products.php', {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest'},
                body: fd
            });
            return r.json();
        }

        /* ── Add preset group (Colors, Sizes, etc.) ────── */
        async function addPresetGroup(name) {
            const btn = event.currentTarget;
            btn.disabled = true;
            const data = await ajaxPost({action:'add_extragrp', ProductID:_curPID, ExtraCategoryName:name});
            btn.disabled = false;
            if (data.ok) {
                EXTRAS_DATA[_curPID] = data.groups;
                renderTree(data.groups);
                // Auto-open add panel for the new group
                const newGrp = data.groups.find(g => g.name === name);
                if (newGrp) openAddPanel(newGrp.id, newGrp.name);
            }
        }

        /* ── Custom group form ───────────────────────────── */
        function showCustomGroupForm() {
            const f = document.getElementById('customGrpForm');
            f.style.display = f.style.display === 'none' || !f.style.display ? 'flex' : 'none';
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('customGrpForm').addEventListener('submit', async e => {
                e.preventDefault();
                const nameVal = e.target.querySelector('[name=ExtraCategoryName]').value.trim();
                if (!nameVal) return;
                const data = await ajaxPost({action:'add_extragrp', ProductID:_curPID, ExtraCategoryName:nameVal});
                if (data.ok) {
                    EXTRAS_DATA[_curPID] = data.groups;
                    renderTree(data.groups);
                    e.target.reset();
                    document.getElementById('customGrpForm').style.display = 'none';
                    const newGrp = data.groups.find(g => g.name === nameVal);
                    if (newGrp) openAddPanel(newGrp.id, newGrp.name);
                }
            });
            document.getElementById('addValueForm').addEventListener('submit', async e => {
                e.preventDefault();
                const nameVal  = document.getElementById('avf_name').value.trim();
                const priceVal = document.getElementById('avf_price').value || '0';
                if (!nameVal) return;
                const data = await ajaxPost({action:'add_extraval', ProductID:_curPID, ExtraCategoryID:_curGrpID, Name:nameVal, Price:priceVal});
                if (data.ok) {
                    EXTRAS_DATA[_curPID] = data.groups;
                    renderTree(data.groups);
                    // Re-open the same panel
                    const grp = data.groups.find(g => g.id == _curGrpID);
                    if (grp) openAddPanel(grp.id, grp.name);
                    document.getElementById('avf_name').value = '';
                    document.getElementById('avf_price').value = '0';
                }
            });
        });

        /* ── Delete group ───────────────────────────────── */
        function ajaxDelGrp(grpID, grpName) {
            if (!confirm('Delete "' + grpName + '" and all its options?')) return;
            ajaxPost({action:'delete_extragrp', ExtraCategoryID:grpID, ProductID:_curPID}).then(data => {
                if (data.ok) {
                    EXTRAS_DATA[_curPID] = data.groups;
                    renderTree(data.groups);
                    document.getElementById('addValuePanel').style.display = 'none';
                }
            });
        }

        /* ── Delete value ───────────────────────────────── */
        function ajaxDelVal(valID) {
            ajaxPost({action:'delete_extraval', ExtraInSideCategotyID:valID, ProductID:_curPID}).then(data => {
                if (data.ok) {
                    EXTRAS_DATA[_curPID] = data.groups;
                    renderTree(data.groups);
                    if (_curGrpID) {
                        const grp = data.groups.find(g => g.id == _curGrpID);
                        if (grp) openAddPanel(grp.id, grp.name);
                    }
                }
            });
        }

        /* ── Open add-value panel ───────────────────────── */
        function openAddPanel(grpID, grpName) {
            _curGrpID = grpID;
            document.getElementById('avf_grp_id').value = grpID;
            document.getElementById('avf_name').value   = '';
            document.getElementById('avf_price').value  = '0';
            document.getElementById('addValuePanel').style.display = 'block';
            document.getElementById('addValueLabel').textContent   = 'Adding option to: ' + grpName;
            const isColor = grpName.toLowerCase().includes('color');
            const isSize  = grpName.toLowerCase().includes('size');
            document.getElementById('colorPickerMode').style.display = isColor ? 'block' : 'none';
            document.getElementById('sizeChipsMode').style.display   = isSize  ? 'block' : 'none';
            document.getElementById('avf_name').placeholder = isColor ? 'Color name (e.g. Coral Pink)' : isSize ? 'Custom size' : 'Option name';
        }

        /* ── Color helpers ───────────────────────────────── */
        function quickAddColor(name, hex) {
            document.getElementById('avf_name').value  = name + '|' + hex;
            document.getElementById('avf_price').value = '0';
            document.getElementById('addValueForm').dispatchEvent(new Event('submit', {bubbles:true, cancelable:true}));
        }
        function quickAddCustomColor(hex) {
            const name = prompt('Enter a name for this color:', 'Custom');
            if (!name) return;
            document.getElementById('avf_name').value  = name + '|' + hex;
            document.getElementById('avf_price').value = '0';
            document.getElementById('addValueForm').dispatchEvent(new Event('submit', {bubbles:true, cancelable:true}));
        }

        /* ── Size / generic quick-add ─────────────────── */
        function quickAddValue(val, price) {
            document.getElementById('avf_name').value  = val;
            document.getElementById('avf_price').value = price;
            document.getElementById('addValueForm').dispatchEvent(new Event('submit', {bubbles:true, cancelable:true}));
        }
    </script>



    <!-- ADD/EDIT PRODUCT MODALS -->
    <dialog id="addModal"><div class="modal-box"><div class="modal-header"><h3>Add Product</h3><button class="close-btn" onclick="window.addModal.close()"><i class="fas fa-times"></i></button></div>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add"><div class="form-group"><label>Product Name</label><input type="text" name="FoodName" class="form-control" required></div><div class="form-group"><label>Category</label><select name="FoodCatID" class="form-control" required><?php foreach($categories as $cat): ?><option value="<?= $cat['CategoryShopID'] ?>"><?= htmlspecialchars($cat['CategoryName']) ?></option><?php endforeach; ?></select></div><div class="form-row"><div class="form-group"><label>Normal Price (MAD)</label><input type="number" step="0.01" name="FoodOfferPrice" class="form-control" required></div><div class="form-group"><label>Offer Price (MAD)</label><input type="number" step="0.01" name="FoodPrice" class="form-control" required></div></div><div class="form-group"><label>Description</label><textarea name="FoodDesc" class="form-control" rows="2"></textarea></div><div class="form-group"><label>Image</label><input type="file" name="Photo" class="form-control" accept="image/*"></div><button type="submit" class="btn-submit">Save Product</button></form></div></dialog>

    <dialog id="editModal"><div class="modal-box"><div class="modal-header"><h3>Edit Product</h3><button class="close-btn" onclick="window.editModal.close()"><i class="fas fa-times"></i></button></div>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="edit"><input type="hidden" name="FoodID" id="e_id"><div class="form-group"><label>Product Name</label><input type="text" name="FoodName" id="e_name" class="form-control" required></div><div class="form-group"><label>Category</label><select name="FoodCatID" id="e_cat" class="form-control" required><?php foreach($categories as $cat): ?><option value="<?= $cat['CategoryShopID'] ?>"><?= htmlspecialchars($cat['CategoryName']) ?></option><?php endforeach; ?></select></div><div class="form-row"><div class="form-group"><label>Normal Price (MAD)</label><input type="number" step="0.01" name="FoodOfferPrice" id="e_offer" class="form-control" required></div><div class="form-group"><label>Offer Price (MAD)</label><input type="number" step="0.01" name="FoodPrice" id="e_price" class="form-control" required></div></div><div class="form-group"><label>Description</label><textarea name="FoodDesc" id="e_desc" class="form-control" rows="2"></textarea></div>
        <div style="display:flex; gap: 16px; align-items:center; margin-top:8px;">
            <img id="e_img_preview" style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; background: #F3F4F6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); flex-shrink: 0;">
            <div class="form-group" style="flex:1; margin:0;"><label>Update Image</label><input type="file" name="Photo" class="form-control" accept="image/*"></div>
        </div>
        <button type="submit" class="btn-submit">Update Product</button></form></div></dialog>

    <script>
        function triggerEdit(productData) {
            document.getElementById('e_id').value = productData.FoodID;
            document.getElementById('e_name').value = productData.FoodName;
            document.getElementById('e_cat').value = productData.FoodCatID;
            document.getElementById('e_offer').value = productData.FoodOfferPrice;
            document.getElementById('e_price').value = productData.FoodPrice;
            document.getElementById('e_desc').value = productData.FoodDesc || '';
            
            let pht = productData.FoodPhoto;
            let imgSrc = (pht && pht.toString().trim() !== '') ? pht.toString().trim() : "https://ui-avatars.com/api/?name=" + encodeURIComponent(productData.FoodName) + "&background=EBE8FA&color=6B4EE6&size=200";
            let preview = document.getElementById('e_img_preview');
            preview.src = imgSrc;
            preview.onerror = function() {
                this.src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(productData.FoodName) + "&background=EBE8FA&color=6B4EE6&size=200";
            };
            
            window.editModal.showModal();
        }
    </script>
<!-- MODERN DELETE CONFIRM MODAL -->
<div id="deleteConfirmOverlay" style="
    display:none; position:fixed; inset:0; z-index:999999;
    background:rgba(0,0,0,0.5); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
">
    <div id="deleteConfirmBox" style="
        background:#fff; border-radius:24px; padding:36px 32px; max-width:380px; width:90%;
        box-shadow:0 30px 80px rgba(0,0,0,0.2);
        transform:scale(0.88); opacity:0;
        transition:transform 0.25s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease;
    ">
        <!-- Icon -->
        <div style="text-align:center; margin-bottom:20px;">
            <div style="
                width:64px; height:64px; border-radius:50%;
                background:#FEF2F2; display:inline-flex;
                align-items:center; justify-content:center;
            ">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
            </div>
        </div>
        <!-- Title -->
        <h3 style="text-align:center; font-size:18px; font-weight:800; color:#111; margin-bottom:8px; letter-spacing:-0.3px;" id="confirmTitle">Delete Product?</h3>
        <!-- Message -->
        <p style="text-align:center; font-size:13px; color:#6B7280; line-height:1.6; margin-bottom:28px;" id="confirmMsg">
            This action cannot be undone.
        </p>
        <!-- Buttons -->
        <div style="display:flex; gap:10px;">
            <button onclick="closeDeleteConfirm()" style="
                flex:1; padding:13px; border-radius:14px;
                border:1.5px solid #E5E7EB; background:#fff;
                font-size:14px; font-weight:700; cursor:pointer; color:#374151;
                transition:background 0.15s;
            " onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='#fff'">
                Cancel
            </button>
            <button onclick="executeDelete()" style="
                flex:1; padding:13px; border-radius:14px;
                border:none; background:#EF4444; color:#fff;
                font-size:14px; font-weight:800; cursor:pointer;
                transition:background 0.15s;
            " onmouseover="this.style.background='#DC2626'" onmouseout="this.style.background='#EF4444'">
                Yes, Delete
            </button>
        </div>
    </div>
</div>

<script>
    let _pendingFormId = null;

    function confirmDelete(formId, type, name) {
        _pendingFormId = formId;
        const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('confirmTitle').textContent = 'Delete ' + typeLabel + '?';
        document.getElementById('confirmMsg').textContent =
            '"' + name + '" will be permanently removed. This action cannot be undone.';

        const overlay = document.getElementById('deleteConfirmOverlay');
        const box = document.getElementById('deleteConfirmBox');
        overlay.style.display = 'flex';
        // Trigger animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                box.style.transform = 'scale(1)';
                box.style.opacity  = '1';
            });
        });
    }

    function closeDeleteConfirm() {
        const overlay = document.getElementById('deleteConfirmOverlay');
        const box = document.getElementById('deleteConfirmBox');
        box.style.transform = 'scale(0.88)';
        box.style.opacity   = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 200);
        _pendingFormId = null;
    }

    function executeDelete() {
        if (_pendingFormId) {
            const form = document.getElementById(_pendingFormId);
            if (form) form.submit();
        }
        closeDeleteConfirm();
    }

    // Close on overlay click
    document.getElementById('deleteConfirmOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteConfirm();
    });

    // Handle premium shimmer loading transition
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            const skeleton = document.getElementById('skeleton-grid');
            const real = document.getElementById('real-grid');
            if(skeleton && real) {
                skeleton.style.display = 'none';
                real.style.display = 'block';
            }
        }, 500); // 500ms guaranteed skeleton display for premium transition feel
    });
</script>
</body>
</html>
