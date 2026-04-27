<?php
// ============================================================
// QOON — One-Time Password Migration Script
// Hashes all plain-text passwords in Users and Shops tables
//
// !! RUN ONCE ON THE SERVER, THEN DELETE THIS FILE !!
// Access is blocked by .htaccess — you must run via CLI or
// temporarily rename to access it via browser.
//
// Usage (CLI): php migrate_passwords_RUNONCE.php
// ============================================================

// Security: only allow CLI execution or authorized IP
$allowedIPs = ['127.0.0.1', '::1'];
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIPs)) {
    http_response_code(403);
    die('Forbidden — run via CLI: php migrate_passwords_RUNONCE.php');
}

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/security.php';

$updatedUsers = 0;
$updatedShops = 0;
$skipped      = 0;
$errors       = 0;

echo "🔐 QOON Password Migration Script\n";
echo "===================================\n\n";

// ── Migrate Users table ───────────────────────────────────────
echo "Migrating Users table...\n";
$res = $con->query("SELECT UserID, Password FROM Users WHERE AccountType='Email' AND Password IS NOT NULL");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $uid  = $row['UserID'];
        $pass = $row['Password'];

        // Skip if already bcrypt
        if (strpos($pass, '$2y$') === 0 || strpos($pass, '$2b$') === 0) {
            $skipped++;
            continue;
        }

        // Skip empty passwords
        if (empty($pass)) {
            $skipped++;
            continue;
        }

        $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $con->prepare("UPDATE Users SET Password=? WHERE UserID=?");
        if ($stmt) {
            $stmt->bind_param("si", $hashed, $uid);
            if ($stmt->execute()) {
                $updatedUsers++;
                echo "  ✅ User #$uid hashed\n";
            } else {
                $errors++;
                echo "  ❌ User #$uid failed: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
}

echo "\nMigrating Shops table...\n";
$res2 = $con->query("SELECT ShopID, ShopPassword FROM Shops WHERE ShopPassword IS NOT NULL");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $sid  = $row['ShopID'];
        $pass = $row['ShopPassword'];

        if (strpos($pass, '$2y$') === 0 || strpos($pass, '$2b$') === 0) {
            $skipped++;
            continue;
        }

        if (empty($pass)) {
            $skipped++;
            continue;
        }

        $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $con->prepare("UPDATE Shops SET ShopPassword=? WHERE ShopID=?");
        if ($stmt) {
            $stmt->bind_param("si", $hashed, $sid);
            if ($stmt->execute()) {
                $updatedShops++;
                echo "  ✅ Shop #$sid hashed\n";
            } else {
                $errors++;
                echo "  ❌ Shop #$sid failed: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
}

echo "\n===================================\n";
echo "✅ Users migrated:  $updatedUsers\n";
echo "✅ Shops migrated:  $updatedShops\n";
echo "⏭️  Already hashed (skipped): $skipped\n";
echo "❌ Errors:          $errors\n\n";
echo "🗑️  IMPORTANT: Delete this file now!\n";
echo "   rm migrate_passwords_RUNONCE.php\n";
