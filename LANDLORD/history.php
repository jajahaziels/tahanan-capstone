<?php
require_once '../connection.php';
include '../session_auth.php';

// ✅ 1. Get landlord ID FIRST
$landlord_id = $_SESSION['landlord_id'];

// ✅ 2. Verify landlord status
$verifyQuery = "SELECT verification_status, admin_rejection_reason FROM landlordtbl WHERE ID = ?";
$verifyStmt  = $conn->prepare($verifyQuery);
$verifyStmt->bind_param("i", $landlord_id);
$verifyStmt->execute();
$resultVerify = $verifyStmt->get_result();
$landlord     = $resultVerify->fetch_assoc();
$status       = $landlord['verification_status'] ?? 'unverified';
$reason       = $landlord['admin_rejection_reason'] ?? '';

// ✅ 3. AUTO-GENERATE OVERDUE ROWS for all active tenants under this landlord
$activeLeasesSql = "
    SELECT ls.ID as lease_id, ls.tenant_id, ls.start_date, ls.end_date, ls.rent
    FROM leasetbl ls
    WHERE ls.landlord_id = ? AND ls.status = 'active'
";
$alStmt = $conn->prepare($activeLeasesSql);
$alStmt->bind_param("i", $landlord_id);
$alStmt->execute();
$activeLeases = $alStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$today = new DateTime();

foreach ($activeLeases as $al) {
    $leaseStart = new DateTime($al['start_date']);
    $cursor     = clone $leaseStart;
    $cursor->modify('first day of this month');

    while ($cursor <= $today) {
        $dueYear  = (int)$cursor->format('Y');
        $dueMonth = (int)$cursor->format('m');

        $chkSql  = "
            SELECT COUNT(*) AS cnt FROM paymentstbl
            WHERE lease_id = ? AND tenant_id = ?
              AND payment_type = 'rent'
              AND YEAR(due_date) = ? AND MONTH(due_date) = ?
        ";
        $chkStmt = $conn->prepare($chkSql);
        $chkStmt->bind_param("iiii", $al['lease_id'], $al['tenant_id'], $dueYear, $dueMonth);
        $chkStmt->execute();
        $cnt = $chkStmt->get_result()->fetch_assoc()['cnt'];

        if ($cnt == 0 && $cursor < $today) {
            $dueDate = (clone $cursor)->modify('last day of this month')->format('Y-m-d');
            if (new DateTime($dueDate) < $today) {
                $insSql = "
                    INSERT INTO paymentstbl
                        (lease_id, tenant_id, landlord_id, payment_type, amount, due_date,
                         paid_date, payment_method, status, reference_no, remarks, created_at)
                    VALUES (?, ?, ?, 'rent', NULL, ?, NULL, NULL, 'overdue', NULL, NULL, NOW())
                ";
                $insStmt = $conn->prepare($insSql);
                $insStmt->bind_param("iiis", $al['lease_id'], $al['tenant_id'], $landlord_id, $dueDate);
                $insStmt->execute();
            }
        }

        $cursor->modify('+1 month');
    }
}

// ✅ 4. Fetch active tenants with last payment and request info
$query = "SELECT 
    t.ID as tenant_id,
    t.firstName,
    t.lastName,
    t.profilePic,
    l.listingName as property_name,
    ls.ID as lease_id,
    ls.rent as amount,
    ls.pdf_path,
    MAX(p.paid_date) AS last_payment_date,
    (SELECT COUNT(*) FROM lease_renewaltbl r WHERE r.lease_id = ls.ID AND r.landlord_status = 'pending') AS pending_renewal,
    (SELECT COUNT(*) FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending') AS pending_termination,
    (SELECT tt.reason FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending' ORDER BY tt.terminated_at DESC LIMIT 1) AS termination_reason,
    (SELECT tt.terminated_at FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending' ORDER BY tt.terminated_at DESC LIMIT 1) AS termination_date,
    (SELECT rr.landlord_response FROM lease_renewaltbl rr WHERE rr.lease_id = ls.ID ORDER BY rr.requested_date DESC LIMIT 1) AS renewal_response,
    (SELECT COUNT(*) FROM paymentstbl pv WHERE pv.lease_id = ls.ID AND pv.status = 'pending_verification') AS pending_payments
FROM leasetbl ls
JOIN tenanttbl t ON ls.tenant_id = t.ID
JOIN listingtbl l ON ls.listing_id = l.ID
LEFT JOIN paymentstbl p ON ls.ID = p.lease_id AND p.status IN ('paid','partial')
WHERE ls.landlord_id = ?
AND ls.status = 'active'
GROUP BY 
    ls.ID, t.ID, t.firstName, t.lastName,
    t.profilePic, l.listingName, ls.rent, ls.pdf_path";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

$active_tenants = [];
while ($row = $result->fetch_assoc()) {
    $active_tenants[] = $row;
}

// ✅ 5. Fetch maintenance requests / complaints for this landlord
$complaints_query = "SELECT 
                        mr.ID as complaint_id,
                        mr.title,
                        mr.description,
                        mr.category,
                        mr.priority,
                        mr.status,
                        mr.requested_date,
                        mr.scheduled_date,
                        mr.completed_date,
                        mr.photo_path,
                        t.firstName,
                        t.lastName,
                        t.profilePic,
                        l.listingName as property_name
                    FROM maintenance_requeststbl mr
                    JOIN leasetbl ls ON mr.lease_id = ls.ID
                    JOIN tenanttbl t ON ls.tenant_id = t.ID
                    JOIN listingtbl l ON ls.listing_id = l.ID
                    WHERE mr.landlord_id = ?
                    ORDER BY mr.requested_date DESC";

$stmt2 = $conn->prepare($complaints_query);
$stmt2->bind_param("i", $landlord_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$complaints = [];
while ($row = $result2->fetch_assoc()) {
    $complaints[] = $row;
}

// ✅ 6. Fetch full payment history per tenant (for history modal + overdue count)
$paymentHistoryMap = [];
$histSql = "
    SELECT 
        p.id, p.payment_type, p.amount, p.due_date, p.paid_date,
        p.payment_method, p.status, p.reference_no, p.remarks, p.created_at,
        p.tenant_id, p.proof_path
    FROM paymentstbl p
    JOIN leasetbl ls ON p.lease_id = ls.ID
    WHERE ls.landlord_id = ?
    ORDER BY p.created_at DESC
";
$histStmt = $conn->prepare($histSql);
$histStmt->bind_param("i", $landlord_id);
$histStmt->execute();
$histResult = $histStmt->get_result();
while ($row = $histResult->fetch_assoc()) {
    $paymentHistoryMap[$row['tenant_id']][] = $row;
}

// ✅ 7. Helper functions
function getPriorityBadge($priority) {
    return match (strtolower($priority)) {
        'low'    => '<span class="badge bg-success">Low</span>',
        'medium' => '<span class="badge bg-warning text-dark">Medium</span>',
        'high'   => '<span class="badge bg-danger">High</span>',
        'urgent' => '<span class="badge bg-dark text-white">Urgent</span>',
        default  => '<span class="badge bg-secondary">' . htmlspecialchars($priority) . '</span>'
    };
}

function getStatusBadge($status) {
    return match (strtolower($status)) {
        'pending'     => '<span class="badge bg-warning text-dark">Pending</span>',
        'in progress' => '<span class="badge bg-primary">Scheduled</span>',
        'completed'   => '<span class="badge bg-success">Completed</span>',
        'rejected'    => '<span class="badge bg-danger">Rejected</span>',
        default       => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
    };
}

function getDaysLate($dueDate) {
    if (!$dueDate) return null;
    $due   = new DateTime($dueDate);
    $today = new DateTime();
    if ($today <= $due) return null;
    return (int)$today->diff($due)->days;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Rental Management</title>
</head>

<style>
    /* ============================= */
    /* LAYOUT                        */
    /* ============================= */
    .payments-section {
        margin: 140px auto 40px auto;
        width: 80%;
        background: white;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        padding: 40px;
    }

    /* ============================= */
    /* TABLE                         */
    /* ============================= */
    .table {
        border-collapse: separate;
        border-spacing: 0 8px;
        margin-top: -8px;
    }
    .table thead th {
        background-color: #f8fafc;
        color: #718096;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border: none;
        padding: 16px;
        white-space: nowrap;
    }
    .table tbody tr {
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s;
    }
    .table tbody tr:hover {
        transform: scale(1.005);
        background-color: #fffafb !important;
    }
    .table td {
        padding: 14px 16px;
        border-top: 1px solid #edf2f7;
        border-bottom: 1px solid #edf2f7;
        color: #4a5568;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* ============================= */
    /* PROFILE AVATAR                */
    /* ============================= */
    .profile-avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        flex-shrink: 0;
    }

    /* ============================= */
    /* SECTION HEADER                */
    /* ============================= */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .section-title i { color: #8d0b41; }

    /* ============================= */
    /* EMPTY STATE                   */
    /* ============================= */
    .empty-reviews {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }
    .empty-reviews i {
        font-size: 64px;
        color: #cbd5e0;
        margin-bottom: 20px;
    }
    .empty-reviews h3 { color: #4a5568; margin-bottom: 8px; }
    .empty-reviews p  { color: #a0aec0; }

    /* ============================= */
    /* STANDARDIZED ACTION BUTTONS   */
    /* ============================= */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        height: 34px;
        padding: 0 14px;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s ease;
        text-decoration: none;
        flex-shrink: 0;
    }
    .action-btn i { font-size: 0.85rem; }

    .action-btn-primary {
        background-color: #8d0b41;
        color: #fff;
    }
    .action-btn-primary:hover {
        background-color: #6a0831;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(141,11,65,0.3);
    }
    .action-btn-outline-blue {
        background-color: transparent;
        border: 1.5px solid #0d6efd;
        color: #0d6efd;
    }
    .action-btn-outline-blue:hover {
        background-color: #0d6efd;
        color: #fff;
        transform: translateY(-1px);
    }
    .action-btn-outline-red {
        background-color: transparent;
        border: 1.5px solid #dc3545;
        color: #dc3545;
    }
    .action-btn-outline-red:hover {
        background-color: #dc3545;
        color: #fff;
        transform: translateY(-1px);
    }
    .action-btn-success {
        background-color: #198754;
        color: #fff;
    }
    .action-btn-success:hover {
        background-color: #146c43;
        color: #fff;
        transform: translateY(-1px);
    }
    .action-btn-blue {
        background-color: #0d6efd;
        color: #fff;
    }
    .action-btn-blue:hover {
        background-color: #0a58ca;
        color: #fff;
        transform: translateY(-1px);
    }
    .action-btn-outline-maroon {
        background-color: transparent;
        border: 1.5px solid #8d0b41;
        color: #8d0b41;
    }
    .action-btn-outline-maroon:hover {
        background-color: #8d0b41;
        color: #fff;
        transform: translateY(-1px);
    }

    /* ============================= */
    /* LAST PAYMENT BADGE            */
    /* ============================= */
    .payment-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 34px;
        padding: 0 14px;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        white-space: nowrap;
        min-width: 120px;
        cursor: default;
    }
    .payment-badge-paid {
        background-color: #198754;
        color: #fff;
    }
    .payment-badge-unpaid {
        background-color: #dc3545;
        color: #fff;
    }

    /* Pending payment notification dot */
    .pending-dot {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffc107;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pending-dot:hover {
        background: #ffc107;
        color: #212529;
    }
    .pending-dot .dot {
        width: 8px;
        height: 8px;
        background: #e65100;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 1.2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.5; transform: scale(1.3); }
    }

    /* ============================= */
    /* SHARED MODAL THEME            */
    /* ============================= */
    .themed-modal .modal-content {
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        animation: fadeInScale 0.25s ease;
        border: none;
    }
    .themed-modal .modal-header {
        background-color: #8d0b41;
        color: #fff;
        border-bottom: none;
        padding: 18px 24px;
    }
    .themed-modal .modal-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .themed-modal .btn-close { filter: invert(1); opacity: 0.85; }
    .themed-modal .modal-body {
        padding: 24px;
        background: #fff;
    }
    .themed-modal .modal-footer {
        border-top: 1px solid #f0f0f0;
        background: #fafafa;
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .themed-modal .form-label {
        font-weight: 600;
        font-size: 0.82rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }
    .themed-modal .form-control,
    .themed-modal .form-select {
        border-radius: 8px;
        border: 1.5px solid #e2e8f0;
        font-size: 0.9rem;
        padding: 10px 12px;
        transition: border-color 0.2s;
    }
    .themed-modal .form-control:focus,
    .themed-modal .form-select:focus {
        border-color: #8d0b41;
        box-shadow: 0 0 0 3px rgba(141,11,65,0.12);
    }

    .modal-info-box {
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 18px;
    }
    .modal-info-box .info-label {
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .modal-info-box .info-text {
        font-size: 0.92rem;
        color: #4a5568;
        line-height: 1.5;
    }
    .modal-info-box .info-date {
        font-size: 0.78rem;
        color: #a0aec0;
        margin-top: 6px;
    }
    .modal-info-box-red {
        background-color: #fff5f7;
        border-left: 4px solid #8d0b41;
    }
    .modal-info-box-red .info-label { color: #8d0b41; }
    .modal-info-box-blue {
        background-color: #f0f5ff;
        border-left: 4px solid #0d6efd;
    }
    .modal-info-box-blue .info-label { color: #0d6efd; }
    .modal-info-box-gold {
        background: #fffdf0;
        border-left: 4px solid #f59e0b;
    }
    .modal-info-box-gold .info-label { color: #92400e; }

    .modal-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 20px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .modal-btn-cancel  { background: #e2e8f0; color: #4a5568; }
    .modal-btn-cancel:hover { background: #cbd5e0; }
    .modal-btn-primary { background: #8d0b41; color: #fff; }
    .modal-btn-primary:hover { background: #6a0831; box-shadow: 0 4px 10px rgba(141,11,65,0.3); }
    .modal-btn-approve { background: #198754; color: #fff; }
    .modal-btn-approve:hover { background: #146c43; box-shadow: 0 4px 10px rgba(25,135,84,0.3); }
    .modal-btn-reject  { background: #8d0b41; color: #fff; }
    .modal-btn-reject:hover  { background: #6e0832; }
    .modal-btn-danger  { background: #dc3545; color: #fff; }
    .modal-btn-danger:hover  { background: #b02a37; }

    .modal-section-divider { border: none; border-top: 1.5px solid #edf2f7; margin: 18px 0; }

    @keyframes fadeInScale {
        from { transform: scale(0.96); opacity: 0; }
        to   { transform: scale(1);   opacity: 1; }
    }

    /* ============================= */
    /* PAYMENT HISTORY MODAL STYLES  */
    /* ============================= */
    .hist-summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .hist-summary-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #718096;
        margin-bottom: 4px;
    }
    .hist-summary-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
        line-height: 1.2;
    }
    .hist-summary-sub {
        font-size: 0.72rem;
        color: #a0aec0;
        margin-top: 2px;
    }
    .hist-th {
        color: #718096;
        text-transform: uppercase;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: .06em;
        padding: 11px 12px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
        text-align: left;
        background: #f8fafc;
    }
    .hist-td {
        padding: 12px;
        color: #4a5568;
        font-size: 0.85rem;
        border-bottom: 1px solid #f0f4f8;
        vertical-align: middle;
        white-space: nowrap;
    }
    .hist-tr:hover { background: #fffafb; }
    .hist-tr-overdue { background: #fff8f8; }
    .hist-tr-overdue:hover { background: #fff0f0 !important; }
    .hist-tr-pending { background: #fffdf0; }
    .hist-tr-pending:hover { background: #fffae0 !important; }

    .hist-badge-paid    { background:#d4edda; color:#155724; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-badge-partial { background:#cce5ff; color:#004085; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-badge-pending { background:#fff3cd; color:#856404; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-badge-overdue { background:#f8d7da; color:#721c24; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-badge-verify  { background:#e0d7ff; color:#4c1d95; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-badge-rejected{ background:#f8d7da; color:#721c24; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }

    .hist-type-rent    { background:rgba(141,11,65,.08); color:#8d0b41; padding:3px 9px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-type-deposit { background:#e8f5e9; color:#2e7d32; padding:3px 9px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-type-penalty { background:#fff3e0; color:#e65100; padding:3px 9px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .hist-type-other   { background:#e2e8f0; color:#4a5568; padding:3px 9px; border-radius:20px; font-size:.72rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }

    .hist-filter-input {
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 7px 12px;
        font-size: 0.83rem;
        outline: none;
        transition: border-color 0.2s;
        background: #fff;
        font-family: inherit;
    }
    .hist-filter-input:focus {
        border-color: #8d0b41;
        box-shadow: 0 0 0 3px rgba(141,11,65,0.12);
    }

    /* Days late chip */
    .days-late-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #fff0f0;
        color: #c0392b;
        border: 1px solid #f5c6cb;
        border-radius: 20px;
        padding: 1px 7px;
        font-size: 0.66rem;
        font-weight: 700;
        margin-left: 4px;
    }

    /* Approve / Reject buttons for pending verification */
    .approve-pay-btn {
        display: inline-flex; align-items: center; gap: 4px;
        background: #198754; color: #fff;
        border: none; border-radius: 7px;
        padding: 4px 11px; font-size: 0.74rem; font-weight: 600;
        cursor: pointer; transition: all 0.18s;
    }
    .approve-pay-btn:hover { background: #146c43; transform: translateY(-1px); }
    .reject-pay-btn {
        display: inline-flex; align-items: center; gap: 4px;
        background: #dc3545; color: #fff;
        border: none; border-radius: 7px;
        padding: 4px 11px; font-size: 0.74rem; font-weight: 600;
        cursor: pointer; transition: all 0.18s;
    }
    .reject-pay-btn:hover { background: #b02a37; transform: translateY(-1px); }
</style>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <?php if ($status !== 'verified'): ?>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-lg p-5 text-center" style="border-radius:20px; max-width:520px; border-top:5px solid #8d0b41;">
            <?php
            if ($status == 'pending') {
                $title   = "Verification in Progress";
                $icon    = "bi-hourglass-split";
                $color   = "#f0ad4e";
                $message = "Your submitted documents are currently being reviewed by the administrator.";
            } elseif ($status == 'rejected') {
                $title   = "Verification Rejected";
                $icon    = "bi-x-circle-fill";
                $color   = "#dc3545";
                $message = "Your verification request was rejected. Please review the reason below and submit again.";
            } else {
                $title   = "Account Verification Required";
                $icon    = "bi-shield-lock-fill";
                $color   = "#8d0b41";
                $message = "You must verify your landlord account before accessing landlord features.";
            }
            ?>
            <i class="bi <?= $icon ?>" style="font-size:70px;color:<?= $color ?>"></i>
            <h3 class="mt-3"><?= $title ?></h3>
            <p class="text-muted"><?= $message ?></p>
            <?php if ($status == 'rejected'): ?>
            <div class="alert alert-danger mt-3">
                <strong>Admin Reason:</strong><br>
                <?= htmlspecialchars($reason) ?>
            </div>
            <?php endif; ?>
            <a href="landlord-verification.php" class="btn mt-3 text-white" style="background:#8d0b41; border:none;">
                Verify Your Account
            </a>
        </div>
    </div>
    </body>
    </html>
    <?php exit; ?>
    <?php endif; ?>

    <!-- ============================= -->
    <!-- ACTIVE TENANTS TABLE          -->
    <!-- ============================= -->
    <div class="payments-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-person-vcard" style="font-size:2rem;"></i>
                Active Tenants &amp; Payment Records
            </h3>
        </div>

        <?php if (!empty($active_tenants)): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Tenant Name</th>
                        <th>Property</th>
                        <th>Lease</th>
                        <th>Rent Amount</th>
                        <th>Last Payment</th>
                        <th>Pending Payments</th>
                        <th>Tenant Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_tenants as $tenant):
                        $tenant_name        = ucwords(strtolower($tenant['firstName'] . ' ' . $tenant['lastName']));
                        $tenant_initial     = strtoupper(substr($tenant['firstName'], 0, 1));
                        $last_payment       = $tenant['last_payment_date'] ? date("M j, Y", strtotime($tenant['last_payment_date'])) : 'No Payment';
                        $termination_reason = htmlspecialchars($tenant['termination_reason'] ?? 'No reason provided.');
                        $termination_date   = $tenant['termination_date'] ? date("M j, Y h:i A", strtotime($tenant['termination_date'])) : '';
                        $pendingPayCount    = (int)($tenant['pending_payments'] ?? 0);
                    ?>
                        <tr>
                            <!-- Profile -->
                            <td>
                                <?php if (!empty($tenant['profilePic'])): ?>
                                    <a href="tenant-profile.php?tenant_id=<?= $tenant['tenant_id'] ?>">
                                        <img src="../uploads/<?= htmlspecialchars($tenant['profilePic']) ?>"
                                            alt="<?= htmlspecialchars($tenant_name) ?>"
                                            class="rounded-circle" width="40" height="40"
                                            style="object-fit:cover; border:2px solid #8d0b41;">
                                    </a>
                                <?php else: ?>
                                    <div class="profile-avatar-sm"><?= $tenant_initial ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Tenant Name -->
                            <td class="fw-bold text-dark"><?= htmlspecialchars($tenant_name) ?></td>

                            <!-- Property -->
                            <td>
                                <span class="text-muted">
                                    <i class="fas fa-home me-1"></i><?= htmlspecialchars($tenant['property_name']) ?>
                                </span>
                            </td>

                            <!-- Lease -->
                            <td>
                                <?php if (!empty($tenant['pdf_path'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($tenant['pdf_path']) ?>" target="_blank"
                                        class="action-btn action-btn-outline-blue">
                                        <i class="bi bi-file-earmark-pdf"></i> View Lease
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No Lease</span>
                                <?php endif; ?>
                            </td>

                            <!-- Rent Amount -->
                            <td>
                                <span class="fw-bold" style="color:#2d3748;">
                                    ₱<?= number_format($tenant['amount'] ?? 0, 2) ?>
                                </span>
                            </td>

                            <!-- Last Payment + History Button -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="payment-badge <?= $tenant['last_payment_date'] ? 'payment-badge-paid' : 'payment-badge-unpaid' ?>">
                                        <?= $last_payment ?>
                                    </span>

                                    <!-- View history button -->
                                    <button
                                        class="action-btn action-btn-outline-maroon view-history-btn"
                                        data-tenant-id="<?= $tenant['tenant_id'] ?>"
                                        data-tenant-name="<?= htmlspecialchars($tenant_name) ?>"
                                        data-property="<?= htmlspecialchars($tenant['property_name']) ?>"
                                        data-amount="<?= $tenant['amount'] ?? 0 ?>"
                                        title="View payment history">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </div>
                            </td>

                            <!-- Pending Payments -->
                            <td>
                                <?php if ($pendingPayCount > 0): ?>
                                    <button class="pending-dot view-history-btn"
                                        data-tenant-id="<?= $tenant['tenant_id'] ?>"
                                        data-tenant-name="<?= htmlspecialchars($tenant_name) ?>"
                                        data-property="<?= htmlspecialchars($tenant['property_name']) ?>"
                                        data-amount="<?= $tenant['amount'] ?? 0 ?>"
                                        data-filter-status="pending_verification"
                                        title="Click to review submitted payments">
                                        <span class="dot"></span>
                                        <?= $pendingPayCount ?> Awaiting Review
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.83rem;">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Tenant Requests -->
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php
                                    $hasRequest = false;

                                    if (($tenant['pending_renewal'] ?? 0) > 0):
                                        $hasRequest = true;
                                    ?>
                                        <button class="action-btn action-btn-blue renewal-btn"
                                            data-lease-id="<?= $tenant['lease_id'] ?>"
                                            data-type="renewal"
                                            data-reason=""
                                            data-date="">
                                            <i class="bi bi-arrow-repeat"></i> Renewal
                                        </button>
                                    <?php endif;

                                    if (($tenant['pending_termination'] ?? 0) > 0):
                                        $hasRequest = true;
                                    ?>
                                        <button class="action-btn action-btn-primary termination-btn"
                                            data-lease-id="<?= $tenant['lease_id'] ?>"
                                            data-type="termination"
                                            data-reason="<?= $termination_reason ?>"
                                            data-date="<?= $termination_date ?>">
                                            <i class="bi bi-file-earmark-x-fill"></i> Termination
                                        </button>
                                    <?php endif;

                                    if (!$hasRequest): ?>
                                        <span class="text-muted" style="font-size:0.85rem;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-reviews">
                <i class="fas fa-user-slash"></i>
                <h3>No Active Tenants</h3>
                <p>Once you approve applications, your active tenants and their rent status will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================= -->
    <!-- COMPLAINTS / MAINTENANCE      -->
    <!-- ============================= -->
    <div class="payments-section mt-5" style="margin-top:20px!important;">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-tools"></i>
                Complaint / Maintenance Requests
            </h3>
            <?php if (!empty($complaints)): ?>
                <span class="action-btn action-btn-primary" style="cursor:default;">
                    <?= count($complaints) ?> Requests
                </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($complaints)): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Tenant Name</th>
                        <th>Property</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Photo</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint):
                        $complaint_status = strtolower(trim($complaint['status']));
                        $tenant_name      = ucwords(strtolower($complaint['firstName'] . ' ' . $complaint['lastName']));
                        $tenant_initial   = strtoupper(substr($complaint['firstName'], 0, 1));
                        $requested_date   = $complaint['requested_date'] ? date("M j, Y", strtotime($complaint['requested_date'])) : '—';
                        $scheduled_date   = $complaint['scheduled_date'] ? date("M j, Y", strtotime($complaint['scheduled_date'])) : '—';
                        $completed_date   = $complaint['completed_date'] ? date("M j, Y", strtotime($complaint['completed_date'])) : '—';
                    ?>
                        <tr>
                            <td>
                                <?php if (!empty($complaint['profilePic'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($complaint['profilePic']) ?>"
                                        alt="<?= htmlspecialchars($tenant_name) ?>"
                                        class="rounded-circle" width="40" height="40"
                                        style="object-fit:cover; border:2px solid #8d0b41;">
                                <?php else: ?>
                                    <div class="profile-avatar-sm"><?= $tenant_initial ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($tenant_name) ?></td>
                            <td><span class="text-muted"><i class="fas fa-home me-1"></i><?= htmlspecialchars($complaint['property_name']) ?></span></td>
                            <td><?= htmlspecialchars($complaint['title']) ?></td>
                            <td><?= htmlspecialchars($complaint['category']) ?></td>
                            <td><?= getPriorityBadge($complaint['priority']) ?></td>
                            <td><?= getStatusBadge($complaint['status']) ?></td>
                            <td><?= $requested_date ?></td>
                            <td><?= $scheduled_date ?></td>
                            <td><?= $completed_date ?></td>
                            <td>
                                <?php if (!empty($complaint['photo_path'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($complaint['photo_path']) ?>" target="_blank"
                                        class="action-btn action-btn-outline-blue" style="text-decoration:none;">
                                        <i class="fas fa-image"></i> View
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if ($complaint_status === 'completed'): ?>
                                        <button class="action-btn action-btn-outline-red remove-complaint-btn"
                                            data-id="<?= $complaint['complaint_id'] ?>">
                                            <i class="bi bi-trash3-fill"></i> Remove
                                        </button>
                                    <?php elseif ($complaint_status === 'rejected'): ?>
                                        <button class="action-btn action-btn-outline-blue"
                                            data-bs-toggle="modal" data-bs-target="#complaintModal"
                                            data-id="<?= $complaint['complaint_id'] ?>"
                                            data-title="<?= htmlspecialchars($complaint['title']) ?>">
                                            <i class="bi bi-reply-all-fill"></i> Respond
                                        </button>
                                        <button class="action-btn action-btn-outline-red remove-complaint-btn"
                                            data-id="<?= $complaint['complaint_id'] ?>">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="action-btn action-btn-blue"
                                            data-bs-toggle="modal" data-bs-target="#complaintModal"
                                            data-id="<?= $complaint['complaint_id'] ?>"
                                            data-title="<?= htmlspecialchars($complaint['title']) ?>">
                                            <i class="bi bi-reply-all-fill"></i> Respond
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-reviews">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No Complaints / Requests</h3>
                <p>Once tenants submit maintenance requests, they will appear here for your review and action.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================= -->
    <!-- COMPLAINT RESPONSE MODAL      -->
    <!-- ============================= -->
    <div class="modal fade themed-modal" id="complaintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="complaintForm" method="post" action="maintenance-respond.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-tools"></i> Respond to Complaint</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="complaint_id" id="complaint_id">
                        <div class="mb-3">
                            <label for="comp_status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="comp_status" required>
                                <option value="pending">Pending</option>
                                <option value="in progress">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="response" class="form-label">Message / Action to Tenant</label>
                            <textarea class="form-control" name="response" id="response" rows="3"
                                placeholder="Write your message here..."></textarea>
                        </div>
                        <div class="mb-1">
                            <label for="scheduled_date" class="form-label">Scheduled Date (if applicable)</label>
                            <input type="date" class="form-control" name="scheduled_date" id="scheduled_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                        <button type="submit" class="modal-btn modal-btn-primary">
                            <i class="bi bi-send-fill"></i> Send Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================= -->
    <!-- APPROVE / REJECT REQUEST MODAL-->
    <!-- ============================= -->
    <div class="modal fade themed-modal" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalTitle">
                        <i class="bi bi-file-earmark-text-fill"></i> Manage Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reqLeaseId">
                    <input type="hidden" id="reqType">

                    <div id="terminationReasonSection" style="display:none;">
                        <div class="modal-info-box modal-info-box-red">
                            <div class="info-label"><i class="bi bi-chat-left-text-fill"></i> Tenant's Reason</div>
                            <div class="info-text" id="terminationReasonText">—</div>
                            <div class="info-date" id="terminationDateText"></div>
                        </div>
                    </div>
                    <div id="renewalInfoSection" style="display:none;">
                        <div class="modal-info-box modal-info-box-blue">
                            <div class="info-label"><i class="bi bi-arrow-repeat"></i> Renewal Request</div>
                            <div class="info-text">The tenant is requesting to renew their lease. Please approve or reject below.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button class="modal-btn modal-btn-approve" id="approveBtn">
                        <i class="bi bi-check-circle-fill"></i> Approve
                    </button>
                    <button class="modal-btn modal-btn-reject" id="rejectBtn">
                        <i class="bi bi-x-circle-fill"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================= -->
    <!-- PAYMENT HISTORY MODAL         -->
    <!-- ============================= -->
    <div class="modal fade themed-modal" id="paymentHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clock-history"></i>
                        Payment History — <span id="histModalTenantName">—</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">

                    <!-- Info Bar -->
                    <div style="background:linear-gradient(135deg,#8d0b41,#6a0831);color:#fff;padding:14px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <i class="bi bi-building" style="font-size:1.5rem;opacity:.8;"></i>
                        <div>
                            <div style="font-weight:700;font-size:.95rem;" id="histModalProperty">—</div>
                            <div style="font-size:.78rem;opacity:.75;">Active Lease</div>
                        </div>
                        <div style="margin-left:auto;text-align:right;">
                            <div style="font-size:.72rem;opacity:.75;">Monthly Rent</div>
                            <div style="font-size:1.2rem;font-weight:700;" id="histModalRent">₱0.00</div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;padding:18px 20px 4px;">
                        <div class="hist-summary-card" style="border-left:4px solid #198754;">
                            <div class="hist-summary-label">Total Paid</div>
                            <div class="hist-summary-value" id="histTotalPaid">₱0.00</div>
                            <div class="hist-summary-sub">All paid records</div>
                        </div>
                        <div class="hist-summary-card" style="border-left:4px solid #0d6efd;">
                            <div class="hist-summary-label">Transactions</div>
                            <div class="hist-summary-value" id="histTotalCount">0</div>
                            <div class="hist-summary-sub" id="histPaidPending">0 paid · 0 pending</div>
                        </div>
                        <div class="hist-summary-card" style="border-left:4px solid #f59e0b;">
                            <div class="hist-summary-label">Last Payment</div>
                            <div class="hist-summary-value" id="histLastPayment" style="font-size:1rem;">—</div>
                            <div class="hist-summary-sub">Most recent</div>
                        </div>
                        <div class="hist-summary-card" style="border-left:4px solid #dc3545;">
                            <div class="hist-summary-label">Overdue</div>
                            <div class="hist-summary-value" id="histOverdueCount">0</div>
                            <div class="hist-summary-sub">Record(s)</div>
                        </div>
                        <div class="hist-summary-card" style="border-left:4px solid #7c3aed;">
                            <div class="hist-summary-label">Awaiting Review</div>
                            <div class="hist-summary-value" id="histVerifyCount">0</div>
                            <div class="hist-summary-sub">Submitted by tenant</div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div style="padding:12px 20px 14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                        <input type="text" id="histSearch" class="hist-filter-input"
                            placeholder="🔍 Search reference, method, remarks..."
                            style="flex:1;min-width:200px;">
                        <select id="histStatusFilter" class="hist-filter-input">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                            <option value="pending_verification">Awaiting Review</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select id="histTypeFilter" class="hist-filter-input">
                            <option value="">All Types</option>
                            <option value="rent">Rent</option>
                            <option value="deposit">Deposit</option>
                            <option value="penalty">Penalty</option>
                        </select>
                    </div>

                    <!-- Table -->
                    <div style="overflow-x:auto;padding:0 20px 4px;">
                        <table style="width:100%;border-collapse:collapse;" id="histTable">
                            <thead>
                                <tr>
                                    <th class="hist-th">#</th>
                                    <th class="hist-th">Type</th>
                                    <th class="hist-th">Due Date</th>
                                    <th class="hist-th">Date Paid</th>
                                    <th class="hist-th">Amount</th>
                                    <th class="hist-th">Method</th>
                                    <th class="hist-th">Reference No.</th>
                                    <th class="hist-th">Proof</th>
                                    <th class="hist-th">Status</th>
                                    <th class="hist-th">Remarks</th>
                                    <th class="hist-th">Action</th>
                                </tr>
                            </thead>
                            <tbody id="histTableBody">
                                <tr>
                                    <td colspan="11" style="text-align:center;padding:40px;color:#a0aec0;">
                                        No payment records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid #e2e8f0;font-size:.82rem;color:#718096;flex-wrap:wrap;gap:8px;">
                        <span id="histRowCount">0 transaction(s)</span>
                        <span>Total paid: <strong style="color:#8d0b41;" id="histFooterTotal">₱0.00</strong></span>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Complaint Modal Script -->
    <script>
        document.getElementById('complaintModal').addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            this.querySelector('#complaint_id').value = btn.dataset.id;
            this.querySelector('.modal-title').innerHTML =
                '<i class="bi bi-tools"></i> Respond to: ' + btn.dataset.title;
        });
    </script>

    <!-- Remove Complaint Script -->
    <script>
        document.querySelectorAll('.remove-complaint-btn').forEach(button => {
            button.addEventListener('click', function () {
                const complaintId = this.dataset.id;
                if (!confirm("Are you sure you want to remove this request?")) return;

                fetch('maintenance-delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'complaint_id=' + complaintId
                })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        location.reload();
                    } else {
                        alert("Failed to delete: " + response);
                    }
                })
                .catch(error => alert("Error: " + error));
            });
        });
    </script>

    <!-- Renewal / Termination Request Script -->
    <script>
        document.querySelectorAll('.renewal-btn, .termination-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const leaseId = this.dataset.leaseId;
                const type    = this.dataset.type;
                const reason  = this.dataset.reason || '';
                const date    = this.dataset.date   || '';

                document.getElementById('reqLeaseId').value = leaseId;
                document.getElementById('reqType').value    = type;

                const titleEl = document.getElementById('requestModalTitle');
                if (type === 'termination') {
                    titleEl.innerHTML = '<i class="bi bi-file-earmark-x-fill"></i> Termination Request';
                } else {
                    titleEl.innerHTML = '<i class="bi bi-arrow-repeat"></i> Renewal Request';
                }

                const termSection  = document.getElementById('terminationReasonSection');
                const renewSection = document.getElementById('renewalInfoSection');

                if (type === 'termination') {
                    termSection.style.display  = 'block';
                    renewSection.style.display = 'none';
                    document.getElementById('terminationReasonText').textContent = reason || 'No reason provided.';
                    document.getElementById('terminationDateText').textContent   = date ? 'Submitted: ' + date : '';
                } else {
                    termSection.style.display  = 'none';
                    renewSection.style.display = 'block';
                }

                new bootstrap.Modal(document.getElementById('requestModal')).show();
            });
        });

        document.getElementById("approveBtn").addEventListener("click", () => sendRequest("approved"));
        document.getElementById("rejectBtn").addEventListener("click",  () => sendRequest("rejected"));

        function sendRequest(status) {
            const leaseId    = document.getElementById("reqLeaseId").value;
            const type       = document.getElementById("reqType").value;
            const approveBtn = document.getElementById("approveBtn");
            const rejectBtn  = document.getElementById("rejectBtn");

            approveBtn.disabled = true;
            rejectBtn.disabled  = true;
            approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
            rejectBtn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

            fetch("update-request.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `lease_id=${leaseId}&type=${type}&status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();

                approveBtn.disabled = false;
                rejectBtn.disabled  = false;
                approveBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Approve';
                rejectBtn.innerHTML  = '<i class="bi bi-x-circle-fill"></i> Reject';

                if (data.success) {
                    let icon = 'success', title = 'Success!', text = data.message;

                    if      (type === 'termination' && status === 'approved') { title = 'Termination Approved'; text = 'The lease has been terminated and the listing is now available again.'; }
                    else if (type === 'termination' && status === 'rejected') { icon = 'info'; title = 'Termination Rejected'; text = 'The termination request has been rejected. The lease remains active.'; }
                    else if (type === 'renewal'     && status === 'approved') { title = 'Renewal Approved'; text = 'The lease renewal has been approved successfully.'; }
                    else if (type === 'renewal'     && status === 'rejected') { icon = 'info'; title = 'Renewal Rejected'; text = 'The renewal request has been rejected.'; }

                    Swal.fire({ icon, title, text, confirmButtonColor: '#8d0b41' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Failed',
                        text: data.message || 'Something went wrong. Please try again.',
                        confirmButtonColor: '#8d0b41'
                    });
                }
            })
            .catch(() => {
                approveBtn.disabled = false;
                rejectBtn.disabled  = false;
                approveBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Approve';
                rejectBtn.innerHTML  = '<i class="bi bi-x-circle-fill"></i> Reject';
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect. Please check your internet connection.', confirmButtonColor: '#8d0b41' });
            });
        }
    </script>

    <!-- ============================= -->
    <!-- PAYMENT HISTORY MODAL SCRIPT  -->
    <!-- ============================= -->
    <script>
        const paymentHistoryData = <?= json_encode($paymentHistoryMap) ?>;

        function fmtDate(d) {
            if (!d) return '—';
            const dt = new Date(d);
            return isNaN(dt.getTime()) ? '—' : dt.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
        }
        function fmtAmt(a) {
            return '₱' + parseFloat(a || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        }
        function daysLate(dueDateStr) {
            if (!dueDateStr) return null;
            const due   = new Date(dueDateStr);
            const today = new Date();
            today.setHours(0,0,0,0);
            due.setHours(0,0,0,0);
            if (today <= due) return null;
            return Math.floor((today - due) / 86400000);
        }
        function getHistStatusBadge(s) {
            const map = {
                paid:                 ['hist-badge-paid',    'bi-check-circle-fill',       'Paid'],
                partial:              ['hist-badge-partial', 'bi-dash-circle-fill',        'Partial'],
                pending:              ['hist-badge-pending', 'bi-clock-fill',              'Pending'],
                overdue:              ['hist-badge-overdue', 'bi-exclamation-circle-fill', 'Overdue'],
                pending_verification: ['hist-badge-verify',  'bi-hourglass-split',         'Awaiting Review'],
                rejected:             ['hist-badge-rejected','bi-x-circle-fill',           'Rejected']
            };
            const [cls, ico, label] = map[s] || ['hist-badge-pending', 'bi-question-circle', s || 'Unknown'];
            return `<span class="${cls}"><i class="bi ${ico}"></i>${label}</span>`;
        }
        function getHistTypePill(t) {
            const map = {
                rent:    ['hist-type-rent',    'bi-house-fill',                'Rent'],
                deposit: ['hist-type-deposit', 'bi-safe2-fill',               'Deposit'],
                penalty: ['hist-type-penalty', 'bi-exclamation-triangle-fill','Penalty']
            };
            const [cls, ico, label] = map[t] || ['hist-type-other', 'bi-tag-fill', t || 'Other'];
            return `<span class="${cls}"><i class="bi ${ico}"></i>${label}</span>`;
        }
        function getMethodPill(m) {
            if (!m) return '<span style="color:#a0aec0;">—</span>';
            return `<span style="background:#f1f5f9;color:#4a5568;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                        <i class="bi bi-credit-card"></i>${m.replace(/_/g,' ')}
                    </span>`;
        }

        let histAllRows = [];

        function renderHistTable(rows) {
            const tbody = document.getElementById('histTableBody');
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:50px;color:#a0aec0;">
                    <i class="bi bi-receipt" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                    No payment records found.</td></tr>`;
                document.getElementById('histRowCount').textContent = '0 transaction(s)';
                return;
            }

            tbody.innerHTML = rows.map((p, i) => {
                const s = (p.status || '').toLowerCase();
                const isOverdue = s === 'overdue';
                const isVerify  = s === 'pending_verification';
                const dl        = isOverdue ? daysLate(p.due_date) : null;
                const rowCls    = isOverdue ? 'hist-tr hist-tr-overdue' : (isVerify ? 'hist-tr hist-tr-pending' : 'hist-tr');

                const proofHtml = p.proof_path
                    ? `<a href="../uploads/${p.proof_path}" target="_blank" style="color:#0d6efd;font-size:.76rem;"><i class="bi bi-file-earmark-check"></i> View</a>`
                    : '<span style="color:#a0aec0;">—</span>';

                let actionHtml = '<span style="color:#a0aec0;">—</span>';
                if (isVerify) {
                    actionHtml = `
                        <div style="display:flex;gap:5px;align-items:center;">
                            <button class="approve-pay-btn" onclick="reviewPayment(${p.id},'approved')">
                                <i class="bi bi-check2"></i> Approve
                            </button>
                            <button class="reject-pay-btn" onclick="reviewPayment(${p.id},'rejected')">
                                <i class="bi bi-x"></i> Reject
                            </button>
                        </div>`;
                }

                const dueDateHtml = p.due_date
                    ? fmtDate(p.due_date) + (dl !== null ? `<br><span class="days-late-chip"><i class="bi bi-clock-history"></i>${dl}d late</span>` : '')
                    : '—';

                return `<tr class="${rowCls}">
                    <td class="hist-td" style="color:#a0aec0;font-size:.78rem;">${i+1}</td>
                    <td class="hist-td">${getHistTypePill((p.payment_type||'').toLowerCase())}</td>
                    <td class="hist-td">${dueDateHtml}</td>
                    <td class="hist-td">${p.paid_date ? fmtDate(p.paid_date) : '<span style="color:#a0aec0;font-size:.8rem;">Not yet paid</span>'}</td>
                    <td class="hist-td" style="font-weight:700;color:#2d3748;">${p.amount !== null && p.amount !== undefined ? fmtAmt(p.amount) : '<span style="color:#a0aec0;">—</span>'}</td>
                    <td class="hist-td">${getMethodPill(p.payment_method)}</td>
                    <td class="hist-td">${p.reference_no ? `<span style="font-family:monospace;font-size:.78rem;background:#f1f5f9;padding:3px 8px;border-radius:5px;">${p.reference_no}</span>` : '<span style="color:#a0aec0;">—</span>'}</td>
                    <td class="hist-td">${proofHtml}</td>
                    <td class="hist-td">${getHistStatusBadge(s)}</td>
                    <td class="hist-td" style="max-width:140px;font-size:.78rem;color:#718096;white-space:normal;">${p.remarks || '<span style="color:#a0aec0;">—</span>'}</td>
                    <td class="hist-td">${actionHtml}</td>
                </tr>`;
            }).join('');

            document.getElementById('histRowCount').textContent = rows.length + ' transaction(s)';
        }

        function filterHist() {
            const q      = document.getElementById('histSearch').value.toLowerCase();
            const status = document.getElementById('histStatusFilter').value.toLowerCase();
            const type   = document.getElementById('histTypeFilter').value.toLowerCase();

            const filtered = histAllRows.filter(p => {
                const search = [p.payment_type||'', p.payment_method||'', p.reference_no||'', p.status||'', p.remarks||''].join(' ').toLowerCase();
                return (!q      || search.includes(q))
                    && (!status || (p.status||'').toLowerCase() === status)
                    && (!type   || (p.payment_type||'').toLowerCase() === type);
            });

            renderHistTable(filtered);
        }

        /* ── Approve / Reject payment ── */
        function reviewPayment(paymentId, action) {
            const label = action === 'approved' ? 'Approve' : 'Reject';
            Swal.fire({
                title: label + ' this payment?',
                text: action === 'approved'
                    ? 'This will mark the payment as Paid and update the record.'
                    : 'This will reject the tenant\'s submitted payment.',
                icon: action === 'approved' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'approved' ? '#198754' : '#dc3545',
                cancelButtonColor:  '#6c757d',
                confirmButtonText:  action === 'approved' ? 'Yes, Approve' : 'Yes, Reject'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch('review-payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `payment_id=${paymentId}&action=${action}`
                })
                .then(r => r.text())
                .then(raw => {
                    let data;
                    try { data = JSON.parse(raw); }
                    catch { Swal.fire({ icon:'error', title:'Error', text:'Unexpected server response.', confirmButtonColor:'#8d0b41' }); return; }

                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Done!' : 'Failed',
                        text: data.success
                            ? (action === 'approved' ? 'Payment approved and marked as Paid.' : 'Payment has been rejected.')
                            : (data.message || 'Something went wrong.'),
                        confirmButtonColor: '#8d0b41'
                    }).then(() => { if (data.success) location.reload(); });
                })
                .catch(err => Swal.fire({ icon:'error', title:'Network Error', text: err.message, confirmButtonColor:'#8d0b41' }));
            });
        }

        /* ── Open Modal ── */
        let defaultFilterStatus = '';

        document.querySelectorAll('.view-history-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const tenantId   = this.dataset.tenantId;
                const tenantName = this.dataset.tenantName;
                const property   = this.dataset.property;
                const rentAmt    = parseFloat(this.dataset.amount || 0);
                const records    = paymentHistoryData[tenantId] || [];
                defaultFilterStatus = this.dataset.filterStatus || '';

                histAllRows = records;

                document.getElementById('histModalTenantName').textContent = tenantName;
                document.getElementById('histModalProperty').textContent   = property;
                document.getElementById('histModalRent').textContent       = fmtAmt(rentAmt);

                const totalPaid    = records.reduce((s,p) => ['paid','partial'].includes((p.status||'').toLowerCase()) ? s+parseFloat(p.amount||0) : s, 0);
                const paidCount    = records.filter(p => (p.status||'').toLowerCase() === 'paid').length;
                const pendingCount = records.filter(p => (p.status||'').toLowerCase() === 'pending').length;
                const overdueCount = records.filter(p => (p.status||'').toLowerCase() === 'overdue').length;
                const verifyCount  = records.filter(p => (p.status||'').toLowerCase() === 'pending_verification').length;
                const lastPaidRec  = records.find(p => p.paid_date);

                document.getElementById('histTotalPaid').textContent    = fmtAmt(totalPaid);
                document.getElementById('histTotalCount').textContent   = records.length;
                document.getElementById('histPaidPending').textContent  = paidCount + ' paid · ' + pendingCount + ' pending';
                document.getElementById('histOverdueCount').textContent = overdueCount;
                document.getElementById('histVerifyCount').textContent  = verifyCount;
                document.getElementById('histLastPayment').textContent  = lastPaidRec ? fmtDate(lastPaidRec.paid_date) : '—';
                document.getElementById('histFooterTotal').textContent  = fmtAmt(totalPaid);

                // Reset / pre-set filters
                document.getElementById('histSearch').value       = '';
                document.getElementById('histStatusFilter').value = defaultFilterStatus;
                document.getElementById('histTypeFilter').value   = '';

                if (defaultFilterStatus) {
                    filterHist();
                } else {
                    renderHistTable(records);
                }

                new bootstrap.Modal(document.getElementById('paymentHistoryModal')).show();
            });
        });

        document.getElementById('histSearch').addEventListener('input',        filterHist);
        document.getElementById('histStatusFilter').addEventListener('change', filterHist);
        document.getElementById('histTypeFilter').addEventListener('change',   filterHist);
    </script>

</body>
</html>