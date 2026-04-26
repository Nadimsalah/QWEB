<?php
if (!isset($categories)) {
    $categories = [];
    try {
        if ($con) {
            $stmtC = $con->prepare("SELECT * FROM Categories WHERE Type='Top' AND Pro != 'Pro' ORDER BY priority DESC LIMIT 20");
            if ($stmtC) {
                $stmtC->execute();
                $result = $stmtC->get_result();
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $categories[] = $row;
                    }
                }
                $stmtC->close();
            }
        }
    } catch (Throwable $e) {}

    // Inject "Book Flight" and "eSIM" categories natively at the end
    array_push($categories, [
        'CategoryId' => 'flights',
        'EnglishCategory' => 'Book Flight',
        'ArabCategory' => 'حجز طيران',
        'NameEn' => 'Book Flight',
        'Photo' => 'flight_category.jpg'
    ]);
    array_push($categories, [
        'CategoryId' => 'esims',
        'EnglishCategory' => 'Global eSIM',
        'ArabCategory' => 'إنترنت دولي',
        'NameEn' => 'Global eSIM',
        'Photo' => 'esim_category.jpg'
    ]);

    // Apply user's custom category order if it exists
    if (isset($_COOKIE['qoon_cat_order']) && !empty($_COOKIE['qoon_cat_order'])) {
        $savedOrder = explode(',', $_COOKIE['qoon_cat_order']);
        $orderedCategories = [];
        foreach ($savedOrder as $savedId) {
            foreach ($categories as $key => $cat) {
                $catId = $cat['CategoryId'] ?? null;
                if ((string)$catId === (string)$savedId) {
                    $orderedCategories[] = $cat;
                    unset($categories[$key]);
                    break;
                }
            }
        }
        foreach ($categories as $cat) {
            $orderedCategories[] = $cat;
        }
        $categories = $orderedCategories;
    }

    if (empty($categories)) {
        $categories = [
            ['NameEn' => 'Food Delivery', 'Image' => ''],
            ['NameEn' => 'Groceries', 'Image' => ''],
            ['NameEn' => 'Pharmacy', 'Image' => ''],
            ['NameEn' => 'Electronics', 'Image' => ''],
            ['NameEn' => 'Fashion', 'Image' => '']
        ];
    }
}
?>

<style>
/* ─── HEADER ACTIONS & BUTTONS ─── */
.header-actions { display: flex; align-items: center; gap: 8px; }

.more-apps-btn {
    width: 42px; height: 42px;
    border-radius: 50%; background: transparent; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s; -webkit-appearance: none; appearance: none; flex-shrink: 0;
}
.more-apps-btn:hover { background: rgba(255,255,255,0.1); }
.grid-dots { display: grid; grid-template-columns: repeat(3,5px); gap: 3.5px; }
.grid-dots span { width:5px; height:5px; border-radius:50%; background:rgba(255,255,255,0.75); display:block; transition:background 0.2s; }
.more-apps-btn:hover .grid-dots span { background:#fff; }

.header-text-link {
    text-decoration: none; color: rgba(255, 255, 255, 0.85); font-size: 14px;
    font-weight: 600; padding: 6px 10px; border-radius: 8px; transition: all 0.2s ease; white-space: nowrap;
}
.header-text-link:hover { color: #fff; background: rgba(255, 255, 255, 0.1); }

@media (max-width: 768px) {
    .header-text-link { font-size: 13px; padding: 6px 8px; }
    .profile-link img { 
        width: 44px !important; 
        height: 44px !important; 
        border: 2.5px solid #f50057 !important; 
        box-shadow: 0 4px 14px rgba(245, 0, 87, 0.4) !important; 
    }
    .apps-panel {
        position: fixed !important;
        top: 75px !important;
        left: 16px !important;
        right: 16px !important;
        width: auto !important;
        max-height: calc(100vh - 100px) !important;
        transform-origin: top right;
    }
}

/* ─── APPS PANEL ─── */
.more-apps-container { position: relative; }
.apps-panel {
    position: absolute; top: calc(100% + 14px); right: 0;
    width: 356px; max-height: 82vh; overflow-y: auto; background: rgba(8,8,18,0.88);
    backdrop-filter: blur(48px) saturate(160%); -webkit-backdrop-filter: blur(48px) saturate(160%);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 0; z-index: 10000;
    box-shadow: 0 32px 100px rgba(0,0,0,0.85), 0 0 0 1px rgba(255,255,255,0.04), inset 0 1px 0 rgba(255,255,255,0.06);
    opacity: 0; transform: translateY(-12px) scale(0.96); pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16,1,0.3,1);
    scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.08) transparent;
}
.apps-panel.open { opacity:1; transform:translateY(0) scale(1); pointer-events:all; }

/* ─── LIQUID GLASS QOON PAY CARD ─── */
.panel-section { padding: 16px; }
.qpay-card {
    position: relative; overflow: hidden; border-radius: 22px; padding: 22px 20px 20px;
    display: flex; flex-direction: column;
    background: linear-gradient(135deg, rgba(255,255,255,0.14) 0%, rgba(255,255,255,0.04) 50%, rgba(255,255,255,0.09) 100%);
    backdrop-filter: blur(60px) saturate(200%); -webkit-backdrop-filter: blur(60px) saturate(200%);
    border: 1px solid rgba(255,255,255,0.22);
    box-shadow: inset 0 1.5px 0 rgba(255,255,255,0.25), inset 0 -1px 0 rgba(0,0,0,0.15), 0 20px 60px rgba(0,0,0,0.5), 0 0 0 0.5px rgba(255,255,255,0.06);
}
.qpay-card::before {
    content:''; position:absolute; width:210px; height:210px;
    background: radial-gradient(circle, rgba(245,0,87,0.65) 0%, transparent 70%);
    top:-65px; right:-55px; border-radius:50%; animation: qpayOrb1 7s ease-in-out infinite alternate; pointer-events:none; z-index:0;
}
.qpay-card::after {
    content:''; position:absolute; width:170px; height:170px;
    background: radial-gradient(circle, rgba(80,30,220,0.6) 0%, transparent 70%);
    bottom:-45px; left:-35px; border-radius:50%; animation: qpayOrb2 9s ease-in-out infinite alternate; pointer-events:none; z-index:0;
}
@keyframes qpayOrb1 { from{transform:translate(0,0) scale(1);} to{transform:translate(-25px,18px) scale(1.2);} }
@keyframes qpayOrb2 { from{transform:translate(0,0) scale(1);} to{transform:translate(18px,-25px) scale(1.25);} }
.qpay-card-shine {
    position:absolute; top:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg, transparent 10%, rgba(255,255,255,0.6) 50%, transparent 90%); pointer-events:none; z-index:3;
}
.qpay-card-inner { position:relative; z-index:2; }
.qpay-panel-logo { height:26px; width:auto; object-fit:contain; margin-bottom:16px; align-self:flex-start; filter:brightness(0) invert(1); opacity:0.92; }
.qpay-panel-logo-fallback { display:flex; align-items:center; gap:9px; font-size:15px; font-weight:700; color:#fff; margin-bottom:16px; letter-spacing:-0.3px; }
.qpay-panel-logo-fallback i { font-size:20px; color:#ff4d7d; }
.qpay-balance-label { font-size:10px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1.2px; margin-bottom:5px; }
.qpay-balance-amount { font-size:34px; font-weight:800; color:#fff; letter-spacing:-2px; margin-bottom:20px; text-shadow:0 4px 24px rgba(0,0,0,0.4); }
.qpay-actions { display:flex; gap:10px; }
.qpay-action-btn {
    flex:1; padding:12px 0; border-radius:14px; font-size:13px; font-weight:600; text-decoration:none;
    display:flex; align-items:center; justify-content:center; gap:7px;
    background:rgba(255,255,255,0.13); border:1px solid rgba(255,255,255,0.22); color:#fff;
    backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); box-shadow: inset 0 1px 0 rgba(255,255,255,0.2); transition: all 0.22s cubic-bezier(0.16,1,0.3,1);
}
.qpay-action-btn:hover { background:rgba(255,255,255,0.22); transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.3); }
.qpay-action-btn.topup:hover  { background:rgba(245,0,87,0.45); border-color:rgba(245,0,87,0.4); }
.qpay-action-btn.transfer:hover { background:rgba(44,181,232,0.4); border-color:rgba(44,181,232,0.35); }

/* ─── PANEL DIVIDER & GRID ─── */
.panel-divider { height:1px; background:rgba(255,255,255,0.07); margin:0 16px; }
.panel-section-title { font-size:11px; font-weight:600; color:rgba(255,255,255,0.38); text-transform:uppercase; letter-spacing:1px; margin-bottom:10px; }
.mini-apps-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:2px; }
.mini-app-item { display:flex; flex-direction:column; align-items:center; gap:7px; padding:12px 4px; border-radius:14px; text-decoration:none; transition:background 0.16s, transform 0.16s; }
.mini-app-item:hover { background:rgba(255,255,255,0.07); transform:translateY(-1px); }
.mini-app-icon { width:50px; height:50px; border-radius:14px; overflow:hidden; display:flex; align-items:center; justify-content:center; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.05); }
.mini-app-icon.icon-esim { background:linear-gradient(135deg,#060e22,#0e285e); box-shadow:0 4px 14px rgba(30,80,220,0.35); border-color:rgba(44,100,232,0.25); }
.mini-app-icon.icon-flights { background:linear-gradient(135deg,#040f26,#0b2155); box-shadow:0 4px 14px rgba(20,60,200,0.35); border-color:rgba(30,80,220,0.2); }
.mini-app-icon img { width:100%; height:100%; object-fit:cover; }
.mini-app-name { font-size:10px; color:rgba(255,255,255,0.68); text-align:center; line-height:1.3; max-width:60px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Sortable ghost */
.sortable-ghost { opacity: 0.4; }

/* ─── CHAT DRAWER ─── */
.chat-drawer-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000;
    opacity: 0; pointer-events: none; transition: opacity 0.3s;
    backdrop-filter: blur(5px);
}
.chat-drawer-overlay.open { opacity: 1; pointer-events: auto; }

.chat-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 50%; max-width: 500px;
    background: #000; z-index: 10001;
    transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: none; border-left: 1px solid rgba(255,255,255,0.1);
    display: flex; flex-direction: column;
}
@media (max-width: 768px) {
    .chat-drawer { width: 100%; max-width: none; }
}
.chat-drawer.open { transform: translateX(0); }

.chat-drawer-header {
    display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(10,10,10,0.95);
}
.chat-drawer-title { font-weight: 700; color: #fff; font-size: 18px; }
.chat-drawer-close { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; transition: 0.2s; }
.chat-drawer-close:hover { color: #f50057; transform: scale(1.1); }
</style>

<header style="width:100%; display:flex; justify-content:space-between; align-items:center; padding: 15px 20px; box-sizing: border-box; position: sticky; top: 0; z-index: 100; background: transparent;">
    <div class="logo-area">
        <a href="index.php">
            <img src="logo_qoon_white.png" alt="QOON Logo" style="height: 38px; width: auto; object-fit: contain;">
        </a>
    </div>
    <div class="header-actions">
        <?php if (isset($_COOKIE['qoon_user_id'])):
            $uName = $_COOKIE['qoon_user_name'] ?? 'User';
            $uPhoto = $_COOKIE['qoon_user_photo'] ?? '';
            $uPhotoUrl = "";
            if (!$uPhoto || $uPhoto == 'NONE' || $uPhoto == '0') {
                $uPhotoUrl = "https://ui-avatars.com/api/?name=" . urlencode($uName) . "&background=random&color=fff";
            } else {
                global $DomainNamee;
                $domain = $DomainNamee ?? 'https://qoon.app/dash/';
                if (strpos($uPhoto, 'http') !== false) {
                    $uPhotoUrl = $uPhoto;
                } else {
                    $uPhotoUrl = (strpos($uPhoto, 'photo/') !== false) ? $domain . $uPhoto : $domain . 'photo/' . $uPhoto;
                }
            }
        ?>
            <!-- Top Navigation Links -->
            <a href="javascript:void(0)" onclick="openOrdersDrawer()" class="header-text-link">Orders</a>
            <a href="javascript:void(0)" onclick="openChatDrawer()" class="header-text-link">Chat</a>

            <!-- Google-style More Button -->
            <div class="more-apps-container" id="moreAppsContainer">
                <button type="button" id="moreAppsBtn" class="more-apps-btn" title="More from QOON" aria-haspopup="true" aria-expanded="false">
                    <span class="grid-dots">
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                    </span>
                </button>
                <!-- The Panel -->
                <div class="apps-panel" id="appsPanel">
                    <!-- QOON Pay Section -->
                    <div class="panel-section">
                        <div class="qpay-card">
                            <div class="qpay-card-shine"></div>
                            <div class="qpay-card-inner">
                                <img src="qoon_pay_logo.png" alt="QOON Pay" class="qpay-panel-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="qpay-panel-logo-fallback" style="display:none;">
                                    <i class="fa-solid fa-wallet"></i>
                                    <span>QOON Pay</span>
                                </div>
                                <div class="qpay-balance-label">Available Balance</div>
                                <div class="qpay-balance-amount" id="headerPayBalance">0.00 MAD</div>
                                <div class="qpay-actions">
                                    <a href="javascript:void(0)" onclick="openQpayDrawer()" class="qpay-action-btn topup">
                                        <i class="fa-solid fa-plus"></i> Topup
                                    </a>
                                    <a href="javascript:void(0)" onclick="openQpayDrawer()" class="qpay-action-btn transfer">
                                        <i class="fa-solid fa-paper-plane"></i> Transfer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Divider -->
                    <div class="panel-divider"></div>
                    <!-- Mini Apps Section -->
                    <div class="panel-section">
                        <div class="panel-section-title" style="display:flex; justify-content:space-between; align-items:center;">
                            <span>Mini Apps</span>
                            <span style="font-size:9px; opacity:0.6; text-transform:none; letter-spacing:0;"><i class="fa-solid fa-arrows-up-down-left-right"></i> Drag to reorder</span>
                        </div>
                        <div class="mini-apps-grid" id="moreAppsGrid">
                            <?php
                            foreach ($categories as $pcat):
                                $pcTitle = htmlspecialchars($pcat['EnglishCategory'] ?? $pcat['ArabCategory'] ?? 'App');
                                $pcPhoto = htmlspecialchars($pcat['Photo'] ?? '');
                                
                                if ($pcat['CategoryId'] === 'flights') {
                                    $pcUrl = 'flights.php';
                                } elseif ($pcat['CategoryId'] === 'esims') {
                                    $pcUrl = 'esim.php';
                                } elseif (intval($pcat['CategoryId']) === 55 || stripos($pcTitle, 'Express') !== false) {
                                    $pcUrl = 'express.php';
                                } else {
                                    $pcUrl = (stripos($pcTitle, 'Kenz') !== false) ? 'kenz.php?cat=' . $pcat['CategoryId'] : 'category.php?cat=' . $pcat['CategoryId'];
                                }
                            ?>
                            <a href="<?= $pcUrl ?>" class="mini-app-item" data-id="<?= htmlspecialchars($pcat['CategoryId'] ?? '') ?>">
                                <div class="mini-app-icon">
                                    <img src="<?= $pcPhoto ?>" alt="<?= $pcTitle ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($pcTitle) ?>&background=2cb5e8&color=fff&size=48'">
                                </div>
                                <span class="mini-app-name"><?= $pcTitle ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Link -->
            <a href="javascript:void(0)" onclick="openProfileDrawer()" class="profile-link" style="padding: 2px; border-radius: 50%; text-decoration: none; display: inline-flex; margin-right: 8px;">
                <img src="<?= htmlspecialchars($uPhotoUrl) ?>" alt="Profile" style="margin: 0; width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(245, 0, 87, 0.6);">
            </a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="if(typeof openSignup==='function'){openSignup();}else{window.location.href='index.php?auth_required=1';}" class="signup-btn" style="padding: 10px 22px; border-radius: 99px; font-weight: 600; text-decoration: none; background: rgba(255,255,255,0.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); color: #fff; border: 1px solid rgba(255,255,255,0.18); font-size: 14px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><i class="fa-solid fa-right-to-bracket" style="font-size: 13px;"></i> Login</a>
        <?php endif; ?>
    </div>
</header>

<!-- Chat Drawer -->
<div id="chatDrawerOverlay" class="chat-drawer-overlay" onclick="closeChatDrawer()"></div>
<div id="chatDrawer" class="chat-drawer">
    <div class="chat-drawer-header">
        <div class="chat-drawer-title">Chat</div>
        <button class="chat-drawer-close" onclick="closeChatDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <iframe id="chatIframe" src="about:blank" style="width:100%; flex:1; border:none; background: transparent;"></iframe>
</div>

<!-- Orders Drawer -->
<div id="ordersDrawerOverlay" class="chat-drawer-overlay" onclick="closeOrdersDrawer()"></div>
<div id="ordersDrawer" class="chat-drawer">
    <div class="chat-drawer-header">
        <div class="chat-drawer-title">Orders</div>
        <button class="chat-drawer-close" onclick="closeOrdersDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <iframe id="ordersIframe" src="about:blank" style="width:100%; flex:1; border:none; background: transparent;"></iframe>
</div>

<!-- Profile Drawer -->
<div id="profileDrawerOverlay" class="chat-drawer-overlay" onclick="closeProfileDrawer()"></div>
<div id="profileDrawer" class="chat-drawer">
    <div class="chat-drawer-header">
        <div class="chat-drawer-title">Profile</div>
        <button class="chat-drawer-close" onclick="closeProfileDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <iframe id="profileIframe" src="about:blank" style="width:100%; flex:1; border:none; background: transparent;"></iframe>
</div>

<!-- QOON Pay Drawer -->
<div id="qpayDrawerOverlay" class="chat-drawer-overlay" onclick="closeQpayDrawer()"></div>
<div id="qpayDrawer" class="chat-drawer">
    <div class="chat-drawer-header">
        <div class="chat-drawer-title">QOON Pay</div>
        <button class="chat-drawer-close" onclick="closeQpayDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <iframe id="qpayIframe" src="about:blank" style="width:100%; flex:1; border:none; background: transparent;"></iframe>
</div>

<script>
/* ── More Apps Panel Script ── */
(function() {
    var moreBtn  = document.getElementById('moreAppsBtn');
    var panel    = document.getElementById('appsPanel');
    var moreWrap = document.getElementById('moreAppsContainer');
    if (!moreBtn || !panel) return;

    moreBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        var isOpen = panel.classList.contains('open');
        panel.classList.toggle('open', !isOpen);
        moreBtn.setAttribute('aria-expanded', String(!isOpen));

        // Load balance on first open
        if (!isOpen && !panel.dataset.balanceLoaded) {
            panel.dataset.balanceLoaded = '1';
            var balEl = document.getElementById('headerPayBalance');
            if (balEl) {
                fetch('qpay_balance.php')
                    .then(r => r.json())
                    .then(d => {
                        if (d && (d.balance !== undefined)) {
                            balEl.textContent = parseFloat(d.balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' MAD';
                        } else {
                            balEl.textContent = '0.00 MAD';
                        }
                    })
                    .catch(() => { balEl.textContent = '0.00 MAD'; });
            }
        }
    });

    // Sortable initialization
    var gridEl = document.getElementById('moreAppsGrid');

    function initSortable() {
        if (!gridEl) return;
        Sortable.create(gridEl, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                var items = gridEl.querySelectorAll('.mini-app-item');
                var order = [];
                items.forEach(function(item) {
                    var id = item.getAttribute('data-id');
                    if (id) order.push(id);
                });
                document.cookie = "qoon_cat_order=" + order.join(',') + "; expires=Thu, 31 Dec 2030 12:00:00 UTC; path=/";
                setTimeout(function() { window.location.reload(); }, 300);
            }
        });
    }

    if (typeof Sortable !== 'undefined') {
        initSortable();
    } else {
        // SortableJS not loaded on this page — load it now
        var sortScript = document.createElement('script');
        sortScript.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
        sortScript.onload = initSortable;
        document.head.appendChild(sortScript);
    }

    document.addEventListener('click', function(e) {
        if (moreWrap && !moreWrap.contains(e.target)) {
            panel.classList.remove('open');
            moreBtn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            panel.classList.remove('open');
            moreBtn.setAttribute('aria-expanded', 'false');
            closeChatDrawer();
        }
    });
})();

/* ── Chat Drawer Script ── */
function openChatDrawer() {
    document.getElementById('chatDrawerOverlay').classList.add('open');
    document.getElementById('chatDrawer').classList.add('open');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    var iframe = document.getElementById('chatIframe');
    if (!iframe.src || iframe.src === window.location.href || iframe.src === 'about:blank') {
        iframe.src = 'chat.php?iframe=1';
    }
}

function closeChatDrawer() {
    var overlay = document.getElementById('chatDrawerOverlay');
    var drawer = document.getElementById('chatDrawer');
    if (overlay && drawer) {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}

/* ── Orders Drawer Script ── */
function openOrdersDrawer() {
    document.getElementById('ordersDrawerOverlay').classList.add('open');
    document.getElementById('ordersDrawer').classList.add('open');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    var iframe = document.getElementById('ordersIframe');
    if (!iframe.src || iframe.src === window.location.href || iframe.src === 'about:blank') {
        iframe.src = 'orders.php?iframe=1';
    }
}

function closeOrdersDrawer() {
    var overlay = document.getElementById('ordersDrawerOverlay');
    var drawer = document.getElementById('ordersDrawer');
    if (overlay && drawer) {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}

/* ── Profile Drawer Script ── */
function openProfileDrawer() {
    document.getElementById('profileDrawerOverlay').classList.add('open');
    document.getElementById('profileDrawer').classList.add('open');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    var iframe = document.getElementById('profileIframe');
    if (!iframe.src || iframe.src === window.location.href || iframe.src === 'about:blank') {
        iframe.src = 'user-profile.php?iframe=1';
    }
}

function closeProfileDrawer() {
    var overlay = document.getElementById('profileDrawerOverlay');
    var drawer = document.getElementById('profileDrawer');
    if (overlay && drawer) {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}

/* ── QOON Pay Drawer Script ── */
function openQpayDrawer(view = '') {
    document.getElementById('qpayDrawerOverlay').classList.add('open');
    document.getElementById('qpayDrawer').classList.add('open');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    var iframe = document.getElementById('qpayIframe');
    var targetSrc = 'qpay.php?iframe=1';
    
    if (view === 'qr') targetSrc = 'qpay.php?iframe=1&view=qr';
    else if (view === 'topup') targetSrc = 'topup.php?iframe=1';
    else if (view === 'send') targetSrc = 'send.php?iframe=1';
    
    // Only update if src is different or if a specific view is requested
    if (iframe.src !== new URL(targetSrc, window.location.href).href || view) {
        iframe.src = targetSrc;
    }
}

function closeQpayDrawer() {
    var overlay = document.getElementById('qpayDrawerOverlay');
    var drawer = document.getElementById('qpayDrawer');
    if (overlay && drawer) {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}
</script>
