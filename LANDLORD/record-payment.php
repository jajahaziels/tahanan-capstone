<?php
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tenant_id = $_POST['tenant_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $paid_date = $_POST['paid_date'] ?? null;
    $landlord_id = $_SESSION['landlord_id'];
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $payment_type = $_POST['payment_type'] ?? 'Rent';
    $remarks = $_POST['remarks'] ?? '';
    $reference_no = $_POST['reference_no'] ?? '';

    // ✅ Validate input
    if ($tenant_id > 0 && $amount > 0 && $paid_date) {

        // Get active lease
        $stmt = $conn->prepare("SELECT ID FROM leasetbl WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $leaseResult = $stmt->get_result();

        if ($leaseResult->num_rows > 0) {

            $lease = $leaseResult->fetch_assoc();
            $lease_id = $lease['ID'];

            // Insert payment
            $stmt = $conn->prepare("INSERT INTO paymentstbl 
                (lease_id, tenant_id, landlord_id, payment_type, amount, paid_date, payment_method, status, reference_no, remarks, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

            $status = 'Paid';

            $stmt->bind_param(
                "iiisssssss",
                $lease_id,
                $tenant_id,
                $landlord_id,
                $payment_type,
                $amount,
                $paid_date,
                $payment_method,
                $status,
                $reference_no,
                $remarks
            );

            if ($stmt->execute()) {
                echo json_encode([
                    "success" => true,
                    "message" => "Payment recorded successfully"
                ]);
                exit;
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $conn->error
                ]);
                exit;
            }

        } else {
            echo json_encode([
                "success" => false,
                "message" => "No active lease found for this tenant."
            ]);
            exit;
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid input."
        ]);
        exit;
    }

} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}