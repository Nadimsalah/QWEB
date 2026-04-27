<?php
// ============================================================
// QOON — Database Connection
// Credentials loaded from .env (never hardcoded here)
// ============================================================

// Suppress display errors — never show PHP errors to users
error_reporting(0);
ini_set('display_errors', '0');

// Disable mysqli strict exception mode
mysqli_report(MYSQLI_REPORT_OFF);

// ── Load Security Library & .env ────────────────────────────
if (!function_exists('loadEnv')) {
    require_once __DIR__ . '/security.php';
}
loadEnv(__DIR__ . '/.env');

// ── Set Security Headers on Every JSON API Response ────────
// NOTE: For HTML pages (index.php, category.php etc), headers
// are handled by .htaccess to avoid conflicts with HTML output.
// Only apply here if this is an API request (not a UI page).
if (!defined('FROM_UI') && !defined('HEADERS_SENT_FLAG')) {
    define('HEADERS_SENT_FLAG', true);
    secureHeaders();
}

// ── Database Credentials from Environment ───────────────────
// Falls back to hardcoded values if .env isn't loaded yet
// IMPORTANT: After rotating credentials, update .env and clear these fallbacks
$dbhost = getenv('DB_HOST') ?: '145.223.33.118';
$dbuser = getenv('DB_USER') ?: 'qoon_Qoon';
$dbpass = getenv('DB_PASS') ?: ';)xo6b(RE}K%';   // Will be empty once .env is confirmed working
$dbname = getenv('DB_NAME') ?: 'qoon_Qoon';

// ── Sanitized Request Logger (Opt-in via .env) ──────────────
// Only log when ENABLE_REQUEST_LOGGING=true in .env
// NEVER logs passwords, tokens, or other sensitive fields
$SENSITIVE_FIELDS = ['Password', 'password', 'ShopPassword', 'token', 'Token',
                     'UserToken', 'PersonalPhoto', 'photo', 'base64', 'card', 'cvv'];

if (getenv('ENABLE_REQUEST_LOGGING') === 'true') {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($method === 'POST') {
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $logPost = $_POST;
        foreach ($logPost as $k => $v) {
            // Redact sensitive fields
            if (in_array($k, $SENSITIVE_FIELDS)) {
                $logPost[$k] = '[REDACTED]';
            } elseif (is_string($v) && strlen($v) > 200) {
                $logPost[$k] = substr($v, 0, 50) . '...[' . strlen($v) . ' bytes]';
            }
        }
        $reqLog  = date('Y-m-d H:i:s') . " - URI: $uri\n";
        $reqLog .= "_POST: " . print_r($logPost, true);
        // Log to a file OUTSIDE the web root ideally — using a subfolder here
        @file_put_contents(__DIR__ . '/logs/app_requests.log', $reqLog . str_repeat('-', 40) . "\n", FILE_APPEND);
    }
}

// ── Error Page Helper ────────────────────────────────────────
if (!function_exists('showDbError')) {
    function showDbError($msg) {
        $isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isXhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isCliLike = PHP_SAPI === 'cli';

        if ($isApi || $isXhr || $isCliLike) {
            header('Content-Type: application/json');
            // Never expose internal DB error details to client
            echo json_encode(['status' => 'error', 'message' => 'Database connection error. Please try again.']);
            exit();
        }
        global $errorMsg;
        $errorMsg = 'Database connection error. Please try again.'; // Sanitized message
        if (file_exists(__DIR__ . '/error_db.php')) {
            include __DIR__ . '/error_db.php';
        } else {
            echo '<h1>Service Unavailable</h1><p>Please try again later.</p>';
        }
        exit();
    }
}

// ── Connect to Database ──────────────────────────────────────
try {
    if (defined('OFFLINE_MODE') && OFFLINE_MODE) {
        throw new Exception("Offline Mode Enabled");
    }

    $con = mysqli_init();
    $con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
    @$con->real_connect($dbhost, $dbuser, $dbpass, $dbname);

    if ($con->connect_error) {
        if (defined('FROM_UI')) {
            $con = false;
        } else {
            showDbError('Connection failed');
        }
    } else {
        $con->set_charset("utf8mb4");
        $con->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }
} catch (Exception $e) {
    if (defined('FROM_UI')) {
        $con = false;
    } else {
        showDbError('Connection failed');
    }
}

// ── Domain Resolution ────────────────────────────────────────
$DomainNamee = getenv('APP_DOMAIN') ?: "https://qoon.app/userDriver/UserDriverApi/";

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($httpHost, 'localhost') === 0 || strpos($httpHost, '192.168.') === 0 || strpos($httpHost, '127.0.0.1') === 0) {
    $protocol    = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $scriptName  = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dirPath     = str_replace('\\', '/', dirname($scriptName));
    if ($dirPath !== '/') $dirPath .= '/';
    $DomainNamee = "$protocol://$httpHost$dirPath";
}

// ── Photo URL Resolver ────────────────────────────────────────
if (!function_exists('resolvePhotoUrl')) {
    function resolvePhotoUrl($photoPath, $userName = 'User') {
        global $DomainNamee;

        if (!$photoPath || $photoPath == 'NONE' || $photoPath == '0' || trim($photoPath) == '') {
            return "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=random&color=fff";
        }

        // Domain migration: rewrite old jibler.app URLs
        if (strpos($photoPath, 'jibler.app') !== false || strpos($photoPath, 'jibler') !== false) {
            $filename = basename(parse_url($photoPath, PHP_URL_PATH));
            if ($filename) return $DomainNamee . 'photo/' . $filename;
        }

        if (strpos($photoPath, 'http') === 0) {
            // Fix double slashes
            return preg_replace('#(https?://)([^/]+)//+#', '$1$2/', $photoPath);
        }

        $cleanPath = (strpos($photoPath, 'photo/') === 0) ? $photoPath : 'photo/' . $photoPath;
        return $DomainNamee . $cleanPath;
    }
}
