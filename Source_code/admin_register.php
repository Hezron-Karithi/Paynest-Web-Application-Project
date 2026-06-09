<?php
require_once __DIR__ . '/bootstrap.php';
$pdo->exec("
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$errors = [];
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'] ?? '';
if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $errors[] = "All fields are required.";
    }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
if (empty($errors)) {
$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);

        if ($stmt->fetch()) {
            $errors[] = "Email or phone already exists.";
        } else {
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare("
                INSERT INTO admins (full_name, email, phone, password)
                VALUES (?, ?, ?, ?)
            ");
            $insert->execute([$fullName, $email, $phone, $passwordHash]);
$success = "Admin registered successfully.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Register</title>
<style>
:root{
  --orange:#ff7a00;
  --orange-dark:#e86f00;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;
  min-height:100vh;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  display:flex;
  justify-content:center;
  align-items:center;
  padding:20px;
}
.card{
  background:#fff;
  padding:30px;
  border-radius:18px;
  width:100%;
  max-width:400px;
}
h2{text-align:center;margin-top:0}
label{font-weight:600;font-size:14px}
input{
  width:100%;
  padding:10px;
  margin:10px 0;
  border:2px solid var(--orange);
  border-radius:8px;
  box-sizing:border-box;
}
button{
  width:100%;
  padding:12px;
  background:var(--orange);
  border:none;
  color:#fff;
  font-weight:700;
  border-radius:8px;
  cursor:pointer;
}
button:hover{background:var(--orange-dark)}
.message{
  text-align:center;
  margin-bottom:15px;
  font-size:14px;
}
.error{color:red}
.success{color:green}
</style>
</head>
<body>
<div class="card">
  <h2>Admin Registration</h2>
<?php if(!empty($errors)): ?>
    <div class="message error">
      <?php foreach($errors as $error): ?>
        <div><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php if($success): ?>
    <div class="message success">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
<form method="POST">
    <label>Full Name</label>
    <input name="full_name" required>
<label>Email</label>
    <input type="email" name="email" required>
<label>Phone</label>
    <input name="phone" required>
<label>Password</label>
    <input type="password" name="password" required>
<button type="submit">Register</button>
  </form>
</div>
</body>
</html>
