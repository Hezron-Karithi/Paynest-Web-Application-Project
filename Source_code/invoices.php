<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
$success = "";
$errors = [];
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id=?")->execute([$deleteId]);
    $pdo->prepare("DELETE FROM payments WHERE invoice_id=?")->execute([$deleteId]);
    $pdo->prepare("DELETE FROM invoices WHERE id=? AND business_id=?")
        ->execute([$deleteId, $businessId]);
    header("Location: invoices.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $customerName = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $items = $_POST['items'] ?? [];
    if (empty($customerName) || empty($phone)) {
        $errors[] = "Customer name and phone are required.";
    }
    $cleanItems = [];
    $grandTotal = 0;
    foreach ($items as $item) {
        $name = trim($item['name'] ?? '');
        if ($name === '') continue;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        $unit_price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
        $lineTotal = $quantity * $unit_price;
        $grandTotal += $lineTotal;
        $cleanItems[] = [
            'name' => $name,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total' => $lineTotal
        ];
    }
    if (empty($cleanItems)) {
        $errors[] = "At least one invoice item is required.";
    }
    if ($grandTotal <= 0) {
        $errors[] = "Invoice total must be greater than zero.";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE business_id=? AND full_name=? LIMIT 1");
        $stmt->execute([$businessId, $customerName]);
        $customerId = $stmt->fetchColumn();
        if (!$customerId) {
            $insertCustomer = $pdo->prepare("INSERT INTO customers (business_id, full_name, email, phone) VALUES (?, ?, ?, ?)");
            $insertCustomer->execute([$businessId, $customerName, $email, $phone]);
            $customerId = $pdo->lastInsertId();
        }
        $invoiceNumber = 'INV-' . time();
        $insertInvoice = $pdo->prepare("
            INSERT INTO invoices (business_id, customer_id, invoice_number, total_amount, status)
            VALUES (?, ?, ?, ?, 'PENDING')
        ");
        $insertInvoice->execute([
            $businessId,
            $customerId,
            $invoiceNumber,
            $grandTotal
        ]);
        $invoiceId = $pdo->lastInsertId();
        $insertItem = $pdo->prepare("
            INSERT INTO invoice_items (invoice_id, item_name, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($cleanItems as $item) {
            $insertItem->execute([
                $invoiceId,
                $item['name'],
                $item['quantity'],
                $item['unit_price'],
                $item['total']
            ]);
        }
        if ($action === 'pay') {
            $mpesaCode = 'SIM' . rand(100000,999999);
            $pdo->prepare("
                INSERT INTO payments (business_id, invoice_id, amount, mpesa_code, phone)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$businessId, $invoiceId, $grandTotal, $mpesaCode, $phone]);
            $pdo->prepare("UPDATE invoices SET status='PAID' WHERE id=? AND business_id=?")
                ->execute([$invoiceId, $businessId]);
            $success = "Invoice saved and marked as PAID.";
        } else {
            $success = "Invoice saved as PENDING.";
        }
    }
}
$stmtInvoices = $pdo->prepare("
    SELECT i.*, c.full_name 
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.business_id = ?
    ORDER BY i.created_at DESC
");
$stmtInvoices->execute([$businessId]);
$invoices = $stmtInvoices->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoices</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff}
.container{max-width:1100px;margin:auto;padding:20px}
.card{background:#fff;color:#333;border-radius:16px;padding:20px;margin-bottom:22px}
input{width:100%;padding:8px;border:2px solid var(--orange);border-radius:8px;}
input:focus{outline:none;box-shadow:0 0 0 3px rgba(255,122,0,.2)}
.btn{background:var(--orange);color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:600;white-space:nowrap;display:inline-block;}
.btn:hover{background:var(--orange-dark)}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eee;font-size:14px}
#itemsTable th:first-child,
#itemsTable td:first-child{min-width:250px;}
#itemsTable th:nth-child(2),
#itemsTable td:nth-child(2){min-width:140px;}
#itemsTable th:nth-child(3),
#itemsTable td:nth-child(3){min-width:180px;}
.actions-inline{display:flex;align-items:center;gap:8px;white-space:nowrap;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h3>Create Invoice</h3>
<?php 
if(!empty($errors)): ?>
<div style="color:red"><?php foreach($errors as $e){echo htmlspecialchars($e)."<br>";} ?></div>
<?php endif; ?>
<?php if($success): ?>
<div style="color:green"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<form method="POST">
<label>Customer Name</label>
<input name="customer_name" required>
<label>Email</label>
<input name="email">
<label>Phone</label>
<input name="phone" required>
<h4>Invoice Items</h4>
<div style="overflow-x:auto;">
<table id="itemsTable">
<thead>
<tr>
<th>Item</th>
<th width="120">Qty</th>
<th width="150">Unit Price</th>
<th width="150">Line Total</th>
<th></th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>
<button type="button" class="btn" onclick="addItem()">
<i class="fa-solid fa-plus"></i> Add Item
</button>
<div style="margin-top:15px;font-weight:700;">
Grand Total: KES <span id="grandTotal">0.00</span>
</div>
<br>
<button name="action" value="save" class="btn">Save Invoice</button>
<button name="action" value="pay" class="btn">Pay Now (Simulated STK)</button>
</form>
</div>
<div class="card">
<h3>All Invoices</h3>
<div style="overflow-x:auto;">
<table>
<thead>
<tr>
<th>Invoice #</th>
<th>Customer</th>
<th>Total (KES)</th>
<th>Status</th>
<th>Date</th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach($invoices as $inv): ?>
<tr>
<td><?= htmlspecialchars($inv['invoice_number']) ?></td>
<td><?= htmlspecialchars($inv['full_name']) ?></td>
<td><?= number_format($inv['total_amount'],2) ?></td>
<td><?= htmlspecialchars($inv['status']) ?></td>
<td><?= htmlspecialchars($inv['created_at']) ?></td>
<td>
<div class="actions-inline">
<a href="generate_receipts.php?invoice_id=<?= $inv['id'] ?>" class="btn">Generate Receipt</a>
<a href="?delete=<?= $inv['id'] ?>" onclick="return confirm('Delete this invoice?')" style="color:red">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<script>
function addItem(){
const index = document.querySelectorAll('#itemsTable tbody tr').length;
const row=document.createElement('tr');
row.innerHTML=`
<td><input name="items[${index}][name]" required></td>
<td><input type="number" name="items[${index}][quantity]" value="1" min="1" oninput="calculate()"></td>
<td><input type="number" name="items[${index}][unit_price]" value="0" min="0" step="0.01" oninput="calculate()"></td>
<td class="lineTotal">0.00</td>
<td><button type="button" onclick="removeItem(this)">✖</button></td>
`;
document.querySelector('#itemsTable tbody').appendChild(row);
calculate();
}
function removeItem(btn){
btn.closest('tr').remove();
calculate();
}
function calculate(){
let total=0;
document.querySelectorAll('#itemsTable tbody tr').forEach(row=>{
const qty=parseFloat(row.querySelector('[name*="[quantity]"]').value)||1;
const price=parseFloat(row.querySelector('[name*="[unit_price]"]').value)||0;
const line=qty*price;
row.querySelector('.lineTotal').innerText=line.toFixed(2);
total+=line;
});
document.getElementById('grandTotal').innerText=total.toFixed(2);
}
addItem();
</script>
</body>
</html>
