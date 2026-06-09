<?php
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if (empty($email) || empty($password)) {
    header("Location: login.php?error=1");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?error=1");
    exit;
}
$stmt = $pdo->prepare("SELECT id, password FROM businesses WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$business = $stmt->fetch();
if (!$business) {
    header("Location: login.php?error=1");
    exit;
}
if (!password_verify($password, $business['password'])) {
    header("Location: login.php?error=1");
    exit;
}
session_regenerate_id(true);
$_SESSION['user_id'] = $business['id'];
$_SESSION['business_id'] = $business['id'];
$_SESSION['role'] = 'BUSINESS';
header("Location: business_dashboard.php");
exit;
?>