<?php
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.php");
    exit;
}
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: admin_login.php?error=1");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: admin_login.php?error=1");
    exit;
}
$stmt = $pdo->prepare("SELECT id, password FROM admins WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$admin = $stmt->fetch();
if (!$admin) {
    header("Location: admin_login.php?error=1");
    exit;
}
if (!password_verify($password, $admin['password'])) {
    header("Location: admin_login.php?error=1");
    exit;
}
session_regenerate_id(true);
$_SESSION['user_id'] = $admin['id'];
$_SESSION['role'] = 'ADMIN';
header("Location: admin_dashboard.php");
exit;
?>