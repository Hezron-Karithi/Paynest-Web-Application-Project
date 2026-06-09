<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$business_id = $_SESSION['business_id'] ?? 0;

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE FROM trips
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([$delete_id, $business_id]);

    header("Location: trips.php");
    exit;
}
$vStmt = $pdo->prepare("SELECT id, plate_number FROM vehicles WHERE business_id=? AND status='ACTIVE'");
$vStmt->execute([$business_id]);
$vehicles = $vStmt->fetchAll();
$rStmt = $pdo->prepare("SELECT id, origin, destination FROM routes WHERE business_id=? AND status='ACTIVE'");
$rStmt->execute([$business_id]);
$routes = $rStmt->fetchAll();
$dStmt = $pdo->prepare("SELECT id, full_name FROM drivers WHERE business_id=? AND status='ACTIVE'");
$dStmt->execute([$business_id]);
$drivers = $dStmt->fetchAll();
$tStmt = $pdo->prepare("
    SELECT t.*, v.plate_number, r.origin, r.destination, d.full_name
    FROM trips t
    JOIN vehicles v ON t.vehicle_id=v.id
    JOIN routes r ON t.route_id=r.id
    JOIN drivers d ON t.driver_id=d.id
    WHERE t.business_id=?
    ORDER BY t.trip_date DESC
");
$tStmt->execute([$business_id]);
$trips = $tStmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trips</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));font-family:system-ui;padding:20px;}
.container{max-width:1100px;margin:auto;}
.card{background:#fff;padding:24px;border-radius:16px;margin-bottom:25px;}
h2,h3{text-align:center;margin-top:0;}
input,select{
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
.status-open{color:#f77f00;font-weight:700;}
.status-closed{color:#06d6a0;font-weight:700;}
.delete-icon{color:#ef476f;text-decoration:none;font-weight:bold;}
.manage-btn{
  background:var(--orange);
  color:#fff;
  padding:6px 12px;
  border-radius:8px;
  text-decoration:none;
  font-weight:600;
  display:inline-block;
}
.manage-btn:hover{
  background:var(--orange-dark);
}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Create Trip</h2>
<form method="POST" action="process_trip.php">
<label>Vehicle</label>
<select name="vehicle_id" required>
<option value="">Select Vehicle</option>
<?php foreach($vehicles as $v): ?>
<option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['plate_number']) ?></option>
<?php endforeach; ?>
</select>
<label>Route</label>
<select name="route_id" required>
<option value="">Select Route</option>
<?php foreach($routes as $r): ?>
<option value="<?= $r['id'] ?>">
<?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?>
</option>
<?php endforeach; ?>
</select>
<label>Driver</label>
<select name="driver_id" required>
<option value="">Select Driver</option>
<?php foreach($drivers as $d): ?>
<option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
<?php endforeach; ?>
</select>
<label>Trip Date</label>
<input type="date" name="trip_date" required>
<div class="btn-wrap">
<button type="submit" class="btn">Create Trip</button>
</div>
</form>
</div>
<div class="card">
<h3>Trips</h3>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Date</th>
<th>Vehicle</th>
<th>Route</th>
<th>Driver</th>
<th>Status</th>
<th>Manage</th>
<th>Delete</th>
</tr>
<?php foreach($trips as $t): ?>
<tr>
<td><?= htmlspecialchars($t['trip_date']) ?></td>
<td><?= htmlspecialchars($t['plate_number']) ?></td>
<td><?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination']) ?></td>
<td><?= htmlspecialchars($t['full_name']) ?></td>
<td class="<?= $t['status']=='OPEN'?'status-open':'status-closed' ?>">
<?= htmlspecialchars($t['status']) ?>
</td>
<td><a class="manage-btn" href="trip_detail.php?id=<?= $t['id'] ?>">Manage</a></td>
<td>
<a class="delete-icon"
   href="?delete=<?= $t['id'] ?>"
   onclick="return confirm('Delete this trip permanently?')">
🗑
</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>
</div>
</body>
</html>
