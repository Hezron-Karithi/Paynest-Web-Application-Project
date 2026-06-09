<?php
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: payments.php");
    exit;
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['business_id'])) {
    header("Location: admin_login.php");
    exit;
}
$business_id = $_SESSION['business_id'];
$invoice_id = $_POST['invoice_id'] ?? '';
$amount = $_POST['amount'] ?? '';
$mpesa_code = trim($_POST['mpesa_code'] ?? '');
if (empty($invoice_id) || empty($amount) || empty($mpesa_code)) {
    header("Location: payments.php?error=1");
    exit;
}
$stmt = $pdo->prepare("
    SELECT id, total_amount 
    FROM invoices 
    WHERE id = ? AND business_id = ?
");
$stmt->execute([$invoice_id, $business_id]);
$invoice = $stmt->fetch();
if (!$invoice) {
    header("Location: payments.php?error=1");
    exit;
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (business_id, invoice_id, amount, mpesa_code, status, created_at)
        VALUES (?, ?, ?, ?, 'COMPLETED', NOW())
    ");
    $stmt->execute([$business_id, $invoice_id, $amount, $mpesa_code]);
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount),0) 
        FROM payments 
        WHERE invoice_id = ? AND status = 'COMPLETED'
    ");
    $stmt->execute([$invoice_id]);
    $totalPaid = $stmt->fetchColumn();
    if ($totalPaid >= $invoice['total_amount']) {
        $pdo->prepare("
            UPDATE invoices 
            SET status = 'PAID'
            WHERE id = ? AND business_id = ?
        ")->execute([$invoice_id, $business_id]);
    } else {
        $pdo->prepare("
            UPDATE invoices 
            SET status = 'PENDING'
            WHERE id = ? AND business_id = ?
        ")->execute([$invoice_id, $business_id]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: payments.php?error=1");
    exit;
}
header("Location: payments.php?success=1");
exit;
?>
