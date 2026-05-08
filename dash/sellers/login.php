<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['SellerID'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../api_conn.php';
    mysqli_set_charset($con, "utf8mb4");

    $identifier = mysqli_real_escape_string($con, trim($_POST['identifier'] ?? ''));
    $password = mysqli_real_escape_string($con, trim($_POST['password'] ?? ''));

    if (!empty($identifier) && !empty($password)) {
        // Find Shop by LogName, Email, or Phone
        $query = "SELECT ShopID, Status, ShopName FROM Shops WHERE (ShopLogName = '$identifier' OR Email = '$identifier' OR ShopPhone = '$identifier') AND ShopPassword = '$password' LIMIT 1";
        $res = $con->query($query);
        
        if ($res && $res->num_rows === 1) {
            $shop = $res->fetch_assoc();
            
            if ($shop['Status'] === 'ACTIVE') {
                $_SESSION['SellerID'] = $shop['ShopID'];
                $_SESSION['SellerName'] = $shop['ShopName'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Your shop account is inactive or disabled. Please contact support.";
            }
        } else {
            $error = "Invalid credentials. Please check your email/phone and password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Gateway | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-purple: #4318FF;
            --brand-purple-dark: #2B0CBA;
            --text-dark: #111827;
            --text-gray: #6B7280;
            --bg-input: #F9FAFB;
            --border-color: #E5E7EB;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Outfit', sans-serif; }
        
        body { 
            display: flex;
            min-height: 100vh;
            background: #FFF;
        }

        /* LEFT SIDE - DYNAMIC VISUALS */
        .hero-section {
            flex: 1;
            position: relative;
            background: #000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 60px;
            color: #FFF;
        }

        /* Animated Mesh Gradient Background */
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: 
                radial-gradient(circle at 50% 50%, rgba(67, 24, 255, 0.4), transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(205, 5, 255, 0.3), transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(5, 205, 153, 0.2), transparent 50%);
            animation: meshFlow 20s ease-in-out infinite alternate;
            z-index: 1;
        }

        @keyframes meshFlow {
            0% { transform: rotate(0deg) scale(1); }
            100% { transform: rotate(15deg) scale(1.2); }
        }

        .hero-section::after {
            content:'';
            position: absolute; top:0; left:0; right:0; bottom:0;
            background: url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=2000&auto=format&fit=crop') center/cover;
            opacity: 0.15;
            z-index: 0;
            mix-blend-mode: overlay;
        }

        .hero-content {
            position: relative;
            z-index: 10;
        }

        .brand-logo {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: auto;
        }
        
        .brand-logo i { color: #A68BFF; }

        .hero-text h1 {
            font-size: 54px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #FFF, #A3AED0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 18px;
            color: #A3AED0;
            line-height: 1.6;
            max-width: 400px;
        }

        /* RIGHT SIDE - LOGIN FORM */
        .auth-section {
            flex: 0 0 500px;
            background: #FFF;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        .auth-card {
            width: 100%;
            max-width: 380px;
        }

        .auth-header {
            margin-bottom: 40px;
        }

        .auth-header h2 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -1px;
        }

        .auth-header p {
            color: var(--text-gray);
            font-size: 15px;
        }

        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        /* Floating Label Interactions */
        .form-group input {
            width: 100%;
            padding: 24px 20px 10px 50px;
            background: var(--bg-input);
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .form-group label {
            position: absolute;
            left: 50px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 15px;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .form-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 18px;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group input:not(:placeholder-shown) {
            background: #FFF;
            border-color: var(--brand-purple);
            box-shadow: 0 4px 20px rgba(67, 24, 255, 0.08);
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 12px;
            font-size: 11px;
            color: var(--brand-purple);
            font-weight: 700;
        }

        .form-group input:focus ~ .form-icon {
            color: var(--brand-purple);
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: var(--brand-purple);
            color: #FFF;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(67, 24, 255, 0.25);
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            background: var(--brand-purple-dark);
            box-shadow: 0 15px 35px rgba(67, 24, 255, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .error-msg {
            background: #FEF2F2;
            color: #DC2626;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #FEE2E2;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .register-link a {
            color: var(--brand-purple);
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Mobile Responsiveness */
        @media (max-width: 900px) {
            body { flex-direction: column; }
            .hero-section { display: none; }
            .auth-section { flex: 1; width: 100%; border-radius: 0; }
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="hero-content brand-logo">
            <i class="fas fa-store"></i> QOON Partnership
        </div>
        
        <div class="hero-content hero-text">
            <h1>Scale your<br>empire.</h1>
            <p>Access your centralized command center to manage orders, track analytics, and distribute content seamlessly across all networks.</p>
        </div>
    </div>

    <div class="auth-section">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome back</h2>
                <p>Enter your credentials to access your store.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'AccountNotFound'): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> Session expired or account removed.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="identifier" id="identifier" placeholder=" " required autocomplete="username">
                    <label for="identifier">Email / Phone Number</label>
                    <i class="fas fa-envelope form-icon"></i>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder=" " required autocomplete="current-password">
                    <label for="password">Access Password</label>
                    <i class="fas fa-lock form-icon"></i>
                </div>
                
                <div style="text-align: right; margin-bottom: 24px;">
                    <a href="#" style="font-size: 13px; color: var(--brand-purple); font-weight: 600; text-decoration: none;">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In to Dashboard <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="register-link">
                Not a partner yet? <a href="register.php">Apply to join QOON</a>
            </div>
        </div>
    </div>

</body>
</html>
