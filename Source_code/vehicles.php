<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE business_id = ?");
$stmtCount->execute([$_SESSION['business_id']]);
$currentUsage = $stmtCount->fetchColumn();
enforceLimit($pdo, $_SESSION['business_id'], 'vehicles_limit', $currentUsage);
$business_id = $_SESSION['business_id'] ?? 0;
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE FROM vehicles
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([$delete_id, $business_id]);
    header("Location: vehicles.php");
    exit;
}
$stmt = $pdo->prepare("
    SELECT id, plate_number, capacity, status, created_at
    FROM vehicles
    WHERE business_id = ?
    ORDER BY id DESC
");
$stmt->execute([$business_id]);
$vehicles = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vehicles</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  min-height:100vh;
  padding:20px;
}
.container{max-width:1000px;margin:auto;}
.card{
  background:#fff;
  border-radius:16px;
  padding:24px;
  margin-bottom:25px;
  box-shadow:0 10px 25px rgba(0,0,0,0.1);
}
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
.btn-wrap{display:flex;justify-content:center;margin-top:10px;}
.btn{
  background:var(--orange);
  color:#fff;
  border:none;
  padding:14px 30px;
  border-radius:30px;
  font-weight:600;
  cursor:pointer;
  transition:.3s;
}
.btn:hover{background:var(--orange-dark);transform:scale(1.05);}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid #eee;font-size:14px;text-align:left;}
.status-active{color:#06d6a0;font-weight:700;}
.status-maintenance{color:#ef476f;font-weight:700;}
.delete-icon{
  color:#ef476f;
  text-decoration:none;
  font-weight:bold;
}
.alert{
  padding:12px;
  border-radius:10px;
  margin-bottom:15px;
  text-align:center;
  font-weight:600;
}
.success{background:#e6fff5;color:#06d6a0;}
.error{background:#ffe6e6;color:#ef476f;}
@media(max-width:600px){.btn{width:100%;}}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Add Vehicle</h2>
<?php if(isset($_GET['success'])): ?>
<div class="alert success">Vehicle added successfully.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<div class="alert error">Something went wrong. Try again.</div>
<?php endif; ?>
<form method="POST" action="process_vehicle.php">
<label>Plate Number</label>
<input type="text" name="plate_number" required>
<label>Capacity</label>
<input type="number" name="capacity" required>
<label>Status</label>
<select name="status" required>
<option value="ACTIVE">Active</option>
<option value="MAINTENANCE">Maintenance</option>
</select>
<div class="btn-wrap">
<button type="submit" class="btn">Save Vehicle</button>
</div>
</form>
</div>
<div class="card">
<h3>Registered Vehicles</h3>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Plate</th>
<th>Capacity</th>
<th>Status</th>
<th>Added</th>
<th>Delete</th>
</tr>
<?php foreach($vehicles as $v): ?>
<tr>
<td><?= htmlspecialchars($v['plate_number']) ?></td>
<td><?= number_format($v['capacity']) ?></td>
<td class="<?= $v['status']=='ACTIVE'?'status-active':'status-maintenance' ?>">
<?= htmlspecialchars($v['status']) ?>
</td>
<td><?= htmlspecialchars($v['created_at']) ?></td>
<td>
<a class="delete-icon"
   href="?delete=<?= $v['id'] ?>"
   onclick="return confirm('Delete this vehicle permanently?')">
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
