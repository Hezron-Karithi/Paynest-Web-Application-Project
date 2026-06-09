<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/admin_guard.php';
$pdo->exec("
CREATE TABLE IF NOT EXISTS support_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  admin_response TEXT NULL,
  status VARCHAR(50) DEFAULT 'OPEN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (business_id),
  INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_name'])) {
 $planName = trim($_POST['plan_name']);
    $priceUsd = (float) $_POST['price_usd'];
    $vat = (float) $_POST['vat_percent'];
if (!empty($planName) && $priceUsd > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO subscription_plans (name, price_usd, vat_percent)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$planName, $priceUsd, $vat]);
    }
header("Location: admin_dashboard.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticketId = (int) $_POST['ticket_id'];
    $response = trim($_POST['response'] ?? '');
if ($response !== '') {
        $stmt = $pdo->prepare("
            UPDATE support_tickets
            SET admin_response = ?, status='RESPONDED'
            WHERE id = ?
        ");
        $stmt->execute([$response, $ticketId]);
    }
header("Location: admin_dashboard.php");
    exit;
}
$totalBusinesses = $pdo->query("SELECT COUNT(*) FROM businesses")->fetchColumn();

$activeSubscriptions = $pdo->query("
    SELECT COUNT(*) FROM subscriptions WHERE status = 'ACTIVE'
")->fetchColumn();
$monthlyRevenue = $pdo->query("
    SELECT IFNULL(SUM(amount_usd),0) FROM subscriptions WHERE status = 'ACTIVE'
")->fetchColumn();
$vatCollected = $pdo->query("
    SELECT IFNULL(SUM(amount_usd * vat_percent/100),0)
    FROM subscriptions WHERE status = 'ACTIVE'
")->fetchColumn();
$plans = $pdo->query("
    SELECT * FROM subscription_plans ORDER BY created_at DESC
")->fetchAll();
$tickets = $pdo->query("SELECT * FROM support_tickets ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{margin:0;min-height:100vh;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff;}
header{padding:16px 22px;display:flex;justify-content:space-between;align-items:center;}
header h2{margin:0;font-size:22px}
.logout{background:#fff;color:var(--orange);padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:700;}
.container{max-width:1300px;margin:auto;padding:20px;}
.card{background:#fff;color:#333;border-radius:18px;padding:20px;margin-bottom:22px;}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;}
.summary-card{background:rgba(255,122,0,.15);border-radius:14px;padding:18px;}
.summary-card span{font-size:13px}
.summary-card h3{margin:6px 0}
label{font-size:13px;font-weight:700}
input, textarea{width:100%;padding:10px;margin-top:6px;border:1px solid #ddd;border-radius:8px;}
.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}
.btn{background:var(--orange);color:#fff;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;font-weight:700;}
.btn:hover{background:var(--orange-dark)}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid #eee;font-size:14px;}
th{text-align:left}
.badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:800;}
.active{background:#06d6a0;color:#fff}
.expired{background:#ef476f;color:#fff}
@media(max-width:600px){header h2{font-size:18px}}
</style>
</head>
<body>
<header>
  <h2>Admin Dashboard</h2>
  <a href="logout.php" class="logout">Logout</a>
</header>
<div class="container">
<div class="card">
<h3>Platform Overview</h3>
<div class="summary">
<div class="summary-card"><span>Total Businesses</span><h3><?= number_format($totalBusinesses) ?></h3></div>
<div class="summary-card"><span>Active Subscriptions</span><h3><?= number_format($activeSubscriptions) ?></h3></div>
<div class="summary-card"><span>Monthly Revenue (USD)</span><h3>$<?= number_format($monthlyRevenue,2) ?></h3></div>
<div class="summary-card"><span>VAT Collected (USD)</span><h3>$<?= number_format($vatCollected,2) ?></h3></div>
</div>
</div>
<div class="card">
<h3>Subscription Plans (Admin Controlled)</h3>
<a href="plans_features.php" class="btn" style="margin-bottom:15px;display:inline-block;">
<i class="fa-solid fa-sliders"></i> Configure Plans
</a>
<h4>Add New Plan</h4>
<form method="POST">
<div class="row">
<div><label>Plan Name</label><input name="plan_name" required></div>
<div><label>Price (USD)</label><input name="price_usd" type="number" step="0.01" required></div>
<div><label>VAT (%)</label><input name="vat_percent" type="number" value="16" required></div>
</div>
<br>
<button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Plan</button>
</form>
<br><br>
<h4>Existing Plans</h4>
<table>
<thead><tr><th>Plan</th><th>USD</th><th>VAT</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($plans as $plan): ?>
<tr>
<td><?= htmlspecialchars($plan['name']) ?></td>
<td>$<?= number_format($plan['price_usd'],2) ?></td>
<td><?= number_format($plan['vat_percent'],2) ?>%</td>
<td><?php if($plan['is_active']): ?><span class="badge active">Active</span><?php else: ?><span class="badge expired">Disabled</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="card">
<h3>Support Tickets</h3>
<?php if(empty($tickets)): ?>
<p>No tickets yet.</p>
<?php else: ?>
<?php foreach($tickets as $t): ?>
<div style="border:1px solid #eee;padding:12px;border-radius:10px;margin-bottom:12px;">
<strong>#<?= $t['id'] ?> — <?= htmlspecialchars($t['subject']) ?></strong><br>
<small>Status: <?= htmlspecialchars($t['status']) ?></small>
<p><?= nl2br(htmlspecialchars($t['message'])) ?></p>
<form method="POST">
<input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
<label>Admin Response</label>
<textarea name="response" required><?= htmlspecialchars($t['admin_response'] ?? '') ?></textarea><br><br>
<button class="btn">Send Response</button>
</form>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</body>
</html>
