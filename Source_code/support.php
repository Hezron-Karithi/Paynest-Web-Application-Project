<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
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
$message = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject = trim($_POST['subject'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if ($businessId <= 0) {
        $error = "Invalid session. Please login again.";
    } elseif ($subject === '' || $msg === '') {
        $error = "Please fill all fields.";
    } else {

        try {
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets (business_id, subject, message, status)
                VALUES (?, ?, ?, 'OPEN')
            ");
            $stmt->execute([$businessId, $subject, $msg]);
            $message = "Ticket submitted successfully.";
        } catch (Exception $e) {
            $error = "Failed to submit ticket.";
        }
    }
}
$stmt = $pdo->prepare("
    SELECT id, subject, status, admin_response, created_at
    FROM support_tickets
    WHERE business_id=?
    ORDER BY id DESC
");
$stmt->execute([$businessId]);
$tickets = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Support</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui;}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff;padding:20px;}
.container{max-width:1000px;margin:auto;}
.card{background:rgba(255,255,255,.2);padding:20px;border-radius:16px;margin-bottom:20px;}
input,textarea{width:100%;max-width:100%;padding:12px;border:2px solid var(--orange);border-radius:10px;margin-bottom:12px;}
.btn{background:var(--orange);border:none;color:#fff;padding:12px 20px;border-radius:10px;cursor:pointer;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.3);}
.msg{color:#06d6a0;}
.err{color:#ffd6d6;}
</style>
</head>
<body>
<div class="container">

<div class="card">
<h2>Contact Support</h2>
<?php if($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="POST">
<label>Subject</label>
<input name="subject" required>
<label>Message</label>
<textarea name="message" required></textarea>
<button class="btn">Submit</button>
</form>
</div>
<div class="card">
<h3>Your Tickets</h3>
<div style="overflow-x:auto;">
<table>
<tr><th>ID</th><th>Subject</th><th>Status</th><th>Response</th><th>Date</th></tr>
<?php if(empty($tickets)): ?>
<tr><td colspan="5">No tickets yet.</td></tr>
<?php else: ?>
<?php foreach($tickets as $t): ?>
<tr>
<td>#<?= $t['id'] ?></td>
<td><?= htmlspecialchars($t['subject']) ?></td>
<td><?= htmlspecialchars($t['status']) ?></td>
<td><?= htmlspecialchars($t['admin_response'] ?? '—') ?></td>
<td><?= htmlspecialchars($t['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
</div>
</div>
</div>
</body>
</html>
