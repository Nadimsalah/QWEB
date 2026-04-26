<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Error · QOON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            min-height: 100dvh; width: 100%;
            font-family: 'Inter', sans-serif;
            background: #000;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }

        /* Ambient glow background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 40%, rgba(44,181,232,0.12) 0%, transparent 70%),
                        radial-gradient(ellipse 60% 50% at 20% 80%, rgba(120,40,200,0.08) 0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }

        .card {
            position: relative; z-index: 1;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 52px 48px 44px;
            max-width: 480px; width: 90%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            animation: fadeUp 0.5s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Animated signal icon */
        .icon-ring {
            width: 88px; height: 88px; border-radius: 50%;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px;
            position: relative;
            animation: pulse-ring 2.4s ease infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
            50%       { box-shadow: 0 0 0 18px rgba(239,68,68,0); }
        }
        .icon-ring i { font-size: 34px; color: #f87171; }

        h1 {
            font-size: 22px; font-weight: 700;
            margin-bottom: 10px; letter-spacing: -0.3px;
        }
        .subtitle {
            font-size: 14px; color: rgba(255,255,255,0.55);
            line-height: 1.6; margin-bottom: 8px;
        }

        /* Error detail box */
        .detail-box {
            background: rgba(239,68,68,0.07);
            border: 1px solid rgba(239,68,68,0.18);
            border-radius: 12px;
            padding: 14px 16px;
            margin: 20px 0 28px;
            text-align: left;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .detail-box i { color: #f87171; margin-top: 2px; flex-shrink: 0; font-size: 13px; }
        .detail-box span {
            font-size: 12px; color: rgba(255,255,255,0.5);
            line-height: 1.5; word-break: break-word;
            font-family: monospace;
        }

        /* Steps */
        .steps {
            text-align: left;
            margin: 0 0 28px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .step {
            display: flex; gap: 10px; align-items: center;
            font-size: 13px; color: rgba(255,255,255,0.6);
        }
        .step-num {
            width: 24px; height: 24px; border-radius: 50%;
            background: rgba(44,181,232,0.15);
            border: 1px solid rgba(44,181,232,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #2cb5e8;
            flex-shrink: 0;
        }

        /* Retry button */
        .btn-retry {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, #2cb5e8 0%, #1a8cbf 100%);
            color: #fff; font-family: 'Inter', sans-serif;
            font-size: 15px; font-weight: 600;
            border: none; border-radius: 50px;
            padding: 14px 36px; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 8px 24px rgba(44,181,232,0.35);
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }
        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(44,181,232,0.45);
        }
        .btn-retry:active { transform: translateY(0); }
        .btn-retry.spinning i { animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-retry i { font-size: 15px; }

        /* Timer badge */
        .auto-retry {
            margin-top: 16px;
            font-size: 12px; color: rgba(255,255,255,0.35);
        }
        #countdown { color: #2cb5e8; font-weight: 600; }

        /* QOON branding */
        .brand {
            margin-top: 28px;
            font-size: 12px; color: rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="icon-ring">
        <i class="fa-solid fa-tower-broadcast"></i>
    </div>

    <h1>Database Unreachable</h1>
    <p class="subtitle">Can't reach the QOON database server right now. This is usually a temporary network issue.</p>

    <div class="detail-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span id="err-msg"><?= isset($errorMsg) ? htmlspecialchars($errorMsg) : 'Connection timeout — server did not respond in time.' ?></span>
    </div>

    <div class="steps">
        <div class="step"><div class="step-num">1</div><span>Check your internet connection</span></div>
        <div class="step"><div class="step-num">2</div><span>The database server may be restarting — wait a moment</span></div>
        <div class="step"><div class="step-num">3</div><span>Click Retry to reconnect automatically</span></div>
    </div>

    <button class="btn-retry" id="retry-btn" onclick="doRetry()">
        <i class="fa-solid fa-rotate-right" id="retry-icon"></i>
        <span id="retry-label">Retry Connection</span>
    </button>

    <p class="auto-retry">Auto-retry in <span id="countdown">15</span>s</p>

    <div class="brand">QOON S-Commerce Platform</div>
</div>

<script>
    // Auto-retry countdown
    let secs = 15;
    const cd = document.getElementById('countdown');
    const iv = setInterval(() => {
        secs--;
        if (cd) cd.textContent = secs;
        if (secs <= 0) { clearInterval(iv); doRetry(); }
    }, 1000);

    function doRetry() {
        clearInterval(iv);
        const btn  = document.getElementById('retry-btn');
        const icon = document.getElementById('retry-icon');
        const lbl  = document.getElementById('retry-label');
        btn.classList.add('spinning');
        if (lbl) lbl.textContent = 'Connecting…';
        setTimeout(() => { location.reload(); }, 800);
    }
</script>

</body>
</html>
