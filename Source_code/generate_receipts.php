<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/feature_guard.php';
require_once __DIR__ . '/usage_helper.php';
date_default_timezone_set('Africa/Nairobi');
$businessId = $_SESSION['business_id'] ?? 0;
$usage = countReceiptsThisMonth($pdo, $businessId);
enforceLimit($pdo, $businessId, 'receipts_limit', $usage);
$invoiceId = (int)($_GET['invoice_id'] ?? 0);
$method = $_GET['method'] ?? null;
if (!$invoiceId) {
    die("Missing invoice_id");
}
$stmt = $pdo->prepare("
    SELECT i.*, c.full_name, c.email, c.phone
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id=? AND i.business_id=?
");
$stmt->execute([$invoiceId, $businessId]);
$invoice = $stmt->fetch();
if (!$invoice) {
    die("Invoice not found");
}
$stmtItems = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=?");
$stmtItems->execute([$invoiceId]);
$items = $stmtItems->fetchAll();
$stmtBiz = $pdo->prepare("SELECT name, email, phone FROM businesses WHERE id=?");
$stmtBiz->execute([$businessId]);
$business = $stmtBiz->fetch();
$stmtLogo = $pdo->prepare("SELECT logo FROM business_settings WHERE business_id=?");
$stmtLogo->execute([$businessId]);
$settings = $stmtLogo->fetch();
if ($method === 'email' && !empty($invoice['email'])) {
    $link = "http://".$_SERVER['HTTP_HOST']."/generate_receipts.php?invoice_id=".$invoiceId;
    @mail($invoice['email'], "Your Receipt ".$invoice['invoice_number'], "View your receipt: ".$link);
}
if ($method === 'sms') {
    error_log("SMS receipt link for invoice ".$invoiceId);
}
if ($method === 'whatsapp') {
    error_log("WhatsApp receipt link for invoice ".$invoiceId);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt</title>
<style>
body{font-family:Arial;padding:20px;background:#ff7a00;color:#fff}
.card{background:#fff;color:#333;padding:20px;border-radius:10px}
h2{margin-bottom:5px}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
.total{margin-top:15px;font-weight:bold}
.print-btn{margin-top:20px;padding:10px 15px;background:#ff7a00;color:#fff;border:none;cursor:pointer}
.logo{max-height:80px;margin-bottom:10px}
</style>
</head>
<body>
<div style="position:relative;margin-bottom:10px;">
<div style="position:absolute;left:0;top:0;"><?= date('Y-m-d H:i:s') ?></div>
<h1 style="text-align:center;margin:0;">Receipt</h1>
</div>
<div class="card">
<?php if(!empty($settings['logo'])): ?>
<img src="<?= htmlspecialchars($settings['logo']) ?>" class="logo">
<?php endif; ?>
<h2><?= htmlspecialchars($business['name'] ?? 'Business') ?></h2>
<p><?= htmlspecialchars($business['email'] ?? '') ?> | <?= htmlspecialchars($business['phone'] ?? '') ?></p>
<hr>
<h2>Receipt <?= htmlspecialchars($invoice['invoice_number']) ?></h2>
<p><strong>Customer Name:</strong> <?= htmlspecialchars($invoice['full_name']) ?></p>
<p><strong>Customer Email:</strong> <?= htmlspecialchars($invoice['email']) ?></p>
<p><strong>Customer Phone:</strong> <?= htmlspecialchars($invoice['phone']) ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($invoice['status']) ?></p>
<table>
<tr>
<th>Item</th>
<th>Qty</th>
<th>Unit Price</th>
<th>Total</th>
</tr>
<?php foreach ($items as $it): ?>
<tr>
<td><?= htmlspecialchars($it['item_name']) ?></td>
<td><?= $it['quantity'] ?></td>
<td><?= number_format($it['unit_price'],2) ?></td>
<td><?= number_format($it['total_price'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>
<div class="total">
Total: KES <?= number_format($invoice['total_amount'],2) ?>
</div>
<button class="print-btn" onclick="window.print()">Download / Print PDF</button>
</div>
</body>
</html>
