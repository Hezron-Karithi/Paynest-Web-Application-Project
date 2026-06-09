<?php
require_once __DIR__ . '/bootstrap.php';
$uploadDir = __DIR__ . '/uploads/ids/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$errors = [];
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $kraPin     = trim($_POST['kra_pin'] ?? '');
    $password   = $_POST['password'] ?? '';
    if (empty($fullName) || empty($email) || empty($phone) || empty($nationalId) || empty($kraPin) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
if (!isset($_FILES['national_id_pdf']) || $_FILES['national_id_pdf']['error'] !== 0) {
        $errors[] = "National ID PDF is required.";
    } else {
        $fileType = mime_content_type($_FILES['national_id_pdf']['tmp_name']);
        if ($fileType !== 'application/pdf') {
            $errors[] = "Only PDF format allowed.";
        }
    }
    if (empty($errors)) {
$stmt = $pdo->prepare("
            SELECT id FROM businesses 
            WHERE email = ? OR phone = ? OR national_id = ? OR kra_pin = ?
        ");
        $stmt->execute([$email, $phone, $nationalId, $kraPin]);

        if ($stmt->fetch()) {
            $errors[] = "Email, phone, National ID or KRA PIN already exists.";
        } else {
$fileName = uniqid('ID_') . '.pdf';
            $destination = $uploadDir . $fileName;
            move_uploaded_file($_FILES['national_id_pdf']['tmp_name'], $destination);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare("
                INSERT INTO businesses 
                (name, email, phone, national_id, kra_pin, national_id_pdf, password)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $fullName,
                $email,
                $phone,
                $nationalId,
                $kraPin,
                'uploads/ids/' . $fileName,
                $passwordHash
            ]);

            $success = "Business registered successfully.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Business Register</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
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
  max-width:480px;
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
.message{text-align:center;margin-bottom:15px;font-size:14px}
.error{color:red}
.success{color:green}
</style>
</head>
<body>

<div class="card">
<h2>Business Registration</h2>

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
<form method="POST" enctype="multipart/form-data">
<label>Name</label>
<input name="name" required>
<label>Email</label>
<input type="email" name="email" required>
<label>Phone</label>
<input name="phone" required>
<label>National ID</label>
<input name="national_id" required>
<label>KRA PIN</label>
<input name="kra_pin" required>
<label>Password</label>
<input type="password" name="password" required>
<label>Scanned National ID (PDF only)</label>
<input type="file" name="national_id_pdf" accept="application/pdf" required>
<button type="submit">Register</button>
</form>
</div>
</body>
</html>
