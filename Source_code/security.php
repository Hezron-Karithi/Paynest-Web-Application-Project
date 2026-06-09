<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: admin_login.php");
    exit;
}
$pdo->exec("
CREATE TABLE IF NOT EXISTS user_2fa (
  user_id INT PRIMARY KEY,
  secret VARCHAR(64),
  enabled TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$message = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
$current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 10) {
            $error = "Password must be at least 10 characters.";
        } elseif ($new !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($current, $hash)) {
                $error = "Current password incorrect.";
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
                $upd->execute([$newHash, $userId]);
                $message = "Password updated successfully.";
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_others'])) {

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        // Assumes session table exists elsewhere; delete others only
        $pdo->prepare("DELETE FROM user_sessions WHERE user_id=? AND session_id<>?")
            ->execute([$userId, session_id()]);

        $message = "Other sessions logged out.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_2fa'])) {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $secret = bin2hex(random_bytes(10));
        $stmt = $pdo->prepare("REPLACE INTO user_2fa (user_id, secret, enabled) VALUES (?,?,1)");
        $stmt->execute([$userId, $secret]);
        $message = "2FA enabled. Secret: " . $secret;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $stmt = $pdo->prepare("UPDATE user_2fa SET enabled=0 WHERE user_id=?");
        $stmt->execute([$userId]);
        $message = "2FA disabled.";
    }
}
$stmt = $pdo->prepare("SELECT enabled FROM user_2fa WHERE user_id=?");
$stmt->execute([$userId]);
$twofaEnabled = (bool)$stmt->fetchColumn();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Security</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{margin:0;font-family:system-ui;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff;padding:20px;}
.container{max-width:900px;margin:auto;}
.card{background:rgba(255,255,255,.2);backdrop-filter:blur(6px);padding:20px;border-radius:16px;margin-bottom:20px;}
input{
  width:100%;
  max-width:100%;
  box-sizing:border-box;
  padding:12px;
  margin-bottom:10px;
  border:2px solid var(--orange);
  border-radius:10px;
}
.btn{background:var(--orange);border:none;color:#fff;padding:12px 20px;border-radius:10px;cursor:pointer;font-weight:600;}
.msg{color:#06d6a0;text-align:center;}
.err{color:#ffd6d6;text-align:center;}
h2{text-align:center;margin-top:0;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Change Password</h2>
<?php if($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?=$csrf?>">
<input type="hidden" name="change_password" value="1">
<label>Current Password</label>
<input type="password" name="current_password" required>
<label>New Password</label>
<input type="password" name="new_password" required>
<label>Confirm Password</label>
<input type="password" name="confirm_password" required>
<button class="btn">Update Password</button>
</form>
</div>
<div class="card">
<h2>Two Factor Authentication</h2>
<p>Status: <strong><?= $twofaEnabled ? 'Enabled' : 'Disabled' ?></strong></p>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?=$csrf?>">
<button name="enable_2fa" class="btn">Enable 2FA</button>
</form>
<form method="POST" style="margin-top:10px;">
<input type="hidden" name="csrf_token" value="<?=$csrf?>">
<button name="disable_2fa" class="btn">Disable 2FA</button>
</form>
</div>
<div class="card">
<h2>Logout Other Sessions</h2>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?=$csrf?>">
<button name="logout_others" class="btn">Logout Other Sessions</button>
</form>
</div>
</div>
</body>
</html>
