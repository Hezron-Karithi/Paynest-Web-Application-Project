<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/feature_guard.php';
if (isset($_SESSION['business_id'])) {
    enforceFeature($pdo, $_SESSION['business_id'], 'reports_enabled');
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['business_id'])) {
    header("Location: admin_login.php");
    exit;
}
$business_id = $_SESSION['business_id'];
if (isset($_GET['delete_invoice'])) {
    $id = (int)$_GET['delete_invoice'];
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id=? AND business_id=?");
    $stmt->execute([$id, $business_id]);
    header("Location: reports.php");
    exit;
}
if (isset($_GET['delete_payment'])) {
    $id = (int)$_GET['delete_payment'];
    $stmt = $pdo->prepare("
        DELETE p FROM payments p
        JOIN invoices i ON p.invoice_id=i.id
        WHERE p.id=? AND i.business_id=?
    ");
    $stmt->execute([$id, $business_id]);
    header("Location: reports.php");
    exit;
}
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$dateFilterInvoices = "";
$dateFilterTrips = "";
$paramsInvoices = [$business_id];
$paramsTrips = [$business_id];
if (!empty($from) && !empty($to)) {
    $dateFilterInvoices = " AND DATE(i.created_at) BETWEEN ? AND ? ";
    $dateFilterTrips = " AND DATE(t.trip_date) BETWEEN ? AND ? ";
    $paramsInvoices[] = $from;
    $paramsInvoices[] = $to;
    $paramsTrips[] = $from;
    $paramsTrips[] = $to;
}
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount),0)
    FROM invoices i
    WHERE i.business_id = ? $dateFilterInvoices
");
$stmt->execute($paramsInvoices);
$totalInvoices = $stmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.amount),0)
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    WHERE i.business_id = ? $dateFilterInvoices
");
$stmt->execute($paramsInvoices);
$totalPaid = $stmt->fetchColumn();
$outstanding = $totalInvoices - $totalPaid;
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(trl.total_amount),0)
    FROM trip_revenue_lines trl
    JOIN trips t ON trl.trip_id = t.id
    WHERE t.business_id = ? $dateFilterTrips
");
$stmt->execute($paramsTrips);
$totalTransportRevenue = $stmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM trips t
    WHERE t.business_id = ? $dateFilterTrips
");
$stmt->execute($paramsTrips);
$totalTrips = $stmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT t.trip_date, v.plate_number, r.origin, r.destination,
           d.full_name, t.status,
           COALESCE(SUM(trl.total_amount),0) as revenue
    FROM trips t
    JOIN vehicles v ON t.vehicle_id=v.id
    JOIN routes r ON t.route_id=r.id
    JOIN drivers d ON t.driver_id=d.id
    LEFT JOIN trip_revenue_lines trl ON trl.trip_id=t.id
    WHERE t.business_id=? $dateFilterTrips
    GROUP BY t.id
    ORDER BY t.trip_date DESC
");
$stmt->execute($paramsTrips);
$tripReports = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));font-family:system-ui;padding:20px;}
.container{max-width:1200px;margin:auto;}
.card{background:#fff;border-radius:16px;padding:24px;margin-bottom:25px;}
h2,h3{text-align:center;margin-top:0;}
input{width:100%;max-width:100%;box-sizing:border-box;padding:12px;border:2px solid var(--orange);border-radius:10px;}
.btn{background:var(--orange);color:#fff;border:none;padding:14px 30px;border-radius:30px;font-weight:600;cursor:pointer;}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;}
.summary-card{background:rgba(255,122,0,.15);padding:18px;border-radius:14px;text-align:center;}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid #eee;text-align:left;}
.status-open{color:#f77f00;font-weight:700;}
.status-closed{color:#06d6a0;font-weight:700;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Filter Reports</h2>
<form method="GET">
<label>From</label>
<input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
<label>To</label>
<input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
<br><br>
<button class="btn">Apply Filter</button>
</form>
</div>
<div class="card">
<h3>Financial Summary</h3>
<div class="summary">
<div class="summary-card"><strong>Invoice Total</strong><br>KES <?=number_format($totalInvoices)?></div>
<div class="summary-card"><strong>Total Paid</strong><br>KES <?=number_format($totalPaid)?></div>
<div class="summary-card"><strong>Outstanding</strong><br>KES <?=number_format($outstanding)?></div>
<div class="summary-card"><strong>Transport Revenue</strong><br>KES <?=number_format($totalTransportRevenue)?></div>
<div class="summary-card"><strong>Total Trips</strong><br><?=number_format($totalTrips)?></div>
</div>
</div>
<div class="card">
<h3>Transport Report</h3>
<div style="overflow-x:auto;">
<table>
<tr><th>Date</th><th>Vehicle</th><th>Route</th><th>Driver</th><th>Status</th><th>Revenue</th></tr>
<?php foreach($tripReports as $tr): ?>
<tr>
<td><?=htmlspecialchars($tr['trip_date'])?></td>
<td><?=htmlspecialchars($tr['plate_number'])?></td>
<td><?=htmlspecialchars($tr['origin'])?> → <?=htmlspecialchars($tr['destination'])?></td>
<td><?=htmlspecialchars($tr['full_name'])?></td>
<td class="<?= $tr['status']=='OPEN'?'status-open':'status-closed' ?>"><?=htmlspecialchars($tr['status'])?></td>
<td>KES <?=number_format($tr['revenue'],2)?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>
</div>
</body>
</html>
