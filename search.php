<?php
define('FROM_UI', true);
define('OFFLINE_MODE', false);
require_once 'conn.php';
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
    <title>Search · QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        /* Specific search page styles overrides to merge with home UI */
        .search-page-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .prompt-container {
            position: relative;
            z-index: 10;
        }

        .clear-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
            cursor: pointer;
            display: none;
            padding: 10px;
        }

        .tab-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 20px 0 12px;
            scrollbar-width: none;
        }

        .tab-pills::-webkit-scrollbar {
            display: none;
        }

        .tab-pill {
            padding: 8px 20px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, .6);
            cursor: pointer;
            white-space: nowrap;
            transition: all .2s;
        }

        .tab-pill.active,
        .tab-pill:hover {
            background: rgba(255, 255, 255, .15);
            border-color: rgba(255, 255, 255, .3);
            color: #fff;
        }

        /* Results grids based on home feed cards */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 28px 0 14px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .section-count {
            font-size: 13px;
            color: rgba(255, 255, 255, .4);
        }

        .results {
            display: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, .5);
        }

        .loader {
            display: none;
            text-align: center;
            padding: 40px;
            color: #fff;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.5);
        }

        .shop-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            margin-bottom: 10px;
            cursor: pointer;
            transition: all .2s;
        }

        .shop-card:hover {
            background: rgba(255, 255, 255, .1);
        }

        .shop-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(255, 255, 255, .05);
        }

        .shop-info {
            flex: 1;
        }

        .shop-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .shop-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, .5);
            margin-top: 4px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .product-card {
            border-radius: 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            overflow: hidden;
            cursor: pointer;
            transition: all .2s;
        }

        .product-card:hover {
            background: rgba(255, 255, 255, .1);
            transform: translateY(-2px);
        }

        .product-img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }

        .product-body {
            padding: 12px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .product-price {
            font-size: 15px;
            font-weight: 700;
            color: #f50057;
            margin-top: 6px;
        }

        .product-shop {
            font-size: 12px;
            color: rgba(255, 255, 255, .4);
            margin-top: 4px;
        }

        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }

        .reel-card {
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            aspect-ratio: 9/16;
            background: rgba(255, 255, 255, .05);
        }

        .reel-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent 50%, rgba(0, 0, 0, .8));
            display: flex;
            align-items: flex-end;
            padding: 10px;
        }

        .post-card {
            display: flex;
            gap: 14px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            margin-bottom: 12px;
            cursor: pointer;
        }

        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-body {
            flex: 1;
        }

        .post-shop {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .post-text {
            font-size: 14px;
            margin-top: 6px;
            color: rgba(255, 255, 255, .7);
        }

        .post-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
        }

        mark {
            background: rgba(245, 0, 87, .3);
            color: #fff;
            border-radius: 4px;
            padding: 0 2px;
        }

        .back-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            cursor: pointer;
            color: #fff;
        }

        /* ─── QOON (UNIVERSE) 3D GALAXY THEME ─── */
        body {
            background-color: #010008 !important;
            /* Pitch black space */
            margin: 0;
            overflow-x: hidden;
        }

        #space {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -3;
            pointer-events: none;
            background: radial-gradient(circle at center, #0a001a 0%, #010008 100%);
        }

        .search-page-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Interactive Glowing Cards */
        .result-card,
        .shop-card,
        .product-card,
        .reel-card {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5) !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        }

        .result-card:hover,
        .shop-card:hover,
        .product-card:hover,
        .reel-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(120, 0, 255, 0.3), 0 0 20px rgba(0, 212, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            background: rgba(255, 255, 255, 0.07) !important;
        }

        /* ─── MOBILE RESPONSIVE ─── */
        @media (max-width: 600px) {
            /* Grid: 2 columns on mobile */
            .products-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }
            .reels-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            /* Search bar full-width */
            .prompt-container {
                width: 100% !important;
                border-radius: 20px !important;
            }

            /* Content padding */
            .content-wrapper { padding: 0 !important; }
            .search-page-wrapper { padding: 0 12px !important; }

            /* Logo smaller */
            .brand-logo-container img { height: 44px !important; }

            /* Tabs */
            .tab-pill { font-size: 12px !important; padding: 6px 14px !important; }

            /* Product card text */
            .product-name  { font-size: 12px !important; }
            .product-price { font-size: 13px !important; }
            .product-body  { padding: 8px !important; }

            /* Section header spacing */
            .section-header { margin: 18px 0 10px !important; }

            /* Back button bigger touch target */
            .search-back-btn { padding: 10px 14px; }

            /* ── Force camera button visible on mobile ──
               main.css sets .icon-btn { display:none } on mobile — override it */
            #imgSearchBtn {
                display: flex !important;
                flex-shrink: 0;
                width: 44px !important;
                height: 44px !important;
                border-radius: 50% !important;
                background: linear-gradient(135deg, #a855f7, #6366f1) !important;
                color: #fff !important;
                align-items: center;
                justify-content: center;
                font-size: 17px !important;
                box-shadow: 0 4px 16px rgba(168,85,247,0.45);
                border: none;
                cursor: pointer;
            }

            /* ── Modal: bottom sheet on mobile ── */
            #img-search-overlay {
                align-items: flex-end !important;
                padding: 0 !important;
            }
            #img-search-modal {
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 28px 28px 0 0 !important;
                padding: 24px 20px max(20px, env(safe-area-inset-bottom)) !important;
                max-height: 90vh;
                overflow-y: auto;
                animation: sheetUp 0.35s cubic-bezier(0.34,1.2,0.64,1) !important;
            }
            @keyframes sheetUp {
                from { transform: translateY(100%); opacity: 0.7; }
                to   { transform: translateY(0);    opacity: 1; }
            }
            /* Drag handle bar on modal */
            #img-search-modal::after {
                content: '';
                display: block;
                width: 40px; height: 4px;
                background: rgba(255,255,255,0.2);
                border-radius: 2px;
                margin: 0 auto 20px;
                order: -1;
                position: absolute;
                top: 10px; left: 50%; transform: translateX(-50%);
            }
            /* Smaller drop zone padding on mobile */
            #img-drop-area { padding: 24px 16px !important; }
            #img-preview { max-height: 180px !important; }

            /* Bigger touch targets for pill buttons */
            .img-pill-btn { padding: 13px 16px !important; font-size: 14px !important; }
            #img-search-go { padding: 15px !important; font-size: 16px !important; }
        }

        /* Very small phones */
        @media (max-width: 360px) {
            .products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important; }
            .product-name  { font-size: 11px !important; }
        }
    </style>
</head>

<body>

    <!-- True 3D Galaxy Canvas -->
    <canvas id="space"></canvas>

    <?php require_once 'includes/header.php'; ?>

    <div class="content-wrapper">



        <style>
            .search-page-wrapper {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                /* Use dvh (dynamic) so mobile keyboard doesn't break layout */
                min-height: calc(100dvh - 64px);
                height: calc(100dvh - 64px);
                width: 100%;
                padding: 0 20px;
                box-sizing: border-box;
                overflow: hidden;
            }
            .search-wrapper {
                transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
                max-width: 860px;
            }
            body.is-searching .search-page-wrapper {
                justify-content: flex-start;
                padding-top: 16px;
                height: auto;
                min-height: unset;
                overflow: visible;
            }
            .brand-logo-container {
                transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
                margin-bottom: 20px;
                transform-origin: bottom center;
                pointer-events: none;
            }
            body.is-searching .brand-logo-container {
                transform: scale(0.62);
                margin-bottom: -4px;
            }
            .search-back-btn {
                position: absolute;
                top: 14px; left: 14px;
                z-index: 20; cursor: pointer;
                padding: 8px; color: #fff;
            }
            #promptBox {
                width: 100% !important;
                max-width: 860px !important;
                border-radius: 24px !important;
            }
            @media (max-width: 600px) {
                .search-page-wrapper {
                    padding: 0 12px;
                    height: 100dvh !important;
                    min-height: 100dvh;
                    padding-top: 64px; /* header height */
                    justify-content: center;
                    box-sizing: border-box;
                }
                body.is-searching .search-page-wrapper {
                    height: auto !important;
                    min-height: unset;
                    padding-top: 16px;
                    justify-content: flex-start;
                }
                #promptBox { max-width: 100% !important; }
                .brand-logo-container { margin-bottom: 12px; }
                .brand-logo-container img { height: 44px !important; }
                body.is-searching .brand-logo-container {
                    transform: scale(0.55);
                    margin-bottom: -8px;
                }
                .results { padding: 0 4px 120px; }
            }
        </style>

        <main class="search-page-wrapper">

            <!-- Back btn: absolute so it never affects flex centering -->
            <div class="search-back-btn" onclick="window.location.href='index.php'">
                <i class="fa-solid fa-arrow-left" style="font-size:22px;"></i>
            </div>

            <!-- Logo + Search Centered -->
            <div class="search-wrapper">

                <div class="brand-logo-container">
                    <img src="logo_qoon_white.png" alt="QOON Logo" class="brand-logo"
                        style="height: 65px; width: auto; object-fit: contain; filter: drop-shadow(0 0 15px rgba(255,255,255,0.4));">
                </div>

                <div class="prompt-container" id="promptBox"
                    style="border-radius: 24px; padding-right: 12px;">
                    <button class="icon-btn" style="margin-left: 4px;"><i
                            class="fa-solid fa-magnifying-glass"></i></button>

                    <input type="text" class="prompt-input" id="searchInput"
                        placeholder="Search shops, products, reels..." autocomplete="off" autofocus>

                    <!-- Camera / Image Search Button -->
                    <button class="icon-btn" id="imgSearchBtn" onclick="openImageSearch()" title="Search by image"
                        style="color:#a855f7; flex-shrink:0;">
                        <i class="fa-solid fa-camera"></i>
                    </button>

                    <button class="clear-btn" id="clearBtn" onclick="clearSearch()"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
            </div>

            <div class="empty-state" id="emptyState" style="display: none;">
                <!-- Emptied per user request for a cleaner look -->
            </div>

            <div class="loader" id="loader">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:32px; color:#f50057;"></i>
            </div>


            <!-- ══ IMAGE SEARCH MODAL ══════════════════════════════════════════ -->
            <style>
                /* ── Overlay ── */
                #img-search-overlay {
                    display: none;
                    position: fixed; inset: 0; z-index: 3000;
                    align-items: center; justify-content: center;
                    background: rgba(0,0,0,0.5);
                    backdrop-filter: blur(20px) saturate(180%);
                    -webkit-backdrop-filter: blur(20px) saturate(180%);
                    animation: overlayIn 0.3s ease;
                }
                @keyframes overlayIn { from { opacity:0; } to { opacity:1; } }

                /* ── Liquid Glass Card ── */
                #img-search-modal {
                    position: relative;
                    width: 92%; max-width: 460px;
                    border-radius: 32px;
                    padding: 28px 24px 24px;
                    background: linear-gradient(135deg,
                        rgba(255,255,255,0.13) 0%,
                        rgba(255,255,255,0.06) 50%,
                        rgba(168,85,247,0.08) 100%);
                    border: 1px solid rgba(255,255,255,0.18);
                    box-shadow:
                        0 40px 100px rgba(0,0,0,0.55),
                        0 0 0 0.5px rgba(255,255,255,0.1) inset,
                        0 1px 0 rgba(255,255,255,0.25) inset;
                    backdrop-filter: blur(40px) saturate(200%);
                    -webkit-backdrop-filter: blur(40px) saturate(200%);
                    animation: modalSlideIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
                    overflow: hidden;
                }
                /* Glass glare streak */
                #img-search-modal::before {
                    content: '';
                    position: absolute; top: 0; left: -60%;
                    width: 50%; height: 100%;
                    background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.08) 50%, transparent 70%);
                    pointer-events: none;
                }
                @keyframes modalSlideIn {
                    from { opacity:0; transform: translateY(28px) scale(0.94); }
                    to   { opacity:1; transform: translateY(0)   scale(1); }
                }

                /* ── Drop Zone ── */
                #img-drop-area {
                    position: relative;
                    border: 1.5px dashed rgba(168,85,247,0.45);
                    border-radius: 22px;
                    padding: 32px 20px;
                    text-align: center;
                    cursor: pointer;
                    transition: all 0.25s ease;
                    background: rgba(168,85,247,0.04);
                    overflow: hidden;
                }
                #img-drop-area:hover {
                    border-color: rgba(168,85,247,0.85);
                    background: rgba(168,85,247,0.09);
                    transform: scale(1.01);
                }
                #img-drop-area.drag-over {
                    border-color: #a855f7;
                    background: rgba(168,85,247,0.12);
                    box-shadow: 0 0 30px rgba(168,85,247,0.25);
                }
                /* Shimmer sweep on hover */
                #img-drop-area::after {
                    content:''; position:absolute; inset:0;
                    background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.06) 50%, transparent 70%);
                    background-size: 200% 100%;
                    opacity: 0; transition: opacity 0.3s;
                }
                #img-drop-area:hover::after { opacity: 1; animation: shimmerSweep 1.2s infinite; }
                @keyframes shimmerSweep {
                    0%   { background-position: -100% 0; }
                    100% { background-position: 200% 0; }
                }

                /* ── Preview Container ── */
                #img-preview-wrap { display: none; }
                .img-preview-glass {
                    position: relative;
                    border-radius: 20px;
                    overflow: hidden;
                    margin-bottom: 14px;
                    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
                }
                #img-preview {
                    width: 100%; max-height: 220px;
                    object-fit: cover; display: block;
                    border-radius: 20px;
                    transition: filter 0.5s ease;
                }

                /* ── Google Lens Scan Animation ── */
                .scan-overlay {
                    position: absolute; inset: 0;
                    border-radius: 20px;
                    display: none;
                    overflow: hidden;
                    pointer-events: none;
                }
                /* Scanning beam */
                .scan-beam {
                    position: absolute; left: 0; right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, transparent, #a855f7, #6366f1, #a855f7, transparent);
                    box-shadow: 0 0 18px 6px rgba(168,85,247,0.6);
                    animation: scanBeam 1.8s cubic-bezier(0.4,0,0.6,1) infinite;
                    top: 0;
                }
                @keyframes scanBeam {
                    0%   { top: 0%;   opacity: 1; }
                    48%  { top: 100%; opacity: 1; }
                    50%  { top: 100%; opacity: 0; }
                    52%  { top: 0%;   opacity: 0; }
                    54%  { top: 0%;   opacity: 1; }
                    100% { top: 100%; opacity: 1; }
                }
                /* Corner brackets */
                .scan-corner {
                    position: absolute; width: 22px; height: 22px;
                    border-color: #a855f7; border-style: solid;
                    border-width: 0; opacity: 0.9;
                }
                .sc-tl { top:8px; left:8px;   border-top-width:2.5px; border-left-width:2.5px;  border-radius:4px 0 0 0; }
                .sc-tr { top:8px; right:8px;   border-top-width:2.5px; border-right-width:2.5px; border-radius:0 4px 0 0; }
                .sc-bl { bottom:8px; left:8px;  border-bottom-width:2.5px; border-left-width:2.5px; border-radius:0 0 0 4px; }
                .sc-br { bottom:8px; right:8px; border-bottom-width:2.5px; border-right-width:2.5px; border-radius:0 0 4px 0; }
                /* Ripple grid */
                .scan-grid {
                    position: absolute; inset: 0;
                    background-image:
                        linear-gradient(rgba(168,85,247,0.07) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(168,85,247,0.07) 1px, transparent 1px);
                    background-size: 28px 28px;
                    animation: gridFade 1.8s ease infinite;
                }
                @keyframes gridFade {
                    0%,100% { opacity:0; } 50% { opacity:1; }
                }
                /* Blur/desaturate image while scanning */
                .is-scanning #img-preview { filter: brightness(0.75) saturate(0.4); }

                /* ── Status text ── */
                #img-status-text {
                    font-size: 13px; color: rgba(255,255,255,0.55);
                    text-align: center; margin-top: 4px; min-height: 18px;
                    letter-spacing: 0.3px;
                    transition: color 0.3s;
                }

                /* ── Buttons ── */
                #img-search-go {
                    width: 100%;
                    padding: 13px;
                    border: none;
                    border-radius: 16px;
                    background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%);
                    color: #fff;
                    font-size: 15px; font-weight: 700;
                    cursor: pointer;
                    box-shadow: 0 6px 24px rgba(168,85,247,0.4);
                    transition: all 0.2s ease;
                    letter-spacing: 0.2px;
                }
                #img-search-go:hover:not(:disabled) {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 32px rgba(168,85,247,0.55);
                }
                #img-search-go:disabled { opacity: 0.6; cursor: default; transform: none; }

                .img-action-row {
                    display: flex; gap: 10px; margin-top: 12px;
                }
                .img-pill-btn {
                    flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
                    padding: 10px 16px; border-radius: 50px;
                    background: rgba(255,255,255,0.07);
                    border: 1px solid rgba(255,255,255,0.12);
                    color: rgba(255,255,255,0.75);
                    font-size: 13px; font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .img-pill-btn:hover {
                    background: rgba(168,85,247,0.15);
                    border-color: rgba(168,85,247,0.4);
                    color: #fff;
                    transform: translateY(-1px);
                }
                .img-pill-btn i { color: #a855f7; }

                /* Close btn */
                .img-close-btn {
                    position: absolute; top: 16px; right: 16px;
                    width: 30px; height: 30px; border-radius: 50%;
                    background: rgba(255,255,255,0.1);
                    border: 1px solid rgba(255,255,255,0.14);
                    color: rgba(255,255,255,0.7);
                    font-size: 14px; cursor: pointer;
                    display: flex; align-items: center; justify-content: center;
                    transition: all 0.2s;
                    backdrop-filter: blur(8px);
                }
                .img-close-btn:hover { background: rgba(255,255,255,0.18); color: #fff; transform: scale(1.1); }
            </style>

            <div id="img-search-overlay" onclick="closeImageSearch(event)">
                <div id="img-search-modal">

                    <!-- Close -->
                    <button class="img-close-btn" onclick="closeImageSearch()">✕</button>

                    <!-- Title -->
                    <div style="margin-bottom:20px; padding-right:36px;">
                        <div style="font-size:17px; font-weight:700; letter-spacing:-0.3px;">
                            <i class="fa-solid fa-camera-viewfinder" style="color:#a855f7; margin-right:8px;"></i>Search by Image
                        </div>
                        <div style="font-size:12px; color:rgba(255,255,255,0.4); margin-top:4px;">
                            Upload any product photo to find similar items on QOON
                        </div>
                    </div>

                    <!-- Drop Zone -->
                    <div id="img-drop-area"
                        onclick="document.getElementById('img-file-input').click()"
                        ondragover="event.preventDefault(); this.classList.add('drag-over');"
                        ondragleave="this.classList.remove('drag-over');"
                        ondrop="handleImgDrop(event)">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size:38px; color:#a855f7; margin-bottom:12px; display:block;"></i>
                        <div style="font-size:15px; font-weight:600; margin-bottom:5px;">Drop image here</div>
                        <div style="font-size:12px; color:rgba(255,255,255,0.38);">Any format — auto-converted to JPEG</div>
                    </div>

                    <!-- Preview + Scan Animation -->
                    <div id="img-preview-wrap">
                        <div class="img-preview-glass" id="img-preview-glass">
                            <img id="img-preview" alt="Preview">

                            <!-- Google Lens-style scan overlay -->
                            <div class="scan-overlay" id="scan-overlay">
                                <div class="scan-grid"></div>
                                <div class="scan-beam"></div>
                                <div class="scan-corner sc-tl"></div>
                                <div class="scan-corner sc-tr"></div>
                                <div class="scan-corner sc-bl"></div>
                                <div class="scan-corner sc-br"></div>
                            </div>
                        </div>

                        <div id="img-status-text">Image ready — tap below to search</div>

                        <button onclick="runImageSearch()" id="img-search-go">
                            <i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Find Similar Products
                        </button>
                    </div>

                    <!-- Action pills -->
                    <div class="img-action-row" id="img-action-row">
                        <button class="img-pill-btn" onclick="document.getElementById('img-file-input').click()">
                            <i class="fa-solid fa-folder-open"></i> Browse Files
                        </button>
                        <button class="img-pill-btn" onclick="document.getElementById('img-camera-input').click()">
                            <i class="fa-solid fa-camera"></i> Take Photo
                        </button>
                    </div>

                    <input type="file" id="img-file-input"    accept="image/*"                     style="display:none" onchange="handleImgFile(this)">
                    <input type="file" id="img-camera-input"  accept="image/*" capture="environment" style="display:none" onchange="handleImgFile(this)">
                </div>
            </div>

            <!-- RESULTS -->
            <div class="results" id="results">
                <div class="tab-pills" id="tabPills"></div>

                <div id="sec-shops" style="display:none">
                    <div class="section-header">
                        <span class="section-title"><i class="fa-solid fa-store"
                                style="margin-right:8px;color:#f50057"></i>Shops</span>
                        <span class="section-count" id="shops-count"></span>
                    </div>
                    <div id="shops-list"></div>
                </div>

                <div id="sec-products" style="display:none">
                    <div class="section-header">
                        <span class="section-title"><i class="fa-solid fa-box"
                                style="margin-right:8px;color:#f50057"></i>Products</span>
                        <span class="section-count" id="products-count"></span>
                    </div>
                    <div class="products-grid" id="products-grid"></div>
                </div>

                <div id="sec-ali" style="display:none">
                    <div class="section-header">
                        <span class="section-title"><i class="fa-solid fa-globe"
                                style="margin-right:8px;color:#ff4081"></i>International Products</span>
                        <span class="section-count" id="ali-count"></span>
                    </div>
                    <div class="products-grid" id="ali-grid"></div>
                    <div id="ali-load-more-container" style="text-align:center; margin-top:20px; display:none;">
                        <button id="ali-load-more-btn" onclick="loadMoreAli()" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#fff; padding:10px 24px; border-radius:20px; cursor:pointer; font-weight:600; font-size:14px; transition:0.2s;">Load More Products</button>
                    </div>
                </div>

                <div id="sec-posts" style="display:none">
                    <div class="section-header">
                        <span class="section-title"><i class="fa-solid fa-newspaper"
                                style="margin-right:8px;color:#f50057"></i>Posts</span>
                        <span class="section-count" id="posts-count"></span>
                    </div>
                    <div id="posts-list"></div>
                </div>

                <div id="sec-reels" style="display:none">
                    <div class="section-header">
                        <span class="section-title"><i class="fa-solid fa-film"
                                style="margin-right:8px;color:#f50057"></i>Reels</span>
                        <span class="section-count" id="reels-count"></span>
                    </div>
                    <div class="reels-grid" id="reels-grid"></div>
                </div>

                <div class="no-results" id="noResults" style="display:none">
                    <i class="fa-solid fa-ghost" style="font-size:40px; margin-bottom:16px;"></i>
                    <p>No results found. Try a different keyword.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        window.toggleProfileMenu = function (e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown) return;
            const isVisible = dropdown.getComputedStyle ? getComputedStyle(dropdown).display === 'flex' : dropdown.style.display === 'flex';
            dropdown.style.display = isVisible ? 'none' : 'flex';
        };

        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('space');
            const c = canvas.getContext('2d');

            let w = canvas.width = window.innerWidth;
            let h = canvas.height = window.innerHeight;

            window.addEventListener('resize', () => {
                w = canvas.width = window.innerWidth;
                h = canvas.height = window.innerHeight;
            });

            const numStars = 800;
            const stars = [];

            class Star {
                constructor() {
                    this.x = Math.random() * w * 2 - w;
                    this.y = Math.random() * h * 2 - h;
                    this.z = Math.random() * w;
                    this.pz = this.z;
                    this.size = Math.random() * 1.5 + 0.5;
                    this.color = Math.random() > 0.8 ? 'rgba(0, 212, 255, ' : 'rgba(255, 255, 255, '; // Add some blue stars
                }

                update() {
                    this.z -= 2; // Speed of flying forward
                    if (this.z < 1) {
                        this.z = w;
                        this.x = Math.random() * w * 2 - w;
                        this.y = Math.random() * h * 2 - h;
                        this.pz = this.z;
                    }
                }

                show() {
                    // Static perspective mapping
                    const sx = (this.x) / this.z * w + w / 2;
                    const sy = (this.y) / this.z * w + h / 2;

                    const r = this.size * (w / this.z);

                    // Calculate opacity based on Z-depth
                    const opacity = Math.max(0, 1 - (this.z / w));

                    c.beginPath();
                    c.arc(sx, sy, r, 0, Math.PI * 2);
                    c.fillStyle = this.color + opacity + ')';
                    c.fill();
                }
            }

            for (let i = 0; i < numStars; i++) {
                stars.push(new Star());
            }

            function animate() {
                requestAnimationFrame(animate);
                // Trailing effect by drawing semi-transparent background
                c.fillStyle = 'rgba(1, 0, 8, 0.2)';
                c.fillRect(0, 0, w, h);

                for (let i = 0; i < stars.length; i++) {
                    stars[i].update();
                    stars[i].show();
                }
            }

            animate();
        });

        let searchTimer = null;
        let lastQuery = '';
        let aliPage = 1;
        let isAliLoading = false;
        const input = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearBtn');

        // Extract query from URL if exists
        const urlParams = new URLSearchParams(window.location.search);
        const initialQ  = urlParams.get('q');
        const mode      = urlParams.get('mode');

        // ── Image search results arriving from index page ──
        if (mode === 'img_results') {
            try {
                const stored = sessionStorage.getItem('imgSearchResults');
                if (stored) {
                    const data = JSON.parse(stored);
                    sessionStorage.removeItem('imgSearchResults'); // consume once
                    renderAliExpressResults(data.products || [], data.total || 0);
                } else {
                    // Nothing stored – just show empty
                    showEmpty();
                }
            } catch(e) { showEmpty(); }
        } else if (initialQ) {
            input.value = initialQ;
            doSearch(initialQ);
        }

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clearBtn.style.display = q ? 'block' : 'none';
            clearTimeout(searchTimer);
            if (q.length < 2) { showEmpty(); return; }
            if (q === lastQuery) return;
            searchTimer = setTimeout(() => doSearch(q), 380);
        });

        function clearSearch() {
            input.value = '';
            clearBtn.style.display = 'none';
            input.focus();
            showEmpty();
            window.history.replaceState({}, '', 'search.php');
        }

        function showEmpty() {
            document.body.classList.remove('is-searching');
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('loader').style.display = 'none';
            document.getElementById('results').style.display = 'none';
            lastQuery = '';
        }

        function hl(text, q) {
            if (!text || !q) return text || '';
            const safe = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return String(text).replace(new RegExp(`(${safe})`, 'gi'), '<mark>$1</mark>');
        }

        function doSearch(q) {
            if (typeof q === 'string') { input.value = q; clearBtn.style.display = 'block'; }
            const query = input.value.trim();
            if (!query || query.length < 2) return;
            lastQuery = query;

            document.body.classList.add('is-searching');

            // Update URL silently
            window.history.replaceState({}, '', `search.php?q=${encodeURIComponent(query)}`);

            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('results').style.display = 'none';
            document.getElementById('loader').style.display = 'block';

            // Show shimmer in the ali section
            const aliSec = document.getElementById('sec-ali');
            const aliGrid = document.getElementById('ali-grid');
            aliSec.style.display = 'block';
            let shimmerHtml = '';
            for(let i = 0; i < 4; i++) {
                shimmerHtml += `
                <div class="product-card" style="padding:12px;display:flex;flex-direction:column;gap:8px;">
                    <div style="width:100%;aspect-ratio:1;border-radius:12px;background:rgba(255,255,255,0.05);position:relative;overflow:hidden;">
                        <div style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.07) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
                    </div>
                    <div style="height:14px;width:80%;border-radius:4px;background:rgba(255,255,255,0.05);position:relative;overflow:hidden;margin-top:4px;">
                        <div style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.07) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
                    </div>
                </div>`;
            }
            aliGrid.innerHTML = shimmerHtml;

            // 1. Fetch Local DB Search
            fetch(`search_api.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => renderResults(data, query))
                .catch(() => {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('results').style.display = 'block';
                    document.getElementById('noResults').style.display = 'block';
                });
                
            // 2. Fetch Parallel AliExpress Search
            aliPage = 1;
            window.aliSearchData = [];
            document.getElementById('ali-load-more-container').style.display = 'none';
            fetch(`ajax_search_ali.php?q=${encodeURIComponent(query)}&page=${aliPage}`)
                .then(r => r.json())
                .then(data => {
                    window.aliSearchData = data.products || [];
                    renderAliResults(data.products || [], query, false);
                })
                .catch(() => {
                    document.getElementById('sec-ali').style.display = 'none';
                });
        }

        function renderResults(data, q) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('results').style.display = 'block';

            const shops = data.shops || [];
            const products = data.products || [];
            const posts = data.posts || [];
            const reels = data.reels || [];
            const total = shops.length + products.length + posts.length + reels.length;

            document.getElementById('noResults').style.display = total ? 'none' : 'block';

            const pills = document.getElementById('tabPills');
            pills.innerHTML = '';
            const tabs = [];
            if (shops.length) tabs.push({ id: 'sec-shops', label: `Shops (${shops.length})` });
            if (products.length) tabs.push({ id: 'sec-products', label: `Products (${products.length})` });
            tabs.push({ id: 'sec-ali', label: `International` }); // Always show tab, will have skeleton initially
            if (posts.length) tabs.push({ id: 'sec-posts', label: `Posts (${posts.length})` });
            if (reels.length) tabs.push({ id: 'sec-reels', label: `Reels (${reels.length})` });

            tabs.forEach((t, i) => {
                const p = document.createElement('button');
                p.className = 'tab-pill' + (i === 0 ? ' active' : '');
                p.innerHTML = t.label;
                p.onclick = () => {
                    document.querySelectorAll('.tab-pill').forEach(x => x.classList.remove('active'));
                    p.classList.add('active');
                    const el = document.getElementById(t.id);
                    if (el) {
                        const yOffset = -100;
                        const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                        window.scrollTo({ top: y, behavior: 'smooth' });
                    }
                };
                pills.appendChild(p);
            });

            const shopsSec = document.getElementById('sec-shops');
            shopsSec.style.display = shops.length ? 'block' : 'none';
            document.getElementById('shops-list').innerHTML = shops.map(s => `
    <div class="shop-card" onclick="window.location.href='shop.php?id=${s.ShopID}'">
      <img class="shop-avatar" src="${s.ShopLogo}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(s.ShopName)}&background=222&color=fff'" alt="">
      <div class="shop-info">
        <div class="shop-name">${hl(s.ShopName, q)}</div>
        <div class="shop-desc">${hl(s.ShopDesc || 'Shop', q)}</div>
      </div>
      <i class="fa-solid fa-chevron-right" style="color:rgba(255,255,255,.3)"></i>
    </div>`).join('');

            const productsSec = document.getElementById('sec-products');
            productsSec.style.display = products.length ? 'block' : 'none';
            document.getElementById('products-grid').innerHTML = products.map(p => `
    <div class="product-card" onclick="window.location.href='shop.php?id=${p.ShopID}'">
      <img class="product-img" src="${p.FoodImage}" onerror="this.src='https://ui-avatars.com/api/?name=Item&background=222&color=fff'" alt="">
      <div class="product-body">
        <div class="product-name">${hl(p.FoodName, q)}</div>
        <div class="product-price">${parseFloat(p.FoodPrice || 0).toFixed(0)} MAD</div>
        <div class="product-shop">${p.ShopName}</div>
      </div>
    </div>`).join('');

            const postsSec = document.getElementById('sec-posts');
            postsSec.style.display = posts.length ? 'block' : 'none';
            document.getElementById('posts-list').innerHTML = posts.map(p => `
    <div class="post-card" onclick="window.location.href='shop.php?id=${p.ShopID}'">
      <img class="post-avatar" src="${p.ShopLogo}" onerror="this.src='https://ui-avatars.com/api/?name=Q&background=222&color=fff'" alt="">
      <div class="post-body">
        <div class="post-shop">${p.ShopName}</div>
        <div class="post-text">${hl(p.PostText, q)}</div>
      </div>
      ${p.Photo && p.Photo !== 'https://qoon.app/userDriver/UserDriverApi/photo/' ? `<img class="post-img" src="${p.Photo}" onerror="this.style.display='none'" alt="">` : ''}
    </div>`).join('');

            const reelsSec = document.getElementById('sec-reels');
            reelsSec.style.display = reels.length ? 'block' : 'none';
            document.getElementById('reels-grid').innerHTML = reels.map(r => `
    <div class="reel-card" onclick="window.location.href='reel.php?id=${r.PostID}'">
      <img class="reel-thumb" src="${r.Thumbnail}" onerror="this.src='https://ui-avatars.com/api/?name=Reel&background=111&color=fff'" alt="">
      <div class="reel-overlay">
        <div style="font-size:12px; font-weight:600; color:#fff">${r.ShopName}</div>
      </div>
    </div>`).join('');
        }

        function renderAliResults(products, q, append) {
            const aliSec = document.getElementById('sec-ali');
            const aliGrid = document.getElementById('ali-grid');
            const loadMoreBtn = document.getElementById('ali-load-more-container');
            
            if (!append && (!products || products.length === 0)) {
                aliSec.style.display = 'none';
                return;
            }
            
            aliSec.style.display = 'block';
            document.getElementById('ali-count').innerText = `(${window.aliSearchData.length})`;
            
            let html = products.map((p, idx) => {
                let actualIdx = append ? (window.aliSearchData.length - products.length + idx) : idx;
                let aliId = String(p.id).replace('ALI_', '');
                return `
    <div class="product-card" onclick="window.location.href='ali_product.php?id=${aliId}'" style="position:relative;">
      <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.6); backdrop-filter:blur(5px); color:#fff; font-size:10px; padding:2px 6px; border-radius:8px; z-index:5;"><i class="fa-solid fa-plane"></i> Int'l</div>
      <img class="product-img" src="${p.img}" onerror="this.src='https://ui-avatars.com/api/?name=Item&background=222&color=fff'" alt="">
      <div class="product-body">
        <div class="product-name" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${hl(p.name, q)}</div>
        <div class="product-price">${p.price} MAD <span style="font-size:11px;color:rgba(255,255,255,0.4);text-decoration:line-through;font-weight:400;margin-left:4px;">${p.oldPrice}</span></div>
      </div>
    </div>`}).join('');
            
            if (append) {
                // Remove shimmer skeletons first
                const shimmers = aliGrid.querySelectorAll('.shimmer-skeleton');
                shimmers.forEach(s => s.remove());
                aliGrid.insertAdjacentHTML('beforeend', html);
            } else {
                aliGrid.innerHTML = html;
            }
            
            // Re-hide noResults if it was shown and we now have ali results
            document.getElementById('noResults').style.display = 'none';
            
            // Show load more button if we got exactly 20 items
            if (products.length >= 20) {
                loadMoreBtn.style.display = 'block';
            } else {
                loadMoreBtn.style.display = 'none';
            }
            isAliLoading = false;
        }

        function loadMoreAli() {
            if (isAliLoading) return;
            isAliLoading = true;
            aliPage++;
            
            const loadMoreContainer = document.getElementById('ali-load-more-container');
            loadMoreContainer.style.display = 'none';
            
            const aliGrid = document.getElementById('ali-grid');
            let shimmerHtml = '';
            for(let i = 0; i < 4; i++) {
                shimmerHtml += `
                <div class="product-card shimmer-skeleton" style="padding:12px;display:flex;flex-direction:column;gap:8px;">
                    <div style="width:100%;aspect-ratio:1;border-radius:12px;background:rgba(255,255,255,0.05);position:relative;overflow:hidden;">
                        <div style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.07) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
                    </div>
                    <div style="height:14px;width:80%;border-radius:4px;background:rgba(255,255,255,0.05);position:relative;overflow:hidden;margin-top:4px;">
                        <div style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.07) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
                    </div>
                </div>`;
            }
            aliGrid.insertAdjacentHTML('beforeend', shimmerHtml);
            
            const query = input.value.trim();
            fetch(`ajax_search_ali.php?q=${encodeURIComponent(query)}&page=${aliPage}`)
                .then(r => r.json())
                .then(data => {
                    if (data.products && data.products.length > 0) {
                        window.aliSearchData = window.aliSearchData.concat(data.products);
                        renderAliResults(data.products, query, true);
                    } else {
                        const shimmers = aliGrid.querySelectorAll('.shimmer-skeleton');
                        shimmers.forEach(s => s.remove());
                        isAliLoading = false;
                    }
                })
                .catch(() => {
                    const shimmers = aliGrid.querySelectorAll('.shimmer-skeleton');
                    shimmers.forEach(s => s.remove());
                    loadMoreContainer.style.display = 'block'; // Show button again so they can retry
                    isAliLoading = false;
                });
        }

        // ── Image Search ──────────────────────────────────────────────────────
        let imgFile   = null;  // actual File object for upload
        let imgBase64 = null;  // data URI for preview only

        function openImageSearch() {
            const overlay = document.getElementById('img-search-overlay');
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImageSearch(e) {
            if (e && e.target !== document.getElementById('img-search-overlay')) return;
            document.getElementById('img-search-overlay').style.display = 'none';
            document.body.style.overflow = '';
        }

        function handleImgDrop(e) {
            e.preventDefault();
            document.getElementById('img-drop-area').style.borderColor = 'rgba(168,85,247,0.5)';
            const file = e.dataTransfer.files[0];
            if (file) processImgFile(file);
        }

        function handleImgFile(input) {
            const file = input.files[0];
            if (file) processImgFile(file);
        }

        function processImgFile(file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.'); return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('Image too large. Max 10MB.'); return;
            }

            imgFile = null;
            const btn = document.getElementById('img-search-go');
            const statusText = document.getElementById('img-status-text');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="margin-right:8px;"></i>Converting...'; }

            const reader = new FileReader();
            reader.onload = (e) => {
                const dataUrl = e.target.result;

                // Show preview
                document.getElementById('img-preview').src = dataUrl;
                document.getElementById('img-preview-wrap').style.display = 'block';
                document.getElementById('img-drop-area').style.display = 'none';
                document.getElementById('img-action-row').style.display = 'none';

                // 🔴 Start scan animation immediately
                const scanOverlay = document.getElementById('scan-overlay');
                const glass = document.getElementById('img-preview-glass');
                scanOverlay.style.display = 'block';
                glass.classList.add('is-scanning');
                if (statusText) statusText.textContent = 'Analysing image...';

                const img = new Image();
                img.onload = () => {
                    let w = img.width, h = img.height;
                    const MAX = 800;
                    if (w > MAX || h > MAX) {
                        if (w > h) { h = Math.round(h * MAX / w); w = MAX; }
                        else       { w = Math.round(w * MAX / h); h = MAX; }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);

                    canvas.toBlob((blob) => {
                        if (!blob) { alert('Could not convert image.'); return; }
                        imgFile = new File([blob], 'search.jpg', { type: 'image/jpeg' });

                        // ✅ Stop scan — image ready
                        setTimeout(() => {
                            scanOverlay.style.display = 'none';
                            glass.classList.remove('is-scanning');
                            if (statusText) statusText.textContent = '✓ Image ready — ' + (imgFile.size/1024).toFixed(0) + ' KB';
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Find Similar Products';
                            }
                        }, 900); // brief pause so user sees the scan finish
                    }, 'image/jpeg', 0.88);
                };
                img.onerror = () => alert('Could not read image file.');
                img.src = dataUrl;
            };
            reader.readAsDataURL(file);
        }

        function runImageSearch() {
            if (!imgFile) { alert('Please select an image first.'); return; }
            const btn = document.getElementById('img-search-go');
            const statusText = document.getElementById('img-status-text');
            const scanOverlay = document.getElementById('scan-overlay');
            const glass = document.getElementById('img-preview-glass');

            // Re-trigger scan animation during API call
            scanOverlay.style.display = 'block';
            glass.classList.add('is-scanning');

            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="margin-right:8px;"></i>Searching AliExpress...';
            btn.disabled = true;

            // Animate status text
            const msgs = ['Scanning image...', 'Identifying product...', 'Matching catalogue...', 'Almost there...'];
            let mIdx = 0;
            if (statusText) statusText.textContent = msgs[0];
            const statusInterval = setInterval(() => {
                mIdx = (mIdx + 1) % msgs.length;
                if (statusText) statusText.textContent = msgs[mIdx];
            }, 1400);

            const fd = new FormData();
            fd.append('image',    imgFile);
            fd.append('country',  'MA');
            fd.append('currency', 'MAD');
            fd.append('page',     '1');

            fetch('ajax_image_search.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                clearInterval(statusInterval);
                scanOverlay.style.display = 'none';
                glass.classList.remove('is-scanning');
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Find Similar Products';
                btn.disabled = false;
                if (statusText) statusText.textContent = '';

                if (data.error) {
                    alert('Image search error: ' + data.error); return;
                }

                // Close modal & show results
                document.getElementById('img-search-overlay').style.display = 'none';
                document.body.style.overflow = '';

                // Show results in the AliExpress section
                const aliSec  = document.getElementById('sec-ali');
                const aliGrid = document.getElementById('ali-grid');
                const results = document.getElementById('results');

                document.body.classList.add('is-searching');
                results.style.display = 'block';
                aliSec.style.display  = 'block';
                document.getElementById('ali-count').innerText = `(${data.products.length})`;

                aliGrid.innerHTML = data.products.map(p => {
                    const aliId = String(p.id).replace('ALI_', '');
                    const discBadge = p.discount && p.discount !== '0%'
                        ? `<div style="position:absolute;top:8px;left:8px;background:#a855f7;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:6px;z-index:5;">-${p.discount}</div>` : '';
                    const ratingBadge = p.rating
                        ? `<span style="font-size:10px;color:#f59e0b;margin-left:4px;">⭐ ${p.rating}</span>` : '';
                    const soldBadge = p.sold && p.sold > 0
                        ? `<span style="font-size:10px;color:rgba(255,255,255,0.4);margin-left:4px;">${p.sold} sold</span>` : '';
                    return `<div class="product-card" onclick="window.location.href='ali_product.php?id=${aliId}'" style="position:relative;">
                        <div style="position:absolute;top:8px;right:8px;background:rgba(168,85,247,0.85);backdrop-filter:blur(5px);color:#fff;font-size:10px;padding:2px 6px;border-radius:8px;z-index:5;">
                            <i class="fa-solid fa-camera"></i> Visual
                        </div>
                        ${discBadge}
                        <img class="product-img" src="${p.img}" onerror="this.src='https://ui-avatars.com/api/?name=Item&background=222&color=fff'" alt="">
                        <div class="product-body">
                            <div class="product-name" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${p.name}</div>
                            <div class="product-price">${p.price} <span style="font-size:11px;color:rgba(255,255,255,0.4);text-decoration:line-through;font-weight:400;margin-left:4px;">${p.oldPrice}</span></div>
                            <div style="display:flex;align-items:center;margin-top:2px;">${ratingBadge}${soldBadge}</div>
                        </div>
                    </div>`;
                }).join('');

                if (!data.products.length) {
                    aliGrid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px 20px;">
                        <div style="font-size:40px; margin-bottom:12px;">🔍</div>
                        <div style="font-size:16px; font-weight:700; margin-bottom:8px;">No similar products found</div>
                        <div style="font-size:13px; color:rgba(255,255,255,0.5); margin-bottom:16px; max-width:280px; margin-left:auto; margin-right:auto;">
                            AliExpress image search works best with clear product photos on a white/neutral background.
                        </div>
                        <div style="font-size:12px; background:rgba(168,85,247,0.1); border:1px solid rgba(168,85,247,0.2); border-radius:12px; padding:12px 16px; text-align:left; max-width:300px; margin:0 auto 16px;">
                            <b style="color:#a855f7;">💡 Tips for better results:</b><br>
                            • Screenshot a product from any website<br>
                            • Use photos with white/plain background<br>
                            • Make sure the product fills most of the image<br>
                            • Avoid group shots or lifestyle photos
                        </div>
                        <button onclick="openImageSearch()" style="background:linear-gradient(135deg,#a855f7,#6366f1);color:#fff;border:none;padding:10px 24px;border-radius:12px;cursor:pointer;font-size:14px;font-weight:600;">
                            <i class="fa-solid fa-camera" style="margin-right:6px;"></i> Try Another Image
                        </button>
                    </div>`;
                    // Reset modal state for retry
                    imgFile = null; imgBase64 = null;
                    document.getElementById('img-preview-wrap').style.display = 'none';
                    document.getElementById('img-drop-area').style.display = 'block';
                }
            })
            .catch(err => {
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Find Similar Products';
                btn.disabled = false;
                alert('Error: ' + err.message);
            });
        }
    </script>

    <?php require_once 'includes/modals/auth.php'; ?>
    <?php require_once 'includes/modals/product.php'; ?>
</body>

</html>