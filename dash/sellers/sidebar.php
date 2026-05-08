<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$totOrdersRes = $con->query("SELECT COUNT(*) as c FROM Orders WHERE ShopID = " . (int)$_SESSION['SellerID']);
$totalOrdersSidebar = $totOrdersRes ? $totOrdersRes->fetch_assoc()['c'] : 0;

$maxOrderRes = $con->query("SELECT MAX(OrderID) as max_id FROM Orders WHERE ShopID = " . (int)$_SESSION['SellerID']);
$initialMaxOrderId = $maxOrderRes ? (int)$maxOrderRes->fetch_assoc()['max_id'] : 0;

$nav_items = [
    ['url' => 'index.php',       'icon' => 'fas fa-th-large',     'label' => 'Dashboard'],
    ['url' => 'products.php',    'icon' => 'fas fa-box',          'label' => 'Products'],
    ['url' => 'orders.php',      'icon' => 'fas fa-shopping-bag', 'label' => 'Orders', 'badge' => $totalOrdersSidebar],
    ['url' => 'content.php',     'icon' => 'fas fa-photo-video',  'label' => 'Content'],
    ['url' => 'boosts.php',      'icon' => 'fas fa-rocket',       'label' => 'Boosts'],
    ['url' => 'wallet.php',      'icon' => 'fas fa-wallet',       'label' => 'Wallet'],
    ['url' => 'store_setup.php', 'icon' => 'fas fa-store-alt',    'label' => 'Online Store'],
    ['url' => 'social.php',      'icon' => 'fas fa-share-nodes',  'label' => 'Social Sync'],
    ['url' => 'settings.php',    'icon' => 'fas fa-cog',          'label' => 'Settings'],
];

// Bottom bar: 4 quick items + More
$bottomItems = [
    $nav_items[0], // Dashboard
    $nav_items[2], // Orders
    $nav_items[1], // Products
    $nav_items[3], // Content
];
$drawerPages = array_column($nav_items, 'url');
$bottomPages = array_column($bottomItems, 'url');
$currentInDrawer = in_array($currentPage, array_diff($drawerPages, $bottomPages));

$shopName    = $SHOP_DATA['ShopName'] ?? 'My Store';
$shopLogo    = $SHOP_DATA['ShopLogo'] ?? '';
$shopInitial = strtoupper(substr($shopName, 0, 1));
?>
<style>
/* ════════════════════════════════════════
   DESKTOP SIDEBAR
════════════════════════════════════════ */
:root {
    --sb-width: 240px;
    --sb-bg: #FFFFFF;
    --sb-border: #E5E7EB;
    --sb-text: #6B7280;
    --sb-hi: #F3F4F6;
    --sb-strong: #111827;
}

/* ── Canonical Layout — overrides any per-page variation ── */
html, body {
    height: 100%;
    margin: 0;
    overflow: hidden;
    background: #F8FAFC;
}
/* Works whether the page uses body-flex or .app-envelope */
.app-envelope {
    display: flex !important;
    width: 100% !important;
    height: 100vh !important;
    overflow: hidden !important;
}
.main-panel {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    height: 100vh !important;
    min-width: 0 !important;
    /* Compensate for fixed sidebar */
    margin-left: var(--sb-width) !important;
}

/* Sidebar: fixed to guarantee same position on every page */
.sidebar {
    width: var(--sb-width);
    background: var(--sb-bg);
    border-right: 1px solid var(--sb-border);
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    overflow: hidden;
}

.logo-box {
    padding: 24px 20px 16px;
    display: flex; align-items: center;
    text-decoration: none;
}
.logo-box img { max-height: 40px; object-fit: contain; }

.nav-section { padding: 0 10px; display: flex; flex-direction: column; gap: 2px; flex: 1; overflow-y: auto; }
.nav-section::-webkit-scrollbar { display: none; }
.nav-label {
    font-size: 10px; font-weight: 700; color: var(--sb-text);
    text-transform: uppercase; letter-spacing: 0.8px;
    padding: 10px 14px 6px;
}
.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 9px;
    color: var(--sb-text); text-decoration: none;
    font-size: 13.5px; font-weight: 600;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
}
.nav-item i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
.nav-item:hover { background: var(--sb-hi); color: var(--sb-strong); }
.nav-item.active { background: var(--sb-strong); color: #fff; }
.nav-item.active:hover { background: #1F2937; }
.nav-item.nav-danger { color: #EF4444; }
.nav-item.nav-danger:hover { background: #FEF2F2; color: #DC2626; }

.sb-footer {
    padding: 10px;
    border-top: 1px solid var(--sb-border);
    flex-shrink: 0;
}
.sb-user-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 9px;
    background: var(--sb-hi); margin-bottom: 4px;
}
.sb-user-avatar {
    width: 30px; height: 30px; border-radius: 8px;
    overflow: hidden; flex-shrink: 0; background: var(--sb-strong);
    display: flex; align-items: center; justify-content: center;
}
.sb-user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.sb-user-avatar span { color: #fff; font-size: 12px; font-weight: 700; }
.sb-user-name { font-size: 13px; font-weight: 700; color: var(--sb-strong); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ════════════════════════════════════════
   MOBILE — hidden by default
════════════════════════════════════════ */
#mob-tabbar       { display: none; }
#mob-drawer-overlay { display: none; }
#mob-drawer       { display: none; }

/* ════════════════════════════════════════
   MOBILE BREAKPOINT
════════════════════════════════════════ */
@media (max-width: 900px) {

    .sidebar { display: none !important; }
    /* remove the margin-left compensation on mobile */
    .main-panel { margin-left: 0 !important; padding-bottom: 80px !important; }

    /* ── Bottom Tab Bar ── */
    #mob-tabbar {
        display: flex;
        position: fixed; bottom: 0; left: 0; right: 0;
        z-index: 1000;
        height: 68px;
        background: rgba(255,255,255,0.97);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid var(--sb-border);
        align-items: center;
        padding: 0 4px;
        box-shadow: 0 -4px 24px rgba(0,0,0,0.06);
    }

    .mob-tab {
        flex: 1;
        display: flex; flex-direction: column;
        align-items: center; gap: 3px;
        padding: 8px 4px; border-radius: 12px;
        color: var(--sb-text); text-decoration: none;
        font-size: 10px; font-weight: 600;
        transition: 0.15s; cursor: pointer;
        background: none; border: none;
        font-family: inherit;
        -webkit-tap-highlight-color: transparent;
    }
    .mob-tab i { font-size: 19px; transition: 0.15s; }
    .mob-tab.active { color: var(--sb-strong); }
    .mob-tab.active i {
        background: var(--sb-strong); color: #fff;
        padding: 6px 14px; border-radius: 20px; font-size: 15px;
    }
    .mob-tab:hover { color: var(--sb-strong); }

    /* ── Overlay ── */
    #mob-drawer-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 1100;
        background: rgba(0,0,0,0.35);
        backdrop-filter: blur(4px);
    }
    #mob-drawer-overlay.open { display: block; }

    /* ── Slide-up Drawer ── */
    #mob-drawer {
        display: block;
        position: fixed; left: 0; right: 0; bottom: 0;
        z-index: 1200;
        background: #fff;
        border-radius: 24px 24px 0 0;
        padding: 12px 0 calc(env(safe-area-inset-bottom, 0px) + 24px);
        box-shadow: 0 -8px 40px rgba(0,0,0,0.12);
        transform: translateY(100%);
        transition: transform 0.36s cubic-bezier(0.32,0.72,0,1);
    }
    #mob-drawer.open { transform: translateY(0); }

    .drawer-handle {
        width: 36px; height: 4px; background: #E5E7EB;
        border-radius: 4px; margin: 0 auto 16px;
    }

    /* Drawer header — shop identity */
    .drawer-header {
        display: flex; align-items: center; gap: 12px;
        padding: 0 20px 14px;
        border-bottom: 1px solid var(--sb-border);
        margin-bottom: 8px;
    }
    .drawer-avatar {
        width: 40px; height: 40px; border-radius: 10px;
        overflow: hidden; flex-shrink: 0;
        background: var(--sb-strong);
        display: flex; align-items: center; justify-content: center;
    }
    .drawer-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .drawer-avatar span { color: #fff; font-size: 15px; font-weight: 700; }
    .drawer-uname { font-size: 15px; font-weight: 700; color: var(--sb-strong); }
    .drawer-role  { font-size: 12px; font-weight: 500; color: var(--sb-text); }

    /* 2-column grid */
    .drawer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 8px 16px;
    }
    .drawer-item {
        display: flex; flex-direction: column;
        align-items: center; gap: 7px;
        padding: 16px 12px; border-radius: 14px;
        background: var(--sb-hi);
        color: var(--sb-text); text-decoration: none;
        font-size: 13px; font-weight: 600;
        text-align: center;
        transition: 0.15s;
        border: 1px solid transparent;
        -webkit-tap-highlight-color: transparent;
    }
    .drawer-item i { font-size: 20px; }
    .drawer-item:hover { background: #E9E9E9; color: var(--sb-strong); }
    .drawer-item.active { background: var(--sb-strong); color: #fff; border-color: var(--sb-strong); }
    .drawer-item:active { transform: scale(0.97); }

    /* Footer actions */
    .drawer-footer-actions {
        display: flex; gap: 8px;
        padding: 12px 16px 0;
        border-top: 1px solid var(--sb-border);
        margin-top: 8px;
    }
    .drawer-action-btn {
        flex: 1; display: flex; align-items: center;
        justify-content: center; gap: 8px;
        padding: 12px; border-radius: 12px;
        font-size: 13px; font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--sb-border);
        background: var(--sb-hi); color: var(--sb-text);
        cursor: pointer; font-family: inherit;
        transition: 0.15s;
        -webkit-tap-highlight-color: transparent;
    }
    .drawer-action-btn.danger { color: #EF4444; }
    .drawer-action-btn:hover { background: #E9E9E9; }
    .drawer-action-btn.danger:hover { background: #FEF2F2; border-color: #FECACA; }

    body { padding-bottom: 68px !important; }
}

@media (min-width: 901px) {
    #mob-tabbar, #mob-drawer-overlay, #mob-drawer { display: none !important; }
}
</style>

<!-- DESKTOP SIDEBAR -->
<div class="sidebar">
    <a href="index.php" class="logo-box">
        <img src="../images/logo.png" alt="QOON">
    </a>

    <div class="nav-section">
        <div class="nav-label">Overview</div>
        <?php foreach ($nav_items as $item):
            $active = ($currentPage === $item['url']) ? 'active' : '';
        ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $active ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <span style="flex: 1;"><?= $item['label'] ?></span>
            <?php if (isset($item['badge'])): ?>
            <span style="background: #10B981; color: #FFF; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 800;"><?= $item['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="sb-footer">
        <div class="sb-user-row">
            <div class="sb-user-avatar">
                <?php if ($shopLogo): ?>
                <img src="<?= htmlspecialchars($shopLogo) ?>" onerror="this.style.display='none';this.nextSibling.style.display='flex';">
                <span style="display:none;"><?= $shopInitial ?></span>
                <?php else: ?>
                <span><?= $shopInitial ?></span>
                <?php endif; ?>
            </div>
            <span class="sb-user-name"><?= htmlspecialchars($shopName) ?></span>
        </div>
        <a href="logout.php" class="nav-item nav-danger">
            <i class="fas fa-power-off"></i><span>Logout</span>
        </a>
    </div>
</div>

<!-- MOBILE BOTTOM TAB BAR -->
<div id="mob-tabbar">
    <?php foreach ($bottomItems as $item):
        $active = ($currentPage === $item['url']) ? 'active' : '';
    ?>
    <a href="<?= $item['url'] ?>" class="mob-tab <?= $active ?>">
        <i class="<?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
    <button class="mob-tab <?= $currentInDrawer ? 'active' : '' ?>" onclick="openDrawer()" id="mob-more-btn" type="button">
        <i class="fas fa-grid-2"></i>
        <span>More</span>
    </button>
</div>

<!-- MOBILE OVERLAY -->
<div id="mob-drawer-overlay" onclick="closeDrawer()"></div>

<!-- MOBILE SLIDE-UP DRAWER -->
<div id="mob-drawer">
    <div class="drawer-handle"></div>

    <!-- Shop identity header -->
    <div class="drawer-header">
        <div class="drawer-avatar">
            <?php if ($shopLogo): ?>
            <img src="<?= htmlspecialchars($shopLogo) ?>" onerror="this.style.display='none';this.nextSibling.style.display='flex';">
            <span style="display:none;"><?= $shopInitial ?></span>
            <?php else: ?>
            <span><?= $shopInitial ?></span>
            <?php endif; ?>
        </div>
        <div>
            <div class="drawer-uname"><?= htmlspecialchars($shopName) ?></div>
            <div class="drawer-role">Seller Dashboard</div>
        </div>
    </div>

    <!-- All nav items in 2-col grid -->
    <div class="drawer-grid">
        <?php foreach ($nav_items as $item):
            $active = ($currentPage === $item['url']) ? 'active' : '';
        ?>
        <a href="<?= $item['url'] ?>" class="drawer-item <?= $active ?>" onclick="closeDrawer()" style="position: relative;">
            <i class="<?= $item['icon'] ?>"></i><?= $item['label'] ?>
            <?php if (isset($item['badge'])): ?>
            <span style="position: absolute; top: 8px; right: 8px; background: #10B981; color: #FFF; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: 800;"><?= $item['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="drawer-footer-actions">
        <button class="drawer-action-btn" onclick="closeDrawer()">
            <i class="fas fa-times"></i> Close
        </button>
        <a href="logout.php" class="drawer-action-btn danger">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</div>

<script>
function openDrawer() {
    document.getElementById('mob-drawer').classList.add('open');
    document.getElementById('mob-drawer-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDrawer() {
    document.getElementById('mob-drawer').classList.remove('open');
    document.getElementById('mob-drawer-overlay').classList.remove('open');
    document.body.style.overflow = '';
}
// Swipe down to close
(function() {
    let sy = 0;
    const d = document.getElementById('mob-drawer');
    d.addEventListener('touchstart', e => { sy = e.touches[0].clientY; }, { passive: true });
    d.addEventListener('touchend',   e => { if (e.changedTouches[0].clientY - sy > 60) closeDrawer(); }, { passive: true });
})();
</script>

<!-- Global Notification System for New Orders -->
<style>
#global-notification-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column-reverse; /* New ones stack on top */
    gap: 12px;
    pointer-events: none;
}
.global-toast {
    width: 340px;
    background: #FFF;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    border-left: 6px solid #10B981; /* Green for new orders */
    overflow: hidden;
    display: flex;
    flex-direction: column;
    pointer-events: auto;
    transform: translateX(120%);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
    cursor: pointer;
}
.global-toast.show {
    transform: translateX(0);
    opacity: 1;
}
.global-toast:hover {
    background: #F8FAFC;
}
.global-toast-content {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.global-toast-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #D1FAE5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(16,185,129,0.2);
}
.global-toast-text h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--sb-strong, #111827);
}
.global-toast-text p {
    margin: 0;
    font-size: 13px;
    color: var(--sb-text, #6B7280);
    font-weight: 600;
}
.global-toast-progress-bar {
    height: 4px;
    background: #F1F5F9;
    width: 100%;
}
.global-toast-progress {
    height: 100%;
    background: #10B981;
    width: 100%;
}
/* Mobile positioning */
@media (max-width: 900px) {
    #global-notification-container {
        top: 24px;
        bottom: auto;
        left: 50%;
        right: auto;
        transform: translateX(-50%);
        flex-direction: column;
    }
    .global-toast {
        transform: translateY(-120%);
    }
    .global-toast.show {
        transform: translateY(0);
    }
}
</style>

<div id="global-notification-container"></div>

<script>
let lastSeenOrderId = <?= (int)$initialMaxOrderId ?>;
const checkIntervalMs = 15000; // Poll every 15 seconds
let activeNotificationAudio = null;

function checkNewOrders() {
    // Only check if we have an initialized last ID
    if (lastSeenOrderId === 0) return;
    
    fetch(`api_poll_orders.php?last_id=${lastSeenOrderId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.new_orders && data.new_orders.length > 0) {
                data.new_orders.forEach(oid => {
                    showNewOrderNotification(oid);
                    if (oid > lastSeenOrderId) {
                        lastSeenOrderId = oid;
                    }
                });
            }
        })
        .catch(err => console.error("Order poll error", err));
}

setInterval(checkNewOrders, checkIntervalMs);

function showNewOrderNotification(orderId) {
    const container = document.getElementById('global-notification-container');
    
    const toast = document.createElement('div');
    toast.className = 'global-toast';
    toast.onclick = () => {
        if(activeNotificationAudio) {
            activeNotificationAudio.pause();
            activeNotificationAudio = null;
        }
        window.location.href = `order_detail.php?id=${orderId}`;
    };
    
    toast.innerHTML = `
        <div class="global-toast-content">
            <div class="global-toast-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="global-toast-text">
                <h4>New Order Received!</h4>
                <p>Order #${orderId} requires your attention.</p>
            </div>
        </div>
        <div class="global-toast-progress-bar">
            <div class="global-toast-progress"></div>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Animate in
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
    });

    // Play looping notification sound
    try {
        if(!activeNotificationAudio) {
            activeNotificationAudio = new Audio('https://cdn.freesound.org/previews/415/415038_1676145-lq.mp3');
            activeNotificationAudio.volume = 0.8;
            activeNotificationAudio.loop = true; // Loop the sound
            activeNotificationAudio.play().catch(e => { console.log('Autoplay blocked'); }); 
        }
    } catch(e) {}

    // Animate progress bar (60 seconds)
    const progressBar = toast.querySelector('.global-toast-progress');
    progressBar.style.transition = 'width 60s linear';
    
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            progressBar.style.width = '0%';
        });
    });
    
    // Auto-remove after 60 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400); // Wait for CSS transition
        
        // Stop audio if no other toasts are visible
        if(document.querySelectorAll('.global-toast.show').length === 0) {
            if(activeNotificationAudio) {
                activeNotificationAudio.pause();
                activeNotificationAudio = null;
            }
        }
    }, 60000);
}
</script>
