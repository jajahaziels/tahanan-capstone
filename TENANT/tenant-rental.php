<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

$lease = null;
$lastPaid = 'No rent yet';
$dueDate = '-';
$expectedAmount = '0.00';
$totalPaid = '0.00';

// Fetch the most relevant lease for the dashboard cards (active first, then pending)
$leaseSql = "
    SELECT ID, start_date, end_date, pdf_path, listing_id, status
    FROM leasetbl
    WHERE tenant_id = ?
      AND status IN ('active', 'pending')
    ORDER BY CASE status WHEN 'active' THEN 1 WHEN 'pending' THEN 2 END, end_date DESC
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();
$leaseStmt->close();

if ($lease && $lease['status'] === 'active') {
    $paymentSql = "
        SELECT MAX(paid_date) AS last_paid,
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
    $paymentStmt->close();
    if ($payment) {
        $lastPaid       = $payment['last_paid'] ? date("F d, Y", strtotime($payment['last_paid'])) : 'Not yet paid';
        $dueDate        = $payment['next_due']  ? date("F d, Y", strtotime($payment['next_due']))  : '-';
        $expectedAmount = number_format($payment['expected_amount'] ?? 0, 2);
        $totalPaid      = number_format($payment['total_paid']     ?? 0, 2);
    }
}

$maintenanceSql = "
    SELECT * FROM maintenance_requeststbl WHERE tenant_id = ?
    ORDER BY CASE priority WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 END, created_at DESC
";
$maintenanceStmt = $conn->prepare($maintenanceSql);
$maintenanceStmt->bind_param("i", $tenant_id);
$maintenanceStmt->execute();
$maintenanceRequests = $maintenanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$maintenanceStmt->close();

$notificationCount = 0;
foreach ($maintenanceRequests as $req) {
    if ($req['status'] === 'Pending' || $req['status'] === 'In Progress') $notificationCount++;
}

// Fetch ALL leases for the table — include terminated/cancelled so tenant can see history
// and take action (remove rejected ones, re-apply, etc.)
$sql = "
    SELECT le.ID AS lease_id, le.start_date, le.end_date, le.status AS lease_status,
           le.pdf_path, le.tenant_response,
           ls.listingName, ls.price, ls.ID AS listing_id,
           l.ID AS landlord_id, l.firstName AS landlord_firstName, l.lastName AS landlord_lastName,
           l.profilePic AS landlord_profilePic
    FROM leasetbl le
    JOIN listingtbl  ls ON le.listing_id  = ls.ID
    JOIN landlordtbl l  ON ls.landlord_id = l.ID
    WHERE le.tenant_id = ?
    ORDER BY le.ID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$leases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function getMaintenanceStatus($status, $created_at = null) {
    if ($status === 'Pending' && $created_at) {
        $days = (new DateTime($created_at))->diff(new DateTime())->days;
        if ($days > 3) return '<span class="badge bg-danger">OVERDUE</span>';
    }
    return match (strtolower($status)) {
        'pending'                  => '<span class="badge bg-warning text-dark">Pending</span>',
        'scheduled', 'in progress' => '<span class="badge bg-primary">Scheduled</span>',
        'completed', 'resolved'    => '<span class="badge bg-success">Completed</span>',
        'rejected'                 => '<span class="badge bg-danger">Rejected</span>',
        default => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>'
    };
}

$complaintDate = '-'; $complaintStatus = 'No requests yet'; $latestRequest = null;
if (!empty($maintenanceRequests)) {
    $latestRequest   = $maintenanceRequests[0];
    $complaintDate   = date("F j, Y", strtotime($latestRequest['created_at']));
    $complaintStatus = $latestRequest['status'];
}

$hasActiveLease = false;
foreach ($leases as $l) {
    if ($l['lease_status'] === 'active') { $hasActiveLease = true; break; }
}

// Show the page only when the tenant has at least one lease record (any status)
$hasAnyLease = !empty($leases);
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
        body,h1,h2,h3,h4,h5,h6,p,span,a,button,input,textarea,label,div { font-family:"Montserrat",sans-serif; }
        .section-title { font-size:1.5rem!important; font-weight:600!important; letter-spacing:.3px!important; color:#25343F; margin-bottom:16px; display:flex; align-items:center; gap:10px; }
        .section-title i { color:#8d0b41; }
        .btn-primary-custom { background:#8d0b41; color:#fff; border:none; border-radius:25px; padding:8px 20px; font-size:.85rem; transition:all .3s ease; }
        .btn-primary-custom:hover { background:#6f0833; color:#fff; transform:translateY(-2px); box-shadow:0 6px 18px rgba(141,11,65,.3); }
        .btn-outline-custom { border:1px solid #8d0b41; color:#8d0b41; background:transparent; border-radius:25px; padding:6px 18px; font-size:.85rem; transition:all .3s ease; display:inline-block; margin-top:8px; }
        .btn-outline-custom:hover { background:#8d0b41; color:#fff; }
        .btn-outline-custom.locked, .btn-primary-custom.locked { opacity:.4; cursor:not-allowed; pointer-events:none; filter:grayscale(50%); }
        .locked-tooltip-wrapper { position:relative; display:inline-block; margin-top:8px; }
        .locked-tooltip-wrapper .locked-tip { display:none; position:absolute; bottom:110%; left:50%; transform:translateX(-50%); background:#333; color:#fff; font-size:.75rem; padding:5px 10px; border-radius:6px; white-space:nowrap; z-index:999; }
        .locked-tooltip-wrapper:hover .locked-tip { display:block; }
        .lease-locked-notice { background:#f0f4ff; border:1px solid #c7d7ff; border-radius:8px; padding:8px 12px; font-size:.82rem; color:#3d5a99; margin-top:10px; display:flex; align-items:center; gap:6px; }
        body { background:#f5f6f8; font-size:14px; color:#333; }
        .container { margin-top:130px; margin-bottom:50px; }
        .dashboard-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:30px; }
        .dashboard-card { border-radius:14px; padding:22px; min-height:300px; box-shadow:0 6px 18px rgba(0,0,0,.08); position:relative; background:#fff; transition:transform .3s ease,box-shadow .3s ease; overflow:hidden; color:#25343F; }
        .dashboard-card:hover { transform:translateY(-5px); box-shadow:0 12px 25px rgba(0,0,0,.12); }
        .dashboard-card > * { position:relative; z-index:1; }
        .card-rent { background:#EAEFEF; }
        .card-lease { background:#fff5dc; border:1px solid #f0e1b5; }
        .card-maintenance { background:#fdeeee; border:1px solid #f4caca; }
        .card-rent::before,.card-lease::before,.card-maintenance::before { content:""; position:absolute; top:0; left:0; width:6px; height:100%; border-radius:14px 0 0 14px; z-index:0; }
        .card-rent::before { background:linear-gradient(to bottom,#56ab2f,#a8e063); }
        .card-lease::before { background:linear-gradient(to bottom,#f7971e,#ffd200); }
        .card-maintenance::before { background:linear-gradient(to bottom,#f00000,#dc281e); }
        .dashboard-card h5 { font-weight:600; margin-bottom:15px; }
        .dashboard-card p  { margin-bottom:8px; font-size:.95rem; }
        .rentals-section { background:#fff; border-radius:14px; padding:22px; border:1px solid #ddd; box-shadow:0 6px 18px rgba(0,0,0,.06); margin-top:30px; }
        .rentals-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid #e2e8f0; }
        .rentals-table { border-collapse:separate; border-spacing:0 8px; width:100%; }
        .rentals-table thead th { background:#f8fafc; color:#718096; text-transform:uppercase; font-size:.75rem; letter-spacing:.05em; border:none; padding:16px; }
        .rentals-table tbody tr { background:#fff; box-shadow:0 2px 4px rgba(0,0,0,.02); transition:transform .2s ease; }
        .rentals-table tbody tr:hover { transform:scale(1.005); background:#fffafb; }
        .rentals-table td { padding:20px 16px; border-top:1px solid #edf2f7; border-bottom:1px solid #edf2f7; color:#4a5568; vertical-align:middle; }
        .rentals-table .btn-sm { border-radius:8px; padding:6px 10px; }
        .complaint-card { background:#fff; border-radius:14px; padding:24px; border:1px solid #e5e7eb; box-shadow:0 6px 18px rgba(0,0,0,.06); margin-top:30px; transition:all .3s ease; }
        .complaint-card:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(0,0,0,.08); }
        .complaint-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .complaint-body { font-size:.95rem; color:#4a5568; }
        .complaint-body p { margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .complaint-btn { background:#8d0b41; color:#fff; border-radius:20px; padding:6px 16px; font-size:.85rem; text-decoration:none; font-weight:500; transition:all .3s ease; }
        .complaint-btn:hover { background:#6f0833; transform:translateY(-2px); }
        .complaint-btn.locked { background:#ccc; cursor:not-allowed; pointer-events:none; }
        .pending-lease-notice { background:#fff8e1; border:1px solid #ffe082; border-left:5px solid #f7971e; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:.95rem; color:#5d4037; }
        .pending-lease-notice i { font-size:1.3rem; color:#f7971e; }
        .alert-success { background:#d4edda; border-color:#c3e6cb; color:#155724; border-radius:10px; padding:15px; margin-bottom:20px; }
        .lease-empty-card { max-width:500px; width:100%; border-top:5px solid #8d0b41; }
        .empty-icon { width:90px; height:90px; margin:auto; border-radius:50%; background:rgba(141,11,65,.08); display:flex; align-items:center; justify-content:center; }
        .empty-icon i { font-size:45px; color:#8d0b41; }
        #terminateModal .modal-content { border-radius:12px; }
        #terminateModal .modal-header { background:#8d0b41; color:#fff; border-bottom:none; border-top-left-radius:12px; border-top-right-radius:12px; }
        #terminateModal .modal-title { font-weight:600; font-size:1.2rem; color:#fff; }
        #terminateModal .btn-close { filter:invert(1); }
        #terminateModal .modal-footer { border-top:none; }
        #terminateModal .btn-danger { background:#8d0b41; border:none; border-radius:8px; }
        #terminateModal .btn-danger:hover { background:#6e0832; }
        /* Rows for ended leases (terminated/cancelled/rejected) get a subtle tint */
        tr.lease-ended td { background:#fafafa; color:#888; }
        tr.lease-ended .badge { opacity:.8; }
    </style>
</head>
<body>
    <?php include '../Components/tenant-header.php'; ?>

    <?php if (!$hasAnyLease): ?>
        <div class="container d-flex justify-content-center align-items-center" style="min-height:70vh;">
            <div class="text-center p-5 bg-white rounded-4 shadow-sm lease-empty-card">
                <div class="empty-icon mb-4"><i class="bi bi-house-x"></i></div>
                <h3 class="fw-semibold mb-2">No Active Lease Yet</h3>
                <p class="text-muted mb-4" style="background:#f8d7e4;color:#8d0b41;border:none;">
                    You currently do not have a lease agreement assigned to your account.
                </p>
                <a href="tenant.php" class="btn mt-3 text-white" style="background:#8d0b41;border:none;">Find Apartment</a>
            </div>
        </div>
    <?php else: ?>

    <div class="container">

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> Your maintenance request has been submitted successfully!</div>
        <?php endif; ?>

        <?php if ($lease && $lease['status'] === 'pending'): ?>
            <div class="pending-lease-notice">
                <i class="bi bi-bell-fill"></i>
                <span><strong>Action Required:</strong> Your landlord has sent you a lease agreement. Please review and respond below in the <strong>My Rentals</strong> table.</span>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-cards">

            <!-- Rent Payment -->
            <div class="dashboard-card card-rent">
                <h5>Rent Payment Status</h5>
                <?php if ($hasActiveLease): ?>
                    <p><strong>Last Paid:</strong> <?= $lastPaid ?></p>
                    <p><strong>Due Date:</strong> <?= $dueDate ?></p>
                    <p><strong>Expected Amount Due:</strong> ₱<?= $expectedAmount ?></p>
                    <p><strong>Total Rent Payment:</strong> ₱<?= $totalPaid ?></p>
                    <a href="payment-historyTenant.php" class="btn-outline-custom">View History</a>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.9rem;">Rent Payment details will appear once your lease is active.</p>
                <?php endif; ?>
            </div>

            <!-- Lease Agreement -->
            <div class="dashboard-card card-lease">
                <h5>Lease Agreement</h5>
                <?php if ($lease): ?>
                    <p><strong>Status:</strong>
                        <?= $lease['status'] === 'active'
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-warning text-dark">Waiting for Your Approval</span>' ?>
                    </p>
                    <p><strong>Lease End:</strong> <?= isset($lease['end_date']) ? date("F Y", strtotime($lease['end_date'])) : '-' ?></p>

                    <?php if ($hasActiveLease && !empty($lease['pdf_path'])): ?>
                        <a href="<?= htmlspecialchars($lease['pdf_path']) ?>" class="btn-outline-custom" target="_blank">View Contract</a>
                    <?php elseif (!empty($lease['pdf_path'])): ?>
                        <div class="locked-tooltip-wrapper">
                            <span class="btn-outline-custom locked">View Contract</span>
                            <span class="locked-tip">Accept the lease agreement first</span>
                        </div>
                    <?php else: ?>
                        <span style="color:#999;font-size:.85rem;">No contract file yet</span>
                    <?php endif; ?>

                    <?php if ($hasActiveLease): ?>
                        <?php foreach ($leases as $l): ?>
                            <?php if ($l['lease_status'] === 'active'): ?>
                                <button class="btn-outline-custom" onclick="window.location.href='tenant-apartment-details.php?lease_id=<?= $l['lease_id'] ?>'">View Apartment Details</button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="locked-tooltip-wrapper">
                            <span class="btn-outline-custom locked">View Apartment Details</span>
                            <span class="locked-tip">Accept the lease agreement first</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No lease information available.</p>
                <?php endif; ?>
            </div>

            <!-- Maintenance Requests -->
            <div class="dashboard-card card-maintenance">
                <h5>Maintenance Requests <?php if ($notificationCount > 0): ?><span class="badge bg-danger"><?= $notificationCount ?></span><?php endif; ?></h5>
                <?php if ($hasActiveLease): ?>
                    <?php if (empty($maintenanceRequests)): ?>
                        <p class="text-muted">No maintenance requests yet</p>
                    <?php else: ?>
                        <p><strong>Latest:</strong> <?= date("F d, Y", strtotime($maintenanceRequests[0]['created_at'])) ?></p>
                        <p><?= getMaintenanceStatus($maintenanceRequests[0]['status'], $maintenanceRequests[0]['created_at']) ?></p>
                    <?php endif; ?>
                    <button class="btn-outline-custom" onclick="window.location.href='maintenance-create.php'">Create New</button>
                    <button class="btn-outline-custom" onclick="window.location.href='maintenance-history.php'">Maintenance History</button>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.9rem;">Maintenance features are available once your lease is active.</p>
                    <div class="locked-tooltip-wrapper"><span class="btn-outline-custom locked">Create New</span><span class="locked-tip">Accept the lease agreement first</span></div>
                    <div class="locked-tooltip-wrapper"><span class="btn-outline-custom locked">Maintenance History</span><span class="locked-tip">Accept the lease agreement first</span></div>
                <?php endif; ?>
            </div>

        </div><!-- end dashboard-cards -->

        <!-- MY RENTALS TABLE -->
        <div class="rentals-section">
            <div class="rentals-header">
                <h3 class="section-title"><i class="bi bi-house-check" style="font-size:1.9rem;"></i> My Rentals</h3>
            </div>
            <table class="rentals-table">
                <thead>
                    <tr>
                        <th>Profile</th><th>Landlord Name</th><th>Apartment</th><th>Price</th>
                        <th>Start Date</th><th>End Date</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leases as $row):
                        $lease_id        = $row['lease_id'];
                        $leaseStatus     = $row['lease_status'];
                        $tenantResponse  = $row['tenant_response'];

                        // Termination request state
                        $checkSql = "SELECT ID, landlord_status FROM lease_terminationstbl WHERE lease_id = ? AND terminated_by = 'tenant' ORDER BY ID DESC LIMIT 1";
                        $checkStmt = $conn->prepare($checkSql);
                        $checkStmt->bind_param("i", $lease_id);
                        $checkStmt->execute();
                        $terminationResult = $checkStmt->get_result()->fetch_assoc();
                        $checkStmt->close();

                        $hasTerminationRequest  = $terminationResult && $terminationResult['landlord_status'] === 'pending';
                        $terminationWasRejected = $terminationResult && $terminationResult['landlord_status'] === 'rejected';

                        $isExpired     = strtotime($row['end_date']) < strtotime(date("Y-m-d"));
                        $isActive      = $leaseStatus === 'active';
                        $isPending     = $leaseStatus === 'pending' && $tenantResponse !== 'rejected';
                        $isTerminated  = $leaseStatus === 'terminated';
                        $isCancelled   = $leaseStatus === 'cancelled';
                        $isRejected    = $tenantResponse === 'rejected';

                        // Rows that are "done" get a faded style
                        $rowClass = ($isTerminated || $isCancelled || $isRejected) ? 'lease-ended' : '';
                    ?>
                    <tr data-lease="<?= $lease_id ?>" class="<?= $rowClass ?>">
                        <td>
                            <?php if (!empty($row['landlord_profilePic'])): ?>
                                <a href="landlord-profile.php?landlord_id=<?= $row['landlord_id'] ?>">
                                    <img src="../uploads/<?= htmlspecialchars($row['landlord_profilePic']) ?>" class="rounded-circle" width="40" height="40" style="object-fit:cover;border:2px solid #8d0b41;">
                                </a>
                            <?php else: ?>
                                <?php $initials = strtoupper(substr($row['landlord_firstName'],0,1).substr($row['landlord_lastName'],0,1)); ?>
                                <div style="width:40px;height:40px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#fff;"><?= $initials ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['landlord_firstName'].' '.$row['landlord_lastName']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['listingName']) ?></td>
                        <td>₱<?= number_format($row['price'], 2) ?></td>
                        <td><?= date("F j, Y", strtotime($row['start_date'])) ?></td>
                        <td><?= date("F j, Y", strtotime($row['end_date'])) ?></td>
                        <td>
                            <?php
                            if ($isRejected) {
                                echo '<span class="badge bg-danger">You Rejected</span>';
                            } elseif ($isTerminated) {
                                echo '<span class="badge bg-danger">Terminated</span>';
                            } elseif ($isCancelled) {
                                echo '<span class="badge bg-secondary">Cancelled</span>';
                            } elseif ($isPending) {
                                echo '<span class="badge bg-warning text-dark">Waiting for Your Approval</span>';
                            } elseif ($isActive) {
                                echo '<span class="badge bg-success">Active</span>';
                            } else {
                                echo '<span class="badge bg-secondary">'.htmlspecialchars($leaseStatus).'</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 flex-wrap">

                                <?php if ($isPending): ?>
                                    <!-- Pending: view PDF, accept, reject -->
                                    <?php if (!empty($row['pdf_path'])): ?>
                                        <a href="<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank"
                                            class="btn btn-primary btn-sm" title="View Contract">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form action="tenant-accept.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="lease_id" value="<?= $lease_id ?>">
                                        <button class="btn btn-success btn-sm" title="Accept Lease">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                        class="btn btn-danger btn-sm reject-lease-btn"
                                        data-lease="<?= $lease_id ?>"
                                        title="Reject Lease">
                                        <i class="bi bi-x-circle"></i>
                                    </button>

                                <?php elseif ($isActive): ?>
                                    <!-- Active: view PDF, optional renew/terminate -->
                                    <?php if (!empty($row['pdf_path'])): ?>
                                        <a href="<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank"
                                            class="btn btn-outline-primary btn-sm" title="View Contract">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($isExpired): ?>
                                        <button class="btn btn-warning btn-sm renew-btn"
                                            data-lease="<?= $lease_id ?>" title="Renew Lease">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button"
                                        class="btn btn-danger btn-sm terminate-btn"
                                        data-lease="<?= $lease_id ?>"
                                        <?= $hasTerminationRequest ? 'disabled title="Termination request already sent"' : '' ?>
                                        <?= $terminationWasRejected ? 'title="Your last request was rejected — you may re-submit"' : '' ?>>
                                        <i class="bi bi-x-square"></i>
                                        <?= $terminationWasRejected ? '<span style="font-size:.7rem;margin-left:3px;">Re-submit</span>' : '' ?>
                                    </button>
                                    <?php if ($hasTerminationRequest): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:.72rem;">Awaiting landlord approval</span>
                                    <?php endif; ?>

                                <?php elseif ($isRejected || $isTerminated || $isCancelled): ?>
                                    <!-- Ended leases: tenant can remove the row from their list -->
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm remove-btn"
                                        data-lease="<?= $lease_id ?>"
                                        title="Remove from list">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                <?php else: ?>
                                    <span class="text-muted" style="font-size:.8rem;">No action</span>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TERMINATE MODAL -->
            <div class="modal fade" id="terminateModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Request Lease Termination</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="terminateLeaseId">
                            <p>Please provide a reason. Your landlord will review and approve or reject this request.</p>
                            <textarea id="terminateReason" class="form-control" rows="4" placeholder="Type your reason here..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmTerminate" class="btn btn-danger">Send Request</button>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end rentals-section -->

        <!-- Maintenance Status Card -->
        <div class="mt-4">
            <div class="complaint-card">
                <div class="complaint-header d-flex justify-content-between align-items-center">
                    <h3 class="section-title"><i class="bi bi-tools" style="font-size:1.5rem;"></i> Maintenance Request</h3>
                    <?php if ($hasActiveLease): ?>
                        <a class="btn-primary-custom complaint-btn" href="maintenance-create.php">File New Complaint</a>
                    <?php else: ?>
                        <div class="locked-tooltip-wrapper" style="margin-top:0;">
                            <span class="complaint-btn locked">File New Complaint</span>
                            <span class="locked-tip">Accept the lease agreement first</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="complaint-body">
                    <?php if ($hasActiveLease): ?>
                        <p><strong>Date:</strong> <?= $complaintDate ?></p>
                        <p><i class="bi bi-exclamation-circle text-warning"></i> Your last complaint: <span><?= htmlspecialchars($complaintStatus) ?></span></p>
                        <p><strong>Status:</strong> <?= getMaintenanceStatus($complaintStatus, $latestRequest['created_at'] ?? null) ?></p>
                    <?php else: ?>
                        <div class="lease-locked-notice"><i class="bi bi-lock-fill"></i> Maintenance filing is available once your lease is accepted and active.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- end container -->
    <?php endif; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener("DOMContentLoaded", () => {

        /* ── Remove (hide) a terminated/cancelled/rejected lease row ──── */
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Remove this lease from your list?',
                    text: 'This will hide it from your rentals. You can still re-apply for the apartment.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const leaseId = btn.dataset.lease;
                    fetch('tenant-remove.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `lease_id=${leaseId}`
                    })
                    .then(r => r.text())
                    .then(msg => {
                        if (msg.trim() === 'success') {
                            const row = btn.closest('tr');
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        } else {
                            Swal.fire('Error', 'Could not remove: ' + msg, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        /* ── Reject a pending lease agreement ───────────────────────── */
        document.querySelectorAll('.reject-lease-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Reject this Lease Agreement?',
                    text: 'The landlord will be notified. You will still be able to apply for this apartment again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, reject it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const leaseId = btn.dataset.lease;

                    // POST to tenant-reject.php
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'tenant-reject.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'lease_id';
                    input.value = leaseId;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });

        /* ── Renew ───────────────────────────────────────────────────── */
        document.querySelectorAll('.renew-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Request Lease Renewal?',
                    text: 'Your landlord will be notified and must approve the renewal.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#8d0b41',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, request renewal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const leaseId = btn.dataset.lease;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&action=renew`
                    })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Submitted!' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#8d0b41'
                        }).then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        /* ── Open terminate modal ────────────────────────────────────── */
        document.querySelectorAll('.terminate-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                document.getElementById('terminateLeaseId').value = this.dataset.lease;
                document.getElementById('terminateReason').value  = '';
                new bootstrap.Modal(document.getElementById('terminateModal')).show();
            });
        });

        /* ── Submit terminate request ────────────────────────────────── */
        document.getElementById("confirmTerminate").addEventListener("click", function() {
            const leaseId = document.getElementById("terminateLeaseId").value;
            const reason  = document.getElementById("terminateReason").value.trim();
            if (!reason) {
                Swal.fire({ icon:'warning', title:'Missing Input', text:'Please enter a reason.', confirmButtonColor:'#8d0b41' });
                return;
            }
            const submitBtn = this;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

            fetch("update-lease.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `lease_id=${leaseId}&action=terminate&reason=${encodeURIComponent(reason)}`
            })
            .then(r => r.json())
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('terminateModal')).hide();
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Request';
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Request Sent!' : 'Error!',
                    text: data.message || (data.success ? 'Your termination request has been sent to your landlord.' : 'Something went wrong.'),
                    confirmButtonColor: '#8d0b41'
                }).then(() => { if (data.success) location.reload(); });
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Request';
                Swal.fire({ icon:'error', title:'Network Error', text:'Please check your connection.', confirmButtonColor:'#8d0b41' });
            });
        });

    });
    </script>
</body>
</html>