<?php
require_once __DIR__ . '/bootstrap.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['business_id'])) {
    header("Location: admin_login.php");
    exit;
}
$business_id = $_SESSION['business_id'];
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE p FROM payments p
        JOIN invoices i ON p.invoice_id = i.id
        WHERE p.id = ? AND i.business_id = ?
    ");
    $stmt->execute([$delete_id, $business_id]);
    header("Location: payments.php");
    exit;
}
$stmt = $pdo->prepare("
    SELECT id, invoice_number, total_amount 
    FROM invoices 
    WHERE business_id = ? 
    AND status = 'PENDING'
    ORDER BY id DESC
");
$stmt->execute([$business_id]);
$invoices = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT p.id, p.amount, p.mpesa_code, p.created_at, 
           i.invoice_number, i.total_amount,
           (SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = i.id) as total_paid
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    WHERE i.business_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$business_id]);
$payments = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payments</title>
<style>
:root{
  --orange:#ff7a00;
  --orange-dark:#e86f00;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  min-height:100vh;
  padding:20px;
}
.container{
  max-width:900px;
  margin:auto;
}
.card{
  background:#fff;
  border-radius:16px;
  padding:24px;
  margin-bottom:25px;
  box-shadow:0 10px 25px rgba(0,0,0,0.1);
}
h2,h3{
  margin-top:0;
  text-align:center;
}
label{
  font-weight:600;
  font-size:14px;
}
input,select{
  width:100%;
  max-width:100%;
  padding:12px;
  margin-top:6px;
  margin-bottom:16px;
  border:2px solid var(--orange);
  border-radius:10px;
  font-size:14px;
}
.btn-wrapper{
  display:flex;
  justify-content:center;
}
.btn{
  background:var(--orange);
  color:#fff;
  border:none;
  padding:14px 30px;
  border-radius:30px;
  font-size:15px;
  font-weight:600;
  cursor:pointer;
  transition:0.3s;
}
.btn:hover{
  background:var(--orange-dark);
  transform:scale(1.05);
}
table{
  width:100%;
  border-collapse:collapse;
}
th,td{
  padding:12px;
  border-bottom:1px solid #eee;
  font-size:14px;
  text-align:left;
}
th{
  background:#fafafa;
}
.delete-icon{
  color:#ef476f;
  cursor:pointer;
  font-weight:bold;
  text-decoration:none;
}
.status-paid{
  color:#06d6a0;
  font-weight:700;
}
.status-partial{
  color:#ef476f;
  font-weight:700;
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
@media(max-width:600px){
  body{padding:12px;}
  .card{padding:18px;}
  .btn{width:100%;}
}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>Record Payment</h2>
<?php 
if(isset($_GET['success'])): ?>
<div class="alert success">Payment recorded successfully.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<div class="alert error">Something went wrong. Try again.</div>
<?php endif; ?>
<form method="POST" action="process_payment.php">
<label>Select Invoice</label>
<select name="invoice_id" required>
<option value="">-- Select Pending Invoice --</option>
<?php foreach ($invoices as $invoice): ?>
<option value="<?= $invoice['id'] ?>">
<?= htmlspecialchars($invoice['invoice_number']) ?> — KES <?= number_format($invoice['total_amount']) ?>
</option>
<?php endforeach; ?>
</select>
<label>Amount Paid</label>
<input type="number" name="amount" step="0.01" required>
<label>M-Pesa Code</label>
<input type="text" name="mpesa_code" required>
<div class="btn-wrapper">
<button type="submit" class="btn">Save Payment</button>
</div>
</form>
</div>
<div class="card">
<h3>Recent Payments</h3>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Invoice</th>
<th>Amount</th>
<th>M-Pesa Code</th>
<th>Status</th>
<th>Delete</th>
</tr>
<?php foreach ($payments as $payment): 
$status = ($payment['total_paid'] >= $payment['total_amount']) ? "Paid" : "Partial";
?>
<tr>
<td><?= htmlspecialchars($payment['invoice_number']) ?></td>
<td>KES <?= number_format($payment['amount']) ?></td>
<td><?= htmlspecialchars($payment['mpesa_code']) ?></td>
<td class="<?= $status === 'Paid' ? 'status-paid' : 'status-partial' ?>">
<?= $status ?>
</td>
<td>
<a class="delete-icon" 
   href="?delete=<?= $payment['id'] ?>" 
   onclick="return confirm('Delete this payment permanently?')">
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
