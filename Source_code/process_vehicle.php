<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: vehicles.php");
    exit;
}
$business_id = $_SESSION['business_id'] ?? 0;
$plate = trim($_POST['plate_number'] ?? '');
$capacity = $_POST['capacity'] ?? '';
$status = $_POST['status'] ?? '';
if (empty($plate) || empty($capacity) || empty($status)) {
    header("Location: vehicles.php?error=1");
    exit;
}
$stmt = $pdo->prepare("
    INSERT INTO vehicles (business_id, plate_number, capacity, status, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$business_id, $plate, $capacity, $status]);
header("Location: vehicles.php?success=1");
exit;
?>
