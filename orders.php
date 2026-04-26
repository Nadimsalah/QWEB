<?php
require_once 'conn.php';

$isIframe = isset($_GET['iframe']) && $_GET['iframe'] == '1';

// Check Auth
$userId = $_COOKIE['qoon_user_id'] ?? null;
$authRequired = false;

if (!$userId) {
    if ($isIframe) {
        $authRequired = true;
    } else {
        header("Location: index.php?auth_required=1");
        exit;
    }
}

$uName = $_COOKIE['qoon_user_name'] ?? 'User';
$uPhoto = $_COOKIE['qoon_user_photo'] ?? '';
if(!$uPhoto || $uPhoto == 'NONE' || $uPhoto == '0') $uPhoto = "https://ui-avatars.com/api/?name=".urlencode($uName)."&background=random&color=fff";

// Fetch Orders
$orders = [];
if (!$authRequired) {
    $query = "SELECT o.*, s.ShopName, s.ShopLogo 
              FROM Orders o 
              LEFT JOIN Shops s ON o.ShopID = s.ShopID 
              WHERE o.UserID = '$userId' 
              ORDER BY o.OrderID DESC 
              LIMIT 100";
    $res = $con->query($query);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}

// Normalize status to a consistent category
function normalizeStatus($raw) {
    $s = strtoupper(trim($raw ?? ''));
    if (in_array($s, ['DONE', 'FINISH', 'RATED', 'DELIVERED', 'COMPLETED'])) return 'delivered';
    if (in_array($s, ['CANCELLED', 'CANCELED', 'CANCEL'])) return 'cancelled';
    if (in_array($s, ['DOING', 'ON_WAY', 'ONWAY', 'PICKED', 'INPROGRESS', 'IN_PROGRESS', 'ACCEPTED'])) return 'doing';
    if (in_array($s, ['DRIVER_OFFER', 'DRIVER_OFFERED', 'OFFER', 'OFFERED'])) return 'driver_offer';
    return 'waiting'; // default
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --accent-glow: #7b00ff;
            --accent-cyan: #00d4ff;
            --text-muted: rgba(255, 255, 255, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #010008; color: #fff; min-height: 100vh; overflow-x: hidden; }

        #space {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -3; pointer-events: none;
            background: radial-gradient(circle at center, #0a001a 0%, #010008 100%);
        }

        .container { max-width: 680px; margin: 0 auto; padding: 80px 20px 60px; }

        /* ── Filter Pills ── */
        .filter-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 24px;
            scrollbar-width: none;
        }
        .filter-scroll::-webkit-scrollbar { display: none; }

        .filter-pill {
            flex-shrink: 0;
            padding: 8px 16px;
            border-radius: 99px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            user-select: none;
        }
        .filter-pill:hover { border-color: rgba(255,255,255,0.3); color: #fff; }
        .filter-pill.active {
            border-color: transparent;
            color: #fff;
        }
        .filter-pill[data-filter="all"].active       { background: rgba(255,255,255,0.15); }
        .filter-pill[data-filter="waiting"].active   { background: rgba(255, 159, 10, 0.25); border-color: rgba(255,159,10,0.4); color: #ff9f0a; }
        .filter-pill[data-filter="driver_offer"].active { background: rgba(44,181,232,0.2); border-color: rgba(44,181,232,0.4); color: #2cb5e8; }
        .filter-pill[data-filter="doing"].active     { background: rgba(123,0,255,0.25); border-color: rgba(123,0,255,0.4); color: #b87eff; }
        .filter-pill[data-filter="delivered"].active { background: rgba(52,199,89,0.2); border-color: rgba(52,199,89,0.4); color: #34c759; }
        .filter-pill[data-filter="cancelled"].active { background: rgba(255,59,48,0.2); border-color: rgba(255,59,48,0.4); color: #ff3b30; }

        .pill-count {
            background: rgba(255,255,255,0.15);
            padding: 1px 7px;
            border-radius: 99px;
            font-size: 11px;
        }

        /* ── Order Cards ── */
        .order-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none;
            color: inherit;
        }
        .order-card:hover {
            transform: translateY(-4px) scale(1.01);
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.15);
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .order-card.hidden { display: none; }

        .shop-icon { width: 52px; height: 52px; border-radius: 14px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0; }
        .order-info { flex: 1; min-width: 0; }
        .shop-name { font-size: 16px; font-weight: 700; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .order-date { font-size: 12px; color: var(--text-muted); }

        .order-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
        .order-price { font-size: 15px; font-weight: 800; }

        .order-status {
            padding: 4px 11px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .status-waiting    { background: rgba(255,159,10,0.15); color: #ff9f0a; }
        .status-driver_offer { background: rgba(44,181,232,0.15); color: #2cb5e8; }
        .status-doing      { background: rgba(123,0,255,0.2); color: #b87eff; }
        .status-delivered  { background: rgba(52,199,89,0.15); color: #34c759; }
        .status-cancelled  { background: rgba(255,59,48,0.15); color: #ff3b30; }

        /* ── Empty State ── */
        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-state i { font-size: 52px; margin-bottom: 20px; display: block; opacity: 0.2; }
        .empty-state h2 { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); }

        #no-results-msg { display: none; text-align: center; padding: 50px 20px; color: var(--text-muted); font-size: 15px; }

        /* ── Re-order Button ── */
        .reorder-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 99px;
            background: rgba(52,199,89,0.12); color: #34c759;
            border: 1px solid rgba(52,199,89,0.3);
            font-size: 11px; font-weight: 800; cursor: pointer;
            transition: all 0.2s; white-space: nowrap;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .reorder-btn:hover { background: rgba(52,199,89,0.22); border-color: rgba(52,199,89,0.6); transform: scale(1.04); }
        .reorder-btn i { font-size: 11px; }

        /* ── Re-order Modal ── */
        #reorder-overlay {
            position: fixed; inset: 0; z-index: 999999;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(10px);
            display: none; align-items: flex-end; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        }
        #reorder-modal {
            width: 100%; max-width: 520px; max-height: 85vh;
            background: #0c0c14;
            border-top-left-radius: 28px; border-top-right-radius: 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex; flex-direction: column;
            transform: translateY(60px);
            transition: transform 0.4s cubic-bezier(0.16,1,0.3,1);
            box-shadow: 0 -20px 60px rgba(0,0,0,0.9);
            overflow: hidden;
        }
        .rom-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .rom-title { font-size: 18px; font-weight: 800; color: #fff; }
        .rom-close {
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(255,255,255,0.07); border: none; color: #fff;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 15px; transition: 0.2s;
        }
        .rom-close:hover { background: rgba(255,255,255,0.14); }
        .rom-body { flex: 1; overflow-y: auto; padding: 16px 20px; }
        .rom-body::-webkit-scrollbar { width: 3px; }
        .rom-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }
        .rom-item {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 14px; border-radius: 18px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 10px;
        }
        .rom-item-img {
            width: 58px; height: 58px; border-radius: 12px;
            object-fit: cover; background: #1a1a1a; flex-shrink: 0;
        }
        .rom-item-no-img {
            width: 58px; height: 58px; border-radius: 12px;
            background: #1a1a1a; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.2); font-size: 20px;
        }
        .rom-item-name { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 3px; }
        .rom-item-price { font-size: 13px; font-weight: 800; color: #34c759; }
        .rom-item-qty {
            margin-left: auto; background: rgba(255,255,255,0.08);
            border-radius: 99px; padding: 4px 12px;
            font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .rom-footer {
            padding: 14px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            background: #0c0c14; flex-shrink: 0;
        }
        .rom-confirm-btn {
            width: 100%; padding: 16px; border-radius: 18px; border: none;
            background: #34c759; color: #000;
            font-size: 16px; font-weight: 800; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.2s; box-shadow: 0 6px 24px rgba(52,199,89,0.3);
        }
        .rom-confirm-btn:hover { background: #2ebd52; transform: translateY(-1px); }
        .rom-confirm-btn:active { transform: scale(0.98); }
        .rom-confirm-btn:disabled { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.3); box-shadow: none; cursor: not-allowed; }

        <?php if ($isIframe): ?>
        body { background: transparent !important; }
        .container { padding-top: 16px; padding-bottom: 30px; }
        #space { display: none; }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php if (!$isIframe): ?>
    <canvas id="space"></canvas>
    <?php require_once 'includes/header.php'; ?>
    <?php endif; ?>

    <div class="container">
        <?php if (!$isIframe): ?>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:30px;">
            <a href="index.php" style="width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:all 0.3s;">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 style="font-size:26px; font-weight:800;">My Orders</h1>
        </div>
        <?php else: ?>
        <div style="margin-bottom: 20px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-bag-shopping" style="font-size:20px; color:var(--accent-glow);"></i>
            <span style="font-size:18px; font-weight:800;">My Orders</span>
        </div>
        <?php endif; ?>

        <?php if ($authRequired): ?>
            <div class="empty-state">
                <i class="fa-solid fa-lock"></i>
                <h2>Sign in Required</h2>
                <p style="margin-bottom: 28px;">Please sign in to view your order history.</p>
                <a href="javascript:void(0)" onclick="window.parent.location.href='index.php?auth_required=1'"
                   style="display:inline-block;padding:14px 32px;background:#7b00ff;color:#fff;border-radius:99px;text-decoration:none;font-weight:700;">
                   Sign In Now
                </a>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h2>No orders yet</h2>
                <p>Start exploring the best stores around you!</p>
                <br>
                <a href="javascript:void(0)" onclick="<?= $isIframe ? "window.parent.closeOrdersDrawer()" : "window.location.href='index.php'" ?>"
                   style="display:inline-block;margin-top:16px;padding:12px 28px;background:rgba(255,255,255,0.08);color:#fff;border-radius:99px;text-decoration:none;font-weight:600;">
                   Browse Stores
                </a>
            </div>
        <?php else: ?>

            <?php
            // Count per category for pills
            $counts = ['all' => count($orders), 'waiting' => 0, 'driver_offer' => 0, 'doing' => 0, 'delivered' => 0, 'cancelled' => 0];
            foreach ($orders as $o) {
                $ns = normalizeStatus($o['OrderState'] ?? $o['OrderStatus'] ?? '');
                if (isset($counts[$ns])) $counts[$ns]++;
            }
            $filterDefs = [
                'all'          => ['label' => 'All',            'icon' => 'fa-layer-group'],
                'waiting'      => ['label' => 'Waiting',        'icon' => 'fa-clock'],
                'driver_offer' => ['label' => 'Driver Offer',   'icon' => 'fa-car'],
                'doing'        => ['label' => 'In Progress',    'icon' => 'fa-spinner'],
                'delivered'    => ['label' => 'Delivered',      'icon' => 'fa-circle-check'],
                'cancelled'    => ['label' => 'Cancelled',      'icon' => 'fa-xmark-circle'],
            ];
            ?>

            <!-- Status Filter Pills -->
            <div class="filter-scroll" id="filterRow">
                <?php foreach ($filterDefs as $key => $def):
                    if ($key !== 'all' && ($counts[$key] ?? 0) === 0) continue; ?>
                <div class="filter-pill <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= $key ?>" onclick="filterOrders('<?= $key ?>', this)">
                    <i class="fa-solid <?= $def['icon'] ?>"></i>
                    <?= $def['label'] ?>
                    <span class="pill-count"><?= $key === 'all' ? $counts['all'] : $counts[$key] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Orders List -->
            <div id="ordersList">
                <?php foreach ($orders as $order):
                    $sName = $order['ShopName'] ?: ($order['DestinationName'] ?: 'QOON Order');
                    $sLogo = $order['ShopLogo'] ?: "https://ui-avatars.com/api/?name=".urlencode($sName)."&background=111&color=fff";
                    $ns = normalizeStatus($order['OrderState'] ?? $order['OrderStatus'] ?? '');

                    $statusLabels = [
                        'waiting'      => 'Waiting',
                        'driver_offer' => 'Driver Offer',
                        'doing'        => 'In Progress',
                        'delivered'    => 'Delivered',
                        'cancelled'    => 'Cancelled',
                    ];
                    $statusText = $statusLabels[$ns] ?? ucfirst($ns);
                    $total = floatval($order['OrderPriceFromShop'] ?? 0) + floatval($order['OrderPrice'] ?? 0) + floatval($order['PlatformFee'] ?? 0);
                    $date = date('M j, Y', strtotime($order['CreatedAtOrders'] ?? 'now'));
                ?>
                <div class="order-card" data-status="<?= $ns ?>" style="cursor:default;">
                    <a href="track_order.php?orderId=<?= $order['OrderID'] ?>&tot=<?= $total ?>" style="display:contents; text-decoration:none; color:inherit;">
                        <img src="<?= htmlspecialchars($sLogo) ?>" alt="Shop" class="shop-icon"
                             onerror="this.src='https://ui-avatars.com/api/?name=S&background=111&color=fff'">
                        <div class="order-info">
                            <div class="shop-name"><?= htmlspecialchars($sName) ?></div>
                            <div class="order-date"><?= $date ?> &nbsp;·&nbsp; #<?= $order['OrderID'] ?></div>
                        </div>
                    </a>
                    <div class="order-right">
                        <div class="order-price"><?= number_format($total, 2) ?> MAD</div>
                        <div class="order-status status-<?= $ns ?>"><?= $statusText ?></div>
                        <?php if (in_array($ns, ['delivered','cancelled'])): ?>
                        <button class="reorder-btn" onclick="event.stopPropagation(); openReorderModal(<?= $order['OrderID'] ?>, <?= $order['ShopID'] ?>, '<?= addslashes(htmlspecialchars($sName)) ?>')">
                            <i class="fa-solid fa-rotate-right"></i> Re-order
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="no-results-msg">
                <i class="fa-solid fa-filter" style="font-size:36px;opacity:0.2;display:block;margin-bottom:12px;"></i>
                No orders in this category
            </div>

        <?php endif; ?>
    </div>

    <!-- RE-ORDER MODAL -->
    <div id="reorder-overlay" onclick="if(event.target===this) closeReorderModal()">
        <div id="reorder-modal">
            <div class="rom-header">
                <div class="rom-title"><i class="fa-solid fa-rotate-right" style="color:#34c759;margin-right:8px;"></i> Re-order</div>
                <button class="rom-close" onclick="closeReorderModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="rom-body" id="rom-body">
                <div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                </div>
            </div>
            <div class="rom-footer">
                <button class="rom-confirm-btn" id="rom-confirm-btn" onclick="confirmReorder()" disabled>
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span id="rom-confirm-label">Add to Cart</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        /* ── Re-order Logic ── */
        let _romShopId = null;
        let _romItems  = [];

        function openReorderModal(orderId, shopId, shopName) {
            _romShopId = shopId;
            _romItems  = [];
            const overlay = document.getElementById('reorder-overlay');
            const modal   = document.getElementById('reorder-modal');
            const body    = document.getElementById('rom-body');
            const btn     = document.getElementById('rom-confirm-btn');

            body.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i><div style="margin-top:14px;font-size:13px;">Loading items...</div></div>';
            btn.disabled = true;
            document.getElementById('rom-confirm-label').textContent = 'Add to Cart';

            overlay.style.display = 'flex';
            setTimeout(() => { overlay.style.opacity = '1'; modal.style.transform = 'translateY(0)'; }, 10);
            document.body.style.overflow = 'hidden';

            const fd = new FormData();
            fd.append('OrderID', orderId);
            fetch('GetOneOrdersDetails.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    if (!json.success || !json.data) throw new Error('No data');
                    const order = json.data;
                    const foods  = order.Food || [];

                    if (foods.length === 0) {
                        body.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.4);"><i class="fa-solid fa-box-open" style="font-size:36px;opacity:0.3;display:block;margin-bottom:12px;"></i>No items found for this order.</div>';
                        return;
                    }

                    // Build cart items from food list
                    _romItems = foods.map(f => ({
                        id:         f.FoodID,
                        name:       f.FoodName || 'Product',
                        unitPrice:  parseFloat(f.OrderFoodPrice || f.FoodOfferPrice || f.FoodPrice || 0),
                        totalPrice: parseFloat(f.OrderFoodPrice || f.FoodOfferPrice || f.FoodPrice || 0) * parseInt(f.FoodQnt || 1),
                        qty:        parseInt(f.FoodQnt || 1),
                        img:        f.FoodPhoto && f.FoodPhoto !== 'NONE' && f.FoodPhoto !== '0'
                                        ? (f.FoodPhoto.startsWith('http') ? f.FoodPhoto : 'https://qoon.app/userDriver/UserDriverApi/photo/' + f.FoodPhoto)
                                        : '',
                        size:  null,
                        color: null,
                        extras: []
                    }));

                    let html = '';
                    _romItems.forEach(item => {
                        html += `<div class="rom-item">
                            ${ item.img
                                ? `<img src="${item.img}" class="rom-item-img" onerror="this.style.display='none'">`
                                : `<div class="rom-item-no-img"><i class="fa-solid fa-image"></i></div>`
                            }
                            <div style="flex:1;min-width:0;">
                                <div class="rom-item-name">${item.name}</div>
                                <div class="rom-item-price">${item.unitPrice.toFixed(2)} MAD</div>
                            </div>
                            <div class="rom-item-qty">×${item.qty}</div>
                        </div>`;
                    });

                    const subtotal = _romItems.reduce((s, i) => s + i.totalPrice, 0);
                    html += `<div style="text-align:right;color:rgba(255,255,255,0.5);font-size:13px;margin-top:8px;">
                        Subtotal: <strong style="color:#fff;">${subtotal.toFixed(2)} MAD</strong></div>`;

                    body.innerHTML = html;
                    btn.disabled = false;
                    document.getElementById('rom-confirm-label').textContent = `Add ${_romItems.length} item${_romItems.length>1?'s':''} to Cart`;
                })
                .catch(err => {
                    body.innerHTML = `<div style="text-align:center;padding:40px;color:rgba(255,59,48,0.7);"><i class="fa-solid fa-triangle-exclamation" style="font-size:32px;margin-bottom:12px;display:block;"></i>Could not load order details.<br><span style="font-size:12px;">${err.message}</span></div>`;
                });
        }

        function closeReorderModal() {
            const overlay = document.getElementById('reorder-overlay');
            const modal   = document.getElementById('reorder-modal');
            overlay.style.opacity = '0';
            modal.style.transform = 'translateY(60px)';
            setTimeout(() => { overlay.style.display = 'none'; document.body.style.overflow = ''; }, 350);
        }

        function confirmReorder() {
            if (!_romShopId || _romItems.length === 0) return;
            const btn = document.getElementById('rom-confirm-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Redirecting...';

            // Save cart to localStorage — shop.php's DOMContentLoaded restores it automatically
            const key = 'qoon_pending_cart_' + _romShopId;
            localStorage.setItem(key, JSON.stringify(_romItems));

            setTimeout(() => {
                const targetUrl = 'shop.php?id=' + _romShopId;
                if (window.top !== window.self) {
                    // We are in an iframe, redirect the parent
                    window.parent.location.href = targetUrl;
                } else {
                    // Not in an iframe
                    window.location.href = targetUrl;
                }
            }, 600);
        }

        function filterOrders(status, pill) {
            // Update active pill
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            // Show/hide cards
            const cards = document.querySelectorAll('.order-card');
            let visible = 0;
            cards.forEach(card => {
                const match = status === 'all' || card.dataset.status === status;
                card.classList.toggle('hidden', !match);
                if (match) visible++;
            });

            // No results message
            const noMsg = document.getElementById('no-results-msg');
            if (noMsg) noMsg.style.display = visible === 0 ? 'block' : 'none';
        }

        <?php if (!$isIframe): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('space');
            if (!canvas) return;
            const c = canvas.getContext('2d');
            let w = canvas.width = window.innerWidth;
            let h = canvas.height = window.innerHeight;
            window.addEventListener('resize', () => { w = canvas.width = window.innerWidth; h = canvas.height = window.innerHeight; });
            const stars = [];
            class Star {
                constructor() { this.reset(true); }
                reset(init) {
                    this.x = Math.random() * w * 2 - w;
                    this.y = Math.random() * h * 2 - h;
                    this.z = init ? Math.random() * w : w;
                    this.size = Math.random() * 1.5 + 0.5;
                    this.color = Math.random() > 0.8 ? 'rgba(0,212,255,' : 'rgba(255,255,255,';
                }
                update() { this.z -= 2; if (this.z < 1) this.reset(false); }
                show() {
                    const sx = this.x / this.z * w + w / 2;
                    const sy = this.y / this.z * w + h / 2;
                    const r = this.size * (w / this.z);
                    const op = Math.max(0, 1 - this.z / w);
                    c.beginPath(); c.arc(sx, sy, r, 0, Math.PI * 2);
                    c.fillStyle = this.color + op + ')'; c.fill();
                }
            }
            for (let i = 0; i < 700; i++) stars.push(new Star());
            (function animate() {
                requestAnimationFrame(animate);
                c.fillStyle = 'rgba(1,0,8,0.2)';
                c.fillRect(0, 0, w, h);
                stars.forEach(s => { s.update(); s.show(); });
            })();
        });
        <?php endif; ?>
    </script>
</body>
</html>
