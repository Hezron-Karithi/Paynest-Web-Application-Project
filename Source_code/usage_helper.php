<?php
require_once __DIR__ . '/bootstrap.php';
function countReceiptsThisMonth($pdo, $businessId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM receipts
        WHERE business_id=?
        AND MONTH(created_at)=MONTH(CURDATE())
        AND YEAR(created_at)=YEAR(CURDATE())
    ");
    $stmt->execute([$businessId]);
    return (int)$stmt->fetchColumn();
}
function countVehicles($pdo, $businessId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE business_id=?");
    $stmt->execute([$businessId]);
    return (int)$stmt->fetchColumn();
}
function countDrivers($pdo, $businessId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM drivers WHERE business_id=?");
    $stmt->execute([$businessId]);
    return (int)$stmt->fetchColumn();
}
?>
