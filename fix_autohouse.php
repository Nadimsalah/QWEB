<?php
require_once 'conn.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #111; color: #fff; }
        .card { background: #222; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #333; }
        .success { color: #4ade80; }
        .error { color: #ef4444; }
        .warning { color: #fbbf24; }
        input[type=text] { padding: 10px; width: 300px; background: #333; color: white; border: 1px solid #555; border-radius: 5px; }
        button { padding: 10px 20px; background: #2cb5e8; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #1e88e5; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
    </style>
</head>
<body>
    <h2>Diagnose User Account Issues</h2>
    <div class="card">
        <form method="GET">
            <input type="text" name="username" placeholder="Enter username (e.g., autohouse)" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
            <button type="submit">Check User</button>
        </form>
    </div>

    <?php
    if (!empty($_GET['username'])) {
        $username = $con->real_escape_string($_GET['username']);
        
        // 1. Find the user
        $res = $con->query("SELECT UserID, name, PhoneNumber, Email, Balance, AccountState, UserToken FROM Users WHERE name LIKE '%$username%' LIMIT 1");
        
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            echo "<div class='card'>";
            echo "<h3>User Found: " . htmlspecialchars($user['name']) . " (ID: {$user['UserID']})</h3>";
            echo "<table>";
            
            // Check Account State
            echo "<tr><td>Account State</td>";
            if (stripos($user['AccountState'], 'suspend') !== false || stripos($user['AccountState'], 'ban') !== false) {
                echo "<td class='error'><b>{$user['AccountState']}</b> (This blocks them from sending money)</td></tr>";
            } else {
                echo "<td class='success'><b>{$user['AccountState']}</b> (OK)</td></tr>";
            }
            
            // Check Balance
            echo "<tr><td>Current Balance</td>";
            if ($user['Balance'] <= 0) {
                echo "<td class='error'><b>{$user['Balance']} MAD</b> (Cannot send money with 0 balance)</td></tr>";
            } else {
                echo "<td class='success'><b>{$user['Balance']} MAD</b> (OK)</td></tr>";
            }
            
            // Check Token
            echo "<tr><td>User Token</td>";
            if (empty($user['UserToken']) || $user['UserToken'] === 's') {
                echo "<td class='error'><b>Missing/Invalid</b> (User must log out and log back in to generate a token)</td></tr>";
            } else {
                echo "<td class='success'><b>Valid</b> (" . substr($user['UserToken'], 0, 10) . "... OK)</td></tr>";
            }

            // Check Phone Number (for receiving)
            echo "<tr><td>Phone Number</td>";
            if (empty($user['PhoneNumber'])) {
                echo "<td class='warning'><b>Missing</b> (Other users cannot search for them by phone)</td></tr>";
            } else {
                echo "<td class='success'><b>{$user['PhoneNumber']}</b> (OK)</td></tr>";
            }
            
            echo "</table>";
            echo "</div>";

            // Fix actions
            echo "<div class='card'>";
            echo "<h3>Quick Fixes</h3>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='fix_user_id' value='{$user['UserID']}'>";
            echo "<button type='submit' name='action' value='unban' style='background:#4ade80; color:#000;'>Set Account to Active</button> ";
            echo "<button type='submit' name='action' value='add_balance' style='background:#f59e0b; color:#000;'>Add 100 MAD Test Balance</button>";
            echo "</form>";
            echo "</div>";
            
        } else {
            echo "<div class='card error'>User not found in the Users table. Are you sure they aren't registered as a Shop or Driver?</div>";
        }
    }

    // Handle Quick Fixes
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['fix_user_id'])) {
        $id = (int)$_POST['fix_user_id'];
        $action = $_POST['action'] ?? '';
        
        if ($action === 'unban') {
            $con->query("UPDATE Users SET AccountState = 'Active' WHERE UserID = $id");
            echo "<script>alert('Account set to Active!'); window.location.href='?username=" . urlencode($_GET['username']) . "';</script>";
        } elseif ($action === 'add_balance') {
            $con->query("UPDATE Users SET Balance = Balance + 100 WHERE UserID = $id");
            echo "<script>alert('100 MAD added!'); window.location.href='?username=" . urlencode($_GET['username']) . "';</script>";
        }
    }
    ?>
</body>
</html>
