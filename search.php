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

        /* ─── MOBILE RESPONSIVE OPTIMIZATIONS ─── */
        @media (max-width: 600px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)) !important;
                gap: 10px !important;
            }

            .reels-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)) !important;
                gap: 10px !important;
            }

            .prompt-container {
                width: 100% !important;
                border-radius: 20px !important;
            }

            .content-wrapper {
                padding: 12px !important;
            }

            .brand-logo {
                height: 50px !important;
                margin-top: 10px;
            }

            .tab-pill {
                font-size: 13px !important;
                padding: 6px 16px !important;
            }
        }
    </style>
</head>

<body>

    <!-- True 3D Galaxy Canvas -->
    <canvas id="space"></canvas>

    <?php require_once 'includes/header.php'; ?>

    <div class="content-wrapper">
        <div class="search-top-nav"
            style="display: flex; align-items: flex-start; justify-content: space-between; position: relative; z-index: 10;">
            <div class="back-nav" onclick="window.location.href='index.php'" style="padding: 10px; z-index: 10;">
                <i class="fa-solid fa-arrow-left" style="font-size: 24px; color: #fff;"></i>
            </div>
        </div>

        <style>
            .search-page-wrapper {
                display: flex;
                flex-direction: column;
                min-height: calc(100vh - 80px);
            }

            .search-wrapper {
                transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
                margin: auto auto;
                /* Perfectly centers vertically and horizontally */
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
            }

            body.is-searching .search-wrapper {
                margin: 2vh auto 0 auto !important;
                /* Flies to the top */
            }

            .brand-logo-container {
                transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
                margin-bottom: -5px;
                /* Small, consistent gap above search bar */
                transform-origin: bottom center;
                /* Keeps the gap perfectly identical when scaling */
                pointer-events: none;
                z-index: 15;
            }

            body.is-searching .brand-logo-container {
                transform: scale(0.65);
                margin-bottom: -5px;
                /* Same exact distance */
            }
        </style>

        <main class="search-page-wrapper" style="padding-top: 0px;">

            <!-- Home UI Prompt Container -->
            <div class="search-wrapper" style="max-width:100%;">

                <div class="brand-logo-container">
                    <img src="logo_qoon_white.png" alt="QOON Logo" class="brand-logo"
                        style="height: 65px; width: auto; object-fit: contain; filter: drop-shadow(0 0 15px rgba(255,255,255,0.4));">
                </div>

                <div class="prompt-container" id="promptBox"
                    style="border-radius: 24px; padding-right: 12px; width: 100%; max-width: 600px;">
                    <button class="icon-btn" style="margin-left: 4px;"><i
                            class="fa-solid fa-magnifying-glass"></i></button>

                    <input type="text" class="prompt-input" id="searchInput"
                        placeholder="Search shops, products, reels..." autocomplete="off" autofocus>

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
        const input = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearBtn');

        // Extract query from URL if exists
        const urlParams = new URLSearchParams(window.location.search);
        const initialQ = urlParams.get('q');
        if (initialQ) {
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

            fetch(`search_api.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => renderResults(data, query))
                .catch(() => {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('results').style.display = 'block';
                    document.getElementById('noResults').style.display = 'block';
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
            document.getElementById('products-grid').innerHTML = products.map(p => {
                if (p.is_global) {
                    const foodJson = JSON.stringify({
                        id: p.FoodID,
                        name: p.FoodName,
                        price: p.FoodPrice,
                        oldPrice: p.oldPrice || null,
                        img: p.FoodImage,
                        desc: p.FoodDesc,
                        cat_id: 999,
                        extra1: '',
                        extra2: '',
                        extra1_p: 0,
                        extra2_p: 0
                    }).replace(/'/g, "&apos;").replace(/"/g, "&quot;");

                    return `
                    <div class="product-card" onclick="openProductModal(this)" data-product="${foodJson}">
                      <div style="position:relative;">
                        <img class="product-img" src="${p.FoodImage}" onerror="this.src='https://ui-avatars.com/api/?name=Global&background=E62E04&color=fff'" alt="" referrerpolicy="no-referrer">
                        <div style="position:absolute;top:8px;right:8px;background:#E62E04;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">Global</div>
                      </div>
                      <div class="product-body">
                        <div class="product-name">${hl(p.FoodName, q)}</div>
                        <div class="product-price">${parseFloat(p.FoodPrice || 0).toFixed(0)} MAD</div>
                        <div class="product-shop">${p.ShopName}</div>
                      </div>
                    </div>`;
                } else {
                    return `
                    <div class="product-card" onclick="window.location.href='shop.php?id=${p.ShopID}'">
                      <img class="product-img" src="${p.FoodImage}" onerror="this.src='https://ui-avatars.com/api/?name=Item&background=222&color=fff'" alt="">
                      <div class="product-body">
                        <div class="product-name">${hl(p.FoodName, q)}</div>
                        <div class="product-price">${parseFloat(p.FoodPrice || 0).toFixed(0)} MAD</div>
                        <div class="product-shop">${p.ShopName}</div>
                      </div>
                    </div>`;
                }
            }).join('');

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
    </script>

    <?php require_once 'includes/modals/auth.php'; ?>
    <?php include 'includes/modals/product.php'; ?>
</body>

</html>