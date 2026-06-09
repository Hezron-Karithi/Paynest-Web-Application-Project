<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM business_settings WHERE business_id = ? LIMIT 1");
$stmt->execute([$businessId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $settings = [
        'business_name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'timezone' => 'UTC',
        'currency' => 'KES',
        'logo' => '',
        'notifications_email' => 1,
        'notifications_sms' => 0
    ];
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Business Profile</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  min-height:100vh;
  padding:20px;
}
.container{max-width:900px;margin:auto;}
.card{
  background:#fff;
  border-radius:16px;
  padding:32px 24px 24px 24px;
  box-shadow:0 10px 25px rgba(0,0,0,0.1);
  position:relative;
}
.header-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
}
h2{margin:0;}
.logo-wrapper{
  position:relative;
  width:70px;
  height:70px;
}

.logo-wrapper img{
  width:100%;
  height:100%;
  border-radius:50%;
  object-fit:cover;
  box-shadow:0 4px 10px rgba(0,0,0,0.2);
}
.online-dot{
  position:absolute;
  bottom:4px;
  right:4px;
  width:14px;
  height:14px;
  background:#06d6a0;
  border-radius:50%;
  border:2px solid #fff;
}
.field{margin-bottom:14px;}
.label{font-weight:700;margin-bottom:4px;}
.value{
  padding:6px 0;
  border:none;
  background:transparent;
}
</style>
</head>
<body>
<div class="container">
<div class="card">
<div class="header-row">
<h2>Business Profile</h2>
<div class="logo-wrapper">
<?php if(!empty($settings['logo'])): ?>
<img src="<?= htmlspecialchars($settings['logo']) ?>" alt="Logo">
<?php else: ?>
<img src="https://via.placeholder.com/70" alt="Logo">
<?php endif; ?>
<div class="online-dot"></div>
</div>
</div>
<form>
<div class="field">
<div class="label">Business Name</div>
<div class="value"><?= htmlspecialchars($settings['business_name'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Email</div>
<div class="value"><?= htmlspecialchars($settings['email'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Phone</div>
<div class="value"><?= htmlspecialchars($settings['phone'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Address</div>
<div class="value"><?= htmlspecialchars($settings['address'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Timezone</div>
<div class="value"><?= htmlspecialchars($settings['timezone'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Currency</div>
<div class="value"><?= htmlspecialchars($settings['currency'] ?? '') ?></div>
</div>
<div class="field">
<div class="label">Notifications Email</div>
<div class="value"><?= !empty($settings['notifications_email']) ? 'Enabled' : 'Disabled' ?></div>
</div>
<div class="field">
<div class="label">Notifications SMS</div>
<div class="value"><?= !empty($settings['notifications_sms']) ? 'Enabled' : 'Disabled' ?></div>
</div>
</form>
</div>
</div>
</body>
</html>
