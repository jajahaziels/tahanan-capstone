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
$maintSql = "
    SELECT status, created_at
    FROM maintenance_requeststbl
    WHERE tenant_id = ?
    ORDER BY created_at DESC
    LIMIT 1
";
$maintStmt = $conn->prepare($maintSql);
$maintStmt->bind_param("i", $tenant_id);
$maintStmt->execute();
$maintenance = $maintStmt->get_result()->fetch_assoc();


$complaintSql = "
    SELECT status, created_at
    FROM maintenance_requeststbl
    WHERE tenant_id = ?
    ORDER BY created_at DESC
    LIMIT 1
";
$complaintStmt = $conn->prepare($complaintSql);
$complaintStmt->bind_param("i", $tenant_id);
$complaintStmt->execute();
$complaint = $complaintStmt->get_result()->fetch_assoc();

$complaintStatus = $complaint['status'] ?? 'No Request Yet';
$complaintDate = isset($complaint['created_at'])
    ? date("F d, Y", strtotime($complaint['created_at']))
    : 'No Request Yet';

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

        .complaint-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            border: 1px solid #ddd;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            position: relative;
        }

        .complaint-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            border-radius: 14px 0 0 14px;
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .complaint-header h5 {
            font-weight: 600;
            margin: 0;
        }

        .complaint-body p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .complaint-status {
            font-weight: 500;
        }

        .complaint-btn {
            background: #ac1152;
            color: #fff;
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 0.85rem;
            text-decoration: none;
            border: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .complaint-btn:hover {
            background: #8d0b41;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(172, 17, 82, 0.3);
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

            <!-- Maintenance -->
            <div class="dashboard-card card-maintenance">
                <h5>Maintenance Request</h5>
                <p><strong>Your Last Request:</strong>
                    <?= isset($maintenance['created_at']) ? date("F d, Y", strtotime($maintenance['created_at'])) : 'No Request Yet'; ?>
                </p>
                <button class="card-btn" onclick="window.location.href='maintenance-create.php'" style="color:#000;">
                    Create New
                </button>
            </div>
        </div>

        <!-- My Rentals Table -->
        <div class="mt-5">
            <div class="box">
                <h3 class="section-title">My Rentals</h3>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
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
                                    <td class="apartment-name"><?= htmlspecialchars($l['listingName']); ?></td>
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
                                                    echo '<span class="status-active"><span class="status-dot"></span> Active</span>';
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
                                                <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank" class="btn btn-primary btn-sm"
                                                    title="View Contract">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                                <form action="tenant-accept.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                                    <button class="btn btn-success btn-sm" title="Accept">
                                                        <i class="bi bi-check2-circle"></i>
                                                    </button>
                                                </form>
                                                <form action="tenant-reject.php" method="POST" class="d-inline"
                                                    onsubmit="return confirm('Reject this lease agreement?');">
                                                    <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                                    <button class="btn btn-danger btn-sm" title="Reject">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($l['lease_status'] === 'active'): ?>
                                                <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank" class="btn btn-outline-primary btn-sm"
                                                    title="View Contract">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                                
                                                <button class="btn btn-warning btn-sm renew-btn" data-lease="<?= $l['lease_id']; ?>" title="Renew">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm terminate-btn" data-lease="<?= $l['lease_id']; ?>" title="Terminate">
                                                    <i class="bi bi-x-square"></i>
                                                </button>
                                            <?php elseif ($l['tenant_response'] === 'rejected'): ?>
                                                <button class="btn btn-danger btn-sm remove-btn" data-lease="<?= $l['lease_id']; ?>" title="Remove">
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
                                <td colspan="6" class="text-center text-muted">No rentals available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

                        <!-- Complaints Status Card -->
<div class="mt-4">
    <div class="complaint-card">
        <div class="complaint-header">
            <h3 class="section-title">Complaints Status</h3>
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
                                            <?php
                                            $statusClass = match ($complaintStatus) {
                                                'pending' => 'text-warning',
                                                'in_progress' => 'text-primary',
                                                'resolved' => 'text-success',
                                                'rejected' => 'text-danger',
                                                default => 'text-muted'
                                            };
                                            ?>
                                            <span class="<?= $statusClass; ?>">
                                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($complaintStatus))); ?>
                                            </span>
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