<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
$stmtMonthInv = $pdo->prepare("
    SELECT IFNULL(SUM(amount),0) 
    FROM payments 
    WHERE business_id = ? 
    AND MONTH(created_at)=MONTH(CURDATE())
    AND YEAR(created_at)=YEAR(CURDATE())
");
$stmtMonthInv->execute([$businessId]);
$thisMonthInvoice = $stmtMonthInv->fetchColumn();
$stmtMonthTransport = $pdo->prepare("
    SELECT IFNULL(SUM(trl.total_amount),0)
    FROM trip_revenue_lines trl
    JOIN trips t ON trl.trip_id = t.id
    WHERE t.business_id = ?
    AND MONTH(t.trip_date)=MONTH(CURDATE())
    AND YEAR(t.trip_date)=YEAR(CURDATE())
");
$stmtMonthTransport->execute([$businessId]);
$thisMonthTransport = $stmtMonthTransport->fetchColumn();
$thisMonthRevenue = $thisMonthInvoice + $thisMonthTransport;
$stmtInvCount = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE business_id=?");
$stmtInvCount->execute([$businessId]);
$totalInvoices = $stmtInvCount->fetchColumn();
$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE business_id=? AND status='PENDING'");
$stmtPending->execute([$businessId]);
$pendingInvoices = $stmtPending->fetchColumn();
$stmtCustomers = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE business_id=?");
$stmtCustomers->execute([$businessId]);
$totalCustomers = $stmtCustomers->fetchColumn();
$stmtTripsOpen = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE business_id=? AND status='OPEN'");
$stmtTripsOpen->execute([$businessId]);
$openTrips = $stmtTripsOpen->fetchColumn();
$stmtRecent = $pdo->prepare("
    SELECT invoice_number, total_amount, status 
    FROM invoices 
    WHERE business_id=? 
    ORDER BY id DESC 
    LIMIT 50
");
$stmtRecent->execute([$businessId]);
$recentInvoices = $stmtRecent->fetchAll();
$stmtSettings = $pdo->prepare("SELECT * FROM business_settings WHERE business_id=? LIMIT 1");
$stmtSettings->execute([$businessId]);
$businessSettings = $stmtSettings->fetch();
$logoPath = $businessSettings['logo'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paynest Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{
  margin:0;
  background:#efe9e4;
  font-family:system-ui,-apple-system,Segoe UI,Roboto;
  padding-bottom:90px;
}
.header{
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
  padding:20px 16px 70px;
}
.header-row{display:flex;justify-content:space-between;align-items:center}
.brand{font-weight:700;font-size:18px}
.avatar-wrapper{
  position:relative;
  width:40px;
  height:40px;
}
.avatar{
  width:40px;
  height:40px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid #fff;
}
.online-dot{
  position:absolute;
  bottom:0;
  right:0;
  width:10px;
  height:10px;
  background:#22c55e;
  border-radius:50%;
  border:2px solid #fff;
}
.main{
  margin-top:-50px;
  background:#f3eee9;
  border-radius:26px;
  padding:16px;
}
.kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.kpi{background:#fff;border-radius:16px;padding:14px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.kpi small{color:#777}
.kpi h3{margin:6px 0 0;font-size:18px}
.panel{background:#fff;border-radius:16px;padding:16px;margin-top:16px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.panel-title{font-weight:700;margin-bottom:10px}
.scroll-list{
  max-height:190px;
  overflow-y:auto;
  border-top:1px solid #eee;
}
.invoice-row{
  padding:8px 0;
  border-bottom:1px solid #f0f0f0;
  font-size:13px;
}
.tools{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(100px,1fr));
  gap:10px;
  margin-top:16px;
}
.tool{
  background:#fff;
  border-radius:12px;
  padding:12px;
  text-align:center;
  text-decoration:none;
  color:#333;
  font-size:12px;
  box-shadow:0 2px 6px rgba(0,0,0,.08);
}
.tool i{display:block;margin-bottom:4px;color:var(--orange)}
.bottom-nav{
  position:fixed;bottom:0;left:0;right:0;
  background:#fff;border-top:1px solid #ddd;
  display:flex;justify-content:space-around;padding:8px 0;
}
.bottom-nav a{text-decoration:none;color:#666;font-size:12px;text-align:center}
.bottom-nav i{display:block;color:var(--orange);margin-bottom:2px}
</style>
</head>
<body>
<div class="header">
  <div class="header-row">
    <div class="brand">Paynest</div>
    <a href="business_profile.php" class="avatar-wrapper">
      <?php if($logoPath): ?>
        <img src="<?= htmlspecialchars($logoPath) ?>" class="avatar">
      <?php else: ?>
        <img src="assets/default-logo.png" class="avatar">
      <?php endif; ?>
      <span class="online-dot"></span>
    </a>
  </div>
</div>
<div class="main">
<div class="kpis">
  <div class="kpi">
    <small>Total Revenue</small>
    <h3>KES <?= number_format($thisMonthRevenue,2) ?></h3>
  </div>
  <div class="kpi">
    <small>Invoices</small>
    <h3><?= number_format($totalInvoices) ?></h3>
    <small>Pending <?= $pendingInvoices ?></small>
  </div>
  <div class="kpi">
    <small>Customers</small>
    <h3><?= number_format($totalCustomers) ?></h3>
  </div>
</div>
<div class="panel">
  <div class="panel-title">Revenue Overview</div>
  <canvas id="revChart" height="120"></canvas>
</div>
<div class="panel">
  <div class="panel-title">Trips / Jobs</div>
  Open Trips: <?= $openTrips ?>
</div>
<div class="panel">
  <div class="panel-title">Recent Invoices</div>
  <div class="scroll-list">
    <?php foreach($recentInvoices as $inv): ?>
      <div class="invoice-row">
        <?= htmlspecialchars($inv['invoice_number']) ?> — 
        <?= number_format($inv['total_amount'],2) ?> 
        (<?= $inv['status'] ?>)
      </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="tools">
  <a href="vehicles.php" class="tool"><i class="fa fa-bus"></i>Vehicles</a>
  <a href="drivers.php" class="tool"><i class="fa fa-id-card"></i>Drivers</a>
  <a href="routes.php" class="tool"><i class="fa fa-route"></i>Routes</a>
  <a href="trips.php" class="tool"><i class="fa fa-road"></i>Trips</a>
  <a href="analytics.php" class="tool"><i class="fa fa-chart-line"></i>Analytics</a>
  <a href="collections.php" class="tool"><i class="fa fa-wallet"></i>Collections</a>
  <a href="subscriptions.php" class="tool"><i class="fa fa-file-contract"></i>Subscriptions</a>
  <a href="reports.php" class="tool"><i class="fa fa-file-pdf"></i>Reports</a>
  <a href="security.php" class="tool"><i class="fa fa-shield-halved"></i>Security</a>
  <a href="support.php" class="tool"><i class="fa fa-circle-info"></i>Support</a>
</div>
</div>
<div class="bottom-nav">
  <a href="business_dashboard.php"><i class="fa fa-home"></i>Dashboard</a>
  <a href="invoices.php"><i class="fa fa-file-invoice"></i>Invoices</a>
  <a href="payments.php"><i class="fa fa-credit-card"></i>Payments</a>
  <a href="customers.php"><i class="fa fa-users"></i>Customers</a>
  <a href="settings.php"><i class="fa fa-gear"></i>Settings</a>
</div>
<script>
new Chart(document.getElementById('revChart'),{
 type:'line',
 data:{labels:['Jan','Feb','Mar','Apr','May','Jun'],
 datasets:[{data:[5,8,7,9,11,12],fill:true}]},
 options:{plugins:{legend:{display:false}}}
});
</script>
</body>
</html>
