<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$myId = $_COOKIE['qoon_user_id'] ?? '';
$myName = $_COOKIE['qoon_user_name'] ?? 'User';
$myPhoto = $_COOKIE['qoon_user_photo'] ?? '';

if (!$myId) {
    header("Location: index.php?auth_required=1");
    exit;
}

$friendId = $_GET['uid'] ?? '';
if (!$friendId || $friendId == $myId) {
    echo "Invalid Chat.";
    exit;
}

$domain = $DomainNamee ?? 'https://qoon.app/dash/';

// Fetch Friend Info
$friendName = 'User';
$friendPhoto = '';
$friendPhone = '';
if ($con) {
    $stmt = $con->prepare("SELECT name, UserPhoto, PhoneNumber FROM Users WHERE UserID = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $friendId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $friendName = trim($row['name'] ?? '') ?: 'User';
            $friendPhoto = $row['UserPhoto'];
            $friendPhone = $row['PhoneNumber'];
        } else {
            echo "User not found.";
            exit;
        }
        $stmt->close();
    }
}

function fullUrl($path, $domain, $name) {
    if (!$path || $path === '0' || $path === 'NONE') return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=2cb5e8&color=fff";
    if (strpos($path, 'http') !== false) return preg_replace('#(?<!:)//+#', '/', $path);
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}

$fPhotoUrl = fullUrl($friendPhoto, $domain, $friendName);
$mPhotoUrl = fullUrl($myPhoto, $domain, $myName);

$isIframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;

// Generate Unique Chat ID (alphabetical or numeric sorting to ensure consistency)
$idArr = [$myId, $friendId];
sort($idArr);
$chatRoomId = $idArr[0] . "_" . $idArr[1];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Chat with <?= htmlspecialchars($friendName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- ⚡ Apply theme BEFORE paint to prevent flash -->
    <script>
        (function() {
            var t = localStorage.getItem('qoon_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.add('light-mode');
        })();
    </script>
    <style>
        :root {
            --bg-color: #050505;
            --tg-header: rgba(30, 35, 41, 0.85);
            --tg-input: rgba(30, 35, 41, 0.95);
            --bubble-me: #2b5278; /* Telegram dark mode blue */
            --bubble-them: #182533; /* Telegram dark mode dark blue */
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        
        body, html { height: 100%; overflow: hidden; background: var(--bg-color); color: var(--text-main); }
        
        /* Light Mode Overrides */
        html.light-mode { --bg-color: #f8f9fa; --tg-header: #ffffff; --tg-input: #ffffff; --bubble-me: #2cb5e8; --bubble-them: #ffffff; --text-main: #0f1115; --text-muted: rgba(0, 0, 0, 0.5); }
        html.light-mode body { background-color: #f8f9fa !important; color: #0f1115 !important; }
        html.light-mode .chat-bg { background-color: #f8f9fa !important; background-image: none !important; }
        html.light-mode .chat-pattern { opacity: 0.02 !important; background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M20,20 Q40,5 60,20 T100,20" fill="none" stroke="black" stroke-width="2"/><circle cx="50" cy="50" r="10" fill="none" stroke="black" stroke-width="2"/></svg>') !important; }
        html.light-mode .chat-header { background: #ffffff !important; border-bottom-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .header-name, html.light-mode .back-btn, html.light-mode .header-actions { color: #0f1115 !important; }
        html.light-mode .msg-wrapper.them .msg-bubble { background: #ffffff !important; color: #0f1115 !important; border: 1px solid rgba(0,0,0,0.06) !important; }
        html.light-mode .msg-wrapper.me .msg-bubble { background: #2cb5e8 !important; color: #ffffff !important; }
        html.light-mode .chat-input-container { background: #ffffff !important; border-top-color: rgba(0,0,0,0.08) !important; }
        html.light-mode .input-wrapper { background: rgba(0,0,0,0.03) !important; border-color: rgba(0,0,0,0.05) !important; }
        html.light-mode .chat-input { color: #0f1115 !important; }
        html.light-mode .msg-meta { color: rgba(0,0,0,0.4) !important; }
        html.light-mode .msg-wrapper.me .msg-meta { color: rgba(255,255,255,0.7) !important; }
        html.light-mode .apple-cash-card { background: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; box-shadow: 0 10px 25px rgba(0,0,0,0.05) !important; }
        html.light-mode .ac-amount { color: #0f1115 !important; }
        html.light-mode .ac-logo-row img { filter: none !important; }
        html.light-mode .ac-bottom { border-top-color: rgba(0,0,0,0.05) !important; }

        
        /* Telegram Style Background Pattern */
        .chat-bg {
            position: fixed; inset: 0; z-index: -2;
            background-color: #0e1621;
            background-image: radial-gradient(circle at 50% 0%, #1e3347 0%, transparent 70%),
                              radial-gradient(circle at 100% 100%, #1b263b 0%, transparent 70%);
        }
        
        /* Doodles/Pattern overlay */
        .chat-pattern {
            position: fixed; inset: 0; z-index: -1; opacity: 0.05; pointer-events: none;
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M20,20 Q40,5 60,20 T100,20" fill="none" stroke="white" stroke-width="2"/><circle cx="50" cy="50" r="10" fill="none" stroke="white" stroke-width="2"/></svg>');
            background-size: 150px;
        }

        .chat-container {
            display: flex; flex-direction: column; height: 100vh;
            /* Adjust for mobile safe areas */
            padding-bottom: env(safe-area-inset-bottom);
            max-width: 800px; margin: 0 auto; background: rgba(0,0,0,0.2);
            box-shadow: 0 0 50px rgba(0,0,0,0.5); position: relative;
        }

        /* Header */
        .chat-header {
            display: flex; align-items: center; gap: 14px; padding: 12px 20px;
            background: var(--tg-header); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05); z-index: 10;
        }
        .back-btn {
            color: #fff; font-size: 20px; cursor: pointer; text-decoration: none; padding: 5px;
            transition: 0.2s;
        }
        .back-btn:active { transform: scale(0.9); }
        
        .header-avatar {
            width: 44px; height: 44px; border-radius: 50%; object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .header-info { flex: 1; }
        .header-name { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 2px; }
        .header-status { font-size: 13px; color: #2ecc71; /* Online color */ }

        .header-actions { display: flex; gap: 16px; color: #fff; font-size: 18px; }
        .header-actions i { cursor: pointer; transition: 0.2s; }
        .header-actions i:active { color: #2cb5e8; transform: scale(0.9); }

        /* Messages Area */
        .messages-area {
            flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px;
            scroll-behavior: smooth;
        }
        .messages-area::-webkit-scrollbar { width: 5px; }
        .messages-area::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .msg-wrapper { display: flex; flex-direction: column; max-width: 80%; }
        .msg-wrapper.me { align-self: flex-end; align-items: flex-end; }
        .msg-wrapper.them { align-self: flex-start; align-items: flex-start; }

        .msg-bubble {
            padding: 10px 14px 20px 14px; border-radius: 16px; font-size: 15px; line-height: 1.4;
            position: relative; word-wrap: break-word; white-space: pre-wrap;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .msg-wrapper.me .msg-bubble {
            background: var(--bubble-me); color: #fff;
            border-bottom-right-radius: 4px;
        }
        .msg-wrapper.them .msg-bubble {
            background: var(--bubble-them); color: #fff;
            border-bottom-left-radius: 4px;
        }

        .msg-meta {
            display: flex; align-items: center; justify-content: flex-end; gap: 4px;
            font-size: 11px; margin-top: 4px;
            color: rgba(255,255,255,0.4);
        }
        .msg-wrapper.me .msg-meta { color: rgba(255,255,255,0.6); }

        .msg-image {
            max-width: 100%; border-radius: 12px; margin-top: 4px; cursor: pointer;
        }

        /* Input Area */
        .chat-input-container {
            display: flex; align-items: flex-end; gap: 10px; padding: 12px 16px;
            background: var(--tg-input); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        .attach-btn {
            color: var(--text-muted); font-size: 22px; cursor: pointer; padding: 10px;
            background: none; border: none; transition: 0.2s; flex-shrink: 0;
        }
        .attach-btn:hover { color: #fff; }

        .input-wrapper {
            flex: 1; background: rgba(0,0,0,0.3); border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.05); padding: 2px;
            display: flex; align-items: center; min-height: 44px;
        }
        .chat-input {
            flex: 1; background: transparent; border: none; color: #fff; font-size: 16px;
            padding: 10px 14px; max-height: 120px; overflow-y: auto; resize: none;
            outline: none; line-height: 1.4;
        }
        .chat-input::placeholder { color: rgba(255,255,255,0.3); }

        .send-btn {
            width: 40px; height: 40px; border-radius: 50%; background: #2cb5e8; color: #fff;
            border: none; display: flex; align-items: center; justify-content: center;
            font-size: 18px; cursor: pointer; transition: 0.2s; flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(44, 181, 232, 0.3); margin-bottom: 2px;
        }
        .send-btn:active { transform: scale(0.9); }

        /* Fullscreen Image View */
        .img-viewer {
            position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 9999;
            display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none;
            transition: 0.3s;
        }
        .img-viewer.active { opacity: 1; pointer-events: auto; }
        .img-viewer img { max-width: 100vw; max-height: 100vh; object-fit: contain; }
        .img-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 30px; cursor: pointer; }

        /* Apple Cash Style Transfer Card */
        .apple-cash-card {
            background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
            border: 1px solid rgba(44, 181, 232, 0.3);
            border-radius: 20px;
            padding: 20px 20px 15px 20px;
            width: 250px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .msg-wrapper.me .apple-cash-card {
            border-bottom-right-radius: 4px;
        }
        .msg-wrapper.them .apple-cash-card {
            border-bottom-left-radius: 4px;
        }
        .ac-logo-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .ac-logo-row img { height: 18px; filter: brightness(0) invert(1); }
        .ac-amount {
            font-size: 32px; font-weight: 800; letter-spacing: -1px;
            margin-bottom: 5px; color: #fff;
        }
        .ac-amount sup { font-size: 16px; font-weight: 600; top: -0.5em; position: relative; margin-left: 2px; }
        .ac-desc {
            font-size: 13px; color: #2cb5e8; font-weight: 600;
        }
        .ac-bottom {
            margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .ac-status { font-size: 11px; font-weight: 600; color: #34c759; text-transform: uppercase; letter-spacing: 0.5px; }
        .ac-icon {
            width: 20px; height: 20px; border-radius: 50%; background: #34c759; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 10px;
        }

    </style>
</head>
<body>

    <div class="chat-bg"></div>
    <div class="chat-pattern"></div>

    <div class="chat-container">
        
        <!-- Header -->
        <div class="chat-header">
            <a href="chat.php<?= $isIframe ? '?iframe=1' : '' ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
            <img src="<?= htmlspecialchars($fPhotoUrl) ?>" class="header-avatar" alt="">
            <div class="header-info">
                <div class="header-name"><?= htmlspecialchars($friendName) ?></div>
                <div class="header-status">Online</div> <!-- Hardcoded for UI aesthetic -->
            </div>
            <div class="header-actions">
                <i class="fa-solid fa-phone"></i>
                <i class="fa-solid fa-video"></i>
                <i class="fa-solid fa-ellipsis-vertical" style="margin-left: 5px;"></i>
            </div>
        </div>

        <!-- Messages -->
        <div class="messages-area" id="messages-area">
            <!-- Messages injected via Firebase -->
        </div>

        <!-- Input Area -->
        <div class="chat-input-container">
            <input type="file" id="image-upload" accept="image/*" style="display: none;">
            <button class="attach-btn" onclick="document.getElementById('image-upload').click()"><i class="fa-solid fa-paperclip"></i></button>
            <div class="input-wrapper">
                <textarea id="chat-input" class="chat-input" placeholder="Message..." rows="1" oninput="autoGrow(this)" onkeypress="handleEnter(event)"></textarea>
            </div>
            <button class="send-btn" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>

    </div>

    <!-- Image Viewer -->
    <div class="img-viewer" id="img-viewer" onclick="this.classList.remove('active')">
        <i class="fa-solid fa-xmark img-close"></i>
        <img id="viewer-img" src="">
    </div>

    <!-- Firebase JS SDK -->
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="assets/js/firebase-auth.js"></script>

    <script>
        const myId = "<?= $myId ?>";
        const friendId = "<?= $friendId ?>";
        const myName = "<?= htmlspecialchars($myName) ?>";
        
        // Init Firebase
        
        const db = firebase.database();
        const chatRef = db.ref('FriendChats/<?= $chatRoomId ?>');

        const messagesArea = document.getElementById('messages-area');
        const chatInput = document.getElementById('chat-input');

        function autoGrow(element) {
            element.style.height = "5px";
            element.style.height = (element.scrollHeight)+"px";
            if(element.value === '') element.style.height = "auto";
        }

        function formatTime(ts) {
            const d = new Date(ts);
            let h = d.getHours();
            let m = d.getMinutes();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12; h = h ? h : 12; 
            m = m < 10 ? '0'+m : m;
            return `${h}:${m} ${ampm}`;
        }

        // Listen for new messages
        chatRef.on('child_added', snapshot => {
            const data = snapshot.val();
            renderMessage(data);
        });

        function renderMessage(data) {
            const isMe = data.senderId === myId;
            const wrap = document.createElement('div');
            wrap.className = `msg-wrapper ${isMe ? 'me' : 'them'}`;

            let contentHtml = '';
            
            if (data.type === 'Image') {
                contentHtml = `<img src="${data.message}" class="msg-image" onclick="openImageViewer(this.src)">`;
            } else if (data.type === 'Transfer') {
                const amountText = parseFloat(data.message).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const descText = isMe ? `You sent to ${friendName}` : `${data.senderName} sent you`;
                contentHtml = `
                    <div class="apple-cash-card">
                        <div class="ac-logo-row">
                            <img src="qoon_pay_logo.png" alt="QOON Pay">
                        </div>
                        <div class="ac-amount">${amountText}<sup>MAD</sup></div>
                        <div class="ac-desc">${descText}</div>
                        <div class="ac-bottom">
                            <div class="ac-status">Completed</div>
                            <div class="ac-icon"><i class="fa-solid fa-check"></i></div>
                        </div>
                    </div>
                `;
            } else {
                contentHtml = `<div class="msg-bubble">${data.message}</div>`;
            }

            const checkmarks = isMe ? `<i class="fa-solid fa-check-double" style="color: #4db8ff; font-size:10px;"></i>` : '';
            const time = formatTime(data.timestamp || Date.now());

            if (data.type === 'Transfer') {
                wrap.innerHTML = `
                    ${contentHtml}
                    <div class="msg-meta">${time} ${checkmarks}</div>
                `;
            } else if (data.type === 'Image') {
                wrap.innerHTML = `
                    ${contentHtml}
                    <div class="msg-meta" style="margin-top: 4px;">${time} ${checkmarks}</div>
                `;
            } else {
                wrap.innerHTML = `
                    <div class="msg-bubble">
                        ${data.message}
                        <div class="msg-meta" style="position:absolute; bottom:6px; right:10px;">${time} ${checkmarks}</div>
                    </div>
                `;
            }

            messagesArea.appendChild(wrap);
            messagesArea.scrollTo({ top: messagesArea.scrollHeight, behavior: 'smooth' });
        }

        function handleEnter(e) { 
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(); 
            }
        }

        function sendMessage() {
            const msg = chatInput.value.trim();
            if(!msg) return;
            
            chatInput.value = '';
            chatInput.style.height = "auto";
            
            chatRef.push({
                timestamp: Date.now(),
                type: 'Text',
                message: msg,
                senderId: myId,
                senderName: myName
            });
        }

        // Image Upload Logic
        document.getElementById('image-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(evt) {
                const base64String = evt.target.result.split(',')[1];
                
                // Show uploading UI
                const wrap = document.createElement('div');
                wrap.className = 'msg-wrapper me';
                wrap.id = 'uploading-msg';
                wrap.innerHTML = `
                    <div class="msg-bubble" style="background: var(--bubble-me); opacity: 0.7;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Uploading...
                    </div>
                `;
                messagesArea.appendChild(wrap);
                messagesArea.scrollTo({ top: messagesArea.scrollHeight, behavior: 'smooth' });

                const fd = new FormData();
                fd.append("photochat", base64String);

                fetch("uploadImageChat.php", {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('uploading-msg').remove();
                    if(data.success) {
                        chatRef.push({
                            timestamp: Date.now(),
                            type: 'Image',
                            message: data.data,
                            senderId: myId,
                            senderName: myName
                        });
                    } else {
                        alert("Failed to upload image.");
                    }
                }).catch((err) => {
                    document.getElementById('uploading-msg').remove();
                    alert("Network error. Unable to upload.");
                });
            };
            reader.readAsDataURL(file);
            e.target.value = '';
        });

        function openImageViewer(src) {
            document.getElementById('viewer-img').src = src;
            document.getElementById('img-viewer').classList.add('active');
        }
    </script>
</body>
</html>



