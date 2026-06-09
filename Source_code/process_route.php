<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: routes.php");
    exit;
}
$business_id = $_SESSION['business_id'] ?? 0;
$origin = trim($_POST['origin'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$fare = $_POST['fare'] ?? '';
$status = $_POST['status'] ?? '';
if (empty($origin) || empty($destination) || empty($fare) || empty($status)) {
    header("Location: routes.php?error=1");
    exit;
}
$stmt = $pdo->prepare("
    INSERT INTO routes (business_id, origin, destination, fare, status, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$business_id, $origin, $destination, $fare, $status]);

header("Location: routes.php?success=1");
exit;
?>
