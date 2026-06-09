<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
$pdo->exec("
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX (business_id),
    INDEX (email),
    INDEX (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$errors = [];
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    if (empty($fullName)) {
        $errors[] = "Customer name is required.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO customers (business_id, full_name, email, phone)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$businessId, $fullName, $email, $phone]);
$success = "Customer added successfully.";
    }
}
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare("
        DELETE FROM customers 
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([$deleteId, $businessId]);

    header("Location: customers.php");
    exit;
}
$stmt = $pdo->prepare("
    SELECT * FROM customers
    WHERE business_id = ?
    ORDER BY id DESC
");
$stmt->execute([$businessId]);
$customers = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{margin:0;min-height:100vh;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:#fff}
header{padding:16px 22px;display:flex;justify-content:space-between;align-items:center}
header h2{margin:0;font-size:22px}
.back{background:#fff;color:var(--orange);padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600}
.container{max-width:1100px;margin:auto;padding:20px}
.card{background:#fff;color:#333;border-radius:16px;padding:20px;margin-bottom:22px}
label{font-size:13px;font-weight:600}
input{
  width:100%;
  max-width:100%;
  padding:10px;
  margin-top:6px;
  border:2px solid var(--orange);
  border-radius:8px;
  box-sizing:border-box;
}
.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.btn{background:var(--orange);color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:600}
.btn:hover{background:var(--orange-dark)}
table{width:100%;border-collapse:collapse;min-width:650px}
th,td{padding:10px;border-bottom:1px solid #eee;font-size:14px;white-space:nowrap}
th{text-align:left}
.actions a{margin-right:8px;color:#ef476f;text-decoration:none}
@media(max-width:600px){header h2{font-size:18px}}
.message{text-align:center;margin-bottom:10px;font-size:14px}
.error{color:red}
.success{color:green}
.table-scroll{
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
}
</style>
</head>
<body>
<header>
  <h2>Customers</h2>
  <a href="business_dashboard.php" class="back">Back</a>
</header>
<div class="container">
<div class="card">
<h3>Add Customer</h3>
<?php if(!empty($errors)): ?>
<div class="message error">
<?php foreach($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if($success): ?>
<div class="message success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<form method="POST">
<input type="hidden" name="add_customer" value="1">
<div class="row">
<div>
<label>Customer Name</label>
<input name="full_name" required>
</div>
<div>
<label>Email</label>
<input type="email" name="email">
</div>
<div>
<label>Phone</label>
<input name="phone">
</div>
</div>
<br>
<button class="btn">
<i class="fa-solid fa-user-plus"></i> Save Customer
</button>
</form>
</div>
<div class="card">
<h3>Customer List</h3>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (empty($customers)): ?>
<tr><td colspan="4">No customers found.</td></tr>
<?php else: ?>
<?php foreach ($customers as $customer): ?>
<tr>
<td><?= htmlspecialchars($customer['full_name']) ?></td>
<td><?= htmlspecialchars($customer['email']) ?></td>
<td><?= htmlspecialchars($customer['phone']) ?></td>
<td class="actions">
<a href="?delete=<?= $customer['id'] ?>" onclick="return confirm('Delete this customer?')">
<i class="fa-solid fa-trash"></i>
</a>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
