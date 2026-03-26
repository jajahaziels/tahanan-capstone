<?php
require_once '../connection.php';
include '../session_auth.php';

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../connection.php';

if (!isset($_SESSION['landlord_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$landlord_id    = intval($_SESSION['landlord_id']);
$lease_id       = intval($_POST['lease_id']    ?? 0);
$tenant_id      = intval($_POST['tenant_id']   ?? 0);
$amount         = floatval($_POST['amount']    ?? 0);
$paid_date      = trim($_POST['paid_date']     ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'cash');
$reference_no   = trim($_POST['reference_no']  ?? '');
$remarks        = trim($_POST['remarks']       ?? '');
$payment_type   = 'rent';
$status         = 'paid';

// Basic validation
if ($lease_id <= 0 || $tenant_id <= 0 || $amount <= 0 || empty($paid_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input. Please fill all required fields.']);
    exit;
}

// Security: verify lease belongs to this landlord
$checkSql = "SELECT ID, rent_due_day FROM leasetbl WHERE ID = ? AND landlord_id = ? AND tenant_id = ? LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("iii", $lease_id, $landlord_id, $tenant_id);
$checkStmt->execute();
$leaseRow = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$leaseRow) {
    echo json_encode(['success' => false, 'message' => 'Lease not found or access denied.']);
    exit;
}

// Compute due_date from rent_due_day if available, else fallback to paid_date
$due_date = $paid_date;
if (!empty($leaseRow['rent_due_day'])) {
    $paidMonth = date('Y-m', strtotime($paid_date));
    $due_date  = $paidMonth . '-' . str_pad($leaseRow['rent_due_day'], 2, '0', STR_PAD_LEFT);
}

// Insert payment
$insertSql = "
    INSERT INTO paymentstbl
        (lease_id, tenant_id, landlord_id, payment_type, amount, due_date, paid_date, payment_method, status, reference_no, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param(
    "iiisdssssss",
    $lease_id,
    $tenant_id,
    $landlord_id,
    $payment_type,
    $amount,
    $due_date,
    $paid_date,
    $payment_method,
    $status,
    $reference_no,
    $remarks
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>