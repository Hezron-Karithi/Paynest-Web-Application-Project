<?php
require 'db.php';
$result = $conn->query("SELECT title, message, updated_at FROM system_general_info ORDER BY id DESC LIMIT 1");
$data = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($data);
?>
