<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO payments 
    (lease_id, tenant_id, landlord_id, payment_type, amount, due_date)
    VALUES (?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiisds",
        $_POST['lease_id'],
        $_POST['tenant_id'],
        $_POST['landlord_id'],
        $_POST['payment_type'],
        $_POST['amount'],
        $_POST['due_date']
    );
    $stmt->execute();

    header("Location: payments.php?lease_id=" . $_POST['lease_id']);
}
?>