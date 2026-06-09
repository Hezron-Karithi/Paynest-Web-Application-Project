<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
$business_id = $_SESSION['business_id'] ?? 0;
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM drivers WHERE business_id = ?");
$stmtCount->execute([$business_id]);
$currentUsage = $stmtCount->fetchColumn();
enforceLimit($pdo, $business_id, 'drivers_limit', $currentUsage);
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE FROM drivers
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([$delete_id, $business_id]);

    header("Location: drivers.php");
    exit;
}
$stmtVehicles = $pdo->prepare("
    SELECT id, plate_number 
    FROM vehicles 
    WHERE business_id = ? AND status = 'ACTIVE'
    ORDER BY plate_number ASC
");
$stmtVehicles->execute([$business_id]);
$vehicles = $stmtVehicles->fetchAll();
$stmt = $pdo->prepare("
    SELECT d.*, v.plate_number 
    FROM drivers d
    LEFT JOIN vehicles v ON d.vehicle_id = v.id
    WHERE d.business_id = ?
    ORDER BY d.id DESC
");
$stmt->execute([$business_id]);
$drivers = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Drivers</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));min-height:100vh;padding:20px;}
.container{max-width:1000px;margin:auto;}
.card{background:#fff;border-radius:16px;padding:24px;margin-bottom:25px;box-shadow:0 10px 25px rgba(0,0,0,0.1);}
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
.btn{background:var(--orange);color:#fff;border:none;padding:14px 30px;border-radius:30px;font-weight:600;cursor:pointer;transition:.3s;}
.btn:hover{background:var(--orange-dark);transform:scale(1.05);}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid #eee;font-size:14px;text-align:left;}
.status-active{color:#06d6a0;font-weight:700;}
.status-inactive{color:#ef476f;font-weight:700;}
.delete-icon{color:#ef476f;text-decoration:none;font-weight:bold;}
.alert{padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;font-weight:600;}
.success{background:#e6fff5;color:#06d6a0;}
.error{background:#ffe6e6;color:#ef476f;}
@media(max-width:600px){.btn{width:100%;}}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Add Driver</h2>
<?php if(isset($_GET['success'])): ?>
<div class="alert success">Driver added successfully.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<div class="alert error">Something went wrong. Try again.</div>
<?php endif; ?>
<form method="POST" action="process_driver.php">
<label>Full Name</label>
<input type="text" name="full_name" required>
<label>Phone</label>
<input type="text" name="phone" required>
<label>License Number</label>
<input type="text" name="license_number" required>
<label>License Expiry Date</label>
<input type="date" name="license_expiry" required>
<label>Assign Vehicle (Optional)</label>
<select name="vehicle_id">
<option value="">-- Select Vehicle --</option>
<?php foreach($vehicles as $v): ?>
<option value="<?= $v['id'] ?>">
<?= htmlspecialchars($v['plate_number']) ?>
</option>
<?php endforeach; ?>
</select>
<label>Status</label>
<select name="status" required>
<option value="ACTIVE">Active</option>
<option value="INACTIVE">Inactive</option>
</select>
<div class="btn-wrap">
<button type="submit" class="btn">Save Driver</button>
</div>
</form>
</div>
<div class="card">
<h3>Registered Drivers</h3>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Name</th>
<th>Phone</th>
<th>License</th>
<th>Expiry</th>
<th>Vehicle</th>
<th>Status</th>
<th>Delete</th>
</tr>
<?php foreach($drivers as $d): ?>
<tr>
<td><?= htmlspecialchars($d['full_name']) ?></td>
<td><?= htmlspecialchars($d['phone']) ?></td>
<td><?= htmlspecialchars($d['license_number']) ?></td>
<td><?= htmlspecialchars($d['license_expiry']) ?></td>
<td><?= htmlspecialchars($d['plate_number'] ?? 'Unassigned') ?></td>
<td class="<?= $d['status']=='ACTIVE'?'status-active':'status-inactive' ?>">
<?= htmlspecialchars($d['status']) ?>
</td>
<td>
<a class="delete-icon"
   href="?delete=<?= $d['id'] ?>"
   onclick="return confirm('Delete this driver permanently?')">
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
