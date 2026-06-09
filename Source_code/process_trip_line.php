<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){exit;}
$trip=$_POST['trip_id'];
$pass=$_POST['passengers'];
$fare=$_POST['fare'];
$total=$_POST['total_amount'];
$stmt=$pdo->prepare("INSERT INTO trip_revenue_lines (trip_id,passengers,fare,total_amount) VALUES (?,?,?,?)");
$stmt->execute([$trip,$pass,$fare,$total]);
header("Location: trip_detail.php?id=".$trip);
exit;
?>
