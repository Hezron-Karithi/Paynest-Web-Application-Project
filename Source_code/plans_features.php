<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/admin_guard.php';
$pdo->exec("
CREATE TABLE IF NOT EXISTS features (
  id INT AUTO_INCREMENT PRIMARY KEY,
  feature_key VARCHAR(100) UNIQUE NOT NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS plan_features (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plan_id INT NOT NULL,
  feature_key VARCHAR(100) NOT NULL,
  feature_value VARCHAR(100) NULL,
  UNIQUE KEY unique_plan_feature (plan_id, feature_key),
  INDEX(plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_feature_key'])) {
    $key = trim($_POST['new_feature_key']);
    $desc = trim($_POST['description'] ?? '');
    if ($key !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO features (feature_key, description) VALUES (?, ?)");
        $stmt->execute([$key, $desc]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_features'])) {
    foreach ($_POST['features'] as $planId => $features) {
        foreach ($features as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO plan_features (plan_id, feature_key, feature_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE feature_value = VALUES(feature_value)
            ");
            $stmt->execute([$planId, $key, $value]);
        }
    }
}
$plans = $pdo->query("SELECT id, name FROM subscription_plans ORDER BY price_usd ASC")->fetchAll();

$features = $pdo->query("SELECT * FROM features ORDER BY feature_key")->fetchAll(PDO::FETCH_ASSOC);

$valuesStmt = $pdo->query("SELECT * FROM plan_features");
$valuesRaw = $valuesStmt->fetchAll(PDO::FETCH_ASSOC);
$values = [];
foreach ($valuesRaw as $v) {
    $values[$v['plan_id']][$v['feature_key']] = $v['feature_value'];
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configure Plan Features</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
body{
  margin:0;
  font-family:system-ui;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
}
.container{
  max-width:1200px;
  margin:auto;
  padding:20px;
}
.card{
  background:#fff;
  color:#333;
  border-radius:16px;
  padding:20px;
  margin-bottom:20px;
}
table{
  width:100%;
  border-collapse:collapse;
}
th,td{
  border-bottom:1px solid #eee;
  padding:10px;
  text-align:left;
}
input{
  width:100%;
  padding:6px;
  border:1px solid #ddd;
  border-radius:6px;
}
.btn{
  background:var(--orange);
  color:#fff;
  border:none;
  padding:10px 16px;
  border-radius:8px;
  cursor:pointer;
  font-weight:700;
}
.btn:hover{background:var(--orange-dark)}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h3>Add New Feature</h3>
<form method="POST">
<input name="new_feature_key" placeholder="feature_key (e.g receipts_limit)" required>
<br><br>
<input name="description" placeholder="Description (optional)">
<br><br>
<button class="btn">Add Feature</button>
</form>
</div>
<div class="card">
<h3>Configure Features Per Plan</h3>

<form method="POST">
<input type="hidden" name="update_features" value="1">
<table>
<thead>
<tr>
<th>Feature</th>
<?php foreach($plans as $p): ?>
<th><?= htmlspecialchars($p['name']) ?></th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach($features as $f): ?>
<tr>
<td>
<strong><?= htmlspecialchars($f['feature_key']) ?></strong><br>
<small><?= htmlspecialchars($f['description']) ?></small>
</td>
<?php foreach($plans as $p): 
$value = $values[$p['id']][$f['feature_key']] ?? '';
?>
<td>
<input name="features[<?= $p['id'] ?>][<?= htmlspecialchars($f['feature_key']) ?>]" value="<?= htmlspecialchars($value) ?>">
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<br>
<button class="btn">Save Changes</button>
</form>
</div>
</div>
</body>
</html>
