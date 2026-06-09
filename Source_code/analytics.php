<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
enforceFeature($pdo, $_SESSION['business_id'], 'analytics_enabled');
$businessId = $_SESSION['business_id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM payments
    WHERE business_id=? AND status='COMPLETED'
");
$stmt->execute([$businessId]);
$totalInvoiceRevenue = $stmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(trl.total_amount),0)
    FROM trip_revenue_lines trl
    JOIN trips t ON trl.trip_id=t.id
    WHERE t.business_id=?
");
$stmt->execute([$businessId]);
$totalTransportRevenue = $stmt->fetchColumn();
$totalRevenue = $totalInvoiceRevenue + $totalTransportRevenue;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE business_id=?");
$stmt->execute([$businessId]);
$totalInvoices = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE business_id=?");
$stmt->execute([$businessId]);
$totalCustomers = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE business_id=?");
$stmt->execute([$businessId]);
$totalTrips = $stmt->fetchColumn();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analytics</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{
  margin:0;
  font-family:system-ui;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
  padding:20px;
}
.container{max-width:1100px;margin:auto;}
.card{
  background:rgba(255,255,255,.2);
  backdrop-filter:blur(6px);
  padding:20px;
  border-radius:16px;
  margin-bottom:20px;
}
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:15px;
}
.metric{
  background:rgba(255,255,255,.25);
  padding:18px;
  border-radius:14px;
}
h2{text-align:center;margin-top:0;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Business Analytics</h2>
<div class="grid">
<div class="metric">
<strong>Total Revenue</strong>
<h3>KES <?= number_format($totalRevenue,2) ?></h3>
</div>
<div class="metric">
<strong>Invoice Revenue</strong>
<h3>KES <?= number_format($totalInvoiceRevenue,2) ?></h3>
</div>
<div class="metric">
<strong>Transport Revenue</strong>
<h3>KES <?= number_format($totalTransportRevenue,2) ?></h3>
</div>
<div class="metric">
<strong>Total Invoices</strong>
<h3><?= number_format($totalInvoices) ?></h3>
</div>
<div class="metric">
<strong>Total Customers</strong>
<h3><?= number_format($totalCustomers) ?></h3>
</div>
<div class="metric">
<strong>Total Trips</strong>
<h3><?= number_format($totalTrips) ?></h3>
</div>
</div>
</div>
</div>
</body>
</html>
