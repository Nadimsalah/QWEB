// Global Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyBJgv2Ltzm5ZMdgKNUcs8stCTJ9lHgFxBQ",
    authDomain: "jibler-37339.firebaseapp.com",
    databaseURL: "https://jibler-37339-default-rtdb.firebaseio.com",
    projectId: "jibler-37339",
    storageBucket: "jibler-37339.firebasestorage.app",
    messagingSenderId: "874793508550",
    appId: "1:874793508550:web:1e16215a9b53f2314a41c7",
    measurementId: "G-6NWSEM7BK9"
};

try {
    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }
    console.log("Firebase Initialized Successfully");
} catch (e) {
    console.error("Firebase Initialization Error:", e);
}

// Shared Server Login Function
function processServerLogin(user, accountType, providerId, btn, originalHtml) {
    if (btn) btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating...';
    
    const providerData = user.providerData && user.providerData.length > 0 ? user.providerData.find(p => p.providerId === providerId) || user.providerData[0] : null;
    const externalId = providerData ? providerData.uid : user.uid;

    const formData = new FormData();
    formData.append('AccountType', accountType);
    formData.append('SocialID', externalId);
    formData.append('name', user.displayName || (accountType + ' User'));
    formData.append('Email', user.email || (user.uid + '@' + accountType.toLowerCase() + '.com'));
    formData.append('Photo', user.photoURL || '');
    formData.append('UserFirebaseToken', '');

    fetch('LogOrSign.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Welcome!';
                const urlP = new URLSearchParams(window.location.search);
                const rTo = urlP.get('return_to');
                if (rTo) window.location.href = rTo;
                else location.reload();
            } else {
                if (btn) btn.innerHTML = originalHtml;
                alert('Backend Error: ' + (json.message || 'Unknown error'));
            }
        })
        .catch(err => {
            if (btn) btn.innerHTML = originalHtml;
            console.error("Fetch Error:", err);
            alert("Could not connect to server.");
        });
}

// Handle Fallback Redirects on Page Load
if (firebase.auth) {
    firebase.auth().getRedirectResult().then((result) => {
        if (result && result.user) {
            console.log("Redirect Auth Success:", result.user.email);
            const providerId = result.credential ? result.credential.providerId : 'google.com';
            const accountType = providerId.includes('apple') ? 'Apple' : 'Google';
            processServerLogin(result.user, accountType, providerId, null, null);
        }
    }).catch((error) => {
        console.error("Redirect Login Error:", error);
    });
}

// Global Google Login Flow
window.googleLogin = function () {
    if (!firebase.auth) {
        console.error("Firebase Auth SDK not loaded.");
        return;
    }
    const provider = new firebase.auth.GoogleAuthProvider();
    const btn = document.querySelector('.btn-google');
    const originalHtml = btn ? btn.innerHTML : 'Google';

    console.log("Starting Google Login...");
    if (btn) btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Loading...';

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    if (isMobile) {
        firebase.auth().signInWithRedirect(provider);
    } else {
        firebase.auth().signInWithPopup(provider).then((result) => {
            processServerLogin(result.user, 'Google', 'google.com', btn, originalHtml);
        }).catch((error) => {
            if (error.code === 'auth/popup-blocked') {
                console.warn("Popup blocked, falling back to redirect flow...");
                firebase.auth().signInWithRedirect(provider);
            } else {
                if (btn) btn.innerHTML = originalHtml;
                console.error("Firebase Login Error:", error.code, error.message);
                if (error.code === 'auth/unauthorized-domain') {
                    alert("Error: This domain is not authorized in Firebase Console. Add your domain to 'Authorized Domains' in Firebase Authentication settings.");
                } else {
                    alert("Google Login failed: " + error.message);
                }
            }
        });
    }
};

// Global Apple Login Flow
window.appleLogin = function () {
    if (!firebase.auth) {
        console.error("Firebase Auth SDK not loaded.");
        return;
    }
    const provider = new firebase.auth.OAuthProvider('apple.com');
    provider.addScope('email');
    provider.addScope('name');
    
    const btn = document.querySelector('.btn-apple');
    const originalHtml = btn ? btn.innerHTML : 'Apple';

    console.log("Starting Apple Login...");
    if (btn) btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Loading...';

    firebase.auth().signInWithPopup(provider).then((result) => {
        processServerLogin(result.user, 'Apple', 'apple.com', btn, originalHtml);
    }).catch((error) => {
        if (error.code === 'auth/popup-blocked') {
            console.warn("Popup blocked, falling back to redirect flow...");
            firebase.auth().signInWithRedirect(provider);
        } else {
            if (btn) btn.innerHTML = originalHtml;
            console.error("Firebase Apple Login Error:", error.code, error.message);
            alert("Apple Login failed: " + error.message);
        }
    });
};
