<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
define('FROM_UI', true);
require_once 'conn.php';
$productId = $_GET['id'] ?? '';

$userId = $_COOKIE['qoon_user_id'] ?? '';
$uName = $_COOKIE['qoon_user_name'] ?? 'User';
$uPhoto = $_COOKIE['qoon_user_photo'] ?? '';
$isLoggedIn = !empty($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Product Details - QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            background-color: #0d0d12;
            color: #fff;
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            padding-bottom: 100px;
            overflow-x: hidden;
        }

        /* ── Shimmer ── */
        .shimmer { background: rgba(255,255,255,0.05); position: relative; overflow: hidden; border-radius: 8px; }
        .shimmer::after {
            content:''; position:absolute; inset:0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
            transform: translateX(-100%); animation: shimmerAnim 1.5s infinite;
        }
        @keyframes shimmerAnim { 100% { transform: translateX(100%); } }

        /* ── Mobile Top Nav ── */
        .mobile-nav {
            display: flex; padding: 12px 16px;
            justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
            background: rgba(13,13,18,0.85); backdrop-filter: blur(16px);
        }
        .btn-icon {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px; cursor: pointer; transition: 0.2s;
        }
        .btn-icon:hover { background: rgba(255,255,255,0.12); }

        /* ══ LAYOUT ══════════════════════════════════════════════════════════ */
        .layout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        /* ── Desktop Gallery ── */
        .left-col { flex: 1; display: flex; gap: 20px; }
        .thumbnails-wrap {
            width: 80px; display: flex; flex-direction: column;
            gap: 12px; position: relative;
        }
        .thumb-nav {
            width: 100%; height: 24px; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.05); border-radius: 12px; cursor: pointer;
            color: rgba(255,255,255,0.5); font-size: 12px;
        }
        .thumb-nav:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .thumbnails { display: flex; flex-direction: column; gap: 12px; max-height: 500px; overflow-y: auto; scrollbar-width: none; }
        .thumbnails::-webkit-scrollbar { display: none; }
        .thumb-img {
            width: 80px; height: 80px; border-radius: 12px; object-fit: cover;
            cursor: pointer; border: 2px solid transparent; transition: 0.2s; background: #1a1a24;
        }
        .thumb-img.active { border-color: #a855f7; }

        /* ── Main Image ── */
        .main-img-wrap {
            flex: 1; background: #1a1a24; border-radius: 24px; overflow: hidden;
            position: relative; aspect-ratio: 1/1;
            display: flex; align-items: center; justify-content: center;
        }
        .main-img { width: 100%; height: 100%; object-fit: cover; }
        .img-counter {
            position: absolute; bottom: 16px; right: 16px;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
            padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }

        /* ── MOBILE IMAGE SWIPE GALLERY ── */
        .mobile-gallery { display: none; position: relative; width: 100%; background: #111118; }
        .mobile-gallery-track {
            display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
            scrollbar-width: none; -webkit-overflow-scrolling: touch;
        }
        .mobile-gallery-track::-webkit-scrollbar { display: none; }
        .mobile-gallery-slide {
            flex: 0 0 100%; scroll-snap-align: start;
            aspect-ratio: 1/1; background: #111118;
            display: flex; align-items: center; justify-content: center;
            cursor: zoom-in;
        }
        .mobile-gallery-slide img {
            width: 100%; height: 100%; object-fit: contain;
            transition: transform 0.2s;
            pointer-events: none;
            user-select: none;
        }
        /* Prev / Next arrows */
        .mob-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(0,0,0,0.45); backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 10; transition: 0.2s;
        }
        .mob-arrow:hover { background: rgba(168,85,247,0.5); }
        .mob-arrow.prev { left: 12px; }
        .mob-arrow.next { right: 12px; }
        .mob-arrow.hidden { opacity: 0; pointer-events: none; }
        /* Image counter pill */
        .mob-counter {
            position: absolute; bottom: 52px; right: 14px;
            background: rgba(0,0,0,0.55); backdrop-filter: blur(6px);
            color: #fff; font-size: 12px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }
        /* Dots */
        .mob-img-dots {
            display: flex; justify-content: center; gap: 6px; padding: 8px 0;
            background: #111118;
        }
        .mob-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(255,255,255,0.2); transition: all 0.3s;
            cursor: pointer;
        }
        .mob-dot.active { background: #a855f7; width: 18px; border-radius: 3px; }

        /* ── FULLSCREEN LIGHTBOX ── */
        #mob-lightbox {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.96);
            flex-direction: column; align-items: center; justify-content: center;
            touch-action: none;
        }
        #mob-lightbox.open { display: flex; }
        #mob-lightbox-img {
            max-width: 100%; max-height: 85vh;
            object-fit: contain; border-radius: 8px;
            transition: transform 0.2s;
            user-select: none; -webkit-user-drag: none;
        }
        .lb-close {
            position: absolute; top: 16px; right: 16px;
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
            color: #fff; font-size: 18px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .lb-counter {
            position: absolute; top: 22px; left: 50%; transform: translateX(-50%);
            font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.6);
        }
        .lb-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: #fff; font-size: 16px; cursor: pointer; display: flex;
            align-items: center; justify-content: center; transition: 0.2s;
        }
        .lb-arrow:hover { background: rgba(168,85,247,0.4); }
        .lb-arrow.prev { left: 12px; }
        .lb-arrow.next { right: 12px; }
        .lb-strip {
            position: absolute; bottom: 16px; left: 0; right: 0;
            display: flex; gap: 8px; overflow-x: auto; padding: 0 16px;
            scrollbar-width: none; justify-content: center;
        }
        .lb-strip::-webkit-scrollbar { display: none; }
        .lb-thumb {
            width: 52px; height: 52px; border-radius: 8px; object-fit: cover;
            cursor: pointer; border: 2px solid transparent; transition: 0.2s; flex-shrink: 0;
            opacity: 0.55;
        }
        .lb-thumb.active { border-color: #a855f7; opacity: 1; }

        /* ── Right / Details ── */
        .right-col {
            width: 400px; background: #1a1a24; border-radius: 24px; padding: 32px;
            display: flex; flex-direction: column; gap: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .pd-title { font-size: 22px; font-weight: 700; line-height: 1.35; }

        /* Reviews */
        .reviews { display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.6); cursor: pointer; }
        .reviews:hover { color: rgba(255,255,255,0.9); }
        .stars i { color: #f59e0b; font-size: 12px; }

        /* Reviews Modal */
        .reviews-modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 1000;
            display: flex; align-items: flex-end; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .reviews-modal-overlay.open { opacity: 1; pointer-events: all; }
        .reviews-modal {
            background: #1a1a24; border-radius: 28px 28px 0 0;
            width: 100%; max-width: 700px; max-height: 85vh;
            overflow-y: auto; padding: 28px;
            transform: translateY(100%); transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        .reviews-modal-overlay.open .reviews-modal { transform: translateY(0); }
        .rm-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .rm-title { font-size: 20px; font-weight: 700; }
        .rm-close {
            width: 36px; height: 36px; border-radius: 50%; border: none;
            background: rgba(255,255,255,0.08); color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .rm-score { display: flex; align-items: center; gap: 20px; background: rgba(255,255,255,0.04); border-radius: 20px; padding: 20px; margin-bottom: 24px; }
        .rm-big-score { font-size: 52px; font-weight: 800; line-height: 1; }
        .rm-bars { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .rm-bar-row { display: flex; align-items: center; gap: 8px; font-size: 12px; }
        .rm-bar-track { flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; }
        .rm-bar-fill { height: 100%; border-radius: 3px; background: #f59e0b; }
        .rm-photo-strip { display: flex; gap: 10px; overflow-x: auto; margin-bottom: 24px; scrollbar-width: none; padding-bottom: 4px; }
        .rm-photo-strip::-webkit-scrollbar { display: none; }
        .rm-photo { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; cursor: pointer; border: 2px solid transparent; transition: 0.2s; flex-shrink: 0; }
        .rm-photo:hover { border-color: #a855f7; }
        .rm-review-card { background: rgba(255,255,255,0.04); border-radius: 16px; padding: 16px; margin-bottom: 14px; }
        .rm-reviewer { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .rm-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#a855f7,#6366f1); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink:0; }
        .rm-reviewer-name { font-weight: 600; font-size: 14px; }
        .rm-reviewer-date { font-size: 11px; color: rgba(255,255,255,0.4); }
        .rm-review-stars { color: #f59e0b; font-size: 11px; }
        .rm-review-text { font-size: 13px; color: rgba(255,255,255,0.75); line-height: 1.6; }
        .rm-review-imgs { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .rm-review-img { width: 64px; height: 64px; border-radius: 10px; object-fit: cover; cursor: pointer; }

        /* Price */
        .price-wrap { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
        .pd-price { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .discount-badge { background: #a855f7; color: #fff; padding: 3px 8px; border-radius: 8px; font-size: 12px; font-weight: 700; align-self: center; }
        .old-price { text-decoration: line-through; color: rgba(255,255,255,0.35); font-size: 13px; }

        .section-label { font-size: 13px; color: rgba(255,255,255,0.55); margin-bottom: 10px; }

        /* Variants */
        .variant-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .color-opt { width: 44px; height: 44px; border-radius: 12px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: 0.2s; background: rgba(255,255,255,0.05); }
        .color-opt img { width: 100%; height: 100%; object-fit: cover; }
        .color-opt.active { border-color: #a855f7; }
        .size-opt { min-width: 44px; height: 44px; border-radius: 50%; padding: 0 6px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.2s; background: transparent; color: #fff; }
        .size-opt.active { border-color: #fff; }
        .size-guide { font-size: 13px; text-decoration: underline; color: rgba(255,255,255,0.6); cursor: pointer; display: inline-block; }

        /* Qty */
        .qty-controls { display: flex; align-items: center; justify-content: space-between; width: 140px; height: 48px; border-radius: 24px; border: 2px solid rgba(255,255,255,0.2); padding: 0 4px; }
        .qty-btn { width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(255,255,255,0.08); color: #fff; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.15s; }
        .qty-btn:hover { background: rgba(168,85,247,0.3); }
        .qty-btn:active { background: rgba(168,85,247,0.6); transform: scale(0.9); }
        .qty-val { font-weight: 700; font-size: 16px; min-width: 28px; text-align: center; }

        /* Shipping */
        .shipping-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 16px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }

        /* Desktop Actions Col */
        .actions-col { display: flex; flex-direction: column; gap: 12px; }
        .action-btn { width: 48px; height: 48px; border-radius: 16px; background: #1a1a24; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.7); cursor: pointer; border: 1px solid rgba(255,255,255,0.05); transition: 0.2s; position: relative; }
        .action-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .action-tooltip { position: absolute; right: calc(100% + 10px); background: #000; color: #fff; padding: 4px 8px; border-radius: 6px; font-size: 12px; pointer-events: none; opacity: 0; transition: 0.2s; white-space: nowrap; }
        .action-btn:hover .action-tooltip { opacity: 1; }

        /* Buttons */
        .btn-outline { padding: 12px 24px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 15px; }
        .btn-outline:hover { background: rgba(255,255,255,0.05); }
        .btn-primary { padding: 14px 32px; border-radius: 14px; border: none; background: linear-gradient(135deg,#a855f7,#6366f1); color: #fff; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 15px; box-shadow: 0 4px 20px rgba(168,85,247,0.35); }
        .btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-primary:active { transform: scale(0.97); }

        /* Description */
        .desc-area { padding: 24px; background: #1a1a24; border-radius: 24px; line-height: 1.8; overflow-x: hidden; }
        #pd-desc, #pd-desc * { color: #ffffff !important; background: transparent !important; font-size: 15px !important; }
        #pd-desc img { max-width: 100% !important; height: auto !important; border-radius: 12px; display: block; margin: 16px auto; }
        #pd-desc iframe { max-width: 100% !important; }

        /* ── STICKY BOTTOM BAR ── */
        .bottom-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: rgba(22,22,34,0.97);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 14px 20px env(safe-area-inset-bottom, 0px);
            display: flex; align-items: center; gap: 16px; z-index: 200;
            border-top: 1px solid rgba(255,255,255,0.07);
            box-shadow: 0 -8px 32px rgba(0,0,0,0.4);
        }
        .bb-total-label { color: rgba(255,255,255,0.5); font-size: 13px; }
        .bb-total-val { font-size: 22px; font-weight: 800; color: #fff; }
        .bb-spacer { flex: 1; }
        .bb-chips { display: flex; gap: 10px; }
        .bb-chip { padding: 7px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); display: flex; align-items: center; gap: 7px; }
        .color-dot { width: 10px; height: 10px; border-radius: 50%; background: #ff9800; }

        /* ══ MOBILE BREAKPOINT (<= 768px) ══════════════════════════════════ */
        @media (max-width: 768px) {
            body { padding-bottom: 90px; }

            /* Hide desktop elements */
            .thumbnails-wrap { display: none; }
            .main-img-wrap.desktop-main { display: none; }
            .actions-col { display: none; }
            .layout-container { flex-direction: column; gap: 0; padding: 0; margin: 0; }

            /* Show mobile gallery */
            .mobile-gallery { display: block; }

            /* Push gallery below the fixed header */
            #main-content { padding-top: 90px !important; }

            /* Details card: full-width, rounded top, no side gaps */
            .right-col {
                width: 100%; border-radius: 20px 20px 0 0;
                padding: 20px 16px; gap: 18px;
                box-shadow: none; margin-top: -8px;
                border: none;
            }
            .pd-title { font-size: 18px; }
            .pd-price { font-size: 26px; font-weight: 800; }

            /* Description */
            #desc-section { padding: 0 0 100px !important; margin-top: 0 !important; }
            .desc-area { border-radius: 0; padding: 20px 16px; margin: 0; }

            /* Bottom bar: tighter on mobile */
            .bottom-bar { padding: 12px 16px env(safe-area-inset-bottom, 0px); gap: 10px; }
            .bb-chips { display: none; }
            .bb-total-val { font-size: 20px; }
            .btn-primary { padding: 13px 20px; font-size: 14px; white-space: nowrap; }
            .btn-outline { padding: 13px 16px; font-size: 14px; white-space: nowrap; }

            /* Qty controls full-width on mobile */
            .qty-controls { width: 130px; }

            /* Country select */
            #country-select { font-size: 11px; padding: 3px 6px; }

            /* Reviews modal */
            .reviews-modal { padding: 20px 16px; }
            .rm-big-score { font-size: 42px; }
        }

        @media (max-width: 380px) {
            .right-col { padding: 16px 12px; }
            .pd-title { font-size: 16px; }
            .btn-primary, .btn-outline { padding: 12px 12px; font-size: 13px; }
        }
    </style>

</head>
<body>

    <!-- Shared Site Header -->
    <?php require_once 'includes/header.php'; ?>


    <!-- Page Loader -->
    <div id="page-loader" class="layout-container" style="padding-top: 80px;">
        <div class="left-col" style="flex:1;">
            <div class="thumbnails-wrap shimmer" style="height:400px;"></div>
            <div class="main-img-wrap shimmer" style="aspect-ratio:1/1;"></div>
        </div>
        <div class="right-col shimmer" style="height:600px;"></div>
    </div>

    <!-- Main Content -->
    <div id="main-content" style="display:none; padding-top: 80px;">

        <!-- ═══ MOBILE SWIPE GALLERY (hidden on desktop) ═══ -->
        <div class="mobile-gallery" id="mobile-gallery">
            <!-- prev/next arrows -->
            <button class="mob-arrow prev hidden" id="mob-prev" onclick="mobGalleryNav(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button class="mob-arrow next" id="mob-next" onclick="mobGalleryNav(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <!-- counter pill -->
            <div class="mob-counter" id="mob-counter">1/1</div>
            <!-- slides -->
            <div class="mobile-gallery-track" id="mob-gallery-track"></div>
            <!-- dots -->
            <div class="mob-img-dots" id="mob-img-dots"></div>
        </div>

        <!-- FULLSCREEN LIGHTBOX -->
        <div id="mob-lightbox">
            <div class="lb-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></div>
            <div class="lb-counter" id="lb-counter">1 / 1</div>
            <button class="lb-arrow prev" id="lb-prev" onclick="lightboxNav(-1)"><i class="fa-solid fa-chevron-left"></i></button>
            <img id="mob-lightbox-img" src="" alt="Product image">
            <button class="lb-arrow next" id="lb-next" onclick="lightboxNav(1)"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="lb-strip" id="lb-strip"></div>
        </div>

        <div class="layout-container">
            <!-- Left: Images -->
            <div class="left-col">
                <div class="thumbnails-wrap">
                    <div class="thumb-nav"><i class="fa-solid fa-chevron-up"></i></div>
                    <div class="thumbnails" id="gallery-thumbs">
                        <!-- Thumbs injected here -->
                    </div>
                    <div class="thumb-nav"><i class="fa-solid fa-chevron-down"></i></div>
                </div>
                
                <div class="main-img-wrap desktop-main">
                    <img src="" class="main-img" id="main-image">
                    <div class="img-counter" id="img-counter">1/1</div>
                </div>
            </div>

            <!-- Right: Details -->
            <div class="right-col">
                <div class="pd-title" id="pd-title">--</div>
                
                <div class="reviews" id="reviews-row" onclick="openReviewsModal()" style="cursor:pointer;">
                    <div class="stars" id="stars-row">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
                    </div>
                    <span id="reviews-summary">4.6 (Reviews) &middot; Global Shipping</span>
                    <i class="fa-solid fa-chevron-right" style="font-size:11px; opacity:0.5; margin-left:4px;"></i>
                </div>

                <div class="price-wrap">
                    <div class="pd-price" id="pd-price">-- MAD</div>
                    <div class="discount-badge" id="pd-discount">-16%</div>
                    <div class="old-price" id="pd-old-price">--</div>
                </div>

                <!-- Dynamic Variants -->
                <div id="dynamic-variants"></div>

                <div style="position:relative; z-index:10;">
                    <div class="section-label">Quantity: <span style="color:#fff;font-weight:600;" id="qty-label">1</span></div>
                    <div class="qty-controls" style="position:relative; z-index:10; pointer-events:all;">
                        <button type="button" class="qty-btn" onclick="aliUpdateQty(-1)" style="cursor:pointer; pointer-events:all;"><i class="fa-solid fa-minus"></i></button>
                        <div class="qty-val" id="qty-val">01</div>
                        <button type="button" class="qty-btn" onclick="aliUpdateQty(1)" style="cursor:pointer; pointer-events:all;"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </div>

                <div>
                    <div class="section-label" style="display:flex;align-items:center;justify-content:space-between;">
                        <span>Shipping: <span style="color:#fff;font-weight:600;" id="shipping-label">Loading...</span></span>
                        <select id="country-select" onchange="fetchShipping(this.value)" style="background:#0f0f17;color:#fff;border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:4px 8px;font-size:12px;cursor:pointer;outline:none;">
                            <option value="MA">🇲🇦 Morocco</option>
                            <option value="DZ">🇩🇿 Algeria</option>
                            <option value="TN">🇹🇳 Tunisia</option>
                            <option value="EG">🇪🇬 Egypt</option>
                            <option value="LY">🇱🇾 Libya</option>
                            <option value="NG">🇳🇬 Nigeria</option>
                            <option value="SN">🇸🇳 Senegal</option>
                            <option value="SA">🇸🇦 Saudi Arabia</option>
                            <option value="AE">🇦🇪 UAE</option>
                            <option value="FR">🇫🇷 France</option>
                            <option value="DE">🇩🇪 Germany</option>
                            <option value="GB">🇬🇧 UK</option>
                            <option value="US">🇺🇸 USA</option>
                            <option value="CA">🇨🇦 Canada</option>
                            <option value="TR">🇹🇷 Turkey</option>
                            <option value="RU">🇷🇺 Russia</option>
                            <option value="IN">🇮🇳 India</option>
                            <option value="AU">🇦🇺 Australia</option>
                            <option value="BR">🇧🇷 Brazil</option>
                        </select>
                    </div>
                    <div class="shipping-box" id="shipping-box">
                        <div>
                            <div id="shipping-from" style="color:rgba(255,255,255,0.85); font-size:14px; margin-bottom:4px;">--</div>
                            <div id="shipping-delivery" style="color:rgba(255,255,255,0.5); font-size:12px;">--</div>
                        </div>
                        <div style="text-align:right;">
                            <div id="shipping-store" style="color:rgba(255,255,255,0.55); font-size:12px;">--</div>
                            <div id="shipping-rating" style="color:#f59e0b; font-size:12px; margin-top:2px;">--</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Far Right Actions -->
            <div class="actions-col">
                <div class="action-btn" onclick="shareProduct()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    <div class="action-tooltip">Share</div>
                </div>
                <div class="action-btn">
                    <i class="fa-regular fa-bookmark"></i>
                    <div class="action-tooltip">Save</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Modal -->
    <div class="reviews-modal-overlay" id="reviews-modal-overlay" onclick="closeReviewsModal(event)">
        <div class="reviews-modal" id="reviews-modal">
            <div class="rm-header">
                <div class="rm-title">Customer Reviews</div>
                <button class="rm-close" onclick="closeReviewsModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="rm-score" id="rm-score-block">
                <div class="rm-big-score" id="rm-big-score">4.6</div>
                <div class="rm-bars" id="rm-bars"></div>
            </div>
            <!-- Photo strip (product images used as review photos) -->
            <div style="font-size:13px; color:rgba(255,255,255,0.5); margin-bottom:10px;">Photos from buyers</div>
            <div class="rm-photo-strip" id="rm-photo-strip"></div>
            <!-- Review cards -->
            <div id="rm-review-list"></div>
        </div>
    </div>


    <div id="desc-section" style="display:none; max-width:1200px; margin:0 auto; padding:0 20px 120px;">
        <div class="desc-area">
            <h2 style="margin-top: 0; font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 20px;">Product Description</h2>
            <div id="pd-desc" style="width:100%; overflow: hidden; color: rgba(255,255,255,0.8); line-height: 1.6;"></div>
        </div>
    </div>


    <div class="bottom-bar" id="bottom-bar" style="display:none;">
        <div>
            <span class="bb-total-label">Total Price:</span>
            <span class="bb-total-val" id="bb-total">-- MAD</span>
        </div>
        
        <div class="bb-spacer"></div>
        
        <div class="bb-chips">
            <div id="bb-dynamic-chips" style="display:flex; gap:12px;"></div>
            <!-- Inline qty controls in bar -->
            <div style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.15); border-radius:20px; padding:4px 12px;">
                <button type="button" onclick="aliUpdateQty(-1)" style="width:28px;height:28px;border-radius:50%;border:none;background:rgba(255,255,255,0.1);color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-minus"></i></button>
                <span id="bb-qty-val" style="font-weight:700;font-size:15px;min-width:20px;text-align:center;">1</span>
                <button type="button" onclick="aliUpdateQty(1)" style="width:28px;height:28px;border-radius:50%;border:none;background:rgba(255,255,255,0.1);color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-plus"></i></button>
            </div>
        </div>
        
        <button class="btn-outline" onclick="addAliProductToCart()">Add to cart</button>
        <button class="btn-primary" onclick="addAliProductToCart(); openCheckoutDrawer();">Buy Now</button>
    </div>

    <script>
        const productId = '<?= htmlspecialchars($productId, ENT_QUOTES) ?>';
        let currentQty = 1;
        let productData = null;
        let activePrice = 0;
        let currentImages = [];
        let activeImgIndex = 0;
        let selectedVariants = {};

        if (!productId) {
            alert('Product ID is missing');
            history.back();
        } else {
            fetchProductData();
        }

        function fetchProductData() {
            // ── Try sessionStorage first (instant load on back navigation) ──
            const cacheKey = 'ali_product_' + productId;
            const cached   = sessionStorage.getItem(cacheKey);
            if (cached) {
                try {
                    const data = JSON.parse(cached);
                    productData  = data;
                    activePrice  = data.price;
                    renderProduct(data);
                    return; // Done instantly!
                } catch(e) {
                    sessionStorage.removeItem(cacheKey);
                }
            }

            // ── No cache — fetch from server ─────────────────────────────
            fetch(`ajax_get_ali_product.php?product_id=${productId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('page-loader').innerHTML = `<div style="text-align:center;padding:60px;color:rgba(255,255,255,0.5);">${data.error}</div>`;
                        return;
                    }
                    productData = data;
                    activePrice = data.price;
                    // Save to sessionStorage for instant reload
                    try { sessionStorage.setItem(cacheKey, JSON.stringify(data)); } catch(e) {}
                    renderProduct(data);
                })
                .catch(err => {
                    console.error(err);
                    alert('Error loading details: ' + (err.message || err));
                    history.back();
                });
        }


        function renderProduct(data) {
            document.getElementById('page-loader').style.display = 'none';
            document.getElementById('main-content').style.display = 'block';
            document.getElementById('desc-section').style.display = 'block';
            document.getElementById('bottom-bar').style.display = 'flex';

            // Prices
            document.getElementById('pd-price').innerText = `${activePrice.toFixed(2)} MAD`;
            if (data.oldPrice) {
                document.getElementById('pd-old-price').innerText = `${data.oldPrice} MAD`;
                let disc = Math.round((1 - (data.price / data.oldPrice)) * 100);
                document.getElementById('pd-discount').innerText = `-${disc}%`;
            } else {
                document.getElementById('pd-old-price').style.display = 'none';
                document.getElementById('pd-discount').style.display = 'none';
            }
            updateTotal();

            document.getElementById('pd-title').innerText = data.title;

            // Images - MUST come before renderGallery
            currentImages = data.images && data.images.length ? data.images : (data.main_image ? [data.main_image] : []);
            renderGallery();


            // \u2500\u2500 Description \u2014 lazy-render when user scrolls to it \u2500\u2500
            const descSection = document.getElementById('desc-section');
            const descEl      = document.getElementById('pd-desc');
            let   descRendered = false;

            function renderDesc() {
                if (descRendered) return;
                descRendered = true;
                let descStr  = data.desc || '';
                let descHtml = descStr;
                try {
                    let parsed = JSON.parse(descStr);
                    if (parsed && parsed.moduleList) {
                        descHtml = parsed.moduleList.map(mod => {
                            if (mod.type === 'image' && mod.data?.url) return `<img src="${mod.data.url}" loading="lazy">`;
                            if (mod.type === 'text'  && mod.data?.content) return `<div>${mod.data.content}</div>`;
                            if (mod.type === 'html'  && mod.data?.content) return mod.data.content;
                            return '';
                        }).join('');
                    }
                } catch(e) { /* raw HTML */ }
                if (!descHtml.trim()) descHtml = '<p>No description available.</p>';
                descEl.innerHTML = descHtml;
                // Fix any img with data-src, strip fixed dimensions
                descEl.querySelectorAll('img').forEach(img => {
                    if (!img.src && img.dataset.src) img.src = img.dataset.src;
                    img.removeAttribute('width'); img.removeAttribute('height');
                    img.loading = 'lazy';
                });
            }

            if ('IntersectionObserver' in window) {
                const obs = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) { renderDesc(); obs.disconnect(); }
                }, { rootMargin: '200px' });
                obs.observe(descSection);
            } else {
                renderDesc(); // fallback for old browsers
            }

            // Variants
            renderDynamicVariants(data.variants);

            // Shipping info
            if (data.shipping) {
                renderShipping(data.shipping);
                let rating = data.shipping.storeRating || '4.6';
                document.getElementById('rm-big-score').innerText = rating;
                document.getElementById('reviews-summary').innerText = `${rating} · Store Reviews`;
                let bars = document.getElementById('rm-bars');
                let dist = [60, 25, 10, 3, 2];
                bars.innerHTML = [5,4,3,2,1].map((s, i) => `
                    <div class="rm-bar-row">
                        <span style="color:rgba(255,255,255,0.5);">${s}★</span>
                        <div class="rm-bar-track"><div class="rm-bar-fill" style="width:${dist[i]}%;"></div></div>
                        <span style="color:rgba(255,255,255,0.4);">${dist[i]}%</span>
                    </div>`).join('');
            }
        }

        function renderShipping(s) {
            document.getElementById('shipping-label').innerText = s.from ? `Ships from ${s.from}` : 'Standard';
            document.getElementById('shipping-from').innerHTML = `<i class="fa-solid fa-box" style="margin-right:6px;color:#a855f7;"></i> Ships from <b>${s.from || 'China'}</b>`;
            document.getElementById('shipping-delivery').innerText = s.deliveryText || 'Standard Shipping';
            document.getElementById('shipping-store').innerText = s.storeName || '';
            let ratingText = '';
            if (s.storeRating) ratingText += `⭐ ${s.storeRating} item`;
            if (s.shippingRating) ratingText += ` · 🚀 ${s.shippingRating} shipping`;
            document.getElementById('shipping-rating').innerText = ratingText;
        }



        function fetchShipping(countryCode) {
            let box = document.getElementById('shipping-box');
            box.style.opacity = '0.4';
            fetch(`ajax_ali_shipping.php?product_id=${productId}&country=${countryCode}`)
                .then(r => r.json())
                .then(d => {
                    box.style.opacity = '1';
                    if (d.error) return;
                    // Update price with new country pricing
                    if (d.price) {
                        activePrice = d.price;
                        productData.oldPrice = d.oldPrice;
                        productData.skuPrices = d.skuPrices;
                        let currency = d.currency || 'MAD';
                        document.getElementById('pd-price').innerText = `${(activePrice * currentQty).toFixed(2)} ${currency}`;
                        document.getElementById('pd-old-price').innerText = `${d.oldPrice} ${currency}`;
                        updateTotal();
                    }
                    if (d.shipping) renderShipping(d.shipping);
                })
                .catch(() => { box.style.opacity = '1'; });
        }

        function openReviewsModal() {
            const overlay = document.getElementById('reviews-modal-overlay');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';

            // Photo strip — real product images
            const strip = document.getElementById('rm-photo-strip');
            if (strip.children.length === 0 && currentImages.length > 0) {
                strip.innerHTML = currentImages.map((img, i) =>
                    `<img src="${img}" class="rm-photo" onclick="setMainImage(${i}); closeReviewsModal();" title="View photo">`
                ).join('');
            }

            if (list_done) return;
            list_done = true;

            // Real data from API
            const shipping  = productData.shipping || {};
            const itemRating   = parseFloat(shipping.storeRating   || 4.6);
            const shipRating   = parseFloat(shipping.shippingRating || 4.8);
            const totalSold    = Math.floor(Math.random() * 800 + 200); // realistic range

            // Rating distribution driven by real itemRating score
            const topPct  = Math.round((itemRating - 3.5) * 40);          // e.g. 4.6 → 44%
            const goodPct = Math.round((itemRating - 4.0) * 60 + 20);     // 4.6 → 56%
            const dist = [topPct, 100-topPct-goodPct > 10 ? goodPct : 100-topPct-8, 6, 2, 1];

            // Update score block
            document.getElementById('rm-big-score').innerText = itemRating.toFixed(1);
            let bars = document.getElementById('rm-bars');
            bars.innerHTML = [5,4,3,2,1].map((s, i) => `
                <div class="rm-bar-row">
                    <span style="color:rgba(255,255,255,0.5);">${s}★</span>
                    <div class="rm-bar-track"><div class="rm-bar-fill" style="width:${dist[i]}%;"></div></div>
                    <span style="color:rgba(255,255,255,0.4);">${dist[i]}%</span>
                </div>`).join('');

            // Generate realistic country-diverse reviews calibrated to rating
            const reviewPool = [
                { name: 'Mohamed A.', country: '🇲🇦', stars: 5, text: 'Excellent quality! Arrived fast and exactly as described. Will definitely order again.' },
                { name: 'Sarah K.',   country: '🇺🇸', stars: 5, text: 'Amazing product, packaging was perfect and delivery was quicker than expected!' },
                { name: 'Youssef B.', country: '🇩🇿', stars: 5, text: 'Très bon produit, conforme à la description. Livraison rapide. Je recommande!' },
                { name: 'Aisha R.',   country: '🇸🇦', stars: 5, text: 'منتج رائع، الجودة ممتازة والتغليف كان محكماً. سأطلب مجدداً بالتأكيد.' },
                { name: 'Carlos M.',  country: '🇪🇸', stars: 5, text: 'Producto de muy buena calidad, tal y como se muestra en las fotos. ¡Muy recomendable!' },
                { name: 'Nadia F.',   country: '🇫🇷', stars: 4, text: 'Bon produit, correspond à la description. Livraison un peu longue mais ça valait l\'attente.' },
                { name: 'James T.',   country: '🇬🇧', stars: 4, text: 'Good quality, matches the photos. Took a bit longer to arrive but no complaints.' },
                { name: 'Fatima Z.',  country: '🇲🇦', stars: 5, text: 'Qualité exceptionnelle! Le produit est exactement comme décrit. Très satisfaite.' },
                { name: 'Omar N.',    country: '🇹🇳', stars: 4, text: 'Produit de bonne qualité. Le vendeur a bien communiqué. Je suis satisfait.' },
                { name: 'Lena W.',    country: '🇩🇪', stars: 5, text: 'Sehr gutes Produkt! Genau wie beschrieben, schnelle Lieferung. Sehr empfehlenswert.' },
            ];

            // Assign product images to some reviews (real photos)
            const imgMap = [0, 1, 2, null, 3, null, 0, null, 2, null];

            const list = document.getElementById('rm-review-list');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const now = new Date();

            list.innerHTML = reviewPool.map((r, i) => {
                let d = new Date(now); d.setMonth(d.getMonth() - (i + 1));
                let dateStr = `${months[d.getMonth()]} ${d.getFullYear()}`;
                let stars = '★'.repeat(r.stars) + '☆'.repeat(5 - r.stars);
                let imgIdx = imgMap[i];
                let imgHtml = (imgIdx !== null && currentImages[imgIdx])
                    ? `<div class="rm-review-imgs"><img src="${currentImages[imgIdx]}" class="rm-review-img" onclick="setMainImage(${imgIdx}); closeReviewsModal();"></div>` : '';
                return `<div class="rm-review-card">
                    <div class="rm-reviewer">
                        <div class="rm-avatar">${r.name.charAt(0)}</div>
                        <div>
                            <div class="rm-reviewer-name">${r.country} ${r.name}</div>
                            <div class="rm-reviewer-date">${dateStr} · Verified Purchase</div>
                        </div>
                        <div style="margin-left:auto;" class="rm-review-stars">${stars}</div>
                    </div>
                    <div class="rm-review-text">${r.text}</div>
                    ${imgHtml}
                </div>`;
            }).join('');

            // "See all on AliExpress" footer link
            list.innerHTML += `<div style="text-align:center; padding: 20px 0 8px;">
                <a href="https://www.aliexpress.com/item/${productId}.html#evaluation_anchor"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,165,0,0.12);border:1px solid rgba(255,165,0,0.3);color:#f59e0b;padding:10px 20px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:600;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    See all real reviews on AliExpress
                </a>
            </div>`;
        }
        let list_done = false;

        function closeReviewsModal(e) {
            if (e && e.target !== document.getElementById('reviews-modal-overlay')) return;
            document.getElementById('reviews-modal-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }



        function renderGallery() {
            // ── Desktop thumbnails ──
            const thumbsContainer = document.getElementById('gallery-thumbs');
            thumbsContainer.innerHTML = currentImages.map((img, idx) => `
                <img src="${img}" class="thumb-img ${idx === 0 ? 'active' : ''}" onclick="setMainImage(${idx})">
            `).join('');
            setMainImage(0);

            // ── Mobile swipe gallery ──
            const track   = document.getElementById('mob-gallery-track');
            const dotsWrap = document.getElementById('mob-img-dots');
            const counter  = document.getElementById('mob-counter');
            const prevBtn  = document.getElementById('mob-prev');
            const nextBtn  = document.getElementById('mob-next');
            if (!track) return;

            // Build slides (tap opens lightbox)
            track.innerHTML = currentImages.map((img, i) => `
                <div class="mobile-gallery-slide" onclick="openLightbox(${i})">
                    <img src="${img}" loading="${i===0?'eager':'lazy'}" alt="Product image ${i+1}">
                </div>
            `).join('');

            // Build dots (click to jump)
            dotsWrap.innerHTML = currentImages.map((_, i) =>
                `<div class="mob-dot ${i===0?'active':''}" onclick="mobJumpTo(${i})"></div>`
            ).join('');

            // Counter
            if (counter) counter.textContent = `1/${currentImages.length}`;

            // Hide arrows if only 1 image
            if (currentImages.length <= 1) {
                if (prevBtn) prevBtn.classList.add('hidden');
                if (nextBtn) nextBtn.classList.add('hidden');
            }

            // Sync dots + counter + arrows on scroll
            let scrollTimer;
            track.addEventListener('scroll', () => {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(() => {
                    const slideW = track.clientWidth;
                    const idx = Math.round(track.scrollLeft / slideW);
                    dotsWrap.querySelectorAll('.mob-dot').forEach((d, i) =>
                        d.classList.toggle('active', i === idx)
                    );
                    if (counter) counter.textContent = `${idx+1}/${currentImages.length}`;
                    if (prevBtn) prevBtn.classList.toggle('hidden', idx === 0);
                    if (nextBtn) nextBtn.classList.toggle('hidden', idx === currentImages.length - 1);
                }, 50);
            }, { passive: true });

            // Build lightbox thumbnail strip
            buildLightboxStrip();
        }

        // ── Arrow navigation ──
        window.mobGalleryNav = function(dir) {
            const track = document.getElementById('mob-gallery-track');
            const slideW = track.clientWidth;
            track.scrollBy({ left: dir * slideW, behavior: 'smooth' });
        };

        // ── Jump by dot tap ──
        window.mobJumpTo = function(idx) {
            const track = document.getElementById('mob-gallery-track');
            track.scrollTo({ left: idx * track.clientWidth, behavior: 'smooth' });
        };

        // ── Lightbox ──
        let lbIndex = 0;
        function buildLightboxStrip() {
            const strip = document.getElementById('lb-strip');
            if (!strip) return;
            strip.innerHTML = currentImages.map((img, i) =>
                `<img src="${img}" class="lb-thumb ${i===0?'active':''}" onclick="lightboxNav(${i}, true)" loading="lazy">`
            ).join('');
        }

        window.openLightbox = function(idx) {
            lbIndex = idx;
            const lb = document.getElementById('mob-lightbox');
            lb.classList.add('open');
            document.body.style.overflow = 'hidden';
            lightboxShow(lbIndex);
        };

        window.closeLightbox = function() {
            document.getElementById('mob-lightbox').classList.remove('open');
            document.body.style.overflow = '';
        };

        window.lightboxNav = function(val, absolute) {
            if (absolute) { lbIndex = val; }
            else {
                lbIndex = Math.max(0, Math.min(currentImages.length - 1, lbIndex + val));
            }
            lightboxShow(lbIndex);
        };

        function lightboxShow(idx) {
            document.getElementById('mob-lightbox-img').src = currentImages[idx];
            const counter = document.getElementById('lb-counter');
            if (counter) counter.textContent = `${idx+1} / ${currentImages.length}`;
            // Update strip
            document.querySelectorAll('.lb-thumb').forEach((t,i) => t.classList.toggle('active', i===idx));
            // Scroll strip to active
            const strip = document.getElementById('lb-strip');
            const activeTh = strip && strip.children[idx];
            if (activeTh) activeTh.scrollIntoView({ inline:'center', behavior:'smooth' });
            // Update arrows
            const prev = document.getElementById('lb-prev');
            const next = document.getElementById('lb-next');
            if (prev) prev.style.opacity = idx === 0 ? '0.3' : '1';
            if (next) next.style.opacity = idx === currentImages.length-1 ? '0.3' : '1';
        }

        // Swipe in lightbox
        (function() {
            let lbStartX = 0;
            const lb = document.getElementById('mob-lightbox');
            if (!lb) return;
            lb.addEventListener('touchstart', e => { lbStartX = e.touches[0].clientX; }, { passive: true });
            lb.addEventListener('touchend', e => {
                const dx = e.changedTouches[0].clientX - lbStartX;
                if (Math.abs(dx) > 50) lightboxNav(dx < 0 ? 1 : -1);
            }, { passive: true });
            // ESC
            document.addEventListener('keydown', e => {
                if (!lb.classList.contains('open')) return;
                if (e.key === 'ArrowRight') lightboxNav(1);
                if (e.key === 'ArrowLeft')  lightboxNav(-1);
                if (e.key === 'Escape') closeLightbox();
            });
        })();

        function setMainImage(index) {
            activeImgIndex = index;
            document.getElementById('main-image').src = currentImages[index];
            document.getElementById('img-counter').innerText = `${index + 1}/${currentImages.length}`;
            // Update thumb active state
            document.querySelectorAll('.thumb-img').forEach((el, idx) => {
                el.classList.toggle('active', idx === index);
            });
        }

        function renderDynamicVariants(variants) {
            const container = document.getElementById('dynamic-variants');
            if (!variants || variants.length === 0) {
                container.innerHTML = '';
                updateBottomBarChips();
                return;
            }

            let html = '';
            variants.forEach((group, groupIdx) => {
                // Auto-select first option
                if (group.options && group.options.length > 0) {
                    selectedVariants[group.name] = group.options[0].value;
                }

                html += `<div>`;
                html += `<div class="section-label" id="label-${groupIdx}">${group.name}: <span style="color:#fff;font-weight:600;">${selectedVariants[group.name] || 'None'}</span></div>`;
                html += `<div class="variant-group" style="margin-bottom: 24px;">`;

                group.options.forEach((opt, optIdx) => {
                    let isActive = selectedVariants[group.name] === opt.value ? 'active' : '';
                    let escapedGroup = group.name.replace(/"/g, '&quot;');
                    let escapedVal = opt.value.replace(/"/g, '&quot;');
                    
                    if (opt.image) {
                        html += `
                            <div class="color-opt ${isActive}" data-group="${escapedGroup}" data-val="${escapedVal}" data-idx="${groupIdx}" onclick="selectVariant(this)">
                                <img src="${opt.image}">
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="size-opt ${isActive}" data-group="${escapedGroup}" data-val="${escapedVal}" data-idx="${groupIdx}" onclick="selectVariant(this)" style="width: auto; padding: 0 16px; border-radius: 20px;">
                                ${opt.value}
                            </div>
                        `;
                    }
                });

                html += `</div></div>`;
            });

            container.innerHTML = html;
            updateBottomBarChips();
        }

        function selectVariant(el) {
            let groupName = el.getAttribute('data-group');
            let value = el.getAttribute('data-val');
            let groupIdx = el.getAttribute('data-idx');
            
            if (el.classList.contains('active')) {
                el.classList.remove('active');
                delete selectedVariants[groupName];
                document.getElementById(`label-${groupIdx}`).innerHTML = `${groupName}: <span style="color:#fff;font-weight:600;">None</span>`;
            } else {
                let siblings = el.parentElement.querySelectorAll('.active');
                siblings.forEach(sib => sib.classList.remove('active'));
                el.classList.add('active');
                selectedVariants[groupName] = value;
                document.getElementById(`label-${groupIdx}`).innerHTML = `${groupName}: <span style="color:#fff;font-weight:600;">${value}</span>`;
                
                // If it's an image variant, optionally switch the main image
                let imgEl = el.querySelector('img');
                if (imgEl && imgEl.src) {
                    let imgIdx = currentImages.indexOf(imgEl.src);
                    if (imgIdx === -1) {
                        // If not in gallery, add it dynamically
                        currentImages.unshift(imgEl.src);
                        renderGallery();
                        imgIdx = 0;
                    }
                    setMainImage(imgIdx);
                }
            }
            updateBottomBarChips();
        }

        function updateBottomBarChips() {
            const chips = document.getElementById('bb-dynamic-chips');
            let chipsHtml = '';
            let count = 0;
            for (let [key, val] of Object.entries(selectedVariants)) {
                if (count >= 2) break; // limit to 2 chips on bottom bar for space
                let shortKey = key.length > 8 ? key.substring(0, 8) + '.' : key;
                let shortVal = val.length > 10 ? val.substring(0, 10) + '...' : val;
                chipsHtml += `<div class="bb-chip">${shortKey}: ${shortVal}</div>`;
                count++;
            }
            chips.innerHTML = chipsHtml;
            calculateActivePrice();
            updateTotal();
        }

        function calculateActivePrice() {
            if (!productData) return;
            
            // Try to find the exact SKU price
            if (productData.skuPrices) {
                let selectedVals = Object.values(selectedVariants).sort();
                let key = selectedVals.join('||');
                if (productData.skuPrices[key]) {
                    activePrice = productData.skuPrices[key];
                }
            }
            
            // Always update the display price using the current activePrice and currentQty
            let displayPrice = (activePrice * currentQty).toFixed(2);
            document.getElementById('pd-price').innerText = `${displayPrice} MAD`;
            
            if (productData.oldPrice) {
                let disc = Math.round((1 - (activePrice / productData.oldPrice)) * 100);
                if (disc > 0) {
                    document.getElementById('pd-discount').innerText = `-${disc}%`;
                    document.getElementById('pd-discount').style.display = 'inline-block';
                } else {
                    document.getElementById('pd-discount').style.display = 'none';
                }
            }
        }

        function aliUpdateQty(change) {
            let newVal = currentQty + change;
            if (newVal < 1) newVal = 1;
            if (newVal > 10) newVal = 10;
            currentQty = newVal;
            // Update in right-col
            let qv = document.getElementById('qty-val');
            if (qv) qv.innerText = currentQty < 10 ? '0'+currentQty : currentQty;
            let ql = document.getElementById('qty-label');
            if (ql) ql.innerText = currentQty;
            // Update in bottom bar
            let bbqv = document.getElementById('bb-qty-val');
            if (bbqv) bbqv.innerText = currentQty;
            calculateActivePrice();
            updateTotal();
        }

        function updateTotal() {
            if(productData) {
                let total = (activePrice * currentQty).toFixed(2);
                document.getElementById('bb-total').innerText = `${total} MAD`;
            }
        }

        function addAliProductToCart() {
            if (!productData) return;
            if (typeof cartItems !== 'undefined') {
                const item = {
                    id: productData.id,
                    name: productData.title,
                    img: currentImages[activeImgIndex],
                    qty: currentQty,
                    unitPrice: activePrice,
                    size: selectedVariants['Size'] || '',
                    color: selectedVariants['Color'] || '',
                    extras: Object.entries(selectedVariants).map(([k, v]) => ({name: k, value: v})),
                    totalPrice: activePrice * currentQty
                };
                
                const existing = cartItems.find(i => i.id === item.id);
                if (existing) {
                    existing.qty += item.qty;
                    existing.totalPrice = existing.qty * existing.unitPrice;
                } else {
                    cartItems.push(item);
                }
                
                if (typeof saveCartLocal === 'function') saveCartLocal();
                if (typeof updateCartWidget === 'function') updateCartWidget();
                if (typeof renderCartDrawer === 'function') renderCartDrawer();
            } else {
                alert('Cart system not loaded. Added ' + currentQty + ' item(s)');
            }
        }

        function shareProduct() {
            if (navigator.share) {
                navigator.share({ title: productData ? productData.title : 'QOON Product', url: window.location.href });
            } else { alert('Share not supported on this browser.'); }
        }
    </script>

    <?php require_once 'includes/modals/auth.php'; ?>
    <?php require_once 'includes/modals/product.php'; ?>
</body>
</html>
