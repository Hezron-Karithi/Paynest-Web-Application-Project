<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_guard.php';
$businessId = $_SESSION['business_id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $check = $pdo->prepare("SELECT id FROM invoices WHERE id=? AND business_id=?");
    $check->execute([$id, $businessId]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM payments WHERE invoice_id=? AND business_id=?")->execute([$id, $businessId]);
        $pdo->prepare("DELETE FROM invoices WHERE id=? AND business_id=?")->execute([$id, $businessId]);
    }
}
header("Location: invoices.php");
exit;
?>
