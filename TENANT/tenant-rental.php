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
   FETCH ACTIVE LEASE
========================= */
$leaseSql = "
    SELECT ID, start_date, end_date, pdf_path, listing_id
    FROM leasetbl
    WHERE tenant_id = ?
      AND status = 'active'
    ORDER BY end_date DESC
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

/* =========================
   FETCH RENT PAYMENT (ONLY IF LEASE EXISTS)
========================= */
if ($lease) {
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
    $paymentStmt->bind_param(
        "ii",
        $tenant_id,
        $lease['ID']
    );
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
        $totalPaid = number_format($payment['total_paid'] ?? 0, 2);
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
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
        END,
        created_at DESC
";

$maintenanceStmt = $conn->prepare($maintenanceSql);
$maintenanceStmt->bind_param("i", $tenant_id);
$maintenanceStmt->execute();
$maintenanceResult = $maintenanceStmt->get_result();
$maintenanceRequests = $maintenanceResult->fetch_all(MYSQLI_ASSOC);

/* =========================
   UNREAD / PENDING COUNTER
========================= */

$notificationCount = 0;

foreach ($maintenanceRequests as $req) {
    if ($req['status'] === 'Pending' || $req['status'] === 'In Progress') {
        $notificationCount++;
    }
}

/* =========================
   FETCH ALL LEASES (TABLE)
========================= */
$sql = "
SELECT
    le.ID AS lease_id,
    le.start_date,
    le.end_date,
    le.status AS lease_status,
    le.pdf_path,
    le.tenant_response,
    ls.listingName,
    ls.price
FROM leasetbl le
JOIN listingtbl ls ON le.listing_id = ls.ID
WHERE le.tenant_id = ?
ORDER BY le.ID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$leases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


function getMaintenanceStatus($status, $created_at = null) {
    // Handle overdue for Pending requests
    if ($status === 'Pending' && $created_at) {
        $created = new DateTime($created_at);
        $now = new DateTime();
        $days = $created->diff($now)->days;

        if ($days > 3) {
            return '<span class="badge bg-danger">OVERDUE</span>';
        }
    }

    return match (strtolower($status)) {
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'scheduled', 'in progress' => '<span class="badge bg-primary">Scheduled</span>',
        'completed', 'resolved' => '<span class="badge bg-success">Completed</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        default => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>'
    };
}

function getPriorityBadge($priority) {
    return match ($priority) {
        'Low' => '<span class="badge bg-success">Low</span>',
        'Medium' => '<span class="badge bg-warning text-dark">Medium</span>',
        'High' => '<span class="badge bg-danger">High</span>',
        'Urgent' => '<span class="badge bg-dark">Urgent</span>',
        default => '<span class="badge bg-secondary">'.$priority.'</span>'
    };
}

// Latest maintenance request
$complaintDate = '-';
$complaintStatus = 'No requests yet';

if (!empty($maintenanceRequests)) {
    $latestRequest = $maintenanceRequests[0]; // Already ordered by priority + date
    $complaintDate = date("F j, Y", strtotime($latestRequest['created_at']));
    $complaintStatus = $latestRequest['status'];
}


?>

 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Font: Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">


    <style>
        :root {
            --primary-color: #8d0b41;
        }

        /* FORCE Montserrat everywhere (Bootstrap override fix) */
        body,
        h1, h2, h3, h4, h5, h6,
        p, span, a, button, input, textarea, label, div {
            font-family: "Montserrat", sans-serif;
        }

        body {
            background: #f5f6f8;
            font-size: 14px;
            color: #333;
        }

        .container {
            margin-top: 130px;
            margin-bottom: 50px;
        }

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
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            position: relative;
        }

        .dashboard-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            border-radius: 14px 0 0 14px;
        }

        .card-rent::before {
            background: linear-gradient(to bottom, #56ab2f, #a8e063);
        }

        .card-lease::before {
            background: linear-gradient(to bottom, #f7971e, #ffd200);
        }

        .card-maintenance::before {
            background: linear-gradient(to bottom, #f00000, #dc281e);
        }

        .card-rent {
            background: #EAEFEF;
            color: #25343F;
        }

        .card-lease {
            background: #fff5dc;
            border: 1px solid #f0e1b5;
        }

        .card-maintenance {
            background: #fdeeee;
            border: 1px solid #f4caca;
        }

        .dashboard-card h5 {
            font-weight: 600;
            margin-bottom: 15px;
        }

        .dashboard-card p {
            margin-bottom: 8px;
            font-size: 0.95rem;
            font-weight: 400;
        }

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

        .card-btn:hover {
            background: currentColor;
            color: white !important;
        }

        .box {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            border: 1px solid #ddd;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            position: relative;
        }

        .apartment-name {
            font-weight: 600;
        }

        .status-active {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #198754;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #198754;
            border-radius: 50%;
        }

/* ============================= */
/* IMPROVED COMPLAINT STATUS CARD */
/* ============================= */

.complaint-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
    margin-top: 30px;
    transition: all 0.3s ease;
}

.complaint-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

/* Header */
.complaint-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.complaint-header h3 {
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
    color: #25343F;
}

/* Body */
.complaint-body {
    font-size: 0.95rem;
    color: #4a5568;
}

.complaint-body p {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Status Badge Style */
.complaint-status span {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Custom status colors (more modern than Bootstrap text-*) */
.text-warning {
    background: #fff7ed;
    color: #c2410c !important;
}

.text-primary {
    background: #eff6ff;
    color: #1d4ed8 !important;
}

.text-success {
    background: #ecfdf5;
    color: #047857 !important;
}

.text-danger {
    background: #fef2f2;
    color: #b91c1c !important;
}

.text-muted {
    background: #f3f4f6;
    color: #6b7280 !important;
}

/* Button improvement */
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
    box-shadow: 0 6px 16px rgba(141, 11, 65, 0.3);
}


        /* FINAL FONT OVERRIDE FOR BOOTSTRAP COMPONENTS */
.table,
.table th,
.table td,
.table thead th,
.badge,
.btn,
.card,
.card h5,
.card h4,
.complaint-card,
.complaint-card h5,
.complaint-card p,
.dashboard-card,
.dashboard-card h5,
.dashboard-card p {
    font-family: "Montserrat", sans-serif !important;
}

/* VISUAL CONFIRMATION + TYPOGRAPHY TUNING */
.table th {
    font-weight: 600;
    letter-spacing: 0.3px;
}

.table td {
    font-weight: 400;
}

.badge {
    font-weight: 500;
    letter-spacing: 0.4px;
}

.btn {
    font-weight: 500;
}

/* ============================= */
/* RENTALS PAGE STYLES */
/* ============================= */
.rentals-section {
    background: #ffffff;
    border-radius: 14px;
    padding: 22px;
    border: 1px solid #ddd;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
    position: relative;
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

.rentals-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.rentals-title i {
    color: #8d0b41;
}

/* Modern Table */
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
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
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

.rentals-table .btn-sm {
    border-radius: 8px;
    padding: 6px 10px;
}

.complaint-card h5,
.box h4 {
    font-weight: 600;
    letter-spacing: 0.2px;
}

/* MAKE MONTSERRAT VISUALLY DISTINCT */

/* Section titles */
h4, h5 {
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Dashboard labels */
.dashboard-card p strong,
.complaint-body strong {
    font-weight: 600;
}

/* Table headers */
.table th {
    font-weight: 600;
    letter-spacing: 0.4px;
    font-size: 0.9rem;
}

/* Table body */
.table td {
    font-weight: 400;
    font-size: 0.9rem;
}

/* Status text */
.status-active,
.complaint-status {
    font-weight: 500;
}

/* Buttons */
.btn,
.card-btn,
.complaint-btn {
    font-weight: 500;
    letter-spacing: 0.2px;
}

.section-title {
    font-family: "Montserrat", sans-serif !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.3px !important;
    color: #25343F;
    margin-bottom: 16px;
}

.alert-success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Your maintenance request has been submitted successfully!
            </div>
        <?php endif; ?>

        <div class="dashboard-cards">

            <!-- Rent Payment -->
            <div class="dashboard-card card-rent">
                <h5>Rent Payment Status</h5>
                <?php if (!$lease): ?>
                    <p class="text-muted">No active lease yet</p>
                <?php else: ?>
                    <p><strong>Last Paid:</strong> <?= $lastPaid ?></p>
                    <p><strong>Due Date:</strong> <?= $dueDate ?></p>
                    <p><strong>Expected Amount Due:</strong> ₱<?= $expectedAmount ?></p>
                    <p><strong>Total Rent Payment:</strong> ₱<?= $totalPaid ?></p>
                    <button class="card-btn" style="color:#000;">View History</button>
                <?php endif; ?>
            </div>

            <!-- Lease Agreement -->
            <div class="dashboard-card card-lease">
                <h5>Lease Agreement</h5>
                <p><strong>Lease End:</strong>
                    <?= isset($lease['end_date']) ? date("F Y", strtotime($lease['end_date'])) : '-' ?>
                </p>
                <?php if (!empty($lease['pdf_path'])): ?>
                    <a href="<?= htmlspecialchars($lease['pdf_path']) ?>" class="card-btn" target="_blank"
                        style="color: #000;">View Contract</a>
                <?php else: ?>
                    <span class="card-btn" style="color: #999; cursor: not-allowed;">No Contract</span>
                <?php endif; ?>
                <?php foreach ($leases as $l): ?>
                    <button class="card-btn ms-2"
                        onclick="window.location.href='tenant-apartment-details.php?lease_id=<?= $l['lease_id'] ?>'">View
                        Apartment Details</button>
                <?php endforeach; ?>
            </div>

            <!-- Remove Lease Modal -->
<div class="modal fade" id="removeLeaseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Remove Lease</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body">
        Are you sure you want to remove this lease?
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmRemoveLease" class="btn btn-danger">
            Yes, Remove
        </button>
      </div>

    </div>
  </div>
</div>

            <!-- Maintenance -->
            <div class="dashboard-card card-maintenance">
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
        <p>
            <?= getMaintenanceStatus(
                $maintenanceRequests[0]['status'],
                $maintenanceRequests[0]['created_at']
            ); ?>
        </p>
    <?php endif; ?>

    <button class="card-btn"
        onclick="window.location.href='maintenance-create.php'"
        style="color:#000;">
        Create New
    </button>

    <button class="card-btn"
            onclick="window.location.href='maintenance-history.php'"
            style="background:#ddd;color:#000;">
            Maintenance History
        </button>
    </div>

        </div>

<!-- My Rentals Section -->
<div class="rentals-section">

    <div class="rentals-header">
        <h3 class="rentals-title">
            <i class="bi bi-house-door"></i> My Rentals
        </h3>
    </div>

    <table class="rentals-table">
        <thead>
            <tr>
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
                <?php foreach ($leases as $l): ?>
                    <tr data-lease="<?= $l['lease_id']; ?>">
                        
                        <td class="fw-semibold">
                            <?= htmlspecialchars($l['listingName']); ?>
                        </td>

                        <td>₱<?= number_format($l['price']); ?>.00</td>

                        <td><?= date("F j, Y", strtotime($l['start_date'])); ?></td>

                        <td><?= date("F j, Y", strtotime($l['end_date'])); ?></td>

                        <td>
                            <?php
                            if ($l['tenant_response'] === 'rejected') {
                                echo '<span class="badge bg-danger">You Rejected</span>';
                            } else {
                                switch ($l['lease_status']) {
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

                        <td>
                            <div class="d-flex align-items-center gap-2">

                                <?php if ($l['lease_status'] === 'pending' && $l['tenant_response'] !== 'rejected'): ?>

                                    <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>

                                    <form action="tenant-accept.php" method="POST">
                                        <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                        <button class="btn btn-success btn-sm">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>

                                    <form action="tenant-reject.php" method="POST"
                                        onsubmit="return confirm('Reject this lease agreement?');">
                                        <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                        <button class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>

                                <?php elseif ($l['lease_status'] === 'active'): ?>

                                    <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>

                                    <button class="btn btn-warning btn-sm renew-btn"
                                        data-lease="<?= $l['lease_id']; ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm terminate-btn"
                                        data-lease="<?= $l['lease_id']; ?>">
                                        <i class="bi bi-x-square"></i>
                                    </button>

                                <?php elseif ($l['tenant_response'] === 'rejected'): ?>

                                    <button class="btn btn-danger btn-sm remove-btn"
                                        data-lease="<?= $l['lease_id']; ?>">
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
                    <td colspan="6" class="text-center text-muted">
                        No rentals available
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

                        <!-- Complaints Status Card -->
           <div class="mt-4">
    <div class="complaint-card">
        <div class="complaint-header d-flex justify-content-between align-items-center">
            <h3 class="section-title">
                <i class="bi bi-tools" style="color: #8d0b41; font-size: 1.5rem; margin-right: 8px;"></i>
                Maintenance Request
            </h3>
            <a href="maintenance-create.php" class="complaint-btn">
                File New Complaint
            </a>
        </div>

        <div class="complaint-body">
            <p>
                <strong>Date:</strong>
                <?= $complaintDate; ?>
                                    </p>
                        
                                    <p>
                                        <i class="bi bi-exclamation-circle text-warning"></i>
                                        Your last complaint:
                                        <span class="complaint-status">
                                            <?= htmlspecialchars($complaintStatus); ?>
                                        </span>
                                    </p>
                        
                                    <p>
                                        <strong>Status:</strong>
                                        <span class="complaint-status">
                                        <?= getMaintenanceStatus($complaintStatus, $latestRequest['created_at'] ?? null); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
         </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let leaseToRemove = null;

            // Open modal and set lease ID
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    leaseToRemove = btn.dataset.lease;
                    const removeModal = new bootstrap.Modal(document.getElementById('removeLeaseModal'));
                    removeModal.show();
                });
            });

            // Confirm removal
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
                            // Remove row from table
                            const row = document.querySelector(`tr[data-lease='${leaseToRemove}']`);
                            if (row) row.remove();

                            // Hide modal
                            const removeModalEl = document.getElementById('removeLeaseModal');
                            const modal = bootstrap.Modal.getInstance(removeModalEl);
                            modal.hide();

                            leaseToRemove = null;
                        } else {
                            alert("Failed to remove lease: " + msg);
                        }
                    })
                    .catch(err => {
                        alert("Error: " + err);
                    });
            });

            // Renew lease
            document.querySelectorAll('.renew-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const leaseId = btn.dataset.lease;
                    if (!confirm("Renew this lease?")) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&action=renew`
                    }).then(res => res.text()).then(msg => {
                        alert(msg);
                        location.reload();
                    });
                });
            });

            // Terminate lease
            document.querySelectorAll('.terminate-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const leaseId = btn.dataset.lease;
                    if (!confirm("Terminate this lease?")) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&action=terminate`
                    }).then(res => res.text()).then(msg => {
                        alert(msg);
                        location.reload();
                    });
                });
            });
        });
    </script>
</body>

</html>