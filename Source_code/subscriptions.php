<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
if (!$businessId) {
    die('Invalid session.');
}
$plansStmt = $pdo->query("
  SELECT id, name, price_usd, vat_percent, is_active
  FROM subscription_plans
  WHERE is_active = 1
  ORDER BY price_usd ASC
");
$plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

$subStmt = $pdo->prepare("
  SELECT s.*, p.name AS plan_name, p.price_usd, p.vat_percent
  FROM subscriptions s
  JOIN subscription_plans p ON p.id = s.plan_id
  WHERE s.business_id = ?
  ORDER BY s.id DESC
  LIMIT 1
");
$subStmt->execute([$businessId]);
$currentSub = $subStmt->fetch(PDO::FETCH_ASSOC);
function usdToKes($usd){
  return round($usd * 185, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Subscriptions</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;min-height:100vh;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
}
header{
  padding:16px 22px;display:flex;justify-content:space-between;align-items:center
}
header h2{margin:0;font-size:22px}
.back{
  background:#fff;color:var(--orange);
  padding:8px 14px;border-radius:8px;
  text-decoration:none;font-weight:600
}
.container{max-width:1200px;margin:auto;padding:20px}
.card{
  background:#fff;color:#333;border-radius:16px;
  padding:20px;margin-bottom:22px
}
.badge{
  display:inline-block;padding:6px 12px;border-radius:20px;
  font-size:12px;font-weight:800
}
.active{background:#06d6a0;color:#fff}
.expired{background:#ef476f;color:#fff}
.plans{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px
}
.plan{
  border:2px solid var(--orange);
  border-radius:16px;
  padding:20px;
  text-align:center
}
.price{font-size:28px;font-weight:800;color:var(--orange)}
.vat{font-size:13px;color:#666}
.btn{
  background:var(--orange);color:#fff;border:none;
  padding:10px 18px;border-radius:8px;
  cursor:pointer;font-weight:700;margin-top:14px
}
.btn:hover{background:var(--orange-dark)}
.btn-outline{
  background:#fff;color:var(--orange);border:2px solid var(--orange)
}

input, select, textarea{
  width:100%;
  max-width:100%;
  box-sizing:border-box;
  border:2px solid var(--orange);
  border-radius:8px;
  padding:10px;
}
@media(max-width:600px){header h2{font-size:18px}}
</style>
</head>
<body>
<header>
  <h2>Subscription</h2>
  <a href="business_dashboard.php" class="back">Back</a>
</header>
<div class="container">
  <div class="card">
    <h3>Current Subscription</h3>
    <?php if($currentSub): ?>
      <p>
        <strong>Plan:</strong> <?=htmlspecialchars($currentSub['plan_name'])?><br>
        <strong>Status:</strong>
        <?php if($currentSub['status']==='ACTIVE'): ?>
          <span class="badge active">Active</span>
        <?php else: ?>
          <span class="badge expired"><?=htmlspecialchars($currentSub['status'])?></span>
        <?php endif; ?><br>
        <strong>Renewal Date:</strong> <?=htmlspecialchars($currentSub['ends_at'])?><br>
        <strong>Amount Paid:</strong>
        KES <?=number_format(usdToKes($currentSub['amount_usd'] * (1 + $currentSub['vat_percent']/100)),2)?>
      </p>
    <?php else: ?>
      <p>No active subscription.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Available Plans</h3>
    <div class="plans">
      <?php foreach($plans as $plan): ?>
        <?php
          $kes = usdToKes($plan['price_usd']);
          $vat = $plan['vat_percent'];
          $isCurrent = $currentSub && $currentSub['plan_id'] == $plan['id'];
        ?>
        <div class="plan">
          <h3><?=htmlspecialchars($plan['name'])?></h3>
          <div class="price">$<?=number_format($plan['price_usd'],2)?></div>
          <div class="vat">+<?=$vat?>% VAT</div>
          <p>KES ~<?=number_format($kes,2)?> / month</p>
          <?php if($isCurrent): ?>
            <button class="btn" disabled>Current Plan</button>
          <?php else: ?>
            <form method="post" action="start_subscription.php">
              <input type="hidden" name="plan_id" value="<?=$plan['id']?>">
              <button class="btn-outline btn">Choose Plan</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h3>Pay Subscription</h3>
    <p>VAT applies to subscriptions as required by law.</p>
    <form method="post" action="mpesa_stk_subscription.php">
      <button class="btn">
        <i class="fa-solid fa-mobile-screen-button"></i> Pay via M-Pesa
      </button>
    </form>
  </div>
</div>
</body>
</html>
