<?php
require_once 'db.php';
require_once 'auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['plan_id'])) {
    die("Invalid request.");
}
$planId = (int)$_POST['plan_id'];
$stmt = $pdo->prepare("
    SELECT id, name, price_usd, vat_percent
    FROM subscription_plans
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$planId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$plan) {
    die("Plan not found.");
}
$totalUsd = $plan['price_usd'] * (1 + $plan['vat_percent']/100);
if (isset($_POST['confirm'])) {
$pdo->prepare("
        UPDATE subscriptions
        SET status = 'EXPIRED'
        WHERE business_id = ? AND status = 'ACTIVE'
    ")->execute([$businessId]);
$stmtInsert = $pdo->prepare("
        INSERT INTO subscriptions
        (business_id, plan_id, status, amount_usd, vat_percent, starts_at, ends_at, created_at)
        VALUES (?, ?, 'ACTIVE', ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), NOW())
    ");
    $stmtInsert->execute([
        $businessId,
        $planId,
        $plan['price_usd'],
        $plan['vat_percent']
    ]);

    header("Location: business_dashboard.php?subscribed=1");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Start Subscription</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
body{
  margin:0;
  font-family:system-ui;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  min-height:100vh;
}
.card{
  background:#fff;
  color:#333;
  padding:24px;
  border-radius:16px;
  max-width:420px;
  width:100%;
  box-shadow:0 10px 30px rgba(0,0,0,.2);
}
h2{margin-top:0}
.btn{
  background:var(--orange);
  color:#fff;
  border:none;
  padding:12px 16px;
  border-radius:8px;
  cursor:pointer;
  font-weight:700;
  width:100%;
}
.btn:hover{background:var(--orange-dark)}
.summary{
  background:#f7f7f7;
  padding:12px;
  border-radius:8px;
  margin:14px 0;
}
</style>
</head>
<body>
<div class="card">
<h2>Confirm Subscription</h2>
<div class="summary">
<strong>Plan:</strong> <?=htmlspecialchars($plan['name'])?><br>
<strong>Price:</strong> $<?=number_format($plan['price_usd'],2)?><br>
<strong>VAT:</strong> <?=$plan['vat_percent']?>%<br>
<strong>Total:</strong> $<?=number_format($totalUsd,2)?>
</div>
<form method="post">
<input type="hidden" name="plan_id" value="<?=$planId?>">
<input type="hidden" name="confirm" value="1">
<button class="btn">Confirm & Activate</button>
</form>
</div>
</body>
</html>
