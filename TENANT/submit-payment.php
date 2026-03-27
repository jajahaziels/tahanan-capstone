<?php
ob_start();
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => "PHP Error [$errno]: $errstr in $errfile line $errline"
    ]);
    exit;
});

try {

    if (!isset($_SESSION['tenant_id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
    }

    $tenant_id   = (int)    $_SESSION['tenant_id'];
    $lease_id    = (int)   ($_POST['lease_id']     ?? 0);
    $payment_id  = (int)   ($_POST['payment_id']   ?? 0);
    $amount      = (float) ($_POST['amount']       ?? 0);
    $paid_date   = trim($_POST['paid_date']         ?? '');
    $paid_method = trim($_POST['payment_method']    ?? '');
    $pay_type    = trim($_POST['payment_type']      ?? 'rent');
    $reference   = trim($_POST['reference_no']      ?? '');
    $remarks     = trim($_POST['remarks']           ?? '');

    // ── Block future dates ──
    if ($paid_date) {
        $paidDateObj = new DateTime($paid_date);
        $todayObj    = new DateTime('today');
        if ($paidDateObj > $todayObj) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Date paid cannot be a future date.']); exit;
        }
    }

    if (!$lease_id || !$amount || !$paid_date) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
    }

    if ($amount <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero.']); exit;
    }

    // ── Fetch lease ──
    $leaseStmt = $conn->prepare("
        SELECT le.rent_due_day, ls.price, le.landlord_id
        FROM leasetbl le
        JOIN listingtbl ls ON le.listing_id = ls.ID
        WHERE le.ID = ? AND le.tenant_id = ?
        LIMIT 1
    ");
    if (!$leaseStmt) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare lease: '.$conn->error]); exit; }
    $leaseStmt->bind_param("ii", $lease_id, $tenant_id);
    $leaseStmt->execute();
    $lease = $leaseStmt->get_result()->fetch_assoc();

    if (!$lease) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Lease not found.']); exit; }

    $rent        = (float) $lease['price'];
    $landlord_id = (int)   $lease['landlord_id'];

    // ── Resolve due date ──
    // For "Pay Now" on an overdue row, use that row's exact due_date
    // For fresh submissions, compute from paid_date month + rent_due_day
    $due = null;

    if ($payment_id > 0) {
        $dueStmt = $conn->prepare("SELECT due_date FROM paymentstbl WHERE id = ? AND tenant_id = ? LIMIT 1");
        if (!$dueStmt) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare due: '.$conn->error]); exit; }
        $dueStmt->bind_param("ii", $payment_id, $tenant_id);
        $dueStmt->execute();
        $dueRow = $dueStmt->get_result()->fetch_assoc();
        $due = $dueRow['due_date'] ?? null;
    }

    if (!$due && !empty($lease['rent_due_day'])) {
        $day    = (int) $lease['rent_due_day'];
        $month  = date('Y-m', strtotime($paid_date));
        $maxDay = (int) date('t', strtotime($month . '-01'));
        $day    = min($day, $maxDay);
        $due    = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
    }
    if (!$due) {
        $due = date('Y-m-t', strtotime($paid_date));
    }

    // ── Proof upload ──
    $proof_path = null;
    if (!empty($_FILES['proof_file']['tmp_name'])) {
        $uploadDir = '../uploads/proofs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
        if (!in_array($ext, $allowed)) {
            ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Invalid file type. Allowed: jpg, png, gif, webp, pdf.']); exit;
        }
        $filename = 'proof_' . $tenant_id . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $uploadDir . $filename)) {
            ob_end_clean(); echo json_encode(['success'=>false,'message'=>'File upload failed.']); exit;
        }
        $proof_path = 'proofs/' . $filename;
    }

    // ── Compute cumulative paid so far for this due_date (only confirmed paid/partial rows) ──
    // This is used later by the landlord approval to set the right status
    // Here we just always submit as pending_verification
    $status = 'pending_verification';

    // ── Insert or Update logic ──
    if ($payment_id > 0) {
        // "Pay Now" from an overdue row — update that specific placeholder row
        $upd = $conn->prepare("
            UPDATE paymentstbl
            SET amount         = ?,
                paid_date      = ?,
                payment_method = ?,
                reference_no   = ?,
                remarks        = ?,
                proof_path     = COALESCE(?, proof_path),
                status         = ?
            WHERE id = ? AND tenant_id = ?
        ");
        if (!$upd) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare upd: '.$conn->error]); exit; }
        $upd->bind_param("dssssssii",
            $amount, $paid_date, $paid_method, $reference, $remarks,
            $proof_path, $status,
            $payment_id, $tenant_id
        );
        $upd->execute();
        if ($upd->error) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Update error: '.$upd->error]); exit; }

    } else {
        // "Submit Payment" button (no specific payment_id)
        // Check if an auto-generated overdue placeholder exists for this due_date
        $chk = $conn->prepare("
            SELECT id, status, amount
            FROM paymentstbl
            WHERE lease_id      = ?
              AND tenant_id     = ?
              AND payment_type  = ?
              AND due_date      = ?
              AND status        = 'overdue'
              AND amount        = 0
            LIMIT 1
        ");
        if (!$chk) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare chk: '.$conn->error]); exit; }
        $chk->bind_param("iiss", $lease_id, $tenant_id, $pay_type, $due);
        $chk->execute();
        $overdueRow = $chk->get_result()->fetch_assoc();

        if ($overdueRow) {
            // Update the overdue placeholder instead of inserting (avoids duplicate key)
            $existingId = (int) $overdueRow['id'];
            $upd2 = $conn->prepare("
                UPDATE paymentstbl
                SET amount         = ?,
                    paid_date      = ?,
                    payment_method = ?,
                    reference_no   = ?,
                    remarks        = ?,
                    proof_path     = COALESCE(?, proof_path),
                    status         = ?
                WHERE id = ? AND tenant_id = ?
            ");
            if (!$upd2) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare upd2: '.$conn->error]); exit; }
            $upd2->bind_param("dssssssii",
                $amount, $paid_date, $paid_method, $reference, $remarks,
                $proof_path, $status,
                $existingId, $tenant_id
            );
            $upd2->execute();
            if ($upd2->error) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Update2 error: '.$upd2->error]); exit; }

        } else {
            // No overdue placeholder — always INSERT a new row
            // This handles: advance payments, additional payments in same month, new months
            $ins = $conn->prepare("
                INSERT INTO paymentstbl
                    (lease_id, tenant_id, landlord_id, payment_type,
                     amount, due_date, paid_date, payment_method,
                     reference_no, remarks, proof_path, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            if (!$ins) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Prepare ins: '.$conn->error]); exit; }
            $ins->bind_param(
                "iiisdsssssss",
                $lease_id, $tenant_id, $landlord_id, $pay_type,
                $amount, $due, $paid_date, $paid_method,
                $reference, $remarks, $proof_path, $status
            );
            $ins->execute();
            if ($ins->error) { ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Insert error: '.$ins->error]); exit; }
        }
    }

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage() . ' on line ' . $e->getLine()
    ]);
}
?>