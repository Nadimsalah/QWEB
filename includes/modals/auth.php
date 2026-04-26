<style>
    /* --- QOON AUTH MODAL PREMIUM STYLES --- */
    .auth-overlay {
        position: fixed;
        inset: 0;
        z-index: 100000;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .auth-modal {
        width: 100%;
        max-width: 420px;
        background: rgba(15, 15, 15, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 32px;
        padding: 40px 32px;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        transform: scale(0.9);
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        font-family: 'Inter', sans-serif;
    }

    .auth-title {
        font-size: 24px;
        font-weight: 800;
        color: #fff;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .auth-subtitle {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 32px;
        line-height: 1.5;
    }

    .social-btns {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .social-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        height: 52px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .social-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }

    .social-btn.btn-google {
        background: #fff;
        color: #000;
        border: none;
    }

    .social-btn.btn-google:hover {
        background: #f1f1f1;
    }

    .auth-form {
        display: none;
        flex-direction: column;
        text-align: left;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 8px;
        margin-left: 4px;
    }

    .form-input {
        width: 100%;
        height: 52px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 0 20px;
        color: #fff;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: all 0.2s ease;
    }

    .form-input:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
    }

    .auth-step {
        display: none;
    }

    .auth-step.active {
        display: block;
    }

    .auth-submit-btn {
        height: 52px;
        border-radius: 16px;
        border: none;
        background: #fff;
        color: #000;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .auth-submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
    }

    .auth-toggle-link {
        margin-top: 24px;
        text-align: center;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
    }

    .auth-toggle-link span {
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        margin-left: 4px;
    }

    .step-dots {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 24px;
    }

    .step-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .step-dot.active {
        width: 20px;
        border-radius: 10px;
        background: #fff;
    }

    .gender-group {
        display: flex;
        gap: 12px;
    }

    .gender-btn {
        flex: 1;
        height: 52px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .gender-btn.active {
        background: #fff;
        color: #000;
        border-color: #fff;
    }

    .avatar-preview-box {
        width: 80px;
        height: 80px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px dashed rgba(255, 255, 255, 0.1);
        margin: 0 auto 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .avatar-preview-box:hover {
        border-color: rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.08);
    }
</style>

<div class="auth-overlay" id="signup-overlay" style="display:none;">
    <div class="auth-modal" id="signup-modal">
        <div style="margin-bottom: 24px;">
            <img src="logo_qoon_white.png" style="height: 44px; width: auto; object-fit: contain;">
        </div>

        <div id="auth-social-view">
            <div class="auth-title">Welcome to QOON</div>
            <div class="auth-subtitle">Join the first social commerce platform tailored to your lifestyle.</div>
            <div class="social-btns">
                <a href="javascript:void(0)" onclick="googleLogin()" class="social-btn btn-google">
                    <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png"
                        style="width:20px; height:20px;"> Continue with Google
                </a>
                <a href="javascript:void(0)" onclick="appleLogin()" class="social-btn btn-apple">
                    <i class="fa-brands fa-apple"></i> Continue with Apple
                </a>
                <a href="javascript:void(0)" onclick="toggleEmailForm(true)" class="social-btn btn-email">
                    <i class="fa-solid fa-envelope"></i> Continue with Email
                </a>
            </div>
        </div>

        <div id="auth-email-view" class="auth-form">
            <!-- Step Indicator -->
            <div class="step-dots" id="signup-dots" style="display:none;">
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
                <div class="step-dot"></div>
            </div>

            <div class="auth-title" id="email-view-title">Sign In</div>
            <div class="auth-subtitle" id="email-view-subtitle">Access your account using your email.</div>

            <!-- Step 1: Personal Info -->
            <div class="auth-step active" id="step-1">
                <div id="signup-only-fields" style="display:none;">
                    <div class="avatar-preview-box">
                        <i class="fa-solid fa-camera" style="color:rgba(255,255,255,0.2); font-size:24px;"></i>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-input" id="auth-name" placeholder="Enter your name">
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-input" id="auth-email" placeholder="name@example.com">
                </div>

                <div class="form-group" id="signup-phone-group" style="display:none;">
                    <label>Phone Number</label>
                    <input type="tel" class="form-input" id="auth-phone" placeholder="+212 600 000 000">
                </div>
            </div>

            <!-- Step 2: Location & Gender (Signup only) -->
            <div class="auth-step" id="step-2">
                <div class="form-group">
                    <label>Your City</label>
                    <input type="text" class="form-input" id="auth-city" placeholder="Casablanca, Marrakech...">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <div class="gender-group">
                        <div class="gender-btn" onclick="selectGender('Male', this)"><i class="fa-solid fa-mars"></i>
                            Male</div>
                        <div class="gender-btn" onclick="selectGender('Female', this)"><i class="fa-solid fa-venus"></i>
                            Female</div>
                    </div>
                    <input type="hidden" id="auth-gender" value="">
                </div>
            </div>

            <!-- Step 3: Password -->
            <div class="auth-step" id="step-3">
                <div class="form-group">
                    <label id="pass-label">Password</label>
                    <input type="password" class="form-input" id="auth-password" placeholder="••••••••">
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button class="auth-submit-btn"
                    style="flex:1; background:rgba(255,255,255,0.05); color:#fff; display:none;" id="btn-prev"
                    onclick="prevStep()">Back</button>
                <button class="auth-submit-btn" style="flex:2;" onclick="handleNextClick()"
                    id="btn-next">Continue</button>
            </div>

            <div class="auth-toggle-link" id="auth-mode-toggle">
                Don't have an account? <span onclick="toggleAuthMode('signup')">Sign Up</span>
            </div>

            <button onclick="toggleEmailForm(false)"
                style="margin-top: 16px; background: transparent; border: none; color: var(--text-muted); font-size: 13px; cursor: pointer;"><i
                    class="fa-solid fa-arrow-left"></i> Exit Email Options</button>
        </div>

        <button onclick="closeSignup()"
            style="margin-top: 24px; background: transparent; border: none; color: rgba(255,255,255,0.4); font-size: 13px; cursor: pointer; text-decoration: underline;">Cancel</button>
    </div>
</div>

<script>
    window.openSignup = function () {
        const overlay = document.getElementById('signup-overlay');
        const modal = document.getElementById('signup-modal');
        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.style.opacity = '1';
            modal.style.transform = 'scale(1)';
        }, 10);
        document.body.style.overflow = 'hidden';

        // Highlight Apple if on Mac/iOS
        const isApple = /Mac|iPhone|iPad|iPod/.test(navigator.userAgent);
        if (isApple) {
            const appleBtn = modal.querySelector('.btn-apple');
            appleBtn.style.borderColor = 'rgba(255,255,255,0.6)';
            appleBtn.style.boxShadow = '0 0 20px rgba(255,255,255,0.1)';
        }
    };

    window.closeSignup = function () {
        const overlay = document.getElementById('signup-overlay');
        const modal = document.getElementById('signup-modal');
        overlay.style.opacity = '0';
        modal.style.transform = 'scale(0.9)';
        setTimeout(() => {
            overlay.style.display = 'none';
            toggleEmailForm(false); // Reset to social view
        }, 300);
        document.body.style.overflow = '';
    };

    let currentSignupStep = 1;

    window.toggleEmailForm = function (show) {
        const socialView = document.getElementById('auth-social-view');
        const emailView = document.getElementById('auth-email-view');
        if (show) {
            socialView.style.display = 'none';
            emailView.style.display = 'flex';
            currentSignupStep = 1;
            toggleAuthMode('login'); // Default
        } else {
            socialView.style.display = 'block';
            emailView.style.display = 'none';
        }
    };

    window.toggleAuthMode = function (mode) {
        const title = document.getElementById('email-view-title');
        const subtitle = document.getElementById('email-view-subtitle');
        const btnNext = document.getElementById('btn-next');
        const toggle = document.getElementById('auth-mode-toggle');
        const dots = document.getElementById('signup-dots');

        const signupFields = document.getElementById('signup-only-fields');
        const phoneGroup = document.getElementById('signup-phone-group');
        const passLabel = document.getElementById('pass-label');

        currentSignupStep = 1;
        updateStepUI();

        if (mode === 'signup') {
            title.innerText = 'Create Account';
            subtitle.innerText = 'Join QOON and start your social journey.';
            btnNext.innerText = 'Next';
            signupFields.style.display = 'block';
            phoneGroup.style.display = 'flex';
            dots.style.display = 'flex';
            passLabel.innerText = 'Choose Password';
            toggle.innerHTML = 'Already have an account? <span onclick="toggleAuthMode(\'login\')">Sign In</span>';
        } else {
            title.innerText = 'Sign In';
            subtitle.innerText = 'Access your account using your email.';
            btnNext.innerText = 'Continue';
            signupFields.style.display = 'none';
            phoneGroup.style.display = 'none';
            dots.style.display = 'none';
            passLabel.innerText = 'Password';
            toggle.innerHTML = 'Don\'t have an account? <span onclick="toggleAuthMode(\'signup\')">Sign Up</span>';
        }
    };

    window.handleNextClick = function () {
        const isLogin = document.getElementById('email-view-title').innerText === 'Sign In';

        if (isLogin) {
            // Login Flow: Step 1 (Email) -> Step 3 (Pass)
            if (currentSignupStep === 1) {
                if (!document.getElementById('auth-email').value) return alert('Enter email');
                currentSignupStep = 3;
                updateStepUI();
            } else {
                handleEmailAuth();
            }
        } else {
            // Signup Flow: 1 -> 2 -> 3
            if (currentSignupStep < 3) {
                if (currentSignupStep === 1) {
                    if (!document.getElementById('auth-name').value || !document.getElementById('auth-email').value) return alert('Fill fields');
                }
                currentSignupStep++;
                updateStepUI();
            } else {
                handleEmailAuth();
            }
        }
    };

    window.prevStep = function () {
        const isLogin = document.getElementById('email-view-title').innerText === 'Sign In';
        if (isLogin) currentSignupStep = 1;
        else currentSignupStep--;
        updateStepUI();
    };

    function updateStepUI() {
        document.querySelectorAll('.auth-step').forEach(s => s.classList.remove('active'));
        document.getElementById(`step-${currentSignupStep}`).classList.add('active');

        // Update dots
        const dots = document.querySelectorAll('.step-dot');
        dots.forEach((d, i) => {
            if (i < currentSignupStep) d.classList.add('active');
            else d.classList.remove('active');
        });

        // Update buttons
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const isLogin = document.getElementById('email-view-title').innerText === 'Sign In';

        btnPrev.style.display = (currentSignupStep > 1) ? 'block' : 'none';
        btnNext.innerText = (currentSignupStep === 3) ? (isLogin ? 'Sign In' : 'Create Account') : 'Next';
    }

    window.selectGender = function (val, el) {
        document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('auth-gender').value = val;
    };

    window.handleEmailAuth = function () {
        const btn = document.getElementById('btn-next');
        const name = document.getElementById('auth-name').value;
        const email = document.getElementById('auth-email').value;
        const phone = document.getElementById('auth-phone').value;
        const city = document.getElementById('auth-city').value;
        const gender = document.getElementById('auth-gender').value;
        const pass = document.getElementById('auth-password').value;

        const isSignUp = document.getElementById('email-view-title').innerText === 'Create Account';

        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        btn.disabled = true;

        const fd = new FormData();
        fd.append('AccountType', 'Email');
        fd.append('Email', email);
        fd.append('Password', pass);
        fd.append('UserFirebaseToken', '');

        if (isSignUp) {
            fd.append('Mode', 'Signup');
            fd.append('name', name);
            fd.append('UserPhone', phone);
            fd.append('City', city);
            fd.append('Gender', gender);
        } else {
            fd.append('Mode', 'Login');
        }

        fetch('LogOrSign.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Success!';
                    const urlP = new URLSearchParams(window.location.search);
                    const rTo = urlP.get('return_to');
                    setTimeout(() => {
                        if (rTo) window.location.href = rTo;
                        else location.reload();
                    }, 1000);
                } else {
                    alert(json.message || 'Error occurred');
                    btn.innerHTML = isSignUp ? 'Create Account' : 'Sign In';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection error');
                btn.innerHTML = isSignUp ? 'Create Account' : 'Sign In';
                btn.disabled = false;
            });
    };

    const signupOverlayEl = document.getElementById('signup-overlay');
    if (signupOverlayEl) {
        signupOverlayEl.addEventListener('click', (e) => {
            if (e.target === signupOverlayEl) window.closeSignup();
        });
    }
</script>
<!-- Firebase SDK (Compat Version) -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
<script src="assets/js/firebase-auth.js"></script>

