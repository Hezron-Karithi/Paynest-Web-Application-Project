<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
enforceFeature($pdo, $_SESSION['business_id'], 'collections_enabled');
$businessId = $_SESSION['business_id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM payments
    WHERE business_id=? AND status='COMPLETED'
");
$stmt->execute([$businessId]);
$totalInvoiceCollections = $stmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(trl.total_amount),0)
    FROM trip_revenue_lines trl
    JOIN trips t ON trl.trip_id=t.id
    WHERE t.business_id=?
");
$stmt->execute([$businessId]);
$totalTransportCollections = $stmt->fetchColumn();
$totalCollections = $totalInvoiceCollections + $totalTransportCollections;
$stmt = $pdo->prepare("
    SELECT p.amount, p.mpesa_code, p.created_at, i.invoice_number
    FROM payments p
    JOIN invoices i ON p.invoice_id=i.id
    WHERE p.business_id=? AND p.status='COMPLETED'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$businessId]);
$recentPayments = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT trl.total_amount, t.trip_date, v.plate_number
    FROM trip_revenue_lines trl
    JOIN trips t ON trl.trip_id=t.id
    JOIN vehicles v ON t.vehicle_id=v.id
    WHERE t.business_id=?
    ORDER BY t.trip_date DESC
    LIMIT 10
");
$stmt->execute([$businessId]);
$recentTransport = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Collections</title>
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
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.3);}
h2{text-align:center;margin-top:0;}
</style>
</head>
<body>
<div class="container">

<div class="card">
<h2>Collections Overview</h2>
<div class="grid">

<div class="metric">
<strong>Total Collections</strong>
<h3>KES <?= number_format($totalCollections,2) ?></h3>
</div>

<div class="metric">
<strong>Invoice Collections</strong>
<h3>KES <?= number_format($totalInvoiceCollections,2) ?></h3>
</div>

<div class="metric">
<strong>Transport Collections</strong>
<h3>KES <?= number_format($totalTransportCollections,2) ?></h3>
</div>

</div>
</div>

<div class="card">
<h3>Recent Invoice Payments</h3>
<div style="overflow-x:auto;">
<table>
<tr><th>Date</th><th>Invoice</th><th>Amount</th><th>M-Pesa Code</th></tr>
<?php foreach($recentPayments as $p): ?>
<tr>
<td><?= htmlspecialchars($p['created_at']) ?></td>
<td><?= htmlspecialchars($p['invoice_number']) ?></td>
<td>KES <?= number_format($p['amount'],2) ?></td>
<td><?= htmlspecialchars($p['mpesa_code']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>

<div class="card">
<h3>Recent Transport Revenue</h3>
<div style="overflow-x:auto;">
<table>
<tr><th>Date</th><th>Vehicle</th><th>Amount</th></tr>
<?php foreach($recentTransport as $t): ?>
<tr>
<td><?= htmlspecialchars($t['trip_date']) ?></td>
<td><?= htmlspecialchars($t['plate_number']) ?></td>
<td>KES <?= number_format($t['total_amount'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>

</div>
</body>
</html>
