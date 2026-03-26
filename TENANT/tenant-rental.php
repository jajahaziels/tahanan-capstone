<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

/* =========================
   DEFAULT VALUES
========================= */
$lease = null;
$lastPaid = 'No rent yet';
$dueDate = '-';
$expectedAmount = '0.00';
$totalPaid = '0.00';

/* =========================
   FETCH ACTIVE OR PENDING LEASE
   ✅ FIX: Also fetch 'pending' so the page renders when landlord sends a new lease
========================= */
$leaseSql = "
    SELECT ID, start_date, end_date, pdf_path, listing_id, status
    FROM leasetbl
    WHERE tenant_id = ?
      AND status IN ('active', 'pending')
    ORDER BY
        CASE status WHEN 'active' THEN 1 WHEN 'pending' THEN 2 END,
        end_date DESC
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

/* =========================
   FETCH RENT PAYMENT (ONLY IF ACTIVE LEASE EXISTS)
========================= */
if ($lease && $lease['status'] === 'active') {
    $paymentSql = "
        SELECT
            MAX(paid_date) AS last_paid,
            MIN(CASE WHEN paid_date IS NULL OR paid_date = '' THEN due_date ELSE NULL END) AS next_due,
            ls.price AS expected_amount,
            SUM(p.amount) AS total_paid
        FROM leasetbl le
        LEFT JOIN paymentstbl p ON le.ID = p.lease_id AND p.tenant_id = ?
        JOIN listingtbl ls ON le.listing_id = ls.ID
        WHERE le.ID = ?
    ";
    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->bind_param("ii", $tenant_id, $lease['ID']);
    $paymentStmt->execute();
    $payment = $paymentStmt->get_result()->fetch_assoc();

    if ($payment) {
        $lastPaid = $payment['last_paid']
            ? date("F d, Y", strtotime($payment['last_paid']))
            : 'Not yet paid';

        $dueDate = $payment['next_due']
            ? date("F d, Y", strtotime($payment['next_due']))
            : '-';

        $expectedAmount = number_format($payment['expected_amount'] ?? 0, 2);
        $totalPaid      = number_format($payment['total_paid']     ?? 0, 2);
    }
}

/* =========================
   FETCH MAINTENANCE INFO
========================= */
$maintenanceSql = "
    SELECT *
    FROM maintenance_requeststbl
    WHERE tenant_id = ?
    ORDER BY
        CASE priority
            WHEN 'Urgent' THEN 1
            WHEN 'High'   THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low'    THEN 4
        END,
        created_at DESC
";
$maintenanceStmt = $conn->prepare($maintenanceSql);
$maintenanceStmt->bind_param("i", $tenant_id);
$maintenanceStmt->execute();
$maintenanceRequests = $maintenanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   PENDING COUNTER
========================= */
$notificationCount = 0;
foreach ($maintenanceRequests as $req) {
    if ($req['status'] === 'Pending' || $req['status'] === 'In Progress') {
        $notificationCount++;
    }
}

/* =========================
   FETCH ALL LEASES WITH LANDLORD INFO
   Exclude fully terminated leases from the table
========================= */
$sql = "
SELECT
    le.ID            AS lease_id,
    le.start_date,
    le.end_date,
    le.status        AS lease_status,
    le.pdf_path,
    le.tenant_response,
    ls.listingName,
    ls.price,
    l.ID             AS landlord_id,
    l.firstName      AS landlord_firstName,
    l.lastName       AS landlord_lastName,
    l.profilePic     AS landlord_profilePic
FROM leasetbl le
JOIN listingtbl  ls ON le.listing_id  = ls.ID
JOIN landlordtbl l  ON ls.landlord_id = l.ID
WHERE le.tenant_id = ?
  AND le.status != 'terminated'
ORDER BY le.ID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$leases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   HELPER FUNCTIONS
========================= */
function getMaintenanceStatus($status, $created_at = null) {
    if ($status === 'Pending' && $created_at) {
        $created = new DateTime($created_at);
        $now     = new DateTime();
        $days    = $created->diff($now)->days;
        if ($days > 3) {
            return '<span class="badge bg-danger">OVERDUE</span>';
        }
    }
    return match (strtolower($status)) {
        'pending'                  => '<span class="badge bg-warning text-dark">Pending</span>',
        'scheduled', 'in progress' => '<span class="badge bg-primary">Scheduled</span>',
        'completed', 'resolved'    => '<span class="badge bg-success">Completed</span>',
        'rejected'                 => '<span class="badge bg-danger">Rejected</span>',
        default                    => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>'
    };
}

function getPriorityBadge($priority) {
    return match ($priority) {
        'Low'    => '<span class="badge bg-success">Low</span>',
        'Medium' => '<span class="badge bg-warning text-dark">Medium</span>',
        'High'   => '<span class="badge bg-danger">High</span>',
        'Urgent' => '<span class="badge bg-dark">Urgent</span>',
        default  => '<span class="badge bg-secondary">'.htmlspecialchars($priority).'</span>'
    };
}

// Latest maintenance request
$complaintDate   = '-';
$complaintStatus = 'No requests yet';
$latestRequest   = null;

if (!empty($maintenanceRequests)) {
    $latestRequest   = $maintenanceRequests[0];
    $complaintDate   = date("F j, Y", strtotime($latestRequest['created_at']));
    $complaintStatus = $latestRequest['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals</title>

    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.2/css/bootstrap.min.css'>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/js/bootstrap.min.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary-color: #8d0b41; }

        body, h1, h2, h3, h4, h5, h6,
        p, span, a, button, input, textarea, label, div {
            font-family: "Montserrat", sans-serif;
        }

        .section-title {
            font-family: "Montserrat", sans-serif !important;
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.3px !important;
            color: #25343F;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: #8d0b41; }

        button, .btn, .card-btn, .complaint-btn {
            font-family: "Montserrat", sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .btn-primary-custom {
            background: #8d0b41;
            color: #ffffff;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background: #6f0833;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(141,11,65,0.3);
        }

        .btn-outline-custom {
            border: 1px solid #8d0b41;
            color: #8d0b41;
            background: transparent;
            border-radius: 25px;
            padding: 6px 18px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 8px;
        }
        .btn-outline-custom:hover {
            background: #8d0b41;
            color: #ffffff;
        }

        body { background: #f5f6f8; font-size: 14px; color: #333; }

        .container { margin-top: 130px; margin-bottom: 50px; }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            border-radius: 14px;
            padding: 22px;
            min-height: 300px;
            box-shadow: 0 6px 18px rgba(0,0,0,.08);
            position: relative;
            background: #ffffff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            color: #25343F;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }
        .dashboard-card > * { position: relative; z-index: 1; }

        .card-rent    { background: #EAEFEF; }
        .card-lease   { background: #fff5dc; border: 1px solid #f0e1b5; }
        .card-maintenance { background: #fdeeee; border: 1px solid #f4caca; }

        .card-rent::before,
        .card-lease::before,
        .card-maintenance::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 6px; height: 100%;
            border-radius: 14px 0 0 14px;
            z-index: 0;
        }
        .card-rent::before        { background: linear-gradient(to bottom, #56ab2f, #a8e063); }
        .card-lease::before       { background: linear-gradient(to bottom, #f7971e, #ffd200); }
        .card-maintenance::before { background: linear-gradient(to bottom, #f00000, #dc281e); }

        .dashboard-card h5 { font-weight: 600; margin-bottom: 15px; }
        .dashboard-card p  { margin-bottom: 8px; font-size: 0.95rem; }

        .card-btn {
            margin-top: 15px;
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 0.85rem;
            background: transparent;
            border: 1px solid currentColor;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .card-btn:hover { background: currentColor; color: white !important; }

        /* Rentals table section */
        .rentals-section {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            border: 1px solid #ddd;
            box-shadow: 0 6px 18px rgba(0,0,0,.06);
            margin-top: 30px;
        }
        .rentals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .rentals-table {
            border-collapse: separate;
            border-spacing: 0 8px;
            width: 100%;
        }
        .rentals-table thead th {
            background-color: #f8fafc;
            color: #718096;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border: none;
            padding: 16px;
        }
        .rentals-table tbody tr {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s ease;
        }
        .rentals-table tbody tr:hover {
            transform: scale(1.005);
            background-color: #fffafb;
        }
        .rentals-table td {
            padding: 20px 16px;
            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
            color: #4a5568;
            vertical-align: middle;
        }
        .rentals-table .btn-sm { border-radius: 8px; padding: 6px 10px; }

        /* Complaint card */
        .complaint-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 18px rgba(0,0,0,.06);
            margin-top: 30px;
            transition: all 0.3s ease;
        }
        .complaint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .complaint-body { font-size: 0.95rem; color: #4a5568; }
        .complaint-body p {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .complaint-btn {
            background: #8d0b41;
            color: #fff;
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .complaint-btn:hover {
            background: #6f0833;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(141,11,65,0.3);
        }

        /* Font overrides */
        .table, .table th, .table td, .table thead th,
        .badge, .btn, .card, .dashboard-card,
        .complaint-card {
            font-family: "Montserrat", sans-serif !important;
        }
        .table th { font-weight: 600; letter-spacing: 0.3px; font-size: 0.9rem; }
        .table td { font-weight: 400; font-size: 0.9rem; }
        .badge    { font-weight: 500; letter-spacing: 0.4px; }

        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        /* ✅ Pending lease notice banner */
        .pending-lease-notice {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-left: 5px solid #f7971e;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            color: #5d4037;
        }
        .pending-lease-notice i { font-size: 1.3rem; color: #f7971e; }

        /* Empty state */
        .lease-empty-card {
            max-width: 500px; width: 100%;
            border-top: 5px solid #8d0b41;
            transition: all 0.3s ease;
        }
        .lease-empty-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .empty-icon {
            width: 90px; height: 90px;
            margin: auto;
            border-radius: 50%;
            background: rgba(141,11,65,0.08);
            display: flex; align-items: center; justify-content: center;
        }
        .empty-icon i { font-size: 45px; color: #8d0b41; }

        .find-apartment-btn { transition: all 0.3s ease; }
        .find-apartment-btn:hover {
            background: #6a0831;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.2);
        }

        /* Terminate Modal */
        #terminateModal .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: fadeInScale 0.25s ease;
        }
        #terminateModal .modal-header {
            background-color: #8d0b41;
            color: #fff;
            border-bottom: none;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        #terminateModal .modal-title { font-weight: 600; font-size: 1.2rem; color: #fff; }
        #terminateModal .btn-close   { filter: invert(1); }
        #terminateModal .modal-body p { font-size: 1rem; color: #25343F; }
        #terminateModal textarea {
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 0.95rem;
            transition: 0.2s ease;
        }
        #terminateModal textarea:focus {
            border-color: #8d0b41;
            box-shadow: 0 0 0 0.15rem rgba(141,11,65,0.25);
        }
        #terminateModal .modal-footer { border-top: none; padding: 15px 20px; }
        #terminateModal .btn-secondary { border-radius: 8px; }
        #terminateModal .btn-danger {
            background-color: #8d0b41;
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }
        #terminateModal .btn-danger:hover { background-color: #6e0832; }

        @keyframes fadeInScale {
            from { transform: scale(0.95); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <?php
    // ✅ FIX: Show empty state only if there are truly NO leases at all (not even pending)
    $hasAnyLease = !empty($leases);
    ?>

    <?php if (!$hasAnyLease): ?>

        <div class="container d-flex justify-content-center align-items-center" style="min-height:70vh;">
            <div class="text-center p-5 bg-white rounded-4 shadow-sm lease-empty-card">
                <div class="empty-icon mb-4">
                    <i class="bi bi-house-x"></i>
                </div>
                <h3 class="fw-semibold mb-2">No Active Lease Yet</h3>
                <p class="text-muted mb-4" style="background:#f8d7e4; color:#8d0b41; border:none;">
                    You currently do not have an active lease agreement assigned to your account.
                </p>
                <a href="tenant.php" class="btn mt-3 text-white find-apartment-btn" style="background:#8d0b41; border:none;">
                    Find Apartment
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="container">

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Your maintenance request has been submitted successfully!
                </div>
            <?php endif; ?>

            <?php if ($lease && $lease['status'] === 'pending'): ?>
                <!-- ✅ Banner: Notify tenant that landlord sent a lease awaiting their response -->
                <div class="pending-lease-notice">
                    <i class="bi bi-bell-fill"></i>
                    <span>
                        <strong>Action Required:</strong> Your landlord has sent you a lease agreement.
                        Please review and respond below in the <strong>My Rentals</strong> table.
                    </span>
                </div>
            <?php endif; ?>

            <!-- DASHBOARD CARDS -->
            <div class="dashboard-cards">

                <!-- Rent Payment -->
                <div class="dashboard-card card-rent">
                    <h5>Rent Payment Status</h5>
                    <?php if ($lease && $lease['status'] === 'active'): ?>
                        <p><strong>Last Paid:</strong> <?= $lastPaid ?></p>
                        <p><strong>Due Date:</strong> <?= $dueDate ?></p>
                        <p><strong>Expected Amount Due:</strong> ₱<?= $expectedAmount ?></p>
                        <p><strong>Total Rent Payment:</strong> ₱<?= $totalPaid ?></p>
                        <a href="payment-historyTenant.php" class="btn-outline-custom">View History</a>
                    <?php else: ?>
                        <p class="text-muted" style="font-size:0.9rem;">
                            Rent details will appear once your lease is active.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Lease Agreement -->
                <div class="dashboard-card card-lease">
                    <h5>Lease Agreement</h5>
                    <?php if ($lease): ?>
                        <p><strong>Status:</strong>
                            <?php if ($lease['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Waiting for Your Approval</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Lease End:</strong>
                            <?= isset($lease['end_date']) ? date("F Y", strtotime($lease['end_date'])) : '-' ?>
                        </p>
                        <?php if (!empty($lease['pdf_path'])): ?>
                            <a href="<?= htmlspecialchars($lease['pdf_path']) ?>" class="btn-outline-custom" target="_blank">View Contract</a>
                        <?php else: ?>
                            <span class="card-btn" style="color:#999; cursor:not-allowed;">No Contract</span>
                        <?php endif; ?>
                        <?php foreach ($leases as $l): ?>
                            <?php if ($l['lease_status'] === 'active'): ?>
                                <button class="btn-outline-custom"
                                    onclick="window.location.href='tenant-apartment-details.php?lease_id=<?= $l['lease_id'] ?>'">
                                    View Apartment Details
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No lease information available.</p>
                    <?php endif; ?>
                </div>

                <!-- Remove Lease Modal -->
                <div class="modal fade" id="removeLeaseModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Remove Lease</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">Are you sure you want to remove this lease?</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirmRemoveLease" class="btn btn-danger">Remove Lease</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Requests -->
                <div class="dashboard-card card-maintenance">
                    <h5>
                        Maintenance Requests
                        <?php if ($notificationCount > 0): ?>
                            <span class="badge bg-danger"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </h5>

                    <?php if (empty($maintenanceRequests)): ?>
                        <p class="text-muted">No maintenance requests yet</p>
                    <?php else: ?>
                        <p><strong>Latest:</strong>
                            <?= date("F d, Y", strtotime($maintenanceRequests[0]['created_at'])) ?>
                        </p>
                        <p><?= getMaintenanceStatus($maintenanceRequests[0]['status'], $maintenanceRequests[0]['created_at']); ?></p>
                    <?php endif; ?>

                    <button class="btn-outline-custom" onclick="window.location.href='maintenance-create.php'">
                        Create New
                    </button>
                    <button class="btn-outline-custom" onclick="window.location.href='maintenance-history.php'">
                        Maintenance History
                    </button>
                </div>

            </div><!-- end dashboard-cards -->

            <!-- ===================== -->
            <!-- MY RENTALS TABLE      -->
            <!-- ===================== -->
            <div class="rentals-section">
                <div class="rentals-header">
                    <h3 class="section-title">
                        <i class="bi bi-house-check" style="font-size:1.9rem;"></i> My Rentals
                    </h3>
                </div>

                <table class="rentals-table">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Landlord Name</th>
                            <th>Apartment</th>
                            <th>Price</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leases): ?>
                            <?php foreach ($leases as $landlord): ?>
                                <?php
                                $lease_id = $landlord['lease_id'];

                                // Check termination status
                                $checkTerminationSql = "
                                    SELECT ID, landlord_status
                                    FROM lease_terminationstbl
                                    WHERE lease_id = ? AND terminated_by = 'tenant'
                                    ORDER BY ID DESC
                                    LIMIT 1
                                ";
                                $checkStmt = $conn->prepare($checkTerminationSql);
                                $checkStmt->bind_param("i", $lease_id);
                                $checkStmt->execute();
                                $terminationResult = $checkStmt->get_result()->fetch_assoc();
                                $checkStmt->close();

                                $hasTerminationRequest  = $terminationResult && $terminationResult['landlord_status'] !== 'rejected';
                                $terminationWasRejected = $terminationResult && $terminationResult['landlord_status'] === 'rejected';

                                $today     = date("Y-m-d");
                                $isExpired = strtotime($landlord['end_date']) < strtotime($today);
                                ?>
                                <tr data-lease="<?= $landlord['lease_id'] ?>">

                                    <!-- Profile -->
                                    <td>
                                        <?php if (!empty($landlord['landlord_profilePic'])): ?>
                                            <a href="landlord-profile.php?landlord_id=<?= $landlord['landlord_id'] ?>">
                                                <img src="../uploads/<?= htmlspecialchars($landlord['landlord_profilePic']) ?>"
                                                    class="rounded-circle" width="40" height="40"
                                                    style="object-fit:cover; border:2px solid #8d0b41;">
                                            </a>
                                        <?php else: ?>
                                            <?php $initials = strtoupper(substr($landlord['landlord_firstName'],0,1).substr($landlord['landlord_lastName'],0,1)); ?>
                                            <div style="width:40px;height:40px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#fff;">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Landlord Name -->
                                    <td class="fw-bold text-dark">
                                        <?= htmlspecialchars($landlord['landlord_firstName'].' '.$landlord['landlord_lastName']) ?>
                                    </td>

                                    <!-- Apartment -->
                                    <td class="fw-semibold"><?= htmlspecialchars($landlord['listingName']) ?></td>

                                    <!-- Price -->
                                    <td>₱<?= number_format($landlord['price'], 2) ?></td>

                                    <!-- Dates -->
                                    <td><?= date("F j, Y", strtotime($landlord['start_date'])) ?></td>
                                    <td><?= date("F j, Y", strtotime($landlord['end_date']))   ?></td>

                                    <!-- Status -->
                                    <td>
                                        <?php
                                        if ($landlord['tenant_response'] === 'rejected') {
                                            echo '<span class="badge bg-danger">You Rejected</span>';
                                        } else {
                                            switch ($landlord['lease_status']) {
                                                case 'pending':
                                                    echo '<span class="badge bg-warning text-dark">Waiting for Your Approval</span>';
                                                    break;
                                                case 'active':
                                                    echo '<span class="badge bg-success">Active</span>';
                                                    break;
                                                case 'terminated':
                                                    echo '<span class="badge bg-danger">Terminated</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                            }
                                        }
                                        ?>
                                    </td>

                                    <!-- Action -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">

                                            <?php if ($landlord['lease_status'] === 'pending' && $landlord['tenant_response'] !== 'rejected'): ?>

                                                <a href="<?= htmlspecialchars($landlord['pdf_path']) ?>" target="_blank"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>

                                                <form action="tenant-accept.php" method="POST">
                                                    <input type="hidden" name="lease_id" value="<?= $landlord['lease_id'] ?>">
                                                    <button class="btn btn-success btn-sm">
                                                        <i class="bi bi-check2-circle"></i>
                                                    </button>
                                                </form>

                                                <form action="tenant-reject.php" method="POST"
                                                    onsubmit="return confirm('Reject this lease agreement?');">
                                                    <input type="hidden" name="lease_id" value="<?= $landlord['lease_id'] ?>">
                                                    <button class="btn btn-danger btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>

                                            <?php elseif ($landlord['lease_status'] === 'active'): ?>

                                                <a href="<?= htmlspecialchars($landlord['pdf_path']) ?>" target="_blank"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>

                                                <?php if ($isExpired): ?>
                                                    <button class="btn btn-warning btn-sm renew-btn"
                                                        data-lease="<?= $landlord['lease_id'] ?>">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <button type="button"
                                                    class="btn btn-danger btn-sm terminate-btn"
                                                    data-lease="<?= $landlord['lease_id'] ?>"
                                                    <?= $hasTerminationRequest ? 'disabled title="Termination request already sent"' : '' ?>
                                                    <?= $terminationWasRejected ? 'title="Your termination was rejected — you may re-submit"' : '' ?>>
                                                    <i class="bi bi-x-square"></i>
                                                    <?= $terminationWasRejected ? '<span style="font-size:0.7rem;margin-left:3px;">Re-submit</span>' : '' ?>
                                                </button>

                                            <?php elseif ($landlord['tenant_response'] === 'rejected'): ?>

                                                <button class="btn btn-danger btn-sm remove-btn"
                                                    data-lease="<?= $landlord['lease_id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            <?php else: ?>
                                                <span class="text-muted">No action</span>
                                            <?php endif; ?>

                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No rentals available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- TERMINATE MODAL -->
                <div class="modal fade" id="terminateModal" tabindex="-1"
                    aria-labelledby="terminateModalLabel" aria-hidden="true"
                    data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="terminateModalLabel">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Terminate Lease
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="terminateLeaseId">
                                <p>Please provide a reason for termination:</p>
                                <textarea id="terminateReason" class="form-control" rows="4"
                                    placeholder="Type your reason here..." required></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirmTerminate" class="btn btn-danger">Terminate</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end rentals-section -->

            <!-- Maintenance Status Card -->
            <div class="mt-4">
                <div class="complaint-card">
                    <div class="complaint-header d-flex justify-content-between align-items-center">
                        <h3 class="section-title">
                            <i class="bi bi-tools" style="font-size:1.5rem;"></i> Maintenance Request
                        </h3>
                        <a class="btn-primary-custom" href="maintenance-create.php">File New Complaint</a>
                    </div>
                    <div class="complaint-body">
                        <p><strong>Date:</strong> <?= $complaintDate ?></p>
                        <p>
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            Your last complaint:
                            <span class="complaint-status"><?= htmlspecialchars($complaintStatus) ?></span>
                        </p>
                        <p>
                            <strong>Status:</strong>
                            <span class="complaint-status">
                                <?= getMaintenanceStatus($complaintStatus, $latestRequest['created_at'] ?? null) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

        </div><!-- end container -->

    <?php endif; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            let leaseToRemove = null;

            /* ── REMOVE LEASE ─────────────────────────────── */
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    leaseToRemove = btn.dataset.lease;
                    new bootstrap.Modal(document.getElementById('removeLeaseModal')).show();
                });
            });

            document.getElementById('confirmRemoveLease')?.addEventListener('click', () => {
                if (!leaseToRemove) return;
                fetch('tenant-remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `lease_id=${leaseToRemove}`
                })
                .then(res => res.text())
                .then(msg => {
                    if (msg.trim() === 'success') {
                        document.querySelector(`tr[data-lease='${leaseToRemove}']`)?.remove();
                        bootstrap.Modal.getInstance(document.getElementById('removeLeaseModal')).hide();
                        leaseToRemove = null;
                    } else {
                        alert("Failed to remove lease: " + msg);
                    }
                })
                .catch(err => alert("Error: " + err));
            });

            /* ── RENEW LEASE ──────────────────────────────── */
            document.querySelectorAll('.renew-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const leaseId = btn.dataset.lease;
                    if (!confirm("Renew this lease?")) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&action=renew`
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Submitted!' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#8d0b41'
                        }).then(() => { if (data.success) location.reload(); });
                    });
                });
            });

            /* ── TERMINATE LEASE — open modal ─────────────── */
            document.querySelectorAll('.terminate-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.getElementById('terminateLeaseId').value = this.dataset.lease;
                    document.getElementById('terminateReason').value  = '';
                    new bootstrap.Modal(document.getElementById('terminateModal')).show();
                });
            });

            /* ── TERMINATE LEASE — confirm ────────────────── */
            document.getElementById("confirmTerminate").addEventListener("click", function () {
                const leaseId = document.getElementById("terminateLeaseId").value;
                const reason  = document.getElementById("terminateReason").value.trim();

                if (!reason) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Input',
                        text: 'Please enter a reason for termination.',
                        confirmButtonColor: '#8d0b41'
                    });
                    return;
                }

                const confirmBtn = document.getElementById("confirmTerminate");
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

                fetch("update-lease.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `lease_id=${leaseId}&action=terminate&reason=${encodeURIComponent(reason)}`
                })
                .then(res => res.json())
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('terminateModal')).hide();
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Terminate';

                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Request Submitted!' : 'Error!',
                        text: data.message || (data.success ? 'Your termination request has been submitted.' : 'Something went wrong.'),
                        confirmButtonColor: '#8d0b41'
                    }).then(() => {
                        if (data.success) location.reload();
                    });
                })
                .catch(() => {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Terminate';
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Please check your connection.',
                        confirmButtonColor: '#8d0b41'
                    });
                });
            });

        });
    </script>

</body>
</html>