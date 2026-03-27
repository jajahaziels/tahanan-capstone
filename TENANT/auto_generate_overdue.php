<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) return;

$tenant_id = (int)$_SESSION['tenant_id'];

// Get active lease
$leaseStmt = $conn->prepare("
    SELECT le.ID, le.start_date, le.rent_due_day
    FROM leasetbl le
    WHERE le.tenant_id = ? AND le.status = 'active'
    LIMIT 1
");
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

if (!$lease) return;

$start  = new DateTime($lease['start_date']);
$today  = new DateTime();
$cursor = new DateTime($start->format('Y-m-01'));

while ($cursor <= $today) {

    // Compute due date
    if (!empty($lease['rent_due_day'])) {
        $day = min((int)$lease['rent_due_day'], (int)$cursor->format('t'));
        $dueDate = $cursor->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
    } else {
        $dueDate = (clone $cursor)->modify('last day of this month')->format('Y-m-d');
    }

    if ($dueDate === '0000-00-00') {
        $cursor->modify('+1 month');
        continue;
    }

    if (new DateTime($dueDate) < $today) {

        $chk = $conn->prepare("
            SELECT id FROM paymentstbl
            WHERE lease_id = ? AND tenant_id = ? AND due_date = ?
        ");
        $chk->bind_param("iis", $lease['ID'], $tenant_id, $dueDate);
        $chk->execute();

        if ($chk->get_result()->num_rows == 0) {

            $ins = $conn->prepare("
                INSERT IGNORE INTO paymentstbl
                (lease_id, tenant_id, landlord_id, payment_type, amount, due_date, status, created_at)
                SELECT ?, ?, le.landlord_id, 'rent', 0, ?, 'overdue', NOW()
                FROM leasetbl le WHERE le.ID = ?
            ");
            $ins->bind_param("iisi", $lease['ID'], $tenant_id, $dueDate, $lease['ID']);
            $ins->execute();
        }
    }

    $cursor->modify('+1 month');
}
?>