<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

// Fetch leases for this tenant
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
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f6f8;
        }

        .container {
            margin-top: 130px;
        }

        .box {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #ddd;
        }

        table th {
            text-align: center;
        }

        table td {
            vertical-align: middle;
        }

        .apartment-name {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .apartment-price {
            color: #007bff;
            font-weight: 600;
        }

        .status-active {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #198754;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #198754;
            border-radius: 50%;
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.6rem;
        }

        /* --- Match tenant/property page style --- */
        .prorperty-details.modal-content {
            background-color: var(--bg-color, #fff);
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
            padding: 0;
            border: none;
        }

        .prorperty-details.modal-content .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 1rem 1.5rem;
        }

        .prorperty-details.modal-content .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: #333;
        }

        .prorperty-details.modal-content .btn-close {
            background: none;
            border: none;
        }

        .prorperty-details.modal-content .modal-body {
            font-size: 1rem;
            color: #555;
            padding: 1rem 1.5rem;
            line-height: 1.6;
        }

        .prorperty-details.modal-content .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .prorperty-details.modal-content .modal-footer .main-button {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .prorperty-details.modal-content .modal-footer .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .prorperty-details.modal-content .modal-footer .btn-danger:hover {
            background-color: #b02a37;
        }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="container">
        <div class="box">
            <h4 class="mb-4">My Rentals</h4>

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
                                <td class="apartment-price">₱<?= number_format($l['price']); ?>.00</td>
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
                                    <?php if ($l['lease_status'] === 'pending' && $l['tenant_response'] !== 'rejected'): ?>
                                        <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank"
                                            class="btn btn-primary btn-sm">View Contract</a>

                                        <form action="tenant-accept.php" method="POST" class="d-inline">
                                            <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                            <button class="btn btn-success btn-sm">Accept</button>
                                        </form>

                                        <form action="tenant-reject.php" method="POST" class="d-inline"
                                            onsubmit="return confirm('Reject this lease agreement?');">
                                            <input type="hidden" name="lease_id" value="<?= $l['lease_id']; ?>">
                                            <button class="btn btn-danger btn-sm">Reject</button>
                                        </form>

                                    <?php elseif ($l['lease_status'] === 'active'): ?>
                                        <a href="<?= htmlspecialchars($l['pdf_path']); ?>" target="_blank"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-file-earmark-pdf"></i> Contract
                                        </a>

                                        <a href="rent-payment.php?lease_id=<?= $l['lease_id']; ?>"
                                            class="btn btn-success btn-sm">Pay Rent</a>

                                        <button class="btn btn-warning btn-sm renew-btn" data-lease="<?= $l['lease_id']; ?>">🔄
                                            Renew</button>
                                        <button class="btn btn-danger btn-sm terminate-btn" data-lease="<?= $l['lease_id']; ?>">❌
                                            Terminate</button>

                                    <?php elseif ($l['tenant_response'] === 'rejected'): ?>
                                        <button class="btn btn-danger btn-sm remove-btn"
                                            data-lease="<?= $l['lease_id']; ?>">Remove</button>
                                    <?php else: ?>
                                        <span class="text-muted">No action</span>
                                    <?php endif; ?>
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

    <!-- Remove Lease Modal -->
    <div class="modal fade" id="removeLeaseModal" tabindex="-1" aria-labelledby="removeLeaseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content prorperty-details">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeLeaseModalLabel">Remove Lease?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    Are you sure you want to remove this lease? <br>
                    <strong>Note:</strong> This will also remove the request from the landlord’s view.
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="main-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRemoveLease" class="main-button btn-danger">Yes, Remove</button>
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
            document.getElementById('confirmRemoveLease').addEventListener('click', () => {
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