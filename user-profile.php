<?php
require_once 'conn.php';

// Check Auth
$userId = $_COOKIE['qoon_user_id'] ?? null;
if (!$userId) {
    header("Location: index.php?auth_required=1");
    exit;
}

// Fetch User Data
$userData = [];
$res = $con->query("SELECT * FROM Users WHERE UserID = '$userId'");
if($res && $res->num_rows > 0) {
    $userData = $res->fetch_assoc();
} else {
    die("User not found.");
}

$uName = $userData['name'] ?: 'User';
$uEmail = $userData['Email'] ?: 'Not provided';
$uPhone = $userData['PhoneNumber'] ?: 'Not provided';
$uBalance = $userData['Balance'] ?? 0;
$uOrders = 0;

$uPhoto = resolvePhotoUrl($userData['UserPhoto'] ?? '', $uName);
$uBalance = floatval($userData['UserBalance'] ?? 0);
$uOrders = intval($userData['UserOrdersNum'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-glow: #2cb5e8;
            --card-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        .aurora { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
        .blob { position: absolute; width: 60vw; height: 60vh; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); filter: blur(100px); opacity: 0.15; animation: move 15s infinite alternate; }
        @keyframes move { from { transform: translate(-10%, -10%); } to { transform: translate(10%, 10%); } }

        <?php if(isset($_GET['iframe'])): ?>
        header { display: none !important; }
        .back-nav { display: none !important; }
        .container { margin: 0 !important; padding: 16px !important; width: 100% !important; max-width: 100% !important; }
        body { background: transparent !important; min-height: auto !important; }
        .aurora { display: none !important; }
        <?php else: ?>
        header { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid var(--glass-border); background: rgba(5,5,5,0.7); }
        .container { max-width: 600px; margin: 20px auto; padding: 0 16px 100px; }
        .back-nav { display:flex; align-items:center; gap:16px; margin-bottom:30px; }
        <?php endif; ?>
        .logo { height: 24px; }
        .back-btn { width:44px; height:44px; border-radius:50%; background:var(--glass-bg); border:1px solid var(--glass-border); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; backdrop-filter:blur(10px); transition:all 0.3s; }
        .back-btn:hover { transform: translateX(-4px); background: rgba(255,255,255,0.1); }
        
        /* Profile Section */
        .profile-header { text-align: center; margin-bottom: 40px; }
        .profile-avatar-container { position: relative; width: 120px; height: 120px; margin: 0 auto 16px; }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .edit-badge { position: absolute; bottom: 4px; right: 4px; background: var(--accent-glow); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 3px solid var(--bg-color); cursor: pointer; }

        .profile-name { font-size: 28px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; }
        .profile-email { font-size: 14px; color: var(--text-muted); }

        /* Hyper-Modern Bank Card */
        .wallet-card {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
            cursor: pointer;
        }

        .wallet-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 100% 0%, rgba(44, 181, 232, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 0% 100%, rgba(155, 45, 241, 0.1) 0%, transparent 50%);
            opacity: 0.5;
        }

        .wallet-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 40px 80px rgba(0,0,0,0.8);
        }

        .card-top { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2; }
        
        .card-chip { 
            width: 50px; height: 38px; 
            background: linear-gradient(135deg, #d4af37 0%, #f9e29c 50%, #b8860b 100%); 
            border-radius: 8px; position: relative; 
            overflow: hidden;
        }
        .card-chip::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(90deg, transparent, transparent 5px, rgba(0,0,0,0.1) 5px, rgba(0,0,0,0.1) 10px); }
        .card-chip::after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(0deg, transparent, transparent 5px, rgba(0,0,0,0.1) 5px, rgba(0,0,0,0.1) 10px); }

        .contactless { font-size: 24px; color: rgba(255,255,255,0.3); transform: rotate(90deg); }

        .card-balance-section { position: relative; z-index: 2; margin-top: 20px; }
        .card-balance-section .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 2px; margin-bottom: 8px; display: block; }
        .card-balance-section .amount { font-size: 38px; font-weight: 800; color: #fff; display: flex; align-items: baseline; gap: 8px; }
        .card-balance-section .currency { font-size: 14px; font-weight: 500; color: var(--text-muted); }

        .card-bottom { display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 2; }
        .card-holder-name { font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.9); }
        .card-brand { font-size: 20px; font-weight: 900; letter-spacing: -1px; text-transform: uppercase; color: #fff; display: flex; align-items: center; gap: 6px; }
        .card-brand i { color: var(--accent-glow); font-size: 16px; }

        .glare {
            position: absolute;
            top: 0; left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transform: skewX(-20deg);
            transition: none;
            pointer-events: none;
        }
        .wallet-card:hover .glare {
            left: 150%;
            transition: left 0.8s ease-in-out;
        }

        /* Stats & Info */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 30px; }
        .info-card { background: var(--glass-bg); padding: 16px; border-radius: 20px; border: 1px solid var(--glass-border); text-align: center; backdrop-filter: blur(10px); }
        .info-card .val { font-size: 20px; font-weight: 800; display: block; }
        .info-card .lbl { font-size: 12px; color: var(--text-muted); }

        .details-list { background: var(--glass-bg); border-radius: 24px; border: 1px solid var(--glass-border); overflow: hidden; }
        .detail-item { padding: 16px 20px; border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 16px; }
        .detail-item:last-child { border-bottom: none; }
        .detail-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; color: var(--accent-glow); }
        .detail-content { flex: 1; }
        .detail-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 15px; font-weight: 600; }

        @media (max-width: 600px) {
            .profile-name { font-size: 24px; }
            .wallet-card { min-height: 180px; padding: 20px; }
            .card-balance-section .amount { font-size: 28px; }
        }

        /* Auto-Save Animation Styles */
        .profile-avatar-container::before {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            padding: 4px;
            background: conic-gradient(from 0deg, var(--accent-glow), transparent 60%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .profile-avatar-container.saving::before {
            opacity: 1;
            animation: rotateGlow 1.2s linear infinite;
        }

        @keyframes rotateGlow {
            to { transform: rotate(360deg); }
        }

        .save-overlay {
            position: absolute;
            inset: 0;
            background: rgba(44, 181, 232, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 5;
            backdrop-filter: blur(2px);
        }

        .save-overlay i {
            font-size: 40px;
            color: #fff;
            transform: scale(0.5);
            transition: transform 0.4s 0.1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .profile-avatar-container.saved .save-overlay {
            opacity: 1;
            visibility: visible;
        }

        .profile-avatar-container.saved .save-overlay i {
            transform: scale(1);
        }

        .saving-text {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-glow);
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0;
            transition: opacity 0.3s;
            white-space: nowrap;
            pointer-events: none;
        }

        .profile-avatar-container.saving .saving-text { opacity: 1; }

        /* Editable Text Fields */
        [contenteditable="true"] {
            outline: none;
            transition: all 0.2s;
            border-radius: 6px;
            position: relative;
            display: inline-block;
        }
        [contenteditable="true"]:hover {
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.05);
            cursor: text;
        }
        [contenteditable="true"]:focus {
            background: rgba(44, 181, 232, 0.1);
            box-shadow: 0 0 0 4px rgba(44, 181, 232, 0.1);
        }
        .editable-hint {
            font-size: 12px;
            color: var(--accent-glow);
            opacity: 0;
            transition: opacity 0.2s;
            position: absolute;
            right: -24px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        [contenteditable="true"]:hover .editable-hint {
            opacity: 1;
        }
        .profile-name[contenteditable="true"] { padding: 4px 12px; margin: -4px -12px 4px; }
    </style>
</head>
<body>
    <div class="aurora"><div class="blob"></div></div>

    <header>
        <a href="index.php"><img src="logo_qoon_white.png" alt="QOON" class="logo"></a>
        <a href="settings.php" class="back-btn" style="width:36px; height:36px; border:none; background:transparent;"><i class="fa-solid fa-gear"></i></a>
    </header>

    <div class="container">
        <div class="back-nav">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1>My Profile</h1>
        </div>

        <div class="profile-header">
            <div class="profile-avatar-container" id="avatarContainer">
                <div class="save-overlay" id="saveOverlay"><i class="fa-solid fa-check"></i></div>
                <img src="<?= htmlspecialchars($uPhoto) ?>" alt="Avatar" class="profile-avatar" id="mainAvatar">
                <div class="edit-badge" onclick="document.getElementById('avatarInput').click()"><i class="fa-solid fa-camera"></i></div>
                <div class="saving-text">Auto Saving...</div>
                <input type="file" id="avatarInput" style="display:none" accept="image/*">
            </div>
            <h2 class="profile-name" id="editFullName" contenteditable="true" spellcheck="false"><?= htmlspecialchars($uName) ?><i class="fa-solid fa-pen editable-hint"></i></h2>
            <p class="profile-email" id="headerEmail"><?= htmlspecialchars($uEmail) ?></p>
        </div>

        <!-- Hyper-Modern Wallet Card -->
        <div class="wallet-card">
            <div class="glare"></div>
            <div class="card-top">
                <div class="card-chip"></div>
                <div class="contactless"><i class="fa-solid fa-wifi"></i></div>
            </div>
            
            <div class="card-balance-section">
                <span class="label">Available Balance</span>
                <div class="amount">
                    <?= number_format($uBalance, 2) ?>
                    <span class="currency">MAD</span>
                </div>
            </div>

            <div class="card-bottom">
                <div class="card-holder">
                    <div class="card-holder-name"><?= htmlspecialchars($uName) ?></div>
                </div>
                <div class="card-brand">
                    <img src="qoon_pay_logo.png" alt="QOON PAY" style="height:28px; opacity:0.9; filter:brightness(0) invert(1);">
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <span class="val"><?= number_format($uOrders) ?></span>
                <span class="lbl">Total Orders</span>
            </div>
            <div class="info-card">
                <span class="val">Member</span>
                <span class="lbl">since <?= date('Y') ?></span>
            </div>
        </div>

        <div class="details-list">
            <div class="detail-item">
                <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value" id="editPhone" contenteditable="true" spellcheck="false" style="padding: 4px; margin: -4px;"><?= htmlspecialchars($uPhone) ?><i class="fa-solid fa-pen editable-hint"></i></div>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-icon"><i class="fa-solid fa-envelope"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value" id="editEmail" contenteditable="true" spellcheck="false" style="padding: 4px; margin: -4px;"><?= htmlspecialchars($uEmail) ?><i class="fa-solid fa-pen editable-hint"></i></div>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Location</div>
                    <div class="detail-value"><?= htmlspecialchars($userData['City'] ?? 'Morocco') ?></div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <a href="orders.php" style="background:#fff; color:#000; padding:16px; border-radius:20px; font-weight:700; text-decoration:none; text-align:center; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="fa-solid fa-clock-rotate-left"></i> My Orders
            </a>
            <a href="logout.php" style="background:rgba(255, 59, 48, 0.1); color:#ff3b30; padding:16px; border-radius:20px; font-weight:700; text-decoration:none; text-align:center; border:1px solid rgba(255, 59, 48, 0.2);">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <script>
        const avatarInput = document.getElementById('avatarInput');
        const mainAvatar = document.getElementById('mainAvatar');
        const avatarContainer = document.getElementById('avatarContainer');

        avatarInput.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            // Preview immediately
            const reader = new FileReader();
            reader.onload = e => {
                mainAvatar.src = e.target.result;
                
                // Compress image before upload
                const img = new Image();
                img.onload = async () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const maxSize = 800; // max width/height

                    if (width > height && width > maxSize) {
                        height *= maxSize / width;
                        width = maxSize;
                    } else if (height > maxSize) {
                        width *= maxSize / height;
                        height = maxSize;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convert to blob
                    canvas.toBlob(async (blob) => {
                        // Upload State
                        avatarContainer.classList.add('saving');
                        avatarContainer.classList.remove('saved');

                        const formData = new FormData();
                        formData.append('photo', blob, 'profile.jpg');
                        formData.append('UserID', '<?= (int)$userId ?>');

                        try {
                            const res = await fetch('UpdateProfileUserWithImageinapp.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const text = await res.text();
                            let json;
                            try {
                                json = JSON.parse(text);
                            } catch(e) {
                                console.error("Raw response:", text);
                                alert("Server error. Check console.");
                                avatarContainer.classList.remove('saving');
                                return;
                            }
                            
                            if (json.success) {
                                avatarContainer.classList.remove('saving');
                                avatarContainer.classList.add('saved');
                                mainAvatar.src = json.photoUrl;
                                setTimeout(() => avatarContainer.classList.remove('saved'), 2500);
                            } else {
                                alert('Upload failed: ' + json.message);
                                location.reload(); 
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Connection error');
                            location.reload();
                        } finally {
                            avatarContainer.classList.remove('saving');
                        }
                    }, 'image/jpeg', 0.8); // 80% quality JPEG
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });

        // Dynamic Text Editing
        const editableFields = document.querySelectorAll('[contenteditable="true"]');
        editableFields.forEach(field => {
            // Remove hint text from innerText calculation
            const getText = (el) => {
                let clone = el.cloneNode(true);
                let hint = clone.querySelector('.editable-hint');
                if(hint) hint.remove();
                return clone.innerText.trim();
            };

            field.dataset.original = getText(field);
            
            field.addEventListener('blur', async function() {
                const newVal = getText(this);
                if (newVal === this.dataset.original) return;
                
                this.style.opacity = '0.5';
                
                const formData = new FormData();
                formData.append('UserID', '<?= (int)$userId ?>');
                formData.append('fullname', getText(document.getElementById('editFullName')));
                formData.append('PhoneNumber', getText(document.getElementById('editPhone')));
                formData.append('email', getText(document.getElementById('editEmail')));
                
                try {
                    const res = await fetch('UpdateProfileUserWithImageinapp.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        this.dataset.original = newVal;
                        
                        // Sync visual UI elements
                        const freshName = getText(document.getElementById('editFullName'));
                        const freshEmail = getText(document.getElementById('editEmail'));
                        document.querySelector('.card-holder-name').innerText = freshName;
                        document.getElementById('headerEmail').innerText = freshEmail;
                        
                        // Briefly pulse green
                        this.style.color = '#2ce87b';
                        setTimeout(() => this.style.color = '', 1000);
                    } else {
                        alert("Update failed: " + data.message);
                        // Revert text (must preserve hint)
                        this.innerHTML = this.dataset.original + '<i class="fa-solid fa-pen editable-hint"></i>';
                    }
                } catch(e) {
                    console.error("Save error:", e);
                    this.innerHTML = this.dataset.original + '<i class="fa-solid fa-pen editable-hint"></i>';
                }
                
                this.style.opacity = '1';
            });
            
            field.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.blur(); // Triggers the save
                }
            });
        });
    </script>
</body>
</html>
