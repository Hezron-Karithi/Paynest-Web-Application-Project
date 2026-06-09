<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: drivers.php");
    exit;
}
$business_id = $_SESSION['business_id'] ?? 0;
$name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$license = trim($_POST['license_number'] ?? '');
$expiry = $_POST['license_expiry'] ?? '';
$vehicle_id = $_POST['vehicle_id'] ?: NULL;
$status = $_POST['status'] ?? '';
if (empty($name) || empty($phone) || empty($license) || empty($expiry) || empty($status)) {
    header("Location: drivers.php?error=1");
    exit;
}
$stmt = $pdo->prepare("
    INSERT INTO drivers (business_id, full_name, phone, license_number, license_expiry, vehicle_id, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$business_id, $name, $phone, $license, $expiry, $vehicle_id, $status]);
header("Location: drivers.php?success=1");
exit;
?>
