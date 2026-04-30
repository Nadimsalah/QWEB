<?php
// ============================================================
// QOON Security Library — Include this in every API endpoint
// Provides: auth, rate limiting, input sanitization, headers
// ============================================================

// ── Load .env file ──────────────────────────────────────────
function loadEnv($path = null) {
    $envFile = $path ?? __DIR__ . '/.env';
    if (!file_exists($envFile)) return;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $i => $line) {
        // Strip UTF-8 BOM from first line
        if ($i === 0) $line = ltrim($line, "\xEF\xBB\xBF");
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        if (!empty($name) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// ── Security Response Headers ────────────────────────────────
function secureHeaders() {
    header('X-Frame-Options: SAMEORIGIN');
    // Set default Content-Type for API responses to JSON to prevent mobile app parsing errors
    header('Content-Type: application/json; charset=utf-8');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
    // Only add HSTS on HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// ── Rate Limiter ─────────────────────────────────────────────
/**
 * Simple DB-based rate limiter.
 * Returns true if request is allowed, false if rate limited.
 * Automatically creates the RateLimits table if it doesn't exist.
 */
function rateLimit($con, string $action, string $identifier, int $maxRequests = 10, int $windowSeconds = 60): bool {
    if (!$con) return true; // Skip if no DB

    // Create table if needed
    $con->query("CREATE TABLE IF NOT EXISTS RateLimits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(64) NOT NULL,
        identifier VARCHAR(128) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT NOW(),
        INDEX idx_action_id (action, identifier, created_at)
    ) ENGINE=InnoDB");

    // Clean old entries
    $con->query("DELETE FROM RateLimits WHERE created_at < NOW() - INTERVAL $windowSeconds SECOND");

    // Count recent requests
    $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM RateLimits WHERE action=? AND identifier=? AND created_at > NOW() - INTERVAL ? SECOND");
    if (!$stmt) return true;
    $stmt->bind_param("ssi", $action, $identifier, $windowSeconds);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row['cnt'] >= $maxRequests) {
        return false; // Rate limited
    }

    // Log this request
    $stmt2 = $con->prepare("INSERT INTO RateLimits (action, identifier) VALUES (?, ?)");
    if ($stmt2) {
        $stmt2->bind_param("ss", $action, $identifier);
        $stmt2->execute();
        $stmt2->close();
    }

    return true;
}

// ── Token Auth ───────────────────────────────────────────────
/**
 * Validates the user token (from header 'token' or POST 'token').
 * Returns the authenticated user row on success.
 * Echoes JSON error and exits on failure.
 */
function requireAuth($con, bool $dieOnFail = true): ?array {
    // Get token from header first, then POST body
    $token = '';
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'token') {
            $token = $value;
            break;
        }
    }
    if (empty($token)) {
        $token = $_POST['token'] ?? $_GET['token'] ?? '';
    }

    if (empty($token) || $token === 's') {
        if ($dieOnFail) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized — missing token', 'status_code' => 401]);
            exit;
        }
        return null;
    }

    if (!$con) {
        if ($dieOnFail) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'Service unavailable']);
            exit;
        }
        return null;
    }

    $stmt = $con->prepare("SELECT UserID, name, UserPhoto, Email, PhoneNumber, Balance, AccountState FROM Users WHERE UserToken=? LIMIT 1");
    if (!$stmt) {
        if ($dieOnFail) { echo json_encode(['success'=>false,'message'=>'Auth error']); exit; }
        return null;
    }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        if ($dieOnFail) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized — invalid token', 'status_code' => 401]);
            exit;
        }
        return null;
    }

    // Block explicitly suspended accounts. Accept 'Active', 'NewAccount', etc.
    $accountState = $user['AccountState'] ?? 'Active';
    if (stripos($accountState, 'suspend') !== false || stripos($accountState, 'ban') !== false) {
        if ($dieOnFail) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Account is suspended']);
            exit;
        }
        return null;
    }

    return $user;
}

/**
 * Same as requireAuth but for drivers.
 */
function requireDriverAuth($con, bool $dieOnFail = true): ?array {
    $token = '';
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'token') { $token = $value; break; }
    }
    if (empty($token)) $token = $_POST['token'] ?? '';

    if (empty($token) || $token === 's') {
        if ($dieOnFail) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        return null;
    }

    $stmt = $con->prepare("SELECT DriverID, FName, LName, DriverPhone, LANG FROM Drivers WHERE DriverToken=? LIMIT 1");
    if (!$stmt) { if ($dieOnFail) { echo json_encode(['success'=>false,'message'=>'Auth error']); exit; } return null; }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $driver = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$driver) {
        if ($dieOnFail) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        return null;
    }
    return $driver;
}

// ── Input Sanitizers ─────────────────────────────────────────
function sanitizeInt($val, int $default = 0): int {
    return is_numeric($val) ? (int)$val : $default;
}

function sanitizeFloat($val, float $default = 0.0): float {
    return is_numeric($val) ? (float)$val : $default;
}

function sanitizeString($val, int $maxLen = 500): string {
    return substr(trim((string)$val), 0, $maxLen);
}

function sanitizeEmail($val): string {
    return filter_var(trim($val), FILTER_SANITIZE_EMAIL) ?: '';
}

// ── File Upload Validator ────────────────────────────────────
/**
 * Validates an uploaded file for type, size, and extension.
 * Returns ['ok'=>true, 'ext'=>'jpg'] or ['ok'=>false, 'error'=>'...']
 */
function validateFileUpload(array $file, int $maxBytes = 5242880): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'File too large. Max ' . ($maxBytes / 1048576) . 'MB allowed.'];
    }

    $allowedMimes = [
        'image/jpeg'  => 'jpg',
        'image/jpg'   => 'jpg',
        'image/png'   => 'png',
        'image/gif'   => 'gif',
        'image/webp'  => 'webp',
    ];

    // Use finfo for real MIME detection (not user-supplied)
    if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        // Fallback: getimagesize
        $imgInfo  = @getimagesize($file['tmp_name']);
        $mimeType = $imgInfo ? $imgInfo['mime'] : '';
    }

    if (!isset($allowedMimes[$mimeType])) {
        return ['ok' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }

    return ['ok' => true, 'ext' => $allowedMimes[$mimeType], 'mime' => $mimeType];
}

// ── Safe cURL Wrapper ────────────────────────────────────────
/**
 * Makes an HTTP POST request with proper SSL verification.
 * Returns ['code'=>200, 'body'=>'...'] or ['code'=>0, 'error'=>'...']
 */
function safePost(string $url, array $headers, string $body, int $timeout = 15): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,   // ← Always verify in production
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $result  = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error   = curl_error($ch);
    curl_close($ch);

    if ($error) return ['code' => 0, 'error' => $error, 'body' => ''];
    return ['code' => $code, 'body' => $result];
}

function safeGet(string $url, array $headers = [], int $timeout = 15): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $result  = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error   = curl_error($ch);
    curl_close($ch);

    if ($error) return ['code' => 0, 'error' => $error, 'body' => ''];
    return ['code' => $code, 'body' => $result];
}

// ── CORS Helper ──────────────────────────────────────────────
function secureCors(array $allowedOrigins = ['https://qoon.app', 'https://www.qoon.app']) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Vary: Origin");
    }
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, token, Authorization');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// ── Password Helpers ─────────────────────────────────────────
function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifies a password. Handles both bcrypt and legacy plain-text.
 * If plain-text matches, automatically upgrades to bcrypt in DB.
 */
function verifyPassword(string $plain, string $stored, $con, string $table, string $idField, int $id): bool {
    // Already bcrypt? (PHP 7 compatible check)
    if (strpos($stored, '$2y$') === 0 || strpos($stored, '$2b$') === 0) {
        return password_verify($plain, $stored);
    }

    // Legacy plain-text comparison + auto-upgrade
    if ($plain === $stored) {
        $newHash = hashPassword($plain);
        $stmt = $con->prepare("UPDATE $table SET Password=? WHERE $idField=?");
        if ($stmt) {
            $stmt->bind_param("si", $newHash, $id);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }
    return false;
}

// ── Client IP ────────────────────────────────────────────────
function getClientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}
