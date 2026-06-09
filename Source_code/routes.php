<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
$business_id = $_SESSION['business_id'] ?? 0;
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE business_id = ?");
$stmtCount->execute([$business_id]);
$currentUsage = $stmtCount->fetchColumn();
enforceLimit($pdo, $business_id, 'routes_limit', $currentUsage);
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE FROM routes
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([$delete_id, $business_id]);
    header("Location: routes.php");
    exit;
}
$stmt = $pdo->prepare("
    SELECT id, origin, destination, fare, status, created_at
    FROM routes
    WHERE business_id = ?
    ORDER BY id DESC
");
$stmt->execute([$business_id]);
$routes = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Routes</title>
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
<h2>Add Route</h2>
<?php if(isset($_GET['success'])): ?>
<div class="alert success">Route added successfully.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<div class="alert error">Something went wrong. Try again.</div>
<?php endif; ?>
<form method="POST" action="process_route.php">
<label>Origin</label>
<input type="text" name="origin" required>
<label>Destination</label>
<input type="text" name="destination" required>
<label>Fare (KES)</label>
<input type="number" name="fare" step="0.01" required>
<label>Status</label>
<select name="status" required>
<option value="ACTIVE">Active</option>
<option value="INACTIVE">Inactive</option>
</select>
<div class="btn-wrap">
<button type="submit" class="btn">Save Route</button>
</div>
</form>
</div>
<div class="card">
<h3>Registered Routes</h3>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Route</th>
<th>Fare</th>
<th>Status</th>
<th>Added</th>
<th>Delete</th>
</tr>
<?php foreach($routes as $r): ?>
<tr>
<td><?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?></td>
<td>KES <?= number_format($r['fare'],2) ?></td>
<td class="<?= $r['status']=='ACTIVE'?'status-active':'status-inactive' ?>">
<?= htmlspecialchars($r['status']) ?>
</td>
<td><?= htmlspecialchars($r['created_at']) ?></td>
<td>
<a class="delete-icon"
   href="?delete=<?= $r['id'] ?>"
   onclick="return confirm('Delete this route permanently?')">
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
