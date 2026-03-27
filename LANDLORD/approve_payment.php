<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    exit("Unauthorized");
}

$landlord_id = $_SESSION['landlord_id'];
$payment_id  = $_POST['payment_id'];

// Get payment info (ensure it belongs to landlord)
$stmt = $conn->prepare("
    SELECT p.lease_id, p.tenant_id, p.due_date, p.amount
    FROM paymentstbl p
    JOIN leasetbl le ON p.lease_id = le.ID
    WHERE p.id = ? AND le.landlord_id = ?
");
$stmt->bind_param("ii", $payment_id, $landlord_id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    exit("Invalid payment");
}

$lease_id  = $p['lease_id'];
$tenant_id = $p['tenant_id'];
$due       = $p['due_date'];
$amount    = $p['amount'];

// Get rent
$r = $conn->prepare("
    SELECT ls.price
    FROM leasetbl le
    JOIN listingtbl ls ON le.listing_id = ls.ID
    WHERE le.ID = ?
");
$r->bind_param("i", $lease_id);
$r->execute();
$rent = (float)$r->get_result()->fetch_assoc()['price'];

// Sum already approved payments
$s = $conn->prepare("
    SELECT SUM(amount) total
    FROM paymentstbl
    WHERE lease_id=? AND tenant_id=? AND due_date=? 
    AND status IN ('paid','partial')
");
$s->bind_param("iis", $lease_id, $tenant_id, $due);
$s->execute();
$total = (float)($s->get_result()->fetch_assoc()['total'] ?? 0);

$newTotal = $total + $amount;

// Final decision
$final = ($newTotal >= $rent) ? 'paid' : 'partial';

// Update
$upd = $conn->prepare("
    UPDATE paymentstbl SET status=? WHERE id=?
");
$upd->bind_param("si", $final, $payment_id);
$upd->execute();

echo "success";
?>