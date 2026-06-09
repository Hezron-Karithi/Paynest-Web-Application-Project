<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: trips.php");exit;}
$business_id=$_SESSION['business_id'];
$vehicle=$_POST['vehicle_id'];
$route=$_POST['route_id'];
$driver=$_POST['driver_id'];
$date=$_POST['trip_date'];
$stmt=$pdo->prepare("INSERT INTO trips (business_id,vehicle_id,route_id,driver_id,trip_date) VALUES (?,?,?,?,?)");
$stmt->execute([$business_id,$vehicle,$route,$driver,$date]);
header("Location: trips.php");
exit;
?>
