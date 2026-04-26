<?php
require_once 'conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --accent-glow: #2cb5e8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .aurora-container {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .aurora-blob {
            position: absolute;
            width: 80vw;
            height: 60vh;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            filter: blur(100px);
            opacity: 0.3;
            animation: drift 20s infinite alternate linear;
        }

        @keyframes drift {
            from { transform: translate(-10%, -10%) scale(1); }
            to { transform: translate(10%, 10%) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 40px;
            padding: 60px 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
            animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .logo {
            height: 50px;
            margin-bottom: 30px;
        }

        .glitch-404 {
            font-size: 100px;
            font-weight: 800;
            letter-spacing: -5px;
            background: linear-gradient(135deg, #fff 0%, var(--accent-glow) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .home-btn {
            background: #fff;
            color: #000;
            padding: 14px 32px;
            border-radius: 99px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .home-btn:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 15px 30px rgba(255,255,255,0.2);
        }

        .home-btn i {
            font-size: 14px;
        }

        /* Ambient grid bg */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: 1;
            opacity: 0.5;
        }

    </style>
</head>
<body>
    <div class="grid-bg"></div>
    <div class="aurora-container">
        <div class="aurora-blob"></div>
    </div>

    <div class="container">
        <img src="logo_qoon_white.png" alt="QOON Logo" class="logo">
        <div class="glitch-404">404</div>
        <h2>Lost in Paradise?</h2>
        <p>The page you are looking for doesn't exist or has been moved to a secret location. Let's get you back track.</p>
        
        <a href="index.php" class="home-btn">
            <i class="fa-solid fa-house"></i> Back to Home
        </a>
    </div>
</body>
</html>
