<?php
require_once 'conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | QOON - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --accent-glow: #4a25e1; /* Deep Purple for settings */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .aurora-container { position: absolute; inset: 0; z-index: 0; overflow: hidden; }
        .aurora-blob { position: absolute; width: 80vw; height: 60vh; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); filter: blur(100px); opacity: 0.3; animation: drift 20s infinite alternate linear; }
        @keyframes drift { from { transform: translate(-10%, -10%) scale(1); } to { transform: translate(10%, 10%) scale(1.1); } }
        .container { position: relative; z-index: 10; text-align: center; background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(32px); -webkit-backdrop-filter: blur(32px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 40px; padding: 60px 40px; max-width: 500px; width: 90%; box-shadow: 0 40px 100px rgba(0,0,0,0.5); animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .logo { height: 50px; margin-bottom: 30px; }
        h2 { font-size: 32px; font-weight: 700; margin-bottom: 12px; letter-spacing: -1px; }
        p { color: var(--text-muted); line-height: 1.6; margin-bottom: 32px; font-size: 16px; }
        .home-btn { background: #fff; color: #000; padding: 14px 32px; border-radius: 99px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .home-btn:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 15px 30px rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="aurora-container"><div class="aurora-blob"></div></div>
    <div class="container">
        <img src="logo_qoon_white.png" alt="QOON Logo" class="logo">
        <i class="fa-solid fa-sliders" style="font-size: 60px; color: var(--accent-glow); margin-bottom: 24px; display: block;"></i>
        <h2>Settings</h2>
        <p>Your personalized settings dashboard is currently under development. Get ready for complete control over your QOON experience.</p>
        <a href="index.php" class="home-btn"><i class="fa-solid fa-house"></i> Back to Home</a>
    </div>
</body>
</html>
