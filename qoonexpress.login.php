<?php
// ============================================================
// QOON Express — Driver Login Page
// Route: /qoonexpress.login
// ============================================================
session_start();
if (isset($_SESSION['driver_id'])) {
    header('Location: GetDriverHome.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QOON Express — Driver Login</title>
    <meta name="description" content="QOON Express driver portal. Sign in to manage your deliveries and earnings.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --express-blue: #2cb5e8;
            --express-purple: #6a11cb;
            --express-pink: #ff0080;
            --bg: #F8FAFC;
            --surface: #ffffff;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --glow-blue: rgba(44, 181, 232, 0.15);
            --glow-purple: rgba(106, 17, 203, 0.1);
        }

        html,
        body {
            height: 100%;
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ── Animated background ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.2;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 700px;
            height: 700px;
            top: -250px;
            left: -200px;
            background: radial-gradient(circle, #2cb5e8, #6a11cb);
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            bottom: -150px;
            right: -150px;
            background: radial-gradient(circle, #6a11cb, #ff0080);
        }

        /* Grid */
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(44, 181, 232, 0.05) 1.5px, transparent 1.5px),
                linear-gradient(90deg, rgba(44, 181, 232, 0.05) 1.5px, transparent 1.5px);
            background-size: 60px 60px;
        }

        /* ── Layout ── */
        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        /* ── Logo ── */
        .logo-wrap {
            text-align: center;
            margin-bottom: 32px;
            animation: fadeDown 0.6s ease both;
        }

        .logo-wrap img {
            height: 64px;
            width: auto;
            object-fit: contain;
        }

        .logo-sub {
            margin-top: 12px;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        /* ── Card ── */
        .login-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 40px 36px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0,0,0,0.02);
            animation: fadeUp 0.6s ease 0.1s both;
            position: relative;
            overflow: hidden;
        }

        /* Top gradient line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2cb5e8, #6a11cb, #ff0080);
        }

        /* ── Driver badge ── */
        .driver-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .card-header {
            margin-bottom: 32px;
        }

        .card-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .card-header p {
            margin-top: 8px;
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ── Form fields ── */
        .field {
            margin-bottom: 22px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .ico {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 1;
        }

        .input-wrap input {
            width: 100%;
            background: var(--input-bg);
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px 48px 16px 48px;
            font-size: 16px;
            font-family: 'Outfit', sans-serif;
            color: var(--text);
            outline: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .input-wrap input::placeholder {
            color: #94a3b8;
        }

        .input-wrap input:focus {
            border-color: var(--express-blue);
            background: #ffffff;
            box-shadow: 0 0 0 4px var(--glow-blue);
        }

        .input-wrap input:focus~.ico {
            color: var(--express-blue);
        }

        /* pw toggle */
        .pw-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            font-size: 16px;
            transition: color 0.2s;
        }

        .pw-toggle:hover {
            color: var(--express-blue);
        }

        /* ── Row meta ── */
        .row-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 8px;
        }

        .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-wrap input {
            display: none;
        }

        .custom-box {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .checkbox-wrap input:checked~.custom-box {
            background: var(--express-blue);
            border-color: var(--express-blue);
        }

        .custom-box i {
            font-size: 11px;
            color: #fff;
            display: none;
        }

        .checkbox-wrap input:checked~.custom-box i {
            display: block;
        }

        .checkbox-wrap span {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .forgot-link {
            font-size: 14px;
            color: var(--express-blue);
            text-decoration: none;
            font-weight: 600;
        }

        /* ── Button ── */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--express-blue), var(--express-purple));
            color: #fff;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 25px rgba(44, 181, 232, 0.3);
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(44, 181, 232, 0.4);
            filter: brightness(1.05);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ── Alerts ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            animation: fadeUp 0.3s ease;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #16a34a;
        }

        /* ── Footer ── */
        .card-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .card-footer a {
            color: var(--express-blue);
            text-decoration: none;
            font-weight: 700;
        }

        /* ── Stats strip ── */
        .stats-strip {
            display: flex;
            gap: 16px;
            margin-top: 24px;
            animation: fadeUp 0.6s ease 0.25s both;
        }

        .stat-pill {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 18px 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .stat-pill .num {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
        }

        .stat-pill .lbl {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
            font-weight: 700;
        }

        /* ── Animations ── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeDown {
            from {
                opacity: 0;
                transform: translateY(-16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 28px 20px;
                border-radius: 20px;
            }

            .stats-strip {
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="bg-scene">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="bg-grid"></div>

    <div class="page-wrapper">
        <div class="login-container">

            <!-- Logo -->
            <div class="logo-wrap">
                <img src="logo_express.png" alt="QOON Express">
                <div class="logo-sub">Driver Portal — Sign in to your account</div>
            </div>

            <!-- Card -->
            <div class="login-card">

                <div class="driver-badge">
                    <i class="fa-solid fa-id-badge"></i>
                    Driver Access
                </div>

                <div class="card-header">
                    <h2>Welcome back, Driver 👋</h2>
                    <p>Enter your credentials to start accepting deliveries</p>
                </div>

                <!-- Alert -->
                <div id="alert-box" style="display:none;"></div>

                <form id="login-form" novalidate>

                    <div class="field">
                        <label for="phone">Phone Number</label>
                        <div class="input-wrap">
                            <input type="tel" id="phone" name="DriverPhone" placeholder="+212 6XX XXX XXX"
                                autocomplete="tel" inputmode="tel" required>
                            <i class="fa-solid fa-phone ico"></i>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="DriverPassword" placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <i class="fa-solid fa-lock ico"></i>
                            <button type="button" class="pw-toggle" id="toggle-btn" onclick="togglePw()"
                                aria-label="Toggle password">
                                <i class="fa-regular fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row-meta">
                        <label class="checkbox-wrap" for="remember">
                            <input type="checkbox" id="remember">
                            <div class="custom-box"><i class="fa-solid fa-check"></i></div>
                            <span>Remember me</span>
                        </label>
                        <a href="ResetPasswordDriver.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login" id="login-btn">
                        <span class="btn-text">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>&nbsp; Sign In
                        </span>
                        <div class="spinner"></div>
                    </button>

                </form>

                <div class="card-footer">
                    <p>New driver? <a href="mailto:drivers@qoon.app">Contact us to register &rarr;</a></p>
                    <p style="margin-top:10px; font-size:11px; opacity:0.6;">
                        <i class="fa-solid fa-shield-halved"></i>&nbsp;
                        Secured with end-to-end encryption
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-strip">
                <div class="stat-pill">
                    <div class="num">2.4K+</div>
                    <div class="lbl">Drivers</div>
                </div>
                <div class="stat-pill">
                    <div class="num">98%</div>
                    <div class="lbl">On-Time</div>
                </div>
                <div class="stat-pill">
                    <div class="num">4.9★</div>
                    <div class="lbl">Rating</div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Password toggle
        function togglePw() {
            const pw = document.getElementById('password');
            const ico = document.getElementById('eye-icon');
            if (pw.type === 'password') {
                pw.type = 'text';
                ico.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pw.type = 'password';
                ico.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Alert helper
        function showAlert(msg, type = 'error') {
            const box = document.getElementById('alert-box');
            const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
            box.className = `alert alert-${type}`;
            box.innerHTML = `<i class="fa-solid ${icon}"></i> ${msg}`;
            box.style.display = 'flex';
        }

        // Submit
        document.getElementById('login-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            document.getElementById('alert-box').style.display = 'none';

            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const btn = document.getElementById('login-btn');

            if (!phone) { showAlert('Please enter your phone number.'); return; }
            if (!password) { showAlert('Please enter your password.'); return; }

            btn.disabled = true;
            btn.classList.add('loading');

            const fd = new FormData();
            fd.append('DriverPhone', phone);
            fd.append('DriverPassword', password);
            fd.append('FirebaseDriverToken', '');

            try {
                const res = await fetch('LoginDriverJibler.php', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.success) {
                    showAlert('Logged in successfully! Redirecting…', 'success');
                    if (json.data) {
                        localStorage.setItem('qoon_driver', JSON.stringify({
                            id: json.data.DriverID,
                            name: ((json.data.FName || '') + ' ' + (json.data.LName || '')).trim() || 'Driver',
                            phone: json.data.DriverPhone,
                            token: json.data.DriverToken,
                            photo: json.data.PersonalPhoto || ''
                        }));
                    }
                    setTimeout(() => {
                        const rTo = new URLSearchParams(window.location.search).get('return_to');
                        window.location.href = rTo || 'driver_dashboard.php';
                    }, 1200);
                } else {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                    const msg = json.message || 'Invalid credentials.';
                    showAlert(msg.includes('خاطئ') ? 'Incorrect phone or password. Please try again.' : msg);
                }
            } catch (err) {
                btn.disabled = false;
                btn.classList.remove('loading');
                showAlert('Connection error. Please check your internet connection.');
            }
        });

        // Auto-focus
        document.getElementById('phone').focus();
    </script>

</body>

</html>