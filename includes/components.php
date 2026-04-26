<?php
function renderPostCard($post, $DomainNamee) {
    // 1. Process Shop Info
    $shopName = htmlspecialchars($post['ShopName'] ?? 'QOON Shop');
    $sPhotoRaw = $post['ShopLogo'] ?? $post['ShopPhoto'] ?? $post['Logo'] ?? $post['Photo'] ?? $post['Image'] ?? null;
    $sPhoto = get_img_url($sPhotoRaw, $DomainNamee ?? null);
    
    // 2. Process Time & Text
    $postTime = date('M j, Y \a\t g:i A', strtotime($post['CreatedAtPosts'] ?? 'now'));
    $postText = '';
    if (!empty($post['PostText']) && $post['PostText'] !== 'NONE') {
        $postText = nl2br(htmlspecialchars($post['PostText']));
    }

    // 3. Process Photos
    $photos = [];
    for ($i = 1; $i <= 4; $i++) {
        $col = ($i === 1) ? 'PostPhoto' : 'PostPhoto' . $i;
        if (!empty($post[$col]) && $post[$col] !== 'NONE' && $post[$col] !== '0') {
            $pUrl = get_img_url($post[$col], $DomainNamee ?? null);
            if ($pUrl) {
                $photos[] = htmlspecialchars($pUrl);
            }
        }
    }

    // 4. Build Photo HTML
    $postPhotoHtml = '';
    if (count($photos) == 1) {
        $postPhotoHtml = '<img src="' . $photos[0] . '" loading="lazy" class="post-img" onerror="this.style.display=\'none\'">';
    } elseif (count($photos) > 1) {
        $postPhotoHtml .= '<div class="carousel-container" style="position: relative;">
            <div class="no-scrollbar" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 8px; border-radius: 16px;" onscroll="updateCarouselIndicator(this)">';
        foreach ($photos as $url) {
            $postPhotoHtml .= '<img src="' . $url . '" loading="lazy" class="post-img" onerror="this.style.display=\'none\'" style="flex: 0 0 100%; scroll-snap-align: center;">';
        }
        $postPhotoHtml .= '</div>
            <div style="position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; z-index: 10;">';
        foreach ($photos as $i => $url) {
            $activeClass = $i === 0 ? 'active' : '';
            $postPhotoHtml .= '<div class="carousel-dot ' . $activeClass . '"></div>';
        }
        $postPhotoHtml .= '</div></div>';
    }

    // 5. Process Actions & Order Button
    $likes = htmlspecialchars($post['PostLikes'] ?? '0');
    $comments = htmlspecialchars($post['Postcomments'] ?? '0');
    $postId = $post['PostId'] ?? $post['PostID'] ?? '0';

    $orderBtnHtml = '';
    if (!empty($post['ProductID']) && $post['ProductID'] != '0') {
        $pPrice = floatval($post['FoodOfferPrice'] ?? 0) > 0 ? floatval($post['FoodOfferPrice']) : floatval($post['Price'] ?? 0);
        $pImgUrl = get_img_url($post['FoodPhoto'] ?? null, $DomainNamee ?? null);
        $foodJson = json_encode([
            'id' => $post['ProductID'],
            'name' => $post['FoodName'] ?? 'Product',
            'price' => $pPrice,
            'oldPrice' => floatval($post['FoodOfferPrice'] ?? 0) > 0 ? floatval($post['Price'] ?? 0) : null,
            'img' => $pImgUrl,
            'desc' => $post['FoodDescription'] ?? $post['FoodComponent'] ?? '',
            'cat_id' => $post['CategoryID'] ?? $_GET['cat'] ?? 0
        ]);
        $orderBtnHtml = '<button class="order-btn" onclick="openProductModal(this)" data-product=\'' . htmlspecialchars($foodJson, ENT_QUOTES, 'UTF-8') . '\'>
            <i class="fa-solid fa-cart-shopping"></i> Order Now - ' . number_format($pPrice, 0) . ' MAD
        </button>';
    }

    // 6. Build the Final Output HTML
    $html = '<div class="post-card">
        <div class="post-header">
            <img src="' . htmlspecialchars($sPhoto ?? 'https://ui-avatars.com/api/?name=Shop&background=222&color=fff') . '" 
                 loading="lazy" class="post-avatar" 
                 onerror="this.src=\'https://ui-avatars.com/api/?name=' . urlencode($shopName) . '&background=random&color=fff\'" 
                 onclick="window.location.href=\'shop.php?id=' . $post['ShopID'] . '\'" style="cursor:pointer;">
            <div class="post-shop-info" onclick="window.location.href=\'shop.php?id=' . $post['ShopID'] . '\'" style="cursor:pointer;">
                <div class="post-shop-name">' . $shopName . '</div>
                <div class="post-time">' . $postTime . '</div>
            </div>
            <button class="icon-btn" style="background:transparent;"><i class="fa-solid fa-ellipsis"></i></button>
        </div>';

    if ($postText) {
        $html .= '<div class="post-text">' . $postText . '</div>';
    }

    $html .= $postPhotoHtml;

    $html .= '<div class="post-actions">
            <div class="action-group">
                <button class="action-btn" onclick="handleLike(this)"><i class="fa-regular fa-heart"></i> ' . $likes . '</button>
                <button class="action-btn" onclick="openCommentModal(' . $postId . ', \'' . addslashes(htmlspecialchars($shopName)) . '\')"><i class="fa-regular fa-comment"></i> ' . $comments . '</button>
                <button class="action-btn"><i class="fa-solid fa-share-nodes"></i></button>
            </div>
            ' . $orderBtnHtml . '
        </div>
    </div>';

    return $html;
}
?>
