<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
$message = "";
$error = "";
$pdo->exec("
CREATE TABLE IF NOT EXISTS business_settings (
  business_id INT PRIMARY KEY,
  business_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(50),
  address TEXT,
  timezone VARCHAR(100) DEFAULT 'UTC',
  currency VARCHAR(10) DEFAULT 'KES',
  logo VARCHAR(255),
  notifications_email TINYINT(1) DEFAULT 1,
  notifications_sms TINYINT(1) DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$stmt = $pdo->prepare("SELECT * FROM business_settings WHERE business_id=?");
$stmt->execute([$businessId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $pdo->prepare("INSERT INTO business_settings (business_id) VALUES (?)")->execute([$businessId]);
    $stmt->execute([$businessId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $timezone = $_POST['timezone'] ?? 'UTC';
    $currency = $_POST['currency'] ?? 'KES';
    $notifEmail = isset($_POST['notifications_email']) ? 1 : 0;
    $notifSms = isset($_POST['notifications_sms']) ? 1 : 0;
    if (!$name) {
        $error = "Business name is required.";
    } else {
 $logoPath = $settings['logo'];
        if (!empty($_FILES['logo']['name'])) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp'];
            if (!in_array($ext, $allowed)) {
                $error = "Invalid logo format.";
            } else {
                $fileName = "logo_" . $businessId . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName);
                $logoPath = "uploads/" . $fileName;
            }
        }
        if (!$error) {
            $stmt = $pdo->prepare("
                UPDATE business_settings SET
                  business_name=?,
                  email=?,
                  phone=?,
                  address=?,
                  timezone=?,
                  currency=?,
                  logo=?,
                  notifications_email=?,
                  notifications_sms=?
                WHERE business_id=?
            ");
            $stmt->execute([
                $name,
                $email,
                $phone,
                $address,
                $timezone,
                $currency,
                $logoPath,
                $notifEmail,
                $notifSms,
                $businessId
            ]);
            $message = "Settings saved successfully.";
            $stmt = $pdo->prepare("SELECT * FROM business_settings WHERE business_id=?");
            $stmt->execute([$businessId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;}
body{margin:0;font-family:system-ui;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff;padding:20px;}
.container{max-width:900px;margin:auto;}
.card{background:rgba(255,255,255,.2);backdrop-filter:blur(6px);padding:20px;border-radius:16px;overflow:hidden;}
input,textarea,select{
  width:100%;
  max-width:100%;
  padding:12px;
  margin-bottom:12px;
  border:2px solid var(--orange);
  border-radius:10px;
}
.btn{background:var(--orange);border:none;color:#fff;padding:12px 20px;border-radius:10px;font-weight:600;cursor:pointer;}
.msg{color:#06d6a0;text-align:center;}
.err{color:#ffd6d6;text-align:center;}
img.logo{max-height:80px;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Business Settings</h2>
<?php if($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<label>Business Name</label>
<input name="business_name" value="<?=htmlspecialchars($settings['business_name'] ?? '')?>" required>
<label>Email</label>
<input name="email" value="<?=htmlspecialchars($settings['email'] ?? '')?>">
<label>Phone</label>
<input name="phone" value="<?=htmlspecialchars($settings['phone'] ?? '')?>">
<label>Address</label>
<textarea name="address"><?=htmlspecialchars($settings['address'] ?? '')?></textarea>
<label>Timezone</label>
<select name="timezone">
<option value="UTC" <?=($settings['timezone']=='UTC'?'selected':'')?>>UTC</option>
<option value="Africa/Nairobi" <?=($settings['timezone']=='Africa/Nairobi'?'selected':'')?>>Africa/Nairobi</option>
<option value="Europe/London" <?=($settings['timezone']=='Europe/London'?'selected':'')?>>Europe/London</option>
<option value="America/New_York" <?=($settings['timezone']=='America/New_York'?'selected':'')?>>America/New_York</option>
</select>
<label>Currency</label>
<select name="currency">
<option value="KES" <?=($settings['currency']=='KES'?'selected':'')?>>KES</option>
<option value="USD" <?=($settings['currency']=='USD'?'selected':'')?>>USD</option>
<option value="EUR" <?=($settings['currency']=='EUR'?'selected':'')?>>EUR</option>
</select>
<label>Logo</label>
<?php if(!empty($settings['logo'])): ?>
<img src="<?=htmlspecialchars($settings['logo'])?>" class="logo">
<?php endif; ?>
<input type="file" name="logo">
<label><input type="checkbox" name="notifications_email" <?=($settings['notifications_email']?'checked':'')?>> Email Notifications</label>
<label><input type="checkbox" name="notifications_sms" <?=($settings['notifications_sms']?'checked':'')?>> SMS Notifications</label>
<br><br>
<button class="btn">Save Settings</button>
</form>
</div>
</div>
</body>
</html>
