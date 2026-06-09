<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$business_id = $_SESSION['business_id'];
$trip_id = $_GET['id'] ?? 0;
if (isset($_GET['delete_line'])) {
    $line_id = (int)$_GET['delete_line'];

    $delStmt = $pdo->prepare("
        DELETE trl FROM trip_revenue_lines trl
        JOIN trips t ON trl.trip_id = t.id
        WHERE trl.id = ? AND t.business_id = ?
    ");
    $delStmt->execute([$line_id, $business_id]);

    header("Location: trip_detail.php?id=" . $trip_id);
    exit;
}
$stmt = $pdo->prepare("
    SELECT t.*, r.fare AS base_fare, v.plate_number 
    FROM trips t 
    JOIN routes r ON t.route_id = r.id 
    JOIN vehicles v ON t.vehicle_id = v.id 
    WHERE t.id = ? AND t.business_id = ?
");
$stmt->execute([$trip_id, $business_id]);
$trip = $stmt->fetch();
if (!$trip) { die("Trip not found"); }
$lStmt = $pdo->prepare("SELECT * FROM trip_revenue_lines WHERE trip_id = ?");
$lStmt->execute([$trip_id]);
$lines = $lStmt->fetchAll();
$totalRevenue = 0;
foreach ($lines as $l) { $totalRevenue += $l['total_amount']; }
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trip Detail</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));font-family:system-ui;padding:20px;}
.container{max-width:900px;margin:auto;}
.card{background:#fff;padding:24px;border-radius:16px;margin-bottom:25px;}
input{
  width:100%;
  max-width:100%;
  box-sizing:border-box;
  padding:12px;
  margin-bottom:15px;
  border:2px solid var(--orange);
  border-radius:10px;
}
.btn-wrap{display:flex;justify-content:center;}
.btn{background:var(--orange);color:#fff;border:none;padding:14px 30px;border-radius:30px;font-weight:600;cursor:pointer;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;}
.delete-icon{color:#ef476f;text-decoration:none;font-weight:bold;}
</style>
<script>
function calcTotal(){
 const fare=parseFloat(document.getElementById('fare').value)||0;
 const pass=parseInt(document.getElementById('passengers').value)||0;
 document.getElementById('total').value=(fare*pass).toFixed(2);
}
</script>
</head>
<body>
<div class="container">
<div class="card">
<h3>Trip: <?= htmlspecialchars($trip['plate_number']) ?> | Date: <?= htmlspecialchars($trip['trip_date']) ?></h3>
<form method="POST" action="process_trip_line.php">
<input type="hidden" name="trip_id" value="<?= $trip_id ?>">
<label>Fare</label>
<input type="number" step="0.01" id="fare" name="fare" value="<?= $trip['base_fare'] ?>" readonly>
<label>Passengers</label>
<input type="number" id="passengers" name="passengers" required oninput="calcTotal()">
<label>Total</label>
<input type="number" step="0.01" id="total" name="total_amount" readonly>
<div class="btn-wrap">
<button class="btn">Add Revenue</button>
</div>
</form>
</div>
<div class="card">
<h3>Revenue Lines</h3>
<table>
<tr>
<th>Passengers</th>
<th>Total</th>
<th>Delete</th>
</tr>
<?php foreach($lines as $l): ?>
<tr>
<td><?= htmlspecialchars($l['passengers']) ?></td>
<td>KES <?= number_format($l['total_amount'],2) ?></td>
<td>
<a class="delete-icon"
   href="?id=<?= $trip_id ?>&delete_line=<?= $l['id'] ?>"
   onclick="return confirm('Delete this revenue line?')">
🗑
</a>
</td>
</tr>
<?php endforeach; ?>
<tr>
<td><strong>Total Revenue</strong></td>
<td><strong>KES <?= number_format($totalRevenue,2) ?></strong></td>
<td></td>
</tr>
</table>
</div>
</div>
</body>
</html>
