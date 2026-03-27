<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

/* =========================
   FETCH ACTIVE LEASE
========================= */
$leaseSql = "
    SELECT le.ID AS lease_id, le.start_date, le.end_date, le.status,
           le.rent_due_day,
           ls.listingName, ls.price
    FROM leasetbl le
    JOIN listingtbl ls ON le.listing_id = ls.ID
    WHERE le.tenant_id = ? AND le.status = 'active'
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

/* =========================
   AUTO-GENERATE OVERDUE ROWS
========================= */
if ($lease) {
    $leaseStart = new DateTime($lease['start_date']);
    $today      = new DateTime();
    $cursor     = new DateTime($leaseStart->format('Y-m-01'));

    while ($cursor <= $today) {

        if (!empty($lease['rent_due_day'])) {
            $day     = (int)$lease['rent_due_day'];
            $maxDay  = (int)$cursor->format('t');
            $day     = min($day, $maxDay);
            $dueDate = $cursor->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
        } else {
            $dueDate = (clone $cursor)->modify('last day of this month')->format('Y-m-d');
        }

        if (!$dueDate || $dueDate === '0000-00-00') {
            $cursor->modify('+1 month');
            continue;
        }

        $dueDateObj      = new DateTime($dueDate);
        $leaseStartMonth = $leaseStart->format('Y-m');
        $cursorMonth     = $cursor->format('Y-m');

        // Skip: due date hasn't passed yet OR this is the lease-start month (grace period)
        if ($dueDateObj < $today && $cursorMonth !== $leaseStartMonth) {

            // Only insert overdue if NO payment row exists at all for this due_date
            $chkStmt = $conn->prepare("
                SELECT id FROM paymentstbl
                WHERE lease_id = ? AND tenant_id = ? AND payment_type = 'rent' AND due_date = ?
                LIMIT 1
            ");
            $chkStmt->bind_param("iis", $lease['lease_id'], $tenant_id, $dueDate);
            $chkStmt->execute();

            if ($chkStmt->get_result()->num_rows == 0) {
                $insStmt = $conn->prepare("
                    INSERT IGNORE INTO paymentstbl
                    (lease_id, tenant_id, landlord_id, payment_type, amount, due_date, status, created_at)
                    SELECT ?, ?, le.landlord_id, 'rent', 0, ?, 'overdue', NOW()
                    FROM leasetbl le WHERE le.ID = ?
                ");
                $insStmt->bind_param("iisi", $lease['lease_id'], $tenant_id, $dueDate, $lease['lease_id']);
                $insStmt->execute();
            }
        }

        $cursor->modify('+1 month');
    }
}

/* =========================
   FETCH PAYMENT HISTORY
========================= */
$payments  = [];
$totalPaid = 0;

if ($lease) {
    $paymentSql = "
        SELECT p.id, p.payment_type, p.amount, p.due_date, p.paid_date,
               p.payment_method, p.status, p.reference_no, p.remarks,
               p.proof_path, p.created_at
        FROM paymentstbl p
        WHERE p.lease_id = ? AND p.tenant_id = ?
        ORDER BY p.due_date DESC, p.created_at DESC
    ";
    $payStmt = $conn->prepare($paymentSql);
    $payStmt->bind_param("ii", $lease['lease_id'], $tenant_id);
    $payStmt->execute();
    $payments = $payStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($payments as $p) {
        if (in_array(strtolower($p['status']), ['paid', 'partial'])) {
            $totalPaid += (float)($p['amount'] ?? 0);
        }
    }
}

/* =========================
   BUILD MONTHLY PAYMENT MAP
   Sum of ALL confirmed + pending amounts per due month
   Used to compute running balance per month
========================= */
$monthlyPaymentMap = [];
foreach ($payments as $p) {
    $status = strtolower($p['status'] ?? '');
    // Count paid, partial, and pending_verification toward the balance
    if ($p['due_date'] && $p['due_date'] !== '0000-00-00'
        && in_array($status, ['paid', 'partial', 'pending_verification'])) {
        $mk = date('Y-m', strtotime($p['due_date']));
        if (!isset($monthlyPaymentMap[$mk])) $monthlyPaymentMap[$mk] = 0;
        $monthlyPaymentMap[$mk] += (float)($p['amount'] ?? 0);
    }
}

/* =========================
   HELPERS
========================= */
function getPaymentStatusBadge($status) {
    return match (strtolower($status ?? '')) {
        'paid'                 => '<span class="badge badge-paid"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>',
        'partial'              => '<span class="badge badge-partial"><i class="bi bi-dash-circle-fill me-1"></i>Partial</span>',
        'pending'              => '<span class="badge badge-pending"><i class="bi bi-clock-fill me-1"></i>Pending</span>',
        'overdue'              => '<span class="badge badge-overdue"><i class="bi bi-exclamation-circle-fill me-1"></i>Overdue</span>',
        'pending_verification' => '<span class="badge badge-verify"><i class="bi bi-hourglass-split me-1"></i>Awaiting Approval</span>',
        'rejected'             => '<span class="badge badge-rejected"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>',
        default                => '<span class="badge badge-secondary">'.htmlspecialchars($status ?? 'N/A').'</span>'
    };
}

function getPaymentTypeLabel($type) {
    return match (strtolower($type ?? '')) {
        'rent'    => '<span class="type-pill type-rent"><i class="bi bi-house-fill"></i> Rent</span>',
        'deposit' => '<span class="type-pill type-deposit"><i class="bi bi-safe2-fill"></i> Deposit</span>',
        'penalty' => '<span class="type-pill type-penalty"><i class="bi bi-exclamation-triangle-fill"></i> Penalty</span>',
        default   => '<span class="type-pill type-other"><i class="bi bi-tag-fill"></i> '.htmlspecialchars($type ?? 'Other').'</span>'
    };
}

function getDaysLate($dueDate) {
    if (!$dueDate) return null;
    $due   = new DateTime($dueDate);
    $today = new DateTime();
    if ($today <= $due) return null;
    return (int)$today->diff($due)->days;
}

function getDisplayDueDate($p, $lease) {
    if (!empty($p['due_date']) && $p['due_date'] !== '0000-00-00') {
        return $p['due_date'];
    }
    if (!empty($lease['rent_due_day'])) {
        $ref    = $p['created_at'] ?? date('Y-m-d');
        $day    = (int)$lease['rent_due_day'];
        $maxDay = (int)date('t', strtotime($ref));
        $day    = min($day, $maxDay);
        return date('Y-m-', strtotime($ref)) . str_pad($day, 2, '0', STR_PAD_LEFT);
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>

    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary:       #8d0b41;
            --primary-dark:  #6f0833;
            --primary-light: rgba(141, 11, 65, 0.08);
            --surface:       #ffffff;
            --bg:            #f5f6f8;
            --border:        #e2e8f0;
            --text-main:     #25343F;
            --text-muted:    #718096;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: "Montserrat", sans-serif; background: var(--bg); font-size: 14px; color: var(--text-main); }
        .container { margin-top: 130px; margin-bottom: 60px; }

        .page-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--surface); border: 1px solid var(--border); border-radius: 10px;
            padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: var(--text-main);
            text-decoration: none; transition: all 0.2s ease;
        }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); box-shadow: 0 2px 8px rgba(141,11,65,0.12); }
        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px; margin: 0; }
        .page-title i { color: var(--primary); }

        .submit-pay-btn {
            margin-left: auto; display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff; border: none; border-radius: 10px; padding: 10px 20px;
            font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
            font-family: "Montserrat", sans-serif;
        }
        .submit-pay-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(141,11,65,0.35); }

        .apt-info-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px; padding: 16px 22px; margin-bottom: 22px;
            display: flex; align-items: center; gap: 14px; color: #fff; flex-wrap: wrap;
        }
        .apt-info-bar > i { font-size: 1.6rem; opacity: 0.85; }
        .apt-info-name    { font-weight: 700; font-size: 1rem; }
        .apt-info-sub     { font-size: 0.82rem; opacity: 0.8; margin-top: 2px; }
        .apt-info-price   { margin-left: auto; text-align: right; }
        .apt-info-price .label { font-size: 0.75rem; opacity: 0.75; }
        .apt-info-price .value { font-size: 1.3rem; font-weight: 700; }

        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card {
            background: var(--surface); border-radius: 14px; padding: 18px 20px;
            border: 1px solid var(--border); box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            position: relative; overflow: hidden; transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .summary-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,0.09); }
        .summary-card::before {
            content: ""; position: absolute; top: 0; left: 0;
            width: 5px; height: 100%; border-radius: 14px 0 0 14px;
        }
        .summary-card.green::before  { background: linear-gradient(to bottom, #56ab2f, #a8e063); }
        .summary-card.blue::before   { background: linear-gradient(to bottom, #2196f3, #64b5f6); }
        .summary-card.red::before    { background: linear-gradient(to bottom, #f00000, #dc281e); }
        .summary-card.gold::before   { background: linear-gradient(to bottom, #f7971e, #ffd200); }
        .summary-card.purple::before { background: linear-gradient(to bottom, #7c3aed, #a78bfa); }
        .summary-icon { position: absolute; top: 12px; right: 14px; font-size: 1.8rem; opacity: 0.25; color: #8d0b41; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
        .summary-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 6px; }
        .summary-value { font-size: 1.4rem; font-weight: 700; color: var(--text-main); line-height: 1.2; }
        .summary-sub   { font-size: 0.76rem; color: var(--text-muted); margin-top: 3px; }

        .table-section { background: var(--surface); border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 6px 18px rgba(0,0,0,0.06); overflow: hidden; }
        .table-section-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 2px solid var(--border); flex-wrap: wrap; gap: 12px; }
        .section-title { font-size: 1rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 8px; margin: 0; }
        .section-title i { color: var(--primary); }

        .filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-input {
            border: 1px solid var(--border); border-radius: 8px; padding: 7px 12px;
            font-size: 0.83rem; font-family: "Montserrat", sans-serif; color: var(--text-main);
            outline: none; transition: border-color 0.2s; background: #fff;
        }
        .filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 0.15rem rgba(141,11,65,0.18); }

        .payments-table { width: 100%; border-collapse: collapse; }
        .payments-table thead th {
            background: #f8fafc; color: var(--text-muted); text-transform: uppercase;
            font-size: 0.7rem; font-weight: 600; letter-spacing: 0.06em;
            padding: 13px 16px; border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        .payments-table tbody tr { border-bottom: 1px solid #f0f4f8; transition: background 0.15s ease; }
        .payments-table tbody tr:hover { background: #fffafb; }
        .payments-table tbody tr:last-child { border-bottom: none; }
        .payments-table tbody tr.row-overdue { background: #fff8f8; }
        .payments-table tbody tr.row-overdue:hover { background: #fff0f0; }
        .payments-table tbody tr.row-partial { background: #f0f7ff; }
        .payments-table tbody tr.row-partial:hover { background: #e8f3ff; }
        .payments-table tbody tr.row-pending-verify { background: #fffdf0; }
        .payments-table tbody tr.row-rejected { background: #fff5f5; }
        .payments-table td { padding: 14px 16px; color: #4a5568; font-size: 0.87rem; vertical-align: middle; }
        .payments-table td.amount-col { font-weight: 700; font-size: 0.95rem; color: var(--text-main); }

        .balance-fully-paid  { display: inline-flex; align-items: center; gap: 5px; color: #198754; font-weight: 700; font-size: 0.82rem; }
        .balance-remaining   { display: inline-flex; align-items: center; gap: 5px; color: #dc3545; font-weight: 700; font-size: 0.82rem; }
        .balance-advance     { display: inline-flex; align-items: center; gap: 5px; color: #0d6efd; font-weight: 700; font-size: 0.82rem; }
        .balance-bar-wrap    { margin-top: 5px; background: #e2e8f0; border-radius: 20px; height: 5px; width: 100px; overflow: hidden; }
        .balance-bar-fill    { height: 100%; border-radius: 20px; background: linear-gradient(90deg, #56ab2f, #a8e063); transition: width 0.4s ease; }

        .days-late-chip {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb;
            border-radius: 20px; padding: 2px 9px; font-size: 0.7rem; font-weight: 700; margin-top: 4px;
        }

        .badge { font-family: "Montserrat", sans-serif; font-weight: 500; font-size: 0.74rem; letter-spacing: 0.2px; padding: 5px 10px; border-radius: 20px; display: inline-flex; align-items: center; }
        .badge-paid      { background: #d4edda; color: #155724; }
        .badge-partial   { background: #cce5ff; color: #004085; }
        .badge-pending   { background: #fff3cd; color: #856404; }
        .badge-overdue   { background: #f8d7da; color: #721c24; }
        .badge-verify    { background: #e0d7ff; color: #4c1d95; }
        .badge-rejected  { background: #f8d7da; color: #721c24; }
        .badge-secondary { background: #e2e8f0; color: #4a5568; }

        .type-pill { display: inline-flex; align-items: center; gap: 4px; font-size: 0.76rem; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .type-rent    { background: var(--primary-light); color: var(--primary); }
        .type-deposit { background: #e8f5e9; color: #2e7d32; }
        .type-penalty { background: #fff3e0; color: #e65100; }
        .type-other   { background: #e2e8f0; color: #4a5568; }

        .method-pill { display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; color: #4a5568; border-radius: 20px; padding: 4px 10px; font-size: 0.76rem; font-weight: 600; text-transform: capitalize; }
        .ref-code { font-family: "Courier New", monospace; font-size: 0.78rem; background: #f1f5f9; padding: 3px 8px; border-radius: 5px; color: #4a5568; }

        .pay-now-btn {
            display: inline-flex; align-items: center; gap: 5px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff; border: none; border-radius: 8px; padding: 6px 14px;
            font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
            font-family: "Montserrat", sans-serif;
        }
        .pay-now-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(141,11,65,0.3); }

        .table-footer { display: flex; justify-content: space-between; align-items: center; padding: 13px 18px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 8px; font-size: 0.82rem; color: var(--text-muted); }

        .empty-state { text-align: center; padding: 55px 20px; }
        .empty-icon-wrap { width: 76px; height: 76px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .empty-icon-wrap i { font-size: 1.9rem; color: var(--primary); }
        .empty-state h5 { font-weight: 600; color: var(--text-main); margin-bottom: 5px; }
        .empty-state p  { color: var(--text-muted); font-size: 0.88rem; }

        /* Modal */
        .themed-modal .modal-content { border-radius: 14px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.15); border: none; }
        .themed-modal .modal-header  { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border-bottom: none; padding: 18px 24px; }
        .themed-modal .modal-title   { font-weight: 700; font-size: 1.05rem; color: #fff; display: flex; align-items: center; gap: 8px; }
        .themed-modal .btn-close     { filter: invert(1); opacity: 0.85; }
        .themed-modal .modal-body    { padding: 24px; background: #fff; }
        .themed-modal .modal-footer  { border-top: 1px solid #f0f0f0; background: #fafafa; padding: 16px 24px; gap: 10px; }
        .themed-modal .form-label    { font-weight: 600; font-size: 0.8rem; color: #718096; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
        .themed-modal .form-control,
        .themed-modal .form-select   { border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 0.9rem; padding: 10px 12px; font-family: "Montserrat", sans-serif; transition: border-color 0.2s; }
        .themed-modal .form-control:focus,
        .themed-modal .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(141,11,65,0.12); }

        .modal-notice { background: #fff8f0; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.82rem; color: #78350f; display: flex; align-items: flex-start; gap: 10px; }
        .modal-notice i { font-size: 1.1rem; margin-top: 1px; flex-shrink: 0; }

        .rent-hint { background: #f0fff4; border: 1px solid #b7ebc8; border-radius: 8px; padding: 8px 14px; font-size: 0.8rem; color: #276749; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
        .partial-warning { background: #fff8f0; border: 1px solid #fbd38d; border-radius: 8px; padding: 8px 14px; font-size: 0.8rem; color: #92400e; margin-top: 6px; display: none; align-items: center; gap: 6px; }

        .modal-btn { display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 20px; font-size: 0.85rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s ease; font-family: "Montserrat", sans-serif; }
        .modal-btn-cancel  { background: #e2e8f0; color: #4a5568; }
        .modal-btn-cancel:hover { background: #cbd5e0; }
        .modal-btn-primary { background: var(--primary); color: #fff; }
        .modal-btn-primary:hover { background: var(--primary-dark); box-shadow: 0 4px 10px rgba(141,11,65,0.3); }

        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .apt-info-price { margin-left: 0; }
            .submit-pay-btn { margin-left: 0; width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <?php include '../Components/tenant-header.php'; ?>

    <div class="container">

        <div class="page-header">
            <a href="tenant-rental.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
            <h1 class="page-title"><i class="bi bi-receipt-cutoff"></i> Payment History</h1>
            <?php if ($lease): ?>
                <button class="submit-pay-btn" data-bs-toggle="modal" data-bs-target="#submitPaymentModal">
                    <i class="bi bi-send-fill"></i> Submit Payment
                </button>
            <?php endif; ?>
        </div>

        <?php if ($lease): ?>

            <div class="apt-info-bar">
                <i class="bi bi-building"></i>
                <div>
                    <div class="apt-info-name"><?= htmlspecialchars($lease['listingName']) ?></div>
                    <div class="apt-info-sub">
                        Lease: <?= date("F j, Y", strtotime($lease['start_date'])) ?>
                        &mdash; <?= date("F j, Y", strtotime($lease['end_date'])) ?>
                        <?php if (!empty($lease['rent_due_day'])): ?>
                            &bull; Due every <strong>day <?= (int)$lease['rent_due_day'] ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="apt-info-price">
                    <div class="label">Monthly Rent</div>
                    <div class="value">₱<?= number_format($lease['price'], 2) ?></div>
                </div>
            </div>

            <?php
                $paidCount     = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'paid'));
                $partialCount  = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'partial'));
                $pendingCount  = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'pending'));
                $overdueCount  = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'overdue'));
                $awaitingCount = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'pending_verification'));
                $rejectedCount = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'rejected'));
            ?>
            <div class="summary-grid">
                <div class="summary-card green">
                    <i class="bi bi-cash-coin summary-icon"></i>
                    <div class="summary-label">Total Paid</div>
                    <div class="summary-value">₱<?= number_format($totalPaid, 2) ?></div>
                    <div class="summary-sub">Across all confirmed records</div>
                </div>
                <div class="summary-card blue">
                    <i class="bi bi-calendar-check summary-icon"></i>
                    <div class="summary-label">Total Transactions</div>
                    <div class="summary-value"><?= count($payments) ?></div>
                    <div class="summary-sub"><?= $paidCount ?> paid &bull; <?= $partialCount ?> partial &bull; <?= $pendingCount ?> pending</div>
                </div>
                <div class="summary-card gold">
                    <i class="bi bi-house summary-icon"></i>
                    <div class="summary-label">Monthly Rent</div>
                    <div class="summary-value">₱<?= number_format($lease['price'], 2) ?></div>
                    <div class="summary-sub">Expected per month</div>
                </div>
                <div class="summary-card red">
                    <i class="bi bi-exclamation-circle summary-icon"></i>
                    <div class="summary-label">Overdue</div>
                    <div class="summary-value"><?= $overdueCount ?></div>
                    <div class="summary-sub">Record(s) marked overdue</div>
                </div>
                <?php if ($awaitingCount > 0): ?>
                <div class="summary-card purple">
                    <i class="bi bi-hourglass-split summary-icon"></i>
                    <div class="summary-label">Awaiting Approval</div>
                    <div class="summary-value"><?= $awaitingCount ?></div>
                    <div class="summary-sub">Submitted to landlord</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="table-section">
                <div class="table-section-header">
                    <h2 class="section-title"><i class="bi bi-list-ul"></i> All Transactions</h2>
                    <div class="filter-group">
                        <input type="text" id="searchInput" class="filter-input"
                            placeholder="🔍 Search reference, method..." style="min-width:200px;">
                        <select id="statusFilter" class="filter-input">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                            <option value="pending_verification">Awaiting Approval</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select id="typeFilter" class="filter-input">
                            <option value="">All Types</option>
                            <option value="rent">Rent</option>
                            <option value="deposit">Deposit</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($payments)): ?>
                <div style="overflow-x:auto;">
                    <table class="payments-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Due Date</th>
                                <th>Date Paid</th>
                                <th>Amount Paid</th>
                                <th>Monthly Rent</th>
                                <th>Balance</th>
                                <th>Method</th>
                                <th>Reference No.</th>
                                <th>Proof</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Recorded On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payments as $i => $p):
                            $statusLower = strtolower($p['status'] ?? '');
                            $isOverdue   = $statusLower === 'overdue';
                            $isAwait     = $statusLower === 'pending_verification';
                            $isPartial   = $statusLower === 'partial';
                            $isRejected  = $statusLower === 'rejected';
                            $isRentType  = strtolower($p['payment_type'] ?? '') === 'rent';

                            $displayDue = getDisplayDueDate($p, $lease);
                            $daysLate   = ($isOverdue && $displayDue) ? getDaysLate($displayDue) : null;

                            // ── Row highlight class ──
                            $rowClass = '';
                            if ($isOverdue)  $rowClass = 'row-overdue';
                            if ($isPartial)  $rowClass = 'row-partial';
                            if ($isAwait)    $rowClass = 'row-pending-verify';
                            if ($isRejected) $rowClass = 'row-rejected';

                            // ── Balance: use MONTHLY MAP (sum of all payments for this due month) ──
                            $monthlyRent = (float)$lease['price'];
                            $paidSoFar   = 0;
                            if ($isRentType && $displayDue && $displayDue !== '0000-00-00') {
                                $mk        = date('Y-m', strtotime($displayDue));
                                $paidSoFar = $monthlyPaymentMap[$mk] ?? 0;
                            } else {
                                $paidSoFar = (float)($p['amount'] ?? 0);
                            }
                            $balance = max(0, $monthlyRent - $paidSoFar);
                            $pctPaid = $monthlyRent > 0 ? min(100, round(($paidSoFar / $monthlyRent) * 100)) : 0;

                            // ── Effective display status ──
                            // For rent rows: derive what the status SHOULD show based on cumulative paid
                            // This makes partial show correctly even if DB still says pending_verification
                            $displayStatus = $statusLower;
                            if ($isRentType && in_array($statusLower, ['paid', 'partial'])) {
                                if ($balance <= 0) {
                                    $displayStatus = 'paid';
                                } else {
                                    $displayStatus = 'partial';
                                }
                            }

                            $searchStr = strtolower(
                                ($p['payment_type']   ?? '') . ' ' .
                                ($p['paid_date']      ?? '') . ' ' .
                                ($displayDue          ?? '') . ' ' .
                                ($p['payment_method'] ?? '') . ' ' .
                                ($p['reference_no']   ?? '') . ' ' .
                                ($statusLower) . ' ' .
                                ($p['remarks']        ?? '')
                            );
                        ?>
                            <tr class="<?= $rowClass ?>"
                                data-status="<?= htmlspecialchars($statusLower) ?>"
                                data-type="<?= strtolower($p['payment_type'] ?? '') ?>"
                                data-search="<?= htmlspecialchars($searchStr) ?>">

                                <td class="text-muted" style="font-size:0.8rem;"><?= $i + 1 ?></td>

                                <td><?= getPaymentTypeLabel($p['payment_type']) ?></td>

                                <!-- Due Date -->
                                <td>
                                    <?php if ($displayDue && $displayDue !== '0000-00-00'): ?>
                                        <?= date("M j, Y", strtotime($displayDue)) ?>
                                        <?php if ($daysLate !== null): ?>
                                            <br>
                                            <span class="days-late-chip">
                                                <i class="bi bi-clock-history"></i>
                                                <?= $daysLate ?> day<?= $daysLate != 1 ? 's' : '' ?> late
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date Paid -->
                                <td>
                                    <?= $p['paid_date']
                                        ? date("M j, Y", strtotime($p['paid_date']))
                                        : '<span class="text-muted" style="font-size:0.82rem;">Not yet paid</span>' ?>
                                </td>

                                <!-- Amount Paid -->
                                <td class="amount-col">
                                    <?= ($p['amount'] !== null && (float)$p['amount'] > 0)
                                        ? '₱' . number_format($p['amount'], 2)
                                        : '<span class="text-muted">—</span>' ?>
                                </td>

                                <!-- Monthly Rent -->
                                <td style="color:#718096; font-size:0.85rem;">
                                    <?= $isRentType ? '₱' . number_format($monthlyRent, 2) : '<span class="text-muted">—</span>' ?>
                                </td>

                                <!-- Balance (cumulative for the month) -->
                                <td>
                                    <?php if ($isRentType && in_array($statusLower, ['paid', 'partial'])): ?>
                                        <?php if ($balance <= 0): ?>
                                            <span class="balance-fully-paid">
                                                <i class="bi bi-check-circle-fill"></i> Fully Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="balance-remaining">
                                                <i class="bi bi-exclamation-circle-fill"></i>
                                                ₱<?= number_format($balance, 2) ?> remaining
                                            </span>
                                            <div class="balance-bar-wrap">
                                                <div class="balance-bar-fill" style="width:<?= $pctPaid ?>%;"></div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif ($isRentType && $isAwait): ?>
                                        <?php if ($balance <= 0): ?>
                                            <span class="balance-fully-paid">
                                                <i class="bi bi-clock"></i> Full — Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="balance-remaining">
                                                <i class="bi bi-exclamation-circle-fill"></i>
                                                ₱<?= number_format($balance, 2) ?> remaining
                                            </span>
                                            <div class="balance-bar-wrap">
                                                <div class="balance-bar-fill" style="width:<?= $pctPaid ?>%;"></div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif ($isRentType && $isOverdue): ?>
                                        <span class="balance-remaining">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                            ₱<?= number_format($monthlyRent, 2) ?> unpaid
                                        </span>

                                    <?php elseif ($isRentType && $isRejected): ?>
                                        <span class="balance-remaining">
                                            <i class="bi bi-x-circle-fill"></i> Rejected
                                        </span>

                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Method -->
                                <td>
                                    <?php if (!empty($p['payment_method'])): ?>
                                        <span class="method-pill">
                                            <i class="bi bi-credit-card"></i>
                                            <?= htmlspecialchars(str_replace('_', ' ', $p['payment_method'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Reference No. -->
                                <td>
                                    <?= !empty($p['reference_no'])
                                        ? '<span class="ref-code">'.htmlspecialchars($p['reference_no']).'</span>'
                                        : '<span class="text-muted">—</span>' ?>
                                </td>

                                <!-- Proof -->
                                <td>
                                    <?php if (!empty($p['proof_path'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($p['proof_path']) ?>"
                                            target="_blank"
                                            style="color:#0d6efd;font-size:0.78rem;display:inline-flex;align-items:center;gap:4px;">
                                            <i class="bi bi-file-earmark-check"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status — uses displayStatus so partial shows correctly -->
                                <td><?= getPaymentStatusBadge($displayStatus) ?></td>

                                <!-- Remarks -->
                                <td style="max-width:160px; font-size:0.8rem; color:var(--text-muted);">
                                    <?= !empty($p['remarks'])
                                        ? htmlspecialchars($p['remarks'])
                                        : '<span class="text-muted">—</span>' ?>
                                </td>

                                <!-- Recorded On -->
                                <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= $p['created_at'] ? date("M j, Y", strtotime($p['created_at'])) : '—' ?>
                                </td>

                                <!-- Action -->
                                <td>
                                    <?php if ($isOverdue): ?>
                                        <button class="pay-now-btn trigger-submit-modal"
                                            data-payment-id="<?= $p['id'] ?>"
                                            data-due-date="<?= htmlspecialchars($displayDue ?? '') ?>"
                                            data-expected="<?= number_format($monthlyRent, 2, '.', '') ?>">
                                            <i class="bi bi-send-fill"></i> Pay Now
                                        </button>
                                    <?php elseif ($isAwait): ?>
                                        <span style="font-size:0.78rem;color:#7c3aed;font-weight:600;">
                                            <i class="bi bi-hourglass-split"></i> Awaiting landlord
                                        </span>
                                    <?php elseif ($isRejected): ?>
                                        <button class="pay-now-btn trigger-submit-modal"
                                            data-payment-id="<?= $p['id'] ?>"
                                            data-due-date="<?= htmlspecialchars($displayDue ?? '') ?>"
                                            data-expected="<?= number_format($monthlyRent, 2, '.', '') ?>">
                                            <i class="bi bi-arrow-clockwise"></i> Resubmit
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="rowCount"><?= count($payments) ?> transaction(s) found</span>
                    <span>Total confirmed paid: <strong style="color:var(--primary);">₱<?= number_format($totalPaid, 2) ?></strong></span>
                </div>

                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon-wrap"><i class="bi bi-receipt"></i></div>
                    <h5>No Payment Records Yet</h5>
                    <p>Your payment history will appear here. Use the <strong>Submit Payment</strong> button above to record a payment.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="table-section">
                <div class="empty-state">
                    <div class="empty-icon-wrap"><i class="bi bi-house-x"></i></div>
                    <h5>No Active Lease Found</h5>
                    <p>Payment history is only available when you have an active lease.</p>
                    <a href="tenant.php" class="btn mt-3 text-white"
                        style="background:var(--primary);border:none;border-radius:25px;padding:8px 22px;font-family:Montserrat,sans-serif;font-weight:500;">
                        Find Apartment
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- SUBMIT PAYMENT MODAL -->
    <?php if ($lease): ?>
    <div class="modal fade themed-modal" id="submitPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="submitPaymentForm" enctype="multipart/form-data">
                    <input type="hidden" name="lease_id"   value="<?= $lease['lease_id'] ?>">
                    <input type="hidden" name="tenant_id"  value="<?= $tenant_id ?>">
                    <input type="hidden" name="payment_id" id="modal_payment_id" value="">

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-send-fill"></i> Submit Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="modal-notice">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Your payment will be sent to your landlord for verification. Status will update once approved.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <select class="form-select" name="payment_type" id="modal_payment_type" required>
                                <option value="rent">Rent</option>
                                <option value="deposit">Deposit</option>
                                <option value="penalty">Penalty</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount Paid (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text"
                                    style="border-radius:8px 0 0 8px;border:1.5px solid #e2e8f0;background:#f8fafc;color:var(--primary);font-weight:700;">₱</span>
                                <input type="number" class="form-control" name="amount" id="modal_amount"
                                    placeholder="0.00" step="0.01" min="0.01" required
                                    style="border-radius:0 8px 8px 0;">
                            </div>
                            <div class="rent-hint" id="rentHint">
                                <i class="bi bi-info-circle"></i>
                                Monthly rent: <strong>₱<?= number_format($lease['price'], 2) ?></strong>
                            </div>
                            <div class="partial-warning" id="partialWarning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Amount is less than the monthly rent — this will be recorded as a <strong>partial payment</strong>.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date Paid</label>
                            <input type="date" class="form-control" name="paid_date" id="modal_paid_date"
                                required max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference No. <span style="color:#a0aec0;font-weight:400;text-transform:none;">(optional)</span></label>
                            <input type="text" class="form-control" name="reference_no" placeholder="e.g. GCash ref, bank transaction ID">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proof of Payment <span style="color:#a0aec0;font-weight:400;text-transform:none;">(optional, image/pdf)</span></label>
                            <input type="file" class="form-control" name="proof_file" accept="image/*,.pdf">
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Remarks <span style="color:#a0aec0;font-weight:400;text-transform:none;">(optional)</span></label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="e.g. January rent, advance payment for April..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                        <button type="submit" class="modal-btn modal-btn-primary" id="submitPayBtn">
                            <i class="bi bi-send-fill"></i> Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        const MONTHLY_RENT = <?= (float)($lease['price'] ?? 0) ?>;

        /* ── Filters ── */
        const searchInput  = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter   = document.getElementById('typeFilter');
        const rows         = document.querySelectorAll('#paymentsTable tbody tr');
        const rowCount     = document.getElementById('rowCount');

        function filterRows() {
            const q      = searchInput?.value.toLowerCase()  ?? '';
            const status = statusFilter?.value.toLowerCase() ?? '';
            const type   = typeFilter?.value.toLowerCase()   ?? '';
            let visible  = 0;
            rows.forEach(row => {
                const matchSearch = !q      || row.dataset.search.includes(q);
                const matchStatus = !status || row.dataset.status === status;
                const matchType   = !type   || row.dataset.type   === type;
                const show = matchSearch && matchStatus && matchType;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (rowCount) rowCount.textContent = visible + ' transaction(s) found';
        }

        searchInput?.addEventListener('input',   filterRows);
        statusFilter?.addEventListener('change', filterRows);
        typeFilter?.addEventListener('change',   filterRows);

        /* ── Partial warning ── */
        document.getElementById('modal_amount')?.addEventListener('input', function () {
            const val       = parseFloat(this.value) || 0;
            const payType   = document.getElementById('modal_payment_type')?.value;
            const warn      = document.getElementById('partialWarning');
            warn.style.display = (payType === 'rent' && MONTHLY_RENT > 0 && val > 0 && val < MONTHLY_RENT)
                ? 'flex' : 'none';
        });

        document.getElementById('modal_payment_type')?.addEventListener('change', function () {
            const hint = document.getElementById('rentHint');
            const warn = document.getElementById('partialWarning');
            hint.style.display = this.value === 'rent' ? 'flex' : 'none';
            if (this.value !== 'rent') warn.style.display = 'none';
        });

        /* ── Pay Now / Resubmit pre-fill ── */
        document.querySelectorAll('.trigger-submit-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('modal_payment_id').value   = this.dataset.paymentId || '';
                // Strip commas from expected amount before setting
                document.getElementById('modal_amount').value       = (this.dataset.expected || '').replace(/,/g, '');
                document.getElementById('modal_paid_date').value    = new Date().toISOString().split('T')[0];
                document.getElementById('modal_payment_type').value = 'rent';
                document.getElementById('partialWarning').style.display = 'none';
                new bootstrap.Modal(document.getElementById('submitPaymentModal')).show();
            });
        });

        /* ── Reset modal on fresh open ── */
        document.getElementById('submitPaymentModal')?.addEventListener('show.bs.modal', function (e) {
            if (!e.relatedTarget) return;
            document.getElementById('modal_payment_id').value       = '';
            document.getElementById('modal_amount').value           = '';
            document.getElementById('modal_paid_date').value        = new Date().toISOString().split('T')[0];
            document.getElementById('partialWarning').style.display = 'none';
        });

        /* ── Submit form ── */
        document.getElementById('submitPaymentForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitPayBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

            fetch('submit-payment.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.text())
            .then(raw => {
                let data;
                try { data = JSON.parse(raw); }
                catch {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit for Approval';
                    Swal.fire({ icon:'error', title:'Server Error', text: raw.substring(0, 200), confirmButtonColor:'#8d0b41' });
                    console.error(raw);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('submitPaymentModal')).hide();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit for Approval';
                Swal.fire({
                    icon:  data.success ? 'success' : 'error',
                    title: data.success ? 'Payment Submitted!' : 'Failed',
                    text:  data.success
                        ? 'Your payment has been submitted and is awaiting landlord approval.'
                        : (data.message || 'Something went wrong.'),
                    confirmButtonColor: '#8d0b41'
                }).then(() => { if (data.success) location.reload(); });
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit for Approval';
                Swal.fire({ icon:'error', title:'Network Error', text: err.message, confirmButtonColor:'#8d0b41' });
            });
        });
    </script>
</body>
</html>