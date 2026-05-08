<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../api_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'submit') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    if (!$data || empty($data['items'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid order data']);
        exit;
    }
    
    $name = $con->real_escape_string($data['name']);
    $phone = $con->real_escape_string($data['phone']);
    $city = $con->real_escape_string($data['city']);
    $address = $con->real_escape_string($data['address']);
    $shopNameEscaped = $con->real_escape_string($data['shopName']);
    $total = (float)$data['total'];
    $userId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
    
    $fullAddress = $con->real_escape_string("$address, $city");
    
    // Fallback UserID logic since some legacy schemas may restrict null UserIDs
    $uidVal = $userId ? "'$userId'" : "'0'";
    
    $shopIdVal = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $randPin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $sql = "INSERT INTO Orders (UserID, UserName, UserPhone, UserAddress, OrderPrice, DestinationName, OrderState, Method, OrderSource, DestnationLat, DestnationLongt, DestnationAddress, UserLat, UserLongt, OrderDetails, DestnationPhoto, OrderDelvTime, UserReview, OrderPriceForOur, ShopID, FourDigit) 
            VALUES ($uidVal, '$name', '$phone', '$fullAddress', '$total', '$shopNameEscaped', 'waiting', 'Delivery', 'WebStore', '0.0', '0.0', '$fullAddress', '0.0', '0.0', '', '', '0', '', '0', '$shopIdVal', '$randPin')";
            
    if ($con->query($sql)) {
        $orderId = $con->insert_id;
        
        foreach ($data['items'] as $item) {
            $fId = (int)$item['id'];
            $qty = (int)$item['qty'];
            
            // Insert line items
            $dSql = "INSERT INTO OrderDetailsOrder (OrderID, FoodID, Quantity) 
                     VALUES ('$orderId', '$fId', '$qty')";
            $con->query($dSql);
        }
        
        echo json_encode(['success' => true, 'orderId' => $orderId]);
    } else {
        echo json_encode(['success' => false, 'error' => $con->error]);
    }
    exit;
}

$shopID = isset($_GET['id']) ? (int)$_GET['id'] : ((isset($_SESSION['SellerID'])) ? (int)$_SESSION['SellerID'] : 0);

if(isset($_GET['u'])) {
    $u = $con->real_escape_string($_GET['u']);
    $q = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$u' LIMIT 1");
    if($q && $q->num_rows > 0) $shopID = (int)$q->fetch_assoc()['ShopID'];
}

$shopQ = $con->query("SELECT * FROM Shops WHERE ShopID = $shopID");
$shop = $shopQ->fetch_assoc();
if (!$shop) die("Store Not Found.");

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

$shopName = $shop['ShopName'];
$shopLogo = normalizeMediaUrl($shop['ShopLogo']) ?: "https://ui-avatars.com/api/?name=".urlencode($shopName)."&background=6C5CE7&color=FFF&bold=true";
$shopPhone = $shop['ShopPhone'] ?? $shop['OwnerPhone'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Checkout - <?= htmlspecialchars($shopName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #fafafa; -webkit-font-smoothing: antialiased; color: #111; }

.checkout-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; background: #fff; border-bottom: 1px solid #f0f0f0;
    position: sticky; top: 0; z-index: 100;
}
.chk-back {
    display: flex; align-items: center; justify-content: center; width: 44px; height: 44px;
    border: none; background: #f4f4f8; border-radius: 50%; color: #111; cursor: pointer; transition: 0.2s;
}
.chk-back:hover { background: #e0e0e8; }
.chk-brand { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 15px; }
.chk-brand img { width: 32px; height: 32px; border-radius: 10px; object-fit: cover; }

.checkout-layout { max-width: 600px; margin: 0 auto; padding: 24px; padding-bottom: 140px; }

.section-title { font-size: 20px; font-weight: 800; margin-bottom: 20px; letter-spacing: -0.5px; }

/* Order Summary */
.summary-card { background: #fff; border-radius: 20px; padding: 20px; margin-bottom: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.order-item { display: flex; gap: 14px; align-items: center; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f5f5f5; }
.order-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
.oi-img { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; background: #f0f0f0; flex-shrink: 0; }
.oi-details { flex: 1; min-width: 0; }
.oi-name { font-size: 14px; font-weight: 700; color: #111; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.oi-meta { font-size: 13px; font-weight: 600; color: #888; }
.oi-price { font-size: 15px; font-weight: 800; color: #111; }

.summary-total { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 2px dashed #eee; }
.st-lbl { font-size: 16px; font-weight: 800; color: #555; }
.st-val { font-size: 22px; font-weight: 900; color: #111; }

/* Delivery Form */
.form-card { background: #fff; border-radius: 20px; padding: 24px 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.input-group { margin-bottom: 20px; }
.input-group:last-child { margin-bottom: 0; }
.lbl { display: block; font-size: 13px; font-weight: 700; color: #555; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.ipt { width: 100%; border: 2px solid #e0e0e0; background: #fafafa; padding: 16px; border-radius: 14px; font-size: 15px; font-weight: 600; color: #111; transition: 0.2s; outline: none; font-family: 'Inter', sans-serif; }
.ipt:focus { border-color: #111; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

/* Sticky Footer */
.floating-bottom { position: fixed; bottom: 0; left: 0; right: 0; padding: 20px 24px max(20px, env(safe-area-inset-bottom)); background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-top: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; }
.place-order-btn { width: 100%; max-width: 600px; background: #111; color: #fff; border: none; border-radius: 16px; padding: 20px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; justify-content: center; align-items: center; gap: 8px; }
.place-order-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,0.25); }
.place-order-btn:active { transform: scale(0.97); }

.qoon-branding-link {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 16px; text-decoration: none; color: #888; transition: 0.2s;
}
.qoon-text { font-size: 13px; font-weight: 600; }
.qoon-logo-img { height: 16px; object-fit: contain; }
.qoon-branding-link:hover { opacity: 0.8; }

/* Success Screen Overwrite UI */
.success-screen { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 20px; animation: fadeIn 0.4s ease; }
@keyframes fadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }
.success-icon { width: 80px; height: 80px; border-radius: 50%; background: #f3f0ff; color: #6C5CE7; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
.success-icon svg { width: 40px; height: 40px; }
@keyframes popIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.success-screen h2 { font-size: 26px; font-weight: 900; letter-spacing: -1px; margin-bottom: 12px; color: #111; }
.success-screen p { font-size: 15px; color: #555; line-height: 1.5; margin-bottom: 32px; max-width: 320px; }
.return-btn { background: #f4f4f8; color: #111; font-weight: 700; border: none; padding: 16px 32px; border-radius: 100px; cursor: pointer; transition: 0.2s; font-family: 'Inter', sans-serif;}
.return-btn:hover { background: #e0e0e8; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.spinner { border: 2px solid rgba(255,255,255,0.2); border-left-color: #fff; width: 20px; height: 20px; border-radius: 50%; display: inline-block; animation: spin 1s linear infinite; }
</style>
</head>
<body>

<div class="checkout-header">
    <button class="chk-back" onclick="window.history.back()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    </button>
    <div class="chk-brand">
        <img src="<?= htmlspecialchars($shopLogo) ?>" alt="">
        <?= htmlspecialchars($shopName) ?>
    </div>
    <div style="width: 44px;"></div>
</div>

<div class="checkout-layout">
    
    <div class="section-title">Order Summary</div>
    <div class="summary-card">
        <div id="receipt-items"></div>
        <div class="summary-total">
            <span class="st-lbl">Total</span>
            <span class="st-val" id="receipt-total">0.00 MAD</span>
        </div>
    </div>
    
    <div class="section-title">Delivery Details</div>
    <div class="form-card">
        <div class="input-group">
            <span class="lbl">Full Name</span>
            <input type="text" id="chk-name" class="ipt" placeholder="John Doe">
        </div>
        <div class="input-group">
            <span class="lbl">Phone Number</span>
            <input type="tel" id="chk-phone" class="ipt" placeholder="06 00 00 00 00">
        </div>
        <div class="input-group">
            <span class="lbl">City</span>
            <input type="text" id="chk-city" class="ipt" placeholder="Casablanca">
        </div>
        <div class="input-group">
            <span class="lbl">Exact Address</span>
            <input type="text" id="chk-address" class="ipt" placeholder="Apartment, Street...">
        </div>
    </div>

</div>

<div class="floating-bottom">
    <button class="place-order-btn" onclick="submitOrder()">
        <span>PLACE ORDER</span> • <span id="btn-total">0.00 MAD</span>
    </button>
    <a href="https://www.qoon.app" target="_blank" class="qoon-branding-link">
        <span class="qoon-text">or continue with</span>
        <img src="../images/logo.png" alt="QOON" class="qoon-logo-img">
    </a>
</div>

<script>
const SHOP_ID = <?= $shopID ?>;
const SHOP_NAME = <?= json_encode($shopName) ?>;
const SHOP_PHONE = <?= json_encode($shopPhone) ?>;

let cartItems = [];
let cartTotal = 0;

function loadCart() {
    try {
        const saved = localStorage.getItem('qoon_store_cart_' + SHOP_ID);
        if (saved) cartItems = JSON.parse(saved);
    } catch(e) {}
    
    if (cartItems.length === 0) {
        alert("Your cart is empty!");
        window.history.back();
        return;
    }
    
    let html = '';
    cartTotal = 0;
    
    cartItems.forEach(item => {
        const linePrice = item.price * item.qty;
        cartTotal += linePrice;
        html += `
            <div class="order-item">
                <img src="${item.photo}" class="oi-img">
                <div class="oi-details">
                    <div class="oi-name">${item.name}</div>
                    <div class="oi-meta">Qty: ${item.qty}</div>
                </div>
                <div class="oi-price">${Number(linePrice).toFixed(2)}</div>
            </div>
        `;
    });
    
    document.getElementById('receipt-items').innerHTML = html;
    document.getElementById('receipt-total').innerText = cartTotal.toFixed(2) + ' MAD';
    document.getElementById('btn-total').innerText = cartTotal.toFixed(2) + ' MAD';
}

async function submitOrder() {
    const name = document.getElementById('chk-name').value.trim();
    const phone = document.getElementById('chk-phone').value.trim();
    const city = document.getElementById('chk-city').value.trim();
    const address = document.getElementById('chk-address').value.trim();
    
    if (!name || !phone || !city || !address) {
        return alert("Please fill out all delivery details.");
    }
    
    // Apply highly visible loading state
    const btn = document.querySelector('.place-order-btn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = `<span class="spinner"></span> <span style="margin-left: 8px;">Processing</span>`;
    btn.style.pointerEvents = 'none';
    btn.style.opacity = '0.8';

    try {
        const payload = {
            name, phone, city, address,
            shopName: SHOP_NAME,
            total: cartTotal,
            items: cartItems
        };
        
        const res = await fetch(`checkout.php?action=submit&id=${SHOP_ID}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const rawText = await res.text();
        let json;
        try {
            json = JSON.parse(rawText);
        } catch(e) {
            console.error('Server returned non-JSON:', rawText);
            alert("Backend Error: " + rawText.substring(0, 500));
            btn.innerHTML = originalContent;
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
            return;
        }
        
        if (json.success) {
            // Unmount Cart 
            localStorage.removeItem('qoon_store_cart_' + SHOP_ID);
            
            // Present Native Animated Confirmation
            document.querySelector('.checkout-layout').innerHTML = `
                <div class="success-screen">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <h2>Thank You, ${name}!</h2>
                    <p>Your order for <b>${cartTotal.toFixed(2)} MAD</b> has been safely received by <b>${SHOP_NAME}</b> and is currently pending review.</p>
                    <button class="return-btn" onclick="window.location.href='store.php?id=${SHOP_ID}'">Return to Store</button>
                </div>
            `;
            // Remove footer elements
            document.querySelector('.floating-bottom').style.display = 'none';
            document.querySelector('.checkout-header').style.display = 'none';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            alert('Could not submit order: ' + (json.error || 'Unknown error'));
            btn.innerHTML = originalContent;
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
        }
    } catch(err) {
        alert('Network error. Details: ' + err.message);
        btn.innerHTML = originalContent;
        btn.style.pointerEvents = 'auto';
        btn.style.opacity = '1';
    }
}

window.addEventListener('DOMContentLoaded', loadCart);
</script>

</body>
</html>
